<?php
/**
 * /modules/processes_tasks/includes/update_task.php
 * Endpoint para actualización inline de campos de tareas
 * 
 * Recibe: { id: number, field: string, value: string }
 * Responde: { success: bool, message: string, data?: object }
 */

// Configuración de headers
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejo de preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido. Use POST.'
    ]);
    exit;
}

// Incluir configuración de BD
require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../../core/auth.php';
require_once __DIR__ . '/../../../core/permissions.php';
require_once __DIR__ . '/../../../core/scope.php';
require_once __DIR__ . '/log_task_audit.php';

if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
}

requireLogin();
$ucId = (int)currentUserCompany();
if (!$ucId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No company context']);
    exit;
}

if (!hasPermission($ucId, 'processes_tasks', 'edit')) {
    $logLine = json_encode([
        'ts' => date('c'),
        'endpoint' => 'includes/update_task.php',
        'reason' => 'no_permission:edit',
        'user_id' => $_SESSION['user_id'] ?? null,
        'company_id' => $ucId,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ], JSON_UNESCAPED_SLASHES);
    @file_put_contents(__DIR__ . '/../logs/legacy_access_denied.log', $logLine . "\n", FILE_APPEND | LOCK_EX);
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

$pdo = db();

try {
    // Leer datos del request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Validar datos requeridos
    if (!isset($data['id']) || !isset($data['field']) || !isset($data['value'])) {
        throw new Exception('Datos incompletos. Se requiere: id, field, value');
    }

    $taskId = (int)$data['id'];
    $field = trim($data['field']);
    $value = trim($data['value']);
    $userId = (int)($_SESSION['user_id'] ?? 0);

    // Rol de compañía (superadmin ignora scope; admin NO lo ignora)
    $stmtRole = $pdo->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ? AND status = 'active' LIMIT 1");
    $stmtRole->execute([$userId, $ucId]);
    $companyRole = (string)($stmtRole->fetchColumn() ?: '');
    $isSuperadmin = in_array($companyRole, ['root', 'superadmin'], true);
    $isAdmin = ($companyRole === 'admin');

    // Validar que el campo sea permitido (whitelist minimalista)
    $fieldMap = [
        'status' => 'status',
        'nivel' => 'nivel',
        'fecha_entrega' => 'fecha_entrega',
        'usuario_delegado' => 'usuario_delegado',
        // Compatibilidad legacy (UI puede mandar "delegado")
        'delegado' => 'usuario_delegado',
        'titulo' => 'titulo',
        'descripcion' => 'descripcion',
    ];

    if (!isset($fieldMap[$field])) {
        throw new Exception("Campo '$field' no permitido para edición");
    }

    $dbField = $fieldMap[$field];

    // Validaciones específicas por campo
    switch ($field) {
        case 'fecha_entrega':
            if (!empty($value) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                throw new Exception('Formato de fecha inválido. Use YYYY-MM-DD');
            }
            break;

        case 'titulo':
            if (empty($value)) {
                throw new Exception('El título no puede estar vacío');
            }
            if (strlen($value) > 250) {
                throw new Exception('El título no puede exceder 250 caracteres');
            }
            break;

        case 'descripcion':
            if (strlen($value) > 1000) {
                throw new Exception('La descripción no puede exceder 1000 caracteres');
            }
            break;

        case 'status':
            $validStatus = ['En tiempo', 'En proceso', 'Terminada', 'Vencida', 'Auditada', 'Pausada'];
            if (!in_array($value, $validStatus)) {
                throw new Exception('Status no válido');
            }
            break;

        case 'usuario_delegado':
        case 'delegado':
            if ($value !== '' && !ctype_digit((string)$value)) {
                throw new Exception('Delegado inválido');
            }
            $value = ($value === '') ? null : (int)$value;
            break;
    }

    // Verificar ownership por company_id + acceso por rol/scope/organigrama
    $stmtTask = $pdo->prepare('SELECT id, unit_id, business_id, usuario_creador, usuario_delegado FROM tasks WHERE id = ? AND company_id = ? LIMIT 1');
    $stmtTask->execute([$taskId, $ucId]);
    $task = $stmtTask->fetch(PDO::FETCH_ASSOC);
    if (!$task) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tarea no encontrada']);
        exit;
    }

    if (!$isSuperadmin) {
        $scope = getUserScope($userId, $ucId);
        if (($scope['type'] ?? '') === 'limited') {
            if (!empty($scope['units'])) {
                if (!in_array((int)($task['unit_id'] ?? 0), array_map('intval', $scope['units']), true)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Sin acceso a la unidad']);
                    exit;
                }
            }
            if (!empty($scope['businesses'])) {
                if (!in_array((int)($task['business_id'] ?? 0), array_map('intval', $scope['businesses']), true)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Sin acceso al negocio']);
                    exit;
                }
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

        $creatorId = (int)($task['usuario_creador'] ?? 0);
        $assigneeId = (int)($task['usuario_delegado'] ?? 0);

        if (empty($allowedAssigneeIds)) {
            if ($creatorId !== $userId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sin acceso a la tarea']);
                exit;
            }
        } else {
            if (!($creatorId === $userId || in_array($assigneeId, $allowedAssigneeIds, true))) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sin acceso a la tarea']);
                exit;
            }
        }
    }

    // Obtener valor anterior para auditoría
    $stmt = $pdo->prepare("SELECT `$dbField` AS old_value FROM tasks WHERE id = ? AND company_id = ? LIMIT 1");
    $stmt->execute([$taskId, $ucId]);
    $oldRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$oldRow) {
        throw new Exception("Tarea #$taskId no encontrada");
    }

    $oldValue = $oldRow['old_value'];

    // Actualizar el campo en la BD
    $updateStmt = $pdo->prepare("UPDATE tasks SET `$dbField` = ?, updated_at = NOW() WHERE id = ? AND company_id = ?");
    $updateStmt->execute([$value, $taskId, $ucId]);

    // Registrar en auditoría
    logTaskAudit($pdo, $taskId, $dbField, $oldValue, $value, $userId);

    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Campo actualizado correctamente',
        'data' => [
            'id' => $taskId,
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $value,
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
