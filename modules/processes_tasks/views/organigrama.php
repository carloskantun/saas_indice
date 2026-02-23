<!-- Header de sección (igual RH) -->
<div class="hr-header">
  <div class="hr-header-left">
    <h1><i class="bi bi-diagram-3 me-2 text-warning" aria-hidden="true"></i>Organigrama Corporativo</h1>
    <p class="subtitle">Estructura jerárquica interactiva de puestos y colaboradores.</p>
  </div>
</div>

<!-- Toolbar Superior -->
<div class="card-min p-3 mb-4 reveal">
  <div class="row g-3 align-items-center">
    <!-- Búsqueda -->
    <div class="col-md-3">
      <div class="input-group" style="border-radius: 12px;">
        <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px; border-color: #e2e8f0;">
          <i class="bi bi-search" style="color: #64748b;"></i>
        </span>
        <input 
          type="text" 
          class="form-control border-start-0" 
          id="org-search" 
          placeholder="Buscar puesto..."
          style="border-radius: 0 12px 12px 0; border-color: #e2e8f0; padding: 0.625rem 1rem;"
        >
      </div>
    </div>

    <!-- Filtros -->
    <div class="col-md-2">
      <select 
        class="form-select" 
        id="org-filter-unit"
        style="border-radius: 12px; padding: 0.625rem 1rem; border: 1px solid #e2e8f0;"
      >
        <option value="">Todas las unidades</option>
        <option value="Corporativo">Corporativo</option>
        <option value="Norte">Norte</option>
        <option value="Sur">Sur</option>
        <option value="Centro">Centro</option>
      </select>
    </div>

    <div class="col-md-2">
      <select 
        class="form-select" 
        id="org-filter-area"
        style="border-radius: 12px; padding: 0.625rem 1rem; border: 1px solid #e2e8f0;"
      >
        <option value="">Todas las áreas</option>
        <option value="Dirección">Dirección</option>
        <option value="Operaciones">Operaciones</option>
        <option value="Administración">Administración</option>
        <option value="Recursos Humanos">Recursos Humanos</option>
        <option value="Tecnología">Tecnología</option>
      </select>
    </div>

    <!-- Acciones -->
    <div class="col-md-5">
      <div class="d-flex gap-2 justify-content-end">
        <button 
          class="btn btn-ghost" 
          id="org-btn-add"
          style="border-radius: 12px; padding: 0.625rem 1rem;"
          title="Agregar puesto"
        >
          <i class="bi bi-plus-circle me-1"></i>Agregar Puesto
        </button>
        <button 
          class="btn btn-ghost" 
          id="org-btn-center"
          style="border-radius: 12px; padding: 0.625rem 0.875rem;"
          title="Centrar vista"
        >
          <i class="bi bi-crosshair"></i>
        </button>
        <button 
          class="btn btn-ghost" 
          id="org-btn-zoom-in"
          style="border-radius: 12px; padding: 0.625rem 0.875rem;"
          title="Acercar"
        >
          <i class="bi bi-zoom-in"></i>
        </button>
        <button 
          class="btn btn-ghost" 
          id="org-btn-zoom-out"
          style="border-radius: 12px; padding: 0.625rem 0.875rem;"
          title="Alejar"
        >
          <i class="bi bi-zoom-out"></i>
        </button>
        <button 
          class="btn btn-ghost" 
          id="org-btn-expand"
          style="border-radius: 12px; padding: 0.625rem 1rem;"
          title="Expandir todo"
        >
          <i class="bi bi-arrows-angle-expand me-1"></i>Expandir
        </button>
        <button 
          class="btn btn-brand" 
          id="org-btn-export"
          style="border-radius: 12px; padding: 0.625rem 1rem;"
        >
          <i class="bi bi-download me-1"></i>Exportar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Container Principal -->
<div class="row g-4">
  <!-- Área del Organigrama (Canvas) -->
  <div class="col-lg-9">
    <!-- Empty State -->
    <div id="org-empty-state" class="card-min text-center py-5" style="display: none;">
      <div class="mb-4">
        <i class="bi bi-diagram-3" style="font-size: 5rem; color: #cbd5e1;"></i>
      </div>
      <h4 class="fw-bold mb-3" style="color: #0f172a;">Aún no has creado tu organigrama</h4>
      <p class="text-muted mb-4" style="max-width: 500px; margin: 0 auto;">
        Comienza definiendo los puestos clave de tu organización. Podrás arrastrar, conectar y organizar la estructura jerárquica de forma visual.
      </p>
      <button class="btn btn-brand" id="org-btn-create-first" style="border-radius: 12px; padding: 0.75rem 2rem;">
        <i class="bi bi-plus-circle me-2"></i>Crear Primer Puesto
      </button>
    </div>

    <!-- Canvas del Organigrama -->
    <div id="org-canvas-container" class="card-min p-4 position-relative reveal" style="min-height: 600px; overflow: hidden;">
      <!-- SVG para conexiones -->
      <svg 
        id="org-connections-svg" 
        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 1;"
      ></svg>

      <!-- Área de nodos (draggable) -->
      <div id="org-canvas" class="position-relative" style="z-index: 2; transform-origin: center; transition: transform 0.3s ease;">
        <!-- Los nodos se insertan aquí dinámicamente -->
      </div>

      <!-- Loading overlay -->
      <div id="org-loading" class="position-absolute top-50 start-50 translate-middle" style="display: none;">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Cargando...</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Panel Lateral (Side Panel) -->
  <div class="col-lg-3">
    <div class="card-min reveal" style="position: sticky; top: 20px;">
      <!-- Panel cerrado: Resumen -->
      <div id="org-panel-closed">
        <div class="text-center py-4">
          <i class="bi bi-hand-index" style="font-size: 3rem; color: #cbd5e1;"></i>
          <p class="text-muted mt-3 mb-0" style="font-size: 0.875rem;">
            Selecciona un puesto para ver detalles
          </p>
        </div>
        
        <!-- Estadísticas rápidas -->
        <div class="border-top pt-3 mt-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted" style="font-size: 0.875rem;">
              <i class="bi bi-briefcase me-1" style="color: #3b82f6;"></i>Total Puestos
            </span>
            <span class="fw-bold" id="org-stat-total">0</span>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted" style="font-size: 0.875rem;">
              <i class="bi bi-people me-1" style="color: #10b981;"></i>Asignados
            </span>
            <span class="fw-bold text-success" id="org-stat-assigned">0</span>
          </div>
          <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted" style="font-size: 0.875rem;">
              <i class="bi bi-person-x me-1" style="color: #f59e0b;"></i>Vacantes
            </span>
            <span class="fw-bold text-warning" id="org-stat-vacant">0</span>
          </div>
        </div>
      </div>

      <!-- Panel abierto: Detalle del puesto -->
      <div id="org-panel-open" style="display: none;">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <h6 class="fw-bold mb-0" style="color: #0f172a;">Detalle del Puesto</h6>
          <button class="btn btn-ghost btn-sm p-1" id="org-panel-close" style="border-radius: 8px;">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <!-- Foto y nombre -->
        <div class="text-center mb-3 pb-3 border-bottom">
          <div class="mb-3">
            <img 
              id="org-detail-photo" 
              src="/assets/img/avatar-placeholder.png" 
              alt="Foto" 
              class="rounded-circle" 
              style="width: 80px; height: 80px; object-fit: cover; border: 3px solid #e2e8f0;"
            >
          </div>
          <h6 class="fw-bold mb-1" id="org-detail-name" style="color: #0f172a;">Nombre del Colaborador</h6>
          <p class="text-muted mb-0" style="font-size: 0.875rem;" id="org-detail-position">Puesto</p>
          <span class="badge mt-2" id="org-detail-level-badge" style="border-radius: 8px; padding: 0.375rem 0.75rem;">
            Nivel
          </span>
        </div>

        <!-- Información -->
        <div class="mb-3">
          <label class="text-muted mb-1" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">
            <i class="bi bi-building me-1"></i>Área
          </label>
          <p class="mb-2" id="org-detail-area">--</p>
        </div>

        <div class="mb-3">
          <label class="text-muted mb-1" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">
            <i class="bi bi-geo-alt me-1"></i>Unidad
          </label>
          <p class="mb-2" id="org-detail-unit">--</p>
        </div>

        <div class="mb-3">
          <label class="text-muted mb-1" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">
            <i class="bi bi-file-text me-1"></i>Descripción
          </label>
          <p class="mb-2 text-muted" style="font-size: 0.875rem;" id="org-detail-description">
            Sin descripción disponible
          </p>
        </div>

        <div class="mb-4">
          <label class="text-muted mb-1" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">
            <i class="bi bi-list-check me-1"></i>Responsabilidades
          </label>
          <ul class="mb-0 ps-3" id="org-detail-responsibilities" style="font-size: 0.875rem;">
            <li class="text-muted">Sin responsabilidades definidas</li>
          </ul>
        </div>

        <!-- Acciones -->
        <div class="d-grid gap-2">
          <button class="btn btn-brand btn-sm" id="org-btn-edit-position" style="border-radius: 10px;">
            <i class="bi bi-pencil me-1"></i>Editar Puesto
          </button>
          <button class="btn btn-outline-danger btn-sm" id="org-btn-delete-position" style="border-radius: 10px;">
            <i class="bi bi-trash me-1"></i>Eliminar
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Crear/Editar Puesto -->
<div class="modal fade" id="org-modal-position" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" style="color: #0f172a;">
          <i class="bi bi-briefcase me-2" style="color: #3b82f6;"></i>
          <span id="org-modal-title">Agregar Puesto</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      
      <div class="modal-body p-4">
        <form id="org-form-position">
          <input type="hidden" id="org-position-id">
          
          <div class="row g-3">
            <!-- Nombre del Puesto -->
            <div class="col-md-6">
              <label class="form-label fw-semibold mb-2" style="font-size: 0.875rem; color: #0f172a;">
                <i class="bi bi-briefcase me-1" style="color: #3b82f6;"></i>Nombre del Puesto *
              </label>
              <input 
                type="text" 
                class="form-control" 
                id="org-position-name" 
                placeholder="Ej: Director General"
                required
                style="border-radius: 12px; padding: 0.625rem 1rem; border: 1px solid #e2e8f0;"
              >
            </div>

            <!-- Colaborador Asignado -->
            <div class="col-md-6">
              <label class="form-label fw-semibold mb-2" style="font-size: 0.875rem; color: #0f172a;">
                <i class="bi bi-person me-1" style="color: #3b82f6;"></i>Colaborador Asignado
              </label>
              <input 
                type="text" 
                class="form-control" 
                id="org-position-person" 
                placeholder="Opcional"
                style="border-radius: 12px; padding: 0.625rem 1rem; border: 1px solid #e2e8f0;"
              >
            </div>

            <!-- Nivel Jerárquico -->
            <div class="col-md-6">
              <label class="form-label fw-semibold mb-2" style="font-size: 0.875rem; color: #0f172a;">
                <i class="bi bi-bar-chart-steps me-1" style="color: #3b82f6;"></i>Nivel Jerárquico *
              </label>
              <select 
                class="form-select" 
                id="org-position-level" 
                required
                style="border-radius: 12px; padding: 0.625rem 1rem; border: 1px solid #e2e8f0;"
              >
                <option value="">Seleccionar...</option>
                <option value="1">Nivel 1 - Dirección General</option>
                <option value="2">Nivel 2 - Dirección</option>
                <option value="3">Nivel 3 - Gerencia</option>
                <option value="4">Nivel 4 - Coordinación</option>
                <option value="5">Nivel 5 - Operativo</option>
              </select>
            </div>

            <!-- Área -->
            <div class="col-md-6">
              <label class="form-label fw-semibold mb-2" style="font-size: 0.875rem; color: #0f172a;">
                <i class="bi bi-building me-1" style="color: #3b82f6;"></i>Área/Departamento
              </label>
              <select 
                class="form-select" 
                id="org-position-area"
                style="border-radius: 12px; padding: 0.625rem 1rem; border: 1px solid #e2e8f0;"
              >
                <option value="">Seleccionar...</option>
                <option value="Dirección">Dirección</option>
                <option value="Operaciones">Operaciones</option>
                <option value="Administración">Administración</option>
                <option value="Recursos Humanos">Recursos Humanos</option>
                <option value="Tecnología">Tecnología</option>
                <option value="Comercial">Comercial</option>
                <option value="Finanzas">Finanzas</option>
              </select>
            </div>

            <!-- Unidad -->
            <div class="col-md-6">
              <label class="form-label fw-semibold mb-2" style="font-size: 0.875rem; color: #0f172a;">
                <i class="bi bi-geo-alt me-1" style="color: #3b82f6;"></i>Unidad/Ubicación
              </label>
              <select 
                class="form-select" 
                id="org-position-unit"
                style="border-radius: 12px; padding: 0.625rem 1rem; border: 1px solid #e2e8f0;"
              >
                <option value="">Seleccionar...</option>
                <option value="Corporativo">Corporativo</option>
                <option value="Norte">Norte</option>
                <option value="Sur">Sur</option>
                <option value="Centro">Centro</option>
              </select>
            </div>

            <!-- Foto -->
            <div class="col-md-6">
              <label class="form-label fw-semibold mb-2" style="font-size: 0.875rem; color: #0f172a;">
                <i class="bi bi-image me-1" style="color: #3b82f6;"></i>Foto (URL)
              </label>
              <input 
                type="url" 
                class="form-control" 
                id="org-position-photo" 
                placeholder="https://..."
                style="border-radius: 12px; padding: 0.625rem 1rem; border: 1px solid #e2e8f0;"
              >
            </div>

            <!-- Descripción -->
            <div class="col-12">
              <label class="form-label fw-semibold mb-2" style="font-size: 0.875rem; color: #0f172a;">
                <i class="bi bi-file-text me-1" style="color: #3b82f6;"></i>Descripción del Puesto
              </label>
              <textarea 
                class="form-control" 
                id="org-position-description" 
                rows="3" 
                placeholder="Describe las funciones principales de este puesto..."
                style="border-radius: 12px; padding: 0.625rem 1rem; border: 1px solid #e2e8f0;"
              ></textarea>
            </div>

            <!-- Responsabilidades -->
            <div class="col-12">
              <label class="form-label fw-semibold mb-2" style="font-size: 0.875rem; color: #0f172a;">
                <i class="bi bi-list-check me-1" style="color: #3b82f6;"></i>Responsabilidades Clave
              </label>
              <textarea 
                class="form-control" 
                id="org-position-responsibilities" 
                rows="3" 
                placeholder="Una responsabilidad por línea..."
                style="border-radius: 12px; padding: 0.625rem 1rem; border: 1px solid #e2e8f0;"
              ></textarea>
              <small class="text-muted">Separa cada responsabilidad con un salto de línea</small>
            </div>

            <!-- Herramientas -->
            <div class="col-12">
              <label class="form-label fw-semibold mb-2" style="font-size: 0.875rem; color: #0f172a;">
                <i class="bi bi-tools me-1" style="color: #3b82f6;"></i>Herramientas y Sistemas
              </label>
              <input 
                type="text" 
                class="form-control" 
                id="org-position-tools" 
                placeholder="Ej: Excel, CRM, SAP..."
                style="border-radius: 12px; padding: 0.625rem 1rem; border: 1px solid #e2e8f0;"
              >
            </div>
          </div>
        </form>
      </div>
      
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-ghost" data-bs-dismiss="modal" style="border-radius: 12px;">
          Cancelar
        </button>
        <button type="submit" form="org-form-position" class="btn btn-brand" style="border-radius: 12px;">
          <i class="bi bi-check2-circle me-1"></i>
          <span id="org-modal-btn-text">Guardar Puesto</span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Script principal del Organigrama -->
<script src="/modules/processes_tasks/js/organigrama.js?v=<?php echo time(); ?>"></script>
