<?php
require __DIR__.'/../../../bootstrap.php';
require __DIR__.'/../../../core/auth.php';
require __DIR__.'/../../../core/permissions.php';
require __DIR__.'/../../../core/scope.php';
require __DIR__.'/../includes/csrf_helper.php';
require __DIR__.'/../includes/validation_helper.php';
require __DIR__.'/../includes/enum_normalizer.php';
header('Content-Type: application/json');

requireLogin();
requireCSRF();
$ucId = (int)currentUserCompany();
$userId = (int)currentUserId();

if (!hasPermission($ucId, 'processes_tasks', 'create')) { 
    http_response_code(403); 
    echo json_encode(['ok'=>false,'error'=>'no_permission']); 
    exit; 
}

// Validar y sanitizar inputs (manteniendo nombres originales del frontend)
$title = sanitizeInput('POST', 'title', 'string', ['max_length' => 255]);
$desc = sanitizeInput('POST', 'desc', 'string', ['max_length' => 5000]);
$assignee = sanitizeInput('POST', 'assignee', 'int', ['min' => 0]);
$delegate = sanitizeInput('POST', 'delegate', 'int', ['min' => 0]);
$priority = sanitizeInput('POST', 'priority', 'string', ['max_length' => 50]);
$status = sanitizeInput('POST', 'status', 'string', ['max_length' => 50]) ?? 'pendiente';
$start = sanitizeInput('POST', 'start', 'string', ['max_length' => 10]);
$due = sanitizeInput('POST', 'due', 'string', ['max_length' => 10]);
$businessId = sanitizeInput('POST', 'business_id', 'int', ['min' => 0]);
$unitId = sanitizeInput('POST', 'unit_id', 'int', ['min' => 0]);
$projectName = sanitizeInput('POST', 'project_name', 'string', ['max_length' => 150]);
$type = sanitizeInput('POST', 'type', 'string', ['max_length' => 50]) ?? 'Tarea';

// También verificar campos alternativos que pueden venir de diferentes formularios
if (!$title) {
    $title = sanitizeInput('POST', 'titulo', 'string', ['max_length' => 255]);
}
if (!$desc) {
    $desc = sanitizeInput('POST', 'descripcion', 'string', ['max_length' => 5000]);
}
if (!$assignee) {
    $assignee = sanitizeInput('POST', 'assignee_id', 'int', ['min' => 0]);
}
if (!$start) {
    $start = sanitizeInput('POST', 'start_date', 'string', ['max_length' => 10]);
    if (!$start) {
        $start = sanitizeInput('POST', 'fecha_inicio', 'string', ['max_length' => 10]);
    }
}
if (!$due) {
    $due = sanitizeInput('POST', 'due_date', 'string', ['max_length' => 10]);
    if (!$due) {
        $due = sanitizeInput('POST', 'fecha_entrega', 'string', ['max_length' => 10]);
    }
}
if (!$priority) {
    $priority = sanitizeInput('POST', 'prioridad', 'string', ['max_length' => 50]);
}

// Validar que el título sea obligatorio
if (!$title) {
    echo json_encode(['ok'=>false,'error'=>'title_required']);
    exit;
}

// Validar fechas si existen
if ($start && validateDate($start) === false) {
    echo json_encode(['ok'=>false,'error'=>'invalid_start_date']);
    exit;
}
if ($due && validateDate($due) === false) {
    echo json_encode(['ok'=>false,'error'=>'invalid_due_date']);
    exit;
}

// Normalizar valores ENUM usando el helper
$normalizedPriority = normalizePriority($priority);
$normalizedStatus = normalizeStatus($status);

try {
    $pdo = db();

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
            if (!empty($scope['units']) && !empty($unitId)) {
                if (!in_array((int)$unitId, array_map('intval', $scope['units']), true)) {
                    http_response_code(403);
                    echo json_encode(['ok' => false, 'error' => 'forbidden_unit']);
                    exit;
                }
            }
            if (!empty($scope['businesses']) && !empty($businessId)) {
                if (!in_array((int)$businessId, array_map('intval', $scope['businesses']), true)) {
                    http_response_code(403);
                    echo json_encode(['ok' => false, 'error' => 'forbidden_business']);
                    exit;
                }
            }
        }
    }

    // No admin/superadmin: no permitir suplantar creador
    if (!$isSuperadmin && !$isAdmin) {
        $delegate = $userId;
    }
    
    // Generar folio único
    $stmtFolio = $pdo->prepare('SELECT MAX(CAST(SUBSTRING(folio, 3) AS UNSIGNED)) as max_num FROM tasks WHERE company_id = ? AND folio LIKE "T-%"');
    $stmtFolio->execute([$ucId]);
    $maxNum = $stmtFolio->fetch(PDO::FETCH_ASSOC)['max_num'] ?? 0;
    $folio = 'T-' . str_pad($maxNum + 1, 5, '0', STR_PAD_LEFT);
    
    // Usar tabla TASKS (unificada) con nombres de campos correctos
    $stmt = $pdo->prepare('
        INSERT INTO tasks (
            company_id, business_id, unit_id, folio, 
            titulo, descripcion, 
            fecha_inicio, fecha_fin, fecha_entrega, 
            usuario_creador, usuario_delegado, 
            nivel, tipo, status, 
            proyecto_asignado, ponderacion,
            created_at, updated_at
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ');
    
    $now = date('Y-m-d H:i:s');
    $stmt->execute([
        $ucId,                          // company_id
        $businessId ?: null,            // business_id
        $unitId ?: null,                // unit_id
        $folio,                         // folio auto-generado
        $title,                         // titulo (DB espera este nombre)
        $desc,                          // descripcion
        $start ?: null,                 // fecha_inicio
        $due ?: null,                   // fecha_fin
        $due ?: null,                   // fecha_entrega (mismo que fin por ahora)
        $delegate ?: $userId,           // usuario_creador (quien delega)
        $assignee ?: null,              // usuario_delegado (a quien se asigna)
        $normalizedPriority,            // nivel (normalizado: Normal, Importante, Urgente)
        $type,                          // tipo (viene del formulario)
        $normalizedStatus,              // status (normalizado: En tiempo, En proceso, etc.)
        $projectName,                   // proyecto_asignado
        1,                              // ponderacion (default)
        $now,                           // created_at
        $now                            // updated_at
    ]);
    
    $id = $pdo->lastInsertId();
    
    // Retornar el registro creado
    $r = $pdo->prepare('SELECT * FROM tasks WHERE id=? LIMIT 1');
    $r->execute([$id]);
    $row = $r->fetch(PDO::FETCH_ASSOC) ?: null;
    
    echo json_encode(['ok'=>true,'id'=>$id,'item'=>$row]);
} catch (Throwable $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) error_log('[processes_tasks:create] '.$e->getMessage());
    echo json_encode(['ok'=>false,'error'=>'server_error','message'=>$e->getMessage()]);
}
