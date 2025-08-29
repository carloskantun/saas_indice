<?php
require __DIR__ . '/../../bootstrap.php';
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
require __DIR__ . '/../../core/auth.php';
require __DIR__ . '/../../core/permissions.php';
require __DIR__ . '/../../core/scope.php';

requireLogin();
$ucId = currentUserCompany();
$roleInfo = getUserModuleRole($ucId, 'expenses');
if (!canAction('expenses', 'view', $roleInfo['role'], $roleInfo['skill_level'])) {
    http_response_code(403);
    exit;
}

list($where, $params) = scopeWhereClause(['company_id' => $ucId], $_GET, 'e');
$query = "SELECT e.* FROM expenses e $where ORDER BY e.pay_date DESC LIMIT 200";
$stmt = db()->prepare($query);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Gastos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1>Gastos</h1>
    <table class="table table-striped">
        <tr><th>ID</th><th>Concepto</th><th>Monto</th><th>Fecha Pago</th></tr>
        <?php foreach ($rows as $r): ?>
        <tr>
            <td><?php echo $r['id']; ?></td>
            <td><?php echo htmlspecialchars($r['concept']); ?></td>
            <td><?php echo htmlspecialchars($r['amount']); ?></td>
            <td><?php echo htmlspecialchars($r['pay_date']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <a href="controller.php?action=export" class="btn btn-secondary">Exportar CSV</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
