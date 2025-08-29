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
    $stmt = db()->prepare("INSERT INTO modules (name,slug,description,icon,badge_text,tier,sort_order,is_core,is_active) VALUES (:name,:slug,:description,:icon,:badge,:tier,:sort,:core,:active)");
    $stmt->execute([
        ':name' => $_POST['name'],
        ':slug' => $_POST['slug'],
        ':description' => $_POST['description'],
        ':icon' => $_POST['icon'],
        ':badge' => $_POST['badge_text'],
        ':tier' => $_POST['tier'],
        ':sort' => $_POST['sort_order'],
        ':core' => isset($_POST['is_core']) ? 1 : 0,
        ':active' => isset($_POST['is_active']) ? 1 : 0,
    ]);
}

$mods = db()->query("SELECT * FROM modules ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Módulos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1>Módulos</h1>
    <form method="post" class="row g-2 mb-4">
        <div class="col-md-3"><input type="text" name="name" class="form-control" placeholder="Nombre" required></div>
        <div class="col-md-2"><input type="text" name="slug" class="form-control" placeholder="slug" required></div>
        <div class="col-md-2"><input type="text" name="icon" class="form-control" placeholder="icon"></div>
        <div class="col-md-2"><input type="text" name="badge_text" class="form-control" placeholder="badge"></div>
        <div class="col-md-1"><input type="number" name="sort_order" class="form-control" placeholder="sort"></div>
        <div class="col-md-1"><input type="text" name="tier" class="form-control" placeholder="tier"></div>
        <div class="col-md-1 form-check"><input class="form-check-input" type="checkbox" name="is_core" id="core"><label class="form-check-label" for="core">Core</label></div>
        <div class="col-md-1 form-check"><input class="form-check-input" type="checkbox" name="is_active" id="active" checked><label class="form-check-label" for="active">Act</label></div>
        <div class="col-md-1"><button class="btn btn-primary">Guardar</button></div>
    </form>
    <table class="table table-bordered">
        <tr><th>Nombre</th><th>Slug</th><th>Tier</th><th>Activo</th></tr>
        <?php foreach ($mods as $m): ?>
        <tr>
            <td><?php echo htmlspecialchars($m['name']); ?></td>
            <td><?php echo htmlspecialchars($m['slug']); ?></td>
            <td><?php echo htmlspecialchars($m['tier']); ?></td>
            <td><?php echo $m['is_active'] ? 'Sí' : 'No'; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
