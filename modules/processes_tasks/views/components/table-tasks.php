<?php
/**
 * TABLA DE TAREAS — Estilo Institucional Índice ERP (Minimal Enterprise)
 * Compatible 100% con backend/JS actual.
 */

$tableId = $tableId ?? 'tableTareas';
$tasks = $tasks ?? [];
$showActions = $showActions ?? true;

function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'badge-status pending',
        'in_progress' => 'badge-status in-progress',
        'completed' => 'badge-status completed',
        'cancelled' => 'badge-status rejected',
        'overdue' => 'badge-status rejected'
    ];
    return $classes[$status] ?? 'badge-status';
}

function getPriorityBadgeClass($priority) {
    $classes = [
        'low' => 'badge-status pending',
        'medium' => 'badge-status in-progress',
        'high' => 'badge-status warning',
        'critical' => 'badge-status rejected'
    ];
    return $classes[$priority] ?? 'badge-status';
}

function translateStatus($status) {
    $map = [
        'pending' => 'Pendiente',
        'in_progress' => 'En Progreso',
        'completed' => 'Completada',
        'cancelled' => 'Cancelada',
        'overdue' => 'Vencida'
    ];
    return $map[$status] ?? $status;
}

function translatePriority($priority) {
    $map = [
        'low' => 'Baja',
        'medium' => 'Media',
        'high' => 'Alta',
        'critical' => 'Crítica'
    ];
    return $map[$priority] ?? $priority;
}
?>

<div class="table-container reveal">
    <table class="table table-hover align-middle" id="<?= $tableId ?>">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Título</th>
                <th>Unidad</th>
                <th>Negocio</th>
                <th>Descripción</th>
                <th>Inicio</th>
                <th>Fin</th>
                <th>Archivos</th>
                <th>Creador</th>
                <th>Delegado</th>
                <th>Nivel</th>
                <th>Tipo</th>
                <th>Proyecto</th>
                <?php if ($showActions): ?>
                <th>Acciones</th>
                <?php endif; ?>
                <th>Auditar</th>
                <th>Pond.</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>
        <?php if (empty($tasks)): ?>
            <tr>
                <td colspan="17" class="text-center py-5">
                    <div class="empty-state">
                        <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                        <div class="empty-title">Sin tareas registradas</div>
                        <div class="empty-sub">Aquí aparecerán las tareas creadas</div>
                    </div>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($tasks as $task): ?>
            <tr class="task-row" data-task-id="<?= $task['id'] ?>">

                <!-- Folio -->
                <td><span class="badge bg-secondary"><?= str_pad($task['id'], 5, '0', STR_PAD_LEFT) ?></span></td>

                <!-- Título (editable) -->
                <td>
                    <span class="editable-field" data-field="title" data-task-id="<?= $task['id'] ?>">
                        <?= htmlspecialchars($task['title']) ?>
                    </span>
                </td>

                <!-- Unidad -->
                <td>
                    <?php if (!empty($task['unit'])): ?>
                        <span class="badge bg-primary editable-field"
                              data-field="unit"
                              data-task-id="<?= $task['id'] ?>">
                            <?= htmlspecialchars($task['unit']) ?>
                        </span>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>

                <!-- Negocio -->
                <td>
                    <?php if (!empty($task['business'])): ?>
                        <span class="badge bg-info text-dark editable-field"
                              data-field="business"
                              data-task-id="<?= $task['id'] ?>">
                            <?= htmlspecialchars($task['business']) ?>
                        </span>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>

                <!-- Descripción -->
                <td>
                    <span class="text-truncate d-inline-block" style="max-width: 240px;"
                          title="<?= htmlspecialchars($task['description'] ?? '') ?>">
                        <?= htmlspecialchars(substr($task['description'] ?? '-', 0, 60)) ?>
                        <?= strlen($task['description'] ?? '') > 60 ? '…' : '' ?>
                    </span>
                </td>

                <!-- Fecha inicio -->
                <td>
                    <span class="editable-field" data-field="start_date" data-task-id="<?= $task['id'] ?>">
                        <?= date('d/m/Y', strtotime($task['start_date'])) ?>
                    </span>
                </td>

                <!-- Fecha fin -->
                <td>
                    <span class="editable-field" data-field="due_date" data-task-id="<?= $task['id'] ?>">
                        <?= date('d/m/Y', strtotime($task['due_date'])) ?>
                    </span>
                </td>

                <!-- Archivos -->
                <td>
                    <?php if (!empty($task['files']) && is_array($task['files'])): ?>
                        <button class="btn btn-sm btn-ghost" onclick="verArchivos(<?= $task['id'] ?>)">
                            <i class="bi bi-paperclip"></i> <?= count($task['files']) ?>
                        </button>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>

                <!-- Creador -->
                <td><small class="text-muted"><?= htmlspecialchars($task['created_by']) ?></small></td>

                <!-- Delegado -->
                <td>
                    <span class="editable-field" data-field="delegated_to" data-task-id="<?= $task['id'] ?>">
                        <?= htmlspecialchars($task['delegated_to'] ?? '-') ?>
                    </span>
                </td>

                <!-- Prioridad -->
                <td>
                    <span class="<?= getPriorityBadgeClass($task['priority']) ?> editable-field"
                          data-field="priority" data-task-id="<?= $task['id'] ?>">
                        <?= translatePriority($task['priority']) ?>
                    </span>
                </td>

                <!-- Tipo -->
                <td>
                    <span class="badge bg-secondary">
                        <?= $task['type'] === 'process_task' ? 'Proceso' : 'Tarea' ?>
                    </span>
                </td>

                <!-- Proyecto -->
                <td>
                    <?php if (!empty($task['project_name'])): ?>
                    <span class="badge bg-info text-dark editable-field"
                          data-field="project_id"
                          data-task-id="<?= $task['id'] ?>">
                        <?= htmlspecialchars($task['project_name']) ?>
                    </span>
                    <?php else: ?>
                        <span class="text-muted">Sin proyecto</span>
                    <?php endif; ?>
                </td>

                <!-- Acciones -->
                <?php if ($showActions): ?>
                <td>
                    <div class="actions d-flex gap-1">
                        <?php if ($task['status'] !== 'completed'): ?>
                        <button class="btn btn-success btn-sm" onclick="completarTarea(<?= $task['id'] ?>)">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        <?php endif; ?>

                        <button class="btn btn-primary btn-sm" onclick="editarTarea(<?= $task['id'] ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>

                        <button class="btn btn-info btn-sm" onclick="duplicarTarea(<?= $task['id'] ?>)">
                            <i class="bi bi-files"></i>
                        </button>

                        <button class="btn btn-danger btn-sm" onclick="eliminarTarea(<?= $task['id'] ?>)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
                <?php endif; ?>

                <!-- Auditar -->
                <td class="text-center">
                    <input type="checkbox" class="form-check-input"
                           <?= $task['audited'] ? 'checked' : '' ?>
                           onchange="toggleAuditoria(<?= $task['id'] ?>, this.checked)">
                </td>

                <!-- Ponderación -->
                <td class="text-center">
                    <span class="editable-field" data-field="ponderacion" data-task-id="<?= $task['id'] ?>">
                        <?= $task['ponderacion'] ?? 3 ?>
                    </span>
                </td>

                <!-- Status -->
                <td>
                    <span class="<?= getStatusBadgeClass($task['status']) ?> editable-field"
                          data-field="status" data-task-id="<?= $task['id'] ?>">
                        <?= translateStatus($task['status']) ?>
                    </span>
                </td>

            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
