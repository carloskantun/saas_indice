<!-- Modal Nueva Tarea -->
<div class="modal fade" id="modalNuevaTarea" tabindex="-1" aria-labelledby="modalNuevaTareaLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalNuevaTareaLabel">
          <i class="bi bi-plus-circle me-2"></i>Nueva Tarea
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formNuevaTarea">
          <input type="hidden" name="action" value="create_task">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
          
          <div class="row g-3">
            <!-- Título -->
            <div class="col-12">
              <label for="tareaTitle" class="form-label">Título <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="tareaTitle" name="title" required>
            </div>
            
            <!-- Descripción -->
            <div class="col-12">
              <label for="tareaDescription" class="form-label">Descripción</label>
              <textarea class="form-control" id="tareaDescription" name="description" rows="3"></textarea>
            </div>
            
            <!-- Fecha de Entrega -->
            <div class="col-md-6">
              <label for="tareaFechaEntrega" class="form-label">
                <i class="bi bi-calendar-event text-primary me-1"></i>Fecha de Entrega
              </label>
              <input type="date" class="form-control" id="tareaFechaEntrega" name="fecha_entrega">
              <small class="form-text text-muted">Fecha comprometida de entrega del entregable</small>
            </div>
            
            <!-- Unidad de Negocio -->
            <div class="col-md-6">
              <label for="tareaUnit" class="form-label">Unidad de Negocio</label>
              <select class="form-select" id="tareaUnit" name="unit">
                <option value="">Seleccionar...</option>
                <option value="CDMX">CDMX</option>
                <option value="Monterrey">Monterrey</option>
                <option value="Guadalajara">Guadalajara</option>
              </select>
            </div>
            
            <!-- Negocio -->
            <div class="col-md-6">
              <label for="tareaBusiness" class="form-label">Negocio</label>
              <select class="form-select" id="tareaBusiness" name="business">
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
              <label for="tareaStartDate" class="form-label">Fecha de Inicio</label>
              <input type="date" class="form-control" id="tareaStartDate" name="start_date" value="<?= date('Y-m-d') ?>">
            </div>
            
            <!-- Fecha de Vencimiento -->
            <div class="col-md-6">
              <label for="tareaDueDate" class="form-label">Fecha de Vencimiento</label>
              <input type="date" class="form-control" id="tareaDueDate" name="due_date">
            </div>
            
            <!-- Delegado a -->
            <div class="col-md-6">
              <label for="tareaDelegatedTo" class="form-label">Delegado a</label>
              <select class="form-select" id="tareaDelegatedTo" name="delegated_to">
                <option value="">Seleccionar usuario...</option>
                <!-- Los usuarios se cargarán dinámicamente -->
              </select>
            </div>
            
            <!-- Nivel de Prioridad -->
            <div class="col-md-6">
              <label for="tareaPriority" class="form-label">Nivel de Prioridad</label>
              <select class="form-select" id="tareaPriority" name="priority">
                <option value="low">Baja</option>
                <option value="medium" selected>Media</option>
                <option value="high">Alta</option>
                <option value="critical">Crítica</option>
              </select>
            </div>
            
            <!-- Tipo -->
            <div class="col-md-6">
              <label for="tareaType" class="form-label">Tipo</label>
              <select class="form-select" id="tareaType" name="type">
                <option value="task">Tarea</option>
                <option value="process_task">Tarea de Proceso</option>
              </select>
            </div>
            
            <!-- Proyecto Asignado -->
            <div class="col-md-6">
              <label for="tareaProject" class="form-label">Proyecto Asignado</label>
              <select class="form-select" id="tareaProject" name="project_id">
                <option value="">Sin proyecto</option>
                <!-- Los proyectos se cargarán dinámicamente -->
              </select>
            </div>
            
            <!-- Ponderación -->
            <div class="col-md-6">
              <label for="tareaPonderacion" class="form-label">Ponderación (1-5)</label>
              <select class="form-select" id="tareaPonderacion" name="ponderacion">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3" selected>3</option>
                <option value="4">4</option>
                <option value="5">5</option>
              </select>
            </div>
            
            <!-- Status Inicial -->
            <div class="col-md-6">
              <label for="tareaStatus" class="form-label">Status</label>
              <select class="form-select" id="tareaStatus" name="status">
                <option value="pending">Pendiente</option>
                <option value="in_progress">En Progreso</option>
                <option value="completed">Completada</option>
              </select>
            </div>
            
            <!-- Archivos Adjuntos -->
            <div class="col-12">
              <label for="tareaFiles" class="form-label">Archivos Adjuntos</label>
              <input type="file" class="form-control" id="tareaFiles" name="files[]" multiple>
              <small class="form-text text-muted">Puedes adjuntar múltiples archivos</small>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarTarea">
          <i class="bi bi-save me-1"></i>Guardar Tarea
        </button>
      </div>
    </div>
  </div>
</div>
