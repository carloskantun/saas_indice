<?php
require __DIR__ . '/../../bootstrap.php';
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
require __DIR__ . '/../../core/auth.php';
require __DIR__ . '/../../core/permissions.php';
require __DIR__ . '/../../core/scope.php';

requireLogin();
$ucId = currentUserCompany();
$roleInfo = getUserModuleRole($ucId, 'expenses');
$action = $_GET['action'] ?? 'view';
if (!canAction('expenses', $action, $roleInfo['role'], $roleInfo['skill_level'])) {
    http_response_code(403);
    exit;
}

if ($action === 'export') {
    list($where, $params) = scopeWhereClause(['company_id' => $ucId], $_GET, 'e');
    $query = "SELECT e.* FROM expenses e $where ORDER BY e.pay_date DESC";
    $stmt = db()->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="expenses.csv"');
    $out = fopen('php://output', 'w');
    if ($rows) {
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $r) {
            fputcsv($out, $r);
        }
    }
    fclose($out);
    exit;
}

header('Location: index.php');
exit;
