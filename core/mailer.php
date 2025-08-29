<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

function mailer(string $to, string $subject, string $body): bool {
    // Placeholder using PHP mail()
    return mail($to, $subject, $body);
}
