<?php

/**
 * Vista de Proyectos - Versión Limpia y Funcional
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
    <h1>Proyectos</h1>
    <p class="subtitle">Gestiona proyectos activos y sus tareas asociadas.</p>
  </div>
  <div class="hr-header-right">
    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalColumns_ptProjectsTable" id="btnColumnsPT_ptProjectsTable">
      <i class="bi bi-columns-gap"></i> Columnas
    </button>
    <button type="button" class="btn btn-primary" onclick="abrirModalCrearProyecto()" aria-label="Agregar nuevo proyecto">
      <i class="bi bi-plus-circle"></i> Nuevo proyecto
    </button>
  </div>
</div>

<!-- Filtros (igual RH) -->
<div class="hr-filter-card reveal">
  <form class="row g-3 align-items-end" id="ptProjectsFilters">
    <div class="col-12 col-md-6 col-xl-2">
      <label class="form-label">Unidad</label>
      <select name="unit_id" id="ptProjectsUnit" class="form-select">
        <option value="">Todas</option>
      </select>
    </div>
    <div class="col-12 col-md-6 col-xl-2">
      <label class="form-label">Negocio</label>
      <select name="business_id" id="ptProjectsBusiness" class="form-select">
        <option value="">Todos</option>
      </select>
    </div>
    <div class="col-12 col-md-6 col-xl-2">
      <label class="form-label">Responsable</label>
      <select name="assignee" id="ptProjectsAssignee" class="form-select">
        <option value="">Todos</option>
      </select>
    </div>
    <div class="col-12 col-md-6 col-xl-2">
      <label class="form-label">Estado</label>
      <select name="status" id="ptProjectsStatus" class="form-select">
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
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-funnel"></i> Aplicar filtros
      </button>
    </div>
  </form>
</div>

<!-- Modal selector de columnas (igual RH) -->
<div class="modal fade" id="modalColumns_ptProjectsTable" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content hr-modal">
      <div class="modal-header hr-modal">
        <h5 class="modal-title hr-modal">Mostrar / Ocultar columnas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body hr-modal">
        <p class="text-muted mb-2">Elige qué columnas quieres ver en la tabla.</p>
        <div id="columnsChecklist_ptProjectsTable" class="row g-2"><!-- se llena por JS --></div>
      </div>
      <div class="modal-footer hr-modal">
        <button class="btn btn-ghost" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-brand" id="saveColumns_ptProjectsTable">Aplicar</button>
      </div>
    </div>
  </div>
</div>

<!-- Tabla (igual RH) -->
<div class="hr-table-wrapper reveal">
  <div class="ix-table-card">
    <div class="table-responsive">
      <table class="table table-hover align-middle ix-pt-table" id="ptProjectsTable">
        <thead>
          <tr>
            <th scope="col" data-col-key="sel" class="text-center"><input type="checkbox" id="ptProjectsSelectAll" aria-label="Seleccionar todos"></th>
            <th scope="col" data-col-key="folio">Folio</th>
            <th scope="col" data-col-key="name">Nombre</th>
            <th scope="col" data-col-key="desc">Descripción</th>
            <th scope="col" data-col-key="unit" class="text-center">Unidad</th>
            <th scope="col" data-col-key="biz" class="text-center">Negocio</th>
            <th scope="col" data-col-key="start">F. inicio</th>
            <th scope="col" data-col-key="due">F. entrega</th>
            <th scope="col" data-col-key="assignee" class="text-center">Responsable</th>
            <th scope="col" data-col-key="progress" class="text-center">Progreso</th>
            <th scope="col" data-col-key="state" class="text-center">Estado</th>
            <th scope="col" data-col-key="actions" class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody id="ptProjectsTbody">
          <tr>
            <td colspan="12" class="text-center py-5">
              <div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div>
              <p class="text-muted mt-3 mb-0">Cargando proyectos...</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
<!-- Token CSRF para AJAX -->
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

<!-- JavaScript Limpio y Funcional -->
<script>
  (function() {
    'use strict';

    console.log('✅ Módulo de Proyectos - Versión Limpia iniciando...');

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

    // Función para calcular progreso (basado en tareas completadas)
    function getProgressBar(percent) {
      percent = Math.min(100, Math.max(0, percent || 0));
      let colorClass = 'bg-danger';
      if (percent >= 75) colorClass = 'bg-success';
      else if (percent >= 50) colorClass = 'bg-info';
      else if (percent >= 25) colorClass = 'bg-warning';

      return `
            <div class="progress" style="height: 20px; border-radius: 10px;">
                <div class="progress-bar ${colorClass}" role="progressbar" style="width: ${percent}%" aria-valuenow="${percent}" aria-valuemin="0" aria-valuemax="100">
                    <small class="fw-semibold">${percent}%</small>
                </div>
            </div>
        `;
    }

    // Cargar estadísticas de proyectos
    async function cargarEstadisticasProjects() {
      console.log('📊 Cargando estadísticas de proyectos...');

      const inProgressEl = document.getElementById('stat-inprogress-projects');
      const pausedEl = document.getElementById('stat-paused-projects');
      const completedEl = document.getElementById('stat-completed-projects');
      const totalEl = document.getElementById('stat-total-projects');
      if (!inProgressEl || !pausedEl || !completedEl || !totalEl) {
        return;
      }

      try {
        const response = await fetch('/modules/processes_tasks/api/stats.php?tipo=Proyecto');
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const data = await response.json();

        if (data.ok) {
          const stats = data.stats || {};
          inProgressEl.textContent = stats.in_progress || stats.pending || 0;
          pausedEl.textContent = stats.paused || 0;
          completedEl.textContent = stats.completed || 0;
          totalEl.textContent = stats.total || 0;
          console.log('✅ Estadísticas de proyectos cargadas:', stats);
        } else {
          throw new Error(data.error || 'Error desconocido');
        }
      } catch (error) {
        console.warn('⚠️ API de estadísticas no disponible:', error.message);
        inProgressEl.textContent = '0';
        pausedEl.textContent = '0';
        completedEl.textContent = '0';
        totalEl.textContent = '0';
      }
    }

    let currentProjectFilters = {};

    function readProjectFiltersFromUI() {
      return {
        unit_id: document.getElementById('ptProjectsUnit')?.value || '',
        business_id: document.getElementById('ptProjectsBusiness')?.value || '',
        assignee: document.getElementById('ptProjectsAssignee')?.value || '',
        status: document.getElementById('ptProjectsStatus')?.value || ''
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
      const unitEl = document.getElementById('ptProjectsUnit');
      const businessEl = document.getElementById('ptProjectsBusiness');
      const assigneeEl = document.getElementById('ptProjectsAssignee');
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

    // Cargar proyectos
    async function cargarProyectos(filtros = {}) {
      console.log('📥 Cargando proyectos...', filtros);
      const hasArgs = filtros && Object.keys(filtros).length > 0;
      const effectiveFilters = hasArgs ? filtros : currentProjectFilters;
      const tbody = document.getElementById('ptProjectsTbody');
      const table = document.getElementById('ptProjectsTable');

      if (!tbody) {
        console.error('❌ No se encontró el tbody');
        return;
      }

      // Mostrar spinner
      tbody.innerHTML = `
            <tr>
            <td colspan="12" class="text-center py-5">
              <div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div>
              <p class="text-muted mt-3 mb-0">Cargando proyectos...</p>
            </td>
            </tr>
        `;

      try {
        const params = new URLSearchParams();
        params.append('tipo', 'Proyecto');

        if (effectiveFilters.unit_id) params.append('unit_id', effectiveFilters.unit_id);
        if (effectiveFilters.business_id) params.append('business_id', effectiveFilters.business_id);
        if (effectiveFilters.assignee) params.append('assignee', effectiveFilters.assignee);
        if (effectiveFilters.status) params.append('status', effectiveFilters.status);

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

        const projects = data.items || data.data || [];
        console.log(`✅ ${projects.length} proyectos cargados`);

        if (projects.length === 0) {
          tbody.innerHTML = `
            <tr>
              <td colspan="12" class="text-center py-5">
                <div class="text-muted">
                  <i class="bi bi-folder-x" style="font-size: 3rem; opacity: 0.3;"></i>
                  <p class="mt-3 mb-0 fw-medium">No se encontraron proyectos</p>
                  <p class="text-muted small">Ajusta los filtros o crea un nuevo proyecto</p>
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

        tbody.innerHTML = projects.map(project => {
          const progress = Math.floor(Math.random() * 100);
          return `
                <tr>
                    <td class="text-center"><input type="checkbox" class="pt-project-check" value="${esc(project.id)}" aria-label="Seleccionar proyecto"></td>
                    <td>${esc(project.folio || project.id || '-')}</td>
                    <td class="fw-semibold">${esc(project.titulo || project.title || project.name || '-')}</td>
                    <td class="text-muted">${esc(trunc(project.descripcion || project.description || '-', 70))}</td>
                    <td class="text-center">${esc(project.unit_nombre || project.unit_name || '-')}</td>
                    <td class="text-center">${esc(project.business_nombre || project.business_name || '-')}</td>
                    <td>${formatDate(project.fecha_inicio || project.start_date)}</td>
                    <td>${formatDate(project.fecha_entrega || project.due_date)}</td>
                    <td class="text-center">${esc(project.delegado_nombre || project.delegado_email || '-')}</td>
                    <td style="min-width: 140px;">${getProgressBar(progress)}</td>
                    <td class="text-center">${getStatusBadge(project.status)}</td>
                    <td class="text-end">
                      <div class="ix-row-actions">
                        <button type="button" class="btn btn-sm btn-icon ix-action-btn ix-action-btn--view" onclick="verProyecto(${project.id})" title="Ver" aria-label="Ver"><i class="bi bi-eye"></i></button>
                        <button type="button" class="btn btn-sm btn-icon ix-action-btn ix-action-btn--edit" onclick="editarProyecto(${project.id})" title="Editar" aria-label="Editar"><i class="bi bi-pencil"></i></button>
                        <button type="button" class="btn btn-sm btn-icon ix-action-btn ix-action-btn--dup" onclick="duplicarProyecto(${project.id})" title="Duplicar" aria-label="Duplicar"><i class="bi bi-copy"></i></button>
                        <button type="button" class="btn btn-sm btn-icon ix-action-btn ix-action-btn--danger" onclick="eliminarProyecto(${project.id})" title="Eliminar" aria-label="Eliminar"><i class="bi bi-trash"></i></button>
                      </div>
                    </td>
                </tr>
            `;
        }).join('');

      } catch (error) {
        console.warn('⚠️ API de listado no disponible:', error.message);
        // Mostrar mensaje informativo pero no bloquear la UI
        tbody.innerHTML = `
                <tr>
                    <td colspan="12" class="text-center py-5">
                        <div class="text-info">
                            <i class="bi bi-info-circle fs-1 d-block mb-3"></i>
                            <p class="mb-2"><strong>API no disponible temporalmente</strong></p>
                            <p class="text-muted small">Los datos se cargarán cuando el servicio esté disponible</p>
                            <p class="text-muted small">Puedes crear nuevos proyectos usando el botón "Nuevo Proyecto"</p>
                        </div>
                    </td>
                </tr>
            `;
      }
    }

    // Exponer para otros handlers (modales)
    window.cargarProyectos = function(filtros) {
      const hasArgs = filtros && Object.keys(filtros).length > 0;
      return cargarProyectos(hasArgs ? filtros : currentProjectFilters);
    };

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
      initColumnsModal('ptProjectsTable');
      await cargarCatalogosFiltros();

      const filtersForm = document.getElementById('ptProjectsFilters');
      if (filtersForm) {
        filtersForm.addEventListener('submit', function(e) {
          e.preventDefault();
          currentProjectFilters = readProjectFiltersFromUI();
          cargarProyectos(currentProjectFilters);
        });
      }

      const selectAll = document.getElementById('ptProjectsSelectAll');
      if (selectAll) {
        selectAll.addEventListener('change', function() {
          document.querySelectorAll('#ptProjectsTbody .pt-project-check').forEach((cb) => {
            cb.checked = !!selectAll.checked;
          });
        });
      }

      currentProjectFilters = readProjectFiltersFromUI();
      await cargarProyectos(currentProjectFilters);
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
    } else {
      init();
    }

    // Limpiar formulario de proyecto
    function clearProjectForm() {
      const form = document.getElementById('formCrearProyecto');
      if (form) {
        form.reset();
      }
    }

    // Form submit handler para crear proyecto
    const form = document.getElementById('formCrearProyecto');
    if (form) {
      form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        try {
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Creando...';

          const formData = new FormData(this);
          formData.append('tipo', 'Proyecto');
          formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

          const response = await fetch('/modules/processes_tasks/api/create.php', {
            method: 'POST',
            body: formData
          });

          const data = await response.json();

          if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalCrearProyecto'));
            modal.hide();
            clearProjectForm();
            await cargarProyectos();
            await cargarEstadisticasProjects();
            alert('Proyecto creado exitosamente');
          } else {
            alert(data.message || 'Error al crear el proyecto');
          }

        } catch (error) {
          console.error('Error:', error);
          alert('Error de conexión al crear el proyecto');
        } finally {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        }
      });
    }

    window.editarProyecto = function(id) {
      console.log('✏️ Editar proyecto:', id);
      alert(`Editar proyecto ${id} - Modal de edición próximamente`);
    };

    window.verProyecto = function(id) {
      console.log('👁️ Ver proyecto:', id);
      alert(`Ver detalles de proyecto ${id} - Modal de detalles próximamente`);
    };

    window.eliminarProyecto = function(id) {
      console.log('🗑️ Eliminar proyecto:', id);
      if (confirm('¿Estás seguro de que quieres eliminar este proyecto?')) {
        eliminarProyectoAPI(id);
      }
    };
    // Función para eliminar proyecto via API
    async function eliminarProyectoAPI(id) {
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
          alert('Proyecto eliminado exitosamente');
          cargarProyectos(); // Recargar la lista
          cargarEstadisticasProjects(); // Actualizar estadísticas
        } else {
          alert('Error al eliminar proyecto: ' + (data.error || 'Error desconocido'));
        }
      } catch (error) {
        console.error('Error al eliminar proyecto:', error);
        alert('Error de conexión al eliminar proyecto');
      }
    }

    // Función para ver tareas de un proyecto
    window.verTareasProyecto = function(id) {
      console.log('📋 Ver tareas del proyecto:', id);
      // Redirigir a la vista de tareas con filtro de proyecto
      window.location.href = `/modules/processes_tasks/?tab=tasks&project_id=${id}`;
    };

    // Función para duplicar proyecto
    window.duplicarProyecto = function(id) {
      console.log('📄 Duplicar proyecto:', id);
      if (confirm('¿Quieres crear una copia de este proyecto?')) {
        duplicarProyectoAPI(id);
      }
    };

    // Función para duplicar proyecto via API
    async function duplicarProyectoAPI(id) {
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
          alert('Proyecto duplicado exitosamente');
          cargarProyectos(); // Recargar la lista
          cargarEstadisticasProjects(); // Actualizar estadísticas
        } else {
          alert('Error al duplicar proyecto: ' + (data.error || 'Error desconocido'));
        }
      } catch (error) {
        console.error('Error al duplicar proyecto:', error);
        alert('Error de conexión al duplicar proyecto');
      }
    }

    // Función auxiliar para poblar selects
    function populateSelect(selectId, items, valueField, textField) {
      const select = document.getElementById(selectId);
      if (!select) {
        console.error(`❌ [Projects] No se encontró el select con ID: ${selectId}`);
        return;
      }

      // Limpiar opciones existentes (excepto la primera)
      while (select.children.length > 1) {
        select.removeChild(select.lastChild);
      }

      if (!items || !Array.isArray(items)) {
        console.warn(`⚠️ [Projects] Items no válidos para select ${selectId}:`, items);
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
      
      console.log(`✅ [Projects] Select ${selectId} poblado con ${items.length} opciones`);
    }

    // Función global para abrir modal de crear proyecto
    window.abrirModalCrearProyecto = async function() {
      try {
        console.log('🔄 [PROJECTS] Abriendo modal de crear proyecto...');
        
        // Abrir modal primero
        const modal = new bootstrap.Modal(document.getElementById('modalCrearProyecto'));
        modal.show();
        
        // Esperar un momento para que el modal se renderice
        setTimeout(async () => {
          console.log('🔄 [PROJECTS] Modal abierto, cargando datos...');
          await cargarDatosSelectsProyecto();
        }, 200);
        
      } catch (error) {
        console.error('❌ [PROJECTS] Error al abrir modal de proyecto:', error);
        // Abrir modal aunque falle la carga de datos
        const modal = new bootstrap.Modal(document.getElementById('modalCrearProyecto'));
        modal.show();
      }
    };

    // Configurar event listener para el formulario de crear proyecto
    const formCrearProyecto = document.getElementById('formCrearProyecto');
    if (formCrearProyecto) {
      formCrearProyecto.addEventListener('submit', async function(e) {
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
            bootstrap.Modal.getInstance(document.getElementById('modalCrearProyecto')).hide();
            form.reset();
            cargarProyectos();
            cargarEstadisticasProjects();
            alert('Proyecto creado exitosamente');
          } else {
            alert('Error al crear proyecto: ' + (result.error || 'Error desconocido'));
          }
        } catch (error) {
          console.error('Error:', error);
          alert('Error de conexión al crear proyecto');
        } finally {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        }
      });
    }

    console.log('✅ Módulo de Proyectos - Versión Limpia inicializado');
  })();
</script>

<!-- Modal de Crear Proyecto -->
<div class="modal fade" id="modalCrearProyecto" tabindex="-1" aria-labelledby="modalCrearProyectoLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCrearProyectoLabel">
          <i class="bi bi-plus-circle me-2 text-warning" aria-hidden="true"></i>Crear Nuevo Proyecto
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formCrearProyecto">
        <div class="modal-body">
          <div class="row g-4">
            <!-- Información Básica -->
            <div class="col-12">
              <label class="form-label fw-medium">Nombre del Proyecto <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="title" required placeholder="Ej: Implementación Sistema CRM">
            </div>
            
            <div class="col-12">
              <label class="form-label fw-medium">Descripción</label>
              <textarea class="form-control" name="description" rows="3" placeholder="Describe los objetivos y alcance del proyecto..."></textarea>
            </div>

            <!-- Fechas -->
            <div class="col-md-6">
              <label class="form-label fw-medium">Fecha de Inicio</label>
              <input type="date" class="form-control" name="start_date" value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium">Fecha de Vencimiento</label>
              <input type="date" class="form-control" name="due_date">
            </div>

            <!-- Configuración -->
            <div class="col-md-4">
              <label class="form-label fw-medium">Prioridad</label>
              <select class="form-select" name="priority">
                <option value="Normal">Normal</option>
                <option value="Importante">Alta</option>
                <option value="Urgente">Urgente</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-medium">Estado</label>
              <select class="form-select" name="status">
                <option value="En tiempo">En tiempo</option>
                <option value="En proceso">En proceso</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-medium">Responsable</label>
              <select class="form-select" name="assignee_id" id="selectResponsableProyecto">
                <option value="">Seleccionar responsable...</option>
              </select>
            </div>

            <!-- Organización -->
            <div class="col-md-6">
              <label class="form-label fw-medium">Unidad de negocio</label>
              <select class="form-select" name="unit_id" id="selectUnidadProyecto">
                <option value="">Seleccionar unidad...</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium">Negocio</label>
              <select class="form-select" name="business_id" id="selectNegocioProyecto">
                <option value="">Seleccionar negocio...</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-brand">
            <i class="bi bi-check me-1"></i>Crear Proyecto
          </button>
        </div>
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="type" value="Proyecto">
      </form>
    </div>
  </div>
</div>

<!-- Modal de Editar Proyecto -->
<div class="modal fade" id="modalEditarProyecto" tabindex="-1" aria-labelledby="modalEditarProyectoLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditarProyectoLabel">
          <i class="bi bi-pencil me-2 text-warning" aria-hidden="true"></i>Editar Proyecto
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditarProyecto">
        <div class="modal-body">
          <div class="row g-4">
            <!-- Similar estructura al modal crear -->
            <div class="col-12">
              <label class="form-label fw-medium">Nombre del Proyecto <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="title" required>
            </div>
            
            <div class="col-12">
              <label class="form-label fw-medium">Descripción</label>
              <textarea class="form-control" name="description" rows="3"></textarea>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium">Fecha de Inicio</label>
              <input type="date" class="form-control" name="start_date">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium">Fecha de Vencimiento</label>
              <input type="date" class="form-control" name="due_date">
            </div>

            <div class="col-md-4">
              <label class="form-label fw-medium">Prioridad</label>
              <select class="form-select" name="priority">
                <option value="Normal">Normal</option>
                <option value="Importante">Alta</option>
                <option value="Urgente">Urgente</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-medium">Estado</label>
              <select class="form-select" name="status">
                <option value="En tiempo">En tiempo</option>
                <option value="En proceso">En proceso</option>
                <option value="Terminada">Terminada</option>
                <option value="Pausada">Pausada</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-medium">Responsable</label>
              <select class="form-select" name="assignee_id" id="selectResponsableProyectoEdit">
                <option value="">Seleccionar responsable...</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium">Unidad de negocio</label>
              <select class="form-select" name="unit_id" id="selectUnidadProyectoEdit">
                <option value="">Seleccionar unidad...</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium">Negocio</label>
              <select class="form-select" name="business_id" id="selectNegocioProyectoEdit">
                <option value="">Seleccionar negocio...</option>
              </select>
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

<!-- Script para modales de proyecto -->
<script>
(function() {
  'use strict';

  let currentEditingProjectId = null;

  // Cargar datos para selects de proyecto
  async function cargarDatosSelectsProyecto() {
    try {
      console.log('🔄 [PROJECTS] Iniciando carga de datos para selects de proyecto...');
      
      const response = await fetch('/modules/processes_tasks/api/form_data_simple.php');
      console.log('📡 [PROJECTS] Response status:', response.status);
      
      if (!response.ok) {
        throw new Error(`HTTP Error: ${response.status}`);
      }
      
      const data = await response.json();
      console.log('📦 [PROJECTS] Data received:', data);
      console.log('📦 [PROJECTS] Employees data:', data.employees);
      
      if (data.ok) {
        // Poblar selects de crear proyecto
        if (data.employees && data.employees.length > 0) {
          console.log(`👥 [PROJECTS] Poblando empleados: ${data.employees.length} items`);
          console.log('👥 [PROJECTS] Primer empleado:', data.employees[0]);
          populateSelect('selectResponsableProyecto', data.employees, 'id', 'name');
          populateSelect('selectResponsableProyectoEdit', data.employees, 'id', 'name');
          console.log('✅ [PROJECTS] Todos los selects de empleados poblados');
        } else {
          console.warn('⚠️ [PROJECTS] No hay empleados en la respuesta para proyectos');
          console.warn('⚠️ [PROJECTS] Data completa:', data);
        }
        
        if (data.units && data.units.length > 0) {
          console.log(`🏢 Poblando unidades para proyectos: ${data.units.length} items`);
          populateSelect('selectUnidadProyecto', data.units, 'id', 'name');
          populateSelect('selectUnidadProyectoEdit', data.units, 'id', 'name');
        } else {
          console.warn('⚠️ No hay unidades en la respuesta para proyectos');
        }
        
        if (data.businesses && data.businesses.length > 0) {
          console.log(`🏪 Poblando negocios para proyectos: ${data.businesses.length} items`);
          populateSelect('selectNegocioProyecto', data.businesses, 'id', 'name');
          populateSelect('selectNegocioProyectoEdit', data.businesses, 'id', 'name');
        } else {
          console.warn('⚠️ No hay negocios en la respuesta para proyectos');
        }
        
        console.log('✅ Datos de proyecto cargados exitosamente');
      } else {
        throw new Error(data.error || 'Error cargando datos de proyecto');
      }
    } catch (error) {
      console.error('❌ Error cargando datos para proyecto:', error);
      // TODO: Agregar fallback si es necesario
    }
  }

  // Función global para editar proyecto
  window.editarProyecto = async function(taskId) {
    try {
      currentEditingProjectId = taskId;
      
      // Cargar datos del proyecto
      const response = await fetch(`/modules/processes_tasks/api/read.php?id=${taskId}`);
      const result = await response.json();

      if (result.ok && result.data) {
        const proyecto = result.data;
        const form = document.getElementById('formEditarProyecto');

        // Llenar el formulario con los datos del proyecto
        form.title.value = proyecto.title || '';
        form.description.value = proyecto.description || '';
        form.start_date.value = proyecto.start_date ? proyecto.start_date.split(' ')[0] : '';
        form.due_date.value = proyecto.due_date ? proyecto.due_date.split(' ')[0] : '';
        form.priority.value = proyecto.priority || 'Normal';
        form.status.value = proyecto.status || 'En tiempo';
        form.assignee_id.value = proyecto.assignee_id || '';
        form.unit_id.value = proyecto.unit_id || '';
        form.business_id.value = proyecto.business_id || '';
        form.task_id.value = taskId;

        // Cargar datos para selects y luego mostrar modal
        await cargarDatosSelectsProyecto();
        
        // Re-asignar valores después de cargar los datos
        setTimeout(() => {
          form.assignee_id.value = proyecto.assignee_id || '';
          form.unit_id.value = proyecto.unit_id || '';
          form.business_id.value = proyecto.business_id || '';
        }, 100);

        const modal = new bootstrap.Modal(document.getElementById('modalEditarProyecto'));
        modal.show();
      } else {
        alert('Error al cargar datos del proyecto');
      }
    } catch (error) {
      console.error('Error:', error);
      alert('Error de conexión al cargar proyecto');
    }
  };

  // Manejar envío del formulario de editar proyecto
  document.getElementById('formEditarProyecto').addEventListener('submit', async function(e) {
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
        bootstrap.Modal.getInstance(document.getElementById('modalEditarProyecto')).hide();
        cargarProyectos();
        cargarEstadisticasProjects();
        alert('Proyecto actualizado exitosamente');
      } else {
        alert('Error al actualizar proyecto: ' + (result.error || 'Error desconocido'));
      }
    } catch (error) {
      console.error('Error:', error);
      alert('Error de conexión al actualizar proyecto');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  });
})();
</script>
    }, 100);
  }

})();
</script>

<!-- Token CSRF para AJAX -->
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
