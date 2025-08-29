<?php
require __DIR__ . '/bootstrap.php';
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
require __DIR__ . '/core/auth.php';
require __DIR__ . '/core/permissions.php';
require __DIR__ . '/core/plan.php';

requireLogin();
$companyId = currentUserCompany();
$user = auth();

$stmt = db()->prepare("SELECT * FROM modules WHERE is_active=1 ORDER BY sort_order, name");
$stmt->execute();
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grid = [];
foreach ($modules as $mod) {
    if (planAllowsModule($companyId, $mod['slug'])) {
        $role = getUserModuleRole($companyId, $mod['slug']);
        if ($role) {
            $grid[] = $mod;
        }
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Indice App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1>Módulos</h1>
    <div class="row">
        <?php foreach ($grid as $mod): ?>
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="h1"><?php echo htmlspecialchars($mod['icon'] ?? '📦'); ?></div>
                    <h5 class="card-title"><?php echo htmlspecialchars($mod['name']); ?></h5>
                    <?php if (!empty($mod['badge_text'])): ?>
                    <span class="badge bg-secondary"><?php echo htmlspecialchars($mod['badge_text']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
