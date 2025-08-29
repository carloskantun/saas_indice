<?php
require __DIR__ . '/../bootstrap.php';
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
require __DIR__ . '/../core/auth.php';

$token = $_GET['token'] ?? '';
$stmt = db()->prepare("SELECT * FROM invitations WHERE token=:token AND status='pending'");
$stmt->execute([':token' => $token]);
$inv = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inv) {
    echo 'Invitación inválida';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        $stmt = db()->prepare("INSERT INTO users (email) VALUES (:email)");
        $stmt->execute([':email' => $inv['email']]);
        $userId = db()->lastInsertId();
        $_SESSION['user_id'] = $userId;
    }
    $stmt = db()->prepare("INSERT INTO user_companies (user_id,company_id,role,visibility,status) VALUES (:uid,:cid,:role,:vis,'active')");
    $stmt->execute([
        ':uid' => $userId,
        ':cid' => $inv['company_id'],
        ':role' => $inv['proposed_role'],
        ':vis' => $inv['proposed_visibility']
    ]);
    $stmt = db()->prepare("UPDATE invitations SET status='accepted' WHERE id=:id");
    $stmt->execute([':id' => $inv['id']]);
    echo 'Invitación aceptada';
    exit;
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Aceptar invitación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1>Aceptar invitación</h1>
    <p>Empresa ID: <?php echo htmlspecialchars($inv['company_id']); ?></p>
    <form method="post">
        <button class="btn btn-success">Aceptar</button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
