// ========================================
// tareas.js v4.0 - ENTERPRISE PATCH COMPLETE
// Índice ERP - Módulo de Tareas
// ========================================

console.log('📦 tareas.js v4.0 cargado');

// ========================================
// CONFIGURACIÓN
// ========================================
const API_BASE = '/modules/processes_tasks/controllers/api.controller.php';
const BASE_API_URL = '/modules/processes_tasks/controllers/api.controller.php';

// ========================================
// HELPERS
// ========================================

function getCSRFToken() {
    return document.querySelector('input[name="csrf_token"]')?.value
        || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || '';
}

function showNotification(message, type = 'info') {
    // Fallback a window.showToast si existe (Enterprise Patch)
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
        return;
    }

    const bgClass = {
        success: 'bg-success',
        error: 'bg-danger',
        warning: 'bg-warning',
        info: 'bg-primary'
    }[type] || 'bg-primary';

    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white ${bgClass} border-0`;
    toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:20000;';
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');

    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;

    document.body.appendChild(toast);

    if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    } else {
        setTimeout(() => toast.remove(), 3000);
    }
}

// ========================================
// FUNCIONES DE PROCESOS
// ========================================

window.nuevoProceso = function () {
    const modalElement = document.getElementById('newProcessModal');
    if (!modalElement) {
        console.error('Modal newProcessModal no encontrado');
        return;
    }

    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    setTimeout(() => {
        if (typeof initSelect2 === 'function') {
            initSelect2();
        }
    }, 200);
};

async function guardarProceso() {
    const form = document.getElementById('formNuevoProceso');
    const btnSave = document.getElementById('btn-guardar-proceso');
    const originalText = btnSave?.innerHTML || '';

    if (!form) {
        showNotification('Formulario no encontrado', 'error');
        return;
    }

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const titulo = document.getElementById('proceso-titulo')?.value?.trim();
    if (!titulo) {
        showNotification('El título es obligatorio', 'warning');
        return;
    }

    if (btnSave) {
        btnSave.disabled = true;
        btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';
    }

    const formData = new FormData(form);
    formData.append('tipo', 'Proceso');

    try {
        const response = await fetch(`${API_BASE}?action=create_task`, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.ok) {
            showNotification('Proceso creado exitosamente', 'success');

            const modal = bootstrap.Modal.getInstance(document.getElementById('newProcessModal'));
            if (modal) modal.hide();

            form.reset();

            if (typeof cargarEstadisticas === 'function') {
                cargarEstadisticas();
            }
        } else {
            showNotification(result.error || 'Error al guardar el proceso', 'error');
        }
    } catch (error) {
        console.error('Error al guardar proceso:', error);
        showNotification('Error de conexión al guardar proceso', 'error');
    } finally {
        if (btnSave) {
            btnSave.disabled = false;
            btnSave.innerHTML = originalText;
        }
    }
}

// ========================================
// FUNCIONES DE ESTADÍSTICAS
// ========================================

async function cargarEstadisticas() {
    try {
        const response = await fetch(`${API_BASE}?action=get_stats_processes`);
        const result = await response.json();

        if (result.ok) {
            const stats = result.data || {};

            document.getElementById('kpi-active-processes').textContent = stats.active || 0;
            document.getElementById('kpi-completed-processes').textContent = stats.completed || 0;
            document.getElementById('kpi-paused-processes').textContent = stats.paused || 0;
            document.getElementById('kpi-total-processes').textContent = stats.total || 0;
        }
    } catch (error) {
        console.error('Error cargando estadísticas de procesos:', error);
    }
}

async function cargarEstadisticasTareas() {
    try {
        const response = await fetch(`${API_BASE}?action=get_stats_tasks`);
        const result = await response.json();

        if (result.ok) {
            const stats = result.data || {};

            const inProgressElem = document.getElementById('kpi-in-progress-tasks');
            const overdueElem = document.getElementById('kpi-overdue-tasks');
            const completedElem = document.getElementById('kpi-completed-tasks');
            const totalElem = document.getElementById('kpi-total-tasks');

            if (inProgressElem) inProgressElem.textContent = stats.in_progress || 0;
            if (overdueElem) overdueElem.textContent = stats.overdue || 0;
            if (completedElem) completedElem.textContent = stats.completed || 0;
            if (totalElem) totalElem.textContent = stats.total || 0;
        }
    } catch (error) {
        console.error('Error cargando estadísticas de tareas:', error);
    }
}

// ========================================
// ENTERPRISE PATCH 7: NORMALIZE DATE HELPER
// ========================================
function normalizeDate(dateString) {
    if (!dateString) return '-';
    // Si viene en formato ISO 8601, tomar solo la parte de fecha
    if (dateString.includes('T')) {
        return dateString.split('T')[0];
    }
    return dateString;
}

// ========================================
// CARGAR TAREAS/PROCESOS (ENTERPRISE PATCH 9)
// ========================================
async function cargarTareas(filtros = {}, targetTbody = null) {
    const tbody = targetTbody || document.querySelector('#tablaTareas tbody') || document.querySelector('#tablaProcesos tbody');

    if (!tbody) {
        console.error('No se encontró tbody para renderizar');
        return;
    }

    tbody.innerHTML = `
        <tr>
            <td colspan="12" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="text-muted mt-3">Cargando datos...</p>
            </td>
        </tr>
    `;

    try {
        const params = new URLSearchParams();
        Object.keys(filtros).forEach(key => {
            const value = filtros[key];
            if (value !== undefined && value !== null && value !== '') {
                params.append(key, value);
            }
        });

        params.append('action', 'list_tasks');

        // ENTERPRISE PATCH 9: Enhanced headers
        const response = await fetch(`${API_BASE}?${params.toString()}`, {
            method: 'GET',
            headers: {
                'X-CSRF-Token': getCSRFToken(),
                'Accept': 'application/json'
            }
        });

        console.log('📡 Request URL:', response.url);
        console.log('📊 Response status:', response.status);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();
        console.log('📦 API Response:', result);

        if (result.ok && Array.isArray(result.data)) {
            console.log(`✅ ${result.data.length} registros recibidos`);
            renderTareasTable(result.data, tbody);
        } else {
            console.warn('⚠️ Respuesta sin datos válidos');
            tbody.innerHTML = '<tr><td colspan="12" class="text-center text-muted py-4">No hay datos disponibles</td></tr>';
        }
    } catch (error) {
        console.error('❌ Error cargando tareas:', error);
        tbody.innerHTML = `<tr><td colspan="12" class="text-center text-danger py-4">Error: ${error.message}</td></tr>`;
        showNotification('Error al cargar datos: ' + error.message, 'error');
    }
}

// ========================================
// RENDERIZAR TABLA (ALL ENTERPRISE PATCHES)
// ========================================
function renderTareasTable(data, tbody) {
    if (!tbody) {
        console.error('❌ tbody no proporcionado a renderTareasTable');
        return;
    }

    if (!Array.isArray(data) || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="12" class="text-center text-muted py-4">No hay registros para mostrar</td></tr>';

        const emptyState = document.getElementById('empty-state-tareas') || document.getElementById('empty-state-procesos');
        if (emptyState) emptyState.style.display = 'block';

        const tableWrapper = tbody.closest('.table-responsive');
        if (tableWrapper) tableWrapper.style.display = 'none';

        return;
    }

    const emptyState = document.getElementById('empty-state-tareas') || document.getElementById('empty-state-procesos');
    if (emptyState) emptyState.style.display = 'none';

    const tableWrapper = tbody.closest('.table-responsive');
    if (tableWrapper) tableWrapper.style.display = 'block';

    const fragment = document.createDocumentFragment();

    data.forEach((row, index) => {
        // ENTERPRISE PATCH 1, 3: ID normalization
        const id = row.id || row.task_id || row.proceso_id || null;

        console.log(`📋 Registro ${index + 1}:`, row);

        // ENTERPRISE PATCH 3, 4: hasFiles calculation
        const hasFiles = !!(row.archivos_count || (Array.isArray(row.archivos) && row.archivos.length));

        const statusClass = {
            'En tiempo': 'info',
            'En proceso': 'primary',
            'Terminada': 'success',
            'Pausada': 'secondary',
            'Vencida': 'danger',
            'Auditada': 'dark'
        }[row.status] || 'secondary';

        const nivelClass = {
            'Normal': 'secondary',
            'Importante': 'warning',
            'Urgente': 'danger'
        }[row.nivel] || 'secondary';

        const tr = document.createElement('tr');

        tr.innerHTML = `
            <td data-column="unidad" class="small">${row.unidad || row.unidad_nombre || '-'}</td>
            <td data-column="negocio" class="small">${row.negocio || row.negocio_nombre || '-'}</td>
            <td data-column="descripcion" class="small">
                <div class="text-truncate" style="max-width: 250px;" title="${row.titulo || row.title || row.descripcion || row.description || '-'}">
                    ${row.titulo || row.title || row.descripcion || row.description || '-'}
                </div>
            </td>
            <td data-column="inicia" class="small">${normalizeDate(row.fecha_inicio || row.start_date)}</td>
            <td data-column="vence" class="small">${normalizeDate(row.fecha_entrega || row.fecha_fin || row.delivery_date)}</td>
            <td data-column="status" class="small">
                <span class="badge bg-${statusClass}-subtle text-${statusClass}">${row.status || '-'}</span>
            </td>
            <td data-column="archivo" class="small text-center">
                ${hasFiles ? '<i class="bi bi-paperclip text-primary"></i>' : '-'}
            </td>
            <td data-column="creador" class="small">
                ${row.creador || row.usuario_creador_nombre || row.creator_name || '-'}
            </td>
            <td data-column="delegado" class="small">
                ${row.delegado
            || row.usuario_delegado_nombre
            || row.assignee_name
            || row.delegate_name
            || row.assigned_to
            || '-'}
            </td>
            <td data-column="nivel" class="small">
                <span class="badge bg-${nivelClass}-subtle text-${nivelClass}">${row.nivel || row.priority || '-'}</span>
            </td>
            <td data-column="tipo" class="small">
                <span class="badge bg-light text-dark border">${row.tipo || row.type || '-'}</span>
            </td>
            <td data-column="acciones" class="small">
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-primary btn-sm" 
                            onclick="verDetalle(${id})" 
                            title="Ver detalle"
                            ${!id ? 'disabled' : ''}>
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" 
                            onclick="editarTarea(${id})" 
                            title="Editar"
                            ${!id ? 'disabled' : ''}>
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-info btn-sm" 
                            onclick="duplicarTarea(${id})" 
                            title="Duplicar"
                            ${!id ? 'disabled' : ''}>
                        <i class="bi bi-files"></i>
                    </button>
                    <button class="btn btn-outline-dark btn-sm" 
                            onclick="auditarTarea(${id})" 
                            title="Auditar"
                            ${!id ? 'disabled' : ''}>
                        <i class="bi bi-shield-check"></i>
                    </button>
                    <button class="btn btn-outline-danger btn-sm" 
                            onclick="eliminarTarea(${id})" 
                            title="Eliminar"
                            ${!id ? 'disabled' : ''}>
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        `;

        fragment.appendChild(tr);
    });

    tbody.innerHTML = '';
    tbody.appendChild(fragment);

    // Reaplicar visibilidad de columnas si existe configuración
    if (typeof TaskModule !== 'undefined' && typeof TaskModule.applyColumnVisibilityTareas === 'function') {
        try {
            const savedConfig = localStorage.getItem('tasks_columns_config');
            if (savedConfig) {
                const config = JSON.parse(savedConfig);
                TaskModule.applyColumnVisibilityTareas(config);
            }
        } catch (e) {
            console.warn('⚠️ Error aplicando visibilidad de columnas:', e);
        }
    }

    // Inicializar tooltips de Bootstrap si existen
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggers = tbody.querySelectorAll('[title]');
        tooltipTriggers.forEach(el => new bootstrap.Tooltip(el));
    }

    console.log(`✅ ${data.length} filas renderizadas correctamente`);
}

// Legacy wrapper para compatibilidad
window.renderTareas = function (data) {
    const tbody = document.querySelector('#tablaTareas tbody') || document.querySelector('#tablaProcesos tbody');
    renderTareasTable(data, tbody);
};

// ========================================
// ACCIONES DE TAREAS
// ========================================

window.verDetalle = function (id) {
    if (!id) {
        showNotification('ID no válido', 'error');
        return;
    }
    console.log('Ver detalle de tarea:', id);
    showNotification('Función de detalle en desarrollo', 'info');
};

window.editarTarea = function (id) {
    if (!id) {
        showNotification('ID no válido', 'error');
        return;
    }
    console.log('Editar tarea:', id);
    showNotification('Función de edición en desarrollo', 'info');
};

window.duplicarTarea = function (id) {
    if (!id) {
        showNotification('ID no válido', 'error');
        return;
    }
    console.log('Duplicar tarea:', id);
    showNotification('Función de duplicación en desarrollo', 'info');
};

window.auditarTarea = function (id) {
    if (!id) {
        showNotification('ID no válido', 'error');
        return;
    }
    console.log('Auditar tarea:', id);
    showNotification('Función de auditoría en desarrollo', 'info');
};

window.eliminarTarea = async function (id) {
    if (!id) {
        showNotification('ID no válido', 'error');
        return;
    }

    if (!confirm('¿Estás seguro de que deseas eliminar esta tarea?')) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE}?action=delete_task&id=${id}`, {
            method: 'GET',
            headers: {
                'X-CSRF-Token': getCSRFToken()
            }
        });

        const result = await response.json();

        if (result.ok) {
            showNotification('Tarea eliminada correctamente', 'success');

            if (typeof cargarTareas === 'function') {
                const tbody = document.querySelector('#tablaTareas tbody');
                cargarTareas({ tipo: 'Tarea' }, tbody);
            }

            if (typeof cargarEstadisticasTareas === 'function') {
                cargarEstadisticasTareas();
            }
        } else {
            showNotification(result.error || 'Error al eliminar la tarea', 'error');
        }
    } catch (error) {
        console.error('Error eliminando tarea:', error);
        showNotification('Error de conexión al eliminar', 'error');
    }
};

// ========================================
// EXPORTAR FUNCIONES AL SCOPE GLOBAL
// ========================================

window.cargarTareas = cargarTareas;
window.cargarEstadisticas = cargarEstadisticas;
window.cargarEstadisticasTareas = cargarEstadisticasTareas;
window.guardarProceso = guardarProceso;
window.showNotification = showNotification;

console.log('✅ tareas.js inicializado correctamente');
