// /modules/processes_tasks/js/organigrama-autosize.js
// Auto-ajusta la altura del lienzo según el contenido y colabora con el reinicio total.
(function(){
  function getLSKey(){
    try {
      const cid = (window.IX_CTX && (window.IX_CTX.companyId||window.IX_CTX.companyID))
        || document.body?.dataset?.companyId || '0';
      return `indice_org_positions:${cid}`;
    } catch(_) { return 'indice_org_positions:0'; }
  }
  const LEGACY_KEY = 'indice_org_positions';
  const BASE_MIN = 700; // consistente con organigrama.js

  function adjustCanvasHeight(){
    const canvas = document.getElementById('org-canvas'); if (!canvas) return;
    const nodes = canvas.querySelectorAll('.org-node');
    if (!nodes.length){ canvas.style.minHeight = BASE_MIN + 'px'; return; }
    let maxBottom = 0;
    nodes.forEach(node => {
      const top = parseFloat(node.style.top||'0')||0;
      const h = node.offsetHeight || 120;
      const bottom = top + h;
      if (bottom > maxBottom) maxBottom = bottom;
    });
    const newH = Math.max(BASE_MIN, Math.round(maxBottom + 200));
    if ((parseInt(canvas.style.minHeight||'0',10)||0) !== newH) canvas.style.minHeight = newH + 'px';
    // Pedir redibujo de líneas
    try { requestAnimationFrame(()=> window.dispatchEvent(new Event('resize'))); } catch(_){ }
  }

  // Guarda posiciones visuales en LS (clave por compañía) y en clave de compatibilidad
  function savePositionsToLS(){
    const canvas = document.getElementById('org-canvas'); if (!canvas) return;
    const map = {};
    canvas.querySelectorAll('.org-node').forEach(n=>{
      const id = String(n.dataset.id||''); if (!id) return;
      const x = parseInt(n.style.left||'0',10)||0;
      const y = parseInt(n.style.top||'0',10)||0;
      map[id] = { x, y };
    });
    try { localStorage.setItem(getLSKey(), JSON.stringify(map)); } catch(_){ }
    // Compatibilidad con otras piezas del módulo
    try { localStorage.setItem('org_nodes_v1', JSON.stringify(map)); } catch(_){ }
  }

  // Layout simple por nivel para posicionar nodos recién creados
  function layoutByLevel(){
    const canvas = document.getElementById('org-canvas'); if (!canvas) return;
    const nodes = Array.from(canvas.querySelectorAll('.org-node'));
    if (!nodes.length) return;
    const NODE_W = 240; const H_SPACING = 260; const LEVEL_H = 160;
    const width = canvas.clientWidth || 1200; const cx = width / 2;
    const byLevel = new Map();
    nodes.forEach(n=>{ const lvl = parseInt(n.dataset.level||'1',10)||1; if(!byLevel.has(lvl)) byLevel.set(lvl, []); byLevel.get(lvl).push(n); });
    const levels = Array.from(byLevel.keys()).sort((a,b)=>a-b);
    levels.forEach(level=>{
      const arr = byLevel.get(level)||[]; const total = arr.length; if (!total) return;
      const rowWidth = (total-1)*H_SPACING; const startX = cx - rowWidth/2;
      arr.forEach((n,i)=>{
        const x = Math.round((startX + i*H_SPACING) - NODE_W/2);
        const y = Math.round((level-1)*LEVEL_H + LEVEL_H*0.4);
        n.style.left = x + 'px'; n.style.top = y + 'px';
      });
    });
    savePositionsToLS();
    try { requestAnimationFrame(()=> window.dispatchEvent(new Event('resize'))); } catch(_){ }
  }

  // Drag ligero sobre el canvas para nodos activos
  function makeCanvasDraggable(el){
    const canvas = document.getElementById('org-canvas'); if (!canvas) return;
    if (el.dataset.dnd==='1') return; el.dataset.dnd='1';
    let sx=0, sy=0, ox=0, oy=0, dragging=false; const id = el.dataset.id;
    const canvasRect = ()=> canvas.getBoundingClientRect();
    function onDown(e){ if (e.ctrlKey || e.target.closest('.org-link-handle')) return; dragging=true; const pt = (e.touches? e.touches[0]: e); sx=pt.clientX; sy=pt.clientY; const r = el.getBoundingClientRect(); const cr = canvasRect(); ox = r.left - cr.left; oy = r.top - cr.top; document.addEventListener('mousemove', onMove); document.addEventListener('mouseup', onUp); e.preventDefault(); }
    function onMove(e){ if(!dragging) return; const pt = (e.touches? e.touches[0]: e); const cr = canvasRect(); let nx = ox + (pt.clientX - sx); let ny = oy + (pt.clientY - sy); nx = Math.max(0, Math.min(canvas.clientWidth - el.offsetWidth, nx)); ny = Math.max(0, Math.min(canvas.clientHeight - el.offsetHeight, ny)); el.style.left = nx + 'px'; el.style.top = ny + 'px'; window.requestAnimationFrame(()=> window.dispatchEvent(new Event('resize'))); }
    function onUp(){ dragging=false; document.removeEventListener('mousemove', onMove); document.removeEventListener('mouseup', onUp); savePositionsToLS(); try{ document.dispatchEvent(new CustomEvent('org:nodeMoved', { detail: { id:String(id) } })); }catch(_){ } }
    el.addEventListener('mousedown', onDown);
  }

  // Crea nodos desde JSON local (sin fetch) y los inyecta en #org-canvas
  function renderColaboradores(items){
    const data = Array.isArray(items) ? items : (Array.isArray(window.colaboradores)? window.colaboradores : []);
    const canvas = document.getElementById('org-canvas'); if (!canvas || !data.length) return;
    data.forEach(p=>{
      const id = String(p.id||''); if (!id) return;
      // Evitar duplicados si ya existe
      if (canvas.querySelector('.org-node[data-id="'+ (window.CSS && CSS.escape ? CSS.escape(id) : id) +'"]')) return;
      const el = document.createElement('div');
      el.className = 'org-node card card-min';
      el.dataset.id = id;
      const level = parseInt(p.nivel||'1',10)||1; el.dataset.level = String(level);
      const active = (p.activo === undefined) ? true : !!p.activo;
      el.dataset.status = active ? 'active' : 'inactive';
      el.draggable = !!active;
      if (!active) el.style.opacity = '0.5';
      el.innerHTML = '<div class="org-node-inner position-relative">\
        <button type="button" class="org-link-handle" title="Conectar (Ctrl+arrastrar)" aria-label="Conectar nodo"></button>\
        <div class="d-flex align-items-center gap-2">\
          <div class="avatar">'+ (String(p.nombre||'').charAt(0).toUpperCase()||'?') +'</div>\
          <div>\
            <div class="fw-semibold">'+ (p.nombre||('ID '+id)) +'</div>\
            <div class="small text-muted">'+ (p.puesto?('Puesto: '+p.puesto):'') + (p.unidad?(' · '+p.unidad):'') +'</div>\
          </div>\
        </div>\
      </div>';
      canvas.appendChild(el);
      if (active) makeCanvasDraggable(el);
    });
    // Posicionar por nivel y guardar
    layoutByLevel();
    try { window.showToast && window.showToast('Organigrama generado desde JSON', 'success'); } catch(_){ }
  }

  // Exponer API global si no existe otra implementación
  try { if (typeof window.renderColaboradores !== 'function') window.renderColaboradores = renderColaboradores; } catch(_){ }

  function clearPositions(){
    try { localStorage.removeItem(getLSKey()); } catch(_){ }
    try { localStorage.removeItem(LEGACY_KEY); } catch(_){ }
    try { localStorage.removeItem('org_nodes_v1'); } catch(_){ }
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    const canvas = document.getElementById('org-canvas');
    const resetBtn = document.getElementById('reset-org');
    if (!canvas) return;

    // Renderizado dinámico antes del primer ajuste si existe JSON embebido
    try { if (Array.isArray(window.colaboradores) && window.colaboradores.length) { renderColaboradores(window.colaboradores); } } catch(_){ }

    // Reaccionar a movimientos y cambios DOM
    document.addEventListener('org:nodeMoved', ()=> setTimeout(adjustCanvasHeight, 40));
    const mo = new MutationObserver(()=> setTimeout(adjustCanvasHeight, 40));
    try { mo.observe(canvas, { childList: true, subtree: false }); } catch(_){ }
    window.addEventListener('resize', ()=> setTimeout(adjustCanvasHeight, 50));

    // Ajuste inicial (por si ya hay posiciones restauradas)
    setTimeout(adjustCanvasHeight, 300);

    // Reinicio completo: limpiar posiciones y devolver altura base
    resetBtn && resetBtn.addEventListener('click', ()=>{
      // No duplicamos diálogos; solo complementamos el reset existente
      clearPositions();
      setTimeout(()=>{ const c = document.getElementById('org-canvas'); if (c) c.style.minHeight = BASE_MIN + 'px'; }, 80);
    });
  });
})();
