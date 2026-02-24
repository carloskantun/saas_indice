<?php

if (!function_exists('isUsersIdInCompany')) {
    function isUsersIdInCompany(int $userId, int $companyId): bool
    {
        if ($userId <= 0 || $companyId <= 0) {
            return false;
        }

        $pdo = db();
        $stmt = $pdo->prepare("SELECT 1 FROM user_companies WHERE user_id = ? AND company_id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$userId, $companyId]);
        return (bool)$stmt->fetchColumn();
    }
}

if (!function_exists('resolveUserIdFromMixed')) {
    /**
     * Resolve a mixed identifier (users.id or hr_employees.id) into canonical users.id.
     * Never resolves cross-company.
     */
    function resolveUserIdFromMixed($mixedId, int $companyId): ?int
    {
        if ($companyId <= 0) {
            return null;
        }

        if ($mixedId === null || $mixedId === '') {
            return null;
        }

        if (is_string($mixedId)) {
            $mixedId = trim($mixedId);
            if ($mixedId === '') {
                return null;
            }
        }

        if (!is_numeric($mixedId)) {
            return null;
        }

        $id = (int)$mixedId;
        if ($id <= 0) {
            return null;
        }

        // 1) Treat as users.id when membership is active.
        if (isUsersIdInCompany($id, $companyId)) {
            return $id;
        }

        // 2) Treat as hr_employees.id (same company) -> user_id
        $pdo = db();
        $stmt = $pdo->prepare('SELECT he.user_id FROM hr_employees he WHERE he.id = ? AND he.company_id = ? AND he.user_id IS NOT NULL LIMIT 1');
        $stmt->execute([$id, $companyId]);
        $userId = (int)($stmt->fetchColumn() ?: 0);
        if ($userId > 0 && isUsersIdInCompany($userId, $companyId)) {
            return $userId;
        }

        return null;
    }
}
