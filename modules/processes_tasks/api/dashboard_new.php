<?php
/**
 * API Dashboard - Processes & Tasks
 */
if (ob_get_level()) { ob_clean(); }

require __DIR__.'/../../../bootstrap.php';
require __DIR__.'/../../../core/auth.php';
require __DIR__.'/../../../core/permissions.php';
require __DIR__.'/../../../core/scope.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requireLogin();
    $ucId = (int)currentUserCompany();
    $userId = (int)currentUserId();

    if (!hasPermission($ucId, 'processes_tasks', 'view')) { 
        http_response_code(403); 
        echo json_encode(['ok'=>false,'error'=>'Sin permisos']); 
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
        'delegated_by_me' => 0
    ];
    
    // Rol de compañía
    $stmtRole = $pdo->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ? AND status = 'active' LIMIT 1");
    $stmtRole->execute([$userId, $ucId]);
    $companyRole = (string)($stmtRole->fetchColumn() ?: '');
    $isSuperadmin = in_array($companyRole, ['root', 'superadmin'], true);
    $isAdmin = ($companyRole === 'admin');

    $where = ['company_id = ?', "tipo = 'Tarea'"];
    $paramsBase = [$ucId];

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

    // Total de tareas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE $whereSql");
    $stmt->execute($paramsBase);
    $stats['total'] = (int)$stmt->fetchColumn();
    
    // Por estado
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM tasks WHERE $whereSql GROUP BY status");
    $stmt->execute($paramsBase);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count = (int)$row['count'];
        switch ($row['status']) {
            case 'En proceso':
            case 'En tiempo':
                $stats['pending'] += $count;
                $stats['in_progress'] += $count;
                break;
            case 'Vencida':
                $stats['overdue'] += $count;
                break;
            case 'Terminada':
            case 'Auditada':
                $stats['completed'] += $count;
                break;
        }
    }
    
    // Tareas del usuario
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE company_id = ? AND usuario_delegado = ? AND tipo = 'Tarea'");
    $stmt->execute([$ucId, $userId]);
    $stats['assigned_to_me'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE company_id = ? AND usuario_creador = ? AND tipo = 'Tarea'");
    $stmt->execute([$ucId, $userId]);
    $stats['delegated_by_me'] = (int)$stmt->fetchColumn();
    
    // Tareas recientes
    $stmt = $pdo->prepare("
        SELECT 
            id, titulo as title, status, fecha_entrega as due_date
        FROM tasks 
        WHERE company_id = ? 
          AND tipo = 'Tarea'
          AND (usuario_delegado = ? OR usuario_creador = ?)
          AND status NOT IN ('Terminada', 'Auditada')
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$ucId, $userId, $userId]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    echo json_encode([
        'ok' => true,
        'stats' => $stats,
        'tasks' => $tasks
    ]);
    
} catch (Exception $e) {
    error_log('[Dashboard API] Error: ' . $e->getMessage());
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
            'delegated_by_me' => 0
        ],
        'tasks' => []
    ]);
}