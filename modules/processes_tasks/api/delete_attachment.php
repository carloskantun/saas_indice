<?php
require __DIR__.'/../../../bootstrap.php';
require __DIR__.'/../../../core/auth.php';
require __DIR__.'/../../../core/permissions.php';
require __DIR__.'/../../../core/scope.php';
header('Content-Type: application/json');

requireLogin();
$ucId = (int)currentUserCompany();
$userId = (int)currentUserId();
if (!hasPermission($ucId, 'processes_tasks', 'edit')) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'no_permission']); exit; }

$taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
$attachId = isset($_POST['attach_id']) ? (int)$_POST['attach_id'] : 0;
if (!$taskId || !$attachId) { echo json_encode(['ok'=>false,'error'=>'missing_params']); exit; }

try {
    $pdo = db();

    // Verifica acceso a la tarea (evita borrado por enumeración de IDs)
    $stmtTask = $pdo->prepare("SELECT id, unit_id, business_id, usuario_creador, usuario_delegado FROM tasks WHERE id = ? AND company_id = ? LIMIT 1");
    $stmtTask->execute([$taskId, $ucId]);
    $task = $stmtTask->fetch(PDO::FETCH_ASSOC);
    if (!$task) { echo json_encode(['ok'=>false,'error'=>'not_found']); exit; }

    $stmtRole = $pdo->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ? AND status = 'active' LIMIT 1");
    $stmtRole->execute([$userId, $ucId]);
    $companyRole = (string)($stmtRole->fetchColumn() ?: '');
    $isSuperadmin = in_array($companyRole, ['root', 'superadmin'], true);
    $isAdmin = ($companyRole === 'admin');

    if (!$isSuperadmin) {
        $scope = getUserScope($userId, $ucId);
        if (($scope['type'] ?? '') === 'limited') {
            if (!empty($scope['units'])) {
                if (!in_array((int)($task['unit_id'] ?? 0), array_map('intval', $scope['units']), true)) {
                    http_response_code(403);
                    echo json_encode(['ok'=>false,'error'=>'forbidden']);
                    exit;
                }
            }
            if (!empty($scope['businesses'])) {
                if (!in_array((int)($task['business_id'] ?? 0), array_map('intval', $scope['businesses']), true)) {
                    http_response_code(403);
                    echo json_encode(['ok'=>false,'error'=>'forbidden']);
                    exit;
                }
            }
        }
    }

    if (!$isSuperadmin && !$isAdmin) {
        $stmtAllowed = $pdo->prepare('SELECT target_user_id FROM processes_tasks_org_access WHERE company_id = ? AND owner_user_id = ?');
        $stmtAllowed->execute([$ucId, $userId]);
        $allowedUserIds = array_map('intval', $stmtAllowed->fetchAll(PDO::FETCH_COLUMN) ?: []);

        $allowedAssigneeIds = $allowedUserIds;
        if (!empty($allowedUserIds)) {
            $ph = implode(',', array_fill(0, count($allowedUserIds), '?'));
            $paramsEmp = array_merge([$ucId], $allowedUserIds);
            $stmtEmp = $pdo->prepare("SELECT id FROM hr_employees WHERE company_id = ? AND user_id IN ($ph)");
            $stmtEmp->execute($paramsEmp);
            $empIds = array_map('intval', $stmtEmp->fetchAll(PDO::FETCH_COLUMN) ?: []);
            $allowedAssigneeIds = array_values(array_unique(array_merge($allowedAssigneeIds, $empIds)));
        }

        $creatorId = (int)($task['usuario_creador'] ?? 0);
        $assigneeId = (int)($task['usuario_delegado'] ?? 0);

        if (empty($allowedAssigneeIds)) {
            if ($creatorId !== $userId) {
                http_response_code(403);
                echo json_encode(['ok'=>false,'error'=>'forbidden']);
                exit;
            }
        } else {
            if (!($creatorId === $userId || in_array($assigneeId, $allowedAssigneeIds, true))) {
                http_response_code(403);
                echo json_encode(['ok'=>false,'error'=>'forbidden']);
                exit;
            }
        }
    }

    $stmt = $pdo->prepare('SELECT filename, meta FROM task_attachments WHERE id=? AND task_id=? AND company_id=? LIMIT 1');
    $stmt->execute([$attachId, $taskId, $ucId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { echo json_encode(['ok'=>false,'error'=>'not_found']); exit; }
    $meta = json_decode($row['meta'] ?? 'null', true) ?: [];
    if (!empty($meta['path'])) {
        $p = __DIR__.'/../uploads/'.$meta['path'];
        if (is_file($p)) @unlink($p);
    }
    $pdo->prepare('DELETE FROM task_attachments WHERE id=? AND company_id=?')->execute([$attachId, $ucId]);
    echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) error_log('[processes_tasks:delete_attachment] '.$e->getMessage());
    echo json_encode(['ok'=>false,'error'=>'server_error']);
}
