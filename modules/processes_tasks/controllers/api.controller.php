<?php
/**
 * Controlador Principal - Módulo Tareas
 * Maneja todas las peticiones AJAX del módulo
 * 
 * @author Nahum Peña / Proyecto Índice 2025
 */

// Habilitar reporte de errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Asegurar que siempre devuelve JSON
header('Content-Type: application/json; charset=utf-8');

// Capturar errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'error' => 'Error del servidor: ' . $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

try {
    // Cargar dependencias
    require_once __DIR__ . '/../../../bootstrap.php';
    require_once __DIR__ . '/../../../core/auth.php';
    require_once __DIR__ . '/../../../core/permissions.php';

    // Verificar autenticación
    requireLogin();
    $ucId = currentUserCompany();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Error cargando dependencias: ' . $e->getMessage()
    ]);
    exit;
}

// Leer JSON del request body si es POST con Content-Type: application/json
$jsonData = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $rawBody = file_get_contents('php://input');
        $jsonData = json_decode($rawBody, true) ?? [];
    }
}

// Verificar permisos básicos del módulo
if (!hasPermission($ucId, 'processes_tasks', 'view')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Sin permisos para acceder al módulo']);
    exit;
}

// Cargar modelos y helpers
try {
    require_once __DIR__ . '/../models/tasks.model.php';
    require_once __DIR__ . '/../models/projects.model.php';
    require_once __DIR__ . '/../models/processes.model.php';
    require_once __DIR__ . '/../models/files.model.php';
    require_once __DIR__ . '/../helpers/kpis.helper.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Error cargando modelos: ' . $e->getMessage()
    ]);
    exit;
}

// Obtener acción (soporta querystring, form y JSON)
$action = $_GET['action'] ?? $_POST['action'] ?? ($jsonData['action'] ?? '');

if (empty($action)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'No se especificó una acción'
    ]);
    exit;
}

// Validar CSRF en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token']
        ?? ($jsonData['csrf_token'] ?? '')
        ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

    if (!verifyCsrfToken($csrfToken)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Token CSRF inválido']);
        exit;
    }
}

// Router de acciones
switch ($action) {
    
    // ========== TAREAS ==========
    
    case 'list_tasks':
        $companyId = $_SESSION['company_id'] ?? 1;
        $filters = [
            'company_id' => $companyId,
            'tipo' => $jsonData['tipo'] ?? $_GET['tipo'] ?? $_GET['type'] ?? '',
            'unit_id' => $jsonData['unit_id'] ?? $_GET['unit_id'] ?? $_GET['unit'] ?? '',
            'business_id' => $jsonData['business_id'] ?? $_GET['business_id'] ?? $_GET['business'] ?? '',
            'status' => $jsonData['status'] ?? $_GET['status'] ?? '',
            'periodo' => $jsonData['periodo'] ?? $_GET['periodo'] ?? '',
            'fecha_inicio' => $jsonData['fecha_inicio'] ?? $_GET['fecha_inicio'] ?? null,
            'fecha_fin' => $jsonData['fecha_fin'] ?? $_GET['fecha_fin'] ?? null,
            'search' => $jsonData['search'] ?? $_GET['q'] ?? '',
            'proyecto' => $_GET['project_id'] ?? null,
            'delegated_to' => $jsonData['delegated_to'] ?? $_GET['delegated_to'] ?? null
        ];
        
        $tasks = TasksModel::list($filters);
        echo json_encode(['ok' => true, 'data' => $tasks]);
        break;
    
    case 'get_agenda':
        $companyId = $_SESSION['company_id'] ?? 1;
        $filters = [
            'company_id' => $companyId,
            'date_filter' => $_GET['date_filter'] ?? 'today',
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
            'unit_id' => $_GET['unit'] ?? '',
            'business_id' => $_GET['business'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];
        
        $tasks = TasksModel::getAgenda($filters);
        echo json_encode(['ok' => true, 'data' => $tasks]);
        break;
    
    case 'create_task':
        if (!hasPermission($ucId, 'processes_tasks', 'create')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sin permisos para crear tareas']);
            exit;
        }
        
        $companyId = $_SESSION['company_id'] ?? 1;
        $userId = $_SESSION['user_id'] ?? 1;
        
        // Map priority values to DB ENUM
        $priorityMap = [
            'baja' => 'Normal',
            'media' => 'Normal',
            'normal' => 'Normal',
            'alta' => 'Importante',
            'importante' => 'Importante',
            'urgente' => 'Urgente',
            'Normal' => 'Normal',
            'Importante' => 'Importante',
            'Urgente' => 'Urgente'
        ];
        
        $rawPriority = $_POST['priority'] ?? 'Normal';
        $nivel = $priorityMap[strtolower($rawPriority)] ?? 'Normal';
        
        // Map status values to DB ENUM
        $statusMap = [
            'pendiente' => 'En tiempo',
            'en_proceso' => 'En proceso',
            'en proceso' => 'En proceso',
            'completada' => 'Terminada',
            'terminada' => 'Terminada',
            'pausada' => 'Pausada',
            'vencida' => 'Vencida',
            'auditada' => 'Auditada',
            'En tiempo' => 'En tiempo',
            'En proceso' => 'En proceso',
            'Terminada' => 'Terminada',
            'Pausada' => 'Pausada',
            'Vencida' => 'Vencida',
            'Auditada' => 'Auditada'
        ];
        
        $rawStatus = $_POST['status'] ?? 'En tiempo';
        $status = $statusMap[strtolower($rawStatus)] ?? 'En tiempo';
        
        $data = [
            'company_id' => $companyId,
            'titulo' => $_POST['title'] ?? $_POST['titulo'] ?? '',
            'descripcion' => $_POST['description'] ?? $_POST['descripcion'] ?? '',
            'unit_id' => !empty($_POST['unit_id']) ? (int)$_POST['unit_id'] : (!empty($_POST['unit']) ? (int)$_POST['unit'] : null),
            'business_id' => !empty($_POST['business_id']) ? (int)$_POST['business_id'] : (!empty($_POST['business']) ? (int)$_POST['business'] : null),
            'nivel' => $nivel,
            'fecha_inicio' => $_POST['start_date'] ?? $_POST['fecha_inicio'] ?? date('Y-m-d'),
            'fecha_fin' => $_POST['end_date'] ?? $_POST['due_date'] ?? $_POST['fecha_fin'] ?? null,
            'usuario_creador' => $userId, // Siempre el user_id del usuario logueado
            'usuario_delegado' => !empty($_POST['usuario_delegado']) ? (int)$_POST['usuario_delegado'] : (!empty($_POST['delegated_to']) ? (int)$_POST['delegated_to'] : null),
            'proyecto_asignado' => $_POST['project_id'] ?? $_POST['proyecto_asignado'] ?? null,
            'tipo' => $_POST['tipo'] ?? $_POST['type'] ?? 'Tarea',
            'status' => $status,
            'ponderacion' => 1,
            '_actor' => $userId
        ];
        
        try {
            $taskId = TasksModel::create($data);
            echo json_encode(['ok' => true, 'data' => ['task_id' => $taskId, 'message' => 'Tarea creada exitosamente']]);
        } catch (Exception $e) {
            error_log("Error creando tarea: " . $e->getMessage());
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    case 'update_task':
        if (!hasPermission($ucId, 'processes_tasks', 'edit')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sin permisos para editar tareas']);
            exit;
        }
        
        $taskId = $_POST['task_id'] ?? 0;
        $data = $_POST;
        unset($data['action'], $data['task_id'], $data['csrf_token']);
        $data['_actor'] = $_SESSION['user_id'] ?? 1;
        
        try {
            $success = TasksModel::update($taskId, $data);
            echo json_encode(['ok' => $success, 'data' => ['updated' => $success]]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    case 'update_status':
        if (!hasPermission($ucId, 'processes_tasks', 'edit')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sin permisos para actualizar estado']);
            exit;
        }
        
        $taskId = $jsonData['id'] ?? ($_POST['id'] ?? 0);
        $status = $jsonData['status'] ?? ($_POST['status'] ?? '');
        $actorId = $_SESSION['user_id'] ?? 1;
        
        if (!$taskId || !$status) {
            echo json_encode(['ok' => false, 'error' => 'ID y status requeridos']);
            exit;
        }
        
        try {
            $data = ['status' => $status, '_actor' => $actorId];
            $success = TasksModel::update($taskId, $data);
            echo json_encode(['ok' => $success, 'data' => ['updated' => $success]]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    case 'delete_task':
        if (!hasPermission($ucId, 'processes_tasks', 'delete')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sin permisos para eliminar tareas']);
            exit;
        }
        
        $taskId = $jsonData['id'] ?? ($_POST['task_id'] ?? ($_POST['id'] ?? 0));
        $actorId = $_SESSION['user_id'] ?? 1;
        
        if (!$taskId) {
            echo json_encode(['ok' => false, 'error' => 'ID de tarea requerido']);
            exit;
        }
        
        try {
            $success = TasksModel::delete($taskId, $actorId);
            echo json_encode(['ok' => $success, 'data' => ['deleted' => $success]]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    case 'complete_task':
        $taskId = $_POST['task_id'] ?? 0;
        $actorId = $_SESSION['user_id'] ?? 1;
        
        try {
            $success = TasksModel::complete($taskId, $actorId);
            echo json_encode(['ok' => $success, 'data' => ['completed' => $success]]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    case 'assign_task':
        if (!hasPermission($ucId, 'processes_tasks', 'edit')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sin permisos para asignar tareas']);
            exit;
        }
        
        $taskId = (int)($jsonData['task_id'] ?? $jsonData['id'] ?? ($_POST['task_id'] ?? ($_POST['id'] ?? 0)));
        $userId = (int)($jsonData['user_id'] ?? $jsonData['assignee'] ?? ($_POST['user_id'] ?? ($_POST['assignee'] ?? 0)));
        $actorId = (int)($_SESSION['user_id'] ?? 1);
        
        try {
            // Firma real y auditoría correcta: usar update con _actor
            $success = TasksModel::update($taskId, [
                'usuario_delegado' => $userId,
                '_actor' => $actorId
            ]);
            echo json_encode(['ok' => $success, 'data' => ['assigned' => $success]]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    // ========== PROYECTOS ==========
    
    case 'list_projects':
        $companyId = $_SESSION['company_id'] ?? 1;
        $filters = [
            'company_id' => $companyId,
            'unit_id' => $_GET['unit'] ?? '',
            'business_id' => $_GET['business'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];
        
        $projects = ProjectsModel::getProjects($filters);
        echo json_encode(['ok' => true, 'data' => $projects]);
        break;
    
    case 'get_project':
        $projectId = $_GET['project_id'] ?? 0;
        $project = ProjectsModel::getProjectById($projectId);
        
        if ($project) {
            echo json_encode(['ok' => true, 'data' => $project]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Proyecto no encontrado']);
        }
        break;
    
    case 'create_project':
        if (!hasPermission($ucId, 'processes_tasks', 'create')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sin permisos para crear proyectos']);
            exit;
        }
        
        $companyId = $_SESSION['company_id'] ?? 1;
        $data = [
            'company_id' => $companyId,
            'name' => $_POST['name'] ?? '',
            'unit' => $_POST['unit'] ?? '',
            'business' => $_POST['business'] ?? '',
            'description' => $_POST['description'] ?? '',
            'created_by' => $_SESSION['user_id'] ?? 1
        ];
        
        $result = ProjectsModel::createProject($data);
        
        if ($result['success']) {
            echo json_encode(['ok' => true, 'data' => $result]);
        } else {
            echo json_encode(['ok' => false, 'error' => $result['message'] ?? 'Error al crear proyecto']);
        }
        break;
    
    case 'update_project':
        if (!hasPermission($ucId, 'processes_tasks', 'edit')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sin permisos para editar proyectos']);
            exit;
        }
        
        $projectId = $_POST['project_id'] ?? 0;
        $data = $_POST;
        unset($data['action'], $data['project_id'], $data['csrf_token']);
        $data['_actor'] = $_SESSION['user_id'] ?? 1;
        
        try {
            $success = ProjectsModel::updateProject($projectId, $data);
            echo json_encode(['ok' => $success, 'data' => ['updated' => $success]]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    case 'delete_project':
        if (!hasPermission($ucId, 'processes_tasks', 'delete')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sin permisos para eliminar proyectos']);
            exit;
        }
        
        $projectId = $_POST['project_id'] ?? 0;
        
        try {
            $success = ProjectsModel::deleteProject($projectId);
            echo json_encode(['ok' => $success, 'data' => ['deleted' => $success]]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    // ========== PROCESOS ==========
    
    case 'list_processes':
        $companyId = $_SESSION['company_id'] ?? 1;
        $filters = [
            'company_id' => $companyId,
            'unit_id' => $_GET['unit'] ?? '',
            'business_id' => $_GET['business'] ?? '',
            'status' => isset($_GET['active']) ? ($_GET['active'] ? 'En Proceso' : 'Pausada') : '',
            'frequency' => $_GET['frequency'] ?? ''
        ];
        
        $processes = ProcessesModel::getProcesses($filters);
        echo json_encode(['ok' => true, 'data' => $processes]);
        break;
    
    case 'create_process':
        if (!hasPermission($ucId, 'processes_tasks', 'create')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sin permisos para crear procesos']);
            exit;
        }
        
        $companyId = $_SESSION['company_id'] ?? 1;
        $data = [
            'company_id' => $companyId,
            'name' => $_POST['name'] ?? '',
            'unit' => $_POST['unit'] ?? '',
            'business' => $_POST['business'] ?? '',
            'description' => $_POST['description'] ?? '',
            'frequency' => $_POST['frequency'] ?? 'monthly',
            'delegated_to' => $_POST['delegated_to'] ?? null,
            'priority' => $_POST['priority'] ?? 'medium',
            'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
            'active' => $_POST['active'] ?? 1,
            'created_by' => $_SESSION['user_id'] ?? 1
        ];
        
        $result = ProcessesModel::createProcess($data);
        
        if ($result['success']) {
            echo json_encode(['ok' => true, 'data' => $result]);
        } else {
            echo json_encode(['ok' => false, 'error' => $result['message'] ?? 'Error al crear proceso']);
        }
        break;
    
    case 'update_process':
        if (!hasPermission($ucId, 'processes_tasks', 'edit')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sin permisos para editar procesos']);
            exit;
        }
        
        $processId = $_POST['process_id'] ?? 0;
        $data = $_POST;
        unset($data['action'], $data['process_id'], $data['csrf_token']);
        $data['_actor'] = $_SESSION['user_id'] ?? 1;
        
        try {
            $success = ProcessesModel::updateProcess($processId, $data);
            echo json_encode(['ok' => $success, 'data' => ['updated' => $success]]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    case 'delete_process':
        if (!hasPermission($ucId, 'processes_tasks', 'delete')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sin permisos para eliminar procesos']);
            exit;
        }
        
        $processId = $_POST['process_id'] ?? 0;
        
        try {
            $success = ProcessesModel::deleteProcess($processId);
            echo json_encode(['ok' => $success, 'data' => ['deleted' => $success]]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    // ========== KPIs ==========
    
    case 'get_kpis':
        $companyId = $_SESSION['company_id'] ?? 1;
        
        try {
            $kpis = TasksModel::getKPIs($companyId);
            echo json_encode(['ok' => true, 'data' => $kpis]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    case 'get_load_by_user':
        $companyId = $_SESSION['company_id'] ?? 1;
        $userId = $_GET['user_id'] ?? null;
        
        $filters = ['company_id' => $companyId];
        if ($userId) {
            $filters['delegated_to'] = $userId;
        }
        
        $tasks = TasksModel::list($filters);
        echo json_encode(['ok' => true, 'data' => ['count' => count($tasks), 'tasks' => $tasks]]);
        break;
    
    case 'get_load_by_unit':
        $companyId = $_SESSION['company_id'] ?? 1;
        $unitId = $_GET['unit'] ?? null;
        
        $filters = ['company_id' => $companyId];
        if ($unitId) {
            $filters['unit_id'] = $unitId;
        }
        
        $tasks = TasksModel::list($filters);
        echo json_encode(['ok' => true, 'data' => ['count' => count($tasks), 'tasks' => $tasks]]);
        break;
    
    case 'get_weekly_performance':
        $companyId = $_SESSION['company_id'] ?? 1;
        $kpis = TasksModel::getKPIs($companyId);
        echo json_encode(['ok' => true, 'data' => $kpis]);
        break;
    
    case 'get_monthly_performance':
        $companyId = $_SESSION['company_id'] ?? 1;
        $kpis = TasksModel::getKPIs($companyId);
        echo json_encode(['ok' => true, 'data' => $kpis]);
        break;
    
    // ========== ACCIONES ADICIONALES ==========
    
    case 'duplicate_task':
        if (!hasPermission($ucId, 'processes_tasks', 'create')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sin permisos para duplicar tareas']);
            exit;
        }
        
        $taskId = (int)($_POST['task_id'] ?? 0);
        $userId = $_SESSION['user_id'] ?? 1;
        
        try {
            $newId = TasksModel::duplicate($taskId, $userId);
            echo json_encode(['ok' => true, 'data' => ['new_task_id' => $newId]]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    case 'audit_task':
        if (!hasPermission($ucId, 'processes_tasks', 'audit')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sin permisos para auditar tareas']);
            exit;
        }
        
        $taskId = (int)($jsonData['task_id'] ?? $jsonData['id'] ?? ($_POST['task_id'] ?? ($_POST['id'] ?? 0)));
        $comentario = (string)($jsonData['comment'] ?? $jsonData['comentario'] ?? ($_POST['comment'] ?? ($_POST['comentario'] ?? '')));
        $userId = (int)($_SESSION['user_id'] ?? 1);
        
        try {
            // Marcar como auditada y registrar comentario (si aplica)
            $success = TasksModel::update($taskId, [
                'status' => 'Auditada',
                '_actor' => $userId
            ]);

            if ($success && $comentario !== '') {
                TasksModel::audit($taskId, $userId, 'audit', 'comment', null, $comentario);
            }
            echo json_encode(['ok' => $success, 'data' => ['audited' => $success]]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    // ========== ARCHIVOS ==========
    
    case 'upload_file':
        if (!hasPermission($ucId, 'processes_tasks', 'edit')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sin permisos para subir archivos']);
            exit;
        }
        
        $taskId = (int)($_POST['task_id'] ?? 0);
        $userId = $_SESSION['user_id'] ?? 1;
        
        if (!isset($_FILES['file'])) {
            echo json_encode(['ok' => false, 'error' => 'No se recibió archivo']);
            exit;
        }
        
        $result = FilesModel::uploadFile($taskId, $_FILES['file'], $userId);
        echo json_encode($result);
        break;
    
    case 'list_files':
        $taskId = (int)($_GET['task_id'] ?? 0);
        
        try {
            $files = FilesModel::getFiles($taskId);
            echo json_encode(['ok' => true, 'data' => $files]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    case 'delete_file':
        if (!hasPermission($ucId, 'processes_tasks', 'delete')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sin permisos para eliminar archivos']);
            exit;
        }
        
        $fileId = (int)($_POST['file_id'] ?? 0);
        
        try {
            $success = FilesModel::deleteFile($fileId);
            echo json_encode(['ok' => $success, 'data' => ['deleted' => $success]]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    case 'download_file':
        $fileId = (int)($_GET['file_id'] ?? 0);
        FilesModel::downloadFile($fileId);
        break;
    
    // ========== EXPORTACIÓN ==========
    
    case 'export_tasks':
        $companyId = $_SESSION['company_id'] ?? 1;
        $format = $_GET['format'] ?? 'csv';
        
        $filters = [
            'company_id' => $companyId,
            'tipo' => $_GET['type'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];
        
        $tasks = TasksModel::list($filters);
        
        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="tareas_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // Encabezados
            fputcsv($output, [
                'Folio', 'Título', 'Tipo', 'Status', 'Unidad', 'Negocio',
                'Creador', 'Delegado', 'Ponderación', 'Fecha Inicio', 'Fecha Fin'
            ]);
            
            // Datos
            foreach ($tasks as $task) {
                fputcsv($output, [
                    $task['folio'],
                    $task['titulo'],
                    $task['tipo'],
                    $task['status'],
                    $task['unit_name'] ?? '',
                    $task['business_name'] ?? '',
                    $task['creador_nombre_rh'] ?? $task['creador_name'] ?? '',
                    $task['delegado_nombre_rh'] ?? $task['delegado_name'] ?? '',
                    $task['ponderacion'],
                    $task['fecha_inicio'],
                    $task['fecha_fin']
                ]);
            }
            
            fclose($output);
        }
        exit;
        break;
    
    // ========== DATOS AUXILIARES ==========
    
    case 'get_form_data':
        $companyId = $_SESSION['company_id'] ?? 1;
        
        try {
            $db = db();
            
            // Obtener unidades
            $stmtUnits = $db->prepare("
                SELECT id, name as nombre 
                FROM units 
                WHERE company_id = ? AND status = 'active'
                ORDER BY name
            ");
            $stmtUnits->execute([$companyId]);
            $units = $stmtUnits->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener negocios
            $stmtBusiness = $db->prepare("
                SELECT id, name as nombre 
                FROM businesses 
                WHERE company_id = ? AND status = 'active'
                ORDER BY name
            ");
            $stmtBusiness->execute([$companyId]);
            $businesses = $stmtBusiness->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener empleados del módulo HR (hr_employees table)
            $stmtEmployees = $db->prepare("
                SELECT 
                    e.id, 
                    e.full_name as nombre_completo,
                    e.email,
                    e.position as puesto,
                    e.department as departamento,
                    u.name as unidad,
                    b.name as negocio
                FROM hr_employees e
                LEFT JOIN units u ON e.unit_id = u.id
                LEFT JOIN businesses b ON e.business_id = b.id
                WHERE e.company_id = ? AND e.status = 'activo'
                ORDER BY e.full_name
            ");
            $stmtEmployees->execute([$companyId]);
            $employees = $stmtEmployees->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener proyectos activos
            $stmtProjects = $db->prepare("
                SELECT id, titulo, folio 
                FROM tasks 
                WHERE company_id = ? AND tipo = 'Proceso' AND status != 'Terminada'
                ORDER BY titulo
            ");
            $stmtProjects->execute([$companyId]);
            $projects = $stmtProjects->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'ok' => true,
                'data' => [
                    'units' => $units,
                    'businesses' => $businesses,
                    'employees' => $employees,
                    'projects' => $projects
                ]
            ]);
        } catch (Exception $e) {
            error_log("Error en get_form_data: " . $e->getMessage());
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    // ========== ESTADÍSTICAS DEL DASHBOARD ==========
    
    case 'getTasks':
        // Alias para compatibilidad con KPIs.js
        $companyId = $_SESSION['company_id'] ?? 1;
        $filters = [
            'company_id' => $companyId,
            'dateFrom' => $jsonData['dateFrom'] ?? $_GET['dateFrom'] ?? null,
            'dateTo' => $jsonData['dateTo'] ?? $_GET['dateTo'] ?? null,
            'user' => $jsonData['user'] ?? $_GET['user'] ?? null,
            'search' => $jsonData['search'] ?? $_GET['search'] ?? ''
        ];

        // Convertir fechas si se proporcionaron
        if ($filters['dateFrom']) {
            $filters['fecha_inicio'] = $filters['dateFrom'];
        }
        if ($filters['dateTo']) {
            $filters['fecha_fin'] = $filters['dateTo'];
        }
        if ($filters['user']) {
            $filters['delegated_to'] = $filters['user'];
        }

        try {
            $tasks = TasksModel::list($filters);
            
            // Obtener usuarios únicos
            $users = [];
            $processes = [];
            $userMap = [];

            foreach ($tasks as $task) {
                // Recopilar usuarios únicos
                $userId = $task['usuario_delegado'] ?? $task['delegated_to'];
                $userName = $task['delegado_nombre_rh'] ?? $task['delegado_name'] ?? 'Sin asignar';
                
                if ($userId && !isset($userMap[$userId])) {
                    $userMap[$userId] = true;
                    $users[] = [
                        'id' => $userId,
                        'nombre_completo' => $userName
                    ];
                }

                // Recopilar procesos únicos
                if ($task['tipo'] === 'Proceso') {
                    $processes[] = $task;
                }
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'tasks' => $tasks,
                    'users' => $users,
                    'processes' => $processes
                ]
            ]);
        } catch (Exception $e) {
            error_log("Error en getTasks: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'getUsers':
        // Obtener usuarios para filtros de KPIs
        $companyId = $_SESSION['company_id'] ?? 1;
        
        try {
            $db = db();
            
            $stmtEmployees = $db->prepare("
                SELECT 
                    e.id, 
                    e.full_name as nombre_completo,
                    e.email,
                    e.position as puesto
                FROM hr_employees e
                WHERE e.company_id = ? AND e.status = 'activo'
                ORDER BY e.full_name
            ");
            $stmtEmployees->execute([$companyId]);
            $employees = $stmtEmployees->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $employees
            ]);
        } catch (Exception $e) {
            error_log("Error en getUsers: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
    
    case 'get_stats':
        $companyId = $_SESSION['company_id'] ?? 1;
        
        try {
            $db = db();
            
            // Total de procesos
            $stmtTotal = $db->prepare("
                SELECT COUNT(*) as total 
                FROM tasks 
                WHERE company_id = ? AND tipo = 'Proceso'
            ");
            $stmtTotal->execute([$companyId]);
            $total = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Procesos activos (En tiempo, En proceso)
            $stmtActivos = $db->prepare("
                SELECT COUNT(*) as activos 
                FROM tasks 
                WHERE company_id = ? 
                  AND tipo = 'Proceso' 
                  AND status IN ('En tiempo', 'En proceso')
            ");
            $stmtActivos->execute([$companyId]);
            $activos = $stmtActivos->fetch(PDO::FETCH_ASSOC)['activos'];
            
            // Procesos recurrentes (procesos que tienen tareas generadas asociadas)
            $stmtRecurrentes = $db->prepare("
                SELECT COUNT(DISTINCT p.id) as recurrentes 
                FROM tasks p
                LEFT JOIN tasks t ON t.proyecto_asignado = p.folio AND t.tipo = 'Tarea de proceso'
                WHERE p.company_id = ? 
                  AND p.tipo = 'Proceso'
                  AND t.id IS NOT NULL
            ");
            $stmtRecurrentes->execute([$companyId]);
            $recurrentes = $stmtRecurrentes->fetch(PDO::FETCH_ASSOC)['recurrentes'];
            
            // Tareas generadas POR procesos (suma de tareas asociadas a cada proceso)
            $stmtTareas = $db->prepare("
                SELECT COUNT(*) as tareas 
                FROM tasks t
                INNER JOIN tasks p ON t.proyecto_asignado = p.folio
                WHERE t.company_id = ? 
                  AND t.tipo IN ('Tarea', 'Tarea de proceso')
                  AND p.tipo = 'Proceso'
            ");
            $stmtTareas->execute([$companyId]);
            $tareas = $stmtTareas->fetch(PDO::FETCH_ASSOC)['tareas'];
            
            echo json_encode([
                'ok' => true,
                'stats' => [
                    'total' => (int)$total,
                    'activos' => (int)$activos,
                    'recurrentes' => (int)$recurrentes,
                    'tareas_generadas' => (int)$tareas
                ]
            ]);
        } catch (Exception $e) {
            error_log("Error en get_stats: " . $e->getMessage());
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    case 'get_stats_tasks':
        // Estadísticas específicas para Tareas (no procesos)
        try {
            $db = db();
            
            // Total de tareas
            $stmtTotal = $db->prepare("
                SELECT COUNT(*) as total 
                FROM tasks 
                WHERE company_id = ? AND tipo = 'Tarea'
            ");
            $stmtTotal->execute([$companyId]);
            $total = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Tareas en proceso (En tiempo, En proceso)
            $stmtEnProceso = $db->prepare("
                SELECT COUNT(*) as en_proceso 
                FROM tasks 
                WHERE company_id = ? 
                  AND tipo = 'Tarea' 
                  AND status IN ('En tiempo', 'En proceso')
            ");
            $stmtEnProceso->execute([$companyId]);
            $enProceso = $stmtEnProceso->fetch(PDO::FETCH_ASSOC)['en_proceso'];
            
            // Tareas vencidas
            $stmtVencidas = $db->prepare("
                SELECT COUNT(*) as vencidas 
                FROM tasks 
                WHERE company_id = ? 
                  AND tipo = 'Tarea' 
                  AND status = 'Vencida'
            ");
            $stmtVencidas->execute([$companyId]);
            $vencidas = $stmtVencidas->fetch(PDO::FETCH_ASSOC)['vencidas'];
            
            // Tareas completadas
            $stmtCompletadas = $db->prepare("
                SELECT COUNT(*) as completadas 
                FROM tasks 
                WHERE company_id = ? 
                  AND tipo = 'Tarea' 
                  AND status = 'Terminada'
            ");
            $stmtCompletadas->execute([$companyId]);
            $completadas = $stmtCompletadas->fetch(PDO::FETCH_ASSOC)['completadas'];
            
            echo json_encode([
                'ok' => true,
                'stats' => [
                    'total' => (int)$total,
                    'en_proceso' => (int)$enProceso,
                    'vencidas' => (int)$vencidas,
                    'completadas' => (int)$completadas
                ]
            ]);
        } catch (Exception $e) {
            error_log("Error en get_stats_tasks: " . $e->getMessage());
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    // ========== NOTIFICACIONES (FUTURO) ==========
    
    case 'notify_user':
        if (!hasPermission($ucId, 'processes_tasks', 'edit')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sin permisos para enviar notificaciones']);
            exit;
        }
        
        // Preparado para integración futura con módulo Chat/Email
        $userId = (int)($_POST['user_id'] ?? 0);
        $taskId = (int)($_POST['task_id'] ?? 0);
        $message = $_POST['message'] ?? '';
        
        // TODO: Implementar integración con sistema de notificaciones
        echo json_encode([
            'ok' => true,
            'message' => 'Funcionalidad de notificaciones en desarrollo',
            'data' => ['user_id' => $userId, 'task_id' => $taskId]
        ]);
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
        break;
}

/**
 * Función auxiliar para verificar CSRF token
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
