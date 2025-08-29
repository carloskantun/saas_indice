<?php
require __DIR__ . '/../bootstrap.php';
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

$payload = json_decode(file_get_contents('php://input'), true);
$intentId = $payload['intent_id'] ?? null;
if ($intentId) {
    $stmt = db()->prepare("UPDATE signup_intents SET status='paid' WHERE id=:id");
    $stmt->execute([':id' => $intentId]);
    provisionCompanyFromIntent($intentId);
}

function provisionCompanyFromIntent($intentId): void {
    // Placeholder: create company and owner
}
