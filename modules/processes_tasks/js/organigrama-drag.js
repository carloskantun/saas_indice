// /modules/processes_tasks/js/organigrama-drag.js
// Lightweight DnD helper to meet prompt acceptance criteria without conflicting with existing logic.
(function(){
  function getLSKey(){
    try {
      const cid = (window.IX_CTX && (window.IX_CTX.companyId||window.IX_CTX.companyID))
        || document.body?.dataset?.companyId || '0';
      return `indice_org_positions:${cid}`;
    } catch(_) { return 'indice_org_positions:0'; }
  }
  const LEGACY_KEY = 'indice_org_positions';

  function savePositions(){
    try {
      const canvas = document.getElementById('org-canvas'); if (!canvas) return;
      const nodes = Array.from(canvas.querySelectorAll('.org-node'));
      const map = {};
      nodes.forEach(n=>{
        const id = String(n.dataset.id||''); if (!id) return;
        const x = parseInt(n.style.left||'0',10)||0;
        const y = parseInt(n.style.top||'0',10)||0;
        map[id] = { x, y };
      });
      localStorage.setItem(getLSKey(), JSON.stringify(map));
    } catch(_){ }
  }

  function restorePositions(){
    try {
      const canvas = document.getElementById('org-canvas'); if (!canvas) return;
      const key = getLSKey();
      let saved = JSON.parse(localStorage.getItem(key) || '{}') || {};
      if (!Object.keys(saved).length){
        const legacy = JSON.parse(localStorage.getItem(LEGACY_KEY)||'{}')||{};
        if (Object.keys(legacy).length){ saved = legacy; localStorage.setItem(key, JSON.stringify(legacy)); }
      }
      Object.entries(saved).forEach(([id, pos])=>{
        const node = canvas.querySelector(`.org-node[data-id="${CSS.escape(String(id))}"]`);
        if (!node) return;
        node.style.left = `${pos.x||0}px`;
        node.style.top  = `${pos.y||0}px`;
        try { document.dispatchEvent(new CustomEvent('org:nodeMoved', { detail: { id: String(id) } })); } catch(_){ }
      });
    } catch(_){ }
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    const canvas = document.getElementById('org-canvas');
    const resetBtn = document.getElementById('reset-org');
    if (!canvas) return;

    // Visual feedback on canvas
    canvas.addEventListener('dragover', (e)=>{ 
      e.preventDefault(); 
      canvas.classList.add('drag-over'); 
      
      // Highlight level bands
      const levelBands = canvas.querySelectorAll('.org-level-band');
      levelBands.forEach(band => {
        const rect = band.getBoundingClientRect();
        const canvasRect = canvas.getBoundingClientRect();
        const y = e.clientY - canvasRect.top + canvas.scrollTop;
        const bandTop = parseInt(band.style.top || '0', 10);
        const bandHeight = parseInt(band.style.height || '160', 10);
        
        if (y >= bandTop && y <= (bandTop + bandHeight)) {
          band.classList.add('drag-over');
        } else {
          band.classList.remove('drag-over');
        }
      });
    });
    
    canvas.addEventListener('dragleave', (e)=>{ 
      // Only remove drag-over if we're really leaving the canvas
      if (!canvas.contains(e.relatedTarget)) {
        canvas.classList.remove('drag-over');
        const levelBands = canvas.querySelectorAll('.org-level-band');
        levelBands.forEach(band => band.classList.remove('drag-over'));
      }
    });
    
    canvas.addEventListener('drop', (e)=>{ 
      e.preventDefault(); 
      canvas.classList.remove('drag-over'); 
      const levelBands = canvas.querySelectorAll('.org-level-band');
      levelBands.forEach(band => band.classList.remove('drag-over'));
      setTimeout(savePositions, 60); 
    });

    // Save after programmatic/user moves
    document.addEventListener('org:nodeMoved', ()=> setTimeout(savePositions, 40));

    // Restore once nodes are present
    setTimeout(restorePositions, 450);

    // Reset: only clear positions (otros resets ya están conectados)
    resetBtn && resetBtn.addEventListener('click', ()=>{
      try { localStorage.removeItem(getLSKey()); } catch(_){ }
      try { localStorage.removeItem(LEGACY_KEY); } catch(_){ }
    });
  });
})();
