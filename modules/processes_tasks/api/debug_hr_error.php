<?php
require __DIR__.'/../../../bootstrap.php';
require __DIR__.'/../../../core/auth.php';
require __DIR__.'/../../../core/permissions.php';
header('Content-Type: application/json');

requireLogin();
$ucId = (int)currentUserCompany();
$userId = (int)currentUserId();

if (!defined('APP_DEBUG') || !APP_DEBUG) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'not_found']);
    exit;
}

if (!hasPermission($ucId, 'processes_tasks', 'view')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'no_permission']);
    exit;
}

$pdo = db();
$stmtRole = $pdo->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ? AND status = 'active' LIMIT 1");
$stmtRole->execute([$userId, $ucId]);
$companyRole = (string)($stmtRole->fetchColumn() ?: '');
if (!in_array($companyRole, ['root', 'superadmin'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

try {
    // $pdo ya inicializado arriba
    
    // Verificar estructura de tabla hr_employees
    $columns = $pdo->query("DESCRIBE hr_employees")->fetchAll(PDO::FETCH_ASSOC);
    
    // Test con consulta correcta
    $sql1 = "SELECT id, full_name as name, status FROM hr_employees WHERE company_id = ? LIMIT 5";
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute([$ucId]);
    $correct_query = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    
    // Test con consulta incorrecta (la que está fallando)
    try {
        $sql2 = "SELECT id, name, status FROM hr_employees WHERE company_id = ? LIMIT 5";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([$ucId]);
        $incorrect_query = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e2) {
        $incorrect_query = "ERROR: " . $e2->getMessage();
    }
    
    echo json_encode([
        'ok' => true,
        'table_structure' => $columns,
        'correct_query_result' => $correct_query,
        'incorrect_query_result' => $incorrect_query,
        'company_id' => $ucId,
        'user_id' => $userId
    ]);
    
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}