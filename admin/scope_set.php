<?php
require __DIR__ . '/../bootstrap.php';
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

$_SESSION['company_id'] = $_POST['company_id'] ?? ($_SESSION['company_id'] ?? null);
$_SESSION['unit_id'] = $_POST['unit_id'] ?? null;
$_SESSION['business_id'] = $_POST['business_id'] ?? null;

header('Location: /index.php');
exit;
