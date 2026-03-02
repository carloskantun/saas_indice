/**
 * Tareas.js - Versión Corporativa 2.0
 * Sistema de gestión de tareas con edición inline
 * Compatible con componente table_tasks.php
 * 
 * @version 2.0.0
 * @author Sistema Índice 2025
 */

// ========================================
// CONFIGURACIÓN Y CONSTANTES
// ========================================

const API_BASE = '/modules/processes_tasks/controllers/api.controller.php';
const CSRF_TOKEN = document.querySelector('input[name="csrf_token"]')?.value || '';

// Cache para evitar múltiples requests
let cacheUnits = [];
let cacheBusinesses = [];
let cacheEmployees = [];
let currentFilters = {
    unit: '',
    business: '',
    status: '',
    colaborador: '',
    periodo: '',
    search: ''
};

// ========================================
// FUNCIONES DE API
// ========================================

/**
 * Hacer request a la API con manejo de errores
 */
async function apiRequest(action, data = {}, method = 'GET') {
    try {
        const url = new URL(API_BASE, window.location.origin);
        url.searchParams.append('action', action);

        if (method === 'GET') {
            Object.entries(data).forEach(([key, value]) => {
                if (value !== '' && value !== null && value !== undefined) {
                    url.searchParams.append(key, value);
                }
            });

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return await response.json();
        } else {
            // POST
            const formData = new FormData();
            formData.append('csrf_token', CSRF_TOKEN);
            Object.entries(data).forEach(([key, value]) => {
                formData.append(key, value);
            });

            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            return await response.json();
        }
    } catch (error) {
        console.error('[API Error]', error);
        showToast('Error de conexión: ' + error.message, 'error');
        return { ok: false, error: error.message };
    }
}

/**
 * Cargar tareas desde el servidor
 */
async function cargarTareas(filters = {}) {
    const tbody = document.getElementById('tbody_tasks');
    const emptyState = document.getElementById('empty_state');

    // Mostrar loading
    tbody.innerHTML = `
        <tr class="text-center">
            <td colspan="12" class="py-5">
                <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                <p class="mt-2 text-muted mb-0">Cargando tareas...</p>
            </td>
        </tr>
    `;

    try {
        const params = {
            ...filters,
            company_id: window.companyId || 1
        };

        const response = await apiRequest('list_tasks', params, 'GET');

        if (response.ok && response.data) {
            if (response.data.length === 0) {
                tbody.innerHTML = '';
                emptyState?.classList.remove('d-none');
            } else {
                renderTasks(response.data);
                emptyState?.classList.add('d-none');
            }
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="12" class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle fs-3"></i>
                        <p class="mt-2">${response.error || 'Error al cargar tareas'}</p>
                    </td>
                </tr>
            `;
        }
    } catch (error) {
        console.error('[cargarTareas] Error:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="12" class="text-center text-danger py-4">
                    <i class="bi bi-x-circle fs-3"></i>
                    <p class="mt-2">Error de conexión</p>
                </td>
            </tr>
        `;
    }
}

/**
 * Renderizar tareas en la tabla
 */
function renderTasks(tasks) {
    const tbody = document.getElementById('tbody_tasks');
    tbody.innerHTML = '';

    tasks.forEach((task, index) => {
        const row = document.createElement('tr');
        row.classList.add('new-row');
        row.dataset.taskId = task.id;

        // Determinar si la tarea está vencida
        if (task.fecha_entrega && new Date(task.fecha_entrega) < new Date() && task.status !== 'completada') {
            row.classList.add('task-overdue');
        }

        if (task.status === 'completada') {
            row.classList.add('task-completed');
        }

        row.innerHTML = `
            <td class="text-center fw-semibold">${task.folio || '-'}</td>
            <td contenteditable="${window.TasksTableConfig?.editable ? 'true' : 'false'}"
                data-field="titulo"
                data-task-id="${task.id}">
                ${escapeHtml(task.titulo || '')}
            </td>
            <td contenteditable="${window.TasksTableConfig?.editable ? 'true' : 'false'}"
                data-field="descripcion"
                data-task-id="${task.id}"
                title="${escapeHtml(task.descripcion || '')}">
                ${truncate(task.descripcion || 'Sin descripción', 50)}
            </td>
            <td class="text-center small">${task.unit_nombre || '-'}</td>
            <td class="text-center small">${task.business_nombre || '-'}</td>
            <td class="text-center small">${formatDate(task.fecha_inicio)}</td>
            <td class="text-center small">${formatDate(task.fecha_entrega)}</td>
            <td class="text-center small">${formatDate(task.fecha_fin)}</td>
            <td class="text-center">${renderBadgeNivel(task.nivel)}</td>
            <td class="text-center"><span class="badge bg-secondary">${task.tipo || 'Tarea'}</span></td>
            <td class="text-center">${renderBadgeStatus(task.status)}</td>
            ${window.TasksTableConfig?.showActions ? `
            <td class="text-center">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-action btn-warning" onclick="editarTarea(${task.id})" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-action btn-danger" onclick="eliminarTarea(${task.id})" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>` : ''}
        `;

        tbody.appendChild(row);
    });

    // Activar edición inline
    if (window.TasksTableConfig?.editable) {
        activarEdicionInline();
    }

    // Activar tooltips
    initTooltips();
}

/**
 * Renderizar badge de nivel/prioridad
 */
function renderBadgeNivel(nivel) {
    const badges = {
        'Urgente': '<span class="badge badge-urgente">URGENTE</span>',
        'Importante': '<span class="badge badge-importante">IMPORTANTE</span>',
        'Normal': '<span class="badge badge-normal">NORMAL</span>',
        'Baja': '<span class="badge badge-baja">BAJA</span>'
    };
    return badges[nivel] || badges['Normal'];
}

/**
 * Renderizar badge de status
 */
function renderBadgeStatus(status) {
    const badges = {
        'pendiente': '<span class="badge badge-pendiente">Pendiente</span>',
        'en_proceso': '<span class="badge badge-en_proceso">En Proceso</span>',
        'completada': '<span class="badge badge-completada">Completada</span>',
        'cancelada': '<span class="badge badge-cancelada">Cancelada</span>'
    };
    return badges[status] || badges['pendiente'];
}

// ========================================
// EDICIÓN INLINE
// ========================================

/**
 * Activar la edición inline en celdas contenteditable
 */
function activarEdicionInline() {
    const editableCells = document.querySelectorAll('td[contenteditable="true"]');

    editableCells.forEach(cell => {
        cell.addEventListener('blur', async function () {
            const taskId = this.dataset.taskId;
            const field = this.dataset.field;
            const newValue = this.textContent.trim();

            await guardarCampoInline(taskId, field, newValue);
        });

        cell.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur();
            }
        });
    });
}

/**
 * Guardar cambio de campo inline
 */
async function guardarCampoInline(taskId, field, value) {
    const data = {
        task_id: taskId,
        [field]: value
    };

    const response = await apiRequest('update_task', data, 'POST');

    if (response.ok) {
        showToast('Campo actualizado correctamente', 'success');
    } else {
        showToast('Error al actualizar: ' + (response.error || 'desconocido'), 'error');
    }
}

// ========================================
// MODALES Y FORMULARIOS
// ========================================

/**
 * Crear nueva tarea
 */
async function crearTarea(formData) {
    const response = await apiRequest('create_task', formData, 'POST');

    if (response.ok) {
        showToast('Tarea creada exitosamente', 'success');
        // Cerrar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalNuevaTarea'));
        modal?.hide();
        // Recargar tabla
        cargarTareas(currentFilters);
    } else {
        showToast('Error al crear tarea: ' + (response.error || 'desconocido'), 'error');
    }
}

/**
 * Editar tarea existente
 */
function editarTarea(taskId) {
    // Implementar modal de edición
    console.log('[editarTarea]', taskId);
    showToast('Modal de edición en desarrollo', 'info');
}

/**
 * Eliminar tarea con confirmación
 */
async function eliminarTarea(taskId) {
    if (!confirm('¿Está seguro de eliminar esta tarea?')) {
        return;
    }

    const response = await apiRequest('delete_task', { task_id: taskId }, 'POST');

    if (response.ok) {
        showToast('Tarea eliminada correctamente', 'success');
        // Remover fila con animación
        const row = document.querySelector(`tr[data-task-id="${taskId}"]`);
        if (row) {
            row.style.opacity = '0';
            row.style.transform = 'translateX(-20px)';
            setTimeout(() => row.remove(), 300);
        }
    } else {
        showToast('Error al eliminar: ' + (response.error || 'desconocido'), 'error');
    }
}

// ========================================
// FILTROS
// ========================================

/**
 * Aplicar filtros a la tabla
 */
function aplicarFiltros() {
    currentFilters = {
        unit: document.getElementById('filter_unit')?.value || '',
        business: document.getElementById('filter_business')?.value || '',
        status: document.getElementById('filter_status')?.value || '',
        colaborador: document.getElementById('filter_colaborador')?.value || '',
        periodo: document.getElementById('filter_periodo')?.value || '',
        search: document.getElementById('filter_search')?.value || ''
    };

    // Guardar en localStorage
    localStorage.setItem('tasks_filters', JSON.stringify(currentFilters));

    // Recargar tareas
    cargarTareas({
        unit_id: currentFilters.unit,
        business_id: currentFilters.business,
        status: currentFilters.status,
        delegated_to: currentFilters.colaborador,
        q: currentFilters.search
    });
}

/**
 * Limpiar todos los filtros
 */
function limpiarFiltros() {
    document.getElementById('filter_unit').value = '';
    document.getElementById('filter_business').value = '';
    document.getElementById('filter_status').value = '';
    document.getElementById('filter_colaborador').value = '';
    document.getElementById('filter_periodo').value = '';
    document.getElementById('filter_search').value = '';

    currentFilters = {};
    localStorage.removeItem('tasks_filters');

    cargarTareas();
}

/**
 * Cargar filtros guardados desde localStorage
 */
function cargarFiltrosGuardados() {
    const saved = localStorage.getItem('tasks_filters');
    if (saved) {
        try {
            const filters = JSON.parse(saved);
            document.getElementById('filter_unit').value = filters.unit || '';
            document.getElementById('filter_business').value = filters.business || '';
            document.getElementById('filter_status').value = filters.status || '';
            document.getElementById('filter_colaborador').value = filters.colaborador || '';
            document.getElementById('filter_periodo').value = filters.periodo || '';
            document.getElementById('filter_search').value = filters.search || '';
            currentFilters = filters;
        } catch (e) {
            console.error('Error cargando filtros guardados:', e);
        }
    }
}

// ========================================
// NOTIFICACIONES TOAST
// ========================================

/**
 * Mostrar notificación toast
 */
function showToast(message, type = 'success') {
    // Buscar contenedor de toast o crearlo
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }

    const icons = {
        success: 'bi-check-circle-fill text-success',
        error: 'bi-x-circle-fill text-danger',
        warning: 'bi-exclamation-triangle-fill text-warning',
        info: 'bi-info-circle-fill text-info'
    };

    const bgColors = {
        success: 'bg-success',
        error: 'bg-danger',
        warning: 'bg-warning',
        info: 'bg-info'
    };

    const toastHtml = `
        <div class="toast align-items-center text-white ${bgColors[type]} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi ${icons[type]} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHtml);

    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement, { delay: 3500 });
    toast.show();

    // Remover elemento después de ocultarse
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

// ========================================
// UTILIDADES
// ========================================

/**
 * Formatear fecha
 */
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

/**
 * Truncar texto
 */
function truncate(text, maxLength) {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

/**
 * Escapar HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Inicializar tooltips Bootstrap
 */
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(el => {
        new bootstrap.Tooltip(el);
    });
}

// ========================================
// INICIALIZACIÓN
// ========================================

document.addEventListener('DOMContentLoaded', function () {
    console.log('[Tareas.js] Inicializando módulo...');

    // Cargar filtros guardados
    cargarFiltrosGuardados();

    // Event listeners para filtros
    document.getElementById('filter_unit')?.addEventListener('change', aplicarFiltros);
    document.getElementById('filter_business')?.addEventListener('change', aplicarFiltros);
    document.getElementById('filter_status')?.addEventListener('change', aplicarFiltros);
    document.getElementById('filter_colaborador')?.addEventListener('change', aplicarFiltros);
    document.getElementById('filter_periodo')?.addEventListener('change', aplicarFiltros);
    document.getElementById('filter_search')?.addEventListener('input', debounce(aplicarFiltros, 500));
    document.getElementById('btn_clear_filters')?.addEventListener('click', limpiarFiltros);

    // Cargar tareas inicial
    cargarTareas(currentFilters);

    console.log('[Tareas.js] Módulo inicializado correctamente');
});

/**
 * Debounce helper
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Exponer funciones globales
window.cargarTareas = cargarTareas;
window.crearTarea = crearTarea;
window.editarTarea = editarTarea;
window.eliminarTarea = eliminarTarea;
window.aplicarFiltros = aplicarFiltros;
window.limpiarFiltros = limpiarFiltros;
window.showToast = showToast;

console.log('[Tareas.js] Módulo cargado');
