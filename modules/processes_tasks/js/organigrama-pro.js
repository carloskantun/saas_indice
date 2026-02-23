// /modules/processes_tasks/js/organigrama-pro.js
// Professional layer: Reset (clear all visual data) and Auto Layout (based on relations).
(function(){
  const LSK = { nodes: 'org_nodes_v1', rels: 'org_rels_v1' };
  const LS_LEVELS = { count: 'org_levels_count_v1', map: 'org_levels_map_v1' };
  const LEVEL_H = 160; // keep consistent with organigrama-levels

  function readJSON(key, fallback){ try{ return JSON.parse(localStorage.getItem(key)||''); }catch(_){ return fallback; } }

  function getActiveNodes(){
    const canvas = document.getElementById('org-canvas');
    if (!canvas) return [];
    return Array.from(canvas.querySelectorAll('.org-node[data-status="active"]'));
  }

  function toNodesMap(nodes){
    const map = {};
    nodes.forEach(n=>{
      const left = parseInt(n.style.left||'0',10)||0;
      const top = parseInt(n.style.top||'0',10)||0;
      map[n.dataset.id] = { x: left, y: top };
    });
    return map;
  }

  function setNodesMap(map){
    try { localStorage.setItem(LSK.nodes, JSON.stringify(map||{})); } catch(_){ }
  }

  function clearLines(){
    const svg = document.getElementById('org-lines');
    if (svg) svg.innerHTML = '';
  }

  function rebalanceCanvasHeight(){
    const canvas = document.getElementById('org-canvas');
    if (!canvas) return;
    // Compute max level in DOM
    let maxLevel = 1;
    canvas.querySelectorAll('.org-node').forEach(n=>{
      const lvl = parseInt(n.dataset.level||'1',10)||1; if (lvl>maxLevel) maxLevel=lvl;
    });
    const minH = Math.max(700, maxLevel * LEVEL_H + 200);
    canvas.style.minHeight = minH + 'px';
    requestAnimationFrame(()=> window.dispatchEvent(new Event('resize')));
  }

  function autoLayout(){
    const canvas = document.getElementById('org-canvas');
    if (!canvas) return;
    const nodes = getActiveNodes();
    const width = canvas.clientWidth || 1200;

    const rels = readJSON(LSK.rels, []) || [];
    const childrenByParent = new Map();
    const parentOf = new Map();
    rels.forEach(r=>{
      if (!childrenByParent.has(String(r.parent_id))) childrenByParent.set(String(r.parent_id), []);
      childrenByParent.get(String(r.parent_id)).push(String(r.child_id));
      parentOf.set(String(r.child_id), String(r.parent_id));
    });

    const idsInDOM = nodes.map(n=>String(n.dataset.id));
    const childIds = new Set(rels.map(r=> String(r.child_id)));
    const rootIds = idsInDOM.filter(id => !childIds.has(id));
    if (rootIds.length===0) {
      window.showToast && window.showToast('No hay jerarquías para autoajustar.', 'warning');
      return;
    }

    // DFS place horizontally
    const H_SPACING = 260; const NODE_W = 240;
    const positions = {}; let maxLevel = 1;
    function place(id, level, x){
      positions[id] = { level, x };
      if (level>maxLevel) maxLevel=level;
      const kids = childrenByParent.get(String(id))||[];
      if (!kids.length) return;
      const start = x - ((kids.length - 1) * H_SPACING)/2;
      kids.forEach((kid, i)=> place(kid, level+1, start + i*H_SPACING));
    }

    const centerX = width/2;
    rootIds.forEach((rid, idx)=> place(rid, 1, centerX + idx*H_SPACING));

    // Apply positions to DOM and persist
    const nodesMap = {};
    nodes.forEach(n=>{
      const id = String(n.dataset.id);
      const pos = positions[id] || { level: 1, x: centerX };
      const left = Math.round(pos.x - NODE_W/2);
      const top = (pos.level - 1) * LEVEL_H + Math.round(LEVEL_H * 0.4);
      n.style.left = left + 'px';
      n.style.top = top + 'px';
      n.dataset.level = String(pos.level);
      nodesMap[id] = { x: left, y: top };
    });
    setNodesMap(nodesMap);

    // Update level map loosely
    const m = {}; nodes.forEach(n=> m[String(n.dataset.id)] = parseInt(n.dataset.level||'1',10)||1);
    try { localStorage.setItem(LS_LEVELS.map, JSON.stringify(m)); } catch(_){ }

    // Adjust canvas height and redraw lines
    const canvasMin = Math.max(700, maxLevel * LEVEL_H + 200);
    canvas.style.minHeight = canvasMin + 'px';
    requestAnimationFrame(()=> window.dispatchEvent(new Event('resize')));
    window.showToast && window.showToast('Organigrama autoajustado correctamente', 'success');
  }

  function resetAll(){
    const btnClear = document.getElementById('org-clear');
    if (btnClear) btnClear.click(); // uses internal logic to clear state, DOM and persist
    try { localStorage.removeItem(LS_LEVELS.map); } catch(_){ }
    // Optionally also clear stored nodes/rels to be explicit
    try { localStorage.removeItem(LSK.nodes); } catch(_){ }
    try { localStorage.removeItem(LSK.rels); } catch(_){ }
    clearLines();
    rebalanceCanvasHeight();
    window.showToast && window.showToast('Organigrama reiniciado por completo', 'info');
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    const resetBtn = document.getElementById('reset-org');
    const autoBtn = document.getElementById('auto-layout');
    resetBtn && resetBtn.addEventListener('click', (e)=>{ e.preventDefault(); if (confirm('¿Deseas reiniciar todo el organigrama? Se eliminarán posiciones, niveles y relaciones.')) resetAll(); });
    autoBtn && autoBtn.addEventListener('click', (e)=>{ e.preventDefault(); autoLayout(); });
  });
})();
