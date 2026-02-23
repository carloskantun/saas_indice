<?php
/**
 * CSRF Protection Helper
 * /modules/processes_tasks/includes/csrf_helper.php
 */

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

/**
 * Validar token CSRF desde headers
 * @return bool True si el token es válido, false en caso contrario
 */
function validateCSRFToken() {
    // Si no hay sesión activa, no se puede validar
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    // Obtener token de sesión
    $sessionToken = $_SESSION['csrf_token'] ?? null;
    
    if (!$sessionToken) {
        error_log('[CSRF] No hay token en sesión');
        return false;
    }
    
    // Obtener token del header
    $headerToken = null;
    
    // Intentar obtener desde diferentes fuentes
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $headerToken = $headers['X-CSRF-Token'] ?? $headers['X-Csrf-Token'] ?? null;
    }
    
    // Fallback: obtener directamente del servidor
    if (!$headerToken) {
        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }
    
    // También permitir desde POST (para formularios tradicionales)
    if (!$headerToken) {
        $headerToken = $_POST['csrf_token'] ?? null;
    }
    
    if (!$headerToken) {
        error_log('[CSRF] No se encontró token en request');
        return false;
    }
    
    // Comparación segura contra timing attacks
    if (!hash_equals($sessionToken, $headerToken)) {
        error_log('[CSRF] Token inválido o no coincide');
        return false;
    }
    
    return true;
}

/**
 * Require CSRF validation y terminar con error 401 si falla
 */
function requireCSRF() {
    if (!validateCSRFToken()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Token CSRF inválido o faltante'
        ]);
        exit;
    }
}
