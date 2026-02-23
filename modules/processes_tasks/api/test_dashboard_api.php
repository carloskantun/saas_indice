<?php
/**
 * Script de prueba para el API del dashboard
 * Muestra la respuesta RAW para debugging
 */

// Simular la misma configuración que el API
require __DIR__ . '/../../../bootstrap.php';
require __DIR__ . '/../../../core/auth.php';
require __DIR__ . '/../../../core/permissions.php';

requireLogin();
$ucId = (int)currentUserCompany();
$userId = (int)currentUserId();

if (!defined('APP_DEBUG') || !APP_DEBUG) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

if (!hasPermission($ucId, 'processes_tasks', 'view')) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$pdo = db();
$stmtRole = $pdo->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ? AND status = 'active' LIMIT 1");
$stmtRole->execute([$userId, $ucId]);
$companyRole = (string)($stmtRole->fetchColumn() ?: '');
if (!in_array($companyRole, ['root', 'superadmin'], true)) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Test API Dashboard</title></head><body>";
echo "<h1>🔍 Test API Dashboard - RAW Output</h1>";

// Capturar la salida del API
ob_start();

try {
    // Incluir el API
    include __DIR__ . '/dashboard.php';
    
    $output = ob_get_clean();
    
    echo "<h2>✅ Respuesta del API:</h2>";
    echo "<pre style='background: #f5f5f5; padding: 15px; border: 1px solid #ccc; overflow: auto;'>";
    echo htmlspecialchars($output);
    echo "</pre>";
    
    // Intentar decodificar como JSON
    echo "<h2>🔍 Validación JSON:</h2>";
    $json = json_decode($output, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<p style='color: green;'>✅ JSON válido</p>";
        echo "<pre>";
        print_r($json);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>❌ Error JSON: " . json_last_error_msg() . "</p>";
        echo "<p>Verifica que no haya output antes del JSON (espacios, BOM, errores, etc.)</p>";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "<h2 style='color: red;'>❌ Error Fatal:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
