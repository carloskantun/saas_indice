// ========================================// /modules/processes_tasks/js/projects.js

// projects.js v4.0 — ENTERPRISE PATCH(function () {

// Índice ERP - Módulo de Proyectos  console.log('[Processes & Tasks] projects.js loaded');

// Adaptado al estilo estructural de tareas.js v4.0

// ========================================  // Helpers

const esc = s => String(s ?? '').replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));

console.log('📦 projects.js v4.0 cargado'); const qs = new URLSearchParams(location.search);

const projectId = qs.get('project_id');

// ========================================  const dayMs = 86400000;

// MÓDULO GLOBAL ProjectModule  const toISO = (d) => new Date(d).toISOString().slice(0, 10);

// ========================================  // Premium Gantt state

window.ProjectModule = window.ProjectModule || {}; let ganttZoom = 'week';

let ganttFilter = 'all';

// ========================================  let ganttRefDate = null;

// CONFIGURACIÓN

// ========================================  // MOCK datasets (sustituir por API luego)

const API_BASE = '/modules/processes_tasks/controllers/api.controller.php'; let projects = [

  {

    // ========================================      id: 'p1', name: 'Implementación POS', owner: 'Ana López', unit: 'Unidad A', business: 'Negocio 1',

    // ESTADO DEL MÓDULO      start: '2025-09-01', end: '2025-09-12', progress: 0.4, status: 'En curso'

    // ========================================    },

    ProjectModule.state = {    {

    proyectos: [], id: 'p2', name: 'Inventario cíclico', owner: 'Luis Pérez', unit: 'Unidad B', business: 'Negocio 2',

    tareasProyectos: [], start: '2025-09-03', end: '2025-09-10', progress: 0.7, status: 'Planificado'

    draggedTask: null,
  },

  draggedOverTask: null    {

  }; id: 'p3', name: 'Onboarding RRHH', owner: 'Carlos Ruiz', unit: 'Unidad A', business: 'Negocio 1',

    start: '2025-09-05', end: '2025-09-18', progress: 0.2, status: 'Planificado'

// ========================================    },

// HELPERS CENTRALIZADOS  ];

// ========================================



ProjectModule.log = function (msg, type = 'info') {

  const colors = {  // Tareas por proyecto (demo)

    info: '#3b82f6', const projectTasks = {

      success: '#10B981', p1: [

        warning: '#f59e0b', { title: 'Levantamiento', assignee: 'Ana López', start: '2025-09-01', end: '2025-09-02', status: 'Completada' },

        error: '#ef4444'      { title: 'Instalación', assignee: 'Luis Pérez', start: '2025-09-03', end: '2025-09-07', status: 'En curso' },

    };      { title: 'Capacitación', assignee: 'Carlos Ruiz', start: '2025-09-08', end: '2025-09-12', status: 'Pendiente' },

        ],

  console.log(p2: [

    `%c[ProjectModule] ${msg}`, { title: 'Conteo A', assignee: 'Ana López', start: '2025-09-03', end: '2025-09-05', status: 'Pendiente' },

    `color: ${colors[type] || colors.info}; font-weight: bold;`      { title: 'Conteo B', assignee: 'Luis Pérez', start: '2025-09-06', end: '2025-09-10', status: 'Pendiente' },

    );    ],

}; p3: [

  { title: 'Docs', assignee: 'Carlos Ruiz', start: '2025-09-05', end: '2025-09-06', status: 'Pendiente' },

  ProjectModule.toast = function (message, type = 'info') { { title: 'Sesiones', assignee: 'Ana López', start: '2025-09-09', end: '2025-09-18', status: 'Pendiente' },

    // Intentar usar showNotification global si existe    ]

    if (typeof window.showNotification === 'function') { };

window.showNotification(message, type);

return;  // HR users (loaded from server)

    }  let PROJECT_USERS = [];

async function loadProjectUsers() {

  // Fallback a window.showToast si existe    try {

  if (typeof window.showToast === 'function') {
    const res = await fetch('/modules/processes_tasks/api/hr_users.php', { credentials: 'same-origin' });

    window.showToast(message, type); if (!res.ok) throw new Error('failed');

    return; const j = await res.json();

  } if (j && j.ok && Array.isArray(j.items)) {

    PROJECT_USERS = j.items.map(u => ({ id: u.id, name: u.full_name || u.name || u.code || '', unit: u.unit_id || '', dept: u.department || '', title: u.position || '' }));

    // Fallback simple con Bootstrap Toast        return;

    const bgClass = {}

    success: 'bg-success',    } catch (e) {

      error: 'bg-danger',      // fallback (derive from projects dataset)

        warning: 'bg-warning', PROJECT_USERS = Array.from(new Set(projects.map(p => p.owner))).map((n, i) => ({ id: 'd' + (i + 1), name: n }));

      info: 'bg-primary'
    }

} [type] || 'bg-primary'; function enhanceOwnerSelect(sel) {

  if (!sel) return;

  const toast = document.createElement('div');      // If Select2 available, initialize it

  toast.className = `toast align-items-center text-white ${bgClass} border-0`; try {

    toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:20000;'; if (window.jQuery && jQuery && typeof jQuery.fn.select2 === 'function') {

      toast.setAttribute('role', 'alert');          // destroy previous if any

      toast.setAttribute('aria-live', 'assertive'); try { jQuery(sel).select2('destroy'); } catch (e) { }

      toast.setAttribute('aria-atomic', 'true'); jQuery(sel).select2({ width: '100%', placeholder: 'Selecciona...' });

      return;

      toast.innerHTML = `        }

        <div class="d-flex">      } catch (e) { /* ignore */ }

            <div class="toast-body">${message}</div>

            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>      // Fallback: add a small search input that filters options

        </div>      const wrap = sel.parentElement;

    `; if (!wrap) return;

      // Avoid adding multiple search inputs

      document.body.appendChild(toast); if (wrap.querySelector('.owner-search-input')) return;

      const input = document.createElement('input');

      if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        input.type = 'search';

        const bsToast = new bootstrap.Toast(toast); input.placeholder = 'Buscar responsable…';

        bsToast.show(); input.className = 'form-control form-control-sm owner-search-input mb-1';

        toast.addEventListener('hidden.bs.toast', () => toast.remove()); input.addEventListener('input', () => {

        } else {
          const q = input.value.trim().toLowerCase();

          setTimeout(() => toast.remove(), 3000); Array.from(sel.options).forEach(opt => {

          }          const txt = (opt.text || '').toLowerCase();

      }; opt.style.display = (!q || txt.includes(q)) ? '' : 'none';

    });

    ProjectModule.confirmDialog = function (message) { });

    // Usar confirm nativo (puede mejorarse con un modal custom)      wrap.insertBefore(input, sel);

    return confirm(message);
  }

};

function populateProjectOwners() {

  ProjectModule.normalizeDate = function (dateStr) {
    const sel = document.getElementById('project-owner-select');

    if (!dateStr) return ''; if (!sel) return loadProjectUsers();

    try {
      return loadProjectUsers().then(() => {

        const date = new Date(dateStr); sel.innerHTML = '<option value="">Selecciona...</option>' + PROJECT_USERS.map(u => `<option value="${esc(u.id)}">${esc(u.name)}</option>`).join('');

        return date.toISOString().slice(0, 10); enhanceOwnerSelect(sel);

      } catch (e) { }).catch (() => {

        ProjectModule.log(`Error normalizando fecha: ${dateStr}`, 'error'); sel.innerHTML = '<option value="">Selecciona...</option>' + PROJECT_USERS.map(u => `<option value="${esc(u.id)}">${esc(u.name)}</option>`).join('');

        return ''; enhanceOwnerSelect(sel);

      }
    });

  };
}

  }

ProjectModule.handleError = function (error, context = 'Operación') {

  ProjectModule.log(`${context} falló: ${error.message || error}`, 'error');  // Expose helpers at module scope so other functions (modals) can call them

  ProjectModule.toast(`Error en ${context}: ${error.message || 'Error desconocido'}`, 'error'); function enhanceOwnerSelect(sel) {

  }; if (!sel) return;

  try {

    ProjectModule.showLoading = function (selector) {
      if (window.jQuery && jQuery && typeof jQuery.fn.select2 === 'function') {

        const element = document.querySelector(selector); try { jQuery(sel).select2('destroy'); } catch (e) { }

        if (!element) return; jQuery(sel).select2({ width: '100%', placeholder: 'Selecciona...' });

        return;

        element.style.opacity = '0.5';
      }

      element.style.pointerEvents = 'none';
    } catch (e) { /* ignore */ }

    const wrap = sel.parentElement;

    const spinner = document.createElement('div'); if (!wrap) return;

    spinner.className = 'spinner-border text-primary project-module-spinner'; if (wrap.querySelector('.owner-search-input')) return;

    spinner.style.cssText = 'position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);z-index:1000;'; const input = document.createElement('input');

    element.style.position = 'relative'; input.type = 'search';

    element.appendChild(spinner); input.placeholder = 'Buscar responsable…';

  }; input.className = 'form-control form-control-sm owner-search-input mb-1';

  input.addEventListener('input', () => {

    ProjectModule.hideLoading = function (selector) {
      const q = input.value.trim().toLowerCase();

      const element = document.querySelector(selector); Array.from(sel.options).forEach(opt => {

        if (!element) return; const txt = (opt.text || '').toLowerCase();

        opt.style.display = (!q || txt.includes(q)) ? '' : 'none';

        element.style.opacity = '1';
      });

      element.style.pointerEvents = 'auto';
    });

  wrap.insertBefore(input, sel);

  const spinner = element.querySelector('.project-module-spinner');
}

if (spinner) spinner.remove();

}; function populateProjectOwners() {

  const sel = document.getElementById('project-owner-select');

  // ========================================    if (!sel) return loadProjectUsers();

  // COMUNICACIÓN API    return loadProjectUsers().then(() => {

  // ========================================      sel.innerHTML = '<option value="">Selecciona...</option>' + PROJECT_USERS.map(u => `<option value="${esc(u.id)}">${esc(u.name)}</option>`).join('');

  enhanceOwnerSelect(sel);

  ProjectModule.api = async function (action, params = {}, method = 'GET') { }).catch (() => {

    const url = `${API_BASE}?action=${action}`; sel.innerHTML = '<option value="">Selecciona...</option>' + PROJECT_USERS.map(u => `<option value="${esc(u.id)}">${esc(u.name)}</option>`).join('');

    enhanceOwnerSelect(sel);

    ProjectModule.log(`API Request: ${method} ${url}`, 'info');
  });

}

try {

  const options = {  // Filter state (list view)

    method: method, const projFilters = { date: [], unit: [], status: [], owner: [], business: [] };

    credentials: 'same-origin',

    headers: {  // Entry point

      'X-Requested-With': 'XMLHttpRequest'  if(!projectId) renderList();

    }  else renderDetail(projectId);

  };

  // =========================

  // Agregar CSRF token si existe  // LISTA PRINCIPAL

  const csrfToken = document.querySelector('input[name="csrf_token"]')?.value  // =========================

    || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'); function renderList() {

      const mount = document.getElementById('projects-list-mount');

      if (csrfToken) {
        if (!mount) return;

        options.headers['X-CSRF-Token'] = csrfToken;

      }    // apply filters on projects dataset

      const inDate = (p) => {

        // Si es POST, agregar body      if (!projFilters.date?.length) return true;

        if (method === 'POST' && params) {
          const map = {

            if(params instanceof FormData) { Hoy: () => sameDay(new Date(), p.start),

              options.body = params; Semana: () => inRange(p.start, 7),

            } else {
          Mes: () => inRange(p.start, 31),

            options.headers['Content-Type'] = 'application/json'; Vencidos: () => (new Date(p.end) < new Date()),

              options.body = JSON.stringify(params);
        };

      }      return projFilters.date.some(tag => map[tag]?.());

    }
};

const sameDay = (d, iso) => new Date(iso).toISOString().slice(0, 10) === new Date(d).toISOString().slice(0, 10);

const response = await fetch(url, options); const inRange = (iso, days) => (Date.now() - Date.parse(iso)) <= days * dayMs;

const has = (arr, val) => !arr?.length || arr.includes(String(val || ''));

if (!response.ok) {
  const filtered = projects.filter(p =>

            throw new Error(`HTTP ${response.status}: ${response.statusText}`); inDate(p) &&

        }      has(projFilters.unit, p.unit) &&

  has(projFilters.status, p.status) &&

  let result; has(projFilters.owner, p.owner) &&

        try {
    has(projFilters.business, p.business)

    result = await response.json();    );

  } catch (e) {

    throw new Error('Respuesta no es JSON válido'); mount.innerHTML = `

        }      <div class="table-responsive">

                <table class="table table-sm align-middle tasks-table">

        ProjectModule.log(`API Response: ${ action } - ${ result.ok ? 'OK' : 'ERROR' } `, result.ok ? 'success' : 'error');          <thead>

                    <tr>

        return result;              <th>Proyecto</th>

                      <th>Responsable</th>

    } catch (error) {              <th>Unidad</th>

        ProjectModule.handleError(error, `API ${ action } `);              <th>Negocio</th>

        throw error;              <th>Inicio</th>

    }              <th>Fin</th>

};              <th>Avance</th>

              <th>Estatus</th>

// ========================================              <th class="text-end">Acciones</th>

// RENDERIZAR ÁRBOL DE PROYECTOS            </tr>

// ========================================          </thead>

          <tbody>

ProjectModule.renderProjectTree = function() {            ${filtered.map(p => `

    const treeRoot = document.getElementById('projectsTree');              <tr data-id="${esc(p.id)}" class="row-project">

    const emptyState = document.getElementById('emptyStateProjects');                <td>${esc(p.name)}</td>

    const projectsCount = document.getElementById('projects-count');                <td>${esc(p.owner)}</td>

                    <td>${esc(p.unit)}</td>

    if (!treeRoot || !emptyState || !projectsCount) {                <td>${esc(p.business)}</td>

        ProjectModule.log('Elementos del DOM no encontrados', 'warning');                <td>${esc(p.start)}</td>

        return;                <td>${esc(p.end)}</td>

    }                <td style="min-width:130px;">

                      <div class="progress" style="height:20px;">

    const proyectos = ProjectModule.state.proyectos;                    <div class="progress-bar" style="width:${Math.round(p.progress * 100)}%;">

    const tareasProyectos = ProjectModule.state.tareasProyectos;                      ${Math.round(p.progress * 100)}%

                        </div>

    if (proyectos.length === 0) {                  </div>

        emptyState.style.display = 'block';                </td>

        treeRoot.style.display = 'none';                <td><span class="badge ${p.status === 'En curso' ? 'bg-primary' : 'bg-secondary'}">${esc(p.status)}</span></td>

        projectsCount.textContent = '0 proyectos';                <td class="text-end">

        ProjectModule.log('No hay proyectos para mostrar', 'info');                  <div class="d-inline-flex gap-2">

        return;                    <a class="btn-act btn-act--blue" href="?tab=projects&project_id=${encodeURIComponent(p.id)}" title="Ver" aria-label="Ver">

    }                      <i class="bi bi-eye"></i>

                        </a>

    emptyState.style.display = 'none';                    <button class="btn-act btn-act--yellow" data-act="edit" title="Editar" aria-label="Editar">

    treeRoot.style.display = 'block';                      <i class="bi bi-pencil"></i>

    projectsCount.textContent = `${ proyectos.length } proyecto${ proyectos.length !== 1 ? 's' : '' }`;                    </button>

                        <button class="btn-act btn-act--red" data-act="delete" title="Eliminar" aria-label="Eliminar">

    treeRoot.innerHTML = proyectos.map(proyecto => {                      <i class="bi bi-trash"></i>

        const tareas = tareasProyectos.filter(t => t.proyecto === proyecto.id);                    </button>

                          </div>

        const tasksHTML = tareas.length > 0 ? tareas.map(tarea => `                </td >

            <li class="tree-task" draggable="true" data-task-id="${tarea.id}" data-project-id="${proyecto.id}" data-priority="${tarea.prioridad}">              </tr>

                <div class="tree-task-icon">            `).join('')}

                    <i class="bi bi-circle${tarea.status === 'Completada' ? '-fill text-success' : ''}"></i>          </tbody>

                </div >        </table >

    <span class="priority-dot ${tarea.nivel}"></span>      </div >

    <div class="tree-task-title">${ProjectModule.escapeHtml(tarea.descripcion)}</div>    `;

                <div class="tree-task-meta">

                    <span class="badge ${tarea.statusClass}">${ProjectModule.escapeHtml(tarea.status)}</span>    // Click en fila → navegar a detalle (evitar si clic en acciones)

                    <small><i class="bi bi-calendar3"></i> ${tarea.vence}</small>    mount.querySelectorAll('tr.row-project').forEach(tr => {

                </div>      tr.style.cursor = 'pointer';

                <div class="tree-task-actions">      tr.addEventListener('click', (e) => {

                    <button type="button" class="btn-task-edit" title="Editar">        if (e.target.closest('[data-act]')) return; // no navegar si se clickeó una acción

                        <i class="bi bi-pencil"></i>        const id = tr.dataset.id;

                    </button>        location.href = `? tab = projects & project_id=${ encodeURIComponent(id) }`;

                    <button type="button" class="btn-task-complete btn-success" title="Completar">      });

                        <i class="bi bi-check2"></i>    });

                    </button>

                    <button type="button" class="btn-task-delete btn-danger" title="Eliminar">    // Acciones Editar/Eliminar

                        <i class="bi bi-trash"></i>    mount.addEventListener('click', (e) => {

                    </button>      const btn = e.target.closest('[data-act]');

                </div>      if (!btn) return;

            </li>      e.stopPropagation();

        `).join('') : `      const tr = btn.closest('tr.row-project');

            <li class="tree-task" style="opacity: 0.6; cursor: default;">      const id = tr?.dataset.id;

                <div class="tree-task-icon"><i class="bi bi-inbox"></i></div>      if (!id) return;

                <div class="tree-task-title">Sin tareas asignadas</div>      if (btn.dataset.act === 'edit') {

            </li>        openEditProjectModal(id);

        `;
  } else if (btn.dataset.act === 'delete') {

    if (confirm('¿Eliminar este proyecto?')) {

      return `          projects = projects.filter(p => p.id !== id);

            <li class="tree-project">          renderList();

                <div class="tree-project-header" data-project-id="${proyecto.id}">        }

                    <div class="tree-toggle">      }

                        <i class="bi bi-chevron-right"></i>    });

                    </div>

                    <div class="tree-folder-icon">    // Modal “Nuevo/Subir proyecto”

                        <i class="bi bi-folder-fill"></i>    const btn = document.getElementById('btn-new-project');

                    </div>    btn?.addEventListener('click', () => openNewProjectModal());

                    <div class="tree-project-title">${ProjectModule.escapeHtml(proyecto.nombre)}</div>

                    <div class="tree-project-meta">    // Build multi-select dropdowns and chips

                        <span class="badge bg-secondary">${tareas.length} tarea${tareas.length !== 1 ? 's' : ''}</span>    buildMulti('dd-proj-date', ['Hoy', 'Semana', 'Mes', 'Vencidos'], projFilters.date, () => rerender());

                        <span class="badge ${proyecto.estadoClass}">${ProjectModule.escapeHtml(proyecto.estadoLabel)}</span>    buildMulti('dd-proj-unit', unique(projects.map(p => p.unit)), projFilters.unit, () => rerender());

                    </div>    buildMulti('dd-proj-status', unique(projects.map(p => p.status)), projFilters.status, () => rerender());

                    <div class="tree-project-actions">    buildMulti('dd-proj-owner', unique(projects.map(p => p.owner)), projFilters.owner, () => rerender());

                        <button type="button" class="btn-project-add" title="Agregar tarea">    // More filters (Negocio)

                            <i class="bi bi-plus-circle"></i>    buildChecklist('mf-proj-business', unique(projects.map(p => p.business)), projFilters.business);

                        </button>    document.getElementById('btn-more-filters-proj')?.addEventListener('click', () => new bootstrap.Modal('#more-filters-modal-proj').show());

                        <button type="button" class="btn-project-edit" title="Editar proyecto">    document.getElementById('more-filters-apply-proj')?.addEventListener('click', () => { rerender(); bootstrap.Modal.getInstance('#more-filters-modal-proj')?.hide(); });

                            <i class="bi bi-pencil"></i>    document.getElementById('btn-clear-filters-proj')?.addEventListener('click', () => { Object.keys(projFilters).forEach(k => projFilters[k] = []); rerender(true); });

                        </button>

                        <button type="button" class="btn-project-delete" title="Eliminar proyecto">    renderChips();

                            <i class="bi bi-trash"></i>

                        </button>    function rerender(clearDropdowns = false) {

                    </div>      renderList();

                </div>    }

                <ul class="tree-tasks">  }

                    ${tasksHTML}  // Helpers for filters UI

                </ul>  function unique(arr) { return Array.from(new Set(arr.filter(Boolean))); }

            </li>  function buildMulti(containerId, options, modelArr, onChange) {

        `; const c = document.getElementById(containerId);

    }).join(''); if (!c) return;

    const menu = c.querySelector('.dropdown-menu');

    ProjectModule.log(`Árbol renderizado: ${proyectos.length} proyectos`, 'success'); menu.innerHTML = options.map(opt => `

    ProjectModule.initTreeInteractions();      <label class="d-flex align-items-center gap-2 form-check mb-1">

};      <input class="form-check-input" type="checkbox" value="${esc(opt)}" ${modelArr.includes(opt) ? 'checked' : ''}>

      <span>${esc(opt)}</span>

ProjectModule.escapeHtml = function(str) {      </label>

    const div = document.createElement('div');    `).join('') + `<div class="mt-2 d-flex gap-2 justify-content-end"><button class="btn btn-sm btn-ghost" data-act="clear">Limpiar</button><button class="btn btn-sm btn-brand" data-act="apply">Aplicar</button></div>`;

    div.textContent = str || ''; const btn = c.querySelector('button.dropdown-toggle');

    return div.innerHTML; const syncBadge = () => {

    }; const n = modelArr.length;

    btn.innerHTML = btn.innerText.split(' ')[0] + (n ? ` <span class=\"badge bg-secondary\">${n}</span>` : '');

    // ========================================    };

    // INTERACCIONES DEL ÁRBOL    syncBadge();

    // ========================================    menu.addEventListener('click', (e) => {

    const t = e.target;

    ProjectModule.initTreeInteractions = function () {
      if (t.matches('input[type="checkbox"]')) {

        ProjectModule.log('Inicializando interacciones del árbol', 'info'); const v = t.value;

        const i = modelArr.indexOf(v);

        // Toggle expand/collapse        if (t.checked && i < 0) modelArr.push(v);

        document.querySelectorAll('.tree-project-header').forEach(header => {        else if (!t.checked && i >= 0) modelArr.splice(i, 1);

          header.addEventListener('click', function (e) { }

            if (e.target.closest('.tree-project-actions')) return; if (t.closest('[data-act="clear"]')) { modelArr.splice(0, modelArr.length);[...menu.querySelectorAll('input')].forEach(i => i.checked = false); }

          this.classList.toggle('expanded'); if (t.closest('[data-act="apply"]')) { onChange?.(); }

        });
      });

    });
  }



  // Expandir/Colapsar todos  function buildChecklist(containerId, options, modelArr) {

  const btnExpandAll = document.getElementById('btn-expand-all'); const c = document.getElementById(containerId);

  const btnCollapseAll = document.getElementById('btn-collapse-all'); if (!c) return;

  c.innerHTML = options.map(opt => `

    if (btnExpandAll) {      <label class="d-flex align-items-center gap-2 form-check mb-1">

        btnExpandAll.replaceWith(btnExpandAll.cloneNode(true));      <input class="form-check-input" type="checkbox" value="${esc(opt)}" ${modelArr.includes(opt) ? 'checked' : ''}>

        document.getElementById('btn-expand-all').addEventListener('click', () => {      <span>${esc(opt)}</span>

            document.querySelectorAll('.tree-project-header').forEach(h => h.classList.add('expanded'));      </label>

            ProjectModule.log('Todos los proyectos expandidos', 'info');    `).join('');

}); c.addEventListener('change', (e) => {

}      const t = e.target; if (t.name) { }

if (t.matches('input[type="checkbox"]')) {

  if (btnCollapseAll) {
    const v = t.value; const i = modelArr.indexOf(v);

    btnCollapseAll.replaceWith(btnCollapseAll.cloneNode(true)); if (t.checked && i < 0) modelArr.push(v); else if (!t.checked && i >= 0) modelArr.splice(i, 1);

    document.getElementById('btn-collapse-all').addEventListener('click', () => { }

            document.querySelectorAll('.tree-project-header').forEach(h => h.classList.remove('expanded'));
  });

  ProjectModule.log('Todos los proyectos colapsados', 'info');
}

        });

    }  function renderChips() {

  const host = document.getElementById('project-filter-chips');

  // Drag & Drop    if (!host) return;

  ProjectModule.initDragAndDrop(); const push = (label, arr) => arr.forEach(v => chips.push({ label, value: v }));

  const chips = [];

  // Acciones de proyectos    push('Fecha', projFilters.date);

  document.querySelectorAll('.btn-project-add').forEach(btn => {
    push('Unidad', projFilters.unit);

    btn.addEventListener('click', (e) => {
      push('Status', projFilters.status);

      e.stopPropagation(); push('Responsable', projFilters.owner);

      const projectId = btn.closest('.tree-project-header').dataset.projectId; push('Negocio', projFilters.business);

      ProjectModule.agregarTareaProyecto(projectId); host.innerHTML = chips.length ? chips.map(c => `<span class="badge rounded-pill text-bg-light me-2 mb-2">${esc(c.label)}: ${esc(c.value)} <button class="btn-close btn-close-white btn-sm ms-2" data-chip="${esc(c.label)}|${esc(c.value)}"></button></span>`).join('') : '';

    }); host.addEventListener('click', (e) => {

    }); const b = e.target.closest('button[data-chip]');

    if (!b) return; const [label, value] = String(b.dataset.chip).split('|');

    document.querySelectorAll('.btn-project-edit').forEach(btn => {
      const map = { 'Fecha': 'date', 'Unidad': 'unit', 'Status': 'status', 'Responsable': 'owner', 'Negocio': 'business' };

      btn.addEventListener('click', (e) => {
        const key = map[label]; if (!key) return;

        e.stopPropagation(); const arr = projFilters[key]; const i = arr.indexOf(value); if (i >= 0) arr.splice(i, 1);

        const projectId = btn.closest('.tree-project-header').dataset.projectId; renderList();

        ProjectModule.editarProyecto(projectId);
      });

    });
  }

    });

function openNewProjectModal() {

  document.querySelectorAll('.btn-project-delete').forEach(btn => {
    ensureProjectModal();

    btn.addEventListener('click', (e) => {
      const form = document.getElementById('project-form');

      e.stopPropagation(); form.reset();

      const projectId = btn.closest('.tree-project-header').dataset.projectId; form.elements.id.value = '';

      ProjectModule.eliminarProyecto(projectId); form.elements.name.value = '';

    }); populateProjectOwners();

  }); form.elements.owner.value = '';

  form.elements.unit.value = '';

  // Acciones de tareas    form.elements.business.value = '';

  document.querySelectorAll('.btn-task-complete').forEach(btn => {
    form.elements.start.value = toISO(Date.now());

    btn.addEventListener('click', (e) => {
      form.elements.end.value = toISO(Date.now());

      e.stopPropagation();    // fields removed: progress, status

      const taskId = btn.closest('.tree-task').dataset.taskId;

      ProjectModule.completarTarea(taskId); new bootstrap.Modal('#project-modal').show();

    });
  }

    });

function openEditProjectModal(id) {

  document.querySelectorAll('.btn-task-edit').forEach(btn => {
    ensureProjectModal();

    btn.addEventListener('click', (e) => {
      const p = projects.find(x => x.id === id);

      e.stopPropagation(); if (!p) return;

      const taskId = btn.closest('.tree-task').dataset.taskId; const form = document.getElementById('project-form');

      ProjectModule.editarTarea(taskId); form.reset();

    }); form.elements.id.value = p.id;

  }); form.elements.name.value = p.name || '';

  // populate owner select and set value (try by id or by name)

  document.querySelectorAll('.btn-task-delete').forEach(btn => {
    populateProjectOwners().then(() => {

      btn.addEventListener('click', (e) => {
        const sel = document.getElementById('project-owner-select');

        e.stopPropagation(); if (!sel) return;

        const taskId = btn.closest('.tree-task').dataset.taskId;      // try ownerId first

        ProjectModule.eliminarTarea(taskId); if (p.ownerId) sel.value = String(p.ownerId);

      });      else {

      }); const opt = Array.from(sel.options).find(o => o.text === p.owner || o.value === p.owner);

    if (opt) sel.value = opt.value;

    ProjectModule.log('Interacciones del árbol inicializadas', 'success');
  }

};    });

form.elements.unit.value = p.unit || '';

// ========================================    form.elements.business.value = p.business || '';

// DRAG & DROP PARA REORDENAR TAREAS    form.elements.start.value = p.start || '';

// ========================================    form.elements.end.value = p.end || '';

new bootstrap.Modal('#project-modal').show();

ProjectModule.initDragAndDrop = function () { }

const tasks = document.querySelectorAll('.tree-task[draggable="true"]');

function ensureProjectModal() {

  tasks.forEach(task => {
    if (document.getElementById('project-modal')) return;

    task.addEventListener('dragstart', ProjectModule.handleDragStart); document.body.insertAdjacentHTML('beforeend', `

        task.addEventListener('dragover', ProjectModule.handleDragOver);      <div class="modal fade" id="project-modal" tabindex="-1" aria-hidden="true">

        task.addEventListener('drop', ProjectModule.handleDrop);        <div class="modal-dialog modal-lg modal-dialog-scrollable">

        task.addEventListener('dragend', ProjectModule.handleDragEnd);          <div class="modal-content">

        task.addEventListener('dragenter', ProjectModule.handleDragEnter);            <div class="modal-header">

        task.addEventListener('dragleave', ProjectModule.handleDragLeave);              <h5 class="modal-title">Subir / Nuevo Proyecto</h5>

    });              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

                </div>

    ProjectModule.log(`Drag & Drop inicializado en ${ tasks.length } tareas`, 'info');            <div class="modal-body">

};              <form id="project-form" class="row g-3">

                <input type="hidden" name="id">

ProjectModule.handleDragStart = function(e) {                <div class="col-md-6">

    ProjectModule.state.draggedTask = this;                  <label class="form-label">Nombre *</label>

    this.classList.add('dragging');                  <input class="form-control" name="name" required maxlength="150">

    e.dataTransfer.effectAllowed = 'move';                </div>

    e.dataTransfer.setData('text/html', this.innerHTML);                <div class="col-md-6">

};                  <label class="form-label">Responsable *</label>

                  <select class="form-select" name="owner" required id="project-owner-select"><option value="">Selecciona...</option></select>

ProjectModule.handleDragOver = function(e) {                </div>

    if (e.preventDefault) {                <div class="col-md-4">

        e.preventDefault();                  <label class="form-label">Unidad</label>

    }                  <input class="form-control" name="unit">

    e.dataTransfer.dropEffect = 'move';                </div>

    return false;                <div class="col-md-4">

};                  <label class="form-label">Negocio</label>

                  <input class="form-control" name="business">

ProjectModule.handleDragEnter = function(e) {                </div>

    const draggedTask = ProjectModule.state.draggedTask;                <div class="col-md-2">

    if (this !== draggedTask && this.dataset.projectId === draggedTask.dataset.projectId) {                  <label class="form-label">Inicio</label>

        this.classList.add('drag-over');                  <input type="date" class="form-control" name="start">

        ProjectModule.state.draggedOverTask = this;                </div>

    }                <div class="col-md-2">

};                  <label class="form-label">Fin</label>

                  <input type="date" class="form-control" name="end">

ProjectModule.handleDragLeave = function(e) {                </div>

    this.classList.remove('drag-over');                <!-- Campos removidos: % Avance, Estatus, Archivo (opcional) -->

};              </form>

            </div>

ProjectModule.handleDrop = function(e) {            <div class="modal-footer">

    if (e.stopPropagation) {              <button class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button>

        e.stopPropagation();              <button class="btn btn-brand" id="project-save">Guardar</button>

    }            </div>

              </div>

    const draggedTask = ProjectModule.state.draggedTask;        </div>

          </div>

    if (draggedTask !== this && this.dataset.projectId === draggedTask.dataset.projectId) {    `);

    const parent = this.parentNode;

    const draggedIndex = Array.from(parent.children).indexOf(draggedTask); document.getElementById('project-save').onclick = () => {

      const droppedIndex = Array.from(parent.children).indexOf(this); const form = document.getElementById('project-form');

      if (!form.reportValidity()) return;

      if (draggedIndex < droppedIndex) {
        const fd = Object.fromEntries(new FormData(form).entries());

        parent.insertBefore(draggedTask, this.nextSibling);

      } else {      // Validación simple fechas

        parent.insertBefore(draggedTask, this); if (fd.start && fd.end && fd.end < fd.start) { alert('Fin no puede ser anterior a inicio'); return; }

      }

      const proj = {

        // Actualizar prioridades en el array        id: fd.id || `p${Date.now()}`,

        const projectId = parseInt(this.dataset.projectId); name: fd.name.trim(),

        const projectTasks = ProjectModule.state.tareasProyectos.filter(t => t.proyecto === projectId); owner: fd.owner.trim(),

        const taskElements = Array.from(parent.querySelectorAll('.tree-task[draggable="true"]')); unit: fd.unit?.trim() || '',

        business: fd.business?.trim() || '',

        taskElements.forEach((el, index) => {
          start: fd.start || '',

            const taskId = parseInt(el.dataset.taskId); end: fd.end || '',

            const task = ProjectModule.state.tareasProyectos.find(t => t.id === taskId);        // Defaults for removed fields

          if (task) {
            progress: 0,

              task.prioridad = index + 1; status: 'Planificado'

          }
        };

      }); const idx = projects.findIndex(x => x.id === proj.id);

  if (idx >= 0) projects[idx] = { ...projects[idx], ...proj };

  ProjectModule.log(`Orden actualizado para proyecto ${projectId}`, 'success');      else projects.unshift(proj);

} bootstrap.Modal.getInstance(document.getElementById('project-modal')).hide();

renderList();

return false;      // TODO: POST /api/projects

};    };

  }

ProjectModule.handleDragEnd = function (e) {

  this.classList.remove('dragging');  // =========================

  document.querySelectorAll('.tree-task').forEach(task => {  // DETALLE DE PROYECTO

    task.classList.remove('drag-over');  // =========================

  }); function renderDetail(id) {

    ProjectModule.state.draggedTask = null; const p = projects.find(x => x.id === id);

    ProjectModule.state.draggedOverTask = null; const title = document.getElementById('project-title');

  }; const summary = document.getElementById('project-summary');

  const tasksMount = document.getElementById('project-tasks-mount');

  // ========================================    const ganttMount = document.getElementById('project-gantt-mount');

  // FILTROS    if (!p || !title || !summary) return;

  // ========================================

  title.textContent = p.name;

  ProjectModule.applyFilters = function () {

    const filterSearch = document.getElementById('filter-search-projects'); summary.innerHTML = `

    const filterUnit = document.getElementById('filter-unit-projects');      <div class="row g-3">

    const filterStatus = document.getElementById('filter-status-projects');        <div class="col-md-3"><strong>Responsable:</strong> ${esc(p.owner)}</div>

            <div class="col-md-3"><strong>Unidad:</strong> ${esc(p.unit || '—')}</div>

    if (!filterSearch || !filterUnit || !filterStatus) {        <div class="col-md-3"><strong>Negocio:</strong> ${esc(p.business || '—')}</div>

        ProjectModule.log('Elementos de filtro no encontrados', 'warning');        <div class="col-md-3"><strong>Estatus:</strong> ${esc(p.status)}</div>

        return;        <div class="col-md-3"><strong>Inicio:</strong> ${esc(p.start || '—')}</div>

    }        <div class="col-md-3"><strong>Fin:</strong> ${esc(p.end || '—')}</div>

            <div class="col-md-6">

    const searchTerm = filterSearch.value.toLowerCase();          <strong>Avance:</strong>

    const selectedUnit = filterUnit.value;          <div class="progress mt-1" style="height:20px;">

    const selectedStatus = filterStatus.value;            <div class="progress-bar" style="width:${Math.round(p.progress * 100)}%">${Math.round(p.progress * 100)}%</div>

              </div>

    document.querySelectorAll('.tree-project').forEach(projectLi => {        </div>

        const header = projectLi.querySelector('.tree-project-header');      </div>

        const projectId = parseInt(header.dataset.projectId);    `;

    const proyecto = ProjectModule.state.proyectos.find(p => p.id === projectId);

    // Tareas del proyecto

    if (!proyecto) {
      const list = projectTasks[id] || [];

      projectLi.style.display = 'none'; tasksMount.innerHTML = `

            return;      <div class="table-responsive">

        }  <table class="table table-sm align-middle tasks-table">

                  <thead>

        const matchesSearch = proyecto.nombre.toLowerCase().includes(searchTerm);            <tr><th>Tarea</th><th>Responsable</th><th>Inicio</th><th>Fin</th><th>Estado</th></tr>

        const matchesUnit = !selectedUnit || proyecto.unidad === selectedUnit;          </thead>

        const matchesStatus = !selectedStatus || proyecto.estado === selectedStatus;          <tbody>

                    ${list.length ? list.map(t => `

        if (matchesSearch && matchesUnit && matchesStatus) {              <tr>

            projectLi.style.display = 'block';                <td>${esc(t.title)}</td>

        } else {                <td>${esc(t.assignee)}</td>

            projectLi.style.display = 'none';                <td>${esc(t.start)}</td>

        }                <td>${esc(t.end)}</td>

    });                <td><span class="badge ${t.status === 'En curso' ? 'bg-primary' : t.status === 'Completada' ? 'bg-success' : 'bg-secondary'}">${esc(t.status)}</span></td>

                  </tr>`).join('') : `<tr><td colspan="5" class="text-muted">Sin tareas.</td></tr>`}

    ProjectModule.log('Filtros aplicados', 'info');          </tbody>

};        </table>

      </div>

ProjectModule.clearFilters = function() {    `;

      const filterSearch = document.getElementById('filter-search-projects');

      const filterUnit = document.getElementById('filter-unit-projects');    // Gantt simplificado (reutiliza la idea anterior)

      const filterStatus = document.getElementById('filter-status-projects'); if (!list.length) { ganttMount.innerHTML = `<div class=\"text-muted\">Sin datos para Gantt.</div>`; return; }



      if (filterSearch) filterSearch.value = ''; const min = new Date(Math.min(...list.map(t => Date.parse(t.start))));

      if (filterUnit) filterUnit.value = ''; const max = new Date(Math.max(...list.map(t => Date.parse(t.end))));

      if (filterStatus) filterStatus.value = ''; const totalDays = Math.round((max - min) / dayMs) + 1;



      ProjectModule.applyFilters(); ganttMount.innerHTML = `

    ProjectModule.toast('Filtros limpiados', 'info');      <div class=\"table-responsive\" id=\"gantt-diagram\" style=\"cursor:pointer;\" title=\"Clic para ver detalle e imprimir\"> 

    ProjectModule.log('Filtros limpiados', 'info');        <table class=\"table table-sm align-middle tasks-table\">

};          <thead><tr><th style=\"width:220px;\">Tarea</th><th>Timeline</th></tr></thead>

          <tbody id=\"gantt-body\"></tbody>

// ========================================        </table>

// ACCIONES DE PROYECTOS      </div>

// ========================================      <div class=\"small text-muted\">Escala: ${totalDays} días (de ${toISO(min)} a ${toISO(max)}). Clic en el diagrama para abrir detalle.</div>

    `;

      ProjectModule.agregarTareaProyecto = function (projectId) {

        const proyecto = ProjectModule.state.proyectos.find(p => p.id == projectId); const tbody = document.getElementById('gantt-body');

        if (!proyecto) {
          list.forEach(t => {

            ProjectModule.toast('Proyecto no encontrado', 'error'); const s = Date.parse(t.start), e = Date.parse(t.end);

            return; const offsetDays = Math.round((s - min) / dayMs);

          }      const durDays = Math.max(1, Math.round((e - s) / dayMs) + 1);

          const left = (offsetDays / totalDays) * 100;

          ProjectModule.toast(`Agregar tarea al proyecto: ${proyecto.nombre}`, 'info'); const width = (durDays / totalDays) * 100;

          ProjectModule.log(`Acción: Agregar tarea al proyecto ${projectId}`, 'info');

          // TODO: Implementar modal de nueva tarea      const tr = document.createElement('tr');

        }; tr.innerHTML = `

        <td>${esc(t.title)}</td>

ProjectModule.editarProyecto = function(projectId) {        <td>

    const proyecto = ProjectModule.state.proyectos.find(p => p.id == projectId);          <div style="position:relative;height:28px;background:rgba(15,23,42,.06);border-radius:8px;">

    if (!proyecto) {            <div style="

        ProjectModule.toast('Proyecto no encontrado', 'error');              position:absolute;left:${left}%;width:${width}%;height:100%;

        return;              border-radius:8px;display:flex;align-items:center;justify-content:center;

    }              background:var(--primary,#1f4d9f);opacity:.9;">

                  <span class="text-white small">${durDays}d</span>

    ProjectModule.toast(`Editar proyecto: ${ proyecto.nombre } `, 'info');            </div>

    ProjectModule.log(`Acción: Editar proyecto ${ projectId } `, 'info');          </div>

    // TODO: Implementar modal de edición        </td>`;

      }; tbody.appendChild(tr);

    });

    ProjectModule.eliminarProyecto = function (projectId) {

      const proyecto = ProjectModule.state.proyectos.find(p => p.id == projectId);    // Nueva tarea dentro del proyecto → navegar a Tasks con contexto de proyecto

      if (!proyecto) {
        document.getElementById('btn-new-task-in-project')?.addEventListener('click', () => {

          ProjectModule.toast('Proyecto no encontrado', 'error'); const params = new URLSearchParams({ tab: 'tasks', new_task: '1', project_id: p.id, project_name: p.name, project_unit: p.unit || '', project_business: p.business || '' });

          return;      // Mantener misma página y solo cambiar query

        }      location.href = `?${params.toString()}`;

      });

      if (!ProjectModule.confirmDialog(`¿Eliminar el proyecto "${proyecto.nombre}" y todas sus tareas?`)) {

        return;    // Abrir modal premium de Gantt al hacer clic en el diagrama compacto

      } document.getElementById('gantt-diagram')?.addEventListener('click', () => {

        ensureGanttDetailModal();

        const index = ProjectModule.state.proyectos.findIndex(p => p.id == projectId); openGanttModal(p, list);

        if (index !== -1) { });

      ProjectModule.state.proyectos.splice(index, 1);
    }

    ProjectModule.renderProjectTree();

    ProjectModule.toast('Proyecto eliminado correctamente', 'success');  // Inject premium Gantt modal once

    ProjectModule.log(`Proyecto ${projectId} eliminado`, 'success'); function ensureGanttDetailModal() {

    } if (document.getElementById('gantt-detail-modal')) return;

  }; document.body.insertAdjacentHTML('beforeend', `

            <div class="modal fade" id="gantt-detail-modal" tabindex="-1" aria-hidden="true">

// ========================================              <div class="modal-dialog modal-xl modal-dialog-scrollable">

// ACCIONES DE TAREAS                <div class="modal-content">

// ========================================                  <div class="modal-header flex-wrap gap-2">

                    <div class="flex-grow-1">

ProjectModule.completarTarea = function(taskId) {                      <h5 class="modal-title mb-1">Diagrama de Gantt</h5>

    const tarea = ProjectModule.state.tareasProyectos.find(t => t.id == taskId);                      <div class="small text-muted" id="gantt-project-meta">—</div>

    if (!tarea) {                      <div class="d-flex align-items-center gap-2 mt-1">

        ProjectModule.toast('Tarea no encontrada', 'error');                        <span id="gantt-badge-status" class="badge bg-secondary">—</span>

        return;                        <div class="progress" style="height:8px;width:160px;">

    }                          <div class="progress-bar" id="gantt-progress" style="width:0%"></div>

                            </div>

    tarea.status = 'Completada';                      </div>

    tarea.statusClass = 'bg-success';                    </div>

    ProjectModule.renderProjectTree();                    <div class="d-flex align-items-center gap-2 flex-wrap">

    ProjectModule.toast('Tarea marcada como completada', 'success');                      <div class="btn-group" role="group" aria-label="Zoom">

    ProjectModule.log(`Tarea ${ taskId } completada`, 'success');                        <button class="btn btn-outline-dark" data-zoom="day" title="Día">Día</button>

};                        <button class="btn btn-outline-dark active" data-zoom="week" title="Semana">Semana</button>

                        <button class="btn btn-outline-dark" data-zoom="month" title="Mes">Mes</button>

ProjectModule.editarTarea = function(taskId) {                      </div>

    const tarea = ProjectModule.state.tareasProyectos.find(t => t.id == taskId);                      <div class="btn-group" role="group" aria-label="Estado">

    if (!tarea) {                        <button class="btn btn-outline-secondary active" data-state="all">Todos</button>

        ProjectModule.toast('Tarea no encontrada', 'error');                        <button class="btn btn-outline-secondary" data-state="En curso">En curso</button>

        return;                        <button class="btn btn-outline-secondary" data-state="Completada">Completadas</button>

    }                        <button class="btn btn-outline-secondary" data-state="Pendiente">Pendientes</button>

                          </div>

    ProjectModule.toast(`Editar tarea: ${ tarea.descripcion }`, 'info');                      <input type="date" class="form-control" id="gantt-go-date" style="width:160px" aria-label="Ir a fecha">

    ProjectModule.log(`Acción: Editar tarea ${ taskId }`, 'info');                      <button class="btn btn-outline-secondary" id="gantt-go-today" title="Ir a hoy">Hoy</button>

    // TODO: Implementar modal de edición                      <button class="btn btn-outline-dark" id="gantt-export-csv" title="Exportar CSV"><i class="bi bi-filetype-csv"></i></button>

};                      <button class="btn btn-outline-dark" id="gantt-print" title="Imprimir / PDF"><i class="bi bi-printer"></i></button>

                    </div>

ProjectModule.eliminarTarea = function(taskId) {                  </div>

    const tarea = ProjectModule.state.tareasProyectos.find(t => t.id == taskId);                  <div class="modal-body">

    if (!tarea) {                    <div id="gantt-scale-track" class="position-relative mb-2" style="min-height:28px;"></div>

        ProjectModule.toast('Tarea no encontrada', 'error');                    <div id="gantt-table" class="gantt-table" style="overflow:auto; white-space:nowrap;"></div>

        return;                  </div>

    }                  <div class="modal-footer justify-content-between">

                        <div class="small text-muted" id="gantt-summary-counts">0 tareas (0 completadas · 0 en curso · 0 pendientes)</div>

    if (!ProjectModule.confirmDialog(`¿Eliminar la tarea "${tarea.descripcion}" ? `)) {                    <div class="d-flex gap-2">

        return;                      <button class="btn btn-outline-dark" id="gantt-print-btm"><i class="bi bi-printer"></i> Imprimir / PDF</button>

    }                      <button class="btn btn-brand" data-bs-dismiss="modal">Listo</button>

                        </div>

    const index = ProjectModule.state.tareasProyectos.findIndex(t => t.id == taskId);                  </div>

    if (index !== -1) {                </div>

        ProjectModule.state.tareasProyectos.splice(index, 1);              </div>

        ProjectModule.renderProjectTree();            </div>`);

  ProjectModule.toast('Tarea eliminada correctamente', 'success');
}

ProjectModule.log(`Tarea ${taskId} eliminada`, 'success');

    }  function openGanttModal(project, tasks) {

};    // Header

const meta = document.getElementById('gantt-project-meta');

// ========================================    meta.textContent = `${project.name} · ${project.start} a ${project.end} · ${diffDays(project.start, project.end)} días`;

// FORMULARIO NUEVO PROYECTO    const badge = document.getElementById('gantt-badge-status');

// ========================================    badge.textContent = project.status;

badge.className = `badge ${badgeClass(project.status)}`;

ProjectModule.guardarProyecto = function () {
  document.getElementById('gantt-progress').style.width = `${Math.round((project.progress || 0) * 100)}%`;

  const form = document.getElementById('formNuevoProyecto');

  const btnGuardar = document.getElementById('btnGuardarProyecto');    // Defaults

  ganttZoom = 'week';

  if (!form) {
    ganttFilter = 'all';

    ProjectModule.log('Formulario no encontrado', 'error'); ganttRefDate = null;

    return;

  }    // Bind controls

  bindGanttControls(project, tasks);

  if (!form.checkValidity()) {    // Initial paint

    form.reportValidity(); renderGantt(project, tasks);

    return;

  } new bootstrap.Modal('#gantt-detail-modal').show();

}

const originalText = btnGuardar?.innerHTML || '';

function diffDays(a, b) { return Math.max(1, Math.round((Date.parse(b) - Date.parse(a)) / dayMs) + 1); }

if (btnGuardar) {
  function badgeClass(status) {

    btnGuardar.disabled = true; if (status === 'Completada') return 'bg-success';

    btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...'; if (status === 'En curso') return 'bg-primary';

  } if (status === 'Detenido') return 'bg-warning';

  return 'bg-secondary';

  try { }

        const formData = new FormData(form);

  const nuevoProyecto = { function bindGanttControls(project, tasks) {

    id: ProjectModule.state.proyectos.length + 1,    // Zoom

      nombre: formData.get('nombre'), document.querySelectorAll('#gantt-detail-modal [data-zoom]')?.forEach(btn => {

        unidad: formData.get('unidad'), btn.onclick = () => {

          estado: 'pendiente', document.querySelectorAll('#gantt-detail-modal [data-zoom].active').forEach(b => b.classList.remove('active'));

          estadoLabel: 'Pendiente', btn.classList.add('active');

          estadoClass: 'bg-warning', ganttZoom = btn.dataset.zoom;

          tareas: 0        renderGantt(project, tasks);

        };
      };

});

ProjectModule.state.proyectos.push(nuevoProyecto);    // State filter

document.querySelectorAll('#gantt-detail-modal [data-state]')?.forEach(btn => {

  // Cerrar modal      btn.onclick = () => {

  const modalElement = document.getElementById('modalNuevoProyecto'); document.querySelectorAll('#gantt-detail-modal [data-state].active').forEach(b => b.classList.remove('active'));

  if (modalElement) {
    btn.classList.add('active');

    const modal = bootstrap.Modal.getInstance(modalElement); ganttFilter = btn.dataset.state;

    if (modal) modal.hide(); renderGantt(project, tasks);

  }
};

            });

// Resetear formulario    // Go to date / today

form.reset(); const goDate = document.getElementById('gantt-go-date');

const goToday = document.getElementById('gantt-go-today');

// Re-renderizar árbol    goDate.value = toISO(Date.now());

ProjectModule.renderProjectTree(); goDate.onchange = () => { ganttRefDate = goDate.value || null; renderGantt(project, tasks); };

goToday.onclick = () => { ganttRefDate = toISO(Date.now()); document.getElementById('gantt-go-date').value = ganttRefDate; renderGantt(project, tasks); };

// Mostrar notificación    // Export / Print

ProjectModule.toast('Proyecto creado exitosamente', 'success'); const doPrint = () => window.print();

ProjectModule.log(`Proyecto creado: ${nuevoProyecto.nombre}`, 'success'); document.getElementById('gantt-print').onclick = doPrint;

document.getElementById('gantt-print-btm').onclick = doPrint;

// Expandir el nuevo proyecto    document.getElementById('gantt-export-csv').onclick = () => {

setTimeout(() => {
  const filtered = tasks.filter(t => ganttFilter === 'all' ? true : t.status === ganttFilter);

  const newProjectHeader = document.querySelector(`.tree-project-header[data-project-id="${nuevoProyecto.id}"]`); exportGanttCSV(project, filtered);

  if (newProjectHeader) { };

  newProjectHeader.classList.add('expanded');    // Keyboard shortcuts

  newProjectHeader.scrollIntoView({ behavior: 'smooth', block: 'center' }); const modalEl = document.getElementById('gantt-detail-modal');

}    const table = document.getElementById('gantt-table');

        }, 100); const onKey = (e) => {

  if (e.key === 'ArrowLeft') { table.scrollLeft -= 200; }

} catch (error) {      else if (e.key === 'ArrowRight') { table.scrollLeft += 200; }

  ProjectModule.handleError(error, 'Guardar proyecto');      else if (e.key === 'h' || e.key === 'H') { ganttRefDate = toISO(Date.now()); renderGantt(project, tasks); }

} finally {      else if (e.key === '1') { ganttZoom = 'day'; renderGantt(project, tasks); syncZoomBtns('day'); }

  if (btnGuardar) {      else if (e.key === '2') { ganttZoom = 'week'; renderGantt(project, tasks); syncZoomBtns('week'); }

    btnGuardar.disabled = false;      else if (e.key === '3') { ganttZoom = 'month'; renderGantt(project, tasks); syncZoomBtns('month'); }

    btnGuardar.innerHTML = originalText;
  };

} const syncZoomBtns = (val) => {

}      document.querySelectorAll('#gantt-detail-modal [data-zoom].active').forEach(b => b.classList.remove('active'));

}; document.querySelector(`#gantt-detail-modal [data-zoom="${val}"]`)?.classList.add('active');

    };

// ========================================    // Remove old handler to avoid duplicates

// DATOS MOCK INICIALES    modalEl.onkeydown = null;

// ========================================    modalEl.onkeydown = onKey;

  }

ProjectModule.loadMockData = function () {

  ProjectModule.log('Cargando datos mock...', 'info'); function renderGantt(project, tasks) {

    const list = tasks.filter(t => ganttFilter === 'all' ? true : t.status === ganttFilter);

    ProjectModule.state.proyectos = [    if (!list.length) {

      {
        document.getElementById('gantt-scale-track').innerHTML = '';

        id: 1, document.getElementById('gantt-table').innerHTML = '<div class="p-3 text-muted">Sin tareas para mostrar.</div>';

        nombre: "Remodelación Hotel Vergel", document.getElementById('gantt-summary-counts').textContent = '0 tareas (0 completadas · 0 en curso · 0 pendientes)';

        unidad: "Unidad Monterrey",       return;

        estado: "en-progreso",     }

      estadoLabel: "En Progreso",    const min = new Date(Math.min(...list.map(t => Date.parse(t.start)), Date.parse(project.start)));

      estadoClass: "bg-primary",    const max = new Date(Math.max(...list.map(t => Date.parse(t.end)), Date.parse(project.end)));

      tareas: 3     // Scale

    }, const scale = document.getElementById('gantt-scale-track');

    {
      scale.innerHTML = '';

      id: 2, drawScale(scale, min, max, ganttZoom);

      nombre: "Implementación CRM",     // Rows

        unidad: "Unidad CDMX",     const table = document.getElementById('gantt-table');

      estado: "pendiente", table.innerHTML = '';

      estadoLabel: "Pendiente",

        estadoClass: "bg-warning",    // Summary

          tareas: 2    const cAll = list.length, cDone = list.filter(t => t.status === 'Completada').length, cRun = list.filter(t => t.status === 'En curso').length, cPend = list.filter(t => t.status === 'Pendiente').length;

    }, document.getElementById('gantt-summary-counts').textContent = `${cAll} tareas (${cDone} completadas · ${cRun} en curso · ${cPend} pendientes)`;

    {

      id: 3,     const todayIso = toISO(Date.now());

      nombre: "Nueva Sede Cancún",     const todayPct = positionPct(min, max, todayIso);

      unidad: "Unidad Guadalajara",

        estado: "completado", list.forEach(t => {

          estadoLabel: "Completado",       const statusClass = 'status-' + String(t.status || '').toLowerCase().replace(/\s+/g, '-');

          estadoClass: "bg-success",      const row = document.createElement('div');

          tareas: 2      row.className = 'gantt-row';

        }, row.innerHTML = `

        {                   <div class="gantt-cell fixed"><div class="fw-semibold">${esc(t.title)}</div></div>

            id: 4,                   <div class="gantt-cell fixed">${esc(t.assignee)}</div>

            nombre: "Actualización Sistema Nómina",                   <div class="gantt-cell flex-grow-1">

            unidad: "Unidad CDMX",                     <div class="gantt-track">

            estado: "en-progreso",            <div class="gantt-bar ${statusClass}"

            estadoLabel: "En Progreso",                            title="${esc(t.title)}\nResponsable: ${esc(t.assignee)}\n${esc(t.start)} → ${esc(t.end)} (${diffDays(t.start, t.end)}d)\nEstado: ${esc(t.status)}"

            estadoClass: "bg-primary",                           style="left:${positionPct(min, max, t.start)}%; width:${widthPct(min, max, t.start, t.end)}%">

            tareas: 0                        <span>${diffDays(t.start, t.end)}d</span>

        },                      </div>

        {                       <div class="gantt-today-line" style="left:${todayPct}%"></div>

            id: 5,                     </div>

            nombre: "Expansión Restaurant La Terraza",                   </div>`;

      unidad: "Unidad Guadalajara", table.appendChild(row);

      estado: "pendiente",    });

    estadoLabel: "Pendiente",

      estadoClass: "bg-warning",    // Scroll to ref date

        tareas: 0    if (ganttRefDate) {

        } const pct = positionPct(min, max, ganttRefDate);

    ]; table.scrollLeft = Math.max(0, (table.scrollWidth * pct / 100) - 200);

  }

  ProjectModule.state.tareasProyectos = [  }

{ id: 1, proyecto: 1, descripcion: "Instalación eléctrica", vence: "2025-11-09", status: "En Progreso", statusClass: "bg-primary", nivel: "alta", prioridad: 1 },

{ id: 2, proyecto: 1, descripcion: "Pintura habitaciones", vence: "2025-11-10", status: "Pendiente", statusClass: "bg-warning", nivel: "media", prioridad: 2 }, function drawScale(container, start, end, zoom) {

  { id: 3, proyecto: 1, descripcion: "Cambio de mobiliario", vence: "2025-11-12", status: "Pendiente", statusClass: "bg-warning", nivel: "alta", prioridad: 3 }, const total = diffDays(start, end);

  { id: 4, proyecto: 2, descripcion: "Configuración base de datos", vence: "2025-11-15", status: "En Progreso", statusClass: "bg-primary", nivel: "urgente", prioridad: 1 }, const one = (d) => new Date(start.getTime() + d * dayMs);

  { id: 5, proyecto: 2, descripcion: "Capacitación usuarios", vence: "2025-11-20", status: "Pendiente", statusClass: "bg-warning", nivel: "media", prioridad: 2 }, container.style.minHeight = '28px';

  { id: 6, proyecto: 3, descripcion: "Permisos y licencias", vence: "2025-10-30", status: "Completada", statusClass: "bg-success", nivel: "alta", prioridad: 1 }, if (zoom === 'day') {

    { id: 7, proyecto: 3, descripcion: "Construcción sede", vence: "2025-12-31", status: "En Progreso", statusClass: "bg-primary", nivel: "urgente", prioridad: 2 } for (let i = 0; i <= total; i++) {

    ]; const pct = (i / total) * 100;

      const tick = document.createElement('div'); tick.className = 'gantt-tick'; tick.style.left = pct + '%';

      ProjectModule.log(`Datos cargados: ${ProjectModule.state.proyectos.length} proyectos, ${ProjectModule.state.tareasProyectos.length} tareas`, 'success'); const lbl = document.createElement('div'); lbl.className = 'gantt-tick-label'; lbl.style.left = pct + '%'; lbl.textContent = toISO(one(i)).slice(5);

    }; container.append(tick, lbl);

  }

  // ========================================    } else if (zoom === 'week') {

  // INICIALIZACIÓN PRINCIPAL      for (let i = 0; i <= total; i += 7) {

  // ========================================        const pct = (i / total) * 100;

  const tick = document.createElement('div'); tick.className = 'gantt-tick'; tick.style.left = pct + '%';

  ProjectModule.init = function () {
    const lbl = document.createElement('div'); lbl.className = 'gantt-tick-label'; lbl.style.left = pct + '%'; lbl.textContent = 'Sem ' + weekNumber(one(i));

    ProjectModule.log('Inicializando módulo de proyectos...', 'info'); container.append(tick, lbl);

  }

  try { } else {

    // Cargar datos mock (reemplazar con API más adelante)      let d = new Date(start); d.setDate(1);

    ProjectModule.loadMockData(); while (d <= end) {

      const offset = Math.max(0, Math.round((d - start) / dayMs));

      // Renderizar árbol inicial        const pct = (offset / total) * 100;

      ProjectModule.renderProjectTree(); const tick = document.createElement('div'); tick.className = 'gantt-tick'; tick.style.left = pct + '%';

      const lbl = document.createElement('div'); lbl.className = 'gantt-tick-label'; lbl.style.left = pct + '%'; lbl.textContent = d.toLocaleString('es-MX', { month: 'short', year: '2-digit' });

      // Configurar listeners de filtros        container.append(tick, lbl);

      const filterSearch = document.getElementById('filter-search-projects'); d.setMonth(d.getMonth() + 1);

      const filterUnit = document.getElementById('filter-unit-projects');
    }

    const filterStatus = document.getElementById('filter-status-projects');
  }

  const btnClearFilters = document.getElementById('btn-clear-filters');
}



if (filterSearch) {
  function positionPct(start, end, iso) {

    filterSearch.addEventListener('input', ProjectModule.applyFilters); const s = start.getTime(), e = end.getTime(), x = Date.parse(iso);

  } return Math.min(100, Math.max(0, ((x - s) / (e - s)) * 100));

}

if (filterUnit) {
  function widthPct(start, end, sIso, eIso) {

    filterUnit.addEventListener('change', ProjectModule.applyFilters); const s = Date.parse(sIso), e = Date.parse(eIso), S = start.getTime(), E = end.getTime();

  } const left = Math.max(0, (s - S) / (E - S));

  const right = Math.min(1, (e - S) / (E - S));

  if (filterStatus) {
    return Math.max(0.02, (right - left)) * 100;

    filterStatus.addEventListener('change', ProjectModule.applyFilters);
  }

} function weekNumber(d) {

  d = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));

  if (btnClearFilters) {
    const dayNum = d.getUTCDay() || 7;

    btnClearFilters.addEventListener('click', ProjectModule.clearFilters); d.setUTCDate(d.getUTCDate() + 4 - dayNum);

  } const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));

  return Math.ceil((((d - yearStart) / dayMs) + 1) / 7);

  // Configurar modal de nuevo proyecto  }

  const btnGuardarProyecto = document.getElementById('btnGuardarProyecto'); function exportGanttCSV(project, tasks) {

    if (btnGuardarProyecto) {
      const headers = ['Tarea', 'Responsable', 'Inicio', 'Fin', 'Duración(d)', 'Estado'];

      btnGuardarProyecto.addEventListener('click', function (e) {
        const rows = tasks.map(t => [t.title, t.assignee, t.start, t.end, diffDays(t.start, t.end), t.status]);

        e.preventDefault(); const csv = [headers.join(','), ...rows.map(r => r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(','))].join('\n');

        ProjectModule.guardarProyecto(); const a = document.createElement('a');

      }); a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));

    } a.download = `gantt_${project.name.replace(/\s+/g, '_')}.csv`;

    a.click();

    ProjectModule.log('Módulo de proyectos inicializado correctamente', 'success');
  }

}) ();

    } catch (error) {                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            null;

  ProjectModule.handleError(error, 'Inicialización del módulo');
}

    }

};  // Función para mostrar toasts

function showToast(message, type = 'success') {

  // ========================================    if (typeof window.showToast === 'function') {

  // AUTO-INICIALIZACIÓN      window.showToast(message, type);

  // ========================================    } else {

  console.log(`[Toast ${type}] ${message}`);

  document.addEventListener('DOMContentLoaded', () => { }

    ProjectModule.init();
}

});

// Función para poblar el select de usuarios

// ========================================  function populateUserSelect() {

// EXPORTAR MÓDULO (Compatibilidad)    const select = document.getElementById('task-assignee');

// ========================================    if (!select) return;



if (typeof module !== 'undefined' && module.exports) {    // Obtener usuarios desde el estado de organigrama o mock data

  module.exports = ProjectModule; const users = [

} { id: '1', name: 'Ana López' },

{ id: '2', name: 'Luis Pérez' },
{ id: '3', name: 'Carlos Ruiz' },
{ id: '4', name: 'María García' },
{ id: '5', name: 'Juan Martínez' }
    ];

select.innerHTML = '<option value="">Sin asignar</option>';
users.forEach(user => {
  const option = document.createElement('option');
  option.value = user.id;
  option.textContent = user.name;
  select.appendChild(option);
});
  }

// Función para renderizar Gantt con tareas de localStorage
function renderDynamicGantt(projectId) {
  const ganttMount = document.getElementById('project-gantt-mount');
  if (!ganttMount) return;

  const storedTasks = getTasksForProject(projectId);
  const originalTasks = projectTasks[projectId] || [];

  // Combinar tareas originales (mock) con las almacenadas
  const allTasks = [
    ...originalTasks.map(t => ({ ...t, source: 'original' })),
    ...storedTasks.map(t => ({
      title: t.name,
      assignee: t.assignedToName || 'Sin asignar',
      start: t.startDate,
      end: t.endDate,
      status: t.status,
      id: t.id,
      source: 'stored'
    }))
  ];

  if (allTasks.length === 0) {
    ganttMount.innerHTML = `
        <div class="text-center text-muted p-4">
          <i class="bi bi-calendar-plus fs-1 mb-2 d-block"></i>
          <p class="mb-0">Aún no hay tareas en este proyecto.</p>
          <p class="small">Agrega una nueva para comenzar.</p>
        </div>
      `;
    return;
  }

  // Renderizar tabla simple de Gantt
  const today = new Date();
  const minDate = new Date(Math.min(...allTasks.map(t => Date.parse(t.start))));
  const maxDate = new Date(Math.max(...allTasks.map(t => Date.parse(t.end))));

  ganttMount.innerHTML = `
      <div class="table-responsive gantt-diagram" id="gantt-diagram"> 
        <table class="table table-sm table-hover">
          <thead>
            <tr>
              <th style="min-width: 200px;">Tarea</th>
              <th style="min-width: 120px;">Responsable</th>
              <th style="min-width: 100px;">Inicio</th>
              <th style="min-width: 100px;">Fin</th>
              <th style="min-width: 100px;">Estado</th>
              <th style="min-width: 300px;">Timeline</th>
            </tr>
          </thead>
          <tbody id="gantt-body"></tbody>
        </table>
      </div>
    `;

  const tbody = document.getElementById('gantt-body');
  const totalDays = Math.ceil((maxDate - minDate) / dayMs) + 5;

  allTasks.forEach(task => {
    const statusClass = getStatusBadgeClass(task.status);
    const statusColor = getStatusColor(task.status);
    const duration = Math.ceil((Date.parse(task.end) - Date.parse(task.start)) / dayMs);
    const startPercent = ((Date.parse(task.start) - minDate) / (maxDate - minDate)) * 100;
    const widthPercent = (duration / totalDays) * 100;

    const row = document.createElement('tr');
    row.className = 'reveal';
    if (task.source === 'stored') {
      row.dataset.taskId = task.id;
      row.style.cursor = 'context-menu';
    }

    row.innerHTML = `
        <td class="fw-medium">${esc(task.title)}</td>
        <td class="text-muted small">${esc(task.assignee)}</td>
        <td class="small">${task.start}</td>
        <td class="small">${task.end}</td>
        <td><span class="badge ${statusClass}">${esc(task.status)}</span></td>
        <td class="position-relative" style="height: 40px;">
          <div class="gantt-bar" 
               style="position: absolute; top: 8px; left: ${startPercent}%; width: ${widthPercent}%; height: 24px; background-color: ${statusColor}; border-radius: 4px; box-shadow: var(--shadow);"
               title="Tarea: ${esc(task.title)} • Responsable: ${esc(task.assignee)} • Estado: ${esc(task.status)}">
          </div>
        </td>
      `;
    tbody.appendChild(row);

    // Agregar menú contextual para tareas almacenadas
    if (task.source === 'stored') {
      row.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        showContextMenu(e, task);
      });
    }
  });
}

function getStatusBadgeClass(status) {
  switch (status) {
    case 'Completada': return 'bg-success';
    case 'En progreso': return 'bg-primary';
    case 'Pendiente': return 'bg-secondary';
    default: return 'bg-secondary';
  }
}

function getStatusColor(status) {
  switch (status) {
    case 'Completada': return '#2bb673'; // Verde Índice
    case 'En progreso': return '#1f4d9f'; // Azul Índice  
    case 'Pendiente': return '#e5e7eb'; // Gris claro
    default: return '#e5e7eb';
  }
}

// Menú contextual para cambiar estado
function showContextMenu(event, task) {
  // Remover menú existente si lo hay
  const existingMenu = document.getElementById('task-context-menu');
  if (existingMenu) existingMenu.remove();

  const menu = document.createElement('div');
  menu.id = 'task-context-menu';
  menu.className = 'dropdown-menu show';
  menu.style.position = 'fixed';
  menu.style.left = event.clientX + 'px';
  menu.style.top = event.clientY + 'px';
  menu.style.zIndex = '9999';

  menu.innerHTML = `
      <h6 class="dropdown-header">${esc(task.title)}</h6>
      <button class="dropdown-item" data-status="Pendiente">Marcar como Pendiente</button>
      <button class="dropdown-item" data-status="En progreso">Marcar como En progreso</button>
      <button class="dropdown-item" data-status="Completada">Marcar como Completada</button>
      <div class="dropdown-divider"></div>
      <button class="dropdown-item text-danger" data-action="delete">Eliminar tarea</button>
    `;

  document.body.appendChild(menu);

  // Event listeners para las opciones del menú
  menu.addEventListener('click', (e) => {
    if (e.target.dataset.status) {
      const newStatus = e.target.dataset.status;
      updateTaskStatus(task.id, newStatus);
      showToast(`🎉 Tarea marcada como ${newStatus}`, 'success');
      renderDynamicGantt(projectId); // Re-renderizar
    } else if (e.target.dataset.action === 'delete') {
      if (confirm(`¿Estás seguro de que quieres eliminar la tarea "${task.title}"?`)) {
        deleteTask(task.id);
        showToast('Tarea eliminada correctamente', 'success');
        renderDynamicGantt(projectId);
      }
    }
    menu.remove();
  });

  // Cerrar menú al hacer clic fuera
  setTimeout(() => {
    document.addEventListener('click', function closeMenu() {
      menu.remove();
      document.removeEventListener('click', closeMenu);
    });
  }, 100);
}

function deleteTask(taskId) {
  const tasks = getTasksFromStorage();
  const filteredTasks = tasks.filter(t => t.id !== taskId);
  saveTasksToStorage(filteredTasks);
}

// Event listeners para el modal de agregar tarea
if (projectId) {
  document.addEventListener('DOMContentLoaded', () => {
    populateUserSelect();

    // Botón para abrir modal
    const addTaskBtn = document.getElementById('btn-add-task-gantt');
    if (addTaskBtn) {
      addTaskBtn.addEventListener('click', () => {
        const modal = new bootstrap.Modal(document.getElementById('add-task-modal'));
        // Establecer fechas por defecto
        const today = new Date().toISOString().split('T')[0];
        const nextWeek = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
        document.getElementById('task-start-date').value = today;
        document.getElementById('task-end-date').value = nextWeek;
        modal.show();
      });
    }

    // Botón para guardar tarea
    const saveTaskBtn = document.getElementById('save-task-btn');
    if (saveTaskBtn) {
      saveTaskBtn.addEventListener('click', () => {
        const form = document.getElementById('add-task-form');
        const formData = new FormData(form);

        const taskName = document.getElementById('task-name').value.trim();
        const assignedTo = document.getElementById('task-assignee').value;
        const assignedToName = document.getElementById('task-assignee').selectedOptions[0]?.textContent || '';
        const startDate = document.getElementById('task-start-date').value;
        const endDate = document.getElementById('task-end-date').value;
        const status = document.getElementById('task-status').value;

        if (!taskName) {
          showToast('El nombre de la tarea es requerido', 'error');
          return;
        }

        if (startDate && endDate && Date.parse(startDate) > Date.parse(endDate)) {
          showToast('La fecha de inicio no puede ser posterior a la fecha de fin', 'error');
          return;
        }

        const newTask = addTaskToProject(projectId, {
          name: taskName,
          assignedTo: assignedTo,
          assignedToName: assignedToName,
          startDate: startDate,
          endDate: endDate,
          status: status
        });

        showToast('✅ Tarea agregada correctamente', 'success');
        showToast('📅 Gantt actualizado', 'info');

        // Limpiar formulario
        form.reset();

        // Cerrar modal
        bootstrap.Modal.getInstance(document.getElementById('add-task-modal')).hide();

        // Re-renderizar Gantt
        renderDynamicGantt(projectId);
      });
    }

    // Renderizar Gantt inicial con tareas existentes
    renderDynamicGantt(projectId);
  });
}

}) ();
