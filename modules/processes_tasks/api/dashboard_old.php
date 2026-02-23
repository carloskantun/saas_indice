<?php
/**
 * API Dashboard - Processes & Tasks
 * Agenda del Día - Tareas diarias del equipo
 */
if (ob_get_level()) { ob_clean(); }

require __DIR__.'/../../../bootstrap.php';
require __DIR__.'/../../../core/auth.php';
require __DIR__.'/../../../core/permissions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requireLogin();
    $ucId = currentUserCompany();
    $userId = currentUserId();

    if (!defined('APP_DEBUG') || !APP_DEBUG) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'not_found']);
        exit;
    }

    $pdo = db();
    $stmtRole = $pdo->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ? AND status = 'active' LIMIT 1");
    $stmtRole->execute([(int)$userId, (int)$ucId]);
    $companyRole = (string)($stmtRole->fetchColumn() ?: '');
    if (!in_array($companyRole, ['root', 'superadmin'], true)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'forbidden']);
        exit;
    }

    if (!hasPermission($ucId, 'processes_tasks', 'view')) { 
        http_response_code(403); 
        echo json_encode(['ok'=>false,'error'=>'Sin permisos']); 
        exit; 
    }

    // $pdo ya inicializado arriba
    
    // Obtener employee_id del usuario actual
    $stmtEmp = $pdo->prepare("SELECT id FROM hr_employees WHERE user_id = ? AND company_id = ? LIMIT 1");
    $stmtEmp->execute([$userId, $ucId]);
    $employeeId = $stmtEmp->fetchColumn();
    
    if (!$employeeId) {
        echo json_encode([
            'ok' => true,
            'tasks' => [],
            'stats' => [
                'pending' => 0,
                'overdue' => 0,
                'completed' => 0,
                'total' => 0,
                'completion_percentage' => 0
            ],
            'message' => 'Usuario sin empleado asociado'
        ]);
        exit;
    }

    // Obtener tareas completas con joins
    $sql = "
        SELECT 
            t.id,
            t.titulo as title,
            t.descripcion as description,
            t.fecha_inicio as start_date,
            t.fecha_entrega as due_date,
            t.status,
            t.prioridad as priority,
            t.archivo as archivos,
            t.tipo as task_type,
            t.nivel,
            t.created_at,
            t.updated_at,
            creator.full_name as creator_name,
            delegate.full_name as assignee_name,
            unit.name as unit_name,
            business.name as business_name
        FROM tasks t
        LEFT JOIN hr_employees creator ON creator.id = t.usuario_creador AND creator.company_id = t.company_id
        LEFT JOIN hr_employees delegate ON delegate.id = t.usuario_delegado AND delegate.company_id = t.company_id
        LEFT JOIN units unit ON unit.id = t.unidad_id AND unit.company_id = t.company_id
        LEFT JOIN businesses business ON business.id = t.negocio_id AND business.company_id = t.company_id
        WHERE t.company_id = ?
          AND t.tipo = 'Tarea'
          AND (t.usuario_delegado = ? OR t.usuario_creador = ?)
        ORDER BY 
            CASE 
                WHEN t.status = 'Vencida' THEN 1
                WHEN t.status = 'En proceso' THEN 2
                WHEN t.status = 'En tiempo' THEN 3
                ELSE 4
            END,
            t.fecha_entrega ASC,
            t.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ucId, $employeeId, $employeeId]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    // Calcular estadísticas
    $stats = [
        'pending' => 0,
        'overdue' => 0,
        'completed' => 0,
        'total' => count($tasks),
        'completion_percentage' => 0
    ];
    
    foreach ($tasks as $task) {
        $taskStatus = $task['status'];
        
        if ($taskStatus === 'Vencida') {
            $stats['overdue']++;
            $stats['pending']++; // Vencidas también son pendientes
        } elseif ($taskStatus === 'En proceso' || $taskStatus === 'En tiempo') {
            $stats['pending']++;
        } elseif ($taskStatus === 'Terminada' || $taskStatus === 'Auditada') {
            $stats['completed']++;
        }
    }
    
    // Calcular porcentaje de completado
    if ($stats['total'] > 0) {
        $stats['completion_percentage'] = round(($stats['completed'] / $stats['total']) * 100);
    }
    
    echo json_encode([
        'ok' => true,
        'tasks' => $tasks,
        'stats' => $stats,
        'employee_id' => $employeeId,
        'user_id' => $userId,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log('[Dashboard API] PDO Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'database_error',
        'message' => 'Error al cargar datos del dashboard',
        'stats' => [
            'pending' => 0,
            'overdue' => 0,
            'completed' => 0,
            'total' => 0,
            'completion_percentage' => 0
        ],
        'tasks' => []
    ]);
} catch (Exception $e) {
    error_log('[Dashboard API] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'server_error',
        'message' => 'Error interno del servidor',
        'stats' => [
            'pending' => 0,
            'overdue' => 0,
            'completed' => 0,
            'total' => 0,
            'completion_percentage' => 0
        ],
        'tasks' => []
    ]);
}
