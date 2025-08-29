<?php
require __DIR__ . '/../bootstrap.php';
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
require __DIR__ . '/../core/auth.php';
require __DIR__ . '/../core/permissions.php';

requireLogin();
if (!hasPermission('root')) {
    http_response_code(403);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = db()->prepare("INSERT INTO plans (name,modules_included) VALUES (:name,:mods)");
    $stmt->execute([
        ':name' => $_POST['name'],
        ':mods' => $_POST['modules_included']
    ]);
}

$plans = db()->query("SELECT * FROM plans ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Planes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1>Planes</h1>
    <form method="post" class="mb-4">
        <div class="mb-3"><input type="text" name="name" class="form-control" placeholder="Nombre" required></div>
        <div class="mb-3"><textarea name="modules_included" class="form-control" placeholder="modules,json"></textarea></div>
        <button class="btn btn-primary">Guardar</button>
    </form>
    <table class="table table-bordered">
        <tr><th>ID</th><th>Nombre</th><th>Módulos</th></tr>
        <?php foreach ($plans as $p): ?>
        <tr>
            <td><?php echo $p['id']; ?></td>
            <td><?php echo htmlspecialchars($p['name']); ?></td>
            <td><?php echo htmlspecialchars($p['modules_included']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
