<?php
// views/bitacoras.php - Activity log view with institutional styling
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="h3 fw-bold text-primary mb-1">📜 Bitácora de Actividad</h2>
    <p class="text-muted mb-0">Registro completo de acciones y cambios en el sistema</p>
  </div>
  
  <div class="d-flex gap-2">
    <button class="btn btn-outline-secondary" id="btn-export-log-excel">
      <i class="bi bi-file-earmark-excel me-1"></i>Excel
    </button>
    <button class="btn btn-outline-secondary" id="btn-export-log-pdf">
      <i class="bi bi-file-earmark-pdf me-1"></i>PDF
    </button>
  </div>
</div>

<!-- KPIs Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
        <div class="card shadow-sm border-0 reveal h-100">
            <div class="card-body text-center">
                <i class="bi bi-clock-history fs-1 text-info mb-2"></i>
                <div class="h3 fw-bold mb-1" id="kpi-last-24h">0</div>
                <div class="small text-muted">Últimas 24h</div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-md-3">
        <div class="card shadow-sm border-0 reveal h-100">
            <div class="card-body text-center">
                <i class="bi bi-activity fs-1 text-primary mb-2"></i>
                <div class="h3 fw-bold mb-1" id="kpi-total-actions">0</div>
                <div class="small text-muted">Total Acciones</div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-md-3">
        <div class="card shadow-sm border-0 reveal h-100">
            <div class="card-body text-center">
                <i class="bi bi-people fs-1 text-success mb-2"></i>
                <div class="h3 fw-bold mb-1" id="kpi-active-users">0</div>
                <div class="small text-muted">Usuarios Activos</div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-md-3">
        <div class="card shadow-sm border-0 reveal h-100">
            <div class="card-body text-center">
                <i class="bi bi-diagram-3 fs-1 text-warning mb-2"></i>
                <div class="h3 fw-bold mb-1" id="kpi-processes-updated">0</div>
                <div class="small text-muted">Procesos Actualizados</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Section -->
<div class="card shadow-sm border-0 reveal mb-4">
    <div class="card-body">
        <h5 class="card-title text-primary mb-3">
            <i class="bi bi-funnel me-2"></i>Filtros
        </h5>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Tipo de Acción</label>
                <select class="form-select" id="filter-action-type">
                    <option value="">Todas</option>
                    <option value="create">Crear</option>
                    <option value="edit">Editar</option>
                    <option value="delete">Eliminar</option>
                    <option value="complete">Completar</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label fw-semibold">Usuario</label>
                <select class="form-select" id="filter-user">
                    <option value="">Todos</option>
                    <!-- Populated dynamically -->
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label fw-semibold">Fecha Desde</label>
                <input type="date" class="form-control" id="filter-date-from">
            </div>
            
            <div class="col-md-3">
                <label class="form-label fw-semibold">Fecha Hasta</label>
                <input type="date" class="form-control" id="filter-date-to">
            </div>
        </div>
    </div>
</div>

<!-- Activity Log Table -->
<div class="card shadow-sm border-0 reveal">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="log-table">
                <thead class="bg-light">
                    <tr>
                        <th class="fw-semibold" style="width: 140px;">Fecha y Hora</th>
                        <th class="fw-semibold">Usuario</th>
                        <th class="fw-semibold">Acción</th>
                        <th class="fw-semibold">Módulo</th>
                        <th class="fw-semibold">Descripción</th>
                        <th class="fw-semibold" style="width: 100px;">Detalles</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Empty state -->
                    <tr id="log-empty-state">
                        <td colspan="6" class="text-center py-5">
                            <div class="py-4">
                                <i class="bi bi-journal-text fs-1 text-muted mb-3 d-block"></i>
                                <h5 class="text-muted mb-2">No hay registros de actividad</h5>
                                <p class="text-muted mb-0">Las acciones del sistema aparecerán aquí</p>
                            </div>
                        </td>
                    </tr>
                    <!-- Sample data rows -->
                    <tr>
                        <td class="text-muted small">2024-01-15 10:30</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="bi bi-person"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">Juan Pérez</div>
                                    <div class="text-muted small">juan.perez@indice.com</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-success">Crear</span>
                        </td>
                        <td>Procesos</td>
                        <td>Creó el proceso "Revisión Semanal de Inventario"</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="showLogDetails('log-001')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="table-light">
                        <td class="text-muted small">2024-01-15 09:15</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-success text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="bi bi-person"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">María González</div>
                                    <div class="text-muted small">maria.gonzalez@indice.com</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-primary">Completar</span>
                        </td>
                        <td>Tareas</td>
                        <td>Completó la tarea "Actualizar documentación técnica"</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="showLogDetails('log-002')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted small">2024-01-14 16:45</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-warning text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="bi bi-person"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">Carlos Ramírez</div>
                                    <div class="text-muted small">carlos.ramirez@indice.com</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-info">Editar</span>
                        </td>
                        <td>Procesos</td>
                        <td>Modificó la frecuencia del proceso "Reporte Mensual"</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="showLogDetails('log-003')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title text-primary fw-bold">
                    <i class="bi bi-info-circle me-2"></i>Detalles de la Acción
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="log-details-content">
                <!-- Content populated dynamically -->
                <p class="text-muted">Cargando detalles...</p>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Activity Log module initialization
(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        loadActivityLog();
        loadLogKPIs();
        bindLogEvents();
    });
    
    function loadActivityLog() {
        // TODO: Load activity log from API
        console.log('Loading activity log...');
    }
    
    function loadLogKPIs() {
        // Sample KPIs - replace with real data
        document.getElementById('kpi-last-24h').textContent = '127';
        document.getElementById('kpi-total-actions').textContent = '1,543';
        document.getElementById('kpi-active-users').textContent = '18';
        document.getElementById('kpi-processes-updated').textContent = '9';
    }
    
    function bindLogEvents() {
        // Export to Excel
        document.getElementById('btn-export-log-excel')?.addEventListener('click', function() {
            exportToExcel();
        });
        
        // Export to PDF
        document.getElementById('btn-export-log-pdf')?.addEventListener('click', function() {
            exportToPDF();
        });
        
        // Apply filters
        document.querySelectorAll('#filter-action-type, #filter-user, #filter-date-from, #filter-date-to').forEach(function(el) {
            el.addEventListener('change', applyLogFilters);
        });
    }
    
    function applyLogFilters() {
        const actionType = document.getElementById('filter-action-type').value;
        const user = document.getElementById('filter-user').value;
        const dateFrom = document.getElementById('filter-date-from').value;
        const dateTo = document.getElementById('filter-date-to').value;
        
        console.log('Applying filters:', { actionType, user, dateFrom, dateTo });
        // TODO: Implement filter logic
    }
    
    function exportToExcel() {
        console.log('Exporting to Excel...');
        // TODO: Implement Excel export
    }
    
    function exportToPDF() {
        console.log('Exporting to PDF...');
        // TODO: Implement PDF export
    }
    
    // Global function for showing log details
    window.showLogDetails = function(logId) {
        const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
        const content = document.getElementById('log-details-content');
        
        // Sample details - replace with API call
        content.innerHTML = `
            <div class="mb-3">
                <strong>ID de Registro:</strong> ${logId}
            </div>
            <div class="mb-3">
                <strong>Fecha y Hora:</strong> 2024-01-15 10:30:45
            </div>
            <div class="mb-3">
                <strong>Usuario:</strong> Juan Pérez (juan.perez@indice.com)
            </div>
            <div class="mb-3">
                <strong>Dirección IP:</strong> 192.168.1.100
            </div>
            <div class="mb-3">
                <strong>Acción:</strong> <span class="badge bg-success">Crear</span>
            </div>
            <div class="mb-3">
                <strong>Módulo:</strong> Procesos
            </div>
            <div>
                <strong>Descripción Completa:</strong><br>
                <p class="mt-2 text-muted">El usuario creó un nuevo proceso recurrente con el nombre "Revisión Semanal de Inventario", configurado para ejecutarse cada lunes a las 09:00 AM. El proceso incluye 5 tareas y está asignado al equipo de Operaciones.</p>
            </div>
        `;
        
        modal.show();
    };
})();
</script>
