<?php
/**
 * /modules/processes_tasks/includes/projects.php
 * Retorna lista de proyectos activos para selector
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../../core/auth.php';
require_once __DIR__ . '/../../../core/permissions.php';
require_once __DIR__ . '/../../../core/scope.php';

if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
}

requireLogin();
$ucId = (int)currentUserCompany();
$userId = (int)currentUserId();
if (!$ucId) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

if (!hasPermission($ucId, 'processes_tasks', 'view')) {
    $logLine = json_encode([
        'ts' => date('c'),
        'endpoint' => 'includes/projects.php',
        'reason' => 'no_permission:view',
        'user_id' => $_SESSION['user_id'] ?? null,
        'company_id' => $ucId,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ], JSON_UNESCAPED_SLASHES);
    @file_put_contents(__DIR__ . '/../logs/legacy_access_denied.log', $logLine . "\n", FILE_APPEND | LOCK_EX);
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$pdo = db();

$stmtRole = $pdo->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ? AND status = 'active' LIMIT 1");
$stmtRole->execute([$userId, $ucId]);
$companyRole = (string)($stmtRole->fetchColumn() ?: '');
$isSuperadmin = in_array($companyRole, ['root', 'superadmin'], true);

try {
    $where = [
        'company_id = ?',
        'proyecto IS NOT NULL',
        "proyecto != ''",
    ];
    $params = [$ucId];

    if (!$isSuperadmin) {
        $scope = getUserScope($userId, $ucId);
        if (($scope['type'] ?? '') === 'limited') {
            if (!empty($scope['units'])) {
                $ph = implode(',', array_fill(0, count($scope['units']), '?'));
                $where[] = "unit_id IN ($ph)";
                $params = array_merge($params, $scope['units']);
            }
            if (!empty($scope['businesses'])) {
                $ph = implode(',', array_fill(0, count($scope['businesses']), '?'));
                $where[] = "business_id IN ($ph)";
                $params = array_merge($params, $scope['businesses']);
            }
        }
    }

    $stmt = $pdo->prepare("
        SELECT DISTINCT proyecto AS label, proyecto AS value
        FROM tasks
        WHERE " . implode(' AND ', $where) . "
        ORDER BY proyecto
    ");
    $stmt->execute($params);
    
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($projects);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([]);
}
