<?php
/**
 * API Duplicate - Duplicar tarea/proyecto/proceso
 */

require __DIR__.'/../../../bootstrap.php';
require __DIR__.'/../../../core/auth.php';
require __DIR__.'/../../../core/permissions.php';
require __DIR__.'/../includes/enum_normalizer.php';
header('Content-Type: application/json; charset=utf-8');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

requireLogin();
$ucId = currentUserCompany();
$userId = $_SESSION['user_id'] ?? null;

if (!hasPermission($ucId, 'processes_tasks', 'create')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Sin permisos para crear']);
    exit;
}

// Validar CSRF
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Token CSRF inválido']);
    exit;
}

$taskId = (int)($_POST['task_id'] ?? 0);
if (!$taskId) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'ID de tarea requerido']);
    exit;
}

try {
    $pdo = db();
    
    // Obtener la tarea original
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND company_id = ? LIMIT 1");
    $stmt->execute([$taskId, $ucId]);
    $original = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$original) {
        throw new Exception('Tarea no encontrada');
    }
    
    // Generar nuevo folio
    $folioPrefix = substr($original['tipo'], 0, 1); // T, P, etc.
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(folio, 3) AS UNSIGNED)) as max_num FROM tasks WHERE company_id = ? AND tipo = ? AND folio LIKE ?");
    $stmt->execute([$ucId, $original['tipo'], $folioPrefix . '-%']);
    $maxNum = (int)$stmt->fetchColumn();
    $newFolio = $folioPrefix . '-' . str_pad($maxNum + 1, 5, '0', STR_PAD_LEFT);
    
    // Preparar datos para inserción (duplicar pero con nuevos valores)
    $data = [
        'company_id' => $ucId,
        'folio' => $newFolio,
        'titulo' => $original['titulo'] . ' (Copia)',
        'descripcion' => $original['descripcion'],
        'tipo' => $original['tipo'],
        'status' => 'En tiempo', // Siempre comenzar con estado inicial
        'nivel' => $original['nivel'],
        'fecha_inicio' => date('Y-m-d'), // Nueva fecha de inicio
        'fecha_entrega' => $original['fecha_entrega'], // Mantener misma fecha de entrega
        'fecha_fin' => null, // Sin fecha fin
        'usuario_creador' => $userId, // El usuario actual es el creador
        'usuario_delegado' => $original['usuario_delegado'], // Mantener asignación
        'unit_id' => $original['unit_id'],
        'business_id' => $original['business_id'],
        'proyecto_asignado' => $original['proyecto_asignado'],
        'ponderacion' => $original['ponderacion'],
        'archivos' => null // No duplicar archivos
    ];
    
    // Insertar la nueva tarea
    $sql = "INSERT INTO tasks (
        company_id, folio, titulo, descripcion, tipo, status, nivel,
        fecha_inicio, fecha_entrega, fecha_fin, usuario_creador, usuario_delegado,
        unit_id, business_id, proyecto_asignado, ponderacion, archivos,
        created_at
    ) VALUES (
        :company_id, :folio, :titulo, :descripcion, :tipo, :status, :nivel,
        :fecha_inicio, :fecha_entrega, :fecha_fin, :usuario_creador, :usuario_delegado,
        :unit_id, :business_id, :proyecto_asignado, :ponderacion, :archivos,
        NOW()
    )";
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute($data);
    
    if ($success) {
        $newId = $pdo->lastInsertId();
        
        echo json_encode([
            'ok' => true,
            'message' => ucfirst(strtolower($original['tipo'])) . ' duplicada exitosamente',
            'item_id' => $newId,
            'folio' => $newFolio,
            'original_id' => $taskId,
            'data' => array_merge($data, ['id' => $newId])
        ]);
    } else {
        throw new Exception('Error al duplicar en la base de datos');
    }
    
} catch (Throwable $e) {
    error_log('[processes_tasks:duplicate] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}