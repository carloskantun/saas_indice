/**
 * tareas.js - Módulo Procesos & Tareas
 * JavaScript unificado funcional
 * @version 4.0 - Clean Rebuild 2025-11-10
 */

console.log('📦 tareas.js v4.0 cargado');

// ========================================
// CONFIGURACIÓN
// ========================================
const API_BASE = '/modules/processes_tasks/controllers/api.controller.php';
const BASE_API_URL = API_BASE;

// ========================================
// HELPER: CSRF TOKEN
// ========================================
function getCSRFToken() {
    return document.querySelector('input[name="csrf_token"]')?.value
        || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || '';
}

// ========================================
// NOTIFICACIONES (Toast)
// ========================================
function showNotification(message, type = 'success') {
    // Intentar usar el helper global de index.php
    if (typeof window.showToast === 'function') {
        const variant = type === 'error' ? 'danger' : type;
        window.showToast(message, variant);
        return;
    }

    // Fallback: crear toast manualmente
    const container = document.getElementById('toastNotification');
    if (!container) {
        console.warn('No toast container found');
        alert(message);
        return;
    }

    const iconMap = {
        success: 'bi-check-circle-fill',
        error: 'bi-exclamation-triangle-fill',
        warning: 'bi-exclamation-circle-fill',
        info: 'bi-info-circle-fill'
    };

    const colorMap = {
        success: 'text-success',
        error: 'text-danger',
        warning: 'text-warning',
        info: 'text-info'
    };

    const icon = document.getElementById('toastIcon');
    const msgEl = document.getElementById('toastMessage');

    if (icon) icon.className = `bi me-2 fs-5 ${iconMap[type] || iconMap.success} ${colorMap[type] || colorMap.success}`;
    if (msgEl) msgEl.textContent = message;

    const toast = new bootstrap.Toast(container);
    toast.show();
}

// ========================================
// PROCESOS: Abrir modal nuevo proceso
// ========================================
function nuevoProceso() {
    const modal = document.getElementById('newProcessModal');
    if (!modal) {
        console.error('Modal newProcessModal no encontrado');
        return;
    }
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();
    console.log('✅ Modal de nuevo proceso abierto');
}

// ========================================
// PROCESOS: Guardar proceso
// ========================================
async function guardarProceso() {
    const form = document.getElementById('formNuevoProceso');
    const nombreProceso = document.getElementById('process-name')?.value.trim();

    if (!nombreProceso) {
        showNotification('El nombre del proceso es obligatorio', 'error');
        return;
    }

    const btnSave = document.getElementById('btn-save-process');
    const btnText = btnSave.innerHTML;
    btnSave.disabled = true;
    btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

    try {
        const formData = new FormData(form);
        formData.append('tipo', 'Proceso');
        formData.append('csrf_token', getCSRFToken());

        const response = await fetch(API_BASE + '?action=create_task', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();

        if (result.ok) {
            showNotification('Proceso guardado exitosamente', 'success');

            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('newProcessModal'));
            if (modal) modal.hide();

            // Limpiar formulario
            form.reset();

            // Recargar tabla
            if (typeof cargarTareas === 'function') {
                cargarTareas({ tipo: 'Proceso' });
            } else {
                console.warn('Función cargarTareas no disponible');
            }
        } else {
            showNotification(result.error || 'Error al guardar el proceso', 'error');
        }
    } catch (error) {
        console.error('Error al guardar proceso:', error);
        showNotification('Error de conexión: ' + error.message, 'error');
    } finally {
        btnSave.disabled = false;
        btnSave.innerHTML = btnText;
    }
}

// ========================================
// ESTADÍSTICAS DEL DASHBOARD
// ========================================
async function cargarEstadisticas() {
    try {
        const response = await fetch(`${BASE_API_URL}?action=get_stats`);
        const data = await response.json();

        if (data.ok && data.stats) {
            document.getElementById('kpi-total-processes').textContent = data.stats.total || 0;
            document.getElementById('kpi-active-processes').textContent = data.stats.activos || 0;
            document.getElementById('kpi-recurring-processes').textContent = data.stats.recurrentes || 0;
            document.getElementById('kpi-generated-tasks').textContent = data.stats.tareas_generadas || 0;
            console.log('✅ Estadísticas cargadas:', data.stats);
        }
    } catch (error) {
        console.error('❌ Error al cargar estadísticas:', error);
    }
}

// ========================================
// ESTADÍSTICAS DE TAREAS (específico para tab tasks)
// ========================================
async function cargarEstadisticasTareas() {
    try {
        const response = await fetch(`${BASE_API_URL}?action=get_stats_tasks`);
        const data = await response.json();

        if (data.ok && data.stats) {
            document.getElementById('kpi-total-tasks').textContent = data.stats.total || 0;
            document.getElementById('kpi-in-progress-tasks').textContent = data.stats.en_proceso || 0;
            document.getElementById('kpi-overdue-tasks').textContent = data.stats.vencidas || 0;
            document.getElementById('kpi-completed-tasks').textContent = data.stats.completadas || 0;
            console.log('✅ Estadísticas de tareas cargadas:', data.stats);
        }
    } catch (error) {
        console.error('❌ Error al cargar estadísticas de tareas:', error);
    }
}

// Hacer función global
window.cargarEstadisticasTareas = cargarEstadisticasTareas;

// ========================================
// HELPER: normalizeDate() - universal YYYY-MM-DD
// ========================================
function normalizeDate(d) {
    if (!d) return '';
    try {
        return d.split('T')[0];
    } catch {
        return d;
    }
}

// ========================================
// TAREAS: Cargar tareas/procesos (ENTERPRISE PATCH)
// ========================================
async function cargarTareas(filtros = {}, customTbody = null) {
    console.log('📥 [ENTERPRISE] Cargando tareas/procesos con filtros:', filtros);

    const tbody = customTbody
        || document.getElementById('tbody-tareas')
        || document.getElementById('tbody-procesos')
        || document.getElementById('tbody_tasks')
        || document.querySelector('#tablaTareas tbody')
        || document.querySelector('#tablaProcesos tbody')
        || document.querySelector('table tbody');

    if (!tbody) {
        console.error('❌ No se encontró tbody para cargar tareas');
        return;
    }

    // Mostrar loading
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
        const params = new URLSearchParams({
            action: 'list_tasks',
            ...filtros
        });

        const response = await fetch(`${API_BASE}?${params.toString()}`, {
            method: 'GET',
            headers: {
                'X-CSRF-Token': getCSRFToken(),
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const result = await response.json();

        console.log('═══════════════════════════════════════════════');
        console.log('📦 [DEBUG] RESPUESTA COMPLETA DEL API:');
        console.log(JSON.stringify(result, null, 2));
        console.log('═══════════════════════════════════════════════');
        console.log('📊 [DEBUG] result.ok:', result.ok);
        console.log('📊 [DEBUG] result.data existe?:', !!result.data);
        console.log('📊 [DEBUG] result.data es array?:', Array.isArray(result.data));
        console.log('📊 [DEBUG] Cantidad de registros:', result.data?.length);
        console.log('📊 [DEBUG] Primer registro:', result.data?.[0]);
        console.log('═══════════════════════════════════════════════');

        if (result.ok && result.data && Array.isArray(result.data)) {
            console.log('✅ [DEBUG] Llamando renderTareasTable con', result.data.length, 'registros');
            renderTareasTable(tbody, result.data);
        } else {
            console.warn('⚠️ [DEBUG] No hay datos válidos. Mostrando empty state');
            console.warn('⚠️ [DEBUG] result.ok:', result.ok);
            console.warn('⚠️ [DEBUG] result.data:', result.data);
            renderTareasTable(tbody, []);
        }
    } catch (error) {
        console.error('❌ Error al cargar tareas:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="12" class="text-center py-5 text-danger">
                    <i class="bi bi-exclamation-triangle" style="font-size: 3rem;"></i>
                    <p class="mt-3">Error al cargar datos: ${error.message}</p>
                </td>
            </tr>
        `;
    }
}

// ========================================
// RENDER: Renderizar tareas en tabla (ENTERPRISE PATCH)
// ========================================
function renderTareasTable(tbody, rows) {
    console.log('🎨 [ENTERPRISE] Renderizando tabla con', rows?.length || 0, 'filas');
    console.log('🔍 [DEBUG] rows recibido:', rows);
    console.log('🔍 [DEBUG] tbody:', tbody);
    console.log('🔍 [DEBUG] Array.isArray(rows):', Array.isArray(rows));

    const emptyState = document.getElementById('empty-state-tareas');
    const tableWrapper = tbody?.closest('.table-responsive');

    console.log('🔍 [DEBUG] emptyState encontrado:', !!emptyState);
    console.log('🔍 [DEBUG] tableWrapper encontrado:', !!tableWrapper);

    if (!tbody) {
        console.error('❌ tbody no proporcionado');
        return;
    }

    // Limpiar tbody
    tbody.innerHTML = '';
    console.log('✅ [DEBUG] tbody limpiado');

    console.log('🔍 [DEBUG] Verificando condición de datos vacíos...');
    console.log('🔍 [DEBUG] !rows:', !rows);
    console.log('🔍 [DEBUG] !Array.isArray(rows):', !Array.isArray(rows));
    console.log('🔍 [DEBUG] rows.length === 0:', rows?.length === 0);

    // Sin datos → mostrar empty state
    if (!rows || !Array.isArray(rows) || rows.length === 0) {
        console.warn('⚠️ [DEBUG] ENTRANDO A BLOQUE DE EMPTY STATE');
        console.warn('⚠️ [DEBUG] Mostrando empty state porque no hay datos');
        if (emptyState) {
            emptyState.style.display = 'block';
            console.log('✅ Empty state mostrado');
        }
        if (tableWrapper) {
            tableWrapper.style.display = 'none';
            console.log('✅ Tabla ocultada');
        }
        console.log('ℹ️ No hay datos - mostrando empty state');
        return;
    }

    console.log('🎉 [DEBUG] HAY DATOS! Procediendo a renderizar', rows.length, 'filas');

    // Hay datos → ocultar empty state
    if (emptyState) {
        emptyState.style.display = 'none';
        console.log('✅ Empty state ocultado');
    }
    if (tableWrapper) {
        tableWrapper.style.display = 'block';
        console.log('✅ Tabla mostrada');
    }

    const fragment = document.createDocumentFragment();
    console.log('📝 [DEBUG] Fragment creado, iniciando forEach...');

    try {
        rows.forEach((row, index) => {
            console.log(`📝 [DEBUG] Procesando fila ${index + 1}/${rows.length}`, row);

            // Normalizar ID universal
            const id = row.id || row.task_id || row.proceso_id || null;
            console.log(`  → ID normalizado: ${id}`);

            // Calcular si tiene archivos (seguro)
            const hasFiles = !!(row.archivos_count || (Array.isArray(row.archivos) && row.archivos.length));
            console.log(`  → Tiene archivos: ${hasFiles}`);

            // Map DB status to colors
            const statusColorMap = {
                'En tiempo': 'success',
                'En proceso': 'primary',
                'Terminada': 'info',
                'Vencida': 'danger',
                'Auditada': 'warning',
                'Pausada': 'secondary'
            };
            const statusColor = statusColorMap[row.status] || 'secondary';

            // Map DB nivel (priority) to colors
            const nivelColorMap = {
                'Normal': 'info',
                'Importante': 'warning',
                'Urgente': 'danger'
            };
            const nivelColor = nivelColorMap[row.nivel] || 'info';

            // Map tipo to colors
            const tipoColorMap = {
                'Tarea': 'primary',
                'Proceso': 'success',
                'Tarea de proceso': 'info'
            };
            const tipoColor = tipoColorMap[row.tipo] || 'secondary';

            const tr = document.createElement('tr');
            tr.dataset.id = id || '';

            tr.innerHTML = `
            <td data-column="unidad"><span class="fw-semibold">${row.unit_nombre || row.unit_name || row.unit || '-'}</span></td>
            <td data-column="negocio">${row.business_nombre || row.business_name || row.business || '-'}</td>
            <td data-column="descripcion">
                <span class="text-dark">${row.titulo || row.title || row.descripcion || row.description || '-'}</span>
                ${row.folio ? `<br><small class="text-muted">${row.folio}</small>` : ''}
            </td>
            <td data-column="inicia"><small class="text-muted">${normalizeDate(row.fecha_inicio || row.start_date) || '-'}</small></td>
            <td data-column="vence"><small class="text-muted">${normalizeDate(row.fecha_entrega || row.fecha_fin || row.delivery_date) || '-'}</small></td>
            <td data-column="status"><span class="badge bg-${statusColor} bg-opacity-25 text-${statusColor}">${row.status || 'En tiempo'}</span></td>
            <td data-column="archivo" class="text-center">
                ${hasFiles ? '<i class="bi bi-paperclip text-primary"></i>' : '<span class="text-muted">—</span>'}
            </td>
            <td data-column="creador"><small>${row.creador_nombre || row.creator_name || row.creador || '-'}</small></td>
            <td data-column="delegado"><small>${row.delegado_nombre ||
                row.assigned_name ||
                row.delegado ||
                row.usuario_delegado_nombre ||
                row.delegado_name ||
                '-'
                }</small></td>
            <td data-column="nivel"><span class="badge bg-${nivelColor} bg-opacity-25 text-${nivelColor}">${row.nivel || row.priority || 'Normal'}</span></td>
            <td data-column="tipo"><span class="badge bg-${tipoColor} bg-opacity-25 text-${tipoColor}">${row.tipo || 'Tarea'}</span></td>
            <td data-column="acciones" class="text-center">
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-light border" onclick="verDetalle(${id})" 
                            data-bs-toggle="tooltip" data-bs-placement="top" title="Ver detalles">
                        <i class="bi bi-eye text-primary"></i>
                    </button>
                    <button type="button" class="btn btn-light border" onclick="editarTarea(${id})" 
                            data-bs-toggle="tooltip" data-bs-placement="top" title="Editar">
                        <i class="bi bi-pencil text-warning"></i>
                    </button>
                    <button type="button" class="btn btn-light border" onclick="duplicarTarea(${id})" 
                            data-bs-toggle="tooltip" data-bs-placement="top" title="Duplicar">
                        <i class="bi bi-files text-info"></i>
                    </button>
                    <button type="button" class="btn btn-light border" onclick="auditarTarea(${id})" 
                            data-bs-toggle="tooltip" data-bs-placement="top" title="Marcar como auditada">
                        <i class="bi bi-check-circle text-success"></i>
                    </button>
                    <button type="button" class="btn btn-light border" onclick="eliminarTarea(${id})" 
                            data-bs-toggle="tooltip" data-bs-placement="top" title="Eliminar">
                        <i class="bi bi-trash text-danger"></i>
                    </button>
                </div>
            </td>
        `;

            fragment.appendChild(tr);
            console.log(`  ✅ Fila ${index + 1} agregada al fragment`);
        });

        console.log('📝 [DEBUG] forEach completado, agregando fragment al tbody...');
        tbody.appendChild(fragment);
        console.log(`✅ [ENTERPRISE] ${rows.length} filas renderizadas y agregadas al DOM`);

        // Reaplicar visibilidad de columnas según configuración guardada
        try {
            const saved = localStorage.getItem('tasks_columns_config');
            if (saved && window.TaskModule && typeof TaskModule.applyColumnVisibilityTareas === 'function') {
                const config = JSON.parse(saved);
                TaskModule.applyColumnVisibilityTareas(config);
                console.log('✅ [ENTERPRISE] Visibilidad de columnas reaplicada');
            }
        } catch (e) {
            console.error('❌ Error aplicando visibilidad de columnas:', e);
        }

        // Inicializar tooltips de Bootstrap 5
        setTimeout(() => {
            const tooltipTriggerList = tbody.querySelectorAll('[data-bs-toggle="tooltip"]');
            [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        }, 100);

        // VERIFICACIÓN FINAL Y FORZAR VISIBILIDAD
        console.log('🏁 [DEBUG] ===== ESTADO FINAL =====');
        console.log('  tbody.children.length:', tbody.children.length);
        console.log('  emptyState.style.display:', emptyState?.style.display);
        console.log('  tableWrapper.style.display:', tableWrapper?.style.display);
        console.log('  tableWrapper es visible?:', tableWrapper && window.getComputedStyle(tableWrapper).display !== 'none');

        // Verificar y forzar visibilidad de parents
        if (tableWrapper) {
            let element = tableWrapper;
            let level = 0;
            while (element && level < 5) {
                const computed = window.getComputedStyle(element);
                console.log(`  Parent ${level}:`, element.tagName, element.className, {
                    display: computed.display,
                    visibility: computed.visibility,
                    opacity: computed.opacity
                });

                // FORZAR visibilidad
                if (computed.display === 'none') {
                    console.warn(`  ⚠️ Parent ${level} estaba oculto, forzando display:block`);
                    element.style.display = 'block';
                }
                if (computed.visibility === 'hidden') {
                    console.warn(`  ⚠️ Parent ${level} estaba invisible, forzando visibility:visible`);
                    element.style.visibility = 'visible';
                }
                if (computed.opacity === '0') {
                    console.warn(`  ⚠️ Parent ${level} tenía opacity 0, forzando opacity:1`);
                    element.style.opacity = '1';
                }

                element = element.parentElement;
                level++;
            }
        }
        console.log('🏁 [DEBUG] ========================');

    } catch (error) {
        console.error('❌ [CRITICAL] Error durante el renderizado de filas:', error);
        console.error('Stack trace:', error.stack);
        tbody.innerHTML = `
            <tr>
                <td colspan="12" class="text-center py-5 text-danger">
                    <i class="bi bi-exclamation-triangle fs-1"></i>
                    <p class="mt-3 fw-bold">Error al renderizar las tareas</p>
                    <p class="small">${error.message}</p>
                </td>
            </tr>
        `;
    }
}

// Mantener función legacy para compatibilidad
function renderTareas(tareas, tbody) {
    console.warn('⚠️ renderTareas() legacy llamada - usando renderTareasTable()');
    renderTareasTable(tbody, tareas);
}

// ========================================
// FUNCIONES DE ACCIONES
// ========================================
function verDetalle(id) {
    console.log('Ver detalle:', id);
    showNotification('Función en desarrollo', 'info');
}

function editarTarea(id) {
    console.log('Editar tarea:', id);
    showNotification('Función en desarrollo', 'info');
}

function duplicarTarea(id) {
    if (!confirm('¿Duplicar esta tarea/proceso?')) return;
    console.log('Duplicar tarea:', id);
    showNotification('Función en desarrollo', 'info');
}

function auditarTarea(id) {
    if (!confirm('¿Marcar como auditada?')) return;
    console.log('Auditar tarea:', id);

    fetch(`${BASE_API_URL}?action=update_status`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status: 'Auditada' })
    })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                showNotification('Tarea auditada correctamente', 'success');
                cargarTareas();
            } else {
                showNotification(data.error || 'Error al auditar', 'error');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showNotification('Error de conexión', 'error');
        });
}

function eliminarTarea(id) {
    if (!confirm('¿Eliminar esta tarea? Esta acción no se puede deshacer.')) return;

    fetch(`${BASE_API_URL}?action=delete_task`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                showNotification('Tarea eliminada correctamente', 'success');
                cargarTareas();
            } else {
                showNotification(data.error || 'Error al eliminar', 'error');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showNotification('Error de conexión', 'error');
        });
}

// ========================================
// INICIALIZACIÓN
// ========================================
console.log('✅ tareas.js inicializado correctamente');
