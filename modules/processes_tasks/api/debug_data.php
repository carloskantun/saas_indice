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
    
    echo json_encode([
        'ok' => true,
        'debug_info' => [
            'current_user_id' => $userId,
            'current_company_id' => $ucId,
            'session_data' => [
                'user_id' => $_SESSION['user_id'] ?? 'NO_SET',
                'company_id' => $_SESSION['company_id'] ?? 'NO_SET',
                'user_name' => $_SESSION['user_name'] ?? 'NO_SET'
            ]
        ],
        'queries' => [
            'hr_employees_total' => $pdo->prepare("SELECT COUNT(*) as count FROM hr_employees WHERE company_id = ?")->execute([$ucId]) ? $pdo->query("SELECT COUNT(*) as count FROM hr_employees WHERE company_id = $ucId")->fetch()['count'] : 0,
            'hr_employees_active' => $pdo->prepare("SELECT COUNT(*) as count FROM hr_employees WHERE company_id = ? AND status = 'activo'")->execute([$ucId]) ? $pdo->query("SELECT COUNT(*) as count FROM hr_employees WHERE company_id = $ucId AND status = 'activo'")->fetch()['count'] : 0,
            'users_total' => $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE company_id = ?")->execute([$ucId]) ? $pdo->query("SELECT COUNT(*) as count FROM users WHERE company_id = $ucId")->fetch()['count'] : 0,
            'current_user_exists' => $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE id = ?")->execute([$userId]) ? $pdo->query("SELECT COUNT(*) as count FROM users WHERE id = $userId")->fetch()['count'] : 0,
            'current_user_in_hr' => $pdo->prepare("SELECT COUNT(*) as count FROM hr_employees WHERE user_id = ?")->execute([$userId]) ? $pdo->query("SELECT COUNT(*) as count FROM hr_employees WHERE user_id = $userId")->fetch()['count'] : 0
        ],
        'sample_data' => [
            'hr_employees' => $pdo->query("SELECT id, full_name, status, company_id FROM hr_employees WHERE company_id = $ucId LIMIT 3")->fetchAll(),
            'users' => $pdo->query("SELECT id, name, email, company_id FROM users WHERE company_id = $ucId LIMIT 3")->fetchAll()
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}