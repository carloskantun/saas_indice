<?php
require __DIR__.'/../../../bootstrap.php';
require __DIR__.'/../../../core/auth.php';
header('Content-Type: application/json');

requireLogin();
$ucId = currentUserCompany();
$userId = currentUserId();

if (!$ucId) {
    echo json_encode(['ok' => false, 'error' => 'No company']);
    exit;
}

try {
    $pdo = db();
    $result = ['ok' => true];
    
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
    
    // 3. Si aún no hay empleados, cargar desde tabla users
    if (count($employees) === 0) {
        $sql = "SELECT id, name, email 
                FROM users 
                WHERE company_id = ? 
                ORDER BY name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ucId]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
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
    
    $result['employees'] = $employees;
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