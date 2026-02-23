// /modules/processes_tasks/js/organigrama-final.js
// Final visual/logic polishing for Organigrama: margins, active-only placement, reset, and balanced grid per level.

(function(){
  const LEVEL_H = 160;
  const PAD_X = 60; // visual padding matches CSS
  const PAD_Y = 40;

  document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('org-canvas');
    if (!canvas) return;

    const addBtn = document.getElementById('add-level');
    const removeBtn = document.getElementById('remove-level');
    const resetBtn = document.getElementById('reset-org');

    // Helper to list nodes by status
    function nodesByStatus(status){
      return Array.from(canvas.querySelectorAll('.org-node')).filter(n => (n.dataset.status || 'active') === status);
    }

  // Balanced horizontal grid per level for ACTIVE nodes only (manual call only)
    function distributeUsersByLevel(){
      const actives = nodesByStatus('active');
      const grouped = new Map();
      actives.forEach(n => {
        const lvl = Math.max(1, parseInt(n.dataset.level || '1', 10));
        if (!grouped.has(lvl)) grouped.set(lvl, []);
        grouped.get(lvl).push(n);
      });
      const width = canvas.clientWidth; // includes padding
      grouped.forEach((nodes, lvl) => {
        const y = (lvl - 1) * LEVEL_H + Math.round(LEVEL_H * 0.4);
        const total = nodes.length;
        if (!total) return;
        // sort by current left to keep somewhat stable order
        nodes.sort((a,b)=> (parseInt(a.style.left||'0')||0) - (parseInt(b.style.left||'0')||0));
        const gap = width / (total + 1);
        nodes.forEach((node, i) => {
          const nodeHalf = (node.offsetWidth||240)/2;
          const x = Math.max(PAD_X, Math.min(width - PAD_X, Math.round(gap * (i+1))));
          node.style.top = y + 'px';
          node.style.left = (x - nodeHalf) + 'px';
        });
      });
      // Ask organigrama.js to recompute lines
      try { requestAnimationFrame(()=> window.dispatchEvent(new Event('resize'))); } catch(_){ }
    }

    // Redistribute a single level only
    function redistributeLevel(level){
      level = Math.max(1, parseInt(level||'1',10));
      const nodes = nodesByStatus('active').filter(n => parseInt(n.dataset.level||'1',10) === level);
      if (!nodes.length) { try { requestAnimationFrame(()=> window.dispatchEvent(new Event('resize'))); } catch(_){ } return; }
      const width = canvas.clientWidth;
      const y = (level - 1) * LEVEL_H + Math.round(LEVEL_H * 0.4);
      // keep order stable by current left
      nodes.sort((a,b)=> (parseInt(a.style.left||'0')||0) - (parseInt(b.style.left||'0')||0));
      const gap = width / (nodes.length + 1);
      nodes.forEach((node, i) => {
        const nodeHalf = (node.offsetWidth||240)/2;
        const x = Math.max(PAD_X, Math.min(width - PAD_X, Math.round(gap * (i+1))));
        node.style.top = y + 'px';
        node.style.left = (x - nodeHalf) + 'px';
      });
      try { requestAnimationFrame(()=> window.dispatchEvent(new Event('resize'))); } catch(_){ }
    }

    // Enforce active-only placement when a node move finishes
    function onNodeMoved(e){
      const id = e?.detail?.id ? String(e.detail.id) : '';
      if (!id) return;
      const node = canvas.querySelector(`.org-node[data-id="${CSS.escape(id)}"]`);
      if (!node) return;
      const status = node.dataset.status || 'active';
      if (status !== 'active') {
        window.showToast && window.showToast('Solo se pueden colocar usuarios activos', 'warning');
      }
      // Do NOT auto-redistribute here to allow manual placement
    }

    // Reset organigrama visuals
    function resetOrganigrama(){
      // Clear per-node level map from levels.js; keep current levels count
      try { localStorage.removeItem('org_levels_map_v1'); } catch(_){ }
      // Trigger a mild reflow
      distributeUsersByLevel();
      window.showToast && window.showToast('Organigrama reiniciado', 'info');
    }

    // Re-distribute when levels are changed (buttons click)
    // On level changes, redistribute ONLY the highest band after levels.js updates
    function getLevelsCount(){
      try { return parseInt(localStorage.getItem('org_levels_count_v1')||'4',10)||4; } catch(_){ return 4; }
    }
    addBtn?.addEventListener('click', ()=> {
      setTimeout(()=>{ const max = getLevelsCount(); redistributeLevel(max); }, 100);
    });
    removeBtn?.addEventListener('click', ()=> {
      setTimeout(()=>{ const max = getLevelsCount(); redistributeLevel(max); }, 120);
    });
  resetBtn?.addEventListener('click', resetOrganigrama);

    // React on node movement (emitted by organigrama.js)
    document.addEventListener('org:nodeMoved', onNodeMoved);

    // Don't auto-distribute on load/resize so users can freely arrange
    // Lines redraw are already handled by organigrama.js on window resize
  });
})();
