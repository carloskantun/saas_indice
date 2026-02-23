<?php
/**
 * /modules/processes_tasks/includes/users.php
 * Retorna lista de usuarios activos para selectores
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
        'endpoint' => 'includes/users.php',
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
    // Intentar obtener usuarios de la tabla users o usuarios
    $tableExists = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
    
    if ($tableExists) {
        $stmt = $pdo->prepare("
            SELECT 
                CONCAT(name, ' ', COALESCE(last_name, '')) AS label,
                CONCAT(name, ' ', COALESCE(last_name, '')) AS value
            FROM users
            WHERE company_id = ?
            AND active = 1
            ORDER BY name
        ");
        $stmt->execute([$ucId]);
    } else {
        // Fallback: buscar en tasks creador y delegado (respetando scope)
        $where = ['company_id = ?'];
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

        $whereSql = implode(' AND ', $where);
        $stmt = $pdo->prepare("
            SELECT DISTINCT creador AS label, creador AS value 
            FROM tasks 
            WHERE $whereSql AND creador IS NOT NULL AND creador != ''
            UNION
            SELECT DISTINCT delegado AS label, delegado AS value 
            FROM tasks 
            WHERE $whereSql AND delegado IS NOT NULL AND delegado != ''
            ORDER BY label
        ");
        $stmt->execute(array_merge($params, $params));
    }
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($users);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([]);
}
