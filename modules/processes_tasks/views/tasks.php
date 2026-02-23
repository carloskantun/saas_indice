<?php

/**
 * Vista de Tareas - Versión Limpia y Funcional
 * Sin dependencias de React, carga directa desde la base de datos
 */

// Asegurar que el token CSRF existe
if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!-- Header de sección (igual RH) -->
<div class="hr-header">
  <div class="hr-header-left">
    <h1>Tareas</h1>
    <p class="subtitle">Gestiona y organiza las tareas del equipo.</p>
  </div>
  <div class="hr-header-right">
    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalColumns_ptTasksTable" id="btnColumnsPT_ptTasksTable">
      <i class="bi bi-columns-gap"></i> Columnas
    </button>
    <button type="button" class="btn btn-primary" onclick="abrirModalCrearTarea()" aria-label="Agregar nueva tarea">
      <i class="bi bi-plus-circle"></i> Nueva tarea
    </button>
  </div>
</div>

<!-- Filtros (igual RH) -->
<div class="hr-filter-card reveal">
  <form class="row g-3 align-items-end" id="ptTasksFilters">
    <div class="col-12 col-md-6 col-xl-2">
      <label class="form-label">Unidad</label>
      <select name="unit_id" id="ptTasksUnit" class="form-select">
        <option value="">Todas</option>
      </select>
    </div>
    <div class="col-12 col-md-6 col-xl-2">
      <label class="form-label">Negocio</label>
      <select name="business_id" id="ptTasksBusiness" class="form-select">
        <option value="">Todos</option>
      </select>
    </div>
    <div class="col-12 col-md-6 col-xl-2">
      <label class="form-label">Responsable</label>
      <select name="assignee" id="ptTasksAssignee" class="form-select">
        <option value="">Todos</option>
      </select>
    </div>
    <div class="col-12 col-md-6 col-xl-2">
      <label class="form-label">Estado</label>
      <select name="status" id="ptTasksStatus" class="form-select">
        <option value="">Todos</option>
        <option value="En tiempo">En tiempo</option>
        <option value="En proceso">En proceso</option>
        <option value="Pausada">Pausada</option>
        <option value="Vencida">Vencida</option>
        <option value="Terminada">Terminada</option>
        <option value="Auditada">Auditada</option>
      </select>
    </div>
    <div class="col-12 col-md-6 col-xl-2 ms-xl-auto d-grid">
      <button type="submit" class="btn btn-primary" id="ptTasksApply">
        <i class="bi bi-funnel"></i> Aplicar filtros
      </button>
    </div>
  </form>
</div>

<!-- Modal selector de columnas (igual RH) -->
<div class="modal fade" id="modalColumns_ptTasksTable" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content hr-modal">
      <div class="modal-header hr-modal">
        <h5 class="modal-title hr-modal">Mostrar / Ocultar columnas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body hr-modal">
        <p class="text-muted mb-2">Elige qué columnas quieres ver en la tabla.</p>
        <div id="columnsChecklist_ptTasksTable" class="row g-2"><!-- se llena por JS --></div>
      </div>
      <div class="modal-footer hr-modal">
        <button class="btn btn-ghost" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-brand" id="saveColumns_ptTasksTable">Aplicar</button>
      </div>
    </div>
  </div>
</div>

<!-- Tabla (igual RH) -->
<div class="hr-table-wrapper reveal">
  <div class="ix-table-card">
    <div class="table-responsive">
      <table class="table table-hover align-middle ix-pt-table" id="ptTasksTable" aria-describedby="ptTasksTotals">
        <thead>
          <tr>
            <th scope="col" data-col-key="sel" class="text-center"><input type="checkbox" id="ptTasksSelectAll" aria-label="Seleccionar todos"></th>
            <th scope="col" data-col-key="folio">Folio</th>
            <th scope="col" data-col-key="title">Título</th>
            <th scope="col" data-col-key="desc">Descripción</th>
            <th scope="col" data-col-key="unit" class="text-center">Unidad</th>
            <th scope="col" data-col-key="biz" class="text-center">Negocio</th>
            <th scope="col" data-col-key="start">F. inicio</th>
            <th scope="col" data-col-key="due">F. entrega</th>
            <th scope="col" data-col-key="prio" class="text-center">Prioridad</th>
            <th scope="col" data-col-key="state" class="text-center">Estado</th>
            <th scope="col" data-col-key="actions" class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody id="ptTasksTbody">
          <tr>
            <td colspan="11" class="text-center py-5">
              <div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div>
              <p class="text-muted mt-3 mb-0">Cargando tareas...</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Token CSRF para AJAX -->
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

<!-- Modal de Crear Tarea -->
<div class="modal fade" id="modalCrearTarea" tabindex="-1" aria-labelledby="modalCrearTareaLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCrearTareaLabel">
          <i class="bi bi-plus-circle me-2"></i>Crear Nueva Tarea
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formCrearTarea">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label for="tarea_titulo" class="form-label">Título de la Tarea <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="tarea_titulo" name="title" required maxlength="255" placeholder="Ej: Revisar documentos de proyecto">
            </div>
            <div class="col-12">
              <label for="tarea_descripcion" class="form-label">Descripción</label>
              <textarea class="form-control" id="tarea_descripcion" name="desc" rows="3" maxlength="5000" placeholder="Describe la tarea y sus objetivos..."></textarea>
            </div>
            <div class="col-md-6">
              <label for="tarea_fecha_inicio" class="form-label">Fecha de Inicio</label>
              <input type="date" class="form-control" id="tarea_fecha_inicio" name="start">
            </div>
            <div class="col-md-6">
              <label for="tarea_fecha_entrega" class="form-label">Fecha de Vencimiento</label>
              <input type="date" class="form-control" id="tarea_fecha_entrega" name="due">
            </div>
            <div class="col-md-6">
              <label for="tarea_prioridad" class="form-label">Prioridad</label>
              <select class="form-select" id="tarea_prioridad" name="priority">
                <option value="Normal">Normal</option>
                <option value="Alta">Alta</option>
                <option value="Crítica">Crítica</option>
                <option value="Baja">Baja</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="tarea_status" class="form-label">Estado</label>
              <select class="form-select" id="tarea_status" name="status">
                <option value="En proceso">En proceso</option>
                <option value="En tiempo">En tiempo</option>
                <option value="Pausada">Pausada</option>
              </select>
            </div>
            <div class="col-12">
              <label for="tarea_assignee" class="form-label">Responsable</label>
              <select class="form-select" id="tarea_assignee" name="assignee">
                <option value="">Seleccionar responsable...</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-brand">
            <i class="bi bi-check-lg me-1"></i>Crear Tarea
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- JavaScript Limpio y Funcional -->
<script>
  (function() {
    'use strict';

    console.log('✅ Módulo de Tareas - Versión Limpia iniciando...');

    // Función para escapar HTML
    function esc(str) {
      if (str === null || str === undefined) return '';
      const div = document.createElement('div');
      div.textContent = String(str);
      return div.innerHTML;
    }

    // Función para formatear fechas
    function formatDate(dateStr) {
      if (!dateStr) return '-';
      try {
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
      } catch (e) {
        return dateStr;
      }
    }

    function getStatusBadge(status) {
      const value = (status || '').trim();
      const variant = (() => {
        switch (value) {
          case 'Terminada':
          case 'Auditada':
          case 'En tiempo':
            return 'ok';
          case 'En proceso':
            return 'info';
          case 'Pausada':
            return 'warn';
          case 'Vencida':
            return 'danger';
          default:
            return 'muted';
        }
      })();
      return `<span class="ix-pill ix-pill--${variant}">${esc(value || 'Desconocido')}</span>`;
    }

    function getPriorityPill(priority) {
      const value = (priority || '').trim();
      const variant = (() => {
        switch (value) {
          case 'Crítica':
          case 'Urgente':
            return 'danger';
          case 'Alta':
          case 'Importante':
            return 'warn';
          case 'Baja':
            return 'muted';
          case 'Normal':
          default:
            return 'info';
        }
      })();
      return `<span class="ix-pill ix-pill--${variant}">${esc(value || 'Normal')}</span>`;
    }

    // Cargar estadísticas
    async function cargarEstadisticas() {
      console.log('📊 Cargando estadísticas...');

      const pendingEl = document.getElementById('stat-pending-tasks');
      const overdueEl = document.getElementById('stat-overdue-tasks');
      const completedEl = document.getElementById('stat-completed-tasks');
      const totalEl = document.getElementById('stat-total-tasks');
      if (!pendingEl || !overdueEl || !completedEl || !totalEl) {
        return;
      }

      try {
        const response = await fetch('/modules/processes_tasks/api/stats.php?tipo=Tarea');
        const data = await response.json();

        if (data.ok) {
          const stats = data.stats || {};
          pendingEl.textContent = stats.in_progress || stats.pending || 0;
          overdueEl.textContent = stats.overdue || 0;
          completedEl.textContent = stats.completed || 0;
          totalEl.textContent = stats.total || 0;
          console.log('✅ Estadísticas cargadas:', stats);
        } else {
          throw new Error(data.error || 'Error desconocido');
        }
      } catch (error) {
        console.error('❌ Error al cargar estadísticas:', error);
        pendingEl.textContent = '0';
        overdueEl.textContent = '0';
        completedEl.textContent = '0';
        totalEl.textContent = '0';
      }
    }

    // Cargar tareas
    let currentTaskFilters = {};

    function readTaskFiltersFromUI() {
      return {
        unit_id: document.getElementById('ptTasksUnit')?.value || '',
        business_id: document.getElementById('ptTasksBusiness')?.value || '',
        assignee: document.getElementById('ptTasksAssignee')?.value || '',
        status: document.getElementById('ptTasksStatus')?.value || ''
      };
    }

    function populateSelectEl(selectEl, items, valueKey, labelKey) {
      if (!selectEl) return;
      const keepFirst = selectEl.querySelector('option')?.outerHTML || '';
      selectEl.innerHTML = keepFirst;
      (items || []).forEach((item) => {
        if (!item || item[valueKey] === undefined || item[valueKey] === null) return;
        const option = document.createElement('option');
        option.value = String(item[valueKey]);
        option.textContent = String(item[labelKey] ?? item[valueKey]);
        selectEl.appendChild(option);
      });
    }

    async function cargarCatalogosFiltros() {
      const unitEl = document.getElementById('ptTasksUnit');
      const businessEl = document.getElementById('ptTasksBusiness');
      const assigneeEl = document.getElementById('ptTasksAssignee');
      if (!unitEl || !businessEl || !assigneeEl) return;

      try {
        const response = await fetch('/modules/processes_tasks/api/form_data_simple.php', {
          method: 'GET',
          credentials: 'same-origin',
          headers: { 'Accept': 'application/json' }
        });
        if (!response.ok) return;
        const data = await response.json();
        if (!data.ok) return;
        populateSelectEl(unitEl, data.units, 'id', 'name');
        populateSelectEl(businessEl, data.businesses, 'id', 'name');
        populateSelectEl(assigneeEl, data.employees, 'id', 'name');
      } catch (e) {
        console.warn('⚠️ No se pudieron cargar catálogos de filtros:', e);
      }
    }

    async function cargarTareas(filtros = {}) {
      console.log('📥 Cargando tareas...', filtros);
      const tbody = document.getElementById('ptTasksTbody');
      const table = document.getElementById('ptTasksTable');

      if (!tbody) {
        console.error('❌ No se encontró el tbody');
        return;
      }

      // Mostrar spinner
      tbody.innerHTML = `
        <tr>
          <td colspan="11" class="text-center py-5">
            <div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div>
            <p class="text-muted mt-3 mb-0">Cargando tareas...</p>
          </td>
        </tr>
      `;

      try {
        const params = new URLSearchParams();
        params.append('tipo', 'Tarea');

        if (filtros.unit_id) params.append('unit_id', filtros.unit_id);
        if (filtros.business_id) params.append('business_id', filtros.business_id);
        if (filtros.assignee) params.append('assignee', filtros.assignee);
        if (filtros.status) params.append('status', filtros.status);

        const url = `/modules/processes_tasks/api/list.php?${params.toString()}`;
        console.log('🔗 URL:', url);

        const response = await fetch(url, {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json'
          }
        });

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        console.log('📦 Datos recibidos:', data);

        if (!data.ok) {
          throw new Error(data.error || 'Error desconocido');
        }

        const tasks = data.items || data.data || [];
        console.log(`✅ ${tasks.length} tareas cargadas`);

        if (tasks.length === 0) {
          tbody.innerHTML = `
            <tr>
              <td colspan="11" class="text-center py-5">
                <div class="text-muted">
                  <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                  <p class="mt-3 mb-0 fw-medium">No se encontraron tareas</p>
                  <p class="text-muted small">Ajusta los filtros o crea una nueva tarea</p>
                </div>
              </td>
            </tr>
          `;
          return;
        }

        const trunc = (text, max = 60) => {
          const t = String(text || '').trim();
          if (!t) return '-';
          return t.length > max ? `${t.slice(0, max)}…` : t;
        };

        tbody.innerHTML = tasks.map(task => `
          <tr>
          <td class="text-center"><input type="checkbox" class="pt-task-check" value="${esc(task.id)}" aria-label="Seleccionar tarea"></td>
          <td>${esc(task.folio || task.id || '-')}</td>
          <td class="fw-semibold">${esc(task.titulo || task.title || '-')}</td>
          <td class="text-muted">${esc(trunc(task.descripcion || task.description || '-', 70))}</td>
          <td class="text-center">${esc(task.unit_nombre || task.unit_name || '-')}</td>
          <td class="text-center">${esc(task.business_nombre || task.business_name || '-')}</td>
          <td>${formatDate(task.fecha_inicio || task.start_date)}</td>
          <td>${formatDate(task.fecha_entrega || task.due_date)}</td>
          <td class="text-center">${getPriorityPill(task.nivel)}</td>
          <td class="text-center">${getStatusBadge(task.status)}</td>
          <td class="text-end">
            <div class="ix-row-actions">
            <button type="button" class="btn btn-sm btn-icon ix-action-btn ix-action-btn--view" onclick="verTarea(${task.id})" title="Ver" aria-label="Ver"><i class="bi bi-eye"></i></button>
            <button type="button" class="btn btn-sm btn-icon ix-action-btn ix-action-btn--edit" onclick="editarTarea(${task.id})" title="Editar" aria-label="Editar"><i class="bi bi-pencil"></i></button>
            <button type="button" class="btn btn-sm btn-icon ix-action-btn ix-action-btn--dup" onclick="duplicarTarea(${task.id})" title="Duplicar" aria-label="Duplicar"><i class="bi bi-copy"></i></button>
            <button type="button" class="btn btn-sm btn-icon ix-action-btn ix-action-btn--danger" onclick="eliminarTarea(${task.id})" title="Eliminar" aria-label="Eliminar"><i class="bi bi-trash"></i></button>
            </div>
          </td>
          </tr>
        `).join('');

      } catch (error) {
        console.error('❌ Error al cargar tareas:', error);
        tbody.innerHTML = `
                <tr>
                    <td colspan="11" class="text-center py-5">
                        <div class="text-danger">
                            <i class="bi bi-exclamation-triangle fs-1 d-block mb-3"></i>
                            <p class="mb-0">Error al cargar tareas: ${esc(error.message)}</p>
                            <button class="btn btn-sm btn-brand mt-3" onclick="location.reload()">
                                <i class="bi bi-arrow-clockwise me-1"></i>Reintentar
                            </button>
                        </div>
                    </td>
                </tr>
            `;
      }
    }

    // Exponer para que el script de modales pueda reutilizar (manteniendo filtros actuales)
    window.cargarTareas = function(filtros) {
      const hasArgs = filtros && Object.keys(filtros).length > 0;
      return cargarTareas(hasArgs ? filtros : currentTaskFilters);
    };
    window.cargarEstadisticas = function() {
      return cargarEstadisticas();
    };

    // Función para duplicar tarea
    window.duplicarTarea = function(id) {
      if (confirm('¿Quieres crear una copia de esta tarea?')) {
        duplicarTareaAPI(id);
      }
    };

    // Función para duplicar tarea via API
    async function duplicarTareaAPI(id) {
      try {
        const formData = new FormData();
        formData.append('task_id', id);
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

        const response = await fetch('/modules/processes_tasks/api/duplicate.php', {
          method: 'POST',
          body: formData
        });

        const data = await response.json();

        if (data.ok) {
          alert('Tarea duplicada exitosamente');
          window.cargarTareas();
          window.cargarEstadisticas();
        } else {
          alert('Error al duplicar tarea: ' + (data.error || 'Error desconocido'));
        }
      } catch (error) {
        console.error('Error al duplicar tarea:', error);
        alert('Error de conexión al duplicar tarea');
      }
    }

    // Función para completar tarea
    window.completarTarea = function(id) {
      if (confirm('¿Marcar esta tarea como completada?')) {
        completarTareaAPI(id);
      }
    };

    // Función para completar tarea via API
    async function completarTareaAPI(id) {
      try {
        const formData = new FormData();
        formData.append('task_id', id);
        formData.append('status', 'Terminada');
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

        const response = await fetch('/modules/processes_tasks/api/update.php', {
          method: 'POST',
          body: formData
        });

        const data = await response.json();

        if (data.ok) {
          alert('Tarea completada exitosamente');
          window.cargarTareas();
          window.cargarEstadisticas();
        } else {
          alert('Error al completar tarea: ' + (data.error || 'Error desconocido'));
        }
      } catch (error) {
        console.error('Error al completar tarea:', error);
        alert('Error de conexión al completar tarea');
      }
    }

    // Inicialización (filtros solo al enviar el formulario)
    function initColumnsModal(tableId) {
      const modal = document.getElementById(`modalColumns_${tableId}`);
      const checklist = document.getElementById(`columnsChecklist_${tableId}`);
      const btnSave = document.getElementById(`saveColumns_${tableId}`);
      const table = document.getElementById(tableId);
      if (!modal || !checklist || !btnSave || !table) return;

      const STORAGE_KEY = `pt.${tableId}.columns.v1`;
      const hidden = new Set(JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'));

      const ths = [...table.querySelectorAll('thead th')];
      const cols = ths.map((th, idx) => ({
        key: th.getAttribute('data-col-key') || `c${idx}`,
        label: th.textContent.trim() || `Col ${idx + 1}`,
        index: idx
      }));

      const LOCKED = new Set(['sel', 'actions']);
      checklist.innerHTML = cols.map(c => {
        if (!c.key) return '';
        if (LOCKED.has(c.key)) return '';
        const checked = hidden.has(c.key) ? '' : 'checked';
        const inputId = `col_${tableId}_${c.key}`;
        return `<div class="col-6"><div class="form-check">
          <input class="form-check-input" type="checkbox" id="${inputId}" data-col-key="${c.key}" ${checked}>
          <label class="form-check-label" for="${inputId}">${esc(c.label)}</label>
        </div></div>`;
      }).join('');

      function applyVisibility() {
        const currentHidden = new Set(JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'));
        const idxToKey = cols.map(c => c.key);
        [...table.querySelectorAll('tr')].forEach(tr => {
          [...tr.children].forEach((cell, i) => {
            const key = idxToKey[i];
            if (!key) return;
            if (LOCKED.has(key)) { cell.style.display = ''; return; }
            cell.style.display = currentHidden.has(key) ? 'none' : '';
          });
        });
      }

      applyVisibility();

      btnSave.addEventListener('click', (e) => {
        e.preventDefault();
        const inputs = checklist.querySelectorAll('input[type="checkbox"][data-col-key]');
        const newHidden = [];
        inputs.forEach(inp => { if (!inp.checked) newHidden.push(inp.getAttribute('data-col-key')); });
        localStorage.setItem(STORAGE_KEY, JSON.stringify(newHidden));
        applyVisibility();
        try { bootstrap.Modal.getInstance(modal)?.hide(); } catch (_) {}
      });
    }

    async function init() {
      initColumnsModal('ptTasksTable');
      await cargarCatalogosFiltros();

      const filtersForm = document.getElementById('ptTasksFilters');
      if (filtersForm) {
        filtersForm.addEventListener('submit', function(e) {
          e.preventDefault();
          currentTaskFilters = readTaskFiltersFromUI();
          cargarTareas(currentTaskFilters);
        });
      }

      const selectAll = document.getElementById('ptTasksSelectAll');
      if (selectAll) {
        selectAll.addEventListener('change', function() {
          document.querySelectorAll('#ptTasksTbody .pt-task-check').forEach((cb) => {
            cb.checked = !!selectAll.checked;
          });
        });
      }

      currentTaskFilters = readTaskFiltersFromUI();
      await cargarEstadisticas();
      await cargarTareas(currentTaskFilters);
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
    } else {
      init();
    }

    // Acciones globales necesarias desde la tabla
    window.eliminarTarea = function(id) {
      if (confirm('¿Estás seguro de que quieres eliminar esta tarea?')) {
        eliminarTareaAPI(id);
      }
    };

    // Función para eliminar tarea via API
    async function eliminarTareaAPI(id) {
      try {
        const formData = new FormData();
        formData.append('task_id', id);
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

        const response = await fetch('/modules/processes_tasks/api/delete.php', {
          method: 'POST',
          body: formData
        });

        const data = await response.json();

        if (data.ok) {
          alert('Tarea eliminada exitosamente');
          cargarTareas(currentTaskFilters);
          cargarEstadisticas();
        } else {
          alert('Error al eliminar tarea: ' + (data.error || 'Error desconocido'));
        }
      } catch (error) {
        console.error('Error al eliminar tarea:', error);
        alert('Error de conexión al eliminar tarea');
      }
    }

    console.log('✅ Módulo de Tareas - Versión Limpia inicializado');
  })();
</script>

<!-- Modal de Crear Tarea -->
<div class="modal fade" id="modalCrearTarea" tabindex="-1" aria-labelledby="modalCrearTareaLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCrearTareaLabel">
          <i class="bi bi-plus-circle me-2 text-warning" aria-hidden="true"></i>Crear Nueva Tarea
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formCrearTarea">
        <div class="modal-body">
          <div class="row g-4">
            <!-- Información Básica -->
            <div class="col-12">
              <h6 class="fw-semibold mb-3" style="color: #374151;">
                <i class="bi bi-info-circle me-2"></i>Información Básica
              </h6>
            </div>
            
            <div class="col-md-8">
              <label class="form-label fw-medium">Título de la tarea <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="title" required placeholder="Ej: Revisar inventario de productos">
            </div>
            
            <div class="col-md-4">
              <label class="form-label fw-medium">Prioridad</label>
              <select class="form-select" name="priority">
                <option value="Normal">Normal</option>
                <option value="Importante">Importante</option>
                <option value="Urgente">Urgente</option>
              </select>
            </div>
            
            <div class="col-12">
              <label class="form-label fw-medium">Descripción</label>
              <textarea class="form-control" name="description" rows="3" placeholder="Describe los detalles de la tarea..."></textarea>
            </div>

            <!-- Fechas y Asignación -->
            <div class="col-12">
              <h6 class="fw-semibold mb-3 mt-4" style="color: #374151;">
                <i class="bi bi-calendar me-2"></i>Fechas y Asignación
              </h6>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-medium">Fecha de inicio</label>
              <input type="date" class="form-control" name="start_date" value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label fw-medium">Fecha de entrega</label>
              <input type="date" class="form-control" name="due_date">
            </div>

            <div class="col-md-4">
              <label class="form-label fw-medium">Estado inicial</label>
              <select class="form-select" name="status">
                <option value="En tiempo">En tiempo</option>
                <option value="En proceso">En proceso</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium">Responsable</label>
              <select class="form-select" name="assignee_id" id="selectAsignado">
                <option value="">Seleccionar responsable...</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium">Unidad de negocio</label>
              <select class="form-select" name="unit_id" id="selectUnidad">
                <option value="">Seleccionar unidad...</option>
              </select>
            </div>

            <!-- Organización -->
            <div class="col-12">
              <h6 class="fw-semibold mb-3 mt-4" style="color: #374151;">
                <i class="bi bi-diagram-3 me-2"></i>Organización
              </h6>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium">Negocio</label>
              <select class="form-select" name="business_id" id="selectNegocio">
                <option value="">Seleccionar negocio...</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium">Proyecto asociado</label>
              <input type="text" class="form-control" name="project" placeholder="Nombre del proyecto (opcional)">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-brand">
            <i class="bi bi-check me-1"></i>Crear Tarea
          </button>
        </div>
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
      </form>
    </div>
  </div>
</div>

<!-- Modal de Editar Tarea -->
<div class="modal fade" id="modalEditarTarea" tabindex="-1" aria-labelledby="modalEditarTareaLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditarTareaLabel">
          <i class="bi bi-pencil me-2 text-warning" aria-hidden="true"></i>Editar Tarea
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditarTarea">
        <div class="modal-body">
          <div class="row g-4">
            <!-- Información Básica -->
            <div class="col-12">
              <h6 class="fw-semibold mb-3" style="color: #374151;">
                <i class="bi bi-info-circle me-2"></i>Información Básica
              </h6>
            </div>
            
            <div class="col-md-8">
              <label class="form-label fw-medium">Título de la tarea <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="title" required placeholder="Ej: Revisar inventario de productos">
            </div>
            
            <div class="col-md-4">
              <label class="form-label fw-medium">Prioridad</label>
              <select class="form-select" name="priority">
                <option value="Normal">Normal</option>
                <option value="Importante">Importante</option>
                <option value="Urgente">Urgente</option>
              </select>
            </div>
            
            <div class="col-12">
              <label class="form-label fw-medium">Descripción</label>
              <textarea class="form-control" name="description" rows="3" placeholder="Describe los detalles de la tarea..."></textarea>
            </div>

            <!-- Fechas y Asignación -->
            <div class="col-12">
              <h6 class="fw-semibold mb-3 mt-4" style="color: #374151;">
                <i class="bi bi-calendar me-2"></i>Fechas y Asignación
              </h6>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-medium">Fecha de inicio</label>
              <input type="date" class="form-control" name="start_date">
            </div>

            <div class="col-md-4">
              <label class="form-label fw-medium">Fecha de entrega</label>
              <input type="date" class="form-control" name="due_date">
            </div>

            <div class="col-md-4">
              <label class="form-label fw-medium">Estado</label>
              <select class="form-select" name="status">
                <option value="En tiempo">En tiempo</option>
                <option value="En proceso">En proceso</option>
                <option value="Terminada">Terminada</option>
                <option value="Pausada">Pausada</option>
                <option value="Vencida">Vencida</option>
                <option value="Auditada">Auditada</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium">Responsable</label>
              <select class="form-select" name="assignee_id" id="selectAsignadoEdit">
                <option value="">Seleccionar responsable...</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium">Unidad de negocio</label>
              <select class="form-select" name="unit_id" id="selectUnidadEdit">
                <option value="">Seleccionar unidad...</option>
              </select>
            </div>

            <!-- Organización -->
            <div class="col-12">
              <h6 class="fw-semibold mb-3 mt-4" style="color: #374151;">
                <i class="bi bi-diagram-3 me-2"></i>Organización
              </h6>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium">Negocio</label>
              <select class="form-select" name="business_id" id="selectNegocioEdit">
                <option value="">Seleccionar negocio...</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium">Proyecto asociado</label>
              <input type="text" class="form-control" name="project" placeholder="Nombre del proyecto (opcional)">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-brand">
            <i class="bi bi-check me-1"></i>Guardar Cambios
          </button>
        </div>
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="task_id">
      </form>
    </div>
  </div>
</div>

<!-- Modal de Ver Detalles -->
<div class="modal fade" id="modalVerTarea" tabindex="-1" aria-labelledby="modalVerTareaLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalVerTareaLabel">
          <i class="bi bi-eye me-2 text-warning" aria-hidden="true"></i>Detalles de la Tarea
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="contenidoVerTarea">
          <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-brand" onclick="editarTareaDesdeModal()">
          <i class="bi bi-pencil me-1"></i>Editar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Token CSRF para AJAX -->
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

<!-- Script para funcionalidad de modales -->
<script>
(function() {
  'use strict';

  let currentEditingTaskId = null;

  // Cargar datos para selects
  async function cargarDatosSelects() {
    try {
      console.log('🔄 Iniciando carga de datos para selects...');
      
      // TEST: Verificar datos HR directamente
      try {
        console.log('🧪 Probando conexión HR...');
        const testResponse = await fetch('/modules/processes_tasks/api/test_hr.php');
        const testData = await testResponse.json();
        console.log('🧪 DATOS HR DIRECTOS:', testData);
      } catch (e) {
        console.warn('⚠️ Error en test HR:', e);
      }
      
      // Cargar todos los datos necesarios desde el nuevo endpoint
      const response = await fetch('/modules/processes_tasks/api/form_data_simple.php');
      console.log('📡 Response status:', response.status);
      
      if (!response.ok) {
        throw new Error(`HTTP Error: ${response.status}`);
      }
      
      const data = await response.json();
      console.log('📦 Data received:', data);
      console.log('🐛 Debug info from server:', data.debug);
      
      if (data.ok) {
        // Poblar selects de empleados
        if (data.employees && data.employees.length > 0) {
          console.log(`👥 Poblando empleados: ${data.employees.length} items`);
          populateSelect('selectAsignado', data.employees, 'id', 'name');
          populateSelect('selectAsignadoEdit', data.employees, 'id', 'name');
          populateSelect('tarea_assignee', data.employees, 'id', 'name'); // Para el modal de crear tarea
        } else {
          console.warn('⚠️ No hay empleados en la respuesta');
        }
        
        // Poblar selects de unidades
        if (data.units && data.units.length > 0) {
          console.log(`🏢 Poblando unidades: ${data.units.length} items`);
          populateSelect('selectUnidad', data.units, 'id', 'name');
          populateSelect('selectUnidadEdit', data.units, 'id', 'name');
        } else {
          console.warn('⚠️ No hay unidades en la respuesta');
        }
        
        // Poblar selects de negocios
        if (data.businesses && data.businesses.length > 0) {
          console.log(`🏪 Poblando negocios: ${data.businesses.length} items`);
          populateSelect('selectNegocio', data.businesses, 'id', 'name');
          populateSelect('selectNegocioEdit', data.businesses, 'id', 'name');
        } else {
          console.warn('⚠️ No hay negocios en la respuesta');
        }
        
        console.log('✅ Datos de formulario cargados exitosamente');
      } else {
        throw new Error(data.error || 'Error cargando datos');
      }
    } catch (error) {
      console.error('❌ Error cargando datos para selects:', error);
      // Fallback: intentar endpoints individuales
      await cargarDatosSelectsFallback();
    }
  }

  // Función de respaldo para cargar datos
  async function cargarDatosSelectsFallback() {
    try {
      console.log('🔄 Intentando cargar datos con método de respaldo...');
      
      // Cargar empleados desde HR
      try {
        const usersResponse = await fetch('/modules/processes_tasks/api/hr_users.php');
        const usersData = await usersResponse.json();
        if (usersData.ok) {
          populateSelect('selectAsignado', usersData.items, 'id', 'name');
          populateSelect('selectAsignadoEdit', usersData.items, 'id', 'name');
          populateSelect('tarea_assignee', usersData.items, 'id', 'name'); // Para el modal de crear tarea
        }
      } catch (e) {
        console.warn('No se pudieron cargar empleados:', e.message);
      }

      // Cargar unidades
      try {
        const unitsResponse = await fetch('/api/units.php');
        const unitsData = await unitsResponse.json();
        if (unitsData.ok) {
          populateSelect('selectUnidad', unitsData.items || unitsData.data || [], 'id', 'name');
          populateSelect('selectUnidadEdit', unitsData.items || unitsData.data || [], 'id', 'name');
        }
      } catch (e) {
        console.warn('No se pudieron cargar unidades:', e.message);
      }

      // Cargar negocios
      try {
        const businessResponse = await fetch('/api/businesses.php');
        const businessData = await businessResponse.json();
        if (businessData.ok) {
          populateSelect('selectNegocio', businessData.items || businessData.data || [], 'id', 'name');
          populateSelect('selectNegocioEdit', businessData.items || businessData.data || [], 'id', 'name');
        }
      } catch (e) {
        console.warn('No se pudieron cargar negocios:', e.message);
      }
    } catch (error) {
      console.error('❌ Error en carga de respaldo:', error);
    }
  }

  // Función auxiliar para poblar selects
  function populateSelect(selectId, items, valueField, textField) {
    const select = document.getElementById(selectId);
    if (!select) {
      console.error(`❌ No se encontró el select con ID: ${selectId}`);
      return;
    }

    // Limpiar opciones existentes excepto la primera (placeholder)
    while (select.children.length > 1) {
      select.removeChild(select.lastChild);
    }

    if (!items || !Array.isArray(items)) {
      console.warn(`⚠️ Items no válidos para select ${selectId}:`, items);
      return;
    }

    items.forEach((item) => {
      if (!item || !item[valueField]) {
        return;
      }
      
      const option = document.createElement('option');
      option.value = item[valueField];
      option.textContent = item[textField] || `Item ${item[valueField]}`;
      select.appendChild(option);
    });
    
    console.log(`✅ Select ${selectId} poblado con ${items.length} opciones`);
  }

  // Mostrar modal de crear tarea
  window.abrirModalCrearTarea = function() {
    cargarDatosSelects();
    const modal = new bootstrap.Modal(document.getElementById('modalCrearTarea'));
    modal.show();
  };

  // Manejar envío del formulario de crear tarea
  document.getElementById('formCrearTarea').addEventListener('submit', async function(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    try {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Creando...';

      const response = await fetch('/modules/processes_tasks/api/create.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (result.ok) {
        // Cerrar modal
        bootstrap.Modal.getInstance(document.getElementById('modalCrearTarea')).hide();
        
        // Limpiar formulario
        form.reset();
        
        // Recargar lista y estadísticas
        cargarTareas();
        cargarEstadisticas();
        
        // Mostrar mensaje de éxito
        alert('Tarea creada exitosamente');
      } else {
        alert('Error al crear tarea: ' + (result.error || 'Error desconocido'));
      }
    } catch (error) {
      console.error('Error:', error);
      alert('Error de conexión al crear tarea');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  });

  // Mostrar modal de editar tarea
  window.editarTarea = function(id) {
    currentEditingTaskId = id;
    cargarDatosSelects();
    cargarDatosTarea(id);
    const modal = new bootstrap.Modal(document.getElementById('modalEditarTarea'));
    modal.show();
  };

  // Cargar datos de una tarea específica para edición
  async function cargarDatosTarea(taskId) {
    try {
      const response = await fetch(`/modules/processes_tasks/api/list.php?task_id=${taskId}`);
      const data = await response.json();

      if (data.ok && data.items && data.items.length > 0) {
        const task = data.items[0];
        const form = document.getElementById('formEditarTarea');

        // Poblar campos del formulario
        form.querySelector('input[name="title"]').value = task.titulo || '';
        form.querySelector('textarea[name="description"]').value = task.descripcion || '';
        form.querySelector('select[name="priority"]').value = task.nivel || 'Normal';
        form.querySelector('select[name="status"]').value = task.status || 'En tiempo';
        form.querySelector('input[name="start_date"]').value = task.fecha_inicio || '';
        form.querySelector('input[name="due_date"]').value = task.fecha_entrega || '';
        form.querySelector('select[name="assignee_id"]').value = task.usuario_delegado || '';
        form.querySelector('select[name="unit_id"]').value = task.unit_id || '';
        form.querySelector('select[name="business_id"]').value = task.business_id || '';
        form.querySelector('input[name="project"]').value = task.proyecto_asignado || '';
        form.querySelector('input[name="task_id"]').value = taskId;
      }
    } catch (error) {
      console.error('Error cargando datos de tarea:', error);
      alert('Error al cargar datos de la tarea');
    }
  }

  // Manejar envío del formulario de editar tarea
  document.getElementById('formEditarTarea').addEventListener('submit', async function(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    try {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

      const response = await fetch('/modules/processes_tasks/api/update.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (result.ok) {
        // Cerrar modal
        bootstrap.Modal.getInstance(document.getElementById('modalEditarTarea')).hide();
        
        // Recargar lista y estadísticas
        cargarTareas();
        cargarEstadisticas();
        
        // Mostrar mensaje de éxito
        alert('Tarea actualizada exitosamente');
      } else {
        alert('Error al actualizar tarea: ' + (result.error || 'Error desconocido'));
      }
    } catch (error) {
      console.error('Error:', error);
      alert('Error de conexión al actualizar tarea');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  });

  // Ver detalles de tarea
  window.verTarea = function(id) {
    cargarDetallesTarea(id);
    const modal = new bootstrap.Modal(document.getElementById('modalVerTarea'));
    modal.show();
  };

  // Cargar detalles de tarea para vista
  async function cargarDetallesTarea(taskId) {
    try {
      const response = await fetch(`/modules/processes_tasks/api/list.php?task_id=${taskId}`);
      const data = await response.json();

      if (data.ok && data.items && data.items.length > 0) {
        const task = data.items[0];
        const contenido = document.getElementById('contenidoVerTarea');
        
        contenido.innerHTML = `
          <div class="row g-3">
            <div class="col-12">
              <h6 class="fw-bold">${esc(task.titulo || 'Sin título')}</h6>
              <p class="text-muted">${esc(task.descripcion || 'Sin descripción')}</p>
            </div>
            <div class="col-md-6">
              <strong>Folio:</strong> ${esc(task.folio || '-')}
            </div>
            <div class="col-md-6">
              <strong>Estado:</strong> ${getStatusBadge(task.status)}
            </div>
            <div class="col-md-6">
              <strong>Prioridad:</strong> <span class="badge bg-${task.nivel === 'Urgente' ? 'danger' : task.nivel === 'Importante' ? 'warning' : 'secondary'}">${esc(task.nivel || 'Normal')}</span>
            </div>
            <div class="col-md-6">
              <strong>Tipo:</strong> ${esc(task.tipo || 'Tarea')}
            </div>
            <div class="col-md-6">
              <strong>Fecha inicio:</strong> ${formatDate(task.fecha_inicio)}
            </div>
            <div class="col-md-6">
              <strong>Fecha entrega:</strong> ${formatDate(task.fecha_entrega)}
            </div>
            <div class="col-md-6">
              <strong>Asignado a:</strong> ${esc(task.delegado_nombre || '-')}
            </div>
            <div class="col-md-6">
              <strong>Unidad:</strong> ${esc(task.unit_nombre || '-')}
            </div>
            <div class="col-md-6">
              <strong>Negocio:</strong> ${esc(task.business_nombre || '-')}
            </div>
            <div class="col-md-6">
              <strong>Proyecto:</strong> ${esc(task.proyecto_asignado || '-')}
            </div>
          </div>
        `;

        currentEditingTaskId = taskId;
      }
    } catch (error) {
      console.error('Error cargando detalles:', error);
      document.getElementById('contenidoVerTarea').innerHTML = 
        '<div class="alert alert-danger">Error al cargar los detalles de la tarea</div>';
    }
  }

  // Editar tarea desde modal de vista
  window.editarTareaDesdeModal = function() {
    if (currentEditingTaskId) {
      bootstrap.Modal.getInstance(document.getElementById('modalVerTarea')).hide();
      editarTarea(currentEditingTaskId);
    }
  };

})();
</script>
