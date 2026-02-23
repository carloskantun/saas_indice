<?php
require __DIR__.'/../../../bootstrap.php';
require __DIR__.'/../../../core/auth.php';
require __DIR__.'/../../../core/permissions.php';
header('Content-Type: application/json; charset=utf-8');

requireLogin();
$companyId = currentUserCompany();
if (!$companyId) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'no_company']); exit; }
if (!hasPermission($companyId, 'processes_tasks', 'view')) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'no_permission']); exit; }

try {
    $pdo = db();
    $sql = 'SELECT id, employee_code, full_name, department, position FROM hr_employees WHERE company_id = ? AND (status = "activo" OR status IS NULL) ORDER BY full_name ASC LIMIT 1000';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$companyId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $data = array_map(function($r){
        return [
            'id' => (int)$r['id'],
            'code' => $r['employee_code'] ?? '',
            'name' => $r['full_name'] ?? '',
            'dept' => $r['department'] ?? '',
            'title' => $r['position'] ?? ''
        ];
    }, $rows);
    echo json_encode(['ok'=>true,'items'=>$data]);
} catch (Throwable $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) error_log('[processes_tasks:hr_users] '.$e->getMessage());
    echo json_encode(['ok'=>false,'items'=>[]]);
}
