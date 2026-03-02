<!-- Modal Nuevo Proceso -->
<div class="modal fade" id="modalNuevoProceso" tabindex="-1" aria-labelledby="modalNuevoProcesoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalNuevoProcesoLabel">
          <i class="bi bi-arrow-repeat me-2"></i>Nuevo Proceso Recurrente
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formNuevoProceso">
          <input type="hidden" name="action" value="create_process">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
          
          <div class="row g-3">
            <!-- Nombre del Proceso -->
            <div class="col-12">
              <label for="procesoName" class="form-label">Nombre del Proceso <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="procesoName" name="name" required>
            </div>
            
            <!-- Descripción -->
            <div class="col-12">
              <label for="procesoDescription" class="form-label">Descripción</label>
              <textarea class="form-control" id="procesoDescription" name="description" rows="3"></textarea>
            </div>
            
            <!-- Fecha de Entrega -->
            <div class="col-md-6">
              <label for="procesoFechaEntrega" class="form-label">
                <i class="bi bi-calendar-event text-primary me-1"></i>Fecha de Entrega
              </label>
              <input type="date" class="form-control" id="procesoFechaEntrega" name="fecha_entrega">
              <small class="form-text text-muted">Fecha comprometida de entrega</small>
            </div>
            
            <!-- Unidad de Negocio -->
            <div class="col-md-6">
              <label for="procesoUnit" class="form-label">Unidad de Negocio</label>
              <select class="form-select" id="procesoUnit" name="unit">
                <option value="">Seleccionar...</option>
                <option value="CDMX">CDMX</option>
                <option value="Monterrey">Monterrey</option>
                <option value="Guadalajara">Guadalajara</option>
              </select>
            </div>
            
            <!-- Negocio -->
            <div class="col-md-6">
              <label for="procesoBusiness" class="form-label">Negocio</label>
              <select class="form-select" id="procesoBusiness" name="business">
                <option value="">Seleccionar...</option>
                <option value="Operaciones">Operaciones</option>
                <option value="Ventas">Ventas</option>
                <option value="Marketing">Marketing</option>
                <option value="Finanzas">Finanzas</option>
                <option value="Recursos Humanos">Recursos Humanos</option>
              </select>
            </div>
            
            <!-- Frecuencia -->
            <div class="col-md-6">
              <label for="procesoFrequency" class="form-label">Frecuencia <span class="text-danger">*</span></label>
              <select class="form-select" id="procesoFrequency" name="frequency" required>
                <option value="">Seleccionar frecuencia...</option>
                <option value="daily">Diario</option>
                <option value="weekly">Semanal</option>
                <option value="biweekly">Quincenal</option>
                <option value="monthly">Mensual</option>
                <option value="quarterly">Trimestral</option>
                <option value="semiannual">Semestral</option>
                <option value="annual">Anual</option>
              </select>
            </div>
            
            <!-- Día Específico (para semanales) -->
            <div class="col-md-6" id="diaSemanContainer" style="display: none;">
              <label for="procesoDiaSemana" class="form-label">Día de la Semana</label>
              <select class="form-select" id="procesoDiaSemana" name="day_of_week">
                <option value="1">Lunes</option>
                <option value="2">Martes</option>
                <option value="3">Miércoles</option>
                <option value="4">Jueves</option>
                <option value="5">Viernes</option>
                <option value="6">Sábado</option>
                <option value="0">Domingo</option>
              </select>
            </div>
            
            <!-- Día del Mes (para mensuales) -->
            <div class="col-md-6" id="diaMesContainer" style="display: none;">
              <label for="procesoDiaMes" class="form-label">Día del Mes</label>
              <input type="number" class="form-control" id="procesoDiaMes" name="day_of_month" min="1" max="31" value="1">
            </div>
            
            <!-- Responsable -->
            <div class="col-md-6">
              <label for="procesoResponsible" class="form-label">Responsable <span class="text-danger">*</span></label>
              <select class="form-select" id="procesoResponsible" name="responsible_id" required>
                <option value="">Seleccionar responsable...</option>
                <!-- Los usuarios se cargarán dinámicamente -->
              </select>
            </div>
            
            <!-- Nivel de Prioridad -->
            <div class="col-md-6">
              <label for="procesoPriority" class="form-label">Nivel de Prioridad</label>
              <select class="form-select" id="procesoPriority" name="priority">
                <option value="low">Baja</option>
                <option value="medium" selected>Media</option>
                <option value="high">Alta</option>
                <option value="critical">Crítica</option>
              </select>
            </div>
            
            <!-- Estado -->
            <div class="col-md-6">
              <label for="procesoStatus" class="form-label">Estado</label>
              <select class="form-select" id="procesoStatus" name="status">
                <option value="active" selected>Activo</option>
                <option value="paused">Pausado</option>
                <option value="inactive">Inactivo</option>
              </select>
            </div>
            
            <!-- Duración Estimada (días) -->
            <div class="col-md-6">
              <label for="procesoDuration" class="form-label">Duración Estimada (días)</label>
              <input type="number" class="form-control" id="procesoDuration" name="estimated_duration" min="1" value="1">
            </div>
            
            <!-- Fecha de Inicio de Ejecución -->
            <div class="col-md-6">
              <label for="procesoStartDate" class="form-label">Iniciar Ejecución Desde</label>
              <input type="date" class="form-control" id="procesoStartDate" name="start_date" value="<?= date('Y-m-d') ?>">
            </div>
            
            <!-- Fecha de Fin (Opcional) -->
            <div class="col-md-6">
              <label for="procesoEndDate" class="form-label">Terminar Ejecución En (Opcional)</label>
              <input type="date" class="form-control" id="procesoEndDate" name="end_date">
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarProceso">
          <i class="bi bi-save me-1"></i>Guardar Proceso
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Mostrar/ocultar campos según la frecuencia seleccionada
document.getElementById('procesoFrequency')?.addEventListener('change', function() {
  const frequency = this.value;
  const diaSemanContainer = document.getElementById('diaSemanContainer');
  const diaMesContainer = document.getElementById('diaMesContainer');
  
  // Ocultar todos por defecto
  diaSemanContainer.style.display = 'none';
  diaMesContainer.style.display = 'none';
  
  // Mostrar según frecuencia
  if (frequency === 'weekly') {
    diaSemanContainer.style.display = 'block';
  } else if (frequency === 'monthly' || frequency === 'quarterly' || frequency === 'semiannual' || frequency === 'annual') {
    diaMesContainer.style.display = 'block';
  }
});
</script>
