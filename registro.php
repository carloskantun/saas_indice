<?php
require __DIR__ . '/bootstrap.php';
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $intentId = $_SESSION['intent_id'] ?? null;
    if (!$intentId) {
        $stmt = db()->prepare("INSERT INTO signup_intents (status) VALUES ('draft')");
        $stmt->execute();
        $intentId = db()->lastInsertId();
        $_SESSION['intent_id'] = $intentId;
    }
    switch ($step) {
        case 1:
            $stmt = db()->prepare("UPDATE signup_intents SET email=:email, password_hash=:ph WHERE id=:id");
            $stmt->execute([
                ':email' => $_POST['email'],
                ':ph' => password_hash($_POST['password'], PASSWORD_BCRYPT),
                ':id' => $intentId
            ]);
            $step = 2;
            break;
        case 2:
            $stmt = db()->prepare("UPDATE signup_intents SET business_name=:bn WHERE id=:id");
            $stmt->execute([
                ':bn' => $_POST['business_name'],
                ':id' => $intentId
            ]);
            $step = 3;
            break;
        case 3:
            $stmt = db()->prepare("UPDATE signup_intents SET plan_slug=:ps WHERE id=:id");
            $stmt->execute([
                ':ps' => $_POST['plan'],
                ':id' => $intentId
            ]);
            $step = 4;
            break;
        case 4:
            $stmt = db()->prepare("UPDATE signup_intents SET status='paid' WHERE id=:id");
            $stmt->execute([':id' => $intentId]);
            header('Location: ' . APP_URL);
            exit;
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Registro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1>Registro</h1>
    <?php if ($step === 1): ?>
    <form method="post" action="?step=1">
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button class="btn btn-primary">Continuar</button>
    </form>
    <?php elseif ($step === 2): ?>
    <form method="post" action="?step=2">
        <div class="mb-3">
            <label class="form-label">Nombre del negocio</label>
            <input type="text" name="business_name" class="form-control" required>
        </div>
        <button class="btn btn-primary">Continuar</button>
    </form>
    <?php elseif ($step === 3): ?>
    <form method="post" action="?step=3">
        <div class="mb-3">
            <label class="form-label">Plan</label>
            <select name="plan" class="form-select">
                <option value="basic">Básico</option>
                <option value="pro">Pro</option>
            </select>
        </div>
        <button class="btn btn-primary">Continuar</button>
    </form>
    <?php elseif ($step === 4): ?>
    <p>Total a pagar: $0 (stub)</p>
    <form method="post" action="?step=4">
        <button class="btn btn-success">Pagar</button>
    </form>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
