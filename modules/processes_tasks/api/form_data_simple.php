<?php
require __DIR__.'/../../../bootstrap.php';
require __DIR__.'/../../../core/auth.php';
header('Content-Type: application/json');

requireLogin();
$ucId = currentUserCompany();
$userId = 0;
if (function_exists('currentUserId')) {
    $userId = (int)(currentUserId() ?? 0);
}
if (!$userId && !empty($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
}

if (!$ucId) {
    echo json_encode(['ok' => false, 'error' => 'No company']);
    exit;
}

try {
    $pdo = db();
    $result = ['ok' => true];

    $colExists = static function (PDO $pdo, string $table, string $column): bool {
        try {
            $st = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
            $st->execute([$column]);
            return (bool)$st->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return false;
        }
    };
    
    // 1. Intentar cargar empleados activos de HR primero
    $sql = "SELECT id, user_id, full_name AS name, employee_code, position, department 
            FROM hr_employees 
            WHERE company_id = ? 
            AND full_name IS NOT NULL 
            AND status = 'activo'
            ORDER BY full_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ucId]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Si no hay empleados activos, intentar con cualquier status
    if (count($employees) === 0) {
        $sql = "SELECT id, user_id, full_name AS name, employee_code, position, department 
                FROM hr_employees 
                WHERE company_id = ? 
                AND full_name IS NOT NULL 
                ORDER BY full_name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ucId]);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 3. Si aún no hay empleados, cargar desde users vía user_companies (relación por empresa)
    if (count($employees) === 0) {
        $sql = "SELECT u.id, u.name, u.email
                FROM users u
                INNER JOIN user_companies uc ON uc.user_id = u.id
                WHERE uc.company_id = ?
                  AND uc.status = 'active'
                ORDER BY u.name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ucId]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fallback: algunas instalaciones no usan status='active' en user_companies
        if (count($users) === 0) {
            $sql = "SELECT u.id, u.name, u.email
                    FROM users u
                    INNER JOIN user_companies uc ON uc.user_id = u.id
                    WHERE uc.company_id = ?
                    ORDER BY u.name";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$ucId]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Último recurso (si existe users.company_id en esa BD)
        if (count($users) === 0) {
            try {
                $sql = "SELECT id, name, email FROM users WHERE company_id = ? ORDER BY name";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$ucId]);
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                $users = [];
            }
        }

        $employees = array_map(function($user) {
            return [
                'id' => $user['id'],
                'user_id' => $user['id'],
                'name' => $user['name'],
                'employee_code' => 'U' . $user['id'],
                'position' => 'Usuario',
                'department' => 'Sistema'
            ];
        }, $users);
    }
    
    // 4. Como último recurso, agregar usuario actual
    if (count($employees) === 0 && $userId) {
        $sql = "SELECT id, name, email FROM users WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($currentUser) {
            $employees = [[
                'id' => $currentUser['id'],
                'user_id' => $currentUser['id'],
                'name' => $currentUser['name'],
                'employee_code' => 'ACTUAL',
                'position' => 'Usuario Actual',
                'department' => 'Sistema'
            ]];
        }
    }

    // 5. Siempre agregar usuarios de la compañía como opciones asignables (users.id)
    //    Esto permite asignar responsables incluso cuando HR no tiene user_id vinculado.
    try {
        $companyUsers = [];

        // 5.1 Intentar vía user_companies con status en diferentes formatos
        try {
            $sql = "SELECT u.id, u.name, u.email
                    FROM users u
                    INNER JOIN user_companies uc ON uc.user_id = u.id
                    WHERE uc.company_id = ?
                      AND (uc.status IN ('active','activo') OR uc.status IS NULL)
                    ORDER BY u.name";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$ucId]);
            $companyUsers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            $companyUsers = [];
        }

        // 5.2 Fallback: sin filtrar por status (algunas BD no usan status o usan otros valores)
        if (count($companyUsers) === 0) {
            try {
                $sql = "SELECT u.id, u.name, u.email
                        FROM users u
                        INNER JOIN user_companies uc ON uc.user_id = u.id
                        WHERE uc.company_id = ?
                        ORDER BY u.name";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$ucId]);
                $companyUsers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e) {
                $companyUsers = [];
            }
        }

        // 5.3 Último recurso (si existe users.company_id en esa BD)
        if (count($companyUsers) === 0) {
            try {
                $sql = "SELECT id, name, email FROM users WHERE company_id = ? ORDER BY name";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$ucId]);
                $companyUsers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e) {
                $companyUsers = [];
            }
        }

        // Deduplicar por user_id (preferir HR cuando tenga user_id)
        $byUserId = [];
        foreach ($employees as $e) {
            $uid = isset($e['user_id']) && $e['user_id'] !== null ? (int)$e['user_id'] : 0;
            if ($uid > 0) {
                $byUserId[$uid] = true;
            }
        }

        foreach ($companyUsers as $u) {
            $uid = (int)($u['id'] ?? 0);
            if ($uid <= 0) continue;
            if (isset($byUserId[$uid])) continue;
            $employees[] = [
                'id' => $uid,
                'user_id' => $uid,
                'name' => ($u['name'] ?? ($u['email'] ?? ('U'.$uid))),
                'employee_code' => 'U' . $uid,
                'position' => 'Usuario',
                'department' => 'Sistema'
            ];
            $byUserId[$uid] = true;
        }

        // Orden final por nombre
        usort($employees, function($a, $b){
            return strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
        });
    } catch (Throwable $e) {
        // No romper el endpoint si alguna instalación no usa user_companies
    }
    
    // Cargar unidades
    $sql = "SELECT id, name, description FROM units WHERE company_id = ? AND status = 'active' ORDER BY name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ucId]);
    $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Cargar negocios/tiendas
    $sql = "SELECT id, name, address FROM businesses WHERE company_id = ? AND status = 'active' ORDER BY name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ucId]);
    $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Cargar proyectos (si existen)
    $projects = [];

    // Usuarios asignables (siempre users.id) para asignación de responsables
    $assignableUsers = [];
    try {
        $hasUserCompanyId = $colExists($pdo, 'hr_employees', 'user_company_id');
        $hasUsersName = $colExists($pdo, 'users', 'name');

        $joinUserCompanyId = $hasUserCompanyId
            ? "LEFT JOIN hr_employees he_uc
                 ON he_uc.company_id = uc.company_id
                AND he_uc.user_company_id = uc.id"
            : '';

        $nameByUserCompanyId = $hasUserCompanyId
            ? "NULLIF(he_uc.full_name, ''),"
            : '';

        $nameByUsersName = $hasUsersName
            ? "NULLIF(u.name, ''),"
            : '';

        $sql = "SELECT u.id AS user_id,
                       COALESCE(
                         NULLIF(he.full_name, ''),
                         {$nameByUserCompanyId}
                         NULLIF(he_email.full_name, ''),
                         NULLIF(u.full_name, ''),
                    {$nameByUsersName}
                         CONCAT('Usuario #', u.id)
                       ) AS name
                FROM users u
                INNER JOIN user_companies uc ON uc.user_id = u.id
                LEFT JOIN hr_employees he
                       ON he.company_id = uc.company_id
                      AND he.user_id = u.id
                {$joinUserCompanyId}
                    LEFT JOIN hr_employees he_email
                         ON he_email.company_id = uc.company_id
                        AND LOWER(TRIM(he_email.email)) = LOWER(TRIM(u.email))
                WHERE uc.company_id = ?
                ORDER BY name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ucId]);
        $assignableUsers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        $assignableUsers = [];
    }
    
    $result['employees'] = $employees;
    $result['assignable_users'] = $assignableUsers;
    $result['units'] = $units;
    $result['businesses'] = $businesses;
    $result['projects'] = $projects;
    $result['debug'] = [
        'company_id' => $ucId,
        'user_id' => $userId,
        'employees_count' => count($employees),
        'units_count' => count($units),
        'businesses_count' => count($businesses)
    ];
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}