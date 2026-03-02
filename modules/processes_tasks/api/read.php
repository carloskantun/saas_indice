<?php
require __DIR__.'/../../../bootstrap.php';
require __DIR__.'/../../../core/auth.php';
require __DIR__.'/../../../core/permissions.php';
require __DIR__.'/../../../core/scope.php';
require __DIR__.'/../includes/enum_normalizer.php';
header('Content-Type: application/json; charset=utf-8');

requireLogin();
$ucId = (int)currentUserCompany();
$userId = (int)currentUserId();

if (!hasPermission($ucId, 'processes_tasks', 'view')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'no_permission']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_id']);
    exit;
}

try {
    $pdo = db();

    $colExists = static function (PDO $pdo, string $table, string $column): bool {
        try {
            $st = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
            $st->execute([$column]);
            return (bool)$st->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return false;
        }
    };

    $hasEmpUserCompanyId = $colExists($pdo, 'hr_employees', 'user_company_id');
    $hasUsersName = $colExists($pdo, 'users', 'name');

    // Rol de compañía
    $stmtRole = $pdo->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ? AND status = 'active' LIMIT 1");
    $stmtRole->execute([$userId, $ucId]);
    $companyRole = (string)($stmtRole->fetchColumn() ?: '');
    $isSuperadmin = in_array($companyRole, ['root', 'superadmin'], true);
    $isAdmin = ($companyRole === 'admin');

    // Cargar tarea base
    $stmtTask = $pdo->prepare('SELECT id, unit_id, business_id, usuario_creador, usuario_delegado FROM tasks WHERE id=? AND company_id=? LIMIT 1');
    $stmtTask->execute([$id, $ucId]);
    $task = $stmtTask->fetch(PDO::FETCH_ASSOC);
    if (!$task) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'not_found']);
        exit;
    }

    // Scope unit/business: superadmin lo ignora; cualquier otro rol (incl admin) lo aplica
    if (!$isSuperadmin) {
        $scope = getUserScope($userId, $ucId);
        if (($scope['type'] ?? '') === 'limited') {
            if (!empty($scope['units'])) {
                if (!in_array((int)($task['unit_id'] ?? 0), array_map('intval', $scope['units']), true)) {
                    http_response_code(403);
                    echo json_encode(['ok' => false, 'error' => 'forbidden_unit']);
                    exit;
                }
            }
            if (!empty($scope['businesses'])) {
                if (!in_array((int)($task['business_id'] ?? 0), array_map('intval', $scope['businesses']), true)) {
                    http_response_code(403);
                    echo json_encode(['ok' => false, 'error' => 'forbidden_business']);
                    exit;
                }
            }
        }
    }

    // Organigrama: solo para no-admin/superadmin
    if (!$isSuperadmin && !$isAdmin) {
        $stmtAllowed = $pdo->prepare('SELECT target_user_id FROM processes_tasks_org_access WHERE company_id = ? AND owner_user_id = ?');
        $stmtAllowed->execute([$ucId, $userId]);
        $allowedUserIds = array_map('intval', $stmtAllowed->fetchAll(PDO::FETCH_COLUMN) ?: []);

        // Compatibilidad: algunas instancias usan hr_employees.id en usuario_delegado
        $allowedAssigneeIds = $allowedUserIds;
        if (!empty($allowedUserIds)) {
            $ph = implode(',', array_fill(0, count($allowedUserIds), '?'));
            $paramsEmp = array_merge([$ucId], $allowedUserIds);
            $stmtEmp = $pdo->prepare("SELECT id FROM hr_employees WHERE company_id = ? AND user_id IN ($ph)");
            $stmtEmp->execute($paramsEmp);
            $empIds = array_map('intval', $stmtEmp->fetchAll(PDO::FETCH_COLUMN) ?: []);
            $allowedAssigneeIds = array_values(array_unique(array_merge($allowedAssigneeIds, $empIds)));
        }

        $creatorId = (int)($task['usuario_creador'] ?? 0);
        $assigneeId = (int)($task['usuario_delegado'] ?? 0);

        if (empty($allowedAssigneeIds)) {
            if ($creatorId !== $userId) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'forbidden']);
                exit;
            }
        } else {
            if (!($creatorId === $userId || in_array($assigneeId, $allowedAssigneeIds, true))) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'forbidden']);
                exit;
            }
        }
    }

    // Cargar con joins (consistente con list.php)
    $joinEmpUc = '';
    $creatorEmpUcExpr = '';
    $delegateEmpUcExpr = '';
    if ($hasEmpUserCompanyId) {
        $joinEmpUc = "
        LEFT JOIN user_companies ucc ON ucc.user_id = uc.id AND ucc.company_id = t.company_id
        LEFT JOIN hr_employees ec_uc ON ec_uc.company_id = t.company_id AND ec_uc.user_company_id = ucc.id
        LEFT JOIN user_companies ucd ON ucd.user_id = ud.id AND ucd.company_id = t.company_id
        LEFT JOIN hr_employees ed_uc ON ed_uc.company_id = t.company_id AND ed_uc.user_company_id = ucd.id
        ";
        $creatorEmpUcExpr = "NULLIF(ec_uc.full_name, ''),";
        $delegateEmpUcExpr = "NULLIF(ed_uc.full_name, ''),";
    }

    $creatorUsersNameExpr = $hasUsersName ? "NULLIF(uc.name, '')," : '';
    $delegateUsersNameExpr = $hasUsersName ? "NULLIF(ud.name, '')," : '';

    $sql = "
        SELECT 
            t.*,
            b.name AS business_nombre,
            u.name AS unit_nombre,
            uc.email AS creador_email,
            COALESCE(
                NULLIF(ec.full_name, ''),
                {$creatorEmpUcExpr}
                NULLIF(ec_email.full_name, ''),
                NULLIF(uc.full_name, ''),
                {$creatorUsersNameExpr}
                CONCAT('Usuario #', uc.id)
            ) AS creador_nombre,
            COALESCE(ud.email, ed_legacy.email) AS delegado_email,
            CASE
                WHEN ud.id IS NOT NULL THEN COALESCE(
                    NULLIF(ed.full_name, ''),
                    {$delegateEmpUcExpr}
                    NULLIF(ed_email.full_name, ''),
                    NULLIF(ud.full_name, ''),
                    {$delegateUsersNameExpr}
                    CONCAT('Usuario #', ud.id)
                )
                WHEN ed_legacy.id IS NOT NULL THEN COALESCE(
                    NULLIF(ed_legacy.full_name, ''),
                    CONCAT('Empleado #', ed_legacy.id)
                )
                ELSE NULL
            END AS delegado_nombre
        FROM tasks t
        LEFT JOIN businesses b ON b.id = t.business_id
        LEFT JOIN units u ON u.id = t.unit_id
        LEFT JOIN users uc ON uc.id = t.usuario_creador
        LEFT JOIN hr_employees ec ON ec.user_id = t.usuario_creador AND ec.company_id = t.company_id
        LEFT JOIN hr_employees ec_email ON LOWER(TRIM(ec_email.email)) = LOWER(TRIM(uc.email)) AND ec_email.company_id = t.company_id
        LEFT JOIN users ud ON ud.id = t.usuario_delegado
        LEFT JOIN hr_employees ed ON ed.user_id = t.usuario_delegado AND ed.company_id = t.company_id
        LEFT JOIN hr_employees ed_email ON LOWER(TRIM(ed_email.email)) = LOWER(TRIM(ud.email)) AND ed_email.company_id = t.company_id
        LEFT JOIN hr_employees ed_legacy ON ed_legacy.id = t.usuario_delegado AND ed_legacy.company_id = t.company_id
        {$joinEmpUc}
        WHERE t.id = ? AND t.company_id = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id, $ucId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'not_found']);
        exit;
    }

    // Normalizar campos para compatibilidad frontend
    $row['title'] = $row['titulo'] ?? $row['title'] ?? '';
    $row['description'] = $row['descripcion'] ?? $row['description'] ?? '';
    $row['start_date'] = $row['fecha_inicio'] ?? $row['start_date'] ?? '';
    $row['due_date'] = $row['fecha_entrega'] ?? $row['due_date'] ?? '';

    echo json_encode(['ok' => true, 'data' => $row]);
} catch (Throwable $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        error_log('[processes_tasks:read] ' . $e->getMessage());
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server_error']);
}
