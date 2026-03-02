// /modules/processes_tasks/js/kanban.js
console.log('[Processes & Tasks] KANBAN JS loaded');

(function(){
  const STATUSES = ['Pendiente','En curso','Pausada','Completada','Auditada'];
  const COLORS = { 'Pendiente':'secondary','En curso':'warning','Pausada':'dark','Completada':'success','Auditada':'info' };
  let tasks = [];

  const board = document.getElementById('kanban-board');
  if (!board) return;

  function esc(s){ return String(s||'').replace(/[&<>"]/g, c=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;"}[c])); }

  async function load(){
    try {
      const res = await fetch('/modules/processes_tasks/api/list.php', { credentials: 'same-origin' });
      const j = await res.json();
      if (j && j.ok && Array.isArray(j.items)) {
        tasks = j.items.map(t => ({
          id: Number(t.id),
          title: t.title || t.name || '',
          status: t.status || 'Pendiente',
          assignee: t.assignee_name || t.assignee || '',
          project: t.project_name || '',
        }));
      }
    } catch(e) { console.warn('kanban load', e); }
  }

  function columnTemplate(status, items){
    return `<div class="kanban-col" data-status="${esc(status)}">
      <div class="kanban-col-head d-flex align-items-center justify-content-between">
        <div class="fw-semibold"><span class="badge bg-${COLORS[status]||'secondary'}">${esc(status)}</span></div>
        <div class="small text-muted">${items.length}</div>
      </div>
      <div class="kanban-col-body" role="list" aria-label="${esc(status)}" data-dropzone></div>
    </div>`;
  }

  function render(){
    board.innerHTML = STATUSES.map(s => columnTemplate(s, tasks.filter(t=>t.status===s))).join('');
    // inject cards
    tasks.forEach(t => {
      const col = board.querySelector(`.kanban-col[data-status="${CSS.escape(t.status)}"] [data-dropzone]`);
      if (!col) return;
      const card = document.createElement('div');
      card.className = 'kanban-card card card-min';
      card.setAttribute('role','listitem');
      card.draggable = true;
      card.dataset.id = String(t.id);
      card.innerHTML = `<div class="p-2">
        <div class="fw-semibold text-truncate" title="${esc(t.title)}">${esc(t.title)}</div>
        <div class="small text-muted d-flex justify-content-between"><span>${esc(t.assignee||'')}</span><span>${esc(t.project||'')}</span></div>
      </div>`;
      col.appendChild(card);
    });
    enableDnd();
  }

  function enableDnd(){
    board.querySelectorAll('.kanban-card').forEach(card => {
      card.addEventListener('dragstart', (e)=>{ e.dataTransfer.setData('text/plain', card.dataset.id); });
    });
    board.querySelectorAll('[data-dropzone]').forEach(zone => {
      zone.addEventListener('dragover', e=>{ e.preventDefault(); zone.classList.add('over'); });
      zone.addEventListener('dragleave', ()=> zone.classList.remove('over'));
      zone.addEventListener('drop', async (e)=>{
        e.preventDefault(); zone.classList.remove('over');
        const id = Number(e.dataTransfer.getData('text/plain'));
        const card = board.querySelector(`.kanban-card[data-id="${CSS.escape(String(id))}"]`);
        if (!card) return;
        zone.appendChild(card);
        const status = zone.closest('.kanban-col')?.dataset.status || 'Pendiente';
        const t = tasks.find(x=>x.id===id); if (t) t.status = status;
        // Persist via API (front-only; server optional)
        try {
          const fd = new FormData(); fd.append('task_id', String(id)); fd.append('field','status'); fd.append('value', status); fd.append('csrf', window.CSRF||'');
          const res = await fetch('/modules/processes_tasks/api/update_field.php', { method: 'POST', body: fd, credentials: 'same-origin' });
          const j = await res.json().catch(()=>({ok:false}));
          if (!j.ok) window.showToast && window.showToast('No se pudo guardar el estado', 'warning');
          else window.showToast && window.showToast('Estado actualizado', 'success');
        } catch(e){ window.showToast && window.showToast('Error de red', 'danger'); }
      });
    });
  }

  async function init(){
    await load();
    render();
    document.getElementById('kanban-refresh')?.addEventListener('click', async ()=>{ await load(); render(); });
  }

  document.addEventListener('DOMContentLoaded', init);
})();
