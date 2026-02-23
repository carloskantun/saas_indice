<!-- Modal Nuevo Proyecto -->
<div class="modal fade" id="modalNuevoProyecto" tabindex="-1" aria-labelledby="modalNuevoProyectoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalNuevoProyectoLabel">
          <i class="bi bi-folder-plus me-2"></i>Nuevo Proyecto
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formNuevoProyecto">
          <input type="hidden" name="action" value="create_project">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
          
          <div class="row g-3">
            <!-- Nombre del Proyecto -->
            <div class="col-12">
              <label for="proyectoName" class="form-label">Nombre del Proyecto <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="proyectoName" name="name" required>
            </div>
            
            <!-- Descripción -->
            <div class="col-12">
              <label for="proyectoDescription" class="form-label">Descripción</label>
              <textarea class="form-control" id="proyectoDescription" name="description" rows="4" placeholder="Describe los objetivos y alcance del proyecto..."></textarea>
            </div>
            
            <!-- Unidad de Negocio -->
            <div class="col-md-6">
              <label for="proyectoUnit" class="form-label">Unidad de Negocio</label>
              <select class="form-select" id="proyectoUnit" name="unit">
                <option value="">Seleccionar...</option>
                <option value="CDMX">CDMX</option>
                <option value="Monterrey">Monterrey</option>
                <option value="Guadalajara">Guadalajara</option>
              </select>
            </div>
            
            <!-- Negocio -->
            <div class="col-md-6">
              <label for="proyectoBusiness" class="form-label">Negocio</label>
              <select class="form-select" id="proyectoBusiness" name="business">
                <option value="">Seleccionar...</option>
                <option value="Operaciones">Operaciones</option>
                <option value="Ventas">Ventas</option>
                <option value="Marketing">Marketing</option>
                <option value="Finanzas">Finanzas</option>
                <option value="Recursos Humanos">Recursos Humanos</option>
              </select>
            </div>
            
            <!-- Fecha de Inicio -->
            <div class="col-md-6">
              <label for="proyectoStartDate" class="form-label">Fecha de Inicio</label>
              <input type="date" class="form-control" id="proyectoStartDate" name="start_date" value="<?= date('Y-m-d') ?>">
            </div>
            
            <!-- Fecha de Fin Estimada -->
            <div class="col-md-6">
              <label for="proyectoEndDate" class="form-label">Fecha de Fin Estimada</label>
              <input type="date" class="form-control" id="proyectoEndDate" name="end_date">
            </div>
            
            <!-- Responsable del Proyecto -->
            <div class="col-md-6">
              <label for="proyectoOwner" class="form-label">Responsable del Proyecto <span class="text-danger">*</span></label>
              <select class="form-select" id="proyectoOwner" name="owner_id" required>
                <option value="">Seleccionar responsable...</option>
                <!-- Los usuarios se cargarán dinámicamente -->
              </select>
            </div>
            
            <!-- Estado -->
            <div class="col-md-6">
              <label for="proyectoStatus" class="form-label">Estado</label>
              <select class="form-select" id="proyectoStatus" name="status">
                <option value="planning">Planeación</option>
                <option value="active" selected>Activo</option>
                <option value="on_hold">En Pausa</option>
                <option value="completed">Completado</option>
                <option value="cancelled">Cancelado</option>
              </select>
            </div>
            
            <!-- Presupuesto (Opcional) -->
            <div class="col-md-6">
              <label for="proyectoBudget" class="form-label">Presupuesto (MXN)</label>
              <input type="number" class="form-control" id="proyectoBudget" name="budget" min="0" step="0.01" placeholder="0.00">
            </div>
            
            <!-- Prioridad -->
            <div class="col-md-6">
              <label for="proyectoPriority" class="form-label">Prioridad</label>
              <select class="form-select" id="proyectoPriority" name="priority">
                <option value="low">Baja</option>
                <option value="medium" selected>Media</option>
                <option value="high">Alta</option>
                <option value="critical">Crítica</option>
              </select>
            </div>
            
            <!-- Progreso Inicial -->
            <div class="col-md-6">
              <label for="proyectoProgress" class="form-label">Progreso Inicial (%)</label>
              <input type="number" class="form-control" id="proyectoProgress" name="progress" min="0" max="100" value="0">
            </div>
            
            <!-- Color de Identificación -->
            <div class="col-md-6">
              <label for="proyectoColor" class="form-label">Color de Identificación</label>
              <input type="color" class="form-control form-control-color" id="proyectoColor" name="color" value="#1f4d9f" title="Elige un color para identificar el proyecto">
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarProyecto">
          <i class="bi bi-save me-1"></i>Crear Proyecto
        </button>
      </div>
    </div>
  </div>
</div>
