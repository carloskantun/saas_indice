// /modules/processes_tasks/js/organigrama.js
console.log('[Processes & Tasks] ORGANIGRAMA JS loaded');

(function () {
  const state = {
    people: [],            // {id, name, position, avatar}
    nodes: {},             // id -> {x,y}
    relations: [],         // [{child_id, parent_id}]
    selected: null         // id of selected node for linking
  };
  const LSK = {
    nodes: 'org_nodes_v1',
    rels: 'org_rels_v1'
  };

  const $ = sel => document.querySelector(sel);
  const $$ = sel => Array.from(document.querySelectorAll(sel));
  const wrap = $('#org-canvas-wrap');
  const canvas = $('#org-canvas');
  const svg = $('#org-lines');
  const list = $('#org-people-list');
  const countEl = document.getElementById('org-count');

  function esc(s) { return String(s || '').replace(/[&<>"]/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", "\"": "&quot;" }[c])); }

  function nodeEl(id) {
    return canvas?.querySelector(`.org-node[data-id="${id}"]`);
  }

  // Flow creation functionality
  function createFlow(parentId, childId) {
    try {
      const parent = state.people.find(p => p.id === Number(parentId));
      const child = state.people.find(p => p.id === Number(childId));

      if (!parent || !child) {
        console.warn('Cannot create flow: parent or child not found', parentId, childId);
        return false;
      }

      const flows = JSON.parse(localStorage.getItem('indice_flows_v1') || '[]');

      // Check if flow already exists
      const existingFlow = flows.find(f => f.parentId === String(parentId) && f.childId === String(childId));
      if (existingFlow) {
        window.showToast && window.showToast('Ya existe un flujo entre estos usuarios', 'warning');
        return false;
      }

      const newFlow = {
        id: 'flow_' + String(flows.length + 1).padStart(3, '0'),
        name: `Flujo: ${parent.name} → ${child.name}`,
        parentId: String(parentId),
        parentName: parent.name,
        childId: String(childId),
        childName: child.name,
        createdAt: new Date().toISOString()
      };

      flows.push(newFlow);
      localStorage.setItem('indice_flows_v1', JSON.stringify(flows));

      window.showToast && window.showToast('Flujo de trabajo creado', 'success');
      console.log('[Organigrama] Flow created:', newFlow);
      return true;
    } catch (error) {
      console.error('Error creating flow:', error);
      window.showToast && window.showToast('Error al crear flujo', 'danger');
      return false;
    }
  }

  function addRelation(parentId, childId) {
    // Avoid duplicates
    const exists = state.relations.some(r => r.parent_id === Number(parentId) && r.child_id === Number(childId));
    if (exists) {
      console.warn('[Organigrama] Relación ya existe:', parentId, '→', childId);
      return false;
    }

    state.relations.push({ parent_id: Number(parentId), child_id: Number(childId) });
    console.info('[Organigrama] Nueva relación:', parentId, '→', childId);
    persist();
    drawLines();

    // Create flow when relation is added
    createFlow(parentId, childId);
    return true;
  }

  function onNodeClick(id) {
    console.log('[Organigrama] Click en nodo:', id, 'Estado actual seleccionado:', state.selected);
    
    if (state.selected === null) {
      // First click: select parent
      state.selected = Number(id);
      const node = nodeEl(id);
      if (node) {
        // Limpiar cualquier selección previa
        canvas.querySelectorAll('.org-node.org-selected').forEach(n => n.classList.remove('org-selected'));
        node.classList.add('org-selected');
        console.log('[Organigrama] Nodo seleccionado como padre:', id);
        window.showToast && window.showToast('Nodo seleccionado. Haz clic en otro para conectar.', 'info');
      }
    } else if (state.selected === Number(id)) {
      // Same node: deselect
      console.log('[Organigrama] Deseleccionando nodo:', id);
      state.selected = null;
      const node = nodeEl(id);
      if (node) node.classList.remove('org-selected');
      window.showToast && window.showToast('Selección cancelada', 'secondary');
    } else {
      // Second click: create connection
      const parentId = state.selected;
      const childId = Number(id);
      
      console.log('[Organigrama] Intentando crear conexión:', parentId, '→', childId);

      if (addRelation(parentId, childId)) {
        window.showToast && window.showToast(`Conexión creada: ${parentId} → ${childId}`, 'success');
        console.log('[Organigrama] Conexión creada exitosamente');
      } else {
        window.showToast && window.showToast('Esta conexión ya existe', 'warning');
        console.log('[Organigrama] Conexión ya existe');
      }

      // Clear selection
      const parentNode = nodeEl(state.selected);
      if (parentNode) parentNode.classList.remove('org-selected');
      state.selected = null;
      console.log('[Organigrama] Selección limpiada');
    }
  }

  async function loadPeople() {
    try {
      // Prefer server-rendered list if present
      const serverItems = Array.from((list || document.createElement('div')).querySelectorAll('.org-user[data-id]'));
      if (serverItems.length) {
        state.people = serverItems.map(el => ({
          id: Number(el.dataset.id),
          name: el.dataset.name || el.getAttribute('aria-label') || ('Empleado ' + el.dataset.id),
          unit: el.dataset.unit || '',
          position: el.dataset.position || '',
          department: el.dataset.department || '',
          avatar: null,
          status: (el.dataset.status || 'active')
        }));
        return true;
      }
      // Fallback to API if no server-rendered users
      const res = await fetch('/modules/processes_tasks/api/hr_users.php', { credentials: 'same-origin' });
      const j = await res.json();
      if (j && j.ok && Array.isArray(j.items)) {
        state.people = j.items.map(u => {
          let raw = (u.status ?? u.state ?? u.employment_status ?? (u.active ? 'active' : '') ?? '').toString().toLowerCase();
          if (raw === '' && (u.is_active === 0 || u.is_active === false)) raw = 'inactive';
          const status = (raw === 'active' || raw === 'activo' || raw === 'activa' || raw === '1' || raw === 'true' || raw === true) ? 'active' : 'inactive';
          const unit = u.business_unit || u.unit || u.unidad || u.unidad_negocio || u.empresa || '';
          const position = u.position || u.cargo || u.rol || u.title || u.puesto || u.job_title || '';
          const department = u.department || u.depto || u.departamento || u.area || '';
          return { id: Number(u.id), name: u.name || u.full_name || ('Empleado ' + u.id), position, unit, department, avatar: u.avatar || null, status };
        });
        return true;
      }
    } catch (e) { console.warn('org: loadPeople', e); }
    state.people = [];
    return false;
  }

  function restore() {
    try {
      state.nodes = JSON.parse(localStorage.getItem(LSK.nodes) || '{}') || {};
      state.relations = JSON.parse(localStorage.getItem(LSK.rels) || '[]') || [];
    } catch (_) { }
  }
  function persist() {
    localStorage.setItem(LSK.nodes, JSON.stringify(state.nodes));
    localStorage.setItem(LSK.rels, JSON.stringify(state.relations));
    // Try to save to backend if available (no DB changes required; endpoint optional)
    try {
      fetch('/modules/processes_tasks/api/org.save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ nodes: state.nodes, relations: state.relations })
      }).catch(() => { });
    } catch (_) { }
  }

  function loadState() {
    try {
      const savedNodes = localStorage.getItem(LSK.nodes);
      const savedRels = localStorage.getItem(LSK.rels);
      
      if (savedNodes) {
        state.nodes = JSON.parse(savedNodes) || {};
      }
      
      if (savedRels) {
        state.relations = JSON.parse(savedRels) || [];
      }
      
      console.info(`[Organigrama] Estado cargado: ${Object.keys(state.nodes).length} nodos, ${state.relations.length} relaciones`);
    } catch (error) {
      console.warn('[Organigrama] Error cargando estado:', error);
      state.nodes = {};
      state.relations = [];
    }
  }

  function renderPeople(filter) {
    const term = (filter || '').toLowerCase();
    const items = state.people.filter(p => {
      if (!term) return true;
      return (p.name || '').toLowerCase().includes(term)
        || (p.position || '').toLowerCase().includes(term)
        || (p.unit || '').toLowerCase().includes(term)
        || (p.department || '').toLowerCase().includes(term);
    });
    const hi = (text, q) => {
      const t = String(text || '');
      if (!q) return esc(t);
      const re = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'ig');
      const parts = t.split(re);
      return parts.map((part, i) => i % 2 ? ('<mark>' + esc(part) + '</mark>') : esc(part)).join('');
    };
    list.innerHTML = items.map(p => {
      const metaRaw = [p.unit ? `Unidad: ${p.unit}` : '', p.position ? `Puesto: ${p.position}` : '', p.department ? `Depto: ${p.department}` : ''].filter(Boolean).join(' | ');
      const draggable = (p.status === 'active') ? 'true' : 'false';
      const status = p.status || 'active';
      return `<button type="button" class="org-user list-group-item list-group-item-action d-flex align-items-center gap-2" draggable="${draggable}" data-id="${p.id}" data-status="${esc(status)}" data-name="${esc(p.name)}" data-position="${esc(p.position || '')}" aria-label="${esc(p.name)}">
        <span class="avatar">${esc((p.name || '?').charAt(0).toUpperCase())}</span>
        <span class="flex-grow-1 text-start">
          <div class="fw-semibold">${hi(p.name, term)}</div>
          <div class="small text-muted">${hi(metaRaw, term)}</div>
        </span>
      </button>`;
    }).join('');
    if (countEl) {
      const total = state.people.length;
      const txt = term ? `${items.length} de ${total} resultados` : `${total} colaboradores`;
      countEl.textContent = txt;
    }

    // Enable drag functionality for the users
    if (typeof window.enableOrgUserDrag === 'function') {
      setTimeout(() => window.enableOrgUserDrag(), 100);
    }
  }

  function nodeEl(id) { return canvas.querySelector(`.org-node[data-id="${CSS.escape(String(id))}"]`); }

  function ensureNode(id) {
    if (nodeEl(id)) return;
    const p = state.people.find(x => x.id === Number(id));
    const el = document.createElement('div');
    el.className = 'org-node card card-min';
    el.setAttribute('tabindex', '0');
    el.setAttribute('role', 'button');
    el.dataset.id = String(id);
    const status = (p?.status || 'active');
    el.dataset.status = status;
    if (status !== 'active') { el.classList.add('inactive'); el.title = 'Usuario inactivo'; }
    // Build meta line: Unidad | Puesto | Depto (only show available pieces)
    function metaLine(p) {
      const parts = [];
      if (p?.unit) parts.push(`Unidad: ${esc(p.unit)}`);
      if (p?.position) parts.push(`Puesto: ${esc(p.position)}`);
      if (p?.department) parts.push(`Depto: ${esc(p.department)}`);
      return parts.join(' | ');
    }
    el.innerHTML = `
      <div class="org-node-inner position-relative">
        <button type="button" class="connect-btn" title="Conectar con otro nodo" aria-label="Conectar nodo"></button>
        <div class="d-flex align-items-center gap-2">
          <div class="avatar">${(p?.name || '?').charAt(0).toUpperCase()}</div>
          <div>
            <div class="fw-semibold">${esc(p?.name || ('ID ' + id))}</div>
            <div class="small text-muted">${metaLine(p)}</div>
          </div>
        </div>
      </div>`;
    el.style.left = (state.nodes[id]?.x ?? 24) + 'px';
    el.style.top = (state.nodes[id]?.y ?? 24) + 'px';
    canvas.appendChild(el);
    makeDraggable(el);
    
    // Event listener para el nodo completo
    el.addEventListener('click', (e) => {
      // Solo si no es el botón de conectar
      if (!e.target.classList.contains('connect-btn')) {
        e.preventDefault();
        e.stopPropagation();
        console.log('[Organigrama] Click event en nodo:', id, 'Target:', e.target.className);
        onNodeClick(id);
      }
    });
    
    // Event listener específico para el botón de conectar
    const connectBtn = el.querySelector('.connect-btn');
    if (connectBtn) {
      connectBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        console.log('[Organigrama] Click en botón conectar:', id);
        onNodeClick(id);
      });
    }
    // Enable drag-to-connect via HTML5 DnD (Ctrl + arrastrar o usando el handle)
    el.draggable = true;
    el.addEventListener('dragstart', (e) => {
      const isHandle = !!e.target.closest('.org-link-handle');
      if (!e.ctrlKey && !isHandle) { e.preventDefault(); return; }
      try { e.dataTransfer.setData('orgNodeId', String(id)); } catch (_) { }
      el.classList.add('drag-connecting');
    });
    el.addEventListener('dragend', () => el.classList.remove('drag-connecting'));
  }

  // Disabled: DnD is handled centrally by /modules/processes_tasks/js/organigrama-dragfix.js
  function handleDnd() { /* no-op to avoid duplicate listeners */ }

  function getPoint(e) {
    const rect = canvas.getBoundingClientRect();
    const x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
    const y = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top;
    return { x, y };
  }

  function drawLines() {
    console.log('[Organigrama] Dibujando líneas, relaciones:', state.relations.length);
    
    if (!svg || !canvas) {
      console.warn('[Organigrama] SVG o Canvas no encontrado');
      return;
    }
    
    svg.setAttribute('width', String(canvas.clientWidth));
    svg.setAttribute('height', String(canvas.clientHeight));
    
    const lines = state.relations.map((r, index) => {
      const a = nodeEl(r.parent_id), b = nodeEl(r.child_id);
      if (!a || !b) {
        console.warn('[Organigrama] Nodos no encontrados para relación:', r.parent_id, '→', r.child_id);
        return '';
      }
      
      const ar = a.getBoundingClientRect();
      const br = b.getBoundingClientRect();
      const cr = canvas.getBoundingClientRect();
      const x1 = (ar.left - cr.left) + ar.width / 2 + canvas.scrollLeft;
      const y1 = (ar.top - cr.top) + ar.height + canvas.scrollTop;
      const x2 = (br.left - cr.left) + br.width / 2 + canvas.scrollLeft;
      const y2 = (br.top - cr.top) + canvas.scrollTop;
      const midY = (y1 + y2) / 2;
      
      console.log(`[Organigrama] Línea ${index + 1}: (${x1},${y1}) → (${x2},${y2})`);
      
      // Polyline elbow
      return `<path d="M ${x1} ${y1} L ${x1} ${midY} L ${x2} ${midY} L ${x2} ${y2}" class="org-connection" stroke="#6366f1" stroke-width="3" fill="none" />`;
    }).join('');
    
    svg.innerHTML = `<g>${lines}</g>`;
    console.log('[Organigrama] SVG actualizado con', state.relations.length, 'líneas');
  }

  // Export redrawConnections function globally
  window.redrawConnections = drawLines;
  
  // Debug function
  window.debugOrganigrama = function() {
    console.log('=== DEBUG ORGANIGRAMA ===');
    console.log('People:', state.people.length);
    console.log('Nodes:', Object.keys(state.nodes).length);
    console.log('Relations:', state.relations.length);
    console.log('Selected:', state.selected);
    console.log('Canvas nodes:', canvas.querySelectorAll('.org-node').length);
    console.log('State object:', state);
    console.log('=========================');
  };
  
  // Manual connect function for debugging
  window.connectNodes = function(parentId, childId) {
    console.log('Manual connection attempt:', parentId, '→', childId);
    return addRelation(parentId, childId);
  };

  /* handleDnd disabled; managed by organigrama-dragfix.js */

  function attachUI() {
    // Debounce helper for smoother filtering
    const debounce = (fn, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };
    const onInput = debounce((e) => renderPeople(e.target.value), 120);
    $('#org-search')?.addEventListener('input', onInput);
    $('#org-clear-search')?.addEventListener('click', () => {
      const inp = document.getElementById('org-search');
      if (inp) { inp.value = ''; renderPeople(''); inp.focus(); }
    });
    $('#org-clear')?.addEventListener('click', () => { state.nodes = {}; state.relations = []; persist(); canvas.innerHTML = ''; drawLines(); window.showToast && window.showToast('Lienzo limpio', 'warning'); });
    $('#org-export-png')?.addEventListener('click', async () => {
      if (!window.html2canvas) { window.showToast && window.showToast('Exportador no disponible', 'danger'); return; }
      const el = document.getElementById('org-canvas-wrap');
      const cnv = await html2canvas(el, { backgroundColor: '#ffffff' });
      cnv.toBlob((blob) => { if (!blob) return; const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'organigrama.png'; a.click(); setTimeout(() => URL.revokeObjectURL(a.href), 2000); });
    });
    $('#org-export-svg')?.addEventListener('click', () => {
      const blob = new Blob([svg.outerHTML], { type: 'image/svg+xml' });
      const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'organigrama.svg'; a.click(); setTimeout(() => URL.revokeObjectURL(a.href), 2000);
    });
    $('#org-help')?.addEventListener('click', () => showOnboarding(true));
  }

  function showOnboarding(force) {
    try {
      if (!force && localStorage.getItem('org_onboarded') === '1') return;
      const m = new bootstrap.Modal(document.getElementById('org-onboarding'));
      document.getElementById('org-dontshow')?.addEventListener('change', (e) => {
        if (e.target.checked) {
          localStorage.setItem('org_onboarded', '1');
          console.info('[Organigrama] Modo aprendizaje desactivado permanentemente.');
        } else {
          localStorage.removeItem('org_onboarded');
        }
      });
      m.show();
    } catch (e) {
      console.warn('[Organigrama] Error showing onboarding modal:', e);
    }
  }

  function mount() {
    renderPeople('');
    restore();
    Object.keys(state.nodes).forEach(id => ensureNode(Number(id)));
    drawLines(); updateCanvasSize();
    // handleDnd(); // disabled in favor of organigrama-dragfix.js
    attachUI();
    // move onboarding modal to modals-root if present
    const bucket = document.getElementById('modals-root'); const modal = document.getElementById('org-onboarding');
    if (bucket && modal) bucket.appendChild(modal);

    // 🔇 Modal desactivado para carga directa - usar toast no intrusivo
    // showOnboarding(false);

    // Mostrar tip elegante no intrusivo después de cargar usuarios
    if (state.people.length > 0) {
      setTimeout(() => {
        window.showToast &&
          window.showToast('💡 Arrastra colaboradores al lienzo para construir tu organigrama', 'info', 4000);
      }, 500); // Pequeño delay para mejor UX
    }

    // Re-draw lines on resize
    window.addEventListener('resize', drawLines);
    window.addEventListener('resize', updateCanvasSize);
  }

  function updateCanvasSize() {
    // Expand canvas height/width based on node distribution and count
    const nodes = Array.from(canvas.querySelectorAll('.org-node'));
    const baseH = Math.max(700, nodes.length * 80);
    let maxRight = 0, maxBottom = 0;
    nodes.forEach(n => {
      const r = n.getBoundingClientRect();
      const cr = canvas.getBoundingClientRect();
      const right = (r.left - cr.left) + r.width;
      const bottom = (r.top - cr.top) + r.height;
      if (right > maxRight) maxRight = right;
      if (bottom > maxBottom) maxBottom = bottom;
    });
    canvas.style.minHeight = Math.max(baseH, maxBottom + 200) + 'px';
    const minW = Math.max(1200, maxRight + 200);
    if (canvas.clientWidth < minW) canvas.style.width = minW + 'px';
  }

  // Export PNG via html2canvas when attribute is present
  document.addEventListener('DOMContentLoaded', () => {
    const btn = document.querySelector('[data-export="png"]');
    if (btn && window.html2canvas) {
      btn.addEventListener('click', async () => {
        try {
          const el = document.getElementById('org-canvas-wrap') || document.getElementById('org-canvas');
          const cnv = await html2canvas(el, { backgroundColor: '#ffffff' });
          cnv.toBlob((blob) => { if (!blob) return; const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'organigrama.png'; a.click(); setTimeout(() => URL.revokeObjectURL(a.href), 2000); });
        } catch (e) { window.showToast && window.showToast('No se pudo exportar PNG', 'danger'); }
      });
    }
  });

  // init
  async function safeInitOrganigrama() {
    // Configurar para carga directa sin modal (se puede reactivar desde el botón de ayuda)
    if (!localStorage.getItem('org_onboarded')) {
      localStorage.setItem('org_onboarded', '1');
      console.info('[Organigrama] Configurado para carga directa. Ayuda disponible en el botón 🛈');
    }

    // Esperar hasta que #org-people-list tenga contenido visible
    let tries = 0;
    while (tries < 10 && (!document.querySelectorAll('.org-user[data-id]').length)) {
      await new Promise(r => setTimeout(r, 200)); // espera 200ms
      tries++;
    }

    await loadPeople();
    loadState(); // Cargar relaciones guardadas
    mount();
    
    // Dibujar líneas después de montar y cargar estado
    setTimeout(() => {
      drawLines();
    }, 100);

    if (!state.people.length) {
      console.warn('[Organigrama] No se pudieron cargar usuarios después de 10 intentos');

      // Fallback de emergencia: intentar leer desde elementos ya renderizados
      const serverRendered = document.querySelectorAll('.org-user[data-id]');
      if (serverRendered.length && !state.people.length) {
        state.people = Array.from(serverRendered).map(el => ({
          id: Number(el.dataset.id),
          name: el.dataset.name || el.getAttribute('aria-label') || 'Empleado desconocido',
          position: el.dataset.position || '',
          unit: el.dataset.unit || '',
          department: el.dataset.department || '',
          status: el.dataset.status || 'active'
        }));
        renderPeople('');
        console.info(`[Organigrama] Usuarios cargados desde fallback: ${state.people.length}`);
      } else {
        window.showToast && window.showToast('No se encontraron colaboradores para el organigrama', 'warning');
      }
    } else {
      console.info(`[Organigrama] Usuarios cargados: ${state.people.length}`);
    }

    // Añadir fallback de depuración visual
    if (countEl) {
      const total = state.people.length;
      countEl.textContent = `${total} colaboradores cargados`;
    }
  }

  document.addEventListener('DOMContentLoaded', safeInitOrganigrama);
})();
