<?php
require __DIR__.'/../../../bootstrap.php';
require __DIR__.'/../../../core/auth.php';
require __DIR__.'/../../../core/permissions.php';
require __DIR__.'/../../../core/scope.php';
require __DIR__.'/../includes/csrf_helper.php';
require __DIR__.'/../includes/validation_helper.php';
require __DIR__.'/../includes/enum_normalizer.php';
require __DIR__.'/../includes/id_resolver.php';
header('Content-Type: application/json');

requireLogin();
requireCSRF();
$ucId = (int)currentUserCompany();
$userId = (int)currentUserId();
if (!hasPermission($ucId, 'processes_tasks', 'edit')) { 
    http_response_code(403); 
    echo json_encode(['ok'=>false,'error'=>'no_permission']); 
    exit; 
}

// Validar inputs
$taskId = sanitizeInput('POST', 'task_id', 'int', ['min' => 1]);
$field = sanitizeInput('POST', 'field', 'string', ['max_length' => 50]);
$value = $_POST['value'] ?? null; // Se validará según el campo

if (!$taskId || !$field) { 
    echo json_encode(['ok'=>false,'error'=>'missing_params']); 
    exit; 
}

$allowed = ['status','priority','start','due','end','delegate','assignee','delegateId','assigneeId','auditScore','title','descripcion','titulo'];
if (!in_array($field, $allowed)) { 
    echo json_encode(['ok'=>false,'error'=>'invalid_field']); 
    exit; 
}

try {
    $pdo = db();

    // Cargar tarea (company_id siempre)
    $stmtTask = $pdo->prepare("SELECT id, unit_id, business_id, usuario_creador, usuario_delegado FROM tasks WHERE id=? AND company_id=? LIMIT 1");
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

    // Scope unit/business: superadmin lo ignora; cualquier otro rol (incl admin) lo aplica
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
    
    // Mapeo de campos frontend → DB para tabla TASKS
    $map = [
        'start' => 'fecha_inicio',
        'due' => 'fecha_entrega',
        'end' => 'fecha_fin',
        'status' => 'status',
        'priority' => 'nivel',
        'delegate' => 'usuario_creador',
        'assignee' => 'usuario_delegado',
        'delegateId' => 'usuario_creador',
        'assigneeId' => 'usuario_delegado',
        'auditScore' => 'ponderacion',
        'title' => 'titulo',
        'descripcion' => 'descripcion',
        'titulo' => 'titulo',
    ];
    
    $col = $map[$field] ?? null;
    if (!$col) { 
        echo json_encode(['ok'=>false,'error'=>'invalid_field_map']); 
        exit; 
    }
    
    // Normalizar valores según tipo de campo
    if ($field === 'priority' || $col === 'nivel') {
        $value = normalizePriority($value);
    } elseif ($field === 'status') {
        $value = normalizeStatus($value);
    } elseif (in_array($col, ['usuario_delegado','usuario_creador','ponderacion'])) { 
        $value = $value === '' ? null : validateInt($value, 0); 
        if ($value === false) {
            echo json_encode(['ok'=>false,'error'=>'invalid_integer_value']);
            exit;
        }

        // Normalización gradual: aceptar hr_employees.id o users.id, guardar SIEMPRE users.id.
        if ($value !== null && in_array($col, ['usuario_delegado','usuario_creador'], true)) {
            $resolved = resolveUserIdFromMixed((int)$value, (int)$ucId);
            if ($resolved === null) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => 'invalid_user_reference', 'field' => $col]);
                exit;
            }
            $value = $resolved;
        }
    }
    
    // Validar fechas
    if (in_array($col, ['fecha_inicio','fecha_fin','fecha_entrega'])) { 
        if ($value !== '' && $value !== null) {
            $validated = validateDate($value);
            if ($validated === false) {
                echo json_encode(['ok'=>false,'error'=>'invalid_date_format']);
                exit;
            }
            $value = $validated;
        } else {
            $value = null;
        }
    }
    
    // Validar strings
    if (in_array($col, ['titulo','descripcion'])) {
        $value = validateString($value, $col === 'titulo' ? 255 : 5000);
        if ($value === false) {
            echo json_encode(['ok'=>false,'error'=>'invalid_string_value']);
            exit;
        }
    }

    $now = date('Y-m-d H:i:s');
    
    // Usar tabla TASKS (unificada)
    $upd = $pdo->prepare("UPDATE tasks SET $col = ?, updated_at = ? WHERE id = ? AND company_id = ?");
    $upd->execute([$value, $now, $taskId, $ucId]);
    
    // Retornar la tarea actualizada
    $selectStmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND company_id = ? LIMIT 1");
    $selectStmt->execute([$taskId, $ucId]);
    $updatedTask = $selectStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['ok'=>true,'item'=>$updatedTask]);
} catch (Throwable $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) error_log('[processes_tasks:update_field] '.$e->getMessage());
    echo json_encode(['ok'=>false,'error'=>'server_error','message'=>$e->getMessage()]);
}
