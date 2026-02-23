<?php
require __DIR__ . '/../../../bootstrap.php';
require __DIR__ . '/../../../core/auth.php';
require __DIR__ . '/../../../core/permissions.php';
require __DIR__ . '/../../../core/scope.php';
require __DIR__ . '/../includes/csrf_helper.php';

header('Content-Type: application/json; charset=utf-8');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

requireLogin();
requireCSRF();

$companyId = (int)currentUserCompany();
$userId = (int)currentUserId();

if (!$companyId || !$userId) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'no_context']);
    exit;
}

// Permiso: assign (preferente) o edit (fallback)
if (!hasPermission($companyId, 'processes_tasks', 'assign') && !hasPermission($companyId, 'processes_tasks', 'edit')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'no_permission']);
    exit;
}

try {
    $pdo = db();

    // Rol de compañía (fuente de verdad)
    $stmtRole = $pdo->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ? AND status = 'active' LIMIT 1");
    $stmtRole->execute([$userId, $companyId]);
    $companyRole = (string)($stmtRole->fetchColumn() ?: '');

    $isSuperadmin = in_array($companyRole, ['root', 'superadmin'], true);

    // Leer JSON
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true) ?: [];

    $relations = $payload['relations'] ?? [];
    if (!is_array($relations)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'invalid_payload']);
        exit;
    }

    // Extraer pares parent->child (IDs vienen del UI; hoy son hr_employees.id)
    $pairs = [];
    foreach ($relations as $rel) {
        $parent = isset($rel['parent_id']) ? (int)$rel['parent_id'] : 0;
        $child = isset($rel['child_id']) ? (int)$rel['child_id'] : 0;
        if ($parent > 0 && $child > 0 && $parent !== $child) {
            $pairs[] = [$parent, $child];
        }
    }

    // Deduplicar
    $uniq = [];
    foreach ($pairs as $p) {
        $uniq[$p[0] . ':' . $p[1]] = $p;
    }
    $pairs = array_values($uniq);

    // Mapear hr_employees.id -> users.id (user_id)
    // Nota: si ya fueran users.id, también intentamos mapear por user_id directo.
    $employeeIds = [];
    foreach ($pairs as $p) {
        $employeeIds[] = $p[0];
        $employeeIds[] = $p[1];
    }
    $employeeIds = array_values(array_unique(array_filter($employeeIds)));

    $employeeToUser = [];
    if ($employeeIds) {
        $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
        $params = array_merge([$companyId], $employeeIds);
        $stmtMap = $pdo->prepare("SELECT id, user_id FROM hr_employees WHERE company_id = ? AND id IN ($placeholders)");
        $stmtMap->execute($params);
        while ($row = $stmtMap->fetch(PDO::FETCH_ASSOC)) {
            $eid = (int)($row['id'] ?? 0);
            $uid = (int)($row['user_id'] ?? 0);
            if ($eid && $uid) {
                $employeeToUser[$eid] = $uid;
            }
        }
    }

    $ownerTargetPairs = [];
    $rejected = [];

    foreach ($pairs as [$parentEmpId, $childEmpId]) {
        $ownerUserId = $employeeToUser[$parentEmpId] ?? 0;
        $targetUserId = $employeeToUser[$childEmpId] ?? 0;

        // Si no mapearon como hr_employee, intentar tratarlos como users.id (compat)
        if (!$ownerUserId) {
            $ownerUserId = $parentEmpId;
        }
        if (!$targetUserId) {
            $targetUserId = $childEmpId;
        }

        if ($ownerUserId <= 0 || $targetUserId <= 0 || $ownerUserId === $targetUserId) {
            $rejected[] = ['parent_id' => $parentEmpId, 'child_id' => $childEmpId, 'reason' => 'invalid_mapping'];
            continue;
        }

        $ownerTargetPairs[] = [$ownerUserId, $targetUserId];
    }

    // Scope: superadmin ignora; cualquier otro rol (incl admin) aplica scope unit/business
    $scope = $isSuperadmin ? ['type' => 'all', 'units' => [], 'businesses' => []] : getUserScope($userId, $companyId);

    // Validar que targets caen dentro del scope del configurador (si limited)
    // Se valida contra hr_employees (user_id -> unit_id/business_id)
    $allowedTargetSet = null;
    if (!$isSuperadmin && ($scope['type'] ?? '') === 'limited' && (!empty($scope['units']) || !empty($scope['businesses']))) {
        $targetIds = array_values(array_unique(array_map(fn($p) => (int)$p[1], $ownerTargetPairs)));
        if ($targetIds) {
            $conds = ['he.company_id = ?'];
            $params = [$companyId];

            $ph = implode(',', array_fill(0, count($targetIds), '?'));
            $conds[] = "he.user_id IN ($ph)";
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
            $allowedTargetSet = [];
            while ($uid = $stmtAllowed->fetchColumn()) {
                $allowedTargetSet[(int)$uid] = true;
            }
        }
    }

    $finalPairs = [];
    foreach ($ownerTargetPairs as [$ownerUserId, $targetUserId]) {
        if ($allowedTargetSet !== null && empty($allowedTargetSet[$targetUserId])) {
            $rejected[] = ['owner_user_id' => $ownerUserId, 'target_user_id' => $targetUserId, 'reason' => 'out_of_scope'];
            continue;
        }
        $finalPairs[] = [$ownerUserId, $targetUserId];
    }

    // Guardar (reemplazo total por compañía) dentro de transacción
    $pdo->beginTransaction();

    // Para mínimo cambio: reemplazo total de la tabla por compañía
    $stmtDel = $pdo->prepare('DELETE FROM processes_tasks_org_access WHERE company_id = ?');
    $stmtDel->execute([$companyId]);

    if ($finalPairs) {
        $stmtIns = $pdo->prepare('INSERT INTO processes_tasks_org_access (company_id, owner_user_id, target_user_id, unit_id, business_id, created_by, created_at) VALUES (?, ?, ?, NULL, NULL, ?, NOW())');
        foreach ($finalPairs as [$ownerUserId, $targetUserId]) {
            $stmtIns->execute([$companyId, $ownerUserId, $targetUserId, $userId]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'saved' => count($finalPairs),
        'rejected' => $rejected,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[processes_tasks:org.save] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server_error']);
}
