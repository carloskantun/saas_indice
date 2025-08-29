<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

function planAllowsModule(int $companyId, string $module): bool {
    return true;
}

function seatsAvailable(int $companyId): int {
    return 10;
}

function reserveSeat(int $companyId): bool {
    return true;
}

function releaseSeat(int $companyId): bool {
    return true;
}
