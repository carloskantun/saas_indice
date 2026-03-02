<?php
/**
 * Componente: Tabla de Proyectos
 * Muestra tabla de proyectos con información clave
 * 
 * Parámetros:
 * @param array $projects - Array de proyectos a mostrar
 * @param string $tableId - ID único para la tabla (default: 'tableProyectos')
 * @param bool $showActions - Mostrar columna de acciones (default: true)
 */

$tableId = $tableId ?? 'tableProyectos';
$projects = $projects ?? [];
$showActions = $showActions ?? true;

// Helper para obtener clase de badge según status del proyecto
function getProjectStatusBadgeClass($status) {
    $classes = [
        'planning' => 'bg-secondary',
        'active' => 'bg-success',
        'on_hold' => 'bg-warning text-dark',
        'completed' => 'bg-primary',
        'cancelled' => 'bg-danger'
    ];
    return $classes[$status] ?? 'bg-secondary';
}

// Helper para traducir status del proyecto
function translateProjectStatus($status) {
    $translations = [
        'planning' => 'Planeación',
        'active' => 'Activo',
        'on_hold' => 'En Pausa',
        'completed' => 'Completado',
        'cancelled' => 'Cancelado'
    ];
    return $translations[$status] ?? $status;
}

// Helper para calcular días restantes
function getDaysRemaining($endDate) {
    $now = new DateTime();
    $end = new DateTime($endDate);
    $diff = $now->diff($end);
    
    if ($end < $now) {
        return '<span class="text-danger">Vencido hace ' . $diff->days . ' días</span>';
    } else {
        return '<span class="text-muted">' . $diff->days . ' días restantes</span>';
    }
}
?>

<div class="table-responsive">
  <table class="table table-hover align-middle" id="<?= $tableId ?>">
    <thead class="table-light">
      <tr>
        <th style="width: 60px;">ID</th>
        <th style="width: 200px;">Nombre</th>
        <th style="width: 120px;">Unidad</th>
        <th style="width: 120px;">Negocio</th>
        <th style="width: 150px;">Responsable</th>
        <th style="width: 110px;">F. Inicio</th>
        <th style="width: 110px;">F. Fin</th>
        <th style="width: 100px;">Progreso</th>
        <th style="width: 100px;">Tareas</th>
        <th style="width: 120px;">Estado</th>
        <?php if ($showActions): ?>
        <th style="width: 180px;">Acciones</th>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($projects)): ?>
      <tr>
        <td colspan="<?= $showActions ? 11 : 10 ?>" class="text-center text-muted py-4">
          <i class="bi bi-folder fs-3 d-block mb-2"></i>
          No hay proyectos para mostrar
        </td>
      </tr>
      <?php else: ?>
        <?php foreach ($projects as $project): ?>
        <tr data-project-id="<?= $project['id'] ?>" class="project-row" style="cursor: pointer;" 
            onclick="verProyecto(<?= $project['id'] ?>)">
          <!-- ID -->
          <td>
            <span class="badge bg-secondary"><?= str_pad($project['id'], 3, '0', STR_PAD_LEFT) ?></span>
          </td>
          
          <!-- Nombre -->
          <td>
            <div class="d-flex align-items-center">
              <?php if (!empty($project['color'])): ?>
              <span class="badge me-2" style="background-color: <?= $project['color'] ?>; width: 8px; height: 25px;"></span>
              <?php endif; ?>
              <strong><?= htmlspecialchars($project['name']) ?></strong>
            </div>
          </td>
          
          <!-- Unidad -->
          <td>
            <?php if (!empty($project['unit'])): ?>
            <span class="badge bg-primary">
              <?= htmlspecialchars($project['unit']) ?>
            </span>
            <?php else: ?>
            <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          
          <!-- Negocio -->
          <td>
            <?php if (!empty($project['business'])): ?>
            <span class="badge bg-info text-dark">
              <?= htmlspecialchars($project['business']) ?>
            </span>
            <?php else: ?>
            <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          
          <!-- Responsable -->
          <td>
            <small><?= htmlspecialchars($project['owner']) ?></small>
          </td>
          
          <!-- Fecha de Inicio -->
          <td>
            <small class="text-muted"><?= date('d/m/Y', strtotime($project['start_date'])) ?></small>
          </td>
          
          <!-- Fecha de Fin -->
          <td>
            <small class="text-muted">
              <?= date('d/m/Y', strtotime($project['end_date'])) ?>
            </small>
            <br>
            <small><?= getDaysRemaining($project['end_date']) ?></small>
          </td>
          
          <!-- Progreso -->
          <td>
            <div class="progress" style="height: 20px;">
              <div class="progress-bar <?= $project['progress'] >= 100 ? 'bg-success' : 'bg-primary' ?>" 
                   role="progressbar" 
                   style="width: <?= $project['progress'] ?>%"
                   aria-valuenow="<?= $project['progress'] ?>" 
                   aria-valuemin="0" 
                   aria-valuemax="100">
                <?= $project['progress'] ?>%
              </div>
            </div>
          </td>
          
          <!-- Tareas (Completadas/Total) -->
          <td>
            <?php 
            $completedTasks = $project['completed_tasks'] ?? 0;
            $totalTasks = $project['total_tasks'] ?? 0;
            $percentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
            ?>
            <span class="badge <?= $percentage >= 100 ? 'bg-success' : 'bg-secondary' ?>">
              <?= $completedTasks ?>/<?= $totalTasks ?>
            </span>
          </td>
          
          <!-- Estado -->
          <td>
            <span class="badge <?= getProjectStatusBadgeClass($project['status']) ?>">
              <?= translateProjectStatus($project['status']) ?>
            </span>
          </td>
          
          <?php if ($showActions): ?>
          <!-- Acciones -->
          <td onclick="event.stopPropagation();">
            <div class="btn-group btn-group-sm" role="group">
              <!-- Ver Tareas -->
              <button class="btn btn-info" onclick="verTareasProyecto(<?= $project['id'] ?>)" title="Ver tareas">
                <i class="bi bi-list-task"></i>
              </button>
              
              <!-- Editar -->
              <button class="btn btn-primary" onclick="editarProyecto(<?= $project['id'] ?>)" title="Editar">
                <i class="bi bi-pencil"></i>
              </button>
              
              <!-- Estadísticas -->
              <button class="btn btn-success" onclick="verEstadisticasProyecto(<?= $project['id'] ?>)" title="Estadísticas">
                <i class="bi bi-graph-up"></i>
              </button>
              
              <!-- Eliminar -->
              <button class="btn btn-danger" onclick="eliminarProyecto(<?= $project['id'] ?>)" title="Eliminar">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Estilos adicionales -->
<style>
.project-row {
  transition: all 0.2s;
}

.project-row:hover {
  background-color: rgba(31, 77, 159, 0.08);
  transform: scale(1.005);
}

.project-row:hover td {
  color: var(--primary);
}
</style>
