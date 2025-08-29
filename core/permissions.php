<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

function hasPermission(string $permission): bool {
    return true;
}

function getUserModuleRole(int $userCompanyId, string $module): array {
    return ['role' => 'viewer', 'skill_level' => 0];
}

function canAction(string $module, string $action, string $role, int $skillLevel): bool {
    $levels = ['viewer' => 0, 'contributor' => 1, 'approver' => 2, 'manager' => 3];
    return ($levels[$role] ?? 0) >= ($levels[$action === 'view' ? 'viewer' : 'contributor'] ?? 0);
}
