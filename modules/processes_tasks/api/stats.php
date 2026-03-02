<?php
/**
 * API Stats para Processes & Tasks
 * Retorna estadísticas de tareas para los KPIs
 */

require __DIR__.'/../../../bootstrap.php';
require __DIR__.'/../../../core/auth.php';
require __DIR__.'/../../../core/permissions.php';
require __DIR__.'/../../../core/scope.php';
require __DIR__.'/../includes/enum_normalizer.php';
header('Content-Type: application/json; charset=utf-8');

requireLogin();
$ucId = currentUserCompany();
$userId = (int)currentUserId();

if (!hasPermission($ucId, 'processes_tasks', 'view')) { 
    http_response_code(403); 
    echo json_encode(['ok'=>false,'error'=>'Sin permisos para ver estadísticas']); 
    exit; 
}

try {
    $pdo = db();

    // Rol de compañía
    $stmtRole = $pdo->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ? AND status = 'active' LIMIT 1");
    $stmtRole->execute([$userId, $ucId]);
    $companyRole = (string)($stmtRole->fetchColumn() ?: '');
    $isSuperadmin = in_array($companyRole, ['root', 'superadmin'], true);
    $isAdmin = ($companyRole === 'admin');
    
    // Filtro por tipo si se especifica
    $tipo = $_GET['tipo'] ?? 'Tarea';
    $normalizedType = normalizeTaskType($tipo);
    
    // Query base para estadísticas
    $where = ['t.company_id = ?', 't.tipo = ?'];
    $params = [$ucId, $normalizedType];

    // Scope unit/business: superadmin lo ignora; cualquier otro rol (incl admin) lo aplica
    if (!$isSuperadmin) {
        $scope = getUserScope($userId, $ucId);
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
            $where[] = 't.usuario_creador = ?';
            $params[] = $userId;
        } else {
            $ph = implode(',', array_fill(0, count($allowedAssigneeIds), '?'));
            $where[] = "(t.usuario_creador = ? OR t.usuario_delegado IN ($ph))";
            $params[] = $userId;
            $params = array_merge($params, $allowedAssigneeIds);
        }
    }

    $baseWhere = implode(' AND ', $where);
    
    // Total de tareas
    $totalStmt = $pdo->prepare("SELECT COUNT(*) as total FROM tasks t WHERE $baseWhere");
    $totalStmt->execute($params);
    $total = (int)$totalStmt->fetchColumn();
    
    // Pendientes (En proceso + En tiempo)
    $pendingStmt = $pdo->prepare("SELECT COUNT(*) as pending FROM tasks t WHERE $baseWhere AND t.status IN ('En proceso', 'En tiempo')");
    $pendingStmt->execute($params);
    $pending = (int)$pendingStmt->fetchColumn();
    
    // Vencidas
    $overdueStmt = $pdo->prepare("SELECT COUNT(*) as overdue FROM tasks t WHERE $baseWhere AND t.status = 'Vencida'");
    $overdueStmt->execute($params);
    $overdue = (int)$overdueStmt->fetchColumn();
    
    // Completadas (Terminada + Auditada)
    $completedStmt = $pdo->prepare("SELECT COUNT(*) as completed FROM tasks t WHERE $baseWhere AND t.status IN ('Terminada', 'Auditada')");
    $completedStmt->execute($params);
    $completed = (int)$completedStmt->fetchColumn();
    
    $stats = [
        'total' => $total,
        'pending' => $pending,
        'in_progress' => $pending, // Alias para compatibilidad
        'overdue' => $overdue,
        'completed' => $completed
    ];
    
    echo json_encode([
        'ok' => true,
        'stats' => $stats,
        'debug' => [
            'company_id' => $ucId,
            'tipo' => $normalizedType,
            'query_executed' => true
        ]
    ]);
    
} catch (Throwable $e) {
    error_log('[processes_tasks:stats] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ]);
}