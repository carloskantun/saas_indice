<?php
require __DIR__.'/../../../bootstrap.php';
require __DIR__.'/../../../core/auth.php';
header('Content-Type: application/json');

requireLogin();
$ucId = currentUserCompany();
$userId = currentUserId();

if (!$ucId) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No company context']);
    exit;
}

$type = $_GET['type'] ?? 'all';

try {
    $pdo = db();
    $result = ['ok' => true];
    
    // Debug: mostrar company_id que se está usando
    error_log("[FORM_DATA] Company ID: $ucId, User ID: $userId");
    
    // Cargar empleados activos del scope del usuario
    if ($type === 'employees' || $type === 'all') {
        $sql = "SELECT 
                    he.id,
                    he.employee_code,
                    he.full_name,
                    he.department,
                    he.position,
                    u.name as unit_name,
                    b.name as business_name
                FROM hr_employees he
                LEFT JOIN users usr ON usr.id = he.user_id
                LEFT JOIN units u ON u.id = he.unit_id
                LEFT JOIN businesses b ON b.id = he.business_id
                WHERE he.company_id = ? 
                AND (he.status = 'activo' OR he.status IS NULL)
                AND he.full_name IS NOT NULL
                ORDER BY he.full_name ASC
                LIMIT 500";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ucId]);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: mostrar cuántos empleados se encontraron
        error_log("[FORM_DATA] Empleados encontrados: " . count($employees));
        
        $result['employees'] = array_map(function($emp) {
            return [
                'id' => (int)$emp['id'],
                'code' => $emp['employee_code'] ?? '',
                'full_name' => $emp['full_name'] ?? '',
                'name' => $emp['full_name'] ?? '', // Alias para compatibilidad
                'department' => $emp['department'] ?? '',
                'position' => $emp['position'] ?? '',
                'unit_name' => $emp['unit_name'] ?? '',
                'business_name' => $emp['business_name'] ?? ''
            ];
        }, $employees);
    }
    
    // Cargar unidades del scope del usuario
    if ($type === 'units' || $type === 'all') {
        $sql = "SELECT 
                    u.id,
                    u.name,
                    u.description,
                    COUNT(he.id) as employees_count
                FROM units u
                LEFT JOIN hr_employees he ON he.unit_id = u.id AND he.company_id = ?
                WHERE u.company_id = ?
                AND u.status = 'active'
                GROUP BY u.id, u.name, u.description
                ORDER BY u.name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ucId, $ucId]);
        $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: mostrar cuántas unidades se encontraron
        error_log("[FORM_DATA] Unidades encontradas: " . count($units));
        
        $result['units'] = array_map(function($unit) {
            return [
                'id' => (int)$unit['id'],
                'name' => $unit['name'] ?? '',
                'description' => $unit['description'] ?? '',
                'employees_count' => (int)($unit['employees_count'] ?? 0)
            ];
        }, $units);
    }
    
    // Cargar negocios del scope del usuario
    if ($type === 'businesses' || $type === 'all') {
        $sql = "SELECT 
                    b.id,
                    b.name,
                    b.description,
                    COUNT(he.id) as employees_count
                FROM businesses b
                LEFT JOIN hr_employees he ON he.business_id = b.id AND he.company_id = ?
                WHERE b.company_id = ?
                AND b.status = 'active'
                GROUP BY b.id, b.name, b.description
                ORDER BY b.name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ucId, $ucId]);
        $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: mostrar cuántos negocios se encontraron
        error_log("[FORM_DATA] Negocios encontrados: " . count($businesses));
        
        $result['businesses'] = array_map(function($biz) {
            return [
                'id' => (int)$biz['id'],
                'name' => $biz['name'] ?? '',
                'description' => $biz['description'] ?? '',
                'employees_count' => (int)($biz['employees_count'] ?? 0)
            ];
        }, $businesses);
    }
    
    // Cargar proyectos existentes para asignación
    if ($type === 'projects' || $type === 'all') {
        $sql = "SELECT 
                    t.id,
                    t.titulo as name,
                    t.descripcion,
                    t.status,
                    t.fecha_inicio,
                    t.fecha_entrega
                FROM tasks t
                WHERE t.company_id = ?
                AND t.tipo = 'Proyecto'
                AND t.status IN ('En tiempo', 'En proceso')
                ORDER BY t.titulo ASC
                LIMIT 100";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ucId]);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result['projects'] = array_map(function($proj) {
            return [
                'id' => (int)$proj['id'],
                'name' => $proj['name'] ?? '',
                'description' => $proj['descripcion'] ?? '',
                'status' => $proj['status'] ?? '',
                'start_date' => $proj['fecha_inicio'] ?? '',
                'due_date' => $proj['fecha_entrega'] ?? ''
            ];
        }, $projects);
    }
    
    // Debug final: mostrar resumen de lo que se va a devolver
    error_log("[FORM_DATA] Resultado final: " . json_encode([
        'employees_count' => count($result['employees'] ?? []),
        'units_count' => count($result['units'] ?? []),
        'businesses_count' => count($result['businesses'] ?? []),
        'projects_count' => count($result['projects'] ?? [])
    ]));
    
    echo json_encode($result);
    
} catch (Throwable $e) {
    error_log('[FORM_DATA] Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error']);
}