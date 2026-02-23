<?php
// Vista: Organigrama con vis.js
?>
<style>
#vis-organigrama {
    width: 100%;
    height: 70vh;
    border: 2px solid var(--bs-border-color);
    border-radius: 8px;
    background: var(--bs-light);
}

#org-status-bar {
    background: var(--bs-primary-bg-subtle);
    border: 1px solid var(--bs-primary-border-subtle);
    border-radius: 6px;
    padding: 8px 12px;
    margin-top: 10px;
    font-size: 0.875rem;
    color: var(--bs-primary-text-emphasis);
}

.org-vis-controls {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
    margin-bottom: 15px;
}

.org-people-sidebar {
    background: var(--bs-light);
    border: 1px solid var(--bs-border-color);
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    max-height: 200px;
    overflow-y: auto;
}

.person-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    border-radius: 6px;
    margin-bottom: 5px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.person-item:hover {
    background: var(--bs-primary-bg-subtle);
}

.person-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--bs-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.875rem;
}
</style>

<div class="card card-min p-3 reveal">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="h5 m-0">🧠 Organigrama (vis.js)</h2>
    <div class="d-flex gap-2 flex-wrap">
      <button class="btn btn-outline-secondary btn-sm" id="org-help-vis" aria-label="Ayuda de organigrama">
        <i class="bi bi-question-circle"></i> Ayuda
      </button>
      <button class="btn btn-outline-success btn-sm" id="org-export-png-vis" aria-label="Exportar PNG">
        <i class="bi bi-image"></i> PNG
      </button>
      <button class="btn btn-outline-primary btn-sm" onclick="window.location.reload()" aria-label="Volver a versión original">
        <i class="bi bi-arrow-left"></i> Versión Original
      </button>
    </div>
  </div>

  <!-- Panel de personas disponibles -->
  <div class="org-people-sidebar">
    <h6 class="mb-2">
      <i class="bi bi-people"></i> Colaboradores disponibles
      <small class="text-muted">(hacer doble clic para info)</small>
    </h6>
    <div id="people-list-vis">
      <!-- Se llenará dinámicamente -->
    </div>
  </div>

  <!-- Controles -->
  <div class="org-vis-controls org-toolbar">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <i class="bi bi-info-circle"></i>
      <span class="small">Haz clic en un nodo, luego en otro para conectar. Doble clic para información.</span>
    </div>
  </div>

  <!-- Contenedor del organigrama -->
  <div id="vis-organigrama"></div>
  
  <!-- Barra de estado -->
  <div id="org-status-bar">
    Haz clic en un nodo para seleccionar
  </div>
</div>

<!-- Cargar vis.js desde CDN -->
<script type="text/javascript" src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>

<!-- Modal de ayuda -->
<div class="modal fade" id="org-help-modal-vis" tabindex="-1" aria-labelledby="orgHelpLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom">
        <h5 class="modal-title text-primary fw-bold" id="orgHelpLabel">
          <i class="bi bi-question-circle me-2"></i>Ayuda del Organigrama
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-4">
        <h6 class="fw-semibold mb-2">🔗 Crear conexiones:</h6>
        <ol class="mb-3">
          <li>Haz clic en el nodo padre (se resaltará en naranja)</li>
          <li>Haz clic en el nodo hijo (se creará la conexión)</li>
          <li>Para cancelar, haz clic en el mismo nodo seleccionado</li>
        </ol>
        
        <h6 class="fw-semibold mb-2">ℹ️ Ver información:</h6>
        <ul class="mb-3">
          <li>Doble clic en cualquier nodo para ver detalles</li>
          <li>Hover sobre los nodos para ver información básica</li>
        </ul>
        
        <h6 class="fw-semibold mb-2">🎛️ Controles:</h6>
        <ul class="mb-3">
          <li><strong>Auto layout:</strong> Reorganiza automáticamente</li>
          <li><strong>Limpiar conexiones:</strong> Elimina todas las conexiones</li>
          <li><strong>Arrastrar:</strong> Mueve los nodos libremente</li>
        </ul>

        <h6 class="fw-semibold mb-2">💾 Guardado:</h6>
        <p class="text-muted mb-0">
          Las conexiones se guardan automáticamente en tu navegador
        </p>
      </div>
      <div class="modal-footer border-top bg-light">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
      </div>
    </div>
  </div>
</div>

<script>
  // Configurar contexto
  window.IX_CTX = window.IX_CTX || {};
  window.IX_CTX.companyId = <?php echo isset($ucId) ? (int)$ucId : 0; ?>;
  window.IX_CTX.module = 'processes_tasks';
  window.IX_CTX.tab = 'organigrama-vis';

  // Cargar script del organigrama vis.js
  document.addEventListener('DOMContentLoaded', () => {
    // Eventos de botones
    document.getElementById('org-help-vis')?.addEventListener('click', () => {
      const modal = new bootstrap.Modal(document.getElementById('org-help-modal-vis'));
      modal.show();
    });

    document.getElementById('org-export-png-vis')?.addEventListener('click', () => {
      if (window.visOrganigrama && window.html2canvas) {
        const container = document.getElementById('vis-organigrama');
        html2canvas(container, { backgroundColor: '#ffffff' }).then(canvas => {
          canvas.toBlob(blob => {
            if (!blob) return;
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'organigrama-vis.png';
            a.click();
            setTimeout(() => URL.revokeObjectURL(a.href), 2000);
          });
        }).catch(e => {
          window.showToast && window.showToast('No se pudo exportar PNG', 'danger');
        });
      }
    });

    // Llenar lista de personas
    function fillPeopleList() {
      const peopleList = document.getElementById('people-list-vis');
      if (!peopleList) return;

      // Obtener personas del DOM existente
      const orgUsers = document.querySelectorAll('.org-user[data-id]');
      if (orgUsers.length === 0) {
        peopleList.innerHTML = '<div class="text-muted small">No hay colaboradores disponibles</div>';
        return;
      }

      peopleList.innerHTML = '';
      orgUsers.forEach(userEl => {
        const personDiv = document.createElement('div');
        personDiv.className = 'person-item';
        
        const name = userEl.dataset.name || userEl.getAttribute('aria-label') || 'Usuario';
        const position = userEl.dataset.position || '';
        const unit = userEl.dataset.unit || '';
        
        personDiv.innerHTML = `
          <div class="person-avatar">${name.charAt(0).toUpperCase()}</div>
          <div class="flex-grow-1">
            <div class="fw-semibold small">${name}</div>
            <div class="text-muted" style="font-size: 0.75rem;">${position}</div>
          </div>
        `;
        
        peopleList.appendChild(personDiv);
      });
    }

    fillPeopleList();
  });
</script>

<!-- Cargar el script principal después de que vis.js esté disponible -->
<script src="/modules/processes_tasks/js/organigrama-vis.js?v=dev"></script>