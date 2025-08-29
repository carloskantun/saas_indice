<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

function scopeWhereClause(array $userCompany, array $filters, string $alias = ''): array {
    $where = 'WHERE 1=1';
    $params = [];
    return [$where, $params];
}
