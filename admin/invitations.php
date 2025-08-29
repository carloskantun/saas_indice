<?php
require __DIR__ . '/../bootstrap.php';
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
require __DIR__ . '/../core/auth.php';
require __DIR__ . '/../core/plan.php';
require __DIR__ . '/../core/permissions.php';

requireLogin();
$companyId = currentUserCompany();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (seatsAvailable($companyId) <= 0) {
        $message = 'No hay asientos disponibles.';
    } else {
        $token = bin2hex(random_bytes(16));
        $stmt = db()->prepare("INSERT INTO invitations (company_id,email,token,seat_reserved,modules,proposed_role,proposed_visibility,status) VALUES (:cid,:email,:token,1,:modules,:role,:visibility,'pending')");
        $stmt->execute([
            ':cid' => $companyId,
            ':email' => $_POST['email'],
            ':token' => $token,
            ':modules' => json_encode($_POST['modules'] ?? []),
            ':role' => $_POST['role'] ?? 'user',
            ':visibility' => $_POST['visibility'] ?? 'all'
        ]);
        $message = 'Invitación enviada.';
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invitaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1>Invitar usuario</h1>
    <?php if ($message): ?><div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Rol propuesto</label>
            <select name="role" class="form-select">
                <option value="user">Usuario</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <button class="btn btn-primary">Enviar</button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
