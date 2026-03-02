// /modules/processes_tasks/js/tasks.js
console.log('[Processes & Tasks] TASKS JS loaded');

document.addEventListener('DOMContentLoaded', () => {
  const mount = document.getElementById('tasks-mount');
  if (!mount) return;

  // Debug probe
  try {
    const probe = document.createElement('div');
    probe.className = 'text-muted mb-2';
    probe.textContent = '[DEBUG] JS montado en TASKS';
    mount.appendChild(probe);
  } catch { }

  // --- Runtime data (populated from APIs). If APIs fail, some demo fallback remains.
  let USERS = [];
  let nextId = 100000;
  let nextAttId = 100000;
  let tasks = [];

  // Normalize a DB row / API item into the internal task shape used by the UI
  function mapServerRowToTask(t) {
    return {
      id: Number(t.id),
      title: t.title || t.name || '',
      desc: t.description ?? t.desc ?? '',
      assigneeId: t.assigneeId ?? (t.assignee_hr_id ? Number(t.assignee_hr_id) : (t.assignee_hr_id === null ? null : undefined)),
      delegateId: t.delegateId ?? (t.delegate_hr_id ? Number(t.delegate_hr_id) : (t.delegate_hr_id === null ? null : undefined)),
      priority: t.priority || 'Media',
      status: t.status || 'Pendiente',
      start: t.start || t.start_date || todayISO(),
      due: t.due || t.due_date || todayISO(),
      businessUnit: t.businessUnit || t.business_unit || t.unit || '',
      business: t.business || '',
      department: t.department || '',
      projectId: t.project_id || t.projectId || '',
      projectName: t.project_name || t.projectName || '',
      auditScore: t.audit_score ?? null,
      auditedAt: t.audited_at || null,
      auditedBy: t.audited_by ?? null,
      attachments: t.attachments || []
    };
  }

  async function loadTasksFromServer() {
    try {
      const res = await fetch('/modules/processes_tasks/api/list.php', { credentials: 'same-origin' });
      const j = await res.json().catch(() => null);
      if (j && j.ok && Array.isArray(j.items)) {
        tasks = j.items.map(t => mapServerRowToTask(t));
        return true;
      }
    } catch (e) { console.warn('loadTasksFromServer failed', e); }
    return false;
  }

  async function loadHRUsers() {
    try {
      // Use module-local endpoint which lists HR employees for the current company
      const res = await fetch('/modules/processes_tasks/api/hr_users.php', { credentials: 'same-origin' });
      const j = await res.json().catch(() => null);
      if (j && j.ok && Array.isArray(j.items)) {
        USERS = j.items.map(u => ({ id: Number(u.id), name: u.name || u.full_name || u.name || u.code || String(u.id) }));
        return true;
      }
    } catch (e) { console.warn('loadHRUsers failed', e); }
    // fallback demo users
    USERS = [{ id: 1, name: 'Demo User 1' }, { id: 2, name: 'Demo User 2' }];
    return false;
  }

  // --- State: filters (multi-select) and inline-edit
  const filters = { unit: [], business: [], collab: [], dept: [], status: [], date: [] };
  // Column ordering and labels similar to HR table
  const DEFAULT_COLUMNS_ORDER = ['id', 'title', 'desc', 'project', 'auditScore', 'department', 'unit', 'business', 'start', 'due', 'status', 'assignee', 'priority', 'evidence', 'delegate'];
  let columnsOrder = [...DEFAULT_COLUMNS_ORDER];
  const columnsLabels = {
    id: 'ID',
    title: 'Nombre',
    desc: 'Descripción',
    project: 'Proyecto',
    auditScore: 'Ponderación',
    department: 'Depto',
    unit: 'Unidad',
    business: 'Negocio',
    start: 'Ingreso',
    due: 'Salida',
    status: 'Estado',
    assignee: 'Responsable',
    priority: 'Prioridad',
    evidence: 'Evid.',
    delegate: 'Delegado',
  };
  const columnsResponsive = { delegate: 'd-none d-md-table-cell' };
  const columnsVisible = {
    id: true,
    title: true,
    desc: true,
    project: true,
    auditScore: true,
    department: true,
    unit: true,
    business: true,
    start: true,
    due: true,
    status: true,
    assignee: false,
    priority: false,
    evidence: false,
    delegate: false,
  };
  // Persisted visibility via Columns modal (UI maps to these keys)
  const COLS_KEY = 'tasks_columns_v1';
  const ORDER_KEY = 'tasks_columns_order_v1';
  let colVisibility = JSON.parse(localStorage.getItem(COLS_KEY) || 'null') || {
    col_title: true,
    col_desc: true,
    col_project: true,
    col_auditScore: true,
    col_assignee: true,
    col_delegate: true,
    col_priority: true,
    col_start: true,
    col_due: true,
    col_status: true,
    col_unit: true,
    col_business: true,
    col_evidence: true,
    col_actions: true,
  };
  // Backfill for users with older localStorage (missing new keys)
  if (colVisibility.col_project === undefined) { colVisibility.col_project = true; localStorage.setItem(COLS_KEY, JSON.stringify(colVisibility)); }
  if (colVisibility.col_auditScore === undefined) { colVisibility.col_auditScore = true; localStorage.setItem(COLS_KEY, JSON.stringify(colVisibility)); }
  if (colVisibility.col_desc === undefined) { colVisibility.col_desc = true; localStorage.setItem(COLS_KEY, JSON.stringify(colVisibility)); }
  function saveCols() { localStorage.setItem(COLS_KEY, JSON.stringify(colVisibility)); }
  let actionsVisible = true;
  function applyColVisibilityToState() {
    // Map modal flags to internal columnsVisible
    columnsVisible.title = !!colVisibility.col_title;
    columnsVisible.desc = !!colVisibility.col_desc;
    columnsVisible.assignee = !!colVisibility.col_assignee;
    columnsVisible.project = !!colVisibility.col_project;
    columnsVisible.auditScore = !!colVisibility.col_auditScore;
    columnsVisible.delegate = !!colVisibility.col_delegate;
    columnsVisible.priority = !!colVisibility.col_priority;
    columnsVisible.start = !!colVisibility.col_start;
    columnsVisible.due = !!colVisibility.col_due;
    columnsVisible.status = !!colVisibility.col_status;
    columnsVisible.unit = !!colVisibility.col_unit;
    columnsVisible.business = !!colVisibility.col_business;
    columnsVisible.evidence = !!colVisibility.col_evidence;
    // keep id and department always visible for now
    columnsVisible.id = true;
    columnsVisible.department = true;
    actionsVisible = !!colVisibility.col_actions;
  }
  // Initialize from persisted state
  applyColVisibilityToState();
  // Restore column order if saved
  try {
    const savedOrder = JSON.parse(localStorage.getItem(ORDER_KEY) || 'null');
    if (Array.isArray(savedOrder) && savedOrder.length) {
      // keep only known keys and preserve any new ones at the end
      const known = new Set(DEFAULT_COLUMNS_ORDER);
      const filtered = savedOrder.filter(k => known.has(k));
      const missing = DEFAULT_COLUMNS_ORDER.filter(k => !filtered.includes(k));
      columnsOrder = [...filtered, ...missing];
    }
  } catch { }
  let sort = { key: null, dir: 'asc' }; // keys per columnsOrder
  const selected = new Set(); // task ids
  let currentEvidenceIndex = null;
  let editIndex = null; // modal edit mode

  // --- Helpers
  function todayISO() { return new Date().toISOString().slice(0, 10); }
  function addDaysISO(n) { const d = new Date(); d.setDate(d.getDate() + n); return d.toISOString().slice(0, 10); }
  const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  const uName = id => (USERS.find(u => u.id === id) || {}).name || '—';
  const fmtBytes = n => {
    if (!n && n !== 0) return '—';
    const u = ['B', 'KB', 'MB', 'GB']; let i = 0, v = n;
    while (v >= 1024 && i < u.length - 1) { v /= 1024; i++; }
    return v.toFixed(v < 10 ? 1 : 0) + ' ' + u[i];
  };
  const badgeStatus = s => {
    const map = { 'Pendiente': 'secondary', 'En curso': 'warning text-dark', 'Pausada': 'dark', 'Completada': 'success', 'Auditada': 'info' };
    const cls = map[s] || 'secondary';
    return `<span class="badge bg-${cls}">${esc(s)}</span>`;
  };
  const badgePriority = p => {
    const map = { 'Baja': 'secondary', 'Media': 'primary', 'Alta': 'danger' };
    const cls = map[p] || 'secondary';
    return `<span class="badge bg-${cls}">${esc(p)}</span>`;
  };

  // --- Minimal MultiSelect UI (dropdown with checkboxes) for header filters
  function buildMultiSelect(id, label) {
    const sel = document.getElementById(id);
    if (!sel || sel.dataset.msBuilt) return;
    sel.dataset.msBuilt = '1';
    sel.classList.add('d-none');
    const wrap = document.createElement('div');
    wrap.className = 'dropdown';
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-outline-secondary btn-sm dropdown-toggle';
    btn.setAttribute('data-bs-toggle', 'dropdown');
    btn.textContent = label;
    const menu = document.createElement('div');
    menu.className = 'dropdown-menu p-2';
    menu.style.maxHeight = '280px';
    menu.style.overflow = 'auto';
    wrap.appendChild(btn);
    wrap.appendChild(menu);
    sel.parentNode.insertBefore(wrap, sel.nextSibling);
    const renderMenu = () => {
      menu.innerHTML = Array.from(sel.options).map(o =>
        `<label class="dropdown-item form-check m-0">
          <input class="form-check-input me-2" type="checkbox" value="${esc(o.value)}" ${o.selected ? 'checked' : ''}>
          ${esc(o.text)}
         </label>`
      ).join('');
    };
    const updateBtn = () => {
      const count = Array.from(sel.selectedOptions || []).length;
      btn.textContent = count ? `${label} (${count})` : `${label} (todas)`;
      btn.classList.toggle('btn-primary', !!count);
      btn.classList.toggle('btn-outline-secondary', !count);
      btn.classList.add('btn-sm');
    };
    renderMenu();
    updateBtn();
    menu.addEventListener('change', (e) => {
      const cb = e.target.closest('input[type=checkbox]');
      if (!cb) return;
      const val = cb.value;
      const opt = Array.from(sel.options).find(o => o.value === val);
      if (opt) opt.selected = cb.checked;
      sel.dispatchEvent(new Event('change', { bubbles: true }));
      updateBtn();
    });
    // expose a refresh hook for dynamic options
    sel._msRefresh = () => { renderMenu(); updateBtn(); };
  }

  // --- Filter chips UI
  function renderChips() {
    const box = document.getElementById('filter-chips');
    if (!box) return;
    const chips = [];
    const push = (key, arr, label, labelsMap) => {
      if (!arr || !arr.length) return;
      arr.forEach(v => {
        const txt = labelsMap ? (labelsMap[v] || v) : v;
        chips.push(`<button type="button" class="btn btn-sm btn-outline-secondary filter-chip" data-key="${key}" data-value="${esc(v)}">${label}: ${esc(txt)} <i class="bi bi-x ms-1"></i></button>`);
      });
    };
    const DATE_LABELS = { today: 'Hoy', yesterday: 'Ayer', tomorrow: 'Mañana', this_month: 'Este mes', last_month: 'Mes pasado', next_month: 'Próximo mes', this_year: 'Todo el año', custom: 'Fechas personalizadas' };
    push('date', filters.date, 'Fecha', DATE_LABELS);
    push('unit', filters.unit, 'Unidad');
    push('status', filters.status, 'Status');
    push('collab', filters.collab, 'Colaborador');
    push('business', filters.business, 'Negocio');
    push('dept', filters.dept, 'Departamento');
    box.innerHTML = chips.join(' ');
  }

  document.addEventListener('click', (e) => {
    const chip = e.target.closest('.filter-chip');
    if (!chip) return;
    const key = chip.getAttribute('data-key');
    const val = chip.getAttribute('data-value');
    if (!key) return;
    if (Array.isArray(filters[key])) {
      filters[key] = filters[key].filter(x => String(x) !== String(val));
    }
    const mapSel = { date: 'f-date', unit: 'f-business-unit', status: 'f-status', collab: 'f-collab', business: 'f-business', dept: 'f-dept' };
    const sel = document.getElementById(mapSel[key] || '');
    if (sel) {
      const opt = Array.from(sel.options).find(o => String(o.value) === String(val));
      if (opt) opt.selected = false;
      sel._msRefresh?.();
    }
    renderChips();
    render();
  });

  // --- API integration (use module endpoints if available)
  async function updateField(taskId, field, value) {
    try {
      const fd = new FormData(); fd.append('task_id', String(taskId)); fd.append('field', field); fd.append('value', String(value));
      fd.append('csrf', window.CSRF || '');
      const res = await fetch('/modules/processes_tasks/api/update_field.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      const j = await res.json().catch(() => ({ ok: false }));
      if (!j.ok) { console.warn('[processes_tasks:updateField] failed', j); }
      return j;
    } catch (e) { console.warn('[processes_tasks:updateField] network', e); return { ok: false }; }
  }

  async function uploadAttachment(taskId, file) {
    try {
      const fd = new FormData(); fd.append('task_id', String(taskId)); fd.append('file', file); fd.append('csrf', window.CSRF || '');
      const res = await fetch('/modules/processes_tasks/api/upload_attachment.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      const j = await res.json().catch(() => ({ ok: false }));
      return j;
    } catch (e) { console.warn('[processes_tasks:uploadAttachment] network', e); return { ok: false }; }
  }

  async function deleteAttachment(taskId, attachId) {
    try {
      const fd = new FormData(); fd.append('task_id', String(taskId)); fd.append('attach_id', String(attachId)); fd.append('csrf', window.CSRF || '');
      const res = await fetch('/modules/processes_tasks/api/delete_attachment.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      const j = await res.json().catch(() => ({ ok: false }));
      return j;
    } catch (e) { console.warn('[processes_tasks:deleteAttachment] network', e); return { ok: false }; }
  }

  function downloadAttachment(taskId, attachId) {
    // open download URL (server should set headers)
    const url = `/modules/processes_tasks/api/download_attachment.php?task_id=${encodeURIComponent(taskId)}&attach_id=${encodeURIComponent(attachId)}`;
    window.open(url, '_blank');
  }

  // --- Filters population
  function uniq(arr) { return [...new Set(arr.filter(Boolean))]; }
  function setOptions(el, items) { if (!el) return; el.innerHTML = items.map(v => `<option value="${esc(v)}">${esc(v)}</option>`).join(''); }
  function restoreMulti(el, selected) { if (!el) return; const set = new Set(selected || []); Array.from(el.options).forEach(o => { o.selected = set.has(o.value); }); }
  function getSelected(el) { return el ? Array.from(el.selectedOptions || []).map(o => o.value) : []; }
  function populateFilters() {
    const byId = id => document.getElementById(id);
    // date filter options are static, ensure defaults
    const dateSel = byId('f-date');
    if (dateSel && !dateSel.options.length) {
      const opts = [
        ['today', 'Hoy'], ['yesterday', 'Ayer'], ['tomorrow', 'Mañana'], ['this_month', 'Este mes'], ['last_month', 'Mes pasado'], ['next_month', 'Próximo mes'], ['this_year', 'Todo el año'], ['custom', 'Fechas personalizadas']
      ];
      dateSel.innerHTML = opts.map(([v, t]) => `<option value="${v}">${t}</option>`).join('');
    }
    const units = uniq(tasks.map(t => t.businessUnit));
    const businesses = uniq(tasks.map(t => t.business));
    const collabs = uniq(tasks.map(t => uName(t.assigneeId)));
    const depts = uniq(tasks.map(t => t.department));
    setOptions(byId('f-business-unit'), units);
    setOptions(byId('f-collab'), collabs);
    setOptions(byId('f-business'), businesses);
    setOptions(byId('f-dept'), depts);
    // restore selections for multi-selects
    restoreMulti(byId('f-business-unit'), filters.unit);
    restoreMulti(byId('f-collab'), filters.collab);
    restoreMulti(byId('f-business'), filters.business);
    restoreMulti(byId('f-dept'), filters.dept);
    restoreMulti(byId('f-status'), filters.status);
    restoreMulti(byId('f-date'), filters.date);
    // refresh dropdown UIs for dynamic ones
    byId('f-business-unit')?._msRefresh?.();
    byId('f-collab')?._msRefresh?.();
  }

  // --- Filtering logic
  function inDateSet(t) {
    const sel = filters.date || [];
    if (!sel.length) return true; // no filter
    const dStart = new Date(t.start);
    const dDue = new Date(t.due);
    const today = new Date(); today.setHours(0, 0, 0, 0);
    const yesterday = new Date(today); yesterday.setDate(today.getDate() - 1);
    const tomorrow = new Date(today); tomorrow.setDate(today.getDate() + 1);
    const monthOf = (d) => ({ m: d.getMonth(), y: d.getFullYear() });
    const tm = monthOf(today);
    const prevMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
    const nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);
    const withinDay = (d, day) => dStart <= day && day <= dDue;
    const withinMonth = (d) => (dStart.getFullYear() === d.getFullYear() && dStart.getMonth() === d.getMonth()) || (dDue.getFullYear() === d.getFullYear() && dDue.getMonth() === d.getMonth());
    const year = today.getFullYear();
    const withinYear = (d) => (dStart.getFullYear() === d.getFullYear()) || (dDue.getFullYear() === d.getFullYear());
    // custom range
    const fromEl = document.getElementById('f-date-from');
    const toEl = document.getElementById('f-date-to');
    let customOk = false;
    if (sel.includes('custom') && fromEl?.value && toEl?.value) {
      const from = new Date(fromEl.value + 'T00:00:00');
      const to = new Date(toEl.value + 'T23:59:59');
      customOk = (dStart <= to && dDue >= from);
    }
    return sel.some(tag =>
      (tag === 'today' && withinDay(today, today)) ||
      (tag === 'yesterday' && withinDay(yesterday, yesterday)) ||
      (tag === 'tomorrow' && withinDay(tomorrow, tomorrow)) ||
      (tag === 'this_month' && withinMonth(today)) ||
      (tag === 'last_month' && withinMonth(prevMonth)) ||
      (tag === 'next_month' && withinMonth(nextMonth)) ||
      (tag === 'this_year' && withinYear(today)) ||
      (tag === 'custom' && customOk)
    );
  }

  function applyFilters(list) {
    const has = (arr, val) => !arr.length || arr.includes(val);
    return list.filter(t =>
      has(filters.unit, t.businessUnit) &&
      has(filters.business, t.business) &&
      has(filters.collab, uName(t.assigneeId)) &&
      has(filters.dept, t.department) &&
      has(filters.status, t.status) &&
      inDateSet(t)
    );
  }

  // --- Rendering
  function render() {
    let data = applyFilters(tasks);

    // sorting
    if (sort.key) {
      const dir = sort.dir === 'desc' ? -1 : 1;
      const pri = v => ({ 'Alta': 3, 'Media': 2, 'Baja': 1 })[v] || 0;
      const st = v => ({ 'Auditada': 4, 'Completada': 3, 'En curso': 2, 'Pausada': 1, 'Pendiente': 0 })[v] || 0;
      data = [...data].sort((a, b) => {
        const val = (k, t) => {
          switch (k) {
            case 'title': return (t.title || '').toLowerCase();
            case 'assignee': return uName(t.assigneeId).toLowerCase();
            case 'delegate': return uName(t.delegateId).toLowerCase();
            case 'desc': return (t.desc || '').toLowerCase();
            case 'priority': return pri(t.priority);
            case 'start': return new Date(t.start).getTime();
            case 'due': return new Date(t.due).getTime();
            case 'status': return st(t.status);
            case 'unit': return String(t.businessUnit || '').toLowerCase();
            case 'project': return String(t.projectName || '').toLowerCase();
            case 'auditScore': return Number(t.auditScore || 0);
            case 'business': return String(t.business || '').toLowerCase();
            case 'department': return String(t.department || '').toLowerCase();
            case 'id': return Number(t.id) || 0;

            case 'evidence': return (t.attachments?.length || 0);
            default: return 0;
          }
        };
        const av = val(sort.key, a), bv = val(sort.key, b);
        return av > bv ? dir : av < bv ? -dir : 0;
      });
    }
    const thSort = (label, key, extraCls = '') => {
      const active = sort.key === key;
      const icon = active ? (sort.dir === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill') : '';
      const cls = `sortable ${active ? 'sorted' : ''} ${extraCls} col-${key}`.trim();
      return `<th class="${cls}" data-sort="${key}">${label}${icon ? ` <i class=\"bi ${icon} small text-muted\"></i>` : ''}</th>`;
    };

    const visibleColsCount = columnsOrder.filter(key => columnsVisible[key]).length;
    const colspanEmpty = 1 /* sel */ + visibleColsCount + (actionsVisible ? 1 : 0);
    mount.innerHTML = `
      <div class="table-responsive">
        <table class="table table-sm align-middle tasks-table">
              <thead>
                <tr>
                  <th style="width:36px"><input type="checkbox" id="sel-all" ${data.length && data.every(t => selected.has(t.id)) ? 'checked' : ''}></th>
                  ${columnsOrder.map(key => columnsVisible[key] ? thSort(columnsLabels[key], key, columnsResponsive[key] || '') : '').join('')}
                  ${actionsVisible ? '<th class="text-end col-actions">Acciones</th>' : ''}
                </tr>
              </thead>
              <tbody id="task-rows">
                ${data.length ? data.map((t, i) => rowHtml(t, i)).join('') : `<tr><td colspan="${colspanEmpty}" class="text-muted">Sin tareas que coincidan con los filtros.</td></tr>`}
              </tbody>
            </table>
          </div>
          <div class="tasks-footer d-flex align-items-center gap-3 mt-2">
            <div class="flex-grow-1 d-flex align-items-center gap-3">
              <div class="small text-muted">Seleccionados: ${Array.from(selected).length} | Total listado: ${data.length}</div>
              <div id="tasks-complete-indicator" class="d-flex align-items-center gap-2" aria-live="polite">
                <span class="badge bg-dark-subtle text-dark-emphasis" id="complete-rate-badge">Completadas: 0%</span>
                <div class="progress" style="width:160px;height:12px;">
                  <div class="progress-bar" id="complete-rate-bar" style="width:0%;"></div>
                </div>
              </div>
            </div>
            <div class="flex-grow-1 d-flex justify-content-center">
              <div id="tasks-audit-indicator" class="d-flex align-items-center gap-2" aria-live="polite">
                <span class="badge" id="audit-rate-badge">Auditadas: 0%</span>
                <div class="progress" style="width:160px;height:12px;">
                  <div class="progress-bar" id="audit-rate-bar" style="width:0%;"></div>
                </div>
              </div>
            </div>
            <div class="flex-grow-0 d-flex gap-2">
              <button class="btn btn-danger btn-sm" id="btn-export-pdf" title="Exportar PDF" aria-label="Exportar PDF"><i class="bi bi-filetype-pdf"></i></button>
              <button class="btn btn-success btn-sm" id="btn-export-excel" title="Exportar Excel" aria-label="Exportar Excel"><i class="bi bi-file-earmark-excel"></i></button>
            </div>
          </div>
        `;

    // bind table interactions
    const tbody = document.getElementById('task-rows');
    tbody?.addEventListener('click', onTableClick);
    tbody?.addEventListener('change', onRowCheck);
    const thead = mount.querySelector('thead');
    thead?.addEventListener('click', onHeaderClick);
    const selAll = document.getElementById('sel-all');
    selAll?.addEventListener('change', () => toggleSelectAll(data, selAll.checked));
    // export buttons
    mount.querySelector('#btn-export-excel')?.addEventListener('click', () => exportCSV(data));
    mount.querySelector('#btn-export-pdf')?.addEventListener('click', () => exportPrint(data));
    updateAuditIndicator(data);
    updateCompleteIndicator(data);
  }

  function rowHtml(t, viewIndex) {
    // Map view index to real tasks index (since we filtered)
    const realIndex = tasks.findIndex(x => x.id === t.id);
    const disabled = t.status === 'Completada' ? 'disabled' : '';
    const cell = (key) => {
      switch (key) {
        case 'id': return `<td class="col-id">${t.id}</td>`;
        case 'title': return `<td class="col-title">${esc(t.title)}</td>`;
        case 'project': return `<td class="col-project">${esc(t.projectName || '—')}</td>`;
        case 'desc': return `<td class="col-desc" title="${esc(t.desc || '')}">${t.desc ? esc(t.desc) : '—'}</td>`;
        case 'auditScore': return `<td class="col-auditScore">${t.auditScore != null ? `<span class="badge bg-primary-subtle text-primary">${t.auditScore}/5</span>` : '—'}</td>`;
        case 'department': return `<td class="col-department">${esc(t.department || '—')}</td>`;
        case 'unit': return `<td class="col-unit">${esc(t.businessUnit || '—')}</td>`;
        case 'business': return `<td class="col-business">${esc(t.business || '—')}</td>`;
        case 'start': return `<td class="col-start"><button class="chip-edit btn btn-xxs btn-outline-secondary btn-square" data-edit="start" data-i="${realIndex}" ${disabled} aria-label="Editar inicio">${esc(t.start)}</button></td>`;
        case 'due': return `<td class="col-due"><button class="chip-edit btn btn-xxs btn-outline-secondary btn-square" data-edit="due" data-i="${realIndex}" ${disabled} aria-label="Editar vencimiento">${esc(t.due)}</button></td>`;
        case 'status': return `<td class="col-status"><button class="chip-edit btn btn-xxs btn-outline-secondary btn-square" data-edit="status" data-i="${realIndex}" aria-label="Editar estado">${badgeStatus(t.status)}</button></td>`;
        case 'assignee': return `<td class="col-assignee">${esc(uName(t.assigneeId))}</td>`;
        case 'priority': return `<td class="col-priority"><button class="chip-edit btn btn-xxs btn-outline-secondary btn-square" data-edit="priority" data-i="${realIndex}" ${disabled} aria-label="Editar prioridad">${badgePriority(t.priority)}</button></td>`;

        case 'evidence': return `<td class="col-evidence"><button class="btn btn-xxs btn-outline-secondary btn-square btn-icon" data-action="evidence" data-i="${realIndex}" aria-label="Evidencia"><i class="bi bi-paperclip"></i></button><span class="count-badge">${t.attachments.length}</span></td>`;
        case 'delegate': return `<td class="${columnsResponsive.delegate || ''} col-delegate"><button class="chip-edit btn btn-xxs btn-outline-secondary btn-square" data-edit="delegate" data-i="${realIndex}" ${disabled} aria-label="Editar delegado">${esc(uName(t.delegateId))}</button></td>`;
        default: return '';
      }
    };
    return `
      <tr data-i="${realIndex}">
        <td><input type="checkbox" class="sel-row" data-id="${t.id}" ${selected.has(t.id) ? 'checked' : ''}></td>
  ${columnsOrder.map(k => columnsVisible[k] ? cell(k) : '').join('')}
  ${actionsVisible ? `<td class="text-end col-actions"><div class="d-inline-flex gap-2">
            <button class="btn-act btn-act--blue" data-action="edit" data-i="${realIndex}" title="Editar" aria-label="Editar"><i class="bi bi-pencil"></i></button>
            <button class="btn-act btn-act--red" data-action="delete" data-i="${realIndex}" title="Eliminar" aria-label="Eliminar"><i class="bi bi-trash3"></i></button>
            <button class="btn-act btn-act--yellow" data-action="report-evidence" data-i="${realIndex}" title="Reporte de evidencia" aria-label="Reporte de evidencia"><i class="bi bi-clipboard-plus"></i></button>
            <button class="btn-act btn-act--purple" data-action="audit" data-i="${realIndex}" title="Auditoría" aria-label="Auditoría"><i class="bi bi-shield-check"></i></button>
          </div></td>` : ''}
      </tr>
    `;
  }

  // --- Inline editors
  function onTableClick(ev) {
    const btn = ev.target.closest('button');
    if (!btn) return;
    const i = Number(btn.dataset.i);
    const row = tasks[i];
    if (!row) return;

    const edit = btn.dataset.edit;
    if (edit) {
      if (row.status === 'Completada') return; // no edit when completed
      const td = btn.closest('td');
      if (!td) return;
      openEditor(td, i, edit, row);
      return;
    }

    const action = btn.dataset.action;
    if (action === 'delete') {
      if (!confirm('¿Eliminar esta tarea?')) return;
      (async () => {
        try {
          const fd = new FormData(); fd.set('task_id', String(row.id)); fd.set('csrf', window.CSRF || '');
          const res = await fetch('/modules/processes_tasks/api/delete.php', { method: 'POST', body: fd, credentials: 'same-origin' });
          const j = await res.json().catch(() => null);
          if (j && j.ok) {
            tasks.splice(i, 1);
          } else if (!j) {
            // network failure; fallback
            tasks.splice(i, 1);
          } else {
            alert('No se pudo eliminar: ' + (j.error || 'error'));
          }
        } catch (e) { console.warn('delete failed', e); tasks.splice(i, 1); }
        render(); populateFilters();
      })();
    } else if (action === 'edit') {
      openEditModal(i);
    } else if (action === 'view') {
      openViewModal(i);
    } else if (action === 'evidence') {
      openEvidenceModal(i);
    } else if (action === 'report-evidence') {
      openReportModal(i);
    } else if (action === 'audit') {
      if (!(row.status === 'Completada' || row.status === 'Auditada')) {
        alert('Solo tareas Completadas pueden auditarse');
        return;
      }
      openAuditModal(i);
    }
  }

  function onRowCheck(ev) {
    const cb = ev.target.closest('.sel-row');
    if (!cb) return;
    const id = Number(cb.getAttribute('data-id'));
    if (cb.checked) selected.add(id); else selected.delete(id);
    // update footer counts
    const selTxt = mount.querySelector('.tasks-footer .text-muted');
    if (selTxt) selTxt.textContent = `Seleccionados: ${Array.from(selected).length} | Total listado: ${applyFilters(tasks).length}`;
  }

  function toggleSelectAll(view, checked) {
    if (checked) view.forEach(t => selected.add(t.id)); else view.forEach(t => selected.delete(t.id));
    render();
  }

  function onHeaderClick(ev) {
    const th = ev.target.closest('th.sortable');
    if (!th) return;
    const key = th.getAttribute('data-sort');
    if (!key) return;
    if (sort.key === key) { sort.dir = sort.dir === 'asc' ? 'desc' : 'asc'; }
    else { sort.key = key; sort.dir = 'asc'; }
    render();
  }

  // Columns modal
  function ensureColumnsModal() {
    if (document.getElementById('columns-modal')) return;
    const tpl = `
      <div class="modal fade" id="columns-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Columnas visibles</h5>
              <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="cols-body"></div>
            <div class="modal-footer">
              <button class="btn btn-ghost" data-bs-dismiss="modal">Cerrar</button>
              <button class="btn btn-brand" id="cols-save">Aplicar</button>
            </div>
          </div>
        </div>
      </div>`;
    document.body.insertAdjacentHTML('beforeend', tpl);
    document.getElementById('cols-save').onclick = () => {
      const body = document.getElementById('cols-body');
      body.querySelectorAll('input[type=checkbox]').forEach(cb => {
        const key = cb.getAttribute('data-key');
        columnsVisible[key] = cb.checked;
      });
      bootstrap.Modal.getOrCreateInstance('#columns-modal').hide();
      render();
    };
  }

  function openColumnsModal() {
    ensureColumnsModal();
    const body = document.getElementById('cols-body');
    // Build reorderable list
    const list = document.getElementById('columns-order-list');
    if (list) {
      list.innerHTML = columnsOrder
        .map(key => ({ key, label: columnsLabels[key] }))
        .map((it, idx) => `
                <li data-key="${it.key}">
                  <div class="col-item-left">
                    <input class="form-check-input" type="checkbox" id="col-${it.key}" data-key="${it.key}" ${columnsVisible[it.key] ? 'checked' : ''}>
                    <label for="col-${it.key}" class="form-check-label">${it.label}</label>
                  </div>
                  <div class="reorder">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-move="up" ${idx === 0 ? 'disabled' : ''} aria-label="Subir"><i class="bi bi-arrow-up"></i></button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-move="down" ${idx === columnsOrder.length - 1 ? 'disabled' : ''} aria-label="Bajar"><i class="bi bi-arrow-down"></i></button>
                  </div>
                </li>`).join('');

      // Bind move handlers (event delegation)
      list.onclick = (ev) => {
        const btn = ev.target.closest('button[data-move]');
        if (!btn) return;
        const li = btn.closest('li[data-key]');
        const key = li?.getAttribute('data-key');
        if (!key) return;
        const idx = columnsOrder.indexOf(key);
        if (idx === -1) return;
        if (btn.dataset.move === 'up' && idx > 0) {
          [columnsOrder[idx - 1], columnsOrder[idx]] = [columnsOrder[idx], columnsOrder[idx - 1]];
        } else if (btn.dataset.move === 'down' && idx < columnsOrder.length - 1) {
          [columnsOrder[idx + 1], columnsOrder[idx]] = [columnsOrder[idx], columnsOrder[idx + 1]];
        }
        openColumnsModal(); // re-render modal to refresh disabled states
      };
    }
    bootstrap.Modal.getOrCreateInstance('#columns-modal').show();
  }

  // Export helpers
  function exportCSV(list) {
    const headers = ['ID', 'Nombre', 'Descripción', 'Proyecto', 'Ponderación', 'Depto', 'Unidad', 'Negocio', 'Ingreso', 'Salida', 'Estado', 'Responsable', 'Prioridad', 'Auditado por', 'Auditado el', 'Evidencias'];
    const rows = list.map(t => [
      t.id,
      t.title,
      t.desc || '',
      t.projectName || '',
      (t.auditScore != null ? `${t.auditScore}/5` : ''),
      t.department || '',
      t.businessUnit || '',
      t.business || '',
      t.start,
      t.due,
      t.status,
      uName(t.assigneeId),
      t.priority,
      uName(t.auditedBy),
      t.auditedAt || '',
      String(t.attachments?.length || 0)
    ]);
    const csv = [headers, ...rows].map(r => r.map(val => '"' + String(val ?? '').replace(/"/g, '""') + '"').join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `tareas_${new Date().toISOString().slice(0, 10)}.csv`;
    a.click();
    URL.revokeObjectURL(a.href);
  }

  function exportPrint(list) {
    const win = window.open('', '_blank');
    if (!win) return;
    const rowsHtml = list.map(t => `<tr>
      <td>${esc(t.id)}</td>
  <td>${esc(t.title)}</td>
  <td>${esc(t.desc || '')}</td>
    <td>${esc(t.projectName || '')}</td>
      <td>${esc(t.auditScore != null ? `${t.auditScore}/5` : '')}</td>
      <td>${esc(t.department || '')}</td>
      <td>${esc(t.businessUnit || '')}</td>
      <td>${esc(t.business || '')}</td>
      <td>${esc(t.start)}</td>
      <td>${esc(t.due)}</td>
      <td>${esc(t.status)}</td>
      <td>${esc(uName(t.assigneeId))}</td>
      <td>${esc(t.priority)}</td>
      <td>${esc(uName(t.auditedBy))}</td>
      <td>${esc(t.auditedAt || '')}</td>
    </tr>`).join('');
    win.document.write(`<!doctype html><html><head><title>Tareas</title>
          <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
          </head><body class="p-3">
          <h5>Listado de tareas</h5>
      <table class="table table-sm table-bordered">
  <thead><tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Proyecto</th><th>Ponderación</th><th>Depto</th><th>Unidad</th><th>Negocio</th><th>Ingreso</th><th>Salida</th><th>Estado</th><th>Responsable</th><th>Prioridad</th><th>Auditado por</th><th>Auditado el</th></tr></thead>
            <tbody>${rowsHtml}</tbody>
          </table>
          <script>window.onload=()=>window.print()</script>
        </body></html>`);
    win.document.close();
  }

  function openEditor(td, i, field, row) {
    if (field === 'delegate') {
      const sel = document.createElement('select');
      sel.className = 'form-select form-select-sm';
      sel.innerHTML = `<option value="">—</option>` + USERS.map(u => `<option value="${u.id}" ${row.delegateId === u.id ? 'selected' : ''}>${esc(u.name)}</option>`).join('');
      td.innerHTML = '';
      td.appendChild(sel);
      sel.focus();
      sel.addEventListener('change', () => {
        row.delegateId = sel.value ? Number(sel.value) : null;
        updateField(row.id, 'delegateId', row.delegateId);
        render();
      });
      sel.addEventListener('blur', () => render());
    }
    else if (field === 'priority') {
      const sel = document.createElement('select');
      sel.className = 'form-select form-select-sm';
      ['Alta', 'Media', 'Baja'].forEach(p => {
        const o = document.createElement('option'); o.value = p; o.textContent = p; if (row.priority === p) o.selected = true; sel.appendChild(o);
      });
      td.innerHTML = ''; td.appendChild(sel); sel.focus();
      sel.addEventListener('change', () => { row.priority = sel.value; updateField(row.id, 'priority', row.priority); render(); });
      sel.addEventListener('blur', () => render());
    }
    else if (field === 'start' || field === 'due') {
      const inp = document.createElement('input');
      inp.type = 'date';
      inp.className = 'form-control form-control-sm';
      inp.value = field === 'start' ? row.start : row.due;
      td.innerHTML = ''; td.appendChild(inp); inp.focus();
      const commit = () => {
        const val = inp.value || (field === 'start' ? row.start : row.due);
        if (field === 'start') {
          if (new Date(val) > new Date(row.due)) { alert('La fecha de inicio no puede ser posterior al vencimiento'); render(); return; }
          row.start = val; updateField(row.id, 'start', val);
        } else {
          if (new Date(val) < new Date(row.start)) { alert('La fecha de vencimiento no puede ser anterior al inicio'); render(); return; }
          row.due = val; updateField(row.id, 'due', val);
        }
        render();
      };
      inp.addEventListener('blur', commit);
      inp.addEventListener('keydown', (e) => { if (e.key === 'Enter') commit(); if (e.key === 'Escape') render(); });
    }
    else if (field === 'status') {
      const sel = document.createElement('select');
      sel.className = 'form-select form-select-sm';
      ['Pendiente', 'En curso', 'Pausada', 'Completada', 'Auditada'].forEach(s => {
        const o = document.createElement('option'); o.value = s; o.textContent = s; if (row.status === s) o.selected = true; sel.appendChild(o);
      });
      td.innerHTML = ''; td.appendChild(sel); sel.focus();
      sel.addEventListener('change', () => { row.status = sel.value; updateField(row.id, 'status', row.status); render(); });
      sel.addEventListener('blur', () => render());
    }
  }

  // --- Evidence modal
  function ensureEvidenceModal() {
    if (document.getElementById('evidence-modal')) return;
    const tpl = `
    <div class="modal fade" id="evidence-modal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Evidencias de tarea</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div id="evidence-list" class="mb-3"></div>
            <div class="d-flex gap-2">
              <input type="file" id="evidence-file" multiple class="form-control" aria-label="Subir evidencias">
              <button class="btn btn-brand" id="evidence-upload">Subir</button>
            </div>
            <div class="small text-muted mt-2">Formatos permitidos (demo): PDF, JPG, PNG, DOCX. Límite simulado 10 MB.</div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-ghost" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', tpl);
    const modalEl = document.getElementById('evidence-modal');
    modalEl.addEventListener('hidden.bs.modal', () => { render(); });
    modalEl.querySelector('#evidence-upload').addEventListener('click', async () => {
      const idx = currentEvidenceIndex; if (idx == null) return;
      const input = modalEl.querySelector('#evidence-file');
      const files = Array.from(input.files || []);
      for (const f of files) {
        // simple limit 10MB demo
        if (f.size > 10 * 1024 * 1024) { alert(`${f.name}: excede 10MB (demo)`); continue; }
        await uploadAttachment(tasks[idx].id, f);
        tasks[idx].attachments.push({ id: nextAttId++, name: f.name, size: f.size, ts: Date.now() });
      }
      input.value = '';
      paintEvidenceList();
    });
  }

  function paintEvidenceList() {
    const modalEl = document.getElementById('evidence-modal');
    const box = modalEl.querySelector('#evidence-list');
    const t = tasks[currentEvidenceIndex];
    if (!t) { box.innerHTML = '<div class="text-muted">Tarea no encontrada.</div>'; return; }
    if (!t.attachments.length) { box.innerHTML = '<div class="text-muted">Sin archivos adjuntos.</div>'; return; }
    box.innerHTML = '<ul class="list-group">' + t.attachments.map(att => `
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
          <div><i class="bi bi-paperclip me-1"></i> ${esc(att.name)}</div>
          <div class="small text-muted">${fmtBytes(att.size)} · ${new Date(att.ts).toLocaleString()}</div>
          ${att.closeDate ? `<div class="small">Cierre: ${esc(att.closeDate)}</div>` : ''}
          ${att.observations ? `<div class="small">Obs: ${esc(att.observations)}</div>` : ''}
        </div>
        <div>
          <button class="btn btn-sm btn-outline-primary me-2" data-att="${att.id}" data-op="report">Informe</button>
          <a href="#" download="${esc(att.name)}" class="btn btn-sm btn-outline-secondary me-2" data-att="${att.id}" data-op="download">Descargar</a>
          <button class="btn btn-sm btn-outline-danger" data-att="${att.id}" data-op="delete">Eliminar</button>
        </div>
      </li>`).join('') + '</ul>';
    // bind ops
    box.onclick = async (ev) => {
      const a = ev.target.closest('[data-op]');
      if (!a) return;
      const id = Number(a.getAttribute('data-att'));
      if (a.getAttribute('data-op') === 'delete') {
        await deleteAttachment(tasks[currentEvidenceIndex].id, id);
        tasks[currentEvidenceIndex].attachments = tasks[currentEvidenceIndex].attachments.filter(x => x.id !== id);
        paintEvidenceList();
      } else if (a.getAttribute('data-op') === 'download') {
        downloadAttachment(tasks[currentEvidenceIndex].id, id);
      } else if (a.getAttribute('data-op') === 'report') {
        downloadEvidenceReport(tasks[currentEvidenceIndex].id, id);
      }
    };
  }

  function downloadEvidenceReport(taskId, attachId) {
    const task = tasks.find(t => t.id === taskId); if (!task) return;
    const att = task.attachments.find(a => a.id === attachId); if (!att) return;
    const win = window.open('', '_blank');
    if (!win) return;
    const safeTitle = (task.title || 'tarea').replace(/[^a-z0-9]+/gi, ' ').trim();
    const html = `<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Informe de evidencia</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; margin: 32px; color: #111; }
    h1 { font-size: 20px; margin: 0 0 8px; }
    h2 { font-size: 16px; margin: 16px 0 8px; }
    .muted { color: #666; }
    .kv { margin: 0; padding: 0; list-style: none; }
    .kv li { margin: 4px 0; }
    .box { border: 1px solid #ddd; border-radius: 6px; padding: 12px; }
    .obs { white-space: pre-wrap; }
    @media print { .noprint { display: none; } }
  </style>
  <script>window.onload = function(){ window.print(); };</script>
  </head>
<body>
  <div class="noprint" style="text-align:right; margin-bottom:8px;"><button onclick="window.print()">Imprimir / Guardar PDF</button></div>
  <h1>Informe de evidencia</h1>
  <div class="box">
    <ul class="kv">
      <li><strong>Tarea:</strong> ${esc(safeTitle)} (ID ${task.id})</li>
      <li><strong>Archivo:</strong> ${esc(att.name)}</li>
      <li><strong>Fecha de carga:</strong> ${new Date(att.ts).toLocaleString()}</li>
      <li><strong>Tamaño:</strong> ${fmtBytes(att.size)}</li>
      <li><strong>Fecha de cierre:</strong> ${esc(att.closeDate || '—')}</li>
    </ul>
    <h2>Observaciones</h2>
    <div class="obs">${esc(att.observations || '—')}</div>
  </div>
</body>
</html>`;
    win.document.open();
    win.document.write(html);
    win.document.close();
  }

  // --- Evidence Report modal (from actions column)
  function ensureReportModal() {
    if (document.getElementById('evidence-report-modal')) return;
    const tpl = `
    <div class="modal fade" id="evidence-report-modal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Reporte de evidencia</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-2">
              <label class="form-label">Fecha de cierre</label>
              <input type="date" class="form-control" id="rep-close-date">
            </div>
            <div class="mb-2">
              <label class="form-label">Observaciones</label>
              <textarea class="form-control" id="rep-observations" rows="3" placeholder="Escribe observaciones..."></textarea>
            </div>
            <div class="mb-2">
              <label class="form-label">Subir archivo</label>
              <input type="file" class="form-control" id="rep-file" aria-label="Subir archivo de evidencia">
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-brand" id="rep-save">Guardar</button>
          </div>
        </div>
      </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', tpl);
    const el = document.getElementById('evidence-report-modal');
    el.querySelector('#rep-save').addEventListener('click', async () => {
      const idx = Number(el.getAttribute('data-i'));
      const date = el.querySelector('#rep-close-date').value || todayISO();
      const obs = el.querySelector('#rep-observations').value.trim();
      const file = el.querySelector('#rep-file').files[0];
      if (!file) { alert('Selecciona un archivo'); return; }
      await uploadAttachment(tasks[idx].id, file);
      tasks[idx].attachments.push({ id: nextAttId++, name: file.name, size: file.size, ts: Date.now(), closeDate: date, observations: obs });
      // marcar tarea como Completada al guardar el reporte
      tasks[idx].status = 'Completada';
      updateField(tasks[idx].id, 'status', 'Completada');
      if (window.bootstrap?.Modal) bootstrap.Modal.getOrCreateInstance(el).hide();
      render();
    });
  }

  function openReportModal(i) {
    ensureReportModal();
    const el = document.getElementById('evidence-report-modal');
    el.setAttribute('data-i', String(i));
    el.querySelector('#rep-close-date').value = todayISO();
    el.querySelector('#rep-observations').value = '';
    el.querySelector('#rep-file').value = '';
    if (window.bootstrap?.Modal) bootstrap.Modal.getOrCreateInstance(el).show();
    else { el.style.display = 'block'; el.classList.add('show'); }
  }

  // --- Audit modal
  function ensureAuditModal() {
    if (document.getElementById('audit-modal')) return;
    const tpl = `
    <div class="modal fade" id="audit-modal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Auditoría de tarea</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-2">
              <label class="form-label">Ponderación</label>
              <select class="form-select" id="audit-score">
                <option value="">—</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Notas de auditoría</label>
              <textarea class="form-control" id="audit-notes" rows="3" placeholder="Observaciones...\n"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-brand" id="audit-save">Guardar</button>
          </div>
        </div>
      </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', tpl);
    const el = document.getElementById('audit-modal');
    el.querySelector('#audit-save').addEventListener('click', () => {
      const idx = Number(el.getAttribute('data-i'));
      const scoreVal = el.querySelector('#audit-score').value;
      const notes = el.querySelector('#audit-notes').value.trim();
      const score = scoreVal ? Number(scoreVal) : null;
      tasks[idx].auditScore = score;
      tasks[idx].auditNotes = notes;
      tasks[idx].auditedAt = todayISO();
      tasks[idx].auditedBy = USERS[0]?.id || 1;
      if (tasks[idx].status === 'Completada') {
        tasks[idx].status = 'Auditada';
        updateField(tasks[idx].id, 'status', 'Auditada');
      }
      updateField(tasks[idx].id, 'auditScore', score);
      updateField(tasks[idx].id, 'auditNotes', notes);
      updateField(tasks[idx].id, 'auditedAt', tasks[idx].auditedAt);
      updateField(tasks[idx].id, 'auditedBy', tasks[idx].auditedBy);
      bootstrap.Modal.getOrCreateInstance(el).hide();
      render();
    });
  }

  function openAuditModal(i) {
    ensureAuditModal();
    const el = document.getElementById('audit-modal');
    el.setAttribute('data-i', String(i));
    const t = tasks[i];
    el.querySelector('#audit-score').value = t.auditScore ? String(t.auditScore) : '';
    el.querySelector('#audit-notes').value = t.auditNotes || '';
    bootstrap.Modal.getOrCreateInstance(el).show();
  }

  // Header indicator
  function updateAuditIndicator(list) {
    try {
      const total = list?.length ?? 0;
      const audited = (list || []).filter(t => (t.status === 'Auditada') || (t.auditScore && Number(t.auditScore) > 0)).length;
      const pct = total ? Math.round((audited / total) * 100) : 0;
      const badge = document.getElementById('audit-rate-badge');
      const bar = document.getElementById('audit-rate-bar');
      if (badge) badge.textContent = `Auditadas: ${pct}%`;
      if (bar) bar.style.width = pct + '%';
    } catch { }
  }

  function updateCompleteIndicator(list) {
    try {
      const total = list?.length ?? 0;
      const completed = (list || []).filter(t => t.status === 'Completada' || t.status === 'Auditada').length;
      const pct = total ? Math.round((completed / total) * 100) : 0;
      const badge = document.getElementById('complete-rate-badge');
      const bar = document.getElementById('complete-rate-bar');
      if (badge) badge.textContent = `Completadas: ${pct}%`;
      if (bar) bar.style.width = pct + '%';
    } catch { }
  }

  function openEvidenceModal(i) {
    currentEvidenceIndex = i;
    ensureEvidenceModal();
    paintEvidenceList();
    const m = bootstrap.Modal.getOrCreateInstance('#evidence-modal');
    m.show();
  }

  // --- Create/Edit modal
  function ensureTaskModal() {
    if (document.getElementById('taskModal')) return document.getElementById('taskModal');
    const modal = document.createElement('div');
    modal.id = 'taskModal';
    modal.className = 'modal fade';
    modal.tabIndex = -1;
    modal.innerHTML = `
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Tarea</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-2">
              <label class="form-label">Título</label>
              <input type="text" class="form-control" id="task-title" required placeholder="Ej. Revisión de mermas" />
            </div>
            <div class="row g-2">
              <div class="col">
                <label class="form-label">Responsable</label>
                <select class="form-select" id="task-assignee"></select>
              </div>
              <div class="col">
                <label class="form-label">Delegado (opcional)</label>
                <select class="form-select" id="task-delegate"><option value="">—</option></select>
              </div>
            </div>
            <div class="row g-2 mt-1">
              <div class="col">
                <label class="form-label">Prioridad</label>
                <select class="form-select" id="task-priority"><option>Media</option><option>Alta</option><option>Baja</option></select>
              </div>
              <div class="col">
                <label class="form-label">Estado</label>
                <select class="form-select" id="task-status"><option>Pendiente</option><option>En curso</option><option>Pausada</option><option>Completada</option></select>
              </div>
            </div>
            <div class="row g-2 mt-1">
              <div class="col">
                <label class="form-label">Inicio</label>
                <input type="date" class="form-control" id="task-start" />
              </div>
              <div class="col">
                <label class="form-label">Vencimiento</label>
                <input type="date" class="form-control" id="task-due" />
              </div>
            </div>
            <div class="row g-2 mt-1">
              <div class="col">
                <label class="form-label">Unidad</label>
                <input type="text" class="form-control" id="task-unit" placeholder="Ej. Retail" />
              </div>
              <div class="col">
                <label class="form-label">Negocio</label>
                <input type="text" class="form-control" id="task-business" placeholder="Ej. Sucursal Cancún" />
              </div>
            </div>
            <div class="mt-2">
              <label class="form-label">Descripción</label>
              <textarea class="form-control" id="task-desc" rows="2" placeholder="Detalles"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-brand" id="task-save">Guardar</button>
          </div>
        </div>
      </div>`;
    document.body.appendChild(modal);
    return modal;
  }

  function openCreateModal() {
    editIndex = null;
    const modalEl = ensureTaskModal();
    // Prefill from query params if coming from a Project
    const qs = new URLSearchParams(location.search);
    const projectId = qs.get('project_id') || '';
    const projectName = qs.get('project_name') || '';
    const projectUnit = qs.get('project_unit') || '';
    const projectBusiness = qs.get('project_business') || '';
    fillTaskModal({
      title: '', assigneeId: USERS[0]?.id || 1, delegateId: null, priority: 'Media', status: 'Pendiente',
      start: todayISO(), due: addDaysISO(2), businessUnit: projectUnit || '', business: projectBusiness || '', desc: '',
      projectId: projectId || '', projectName: projectName || ''
    });
    bindSave(modalEl);
    showModal(modalEl);
  }

  function openEditModal(i) {
    editIndex = i;
    const modalEl = ensureTaskModal();
    fillTaskModal(tasks[i]);
    bindSave(modalEl);
    showModal(modalEl);
  }

  function fillTaskModal(t) {
    const m = document.getElementById('taskModal');
    m.querySelector('#task-title').value = t.title || '';
    // populate assignee/delegate selects from USERS
    const assSel = m.querySelector('#task-assignee');
    const delSel = m.querySelector('#task-delegate');
    if (assSel) {
      assSel.innerHTML = USERS.map(u => `<option value="${u.id}">${esc(u.name)}</option>`).join('');
      assSel.value = String(t.assigneeId || (USERS[0] ? USERS[0].id : ''));
    }
    if (delSel) {
      delSel.innerHTML = '<option value="">—</option>' + USERS.map(u => `<option value="${u.id}">${esc(u.name)}</option>`).join('');
      delSel.value = t.delegateId ? String(t.delegateId) : '';
    }
    m.querySelector('#task-priority').value = t.priority || 'Media';
    m.querySelector('#task-status').value = t.status || 'Pendiente';
    m.querySelector('#task-start').value = t.start || todayISO();
    m.querySelector('#task-due').value = t.due || addDaysISO(2);
    m.querySelector('#task-unit').value = t.businessUnit || '';
    m.querySelector('#task-business').value = t.business || '';
    m.querySelector('#task-desc').value = t.desc || '';
    // store project context in dataset for save
    m.dataset.projectId = t.projectId || '';
    m.dataset.projectName = t.projectName || '';
  }

  function bindSave(modalEl) {
    const saveBtn = modalEl.querySelector('#task-save');
    saveBtn.onclick = () => {
      const title = modalEl.querySelector('#task-title').value.trim();
      if (!title) { alert('Indica un título'); return; }
      const assigneeId = Number(modalEl.querySelector('#task-assignee').value);
      const delegateVal = modalEl.querySelector('#task-delegate').value;
      const delegateId = delegateVal ? Number(delegateVal) : null;
      const priority = modalEl.querySelector('#task-priority').value;
      const status = modalEl.querySelector('#task-status').value;
      const start = modalEl.querySelector('#task-start').value || todayISO();
      const due = modalEl.querySelector('#task-due').value || start;
      if (new Date(due) < new Date(start)) { alert('La fecha de vencimiento no puede ser anterior al inicio'); return; }
      const businessUnit = modalEl.querySelector('#task-unit').value.trim();
      const business = modalEl.querySelector('#task-business').value.trim();
      const desc = modalEl.querySelector('#task-desc').value.trim();
      const projectId = modalEl.dataset.projectId || '';
      const projectName = modalEl.dataset.projectName || '';

      (async () => {
        const fd = new FormData();
        fd.set('csrf', window.CSRF || '');
        fd.set('title', title);
        fd.set('assignee_id', String(assigneeId));
        fd.set('delegate_id', delegateId ? String(delegateId) : '');
        fd.set('priority', priority);
        fd.set('status', status);
        fd.set('start', start);
        fd.set('due', due);
        fd.set('business_unit', businessUnit);
        fd.set('business', business);
        fd.set('desc', desc);
        fd.set('project_id', projectId || '');
        fd.set('project_name', projectName || '');
        try {
          if (editIndex == null) {
            const res = await fetch('/modules/processes_tasks/api/create.php', { method: 'POST', body: fd, credentials: 'same-origin' });
            const j = await res.json().catch(() => null);
            if (j && j.ok && j.item) {
              // Normalize server-returned row into UI shape
              tasks.unshift(mapServerRowToTask(j.item));
            } else if (!j) {
              // network error, fallback to local
              tasks.unshift({ id: nextId++, title, assigneeId, delegateId, priority, status, start, due, desc, businessUnit, business, projectId: projectId || '', projectName: projectName || '', attachments: [] });
            }
          } else {
            fd.set('task_id', String(tasks[editIndex].id));
            const res = await fetch('/modules/processes_tasks/api/update.php', { method: 'POST', body: fd, credentials: 'same-origin' });
            const j = await res.json().catch(() => null);
            if (j && j.ok) {
              // refresh local item by updating fields
              Object.assign(tasks[editIndex], { title, assigneeId, delegateId, priority, status, start, due, desc, businessUnit, business });
            } else if (!j) {
              Object.assign(tasks[editIndex], { title, assigneeId, delegateId, priority, status, start, due, desc, businessUnit, business });
            }
          }
        } catch (e) { console.warn('save task failed', e); }
        hideModal(modalEl);
        render();
        populateFilters();
      })();
    };
  }

  // --- View modal
  function openViewModal(i) {
    const t = tasks[i]; if (!t) return;
    const id = 'task-view-modal';
    let el = document.getElementById(id);
    if (!el) {
      const tpl = `
      <div class="modal fade" id="${id}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Detalle de tarea</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><div id="task-view-body"></div></div>
            <div class="modal-footer"><button class="btn btn-ghost" data-bs-dismiss="modal">Cerrar</button></div>
          </div>
        </div>
      </div>`;
      document.body.insertAdjacentHTML('beforeend', tpl);
      el = document.getElementById(id);
    }
    const body = el.querySelector('#task-view-body');
    body.innerHTML = `
      <div class="row g-2">
        <div class="col-12"><strong>${esc(t.title)}</strong></div>
        <div class="col-md-4"><div class="text-muted small">Responsable</div>${esc(uName(t.assigneeId))}</div>
        <div class="col-md-4"><div class="text-muted small">Delegado</div>${esc(uName(t.delegateId))}</div>
        <div class="col-md-4"><div class="text-muted small">Prioridad</div>${badgePriority(t.priority)}</div>
        <div class="col-md-4"><div class="text-muted small">Inicio</div>${esc(t.start)}</div>
        <div class="col-md-4"><div class="text-muted small">Vencimiento</div>${esc(t.due)}</div>
        <div class="col-md-4"><div class="text-muted small">Estado</div>${badgeStatus(t.status)}</div>
        <div class="col-md-4"><div class="text-muted small">Unidad</div>${esc(t.businessUnit)}</div>
  <div class="col-md-4"><div class="text-muted small">Negocio</div>${esc(t.business)}</div>
        <div class="col-12"><div class="text-muted small">Descripción</div>${esc(t.desc || '')}</div>
        <div class="col-12"><div class="text-muted small mb-1">Evidencias</div>
          ${t.attachments.length ? ('<ul class="list-group">' + t.attachments.map(a => `<li class="list-group-item d-flex justify-content-between align-items-center"><span><i class=\"bi bi-paperclip me-1\"></i> ${esc(a.name)}</span><span class="small text-muted">${fmtBytes(a.size)}</span></li>`).join('') + '</ul>') : '<div class="text-muted">Sin evidencias.</div>'}
        </div>
      </div>`;
    bootstrap.Modal.getOrCreateInstance(el).show();
  }

  // --- Bootstrap helpers
  function showModal(el) { if (window.bootstrap?.Modal) bootstrap.Modal.getOrCreateInstance(el).show(); else { el.style.display = 'block'; el.classList.add('show'); } }
  function hideModal(el) { if (window.bootstrap?.Modal) bootstrap.Modal.getOrCreateInstance(el).hide(); else { el.style.display = 'none'; el.classList.remove('show'); } }

  // --- Bind header filters
  function hookFilters() {
    const byId = id => document.getElementById(id);
    const bindMulti = (id, key) => { const el = byId(id); if (!el) return; el.addEventListener('change', () => { filters[key] = getSelected(el); render(); }); };
    bindMulti('f-business-unit', 'unit');
    bindMulti('f-collab', 'collab');
    bindMulti('f-status', 'status');
    bindMulti('f-date', 'date');
    const clear = byId('btn-clear-filters');
    if (clear) clear.onclick = () => {
      Object.assign(filters, { unit: [], business: [], collab: [], dept: [], status: [], date: [] });
      // clear UI selections
      ['f-business-unit', 'f-collab', 'f-status', 'f-date', 'f-business', 'f-dept'].forEach(id => {
        const el = byId(id);
        if (el) Array.from(el.options).forEach(o => o.selected = false);
        el?._msRefresh?.();
      });
      renderChips();
      render();
    };
    const colsBtn = byId('btn-columns');
    if (colsBtn) colsBtn.onclick = () => {
      // Sync 'Acciones'
      const actionsCb = document.querySelector('#columns-form input[name="col_actions"]');
      if (actionsCb) actionsCb.checked = !!colVisibility.col_actions;
      openColumnsModal();
    };
    // More filters modal
    const moreBtn = byId('btn-toggle-filters');
    moreBtn && (moreBtn.onclick = () => {
      // sync options and selected for business/dept
      populateFilters();
      bootstrap.Modal.getOrCreateInstance('#more-filters-modal').show();
    });
    byId('more-filters-apply')?.addEventListener('click', () => {
      const bEl = byId('f-business');
      const dEl = byId('f-dept');
      filters.business = getSelected(bEl);
      filters.dept = getSelected(dEl);
      const df = byId('f-date-from')?.value;
      const dt = byId('f-date-to')?.value;
      if (df && dt) {
        const set = new Set(filters.date || []);
        set.add('custom');
        filters.date = Array.from(set);
      }
      bootstrap.Modal.getOrCreateInstance('#more-filters-modal').hide();
      renderChips();
      render();
    });
  }

  // Columns modal save handler (static modal)
  document.getElementById('columns-save')?.addEventListener('click', () => {
    const form = document.getElementById('columns-form');
    if (!form) return;
    // visibility: list checkboxes
    const list = document.getElementById('columns-order-list');
    if (list) {
      list.querySelectorAll('input[type="checkbox"][data-key]').forEach(cb => {
        const key = cb.getAttribute('data-key');
        const colKey = 'col_' + key;
        if (Object.prototype.hasOwnProperty.call(colVisibility, colKey)) {
          colVisibility[colKey] = !!cb.checked;
        }
      });
    }
    // visibility: acciones toggle
    Array.from(form.elements).forEach(el => {
      if (el.name && Object.prototype.hasOwnProperty.call(colVisibility, el.name)) {
        colVisibility[el.name] = !!el.checked;
      }
    });
    // save order (read back from list in DOM)
    if (list) {
      const keys = Array.from(list.querySelectorAll('li[data-key]')).map(li => li.getAttribute('data-key'));
      if (keys.length) columnsOrder = keys;
      try { localStorage.setItem(ORDER_KEY, JSON.stringify(columnsOrder)); } catch { }
    }
    saveCols();
    applyColVisibilityToState();
    bootstrap.Modal.getOrCreateInstance('#columns-modal').hide();
    render();
  });

  // Removed Offcanvas code; using "Más filtros" modal instead

  // --- Init
  const newBtn = document.getElementById('btn-new-task');
  if (newBtn) newBtn.addEventListener('click', openCreateModal);
  hookFilters();
  // load HR users and tasks from server (best-effort). then populate filters and render.
  (async () => {
    await loadHRUsers();
    const ok = await loadTasksFromServer();
    populateFilters();
    // Build MultiSelect UIs after options exist
    buildMultiSelect('f-date', 'Fecha');
    buildMultiSelect('f-business-unit', 'Unidad');
    buildMultiSelect('f-status', 'Status');
    buildMultiSelect('f-collab', 'Colaborador');
    render();
    // If API failed to load tasks, still ensure demo seed may mount if ?demo
    try { const qs = new URLSearchParams(location.search); if (!ok && qs.has('demo')) { window.__demoTasks?.(); } } catch (e) { }
  })();

  // Build MultiSelect UIs after options exist
  buildMultiSelect('f-date', 'Fecha');
  buildMultiSelect('f-business-unit', 'Unidad');
  buildMultiSelect('f-status', 'Status');
  buildMultiSelect('f-collab', 'Colaborador');
  render();
  // Build MultiSelect UIs after options exist
  buildMultiSelect('f-date', 'Fecha');
  buildMultiSelect('f-business-unit', 'Unidad');
  buildMultiSelect('f-status', 'Status');
  buildMultiSelect('f-collab', 'Colaborador');
  render();

  // If coming from Projects detail with intent to create new task, open the modal prefilled
  try {
    const qs = new URLSearchParams(location.search);
    if (qs.get('new_task') === '1' && (qs.get('project_id') || qs.get('project_name'))) {
      // slight delay to ensure UI is ready
      setTimeout(() => openCreateModal(), 150);
    }
  } catch { }
});
