<?php
/**
 * Componente: Tarjeta KPI
 * Tarjeta compacta y uniforme para mostrar métricas
 * 
 * @param string $label Etiqueta del KPI
 * @param string $value Valor a mostrar
 * @param string $id ID del elemento (opcional)
 * @param string $icon Icono Bootstrap (opcional)
 */

$label = $label ?? 'KPI';
$value = $value ?? '0';
$id = $id ?? '';
$icon = $icon ?? '';
?>

<div class="kpi-card">
    <?php if ($icon): ?>
    <div class="kpi-icon mb-2">
        <i class="bi bi-<?= htmlspecialchars($icon) ?>"></i>
    </div>
    <?php endif; ?>
    
    <p><?= htmlspecialchars($label) ?></p>
    <h2 <?= $id ? 'id="' . htmlspecialchars($id) . '"' : '' ?>>
        <?= htmlspecialchars($value) ?>
    </h2>
</div>
