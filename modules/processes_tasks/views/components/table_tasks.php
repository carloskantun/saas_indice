<?php
/**
 * Componente Único de Tabla - Procesos & Tareas
 * Reutilizable en: Agenda (dashboard), Tareas (tasks), Procesos (processes)
 * Limpieza y refactor del componente de tabla unificada (procesos & tareas)
 * Compatible con PHP antiguos (sin short tags ni operadores modernos)
 */

// Asegurar helpers mínimos
if (!function_exists('h')) {
    function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

// Normalizar variables externas (evitar notices)
$tableId     = isset($tableId) ? $tableId : 'table_tasks';
$filtroTipo  = isset($filtroTipo) ? $filtroTipo : ''; // '', 'Tarea', 'Proceso'
$filtroFecha = isset($filtroFecha) ? (bool)$filtroFecha : false;
$config      = isset($config) && is_array($config) ? $config : [];

// Defaults de configuración
if (!isset($config['tipo']))         { $config['tipo'] = 'tarea'; }
if (!isset($config['show_filters'])) { $config['show_filters'] = true; }
if (!isset($config['editable']))     { $config['editable'] = true; }
if (!isset($config['show_actions'])) { $config['show_actions'] = true; }

?>
<div class="table-tasks-container" data-component="tasks-table">
    <?php if ($config['show_filters']): ?>
        <div class="card border-0 shadow-sm mb-3 filters-card">
            <div class="card-body py-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small text-muted fw-semibold">Unidad</label>
                        <select id="filter_unit" class="form-select form-select-sm">
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted fw-semibold">Negocio</label>
                        <select id="filter_business" class="form-select form-select-sm">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted fw-semibold">Status</label>
                        <select id="filter_status" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="en_proceso">En Proceso</option>
                            <option value="completada">Completada</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted fw-semibold">Período</label>
                        <select id="filter_periodo" class="form-select form-select-sm">
                            <option value="">Todo</option>
                            <option value="hoy">Hoy</option>
                            <option value="semana">Semana</option>
                            <option value="mes">Mes</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted fw-semibold">Buscar</label>
                        <input id="filter_search" type="text" class="form-control form-control-sm" placeholder="Folio, título...">
                    </div>
                </div>
                <div class="mt-3">
                    <button id="btn_clear_filters" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i>Limpiar
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm table-shell">
            <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                <h6 class="mb-0 fw-semibold text-muted">
                    <i class="bi bi-list-ul me-2"></i>Listado de <?php echo $filtroTipo ? h($filtroTipo).'s' : 'Tareas'; ?>
                </h6>
                <?php if ($config['show_actions']): ?>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#columnSettingsModal">
                        <i class="bi bi-sliders me-1"></i>Columnas
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <div class="table-responsive" style="max-height:520px;overflow:auto;">
                <table id="<?php echo h($tableId); ?>" class="table table-hover align-middle mb-0 table-sm" style="min-width:1100px;">
                    <thead class="bg-gradient-indice text-white">
                        <tr>
                            <th class="text-center" style="width:90px;">Folio</th>
                            <th style="width:220px;">Título</th>
                            <th style="width:260px;">Descripción</th>
                            <th class="text-center" style="width:120px;">Unidad</th>
                            <th class="text-center" style="width:120px;">Negocio</th>
                            <th class="text-center" style="width:110px;">F.Inicio</th>
                            <th class="text-center" style="width:110px;">F.Entrega</th>
                            <th class="text-center" style="width:110px;">F.Fin</th>
                            <th class="text-center" style="width:100px;">Nivel</th>
                            <th class="text-center" style="width:100px;">Tipo</th>
                            <th class="text-center" style="width:120px;">Status</th>
                            <?php if ($config['show_actions']): ?><th class="text-center" style="width:100px;">Acciones</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="tbody_tasks">
                        <tr class="text-center text-muted">
                            <td colspan="<?php echo $config['show_actions'] ? '12' : '11'; ?>" class="py-5">
                                <div class="spinner-border spinner-border-sm text-warning" role="status"><span class="visually-hidden">Cargando...</span></div>
                                <p class="mt-3 mb-0 fw-medium">Cargando...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="empty_state" class="text-center py-5 d-none">
            <i class="bi bi-inbox" style="font-size:3.5rem;color:#d1d5db;"></i>
            <p class="text-muted mt-3 mb-0 fw-medium">No se encontraron registros</p>
            <p class="text-muted small">Ajusta filtros o crea uno nuevo</p>
        </div>
    </div>

    <style>
    .table-shell { border-radius:16px; overflow:hidden; }
    .bg-gradient-indice { background: linear-gradient(135deg,#FACC15 0%,#EAB308 100%); }
    .table-shell thead th { font-weight:600; font-size:0.7rem; text-transform:uppercase; letter-spacing:.5px; }
    .table-shell tbody td { font-size:.82rem; }
    .table-shell tbody tr:hover { background: linear-gradient(90deg,#fef9e7 0%,#fffbeb 100%); }
    @media (max-width:1400px){ .table-shell thead th, .table-shell tbody td { padding:.55rem .45rem; } }
    </style>

    <script>
    (function(){
        window.TasksTableConfig = {
            tipo: '<?php echo h($config['tipo']); ?>',
            editable: <?php echo $config['editable'] ? 'true' : 'false'; ?>,
            showActions: <?php echo $config['show_actions'] ? 'true' : 'false'; ?>,
            filtroTipo: '<?php echo h($filtroTipo); ?>',
            filtroFecha: <?php echo $filtroFecha ? 'true' : 'false'; ?>,
            tableId: '<?php echo h($tableId); ?>'
        };
        console.log('[TasksTable] Config', window.TasksTableConfig);
        document.addEventListener('DOMContentLoaded', function(){
            if (typeof cargarTareas === 'function') {
                var filtros = { tipo: window.TasksTableConfig.filtroTipo || '' };
                if (window.TasksTableConfig.filtroFecha) { filtros.date_filter = 'today'; }
                try { cargarTareas(filtros); } catch(e){ console.error('[TasksTable] cargarTareas error', e); }
            }
        });
    })();
    </script>

    <style>
    /* ========================================
       ESTILOS ADICIONALES - BADGES DE PRIORIDAD/NIVEL
       ======================================== */
    
    .badge-urgente {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        font-weight: 700;
        padding: 0.4rem 0.85rem;
        border-radius: 10px;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        box-shadow: 0 4px 8px rgba(239, 68, 68, 0.25);
    }

.badge-importante {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    font-weight: 700;
    padding: 0.4rem 0.85rem;
    border-radius: 10px;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    box-shadow: 0 4px 8px rgba(245, 158, 11, 0.25);
}

.badge-normal {
    background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
    color: white;
    font-weight: 700;
    padding: 0.4rem 0.85rem;
    border-radius: 10px;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    box-shadow: 0 4px 8px rgba(156, 163, 175, 0.25);
}

.badge-baja {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    font-weight: 700;
    padding: 0.4rem 0.85rem;
    border-radius: 10px;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    box-shadow: 0 4px 8px rgba(16, 185, 129, 0.25);
}

/* ========================================
   BADGES DE STATUS
   ======================================== */

.badge-pendiente {
    background: #fbbf24;
    color: #78350f;
    font-weight: 700;
    padding: 0.4rem 0.85rem;
    border-radius: 10px;
    font-size: 0.7rem;
    letter-spacing: 0.4px;
}

.badge-en_proceso {
    background: #3b82f6;
    color: white;
    font-weight: 700;
    padding: 0.4rem 0.85rem;
    border-radius: 10px;
    font-size: 0.7rem;
    letter-spacing: 0.4px;
}

.badge-completada {
    background: #10b981;
    color: white;
    font-weight: 700;
    padding: 0.4rem 0.85rem;
    border-radius: 10px;
    font-size: 0.7rem;
    letter-spacing: 0.4px;
}

.badge-cancelada {
    background: #6b7280;
    color: white;
    font-weight: 700;
    padding: 0.4rem 0.85rem;
    border-radius: 10px;
    font-size: 0.7rem;
    letter-spacing: 0.4px;
}

/* ========================================
   TOOLTIPS PERSONALIZADOS
   ======================================== */

.table-tasks-container [data-bs-toggle="tooltip"] {
    cursor: help;
    position: relative;
}

/* ========================================
   ANIMACIONES
   ======================================== */

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.table-tasks-container tbody tr.new-row {
    animation: slideIn 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* ========================================
   BOTONES DE ACCIÓN
   ======================================== */

.btn-action {
    padding: 0.35rem 0.65rem;
    font-size: 0.75rem;
    border-radius: 8px;
    transition: all 0.2s ease;
    font-weight: 600;
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}

.btn-action.btn-warning {
    background: #FACC15;
    border-color: #FACC15;
    color: #000;
}

.btn-action.btn-warning:hover {
    background: #EAB308;
    border-color: #EAB308;
}

/* ========================================
   RESPONSIVE
   ======================================== */

@media (max-width: 1400px) {
    .table-tasks-container .table {
        font-size: 0.8rem;
    }
    
    .table-tasks-container .table thead th,
    .table-tasks-container .table tbody td {
        padding: 0.625rem 0.5rem;
    }
}

/* ========================================
   ESTADOS DE FILAS
   ======================================== */

.table-tasks-container tbody tr.task-overdue {
    background-color: rgba(239, 68, 68, 0.05);
}

.table-tasks-container tbody tr.task-overdue:hover {
    background-color: rgba(239, 68, 68, 0.1);
}

.table-tasks-container tbody tr.task-completed {
    opacity: 0.7;
}

/* ========================================
   FORMULARIOS INLINE
   ======================================== */

.table-tasks-container input.form-control-sm,
.table-tasks-container select.form-select-sm {
    border-radius: 8px;
    border: 1px solid #d1d5db;
    transition: all 0.2s ease;
}

.table-tasks-container input.form-control-sm:focus,
.table-tasks-container select.form-select-sm:focus {
    border-color: #FACC15;
    box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.1);
}
</style>
