<?php
require __DIR__.'/../../../bootstrap.php';
require __DIR__.'/../../../core/auth.php';
header('Content-Type: application/json');

requireLogin();
$ucId = currentUserCompany();

if (!$ucId) {
    echo json_encode(['ok' => false, 'error' => 'No company']);
    exit;
}

try {
    $pdo = db();
    
    // Cargar empleados de HR con información completa
    $sql = "SELECT 
                he.id,
                he.user_id,
                he.employee_code,
                he.full_name as name,
                he.department,
                he.position,
                u.name as unit_name,
                b.name as business_name
            FROM hr_employees he
            LEFT JOIN units u ON u.id = he.unit_id
            LEFT JOIN businesses b ON b.id = he.business_id
            WHERE he.company_id = ? 
            AND he.status = 'activo'
            AND he.full_name IS NOT NULL
            ORDER BY he.full_name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ucId]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'ok' => true,
        'data' => $employees,
        'items' => $employees // Para compatibilidad con diferentes formatos
    ]);
    
} catch (Exception $e) {
    error_log('[HR_USERS] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database error']);
}