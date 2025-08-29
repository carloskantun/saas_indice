<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

function auth(): ?array {
    if (!empty($_SESSION['user_id'])) {
        return ['id' => $_SESSION['user_id'], 'name' => $_SESSION['user_name'] ?? ''];
    }
    return null;
}

function requireLogin(): void {
    if (!auth()) {
        header('Location: /login.php');
        exit;
    }
}

function currentUserCompany(): ?int {
    return $_SESSION['company_id'] ?? null;
}
