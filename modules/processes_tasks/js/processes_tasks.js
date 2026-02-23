/**
 * /modules/processes_tasks/js/processes_tasks.js
 * SmartTableEditable - Tabla unificada con edición inline
 * Índice ERP - Processes & Tasks Module
 * 
 * @version 2.0
 * @features 17 campos editables, auto-guardado, filtros, paginación, exportar CSV
 */

(function (global, $) {
    'use strict';

    // Validar que el módulo esté presente
    const $root = $('#module-processes-tasks');
    if (!$root.length) {
        console.warn('[ProcessesTasksApp] Contenedor #module-processes-tasks no encontrado');
        return;
    }

    // ==========================================
    // CATÁLOGOS ESTÁTICOS
    // ==========================================
    const CATALOGOS = {
        unidades: ['Corporativo', 'Norte', 'Centro', 'Sur'],
        niveles: ['Importante', 'Urgente', 'Normal', 'Bajo'],
        tipos: ['Tarea', 'Proceso', 'Tarea de proceso'],
        ponderaciones: ['1', '2', '3', '4', '5'],
        status: ['En tiempo', 'En proceso', 'Terminada', 'Vencida', 'Auditada']
    };

    // ==========================================
    // DEFINICIÓN DE COLUMNAS EDITABLES (17 CAMPOS)
    // ==========================================
    const EDITABLE_COLUMNS = [
        { key: 'folio', label: 'Folio', editable: false, sortable: true, width: 100, renderer: folioRenderer },
        { key: 'unidad', label: 'Unidad de Negocio', editable: true, input: 'select', sortable: true, width: 180, options: CATALOGOS.unidades, renderer: editableRenderer },
        { key: 'negocio', label: 'Negocio', editable: true, input: 'select', sortable: true, width: 180, source: '/modules/processes_tasks/includes/business.php', renderer: editableRenderer },
        { key: 'titulo', label: 'Título de la tarea', editable: true, input: 'text', sortable: true, width: 250, renderer: editableRenderer },
        { key: 'descripcion', label: 'Descripción', editable: true, input: 'textarea', sortable: false, width: 300, renderer: editableRenderer },
        { key: 'fecha_inicio', label: 'Fecha de inicio', editable: true, input: 'date', sortable: true, width: 140, renderer: editableRenderer },
        { key: 'fecha_fin', label: 'Fecha de terminación', editable: true, input: 'date', sortable: true, width: 160, renderer: editableRenderer },
        { key: 'archivos', label: 'Archivos', editable: true, input: 'file', sortable: false, width: 110, renderer: filesRenderer },
        { key: 'creador', label: 'Creador', editable: true, input: 'select', sortable: true, width: 140, source: '/modules/processes_tasks/includes/users.php', renderer: editableRenderer },
        { key: 'delegado', label: 'Delegado', editable: true, input: 'select', sortable: true, width: 140, source: '/modules/processes_tasks/includes/users.php', renderer: editableRenderer },
        { key: 'nivel', label: 'Nivel', editable: true, input: 'select', sortable: true, width: 130, options: CATALOGOS.niveles, renderer: nivelRenderer },
        { key: 'tipo', label: 'Tipo', editable: true, input: 'select', sortable: true, width: 160, options: CATALOGOS.tipos, renderer: tipoRenderer },
        { key: 'proyecto', label: 'Proyecto Asignado', editable: true, input: 'select', sortable: true, width: 180, source: '/modules/processes_tasks/includes/projects.php', renderer: editableRenderer },
        { key: 'ponderacion', label: 'Ponderación', editable: true, input: 'select', sortable: true, width: 130, options: CATALOGOS.ponderaciones, renderer: ponderacionRenderer },
        { key: 'status', label: 'Status', editable: true, input: 'select', sortable: true, width: 140, options: CATALOGOS.status, renderer: statusRenderer },
        { key: 'acciones', label: 'Acciones', editable: false, sortable: false, width: 140, renderer: accionesRenderer },
        { key: 'auditar', label: 'Auditar', editable: false, sortable: false, width: 80, renderer: auditarRenderer }
    ];

    // ==========================================
    // RENDERERS PERSONALIZADOS
    // ==========================================
    function folioRenderer(row) {
        return `<span class="badge bg-secondary">${row.folio || 'N/A'}</span>`;
    }

    function editableRenderer(row, col) {
        const value = row[col.key] || '';
        return `<span class="editable-cell" data-id="${row.id}" data-field="${col.key}" data-type="${col.input}">${value || '-'}</span>`;
    }

    function nivelRenderer(row, col) {
        const nivel = row.nivel || '';
        const colorMap = { 'Importante': 'danger', 'Urgente': 'warning', 'Normal': 'primary', 'Bajo': 'secondary' };
        const color = colorMap[nivel] || 'secondary';
        return `<span class="badge bg-${color} editable-cell" data-id="${row.id}" data-field="${col.key}" data-type="${col.input}">${nivel || '-'}</span>`;
    }

    function tipoRenderer(row, col) {
        const tipo = row.tipo || '';
        return `<span class="badge bg-light text-dark editable-cell" data-id="${row.id}" data-field="${col.key}" data-type="${col.input}">${tipo || '-'}</span>`;
    }

    function ponderacionRenderer(row, col) {
        const pond = parseInt(row.ponderacion) || 1;
        const stars = '⭐'.repeat(pond);
        const colorMap = ['secondary', 'info', 'primary', 'warning', 'danger'];
        const color = colorMap[pond - 1] || 'secondary';
        return `<span class="badge bg-${color} editable-cell" data-id="${row.id}" data-field="${col.key}" data-type="${col.input}">${stars} (${pond})</span>`;
    }

    function statusRenderer(row, col) {
        const status = row.status || '';
        const colorMap = { 'En tiempo': 'success', 'En proceso': 'primary', 'Terminada': 'info', 'Vencida': 'danger', 'Auditada': 'warning' };
        const color = colorMap[status] || 'secondary';
        return `<span class="badge bg-${color} editable-cell" data-id="${row.id}" data-field="${col.key}" data-type="${col.input}">${status || '-'}</span>`;
    }

    function filesRenderer(row) {
        const count = row.archivos_count || 0;
        return `<button class="btn btn-sm btn-outline-secondary" data-action="files" data-id="${row.id}" title="Ver archivos adjuntos">
            <i class="bi bi-paperclip"></i> ${count > 0 ? `<span class="badge bg-primary ms-1">${count}</span>` : ''}
        </button>`;
    }

    function accionesRenderer(row) {
        return `<div class="btn-group btn-group-sm" role="group">
            <button class="btn btn-outline-primary" data-action="copy" data-id="${row.id}" title="Duplicar tarea"><i class="bi bi-copy"></i></button>
            <button class="btn btn-outline-success" data-action="complete" data-id="${row.id}" title="Completar tarea"><i class="bi bi-check-circle"></i></button>
            <button class="btn btn-outline-danger" data-action="delete" data-id="${row.id}" title="Eliminar tarea"><i class="bi bi-trash"></i></button>
        </div>`;
    }

    function auditarRenderer(row) {
        return `<button class="btn btn-sm btn-outline-warning" data-action="audit" data-id="${row.id}" title="Ver historial de cambios"><i class="bi bi-clipboard-check"></i></button>`;
    }

    // ==========================================
    // UTILIDADES
    // ==========================================
    const LS_KEY = (key) => `ix_table_${key}`;

    function saveLS(key, value) {
        try { localStorage.setItem(key, JSON.stringify(value)); } catch (e) { console.warn('Error guardando en localStorage:', e); }
    }

    function readLS(key, fallback) {
        try {
            const value = localStorage.getItem(key);
            return value ? JSON.parse(value) : fallback;
        } catch (e) { return fallback; }
    }

    // ==========================================
    // CLASE PRINCIPAL: SmartTableEditable
    // ==========================================
    class SmartTableEditable {
        constructor(opts) {
            this.el = opts.el;
            this.tableKey = opts.tableKey || 'default';
            this.columns = opts.columns || EDITABLE_COLUMNS;
            this.fetchData = opts.fetchData || this.realFetch.bind(this);
            this.updateEndpoint = opts.updateEndpoint || '/modules/processes_tasks/includes/update_task.php';
            this.getEndpoint = opts.getEndpoint || '/modules/processes_tasks/includes/get_tasks.php';
            this.pageSize = opts.pageSize || 25;
            this._handlers = {};
            this._catalogCache = {};

            const saved = readLS(LS_KEY(this.tableKey), null);
            this.state = saved || {
                search: '', sortBy: null, sortDir: 'asc',
                filters: { tipo: 'Todos', unidad: 'Todas', status: 'Todos' },
                visibleColumns: this.columns.filter(c => c.key !== 'descripcion').map(c => c.key),
                orderColumns: this.columns.map(c => c.key), page: 1
            };

            this._init();
        }

        async _init() {
            await this._loadCatalogs();
            this._buildSkeleton();
            this._bindEvents();
            this.refresh();
        }

        async _loadCatalogs() {
            const sourceCols = this.columns.filter(c => c.source);
            for (const col of sourceCols) {
                try {
                    const response = await fetch(col.source);
                    const data = await response.json();
                    this._catalogCache[col.key] = data;
                    col.options = data.map(item => item.label || item.name || item);
                } catch (err) {
                    console.warn(`Error cargando catálogo ${col.key}:`, err);
                    col.options = [];
                }
            }
        }

        _buildSkeleton() {
            this.el.innerHTML = `
                <div class="ptable-wrapper animate-fade-in">
                    <div class="ptable-toolbar">
                        <div class="ptable-toolbar-left">
                            <input type="search" class="ptable-search" placeholder="Buscar..." data-role="search">
                            <select class="form-select" data-role="filter-tipo" style="width: 180px;">
                                <option value="Todos">Todos los tipos</option>
                                ${CATALOGOS.tipos.map(t => `<option value="${t}">${t}</option>`).join('')}
                            </select>
                            <select class="form-select" data-role="filter-unidad" style="width: 180px;">
                                <option value="Todas">Todas las unidades</option>
                                ${CATALOGOS.unidades.map(u => `<option value="${u}">${u}</option>`).join('')}
                            </select>
                            <select class="form-select" data-role="filter-status" style="width: 180px;">
                                <option value="Todos">Todos los status</option>
                                ${CATALOGOS.status.map(s => `<option value="${s}">${s}</option>`).join('')}
                            </select>
                        </div>
                        <div class="ptable-toolbar-right">
                            <button class="btn btn-ghost btn-sm" data-role="config"><i class="bi bi-sliders"></i> Configurar</button>
                            <button class="btn btn-brand btn-sm" data-role="export"><i class="bi bi-download"></i> Exportar</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead data-role="thead"></thead>
                            <tbody data-role="tbody"><tr><td colspan="50" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr></tbody>
                        </table>
                    </div>

                    <div class="ptable-footer">
                        <div data-role="meta" class="text-muted"></div>
                        <div data-role="pager"></div>
                    </div>
                </div>

                <div class="modal fade" data-role="config-modal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="bi bi-sliders me-2"></i>Configurar Columnas</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted mb-3">Arrastra para reordenar • Activa/desactiva columnas</p>
                                <ul class="list-group" data-role="config-list"></ul>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-brand" data-role="config-save">Guardar</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            this._refs = {
                search: this.el.querySelector('[data-role="search"]'),
                filterTipo: this.el.querySelector('[data-role="filter-tipo"]'),
                filterUnidad: this.el.querySelector('[data-role="filter-unidad"]'),
                filterStatus: this.el.querySelector('[data-role="filter-status"]'),
                thead: this.el.querySelector('[data-role="thead"]'),
                tbody: this.el.querySelector('[data-role="tbody"]'),
                meta: this.el.querySelector('[data-role="meta"]'),
                pager: this.el.querySelector('[data-role="pager"]'),
                configBtn: this.el.querySelector('[data-role="config"]'),
                exportBtn: this.el.querySelector('[data-role="export"]'),
                configModal: this.el.querySelector('[data-role="config-modal"]'),
                configList: this.el.querySelector('[data-role="config-list"]'),
                configSave: this.el.querySelector('[data-role="config-save"]')
            };
        }

        _bindEvents() {
            let searchTimeout;

            this._refs.search.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => { this.state.search = e.target.value.trim(); this.state.page = 1; this.refresh(); }, 300);
            });

            this._refs.filterTipo.addEventListener('change', (e) => { this.state.filters.tipo = e.target.value; this.state.page = 1; this.refresh(); });
            this._refs.filterUnidad.addEventListener('change', (e) => { this.state.filters.unidad = e.target.value; this.state.page = 1; this.refresh(); });
            this._refs.filterStatus.addEventListener('change', (e) => { this.state.filters.status = e.target.value; this.state.page = 1; this.refresh(); });

            this._refs.thead.addEventListener('click', (e) => {
                const th = e.target.closest('[data-sort]');
                if (!th) return;
                const key = th.dataset.sort;
                if (this.state.sortBy === key) {
                    this.state.sortDir = this.state.sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    this.state.sortBy = key; this.state.sortDir = 'asc';
                }
                this.refresh();
            });

            this._refs.tbody.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-action]');
                if (btn) {
                    const action = btn.dataset.action;
                    const id = btn.dataset.id;
                    const row = this._lastRows.find(r => String(r.id) === String(id));
                    this._handleAction(action, id, row);
                    return;
                }

                const cell = e.target.closest('.editable-cell');
                if (cell) this._makeEditable(cell);
            });

            this._refs.pager.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-page]');
                if (!btn) return;
                const page = parseInt(btn.dataset.page);
                if (!isNaN(page) && page !== this.state.page) { this.state.page = page; this.refresh(); }
            });

            this._refs.configBtn.addEventListener('click', () => {
                this._renderConfigModal();
                const modal = new bootstrap.Modal(this._refs.configModal);
                modal.show();
            });

            this._refs.configSave.addEventListener('click', () => {
                const items = this._refs.configList.querySelectorAll('[data-key]');
                const newOrder = [], newVisible = [];
                items.forEach(li => {
                    const key = li.dataset.key;
                    newOrder.push(key);
                    if (li.querySelector('input[type="checkbox"]').checked) newVisible.push(key);
                });
                this.state.orderColumns = newOrder;
                this.state.visibleColumns = newVisible;
                this._saveState();
                this.refresh();
                bootstrap.Modal.getInstance(this._refs.configModal).hide();
            });

            this._refs.exportBtn.addEventListener('click', () => this._exportCSV());
            this._setupDragDrop();
        }

        _setupDragDrop() {
            let draggedLi = null;
            this._refs.configList.addEventListener('dragstart', (e) => { if (e.target.tagName === 'LI') { draggedLi = e.target; e.target.classList.add('opacity-50'); } });
            this._refs.configList.addEventListener('dragend', (e) => { if (e.target.tagName === 'LI') { e.target.classList.remove('opacity-50'); draggedLi = null; } });
            this._refs.configList.addEventListener('dragover', (e) => {
                e.preventDefault();
                if (!draggedLi) return;
                const afterElement = this._getDragAfterElement(this._refs.configList, e.clientY);
                if (afterElement == null) this._refs.configList.appendChild(draggedLi);
                else this._refs.configList.insertBefore(draggedLi, afterElement);
            });
        }

        _getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('li:not(.opacity-50)')];
            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) return { offset: offset, element: child };
                else return closest;
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }

        _makeEditable(cell) {
            if (cell.querySelector('input, select, textarea')) return;

            const id = cell.dataset.id;
            const field = cell.dataset.field;
            const type = cell.dataset.type;
            const col = this.columns.find(c => c.key === field);

            if (!col || !col.editable) return;

            const oldValue = cell.textContent.trim();
            let input;
            const baseStyle = "width:100%;font-size:0.85rem;border:1px solid #cbd5e1;border-radius:6px;padding:4px 8px;background:#f8fafc;";

            switch (type) {
                case 'select':
                    input = document.createElement('select');
                    input.className = 'form-select form-select-sm';
                    input.style.cssText = baseStyle;
                    const options = col.options || [];
                    input.innerHTML = `<option value="">Seleccionar...</option>` + options.map(opt => {
                        const selected = opt === oldValue || opt === oldValue.replace(/[⭐\(\)]/g, '').trim();
                        return `<option value="${opt}" ${selected ? 'selected' : ''}>${opt}</option>`;
                    }).join('');
                    break;
                case 'date':
                    input = document.createElement('input');
                    input.type = 'date';
                    input.className = 'form-control form-control-sm';
                    input.value = oldValue;
                    input.style.cssText = baseStyle;
                    break;
                case 'textarea':
                    input = document.createElement('textarea');
                    input.className = 'form-control form-control-sm';
                    input.rows = 2;
                    input.value = oldValue === '-' ? '' : oldValue;
                    input.style.cssText = baseStyle + 'resize:vertical;min-height:50px;';
                    break;
                default:
                    input = document.createElement('input');
                    input.type = 'text';
                    input.className = 'form-control form-control-sm';
                    input.value = oldValue === '-' ? '' : oldValue;
                    input.style.cssText = baseStyle;
            }

            const save = async () => {
                let newValue = input.value.trim();

                if (field === 'fecha_fin' && newValue) {
                    const row = this._lastRows.find(r => String(r.id) === String(id));
                    if (row && row.fecha_inicio && newValue < row.fecha_inicio) {
                        this._showToast('⚠️ La fecha de terminación debe ser posterior a la fecha de inicio', 'warning');
                        cell.innerHTML = this._renderCellContent(row, col);
                        return;
                    }
                }

                if (newValue !== oldValue && newValue !== oldValue.replace(/[⭐\(\)]/g, '').trim()) {
                    const success = await this._saveField(id, field, newValue);
                    if (success) {
                        const row = this._lastRows.find(r => String(r.id) === String(id));
                        if (row) {
                            row[field] = newValue;
                            cell.innerHTML = this._renderCellContent(row, col);
                        }
                    } else {
                        cell.innerHTML = this._renderCellContent({ [field]: oldValue }, col);
                    }
                } else {
                    const row = this._lastRows.find(r => String(r.id) === String(id));
                    cell.innerHTML = this._renderCellContent(row || { [field]: oldValue }, col);
                }
            };

            cell.innerHTML = '';
            cell.appendChild(input);
            cell.classList.add('editing-cell');
            input.focus();

            if (type === 'select') {
                input.addEventListener('change', save);
            } else {
                input.addEventListener('blur', save);
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && type !== 'textarea') { e.preventDefault(); save(); }
                    if (e.key === 'Escape') {
                        const row = this._lastRows.find(r => String(r.id) === String(id));
                        cell.innerHTML = this._renderCellContent(row || { [field]: oldValue }, col);
                        cell.classList.remove('editing-cell');
                    }
                });
            }
        }

        _renderCellContent(row, col) {
            if (col.renderer) return col.renderer(row, col);
            return row[col.key] || '-';
        }

        async _saveField(id, field, value) {
            const cell = this.el.querySelector(`.editable-cell[data-id="${id}"][data-field="${field}"]`);
            if (cell) { cell.style.border = '2px solid #ffd650'; cell.style.background = '#fff8dc'; }

            try {
                const response = await fetch(this.updateEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, field, value })
                });

                const data = await response.json();

                if (data.success) {
                    if (cell) {
                        cell.style.border = '2px solid #2bb673';
                        cell.style.background = '#d1fae5';
                        setTimeout(() => { cell.style.border = ''; cell.style.background = ''; cell.classList.remove('editing-cell'); }, 1000);
                    }
                    this._showToast('✅ Cambios guardados', 'success');
                    return true;
                } else {
                    throw new Error(data.message || 'Error al guardar');
                }
            } catch (error) {
                console.error('Error guardando campo:', error);
                if (cell) {
                    cell.style.border = '2px solid #e74c3c';
                    cell.style.background = '#fee2e2';
                    setTimeout(() => { cell.style.border = ''; cell.style.background = ''; cell.classList.remove('editing-cell'); }, 2000);
                }
                this._showToast('❌ Error al guardar: ' + error.message, 'danger');
                return false;
            }
        }

        _renderHead() {
            const cols = this._getVisibleColumns();
            const html = cols.map(c => {
                const sortable = c.sortable ? 'cursor-pointer' : '';
                let sortIcon = '';
                if (c.sortable && this.state.sortBy === c.key) sortIcon = this.state.sortDir === 'asc' ? ' ▲' : ' ▼';
                return `<th class="${sortable}" ${c.sortable ? `data-sort="${c.key}"` : ''} style="width: ${c.width}px;">${c.label}${sortIcon}</th>`;
            }).join('');
            this._refs.thead.innerHTML = `<tr>${html}</tr>`;
        }

        async refresh() {
            this._saveState();
            this._renderHead();
            this._refs.tbody.innerHTML = '<tr><td colspan="50" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr>';

            try {
                const params = { search: this.state.search, sortBy: this.state.sortBy, sortDir: this.state.sortDir, filters: this.state.filters, page: this.state.page, pageSize: this.pageSize };
                const result = await this.fetchData(params);
                this._lastRows = result.rows;
                this._lastTotal = result.total;
                this._renderBody(result.rows);
                this._renderPager(result.total);
                this._refs.meta.textContent = `Mostrando ${result.rows.length} de ${result.total} resultados`;
                this._enableTooltips();
            } catch (err) {
                console.error('Error cargando datos:', err);
                this._refs.tbody.innerHTML = '<tr><td colspan="50" class="text-center text-danger py-4">Error al cargar datos</td></tr>';
            }
        }

        _renderBody(rows) {
            if (rows.length === 0) {
                this._refs.tbody.innerHTML = '<tr><td colspan="50" class="text-center text-muted py-5">No hay resultados</td></tr>';
                return;
            }

            const cols = this._getVisibleColumns();
            const html = rows.map(row => {
                const cells = cols.map(c => {
                    let content = c.renderer ? c.renderer(row, c) : (row[c.key] || '-');
                    return `<td>${content}</td>`;
                }).join('');
                return `<tr data-id="${row.id}">${cells}</tr>`;
            }).join('');

            this._refs.tbody.innerHTML = html;
        }

        _renderPager(total) {
            const totalPages = Math.ceil(total / this.pageSize);
            if (totalPages <= 1) { this._refs.pager.innerHTML = ''; return; }

            const current = this.state.page;
            let pages = [];

            if (totalPages <= 7) {
                for (let i = 1; i <= totalPages; i++) pages.push(i);
            } else {
                pages.push(1);
                if (current > 3) pages.push('...');
                for (let i = Math.max(2, current - 1); i <= Math.min(totalPages - 1, current + 1); i++) pages.push(i);
                if (current < totalPages - 2) pages.push('...');
                pages.push(totalPages);
            }

            const html = `<div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary" data-page="${current - 1}" ${current === 1 ? 'disabled' : ''}><i class="bi bi-chevron-left"></i></button>
                ${pages.map(p => p === '...' ? '<button class="btn btn-outline-secondary" disabled>...</button>' : `<button class="btn ${p === current ? 'btn-brand' : 'btn-outline-secondary'}" data-page="${p}">${p}</button>`).join('')}
                <button class="btn btn-outline-secondary" data-page="${current + 1}" ${current === totalPages ? 'disabled' : ''}><i class="bi bi-chevron-right"></i></button>
            </div>`;
            this._refs.pager.innerHTML = html;
        }

        _renderConfigModal() {
            const html = this.state.orderColumns.map(key => {
                const col = this.columns.find(c => c.key === key);
                if (!col) return '';
                const checked = this.state.visibleColumns.includes(key) ? 'checked' : '';
                const disabled = ['folio', 'acciones'].includes(key) ? 'disabled' : '';
                return `<li class="list-group-item d-flex align-items-center gap-3" draggable="${!disabled}" data-key="${key}">
                    ${!disabled ? '<i class="bi bi-grip-vertical text-muted cursor-pointer"></i>' : ''}
                    <div class="form-check form-switch mb-0 flex-grow-1">
                        <input class="form-check-input" type="checkbox" ${checked} ${disabled}>
                        <label class="form-check-label">${col.label}</label>
                    </div>
                </li>`;
            }).join('');
            this._refs.configList.innerHTML = html;
        }

        _getVisibleColumns() {
            const order = this.state.orderColumns.filter(k => this.state.visibleColumns.includes(k));
            return order.map(k => this.columns.find(c => c.key === k)).filter(Boolean);
        }

        _exportCSV() {
            const cols = this._getVisibleColumns();
            const header = cols.map(c => c.label).join(',');
            const rows = this._lastRows.map(row => cols.map(c => {
                let val = row[c.key] || '';
                if (c.input === 'date' && val) val = new Date(val).toLocaleDateString('es-MX');
                return `"${String(val).replace(/"/g, '""')}"`;
            }).join(',')).join('\n');
            const csv = header + '\n' + rows;
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `${this.tableKey}_${new Date().toISOString().slice(0, 10)}.csv`;
            link.click();
        }

        _saveState() { saveLS(LS_KEY(this.tableKey), this.state); }

        _enableTooltips() {
            setTimeout(() => {
                const tooltipTriggerList = this.el.querySelectorAll('[title]');
                [...tooltipTriggerList].forEach(el => { if (!el._tooltip) el._tooltip = new bootstrap.Tooltip(el); });
            }, 100);
        }

        _showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
            toast.style.zIndex = '9999';
            toast.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
            document.body.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
            bsToast.show();
            setTimeout(() => toast.remove(), 3500);
        }

        _handleAction(action, id, row) {
            switch (action) {
                case 'copy': if (confirm(`¿Duplicar la tarea "${row.titulo}"?`)) this._showToast('Funcionalidad en desarrollo', 'info'); break;
                case 'complete': if (confirm(`¿Marcar como completada la tarea "${row.titulo}"?`)) { this._saveField(id, 'status', 'Terminada'); this.refresh(); } break;
                case 'delete': if (confirm(`¿Eliminar la tarea "${row.titulo}"?\n\nEsta acción no se puede deshacer.`)) this._showToast('Funcionalidad en desarrollo', 'info'); break;
                case 'audit': this._showToast('Modal de auditoría en desarrollo', 'info'); break;
                case 'files': this._showToast('Modal de archivos en desarrollo', 'info'); break;
            }
        }

        async realFetch(params) {
            try {
                const url = new URL(this.getEndpoint, window.location.origin);
                url.searchParams.append('page', params.page || 1);
                url.searchParams.append('pageSize', params.pageSize || this.pageSize);

                if (params.search) url.searchParams.append('search', params.search);
                if (params.filters.tipo !== 'Todos') url.searchParams.append('tipo', params.filters.tipo);
                if (params.filters.unidad !== 'Todas') url.searchParams.append('unidad', params.filters.unidad);
                if (params.filters.status !== 'Todos') url.searchParams.append('status', params.filters.status);
                if (params.sortBy) {
                    url.searchParams.append('sortBy', params.sortBy);
                    url.searchParams.append('sortDir', params.sortDir || 'asc');
                }

                const response = await fetch(url);
                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const result = await response.json();
                if (!result.success) throw new Error(result.message || 'Error al cargar datos');

                return {
                    rows: result.data || [],
                    total: result.total || 0
                };
            } catch (error) {
                console.error('Error en realFetch:', error);
                this._showToast('Error al cargar datos: ' + error.message, 'danger');
                return { rows: [], total: 0 };
            }
        }

        async mockFetch(params) {
            await new Promise(r => setTimeout(r, 400));

            let data = [];
            for (let i = 1; i <= 150; i++) {
                data.push({
                    id: i, folio: `T-${String(i).padStart(5, '0')}`,
                    unidad: CATALOGOS.unidades[Math.floor(Math.random() * CATALOGOS.unidades.length)],
                    negocio: ['Netflix', 'Amazon Prime', 'Disney+', 'HBO Max'][Math.floor(Math.random() * 4)],
                    titulo: `Tarea de ejemplo ${i}`,
                    descripcion: `Descripción detallada de la tarea número ${i} con información relevante.`,
                    fecha_inicio: new Date(2024, 10, Math.floor(Math.random() * 28) + 1).toISOString().slice(0, 10),
                    fecha_fin: new Date(2024, 11, Math.floor(Math.random() * 28) + 1).toISOString().slice(0, 10),
                    archivos_count: Math.floor(Math.random() * 6),
                    creador: ['Juan Pérez', 'María García', 'Carlos López'][Math.floor(Math.random() * 3)],
                    delegado: ['Ana Martínez', 'Luis Rodríguez', 'Sofia Torres'][Math.floor(Math.random() * 3)],
                    nivel: CATALOGOS.niveles[Math.floor(Math.random() * CATALOGOS.niveles.length)],
                    tipo: CATALOGOS.tipos[Math.floor(Math.random() * CATALOGOS.tipos.length)],
                    proyecto: ['Proyecto Alpha', 'Proyecto Beta', 'Proyecto Gamma'][Math.floor(Math.random() * 3)],
                    ponderacion: (Math.floor(Math.random() * 5) + 1).toString(),
                    status: CATALOGOS.status[Math.floor(Math.random() * CATALOGOS.status.length)]
                });
            }

            if (params.search) {
                const s = params.search.toLowerCase();
                data = data.filter(r => r.titulo.toLowerCase().includes(s) || r.descripcion.toLowerCase().includes(s) || r.folio.toLowerCase().includes(s));
            }

            if (params.filters.tipo !== 'Todos') data = data.filter(r => r.tipo === params.filters.tipo);
            if (params.filters.unidad !== 'Todas') data = data.filter(r => r.unidad === params.filters.unidad);
            if (params.filters.status !== 'Todos') data = data.filter(r => r.status === params.filters.status);

            if (params.sortBy) {
                data.sort((a, b) => {
                    const aVal = a[params.sortBy] || '', bVal = b[params.sortBy] || '';
                    const cmp = aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
                    return params.sortDir === 'asc' ? cmp : -cmp;
                });
            }

            const total = data.length;
            const start = (params.page - 1) * params.pageSize;
            const rows = data.slice(start, start + params.pageSize);

            return { rows, total };
        }

        on(event, handler) {
            if (!this._handlers[event]) this._handlers[event] = [];
            this._handlers[event].push(handler);
        }
    }

    // Exponer API pública
    global.ProcessesTasksApp = {
        SmartTableEditable: SmartTableEditable,
        EDITABLE_COLUMNS: EDITABLE_COLUMNS,
        CATALOGOS: CATALOGOS,
        $root: $root,
        init: function () {
            // Inicialización adicional si es necesaria
            console.log('[ProcessesTasksApp] Módulo inicializado');
        }
    };

    // Auto-inicializar al cargar
    $(function () {
        if (global.ProcessesTasksApp && global.ProcessesTasksApp.init) {
            global.ProcessesTasksApp.init();
        }
    });

})(window, jQuery);
