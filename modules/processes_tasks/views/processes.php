<?php

/**
 * Vista de Procesos - Versión Limpia y Funcional
 * Sigue el patrón exitoso de tasks.php
 */

// Asegurar que el token CSRF existe
if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
?>

<!-- Forzar carga sin cache -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<div class="container-fluid py-3">
  <!-- Header de sección (igual RH) -->
  <div class="hr-header">
    <div class="hr-header-left">
      <h1>Procesos</h1>
      <p class="subtitle">Gestiona flujos activos, recurrencias y tareas generadas por cada proceso.</p>
    </div>
    <div class="hr-header-right">
      <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalColumns_ptProcessesTable" id="btnColumnsPT_ptProcessesTable">
        <i class="bi bi-columns-gap"></i> Columnas
      </button>
      <button type="button" class="btn btn-primary" onclick="abrirModalCrearProceso()" aria-label="Agregar nuevo proceso">
        <i class="bi bi-plus-circle"></i> Nuevo proceso
      </button>
    </div>
  </div>

  <!-- Filtros (igual RH) -->
  <div class="hr-filter-card reveal">
    <form class="row g-3 align-items-end" id="ptProcessesFilters">
      <div class="col-12 col-md-6 col-xl-2">
        <label class="form-label">Unidad</label>
        <select name="unit_id" id="ptProcessesUnit" class="form-select">
          <option value="">Todas</option>
        </select>
      </div>
      <div class="col-12 col-md-6 col-xl-2">
        <label class="form-label">Negocio</label>
        <select name="business_id" id="ptProcessesBusiness" class="form-select">
          <option value="">Todos</option>
        </select>
      </div>
      <div class="col-12 col-md-6 col-xl-2">
        <label class="form-label">Responsable</label>
        <select name="assignee" id="ptProcessesAssignee" class="form-select">
          <option value="">Todos</option>
        </select>
      </div>
      <div class="col-12 col-md-6 col-xl-2">
        <label class="form-label">Estado</label>
        <select name="status" id="ptProcessesStatus" class="form-select">
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
  <div class="modal fade" id="modalColumns_ptProcessesTable" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content hr-modal">
        <div class="modal-header hr-modal">
          <h5 class="modal-title hr-modal">Mostrar / Ocultar columnas</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body hr-modal">
          <p class="text-muted mb-2">Elige qué columnas quieres ver en la tabla.</p>
          <div id="columnsChecklist_ptProcessesTable" class="row g-2"><!-- se llena por JS --></div>
        </div>
        <div class="modal-footer hr-modal">
          <button class="btn btn-ghost" data-bs-dismiss="modal">Cerrar</button>
          <button class="btn btn-brand" id="saveColumns_ptProcessesTable">Aplicar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabla (igual RH) -->
  <div class="hr-table-wrapper reveal">
    <div class="ix-table-card">
      <div class="table-responsive">
        <table class="table table-hover align-middle ix-pt-table" id="ptProcessesTable">
          <thead>
            <tr>
              <th scope="col" data-col-key="sel" class="text-center"><input type="checkbox" id="ptProcessesSelectAll" aria-label="Seleccionar todos"></th>
              <th scope="col" data-col-key="folio">Folio</th>
              <th scope="col" data-col-key="name">Nombre del proceso</th>
              <th scope="col" data-col-key="unit" class="text-center">Unidad</th>
              <th scope="col" data-col-key="biz" class="text-center">Negocio</th>
              <th scope="col" data-col-key="assignee" class="text-center">Responsable</th>
              <th scope="col" data-col-key="start">F. inicio</th>
              <th scope="col" data-col-key="due">F. vencimiento</th>
              <th scope="col" data-col-key="prio" class="text-center">Prioridad</th>
              <th scope="col" data-col-key="state" class="text-center">Estado</th>
              <th scope="col" data-col-key="actions" class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody id="ptProcessesTbody">
            <tr>
              <td colspan="11" class="text-center py-5">
                <div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div>
                <p class="text-muted mt-3 mb-0">Cargando procesos...</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Token CSRF para AJAX -->
<input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

<!-- Modal de Crear Proceso -->
<div class="modal fade" id="modalCrearProceso" tabindex="-1" aria-labelledby="modalCrearProcesoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCrearProcesoLabel">
          <i class="bi bi-plus-circle me-2"></i>Crear Nuevo Proceso
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formCrearProceso">
        <div class="modal-body">
          <div class="row g-3">
            <!-- Título del proceso -->
            <div class="col-12">
              <label for="titulo" class="form-label">Título del Proceso <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="titulo" name="titulo" required maxlength="255" placeholder="Ej: Proceso de aprobación de documentos">
            </div>

            <!-- Descripción -->
            <div class="col-12">
              <label for="descripcion" class="form-label">Descripción</label>
              <textarea class="form-control" id="descripcion" name="descripcion" rows="3" maxlength="5000" placeholder="Describe el proceso y sus objetivos..."></textarea>
            </div>

            <!-- Fechas -->
            <div class="col-md-6">
              <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
              <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
            </div>

            <div class="col-md-6">
              <label for="fecha_entrega" class="form-label">Fecha de Vencimiento</label>
              <input type="date" class="form-control" id="fecha_entrega" name="fecha_entrega">
            </div>

            <!-- Prioridad y Status -->
            <div class="col-md-6">
              <label for="prioridad" class="form-label">Prioridad</label>
              <select class="form-select" id="prioridad" name="prioridad">
                <option value="Normal">Normal</option>
                <option value="Alta">Alta</option>
                <option value="Crítica">Crítica</option>
                <option value="Baja">Baja</option>
              </select>
            </div>

            <div class="col-md-6">
              <label for="status" class="form-label">Estado</label>
              <select class="form-select" id="status" name="status">
                <option value="En proceso">En proceso</option>
                <option value="En tiempo">En tiempo</option>
                <option value="Pausada">Pausada</option>
              </select>
            </div>

            <!-- Asignación -->
            <div class="col-md-6">
              <label for="unit_id" class="form-label">Unidad</label>
              <select class="form-select" id="unit_id" name="unit_id">
                <option value="">Seleccionar unidad...</option>
                <!-- Se llenarán dinámicamente -->
              </select>
            </div>

            <div class="col-md-6">
              <label for="business_id" class="form-label">Empresa</label>
              <select class="form-select" id="business_id" name="business_id">
                <option value="">Seleccionar empresa...</option>
                <!-- Se llenarán dinámicamente -->
              </select>
            </div>

            <div class="col-12">
              <label for="assignee" class="form-label">Responsable</label>
              <select class="form-select" id="assignee" name="assignee">
                <option value="">Seleccionar responsable...</option>
                <!-- Se llenarán dinámicamente -->
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-brand">
            <i class="bi bi-check-lg me-1"></i>Crear Proceso
          </button>
        </div>
        <!-- Campos hidden -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="type" value="Proceso">
      </form>
    </div>
  </div>
</div>

<!-- Modal de Ver Detalles Proceso -->
<div class="modal fade" id="modalVerProceso" tabindex="-1" aria-labelledby="modalVerProcesoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalVerProcesoLabel">
          <i class="bi bi-eye me-2"></i>Detalles del Proceso
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="contenidoVerProceso">
          <div class="text-center py-4">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-brand" onclick="editarProcesoDesdeModa()">
          <i class="bi bi-pencil me-1"></i>Editar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  (function() {
    'use strict';

    console.log('✅ Módulo de Procesos - Versión Limpia iniciando...');

    // Variables globales
    let allProcesses = [];
    let currentProcessFilters = {};

    // Elementos DOM
    const tbody = document.getElementById('ptProcessesTbody');
    const table = document.getElementById('ptProcessesTable');

    // Función para mostrar errores
    function mostrarError(mensaje) {
      tbody.innerHTML = `
            <tr>
          <td colspan="11" class="text-center py-4">
                    <div class="text-danger">
                        <i class="bi bi-exclamation-circle me-2"></i>${mensaje}
                    </div>
                </td>
            </tr>
        `;
    }

    // Función para escapar HTML
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text ?? '';
      return div.innerHTML;
    }

    function formatDate(dateStr) {
      if (!dateStr) return '-';
      const d = new Date(dateStr);
      if (isNaN(d.getTime())) return escapeHtml(dateStr);
      const day = String(d.getDate()).padStart(2, '0');
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const year = d.getFullYear();
      return `${day}/${month}/${year}`;
    }

    // Pills estilo RH
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
      return `<span class="ix-pill ix-pill--${variant}">${escapeHtml(value || 'Sin estado')}</span>`;
    }

    function getPriorityBadge(priority) {
      const value = (priority || '').trim();
      const variant = (() => {
        switch (value) {
          case 'Crítica':
            return 'danger';
          case 'Alta':
            return 'warn';
          case 'Media':
          case 'Normal':
            return 'info';
          case 'Baja':
            return 'muted';
          default:
            return 'muted';
        }
      })();
      return `<span class="ix-pill ix-pill--${variant}">${escapeHtml(value || 'Normal')}</span>`;
    }

    // Cargar estadísticas de procesos
    async function cargarEstadisticas() {
      const pendingEl = document.getElementById('stat-pending');
      const overdueEl = document.getElementById('stat-overdue');
      const completedEl = document.getElementById('stat-completed');
      const totalEl = document.getElementById('stat-total');
      if (!pendingEl || !overdueEl || !completedEl || !totalEl) {
        return;
      }
      try {
        console.log('📊 Cargando estadísticas de procesos...');

        const response = await fetch('/modules/processes_tasks/api/stats.php?tipo=Proceso');
        const data = await response.json();

        if (data.ok && data.stats) {
          const stats = data.stats;
          console.log('✅ Estadísticas de procesos cargadas:', stats);

          pendingEl.textContent = stats.pending || 0;
          overdueEl.textContent = stats.overdue || 0;
          completedEl.textContent = stats.completed || 0;
          totalEl.textContent = stats.total || 0;
        }
      } catch (error) {
        console.error('❌ Error al cargar estadísticas de procesos:', error);
        pendingEl.textContent = '0';
        overdueEl.textContent = '0';
        completedEl.textContent = '0';
        totalEl.textContent = '0';
      }
    }

    function readProcessFiltersFromUI() {
      return {
        unit_id: document.getElementById('ptProcessesUnit')?.value || '',
        business_id: document.getElementById('ptProcessesBusiness')?.value || '',
        assignee: document.getElementById('ptProcessesAssignee')?.value || '',
        status: document.getElementById('ptProcessesStatus')?.value || ''
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
      const unitEl = document.getElementById('ptProcessesUnit');
      const businessEl = document.getElementById('ptProcessesBusiness');
      const assigneeEl = document.getElementById('ptProcessesAssignee');
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

    // Cargar lista de procesos
    async function cargarProcesos(filtros) {
      try {
        console.log('📋 Cargando lista de procesos...');

        const effectiveFilters = (filtros && Object.keys(filtros).length) ? filtros : currentProcessFilters;
        const params = new URLSearchParams();
        params.append('tipo', 'Proceso');
        if (effectiveFilters.unit_id) params.append('unit_id', effectiveFilters.unit_id);
        if (effectiveFilters.business_id) params.append('business_id', effectiveFilters.business_id);
        if (effectiveFilters.assignee) params.append('assignee', effectiveFilters.assignee);
        if (effectiveFilters.status) params.append('status', effectiveFilters.status);

        const response = await fetch(`/modules/processes_tasks/api/list.php?${params.toString()}`, { credentials: 'same-origin' });
        const data = await response.json();

        if (data.ok) {
          allProcesses = data.items || [];
          console.log(`✅ ${allProcesses.length} procesos cargados`);

          renderizarTabla(allProcesses);
        } else {
          throw new Error(data.message || 'Error al cargar procesos');
        }
      } catch (error) {
        console.error('❌ Error al cargar procesos:', error);
        mostrarError('Error al cargar los procesos');
      }
    }

    // Renderizar tabla
    function renderizarTabla(processes) {
      if (!processes || processes.length === 0) {
        tbody.innerHTML = `
                <tr>
                    <td colspan="11" class="text-center py-5">
                        <div class="text-muted">
                            <i class="bi bi-gear-wide-connected" style="font-size: 3rem; opacity: 0.3;"></i>
                <p class="mt-3 mb-0 fw-medium">No se encontraron procesos</p>
                <p class="text-muted small">Ajusta los filtros o crea un nuevo proceso</p>
                        </div>
                    </td>
                </tr>
            `;
        return;
      }

      tbody.innerHTML = processes.map(process => {
        return `
            <tr>
                <td class="text-center"><input type="checkbox" class="pt-process-check" value="${escapeHtml(process.id)}" aria-label="Seleccionar proceso"></td>
                <td>${escapeHtml(process.folio || '-')}</td>
                <td class="fw-semibold">${escapeHtml(process.titulo || '-')}</td>
                <td class="text-center">${escapeHtml(process.unit_nombre || '-')}</td>
                <td class="text-center">${escapeHtml(process.business_nombre || '-')}</td>
                <td class="text-center">${escapeHtml(process.responsable_nombre || process.delegado_nombre || process.delegado_email || '-')}</td>
                <td>${formatDate(process.fecha_inicio)}</td>
                <td>${formatDate(process.fecha_entrega)}</td>
                <td class="text-center">${getPriorityBadge(process.prioridad || process.nivel)}</td>
                <td class="text-center">${getStatusBadge(process.status)}</td>
                <td class="text-end">
                    <div class="ix-row-actions">
                        <button type="button" class="btn btn-sm btn-icon ix-action-btn ix-action-btn--view" onclick="verProceso(${process.id})" title="Ver" aria-label="Ver"><i class="bi bi-eye"></i></button>
                        <button type="button" class="btn btn-sm btn-icon ix-action-btn ix-action-btn--edit" onclick="editarProceso(${process.id})" title="Editar" aria-label="Editar"><i class="bi bi-pencil"></i></button>
                        <button type="button" class="btn btn-sm btn-icon ix-action-btn ix-action-btn--dup" onclick="duplicarProceso(${process.id})" title="Duplicar" aria-label="Duplicar"><i class="bi bi-copy"></i></button>
                        <button type="button" class="btn btn-sm btn-icon ix-action-btn ix-action-btn--danger" onclick="eliminarProceso(${process.id})" title="Eliminar" aria-label="Eliminar"><i class="bi bi-trash"></i></button>
                    </div>
                </td>
            </tr>
            `;
      }).join('');
    }

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
          <label class="form-check-label" for="${inputId}">${escapeHtml(c.label)}</label>
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

    // Funciones globales para acciones (implementación real)
    window.abrirModalCrearProceso = function() {
      console.log('🆕 Crear nuevo proceso - Iniciando...');

      // Verificar que Bootstrap esté disponible
      if (typeof bootstrap === 'undefined') {
        console.error('❌ Bootstrap no está disponible');
        alert('Error: Bootstrap no está cargado. Recarga la página.');
        return;
      }

      // Verificar que el modal existe
      const modalElement = document.getElementById('modalCrearProceso');
      if (!modalElement) {
        console.error('❌ Modal modalCrearProceso no encontrado');
        alert('Error: Modal no encontrado en el DOM.');
        return;
      }

      console.log('✅ Bootstrap disponible, modal encontrado');
      clearProcessForm();
      cargarOpcionesFormulario();

      try {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        console.log('✅ Modal abierto exitosamente');
      } catch (error) {
        console.error('❌ Error al abrir modal:', error);
        alert('Error al abrir modal: ' + error.message);
      }
    };

    // Limpiar formulario de proceso
    function clearProcessForm() {
      const form = document.getElementById('formCrearProceso');
      if (form) {
        form.reset();
      }
    }

    window.editarProceso = function(id) {
      console.log('✏️ Editar proceso:', id);
      // TODO: Implementar modal de edición
      alert(`Editar proceso ${id} - Modal de edición próximamente`);
    };

    window.verProceso = function(id) {
      console.log('👁️ Ver proceso:', id);
      cargarDetallesProceso(id);
      const modal = new bootstrap.Modal(document.getElementById('modalVerProceso'));
      modal.show();
    };

    window.eliminarProceso = function(id) {
      console.log('🗑️ Eliminar proceso:', id);
      if (confirm('¿Estás seguro de que quieres eliminar este proceso?')) {
        eliminarProcesoAPI(id);
      }
    };

    // Función para cargar opciones de formulario (unidades, empresas, usuarios)
    async function cargarOpcionesFormulario() {
      try {
        console.log('🔄 [Processes] Iniciando carga de datos para formulario...');
        
        const response = await fetch('/modules/processes_tasks/api/form_data_simple.php');
        console.log('📡 [Processes] Response status:', response.status);
        
        if (!response.ok) {
          throw new Error(`HTTP Error: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('📦 [Processes] Data received:', data);
        
        if (data.ok) {
          // Llenar select de empleados
          if (data.employees && data.employees.length > 0) {
            console.log(`👥 [Processes] Poblando empleados: ${data.employees.length} items`);
            const selectEmpleado = document.getElementById('assignee');
            if (selectEmpleado) {
              // Limpiar opciones excepto la primera
              while (selectEmpleado.children.length > 1) {
                selectEmpleado.removeChild(selectEmpleado.lastChild);
              }
              
              data.employees.forEach(employee => {
                const option = document.createElement('option');
                option.value = employee.id;
                option.textContent = employee.name;
                selectEmpleado.appendChild(option);
              });
            } else {
              console.error('❌ [Processes] No se encontró select #assignee');
            }
          } else {
            console.warn('⚠️ [Processes] No hay empleados en la respuesta');
          }
          
          // Llenar select de unidades
          if (data.units && data.units.length > 0) {
            console.log(`🏢 [Processes] Poblando unidades: ${data.units.length} items`);
            const selectUnidad = document.getElementById('unit_id');
            if (selectUnidad) {
              while (selectUnidad.children.length > 1) {
                selectUnidad.removeChild(selectUnidad.lastChild);
              }
              
              data.units.forEach(unit => {
                const option = document.createElement('option');
                option.value = unit.id;
                option.textContent = unit.name;
                selectUnidad.appendChild(option);
              });
            } else {
              console.error('❌ [Processes] No se encontró select #unit_id');
            }
          } else {
            console.warn('⚠️ [Processes] No hay unidades en la respuesta');
          }
          
          // Llenar select de negocios
          if (data.businesses && data.businesses.length > 0) {
            console.log(`🏪 [Processes] Poblando negocios: ${data.businesses.length} items`);
            const selectNegocio = document.getElementById('business_id');
            if (selectNegocio) {
              while (selectNegocio.children.length > 1) {
                selectNegocio.removeChild(selectNegocio.lastChild);
              }
              
              data.businesses.forEach(business => {
                const option = document.createElement('option');
                option.value = business.id;
                option.textContent = business.name;
                selectNegocio.appendChild(option);
              });
            } else {
              console.error('❌ [Processes] No se encontró select #business_id');
            }
          } else {
            console.warn('⚠️ [Processes] No hay negocios en la respuesta');
          }
          
          console.log('✅ [Processes] Opciones de formulario cargadas');
        } else {
          console.error('❌ [Processes] Error en respuesta:', data.error);
          // Intentar cargar individualmente como fallback
          await cargarOpcionesIndividuales();
        }
      } catch (error) {
        console.error('❌ [Processes] Error al cargar opciones:', error);
        await cargarOpcionesIndividuales();
      }
    }

    // Función de fallback para cargar opciones individuales
    async function cargarOpcionesIndividuales() {
      console.log('🔄 Usando fallback para cargar opciones...');
      
      try {
        // Cargar empleados
        const empleadosResp = await fetch('/modules/processes_tasks/api/employees.php');
        if (empleadosResp.ok) {
          const empleados = await empleadosResp.json();
          if (empleados.ok && empleados.data) {
            const select = document.getElementById('assignee');
            if (select) {
              while (select.children.length > 1) {
                select.removeChild(select.lastChild);
              }
              empleados.data.forEach(emp => {
                const option = document.createElement('option');
                option.value = emp.id;
                option.textContent = emp.name;
                select.appendChild(option);
              });
            }
          }
        }

        // Cargar unidades
        const unitsResp = await fetch('/api/units.php');
        if (unitsResp.ok) {
          const units = await unitsResp.json();
          if (units.ok && units.data) {
            const select = document.getElementById('unit_id');
            if (select) {
              while (select.children.length > 1) {
                select.removeChild(select.lastChild);
              }
              units.data.forEach(unit => {
                const option = document.createElement('option');
                option.value = unit.id;
                option.textContent = unit.name;
                select.appendChild(option);
              });
            }
          }
        }

        // Cargar negocios
        const businessResp = await fetch('/api/businesses.php');
        if (businessResp.ok) {
          const businesses = await businessResp.json();
          if (businesses.ok && businesses.data) {
            const select = document.getElementById('business_id');
            if (select) {
              while (select.children.length > 1) {
                select.removeChild(select.lastChild);
              }
              businesses.data.forEach(biz => {
                const option = document.createElement('option');
                option.value = biz.id;
                option.textContent = biz.name;
                select.appendChild(option);
              });
            }
          }
        }

        console.log('✅ Fallback completado');
      } catch (error) {
        console.error('❌ Error en fallback:', error);
      }
    }

    // Función para cargar detalles de un proceso
    async function cargarDetallesProceso(id) {
      const contenido = document.getElementById('contenidoVerProceso');

      try {
        // Buscar el proceso en los datos ya cargados
        const proceso = allProcesses.find(p => p.id == id);

        if (proceso) {
          contenido.innerHTML = `
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>Folio:</strong> ${escapeHtml(proceso.folio || '-')}
                        </div>
                        <div class="col-md-6">
                            <strong>Estado:</strong> ${getStatusBadge(proceso.status)}
                        </div>
                        <div class="col-12">
                            <strong>Título:</strong> ${escapeHtml(proceso.titulo || '-')}
                        </div>
                        <div class="col-12">
                            <strong>Descripción:</strong> ${escapeHtml(proceso.descripcion || 'Sin descripción')}
                        </div>
                        <div class="col-md-6">
                            <strong>Unidad:</strong> ${escapeHtml(proceso.unit_nombre || '-')}
                        </div>
                        <div class="col-md-6">
                            <strong>Empresa:</strong> ${escapeHtml(proceso.business_nombre || '-')}
                        </div>
                        <div class="col-md-6">
                            <strong>Fecha Inicio:</strong> ${proceso.fecha_inicio ? new Date(proceso.fecha_inicio).toLocaleDateString() : '-'}
                        </div>
                        <div class="col-md-6">
                            <strong>Vencimiento:</strong> ${proceso.fecha_entrega ? new Date(proceso.fecha_entrega).toLocaleDateString() : '-'}
                        </div>
                        <div class="col-md-6">
                            <strong>Prioridad:</strong> ${getPriorityBadge(proceso.prioridad)}
                        </div>
                        <div class="col-md-6">
                            <strong>Responsable:</strong> ${escapeHtml(proceso.responsable_nombre || '-')}
                        </div>
                    </div>
                `;
        } else {
          contenido.innerHTML = '<div class="alert alert-warning">No se encontraron detalles del proceso.</div>';
        }
      } catch (error) {
        console.error('Error al cargar detalles:', error);
        contenido.innerHTML = '<div class="alert alert-danger">Error al cargar los detalles del proceso.</div>';
      }
    }

    // Manejar envío del formulario de crear proceso
    document.getElementById('formCrearProceso').addEventListener('submit', async function(e) {
      e.preventDefault();

      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Creando...';
      submitBtn.disabled = true;

      try {
        const formData = new FormData(this);
        // No agregar tipo aquí, ya está en el campo hidden

        const response = await fetch('/modules/processes_tasks/api/create.php', {
          method: 'POST',
          body: formData
        });

        const data = await response.json();

        if (data.ok) {
          alert('Proceso creado exitosamente');
          bootstrap.Modal.getInstance(document.getElementById('modalCrearProceso')).hide();
          this.reset();
          cargarProcesos(currentProcessFilters); // Recargar lista
          cargarEstadisticas(); // Actualizar estadísticas
        } else {
          alert('Error al crear proceso: ' + (data.error || 'Error desconocido'));
        }
      } catch (error) {
        console.error('Error al crear proceso:', error);
        alert('Error de conexión al crear proceso');
      } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
      }
    }); // Función para eliminar proceso via API
    async function eliminarProcesoAPI(id) {
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
          alert('Proceso eliminado exitosamente');
          cargarProcesos(currentProcessFilters); // Recargar la lista
          cargarEstadisticas(); // Actualizar estadísticas
        } else {
          alert('Error al eliminar proceso: ' + (data.error || 'Error desconocido'));
        }
      } catch (error) {
        console.error('Error al eliminar proceso:', error);
        alert('Error de conexión al eliminar proceso');
      }
    }

    // Función para duplicar proceso
    window.duplicarProceso = function(id) {
      console.log('📄 Duplicar proceso:', id);
      if (confirm('¿Quieres crear una copia de este proceso?')) {
        duplicarProcesoAPI(id);
      }
    };

    // Función para duplicar proceso via API
    async function duplicarProcesoAPI(id) {
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
          alert('Proceso duplicado exitosamente');
          cargarProcesos(currentProcessFilters); // Recargar la lista
          cargarEstadisticas(); // Actualizar estadísticas
        } else {
          alert('Error al duplicar proceso: ' + (data.error || 'Error desconocido'));
        }
      } catch (error) {
        console.error('Error al duplicar proceso:', error);
        alert('Error de conexión al duplicar proceso');
      }
    }

    // Función para ejecutar proceso (generar tareas)
    window.ejecutarProceso = function(id) {
      console.log('▶️ Ejecutar proceso:', id);
      if (confirm('¿Quieres ejecutar este proceso? Se generarán las tareas asociadas.')) {
        ejecutarProcesoAPI(id);
      }
    };

    // Función para ejecutar proceso via API
    async function ejecutarProcesoAPI(id) {
      try {
        const formData = new FormData();
        formData.append('process_id', id);
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

        // Cambiar status a "En proceso" al ejecutar
        const response = await fetch('/modules/processes_tasks/api/update.php', {
          method: 'POST',
          body: new FormData(Object.assign(formData, {
            task_id: id,
            status: 'En proceso'
          }))
        });

        const data = await response.json();

        if (data.ok) {
          alert('Proceso ejecutado exitosamente');
          cargarProcesos(currentProcessFilters); // Recargar la lista
          cargarEstadisticas(); // Actualizar estadísticas
        } else {
          alert('Error al ejecutar proceso: ' + (data.error || 'Error desconocido'));
        }
      } catch (error) {
        console.error('Error al ejecutar proceso:', error);
        alert('Error de conexión al ejecutar proceso');
      }
    }

    // Función para ver tareas de un proceso
    window.verTareasProceso = function(id) {
      console.log('📋 Ver tareas del proceso:', id);
      // Redirigir a la vista de tareas con filtro de proceso
      window.location.href = `/modules/processes_tasks/?tab=tasks&process_id=${id}`;
    };

    // Inicializar módulo
    async function inicializar() {
      console.log('🚀 Inicializando módulo de procesos...');

      initColumnsModal('ptProcessesTable');

      await cargarCatalogosFiltros();

      const filtersForm = document.getElementById('ptProcessesFilters');
      if (filtersForm) {
        filtersForm.addEventListener('submit', function(e) {
          e.preventDefault();
          currentProcessFilters = readProcessFiltersFromUI();
          cargarProcesos(currentProcessFilters);
        });
      }

      const selectAll = document.getElementById('ptProcessesSelectAll');
      if (selectAll) {
        selectAll.addEventListener('change', function() {
          document.querySelectorAll('#ptProcessesTbody .pt-process-check').forEach((cb) => {
            cb.checked = !!selectAll.checked;
          });
        });
      }

      currentProcessFilters = readProcessFiltersFromUI();

      await Promise.all([
        cargarEstadisticas(),
        cargarProcesos(currentProcessFilters)
      ]);

      console.log('✅ Módulo de procesos inicializado correctamente');
    }

    // Ejecutar cuando el DOM esté listo
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', inicializar);
    } else {
      inicializar();
    }
  })();
</script>