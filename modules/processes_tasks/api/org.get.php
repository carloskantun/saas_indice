<?php
require __DIR__ . '/../../../bootstrap.php';
require __DIR__ . '/../../../core/auth.php';
require __DIR__ . '/../../../core/permissions.php';
require __DIR__ . '/../../../core/scope.php';

header('Content-Type: application/json; charset=utf-8');

requireLogin();

$companyId = (int)currentUserCompany();
$userId = (int)currentUserId();

if (!$companyId || !$userId) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'no_context']);
    exit;
}

if (!hasPermission($companyId, 'processes_tasks', 'view')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'no_permission']);
    exit;
}

try {
    $pdo = db();

    // Rol de compañía
    $stmtRole = $pdo->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ? AND status = 'active' LIMIT 1");
    $stmtRole->execute([$userId, $companyId]);
    $companyRole = (string)($stmtRole->fetchColumn() ?: '');

    $isSuperadmin = in_array($companyRole, ['root', 'superadmin'], true);

    // Scope: superadmin ignora; cualquier otro rol (incl admin) aplica scope
    $scope = $isSuperadmin ? ['type' => 'all', 'units' => [], 'businesses' => []] : getUserScope($userId, $companyId);

    // Devolver asignaciones para el usuario actual (owner_user_id)
    $stmt = $pdo->prepare('SELECT target_user_id, unit_id, business_id, created_at, created_by FROM processes_tasks_org_access WHERE company_id = ? AND owner_user_id = ?');
    $stmt->execute([$companyId, $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Si aplica scope limitado, filtrar targets a los que el usuario puede acceder (best-effort vía hr_employees)
    if (!$isSuperadmin && ($scope['type'] ?? '') === 'limited' && (!empty($scope['units']) || !empty($scope['businesses'])) && $rows) {
        $targetIds = array_values(array_unique(array_map(fn($r) => (int)$r['target_user_id'], $rows)));
        $allowed = [];

        $conds = ['he.company_id = ?'];
        $params = [$companyId];

        $phT = implode(',', array_fill(0, count($targetIds), '?'));
        $conds[] = "he.user_id IN ($phT)";
        $params = array_merge($params, $targetIds);

        if (!empty($scope['units'])) {
            $phU = implode(',', array_fill(0, count($scope['units']), '?'));
            $conds[] = "he.unit_id IN ($phU)";
            $params = array_merge($params, $scope['units']);
        }
        if (!empty($scope['businesses'])) {
            $phB = implode(',', array_fill(0, count($scope['businesses']), '?'));
            $conds[] = "he.business_id IN ($phB)";
            $params = array_merge($params, $scope['businesses']);
        }

        $stmtAllowed = $pdo->prepare('SELECT DISTINCT he.user_id FROM hr_employees he WHERE ' . implode(' AND ', $conds));
        $stmtAllowed->execute($params);
        while ($uid = $stmtAllowed->fetchColumn()) {
            $allowed[(int)$uid] = true;
        }

        $rows = array_values(array_filter($rows, fn($r) => !empty($allowed[(int)$r['target_user_id']])));
    }

    echo json_encode([
        'ok' => true,
        'items' => $rows,
        'count' => count($rows),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[processes_tasks:org.get] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server_error']);
}
