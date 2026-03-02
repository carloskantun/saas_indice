// /modules/processes_tasks/js/organigrama-levels.js
console.log('[Processes & Tasks] ORGANIGRAMA LEVELS JS loaded');

(function(){
  const LEVEL_HEIGHT = 160;
  const LS_COUNT = 'org_levels_count_v1';
  const LS_MAP = 'org_levels_map_v1'; // { nodeId: level }

  const canvas = document.getElementById('org-canvas');
  if (!canvas) return;

  const addBtn = document.getElementById('add-level');
  const removeBtn = document.getElementById('remove-level');

  let levels = Number(localStorage.getItem(LS_COUNT) || '4') || 4;
  let levelMap = {};
  try { levelMap = JSON.parse(localStorage.getItem(LS_MAP) || '{}') || {}; } catch(_) {}

  function bandsContainer(){
    let el = canvas.querySelector('.org-level-bands');
    if (!el) {
      el = document.createElement('div');
      el.className = 'org-level-bands';
      el.setAttribute('aria-hidden','true');
      canvas.insertBefore(el, canvas.firstChild);
    }
    return el;
  }

  function saveCount(){ localStorage.setItem(LS_COUNT, String(levels)); }
  function saveMap(){ localStorage.setItem(LS_MAP, JSON.stringify(levelMap)); }

  function renderLevels(){
    const cont = bandsContainer();
    cont.innerHTML = '';
  const totalHeight = Math.max(levels * LEVEL_HEIGHT, parseInt(window.getComputedStyle(canvas).minHeight)||0);
    canvas.style.minHeight = totalHeight + 'px';
    for (let i=1; i<=levels; i++){
      const band = document.createElement('div');
      band.className = 'org-level-band';
      band.dataset.level = String(i);
      band.setAttribute('data-bs-toggle','tooltip');
      band.style.top = ((i-1) * LEVEL_HEIGHT) + 'px';
      band.style.height = LEVEL_HEIGHT + 'px';
      band.innerHTML = `<span class="org-level-label">Nivel ${i}</span>`;
      cont.appendChild(band);
    }
    updateLevelTooltips();
  }

  function alignNodeToLevel(node){
    const id = node.dataset.id;
    let lvl = parseInt(node.dataset.level || levelMap[id] || '1', 10);
    if (!Number.isFinite(lvl) || lvl < 1) lvl = 1;
    if (lvl > levels) lvl = levels;
    node.dataset.level = String(lvl);
  const y = (lvl - 1) * LEVEL_HEIGHT + Math.round(LEVEL_HEIGHT * 0.4);
    node.style.top = y + 'px';
  }

  function alignNodesByLevel(){
    canvas.querySelectorAll('.org-node').forEach(alignNodeToLevel);
    updateLevelTooltips();
  }

  function onAddLevel(){ 
    levels++; 
    saveCount(); 
    renderLevels(); 
    alignNodesByLevel(); 
    document.dispatchEvent(new CustomEvent('org:levelsUpdated', { detail: { action: 'add', levels } }));
  }
  
  function onRemoveLevel(){ 
    if (levels>1){ 
      levels--; 
      saveCount(); 
      renderLevels(); 
      alignNodesByLevel(); 
      document.dispatchEvent(new CustomEvent('org:levelsUpdated', { detail: { action: 'remove', levels } }));
    } 
  }

  addBtn?.addEventListener('click', onAddLevel);
  removeBtn?.addEventListener('click', onRemoveLevel);

  // Recalculate level when a node finishes movement (from organigrama.js dispatch)
  document.addEventListener('org:nodeMoved', (e)=>{
    try {
      const id = String(e.detail?.id || '');
      const node = id ? canvas.querySelector(`.org-node[data-id="${CSS.escape(id)}"]`) : null;
      if (!node) return;
      const top = parseInt(node.style.top||'0', 10) || 0;
  let lvl = Math.ceil((top + (LEVEL_HEIGHT/3)) / LEVEL_HEIGHT);
      if (lvl < 1) lvl = 1; if (lvl > levels) lvl = levels;
      node.dataset.level = String(lvl);
      levelMap[id] = lvl; saveMap();
      // Snap into band center
  const y = (lvl - 1) * LEVEL_HEIGHT + Math.round(LEVEL_HEIGHT * 0.4);
      node.style.top = y + 'px';
    } catch(_){}
    updateLevelTooltips();
  });

  // Observe new nodes appended to canvas to align them
  const mo = new MutationObserver((mrs)=>{
    let needsAlign = false;
    for (const mr of mrs){
      mr.addedNodes && mr.addedNodes.forEach(n=>{ if (n.classList?.contains('org-node')) needsAlign = true; });
    }
    if (needsAlign) alignNodesByLevel();
    else updateLevelTooltips();
  });
  try { mo.observe(canvas, { childList: true }); } catch(_){}

  // Initial render and alignment
  renderLevels();
  alignNodesByLevel();
  updateLevelTooltips();

  // Re-render bands when canvas size could change
  window.addEventListener('resize', ()=>{ renderLevels(); });

  // --- Tooltip helpers ---
  function countActiveByLevel(lvl){
    const nodes = canvas.querySelectorAll(`.org-node[data-status="active"][data-level="${lvl}"]`);
    return nodes ? nodes.length : 0;
  }
  function updateLevelTooltips(){
    try {
      const bands = canvas.querySelectorAll('.org-level-band');
      bands.forEach(band => {
        const lvl = parseInt(band.dataset.level||'1',10)||1;
        const cnt = countActiveByLevel(lvl);
        const text = `Nivel ${lvl} — ${cnt} ${cnt===1?'usuario':'usuarios'}`;
        band.setAttribute('title', text);
        // Deshabilitar tooltips Bootstrap problemáticos
        // band.setAttribute('data-bs-title', text);
        // if (window.bootstrap && typeof window.bootstrap.Tooltip === 'function'){
        //   if (band._ixTooltip){ try { band._ixTooltip.dispose(); } catch(_){} }
        //   band._ixTooltip = new window.bootstrap.Tooltip(band, { container: 'body', placement: 'left', trigger: 'hover focus' });
        // }
      });
    } catch(_){ }
  }
})();
