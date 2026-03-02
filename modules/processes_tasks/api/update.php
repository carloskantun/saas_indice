<?php
require __DIR__.'/../../../bootstrap.php';
require __DIR__.'/../../../core/auth.php';
require __DIR__.'/../../../core/permissions.php';
require __DIR__.'/../../../core/scope.php';
require __DIR__.'/../includes/csrf_helper.php';
require __DIR__.'/../includes/enum_normalizer.php';
require __DIR__.'/../includes/id_resolver.php';
header('Content-Type: application/json');

requireLogin();
requireCSRF(); // Validación CSRF
$ucId = (int)currentUserCompany();
$userId = (int)currentUserId();
if (!hasPermission($ucId, 'processes_tasks', 'edit')) { 
    http_response_code(403); 
    echo json_encode(['ok'=>false,'error'=>'no_permission']); 
    exit; 
}

$taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
if (!$taskId) { 
    echo json_encode(['ok'=>false,'error'=>'missing_task']); 
    exit; 
}

// Mapeo de campos frontend → DB (TASKS table)
$fieldMap = [
    'title' => 'titulo',
    'description' => 'descripcion',
    'desc' => 'descripcion',
    'priority' => 'nivel',
    'status' => 'status',
    'start_date' => 'fecha_inicio',
    'start' => 'fecha_inicio',
    'due_date' => 'fecha_entrega',
    'due' => 'fecha_entrega',
    'end_date' => 'fecha_fin',
    'assignee' => 'usuario_delegado',
    'assignee_hr_id' => 'usuario_delegado',
    'delegate' => 'usuario_creador',
    'delegate_hr_id' => 'usuario_creador',
    'project_name' => 'proyecto_asignado',
    'project' => 'proyecto_asignado',
    'business_id' => 'business_id',
    'unit_id' => 'unit_id',
    'type' => 'tipo',
    'tipo' => 'tipo',
];

$fields = [];
$params = [];

// Enforcement de acceso a la tarea (rol/scope/organigrama)
$pdo = db();

$stmtTask = $pdo->prepare('SELECT id, unit_id, business_id, usuario_creador, usuario_delegado FROM tasks WHERE id=? AND company_id=? LIMIT 1');
$stmtTask->execute([$taskId, $ucId]);
$task = $stmtTask->fetch(PDO::FETCH_ASSOC);
if (!$task) {
    echo json_encode(['ok' => false, 'error' => 'task_not_found']);
    exit;
}

$stmtRole = $pdo->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ? AND status = 'active' LIMIT 1");
$stmtRole->execute([$userId, $ucId]);
$companyRole = (string)($stmtRole->fetchColumn() ?: '');
$isSuperadmin = in_array($companyRole, ['root', 'superadmin'], true);
$isAdmin = ($companyRole === 'admin');

$scope = null;
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

$allowedAssigneeIds = null;
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

foreach ($_POST as $key => $value) {
    if ($key === 'task_id' || $key === 'csrf_token') continue;
    
    // Mapear campo si existe en el mapeo
    $dbField = $fieldMap[$key] ?? $key;
    
    // Normalizar valores según el campo
    if ($key === 'priority' || $key === 'nivel') {
        $value = normalizePriority($value);
        $dbField = 'nivel';
    } elseif ($key === 'status') {
        $value = normalizeStatus($value);
    } elseif ($key === 'type' || $key === 'tipo') {
        $value = normalizeTaskType($value);
        $dbField = 'tipo';
    }

    // No admin/superadmin: no permitir suplantar creador
    if (!$isSuperadmin && !$isAdmin && $dbField === 'usuario_creador') {
        continue;
    }

    // Normalización gradual: aceptar hr_employees.id o users.id, guardar SIEMPRE users.id.
    if ($dbField === 'usuario_delegado' || $dbField === 'usuario_creador') {
        $incoming = ($value === '' ? null : (int)$value);
        if ($incoming !== null && $incoming > 0) {
            $resolved = resolveUserIdFromMixed($incoming, $ucId);
            if ($resolved === null) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => 'invalid_user_reference', 'field' => $dbField]);
                exit;
            }
            $value = $resolved;
        } else {
            $value = null;
        }
    }

    // Validar cambios de unit_id/business_id contra scope (excepto superadmin)
    if (!$isSuperadmin && ($dbField === 'unit_id' || $dbField === 'business_id')) {
        $incoming = ($value === '' ? null : (int)$value);
        if ($incoming !== null && $incoming > 0 && $scope && ($scope['type'] ?? '') === 'limited') {
            if ($dbField === 'unit_id' && !empty($scope['units'])) {
                if (!in_array($incoming, array_map('intval', $scope['units']), true)) {
                    http_response_code(403);
                    echo json_encode(['ok' => false, 'error' => 'forbidden_unit']);
                    exit;
                }
            }
            if ($dbField === 'business_id' && !empty($scope['businesses'])) {
                if (!in_array($incoming, array_map('intval', $scope['businesses']), true)) {
                    http_response_code(403);
                    echo json_encode(['ok' => false, 'error' => 'forbidden_business']);
                    exit;
                }
            }
        }
    }

    // No admin/superadmin: limitar asignación a organigrama (si hay)
    if (!$isSuperadmin && !$isAdmin && $dbField === 'usuario_delegado') {
        $incomingAssignee = ($value === '' ? null : (int)$value);
        if ($incomingAssignee !== null && $incomingAssignee > 0 && is_array($allowedAssigneeIds) && !empty($allowedAssigneeIds)) {
            if (!in_array($incomingAssignee, $allowedAssigneeIds, true)) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'forbidden_assignee']);
                exit;
            }
        }
    }
    
    $fields[] = "$dbField = ?";
    $params[] = $value === '' ? null : $value;
}

if (!count($fields)) { 
    echo json_encode(['ok'=>false,'error'=>'nothing_to_update']); 
    exit; 
}

try {
    
    // Agregar updated_at
    $params[] = date('Y-m-d H:i:s');
    $set = implode(', ', $fields) . ', updated_at = ?';
    
    // Agregar parámetros WHERE
    $params[] = $taskId; 
    $params[] = $ucId;
    
    // Usar tabla TASKS (unificada)
    $sql = "UPDATE tasks SET $set WHERE id = ? AND company_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Retornar la tarea actualizada
    $stmtSelect = $pdo->prepare('SELECT * FROM tasks WHERE id = ? AND company_id = ? LIMIT 1');
    $stmtSelect->execute([$taskId, $ucId]);
    $updatedTask = $stmtSelect->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['ok'=>true,'item'=>$updatedTask]);
} catch (Throwable $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) error_log('[processes_tasks:update] '.$e->getMessage());
    echo json_encode(['ok'=>false,'error'=>'server_error','message'=>$e->getMessage()]);
}
