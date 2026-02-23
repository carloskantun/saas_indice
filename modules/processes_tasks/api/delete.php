<?php
require __DIR__.'/../../../bootstrap.php';
require __DIR__.'/../../../core/auth.php';
require __DIR__.'/../../../core/permissions.php';
require __DIR__.'/../../../core/scope.php';
require __DIR__.'/../includes/csrf_helper.php';
require __DIR__.'/../includes/validation_helper.php';
header('Content-Type: application/json');

requireLogin();
requireCSRF();
$ucId = (int)currentUserCompany();
$userId = (int)currentUserId();
if (!hasPermission($ucId, 'processes_tasks', 'delete')) { 
    http_response_code(403); 
    echo json_encode(['ok'=>false,'error'=>'no_permission']); 
    exit; 
}

// Validar task_id
$taskId = sanitizeInput('POST', 'task_id', 'int', ['min' => 1]);
if (!$taskId) { 
    echo json_encode(['ok'=>false,'error'=>'invalid_task_id']); 
    exit; 
}

try {
    $pdo = db();

    // Cargar tarea
    $stmtTask = $pdo->prepare('SELECT id, unit_id, business_id, usuario_creador, usuario_delegado, archivos FROM tasks WHERE id=? AND company_id=? LIMIT 1');
    $stmtTask->execute([$taskId, $ucId]);
    $task = $stmtTask->fetch(PDO::FETCH_ASSOC);
    if (!$task) {
        echo json_encode(['ok'=>false,'error'=>'task_not_found']);
        exit;
    }

    // Rol de compañía
    $stmtRole = $pdo->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ? AND status = 'active' LIMIT 1");
    $stmtRole->execute([$userId, $ucId]);
    $companyRole = (string)($stmtRole->fetchColumn() ?: '');
    $isSuperadmin = in_array($companyRole, ['root', 'superadmin'], true);
    $isAdmin = ($companyRole === 'admin');

    // Scope unit/business
    if (!$isSuperadmin) {
        $scope = getUserScope($userId, $ucId);
        if (($scope['type'] ?? '') === 'limited') {
            if (!empty($scope['units'])) {
                if (!in_array((int)($task['unit_id'] ?? 0), array_map('intval', $scope['units']), true)) {
                    http_response_code(403);
                    echo json_encode(['ok'=>false,'error'=>'forbidden']);
                    exit;
                }
            }
            if (!empty($scope['businesses'])) {
                if (!in_array((int)($task['business_id'] ?? 0), array_map('intval', $scope['businesses']), true)) {
                    http_response_code(403);
                    echo json_encode(['ok'=>false,'error'=>'forbidden']);
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
                echo json_encode(['ok'=>false,'error'=>'forbidden']);
                exit;
            }
        } else {
            if (!($creatorId === $userId || in_array($assigneeId, $allowedAssigneeIds, true))) {
                http_response_code(403);
                echo json_encode(['ok'=>false,'error'=>'forbidden']);
                exit;
            }
        }
    }
    
    // Eliminar archivos adjuntos si existen (en campo JSON archivos)
    if (!empty($task['archivos'])) {
        $archivos = json_decode($task['archivos'], true) ?: [];
        foreach ($archivos as $archivo) {
            if (!empty($archivo['path'])) { 
                @unlink(__DIR__.'/../uploads/'.$archivo['path']); 
            }
        }
    }
    
    // También limpiar tabla task_files si existe referencia antigua
    $pdo->prepare('DELETE FROM task_files WHERE task_id=?')->execute([$taskId]);
    
    // Eliminar auditorías relacionadas
    $pdo->prepare('DELETE FROM task_audit WHERE task_id=?')->execute([$taskId]);
    
    // Eliminar la tarea de tabla TASKS (unificada)
    $pdo->prepare('DELETE FROM tasks WHERE id=? AND company_id=?')->execute([$taskId, $ucId]);
    
    echo json_encode(['ok'=>true,'message'=>'Tarea eliminada correctamente']);
} catch (Throwable $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) error_log('[processes_tasks:delete] '.$e->getMessage());
    echo json_encode(['ok'=>false,'error'=>'server_error','message'=>$e->getMessage()]);
}
