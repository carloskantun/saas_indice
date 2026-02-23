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
    
    // Test directo de tabla hr_employees con ALIAS EXPLÍCITO para evitar confusion
    $sql = "SELECT id, full_name AS name, status, company_id FROM hr_employees ORDER BY id DESC LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $allEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Test con filtro de company
    $sql = "SELECT id, full_name AS name, status, company_id FROM hr_employees WHERE company_id = ? ORDER BY id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ucId]);
    $companyEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Test usuarios
    $sql = "SELECT id, name, company_id FROM users WHERE company_id = ? ORDER BY id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ucId]);
    $companyUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'ok' => true,
        'current_user_id' => $userId,
        'current_company_id' => $ucId,
        'all_employees' => $allEmployees,
        'company_employees' => $companyEmployees,
        'company_users' => $companyUsers,
        'counts' => [
            'total_hr_employees' => count($allEmployees),
            'company_hr_employees' => count($companyEmployees),
            'company_users' => count($companyUsers)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}