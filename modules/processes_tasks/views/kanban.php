<?php
// Vista: Kanban (front-only)
?>
<div class="card card-min p-3 reveal">
  <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
    <h2 class="h6 m-0">🧩 Kanban</h2>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm" id="kanban-refresh" aria-label="Refrescar"><i class="bi bi-arrow-clockwise"></i> Refrescar</button>
    </div>
  </div>
  <div id="kanban-board" class="kanban-board" aria-label="Tablero Kanban">
    <!-- Columns injected by JS -->
  </div>
</div>
<script src="/modules/processes_tasks/js/kanban.js?v=dev"></script>
