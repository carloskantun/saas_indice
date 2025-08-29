<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

$env = require __DIR__ . '/.env.php';

define('APP_URL', $env['APP_URL'] ?? 'http://localhost');
define('APP_ENV', $env['APP_ENV'] ?? 'production');
define('APP_DEBUG', (bool)($env['APP_DEBUG'] ?? false));

function db(): PDO {
    static $pdo;
    global $env;
    if (!$pdo) {
        $dsn = 'mysql:host=' . $env['DB_HOST'] . ';dbname=' . $env['DB_NAME'] . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }
    return $pdo;
}
