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

    $ucId = (int)currentUserCompany();
    $userId = (int)currentUserId();

    if (!hasPermission($ucId, 'processes_tasks', 'view')) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Sin permisos'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = db();

    // Rol de compañía (superadmin vs admin vs usuario)
    $stmtRole = $pdo->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ? AND status = 'active' LIMIT 1");
    $stmtRole->execute([$userId, $ucId]);
    $companyRole = (string)($stmtRole->fetchColumn() ?: '');
    $isSuperadmin = in_array($companyRole, ['root', 'superadmin'], true);
    $isAdmin = ($companyRole === 'admin');

    // Base de acceso: company_id SIEMPRE, tipo='Tarea' SIEMPRE en dashboard
    $where = ['company_id = ?', "tipo = 'Tarea'"];
    $paramsBase = [$ucId];

    // Scope unit/business: superadmin lo ignora; cualquier otro rol (incl admin) lo aplica
    if (!$isSuperadmin) {
        $scope = getUserScope($userId, $ucId);
        if (($scope['type'] ?? '') === 'limited') {
            if (!empty($scope['units'])) {
                $ph = implode(',', array_fill(0, count($scope['units']), '?'));
                $where[] = "unit_id IN ($ph)";
                $paramsBase = array_merge($paramsBase, $scope['units']);
            }
            if (!empty($scope['businesses'])) {
                $ph = implode(',', array_fill(0, count($scope['businesses']), '?'));
                $where[] = "business_id IN ($ph)";
                $paramsBase = array_merge($paramsBase, $scope['businesses']);
            }
        }
    }

    // Organigrama: solo para no-admin/superadmin
    if (!$isSuperadmin && !$isAdmin) {
        $stmtAllowed = $pdo->prepare('SELECT target_user_id FROM processes_tasks_org_access WHERE company_id = ? AND owner_user_id = ?');
        $stmtAllowed->execute([$ucId, $userId]);
        $allowedUserIds = array_map('intval', $stmtAllowed->fetchAll(PDO::FETCH_COLUMN) ?: []);

        $allowedAssigneeIds = $allowedUserIds;
        if (!empty($allowedUserIds)) {
            $ph = implode(',', array_fill(0, count($allowedUserIds), '?'));
            $paramsEmp = array_merge([$ucId], $allowedUserIds);
            $stmtEmp = $pdo->prepare("SELECT id FROM hr_employees WHERE company_id = ? AND user_id IN ($ph)");
            $stmtEmp->execute($paramsEmp);
            $empIds = array_map('intval', $stmtEmp->fetchAll(PDO::FETCH_COLUMN) ?: []);
            $allowedAssigneeIds = array_values(array_unique(array_merge($allowedAssigneeIds, $empIds)));
        }

        if (empty($allowedAssigneeIds)) {
            $where[] = 'usuario_creador = ?';
            $paramsBase[] = $userId;
        } else {
            $ph = implode(',', array_fill(0, count($allowedAssigneeIds), '?'));
            $where[] = "(usuario_creador = ? OR usuario_delegado IN ($ph))";
            $paramsBase[] = $userId;
            $paramsBase = array_merge($paramsBase, $allowedAssigneeIds);
        }
    }

    $whereSql = implode(' AND ', $where);

    $stats = [
        'total' => 0,
        'pending' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'overdue' => 0,
        'assigned_to_me' => 0,
        'delegated_by_me' => 0,
    ];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE $whereSql");
    $stmt->execute($paramsBase);
    $stats['total'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT status, COUNT(*) AS c FROM tasks WHERE $whereSql GROUP BY status");
    $stmt->execute($paramsBase);
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

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE $whereSql AND usuario_delegado = ?");
    $stmt->execute(array_merge($paramsBase, [$userId]));
    $stats['assigned_to_me'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE $whereSql AND usuario_creador = ?");
    $stmt->execute(array_merge($paramsBase, [$userId]));
    $stats['delegated_by_me'] = (int)$stmt->fetchColumn();

    $whereSqlT = str_replace(
        ['company_id', 'tipo', 'unit_id', 'business_id', 'usuario_creador', 'usuario_delegado'],
        ['t.company_id', 't.tipo', 't.unit_id', 't.business_id', 't.usuario_creador', 't.usuario_delegado'],
        $whereSql
    );

    $stmt = $pdo->prepare("
        SELECT
            t.id,
            t.folio,
            t.titulo AS title,
            t.status,
            t.fecha_entrega AS due_date,
            t.created_at,
            ed.full_name AS delegado_nombre,
            ec.full_name AS creador_nombre
        FROM tasks t
        LEFT JOIN hr_employees ec ON ec.user_id = t.usuario_creador AND ec.company_id = t.company_id
        LEFT JOIN hr_employees ed ON ed.user_id = t.usuario_delegado AND ed.company_id = t.company_id
				WHERE $whereSqlT
                    AND t.status NOT IN ('Terminada', 'Auditada')
        ORDER BY t.created_at DESC
        LIMIT 50
    ");
    $stmt->execute($paramsBase);
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

// NOTE: En algunos despliegues este archivo quedó con contenido duplicado al final.
// `__halt_compiler()` asegura que lo que siga NO se compile/ejecute.
__halt_compiler();

<?php
/**
 * API Dashboard - Processes & Tasks
 *
 * Endpoint JSON simple para analytics/widgets del módulo.
 * Contrato estable:
 *   { ok: true, stats: {...}, tasks: [...] }
 */

if (ob_get_level()) {
    @ob_clean();
}

require __DIR__ . '/../../../bootstrap.php';
require __DIR__ . '/../../../core/auth.php';
require __DIR__ . '/../../../core/permissions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requireLogin();

    $ucId = (int)currentUserCompany();
    $userId = (int)currentUserId();

    if (!hasPermission($ucId, 'processes_tasks', 'view')) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Sin permisos'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = db();

    $stats = [
        'total' => 0,
        'pending' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'overdue' => 0,
        'assigned_to_me' => 0,
        'delegated_by_me' => 0,
    ];

    // Total de tareas (tipo=Tarea)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE company_id = ? AND tipo = 'Tarea'");
    $stmt->execute([$ucId]);
    $stats['total'] = (int)$stmt->fetchColumn();

    // Conteo por estado
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as c FROM tasks WHERE company_id = ? AND tipo = 'Tarea' GROUP BY status");
    $stmt->execute([$ucId]);
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

    // Tareas del usuario (asignadas / creadas)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE company_id = ? AND tipo = 'Tarea' AND usuario_delegado = ?");
    $stmt->execute([$ucId, $userId]);
    $stats['assigned_to_me'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE company_id = ? AND tipo = 'Tarea' AND usuario_creador = ?");
    $stmt->execute([$ucId, $userId]);
    $stats['delegated_by_me'] = (int)$stmt->fetchColumn();

    // Tareas recientes (para widgets)
    $stmt = $pdo->prepare("
        SELECT
            t.id,
            t.folio,
            t.titulo AS title,
            t.status,
            t.fecha_entrega AS due_date,
            t.created_at,
            ed.full_name AS delegado_nombre,
            ec.full_name AS creador_nombre
        FROM tasks t
        LEFT JOIN hr_employees ec ON ec.user_id = t.usuario_creador AND ec.company_id = t.company_id
        LEFT JOIN hr_employees ed ON ed.user_id = t.usuario_delegado AND ed.company_id = t.company_id
        WHERE t.company_id = ?
          AND t.tipo = 'Tarea'
          AND (t.usuario_delegado = ? OR t.usuario_creador = ?)
          AND t.status NOT IN ('Terminada', 'Auditada')
        ORDER BY t.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$ucId, $userId, $userId]);
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

        require __DIR__ . '/../../../bootstrap.php';
        require __DIR__ . '/../../../core/auth.php';
        require __DIR__ . '/../../../core/permissions.php';

        header('Content-Type: application/json; charset=utf-8');

        try {
            requireLogin();

            $ucId = currentUserCompany();
            $userId = currentUserId();

            if (!hasPermission($ucId, 'processes_tasks', 'view')) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Sin permisos']);
                exit;
            }

            $pdo = db();

            $stats = [
                'total' => 0,
                'pending' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'overdue' => 0,
                'assigned_to_me' => 0,
                'delegated_by_me' => 0,
            ];

            // Total de tareas (tipo=Tarea)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE company_id = ? AND tipo = 'Tarea'");
            $stmt->execute([$ucId]);
            $stats['total'] = (int)$stmt->fetchColumn();

            // Conteo por estado
            $stmt = $pdo->prepare("SELECT status, COUNT(*) as c FROM tasks WHERE company_id = ? AND tipo = 'Tarea' GROUP BY status");
            $stmt->execute([$ucId]);
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

            // Tareas del usuario (asignadas / creadas)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE company_id = ? AND tipo = 'Tarea' AND usuario_delegado = ?");
            $stmt->execute([$ucId, $userId]);
            $stats['assigned_to_me'] = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE company_id = ? AND tipo = 'Tarea' AND usuario_creador = ?");
            $stmt->execute([$ucId, $userId]);
            $stats['delegated_by_me'] = (int)$stmt->fetchColumn();

            // Tareas recientes (para widgets) — conservar keys usadas por dashboard.js
            $stmt = $pdo->prepare("
                SELECT
                    t.id,
                    t.folio,
                    t.titulo AS title,
                    t.status,
                    t.fecha_entrega AS due_date,
                    t.created_at,
                    ed.full_name AS delegado_nombre,
                    ec.full_name AS creador_nombre
                FROM tasks t
                LEFT JOIN hr_employees ec ON ec.user_id = t.usuario_creador AND ec.company_id = t.company_id
                LEFT JOIN hr_employees ed ON ed.user_id = t.usuario_delegado AND ed.company_id = t.company_id
                WHERE t.company_id = ?
                  AND t.tipo = 'Tarea'
                  AND (t.usuario_delegado = ? OR t.usuario_creador = ?)
                  AND t.status NOT IN ('Terminada', 'Auditada')
                ORDER BY t.created_at DESC
                LIMIT 50
            ");
            $stmt->execute([$ucId, $userId, $userId]);
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
