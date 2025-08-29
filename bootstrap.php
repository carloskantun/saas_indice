<?php
define('APP_BOOTSTRAPPED', 1);
require __DIR__ . '/config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
