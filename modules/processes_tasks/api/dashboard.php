<?php
/**
 * API Dashboard - Processes & Tasks
 *
 * Endpoint JSON para widgets/analytics del módulo.
 * Contrato:
 *   { ok: true, stats: {...}, tasks: [...] }
 */

if (ob_get_level()) {
    @ob_clean();
}

require __DIR__ . '/../../../bootstrap.php';
require __DIR__ . '/../../../core/auth.php';
require __DIR__ . '/../../../core/permissions.php';
require __DIR__ . '/../../../core/scope.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requireLogin();

    $companyId = (int)currentUserCompany();
    $userId = (int)currentUserId();

    if (!hasPermission($companyId, 'processes_tasks', 'view')) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Sin permisos'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = db();

    // Rol de compañía (superadmin vs admin vs usuario)
    $stmtRole = $pdo->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ? AND status = 'active' LIMIT 1");
    $stmtRole->execute([$userId, $companyId]);
    $companyRole = (string)($stmtRole->fetchColumn() ?: '');
    $isSuperadmin = in_array($companyRole, ['root', 'superadmin'], true);
    $isAdmin = ($companyRole === 'admin');

    // Base de acceso: company_id SIEMPRE, tipo='Tarea' SIEMPRE en dashboard
    $where = ['t.company_id = ?', "t.tipo = 'Tarea'"];
    $params = [$companyId];

    // Scope unit/business: superadmin lo ignora; cualquier otro rol (incl admin) lo aplica
    if (!$isSuperadmin) {
        $scope = getUserScope($userId, $companyId);
        if (($scope['type'] ?? '') === 'limited') {
            if (!empty($scope['units'])) {
                $ph = implode(',', array_fill(0, count($scope['units']), '?'));
                $where[] = "t.unit_id IN ($ph)";
                $params = array_merge($params, $scope['units']);
            }
            if (!empty($scope['businesses'])) {
                $ph = implode(',', array_fill(0, count($scope['businesses']), '?'));
                $where[] = "t.business_id IN ($ph)";
                $params = array_merge($params, $scope['businesses']);
            }
        }
    }

    // Organigrama: solo para no-admin/superadmin
    if (!$isSuperadmin && !$isAdmin) {
        $stmtAllowed = $pdo->prepare('SELECT target_user_id FROM processes_tasks_org_access WHERE company_id = ? AND owner_user_id = ?');
        $stmtAllowed->execute([$companyId, $userId]);
        $allowedUserIds = array_map('intval', $stmtAllowed->fetchAll(PDO::FETCH_COLUMN) ?: []);

        // Compatibilidad: algunas instancias guardan hr_employees.id en usuario_delegado
        $allowedAssigneeIds = $allowedUserIds;
        if (!empty($allowedUserIds)) {
            $ph = implode(',', array_fill(0, count($allowedUserIds), '?'));
            $stmtEmp = $pdo->prepare("SELECT id FROM hr_employees WHERE company_id = ? AND user_id IN ($ph)");
            $stmtEmp->execute(array_merge([$companyId], $allowedUserIds));
            $empIds = array_map('intval', $stmtEmp->fetchAll(PDO::FETCH_COLUMN) ?: []);
            $allowedAssigneeIds = array_values(array_unique(array_merge($allowedAssigneeIds, $empIds)));
        }

        if (empty($allowedAssigneeIds)) {
            $where[] = 't.usuario_creador = ?';
            $params[] = $userId;
        } else {
            $ph = implode(',', array_fill(0, count($allowedAssigneeIds), '?'));
            $where[] = "(t.usuario_creador = ? OR t.usuario_delegado IN ($ph))";
            $params[] = $userId;
            $params = array_merge($params, $allowedAssigneeIds);
        }
    }

    $whereSql = implode(' AND ', $where);

    // Compatibilidad: el usuario actual podría existir como hr_employees.id en registros legacy
    $myIds = [$userId];
    try {
        $stmtMe = $pdo->prepare('SELECT id FROM hr_employees WHERE company_id = ? AND user_id = ? LIMIT 1');
        $stmtMe->execute([$companyId, $userId]);
        $myEmpId = (int)($stmtMe->fetchColumn() ?: 0);
        if ($myEmpId > 0) {
            $myIds[] = $myEmpId;
        }
    } catch (Throwable $e) {
        // noop
    }
    $myIds = array_values(array_unique(array_filter(array_map('intval', $myIds), fn ($v) => $v > 0)));

    $stats = [
        'total' => 0,
        'pending' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'overdue' => 0,
        'assigned_to_me' => 0,
        'delegated_by_me' => 0,
    ];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks t WHERE $whereSql");
    $stmt->execute($params);
    $stats['total'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT t.status, COUNT(*) AS c FROM tasks t WHERE $whereSql GROUP BY t.status");
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count = (int)($row['c'] ?? 0);
        $status = (string)($row['status'] ?? '');

        if ($status === 'En proceso' || $status === 'En tiempo') {
            $stats['pending'] += $count;
            $stats['in_progress'] += $count;
        } elseif ($status === 'Vencida') {
            $stats['overdue'] += $count;
        } elseif ($status === 'Terminada' || $status === 'Auditada') {
            $stats['completed'] += $count;
        }
    }

    if (count($myIds) === 1) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks t WHERE $whereSql AND t.usuario_delegado = ?");
        $stmt->execute(array_merge($params, [$myIds[0]]));
    } else {
        $ph = implode(',', array_fill(0, count($myIds), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks t WHERE $whereSql AND t.usuario_delegado IN ($ph)");
        $stmt->execute(array_merge($params, $myIds));
    }
    $stats['assigned_to_me'] = (int)$stmt->fetchColumn();

    if (count($myIds) === 1) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks t WHERE $whereSql AND t.usuario_creador = ?");
        $stmt->execute(array_merge($params, [$myIds[0]]));
    } else {
        $ph = implode(',', array_fill(0, count($myIds), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks t WHERE $whereSql AND t.usuario_creador IN ($ph)");
        $stmt->execute(array_merge($params, $myIds));
    }
    $stats['delegated_by_me'] = (int)$stmt->fetchColumn();

    // Tareas recientes (para widgets)
    $stmt = $pdo->prepare("
        SELECT
            t.id,
            t.folio,
            t.titulo AS title,
            t.status,
            t.nivel,
            t.fecha_entrega AS due_date,
            t.created_at,
            COALESCE(NULLIF(ec.full_name, ''), NULLIF(ec_legacy.full_name, ''), CONCAT('Usuario #', t.usuario_creador)) AS creador_nombre,
            CASE
                WHEN t.usuario_delegado IS NULL THEN NULL
                ELSE COALESCE(NULLIF(ed.full_name, ''), NULLIF(ed_legacy.full_name, ''), CONCAT('Usuario #', t.usuario_delegado))
            END AS delegado_nombre
        FROM tasks t
        LEFT JOIN hr_employees ec ON ec.user_id = t.usuario_creador AND ec.company_id = t.company_id
        LEFT JOIN hr_employees ed ON ed.user_id = t.usuario_delegado AND ed.company_id = t.company_id
        LEFT JOIN hr_employees ec_legacy ON ec_legacy.id = t.usuario_creador AND ec_legacy.company_id = t.company_id
        LEFT JOIN hr_employees ed_legacy ON ed_legacy.id = t.usuario_delegado AND ed_legacy.company_id = t.company_id
        WHERE $whereSql
          AND t.status NOT IN ('Terminada', 'Auditada')
        ORDER BY t.created_at DESC
        LIMIT 50
    ");
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    echo json_encode([
        'ok' => true,
        'stats' => $stats,
        'tasks' => $tasks,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[processes_tasks:dashboard] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Error interno',
        'stats' => [
            'total' => 0,
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'overdue' => 0,
            'assigned_to_me' => 0,
            'delegated_by_me' => 0,
        ],
        'tasks' => [],
    ], JSON_UNESCAPED_UNICODE);
}
