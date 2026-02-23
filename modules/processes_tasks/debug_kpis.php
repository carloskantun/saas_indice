<?php
/**
 * Debug KPIs - Para verificar qué datos llegan desde el API
 */

require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/../../core/auth.php';

requireLogin();
$companyId = $_SESSION['company_id'] ?? 1;

echo "<h2>Debug KPIs - Empresa ID: {$companyId}</h2>";

// Test directo al controlador API
$url = '/modules/processes_tasks/controllers/api.controller.php';

// Simular petición getTasks
$postData = json_encode(['action' => 'getTasks']);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'X-CSRF-Token: ' . ($_SESSION['csrf_token'] ?? '')
        ],
        'content' => $postData
    ]
]);

echo "<h3>1. Test getTasks API:</h3>";
echo "<pre>";

try {
    $response = file_get_contents($_SERVER['DOCUMENT_ROOT'] . $url, false, $context);
    echo htmlspecialchars($response);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "</pre>";

// Test directo al modelo
echo "<h3>2. Test TasksModel::list() directo:</h3>";
echo "<pre>";

try {
    require __DIR__ . '/models/tasks.model.php';
    
    $tasks = TasksModel::list(['company_id' => $companyId]);
    echo "Total tareas encontradas: " . count($tasks) . "\n";
    
    if (count($tasks) > 0) {
        echo "Primera tarea:\n";
        print_r($tasks[0]);
    }
    
    echo "\nEstadísticas rápidas:\n";
    $stats = [
        'total' => count($tasks),
        'completadas' => 0,
        'en_proceso' => 0,
        'vencidas' => 0,
        'urgentes' => 0
    ];
    
    foreach ($tasks as $task) {
        if ($task['status'] === 'Terminada') $stats['completadas']++;
        if ($task['status'] === 'En proceso') $stats['en_proceso']++;
        if ($task['status'] === 'Vencida') $stats['vencidas']++;
        if ($task['nivel'] === 'Urgente') $stats['urgentes']++;
    }
    
    print_r($stats);
    
} catch (Exception $e) {
    echo "Error en modelo: " . $e->getMessage();
}

echo "</pre>";

?>