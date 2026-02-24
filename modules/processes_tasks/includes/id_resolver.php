<?php

if (!function_exists('normalizePositiveInt')) {
    /**
     * Normaliza IDs que llegan como int|string|float|null.
     * Devuelve int (>0) o null.
     */
    function normalizePositiveInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null;
            }
            // Solo dígitos (evita "22abc" / "22e0")
            if (!ctype_digit($value)) {
                return null;
            }
            $value = (int)$value;
        }

        if (is_float($value)) {
            // Aceptar floats enteros (p.ej. 22.0) como compat extra
            if ($value <= 0 || floor($value) !== $value) {
                return null;
            }
            $value = (int)$value;
        }

        if (!is_int($value) || $value <= 0) {
            return null;
        }

        return $value;
    }
}

if (!function_exists('isUsersIdInCompany')) {
    function isUsersIdInCompany(int $userId, int $companyId): bool
    {
        if ($userId <= 0 || $companyId <= 0) {
            return false;
        }

        static $memo = []; // per-request memoization
        $key = $companyId . ':' . $userId;
        if (array_key_exists($key, $memo)) {
            return (bool)$memo[$key];
        }

        $pdo = db();
        $stmt = $pdo->prepare("SELECT 1 FROM user_companies WHERE user_id = ? AND company_id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$userId, $companyId]);
        $memo[$key] = (bool)$stmt->fetchColumn();
        return (bool)$memo[$key];
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

        $id = normalizePositiveInt($mixedId);
        if ($id === null) {
            return null;
        }

        static $memo = []; // per-request memoization
        $key = $companyId . ':' . $id;
        if (array_key_exists($key, $memo)) {
            return $memo[$key]; // int|null
        }

        // 1) Treat as users.id when membership is active.
        if (isUsersIdInCompany($id, $companyId)) {
            $memo[$key] = $id;
            return $memo[$key];
        }

        // 2) Treat as hr_employees.id (same company) -> user_id
        $pdo = db();
        $stmt = $pdo->prepare('SELECT he.user_id FROM hr_employees he WHERE he.id = ? AND he.company_id = ? AND he.user_id IS NOT NULL LIMIT 1');
        $stmt->execute([$id, $companyId]);
        $userId = (int)($stmt->fetchColumn() ?: 0);
        if ($userId > 0 && isUsersIdInCompany($userId, $companyId)) {
            $memo[$key] = $userId;
            return $memo[$key];
        }

        $memo[$key] = null;
        return $memo[$key];
    }
}
