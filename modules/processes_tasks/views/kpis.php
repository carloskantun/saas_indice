<!-- Header de sección (igual RH) -->
<div class="hr-header">
  <div class="hr-header-left">
    <h1><i class="bi bi-graph-up-arrow me-2 text-warning" aria-hidden="true"></i>Indicadores de Productividad</h1>
    <p class="subtitle">Métricas en tiempo real para medir rendimiento, cumplimiento y efectividad del equipo.</p>
  </div>
</div>

<!-- Barra de Filtros -->
<div class="card-min p-4 mb-4 reveal">
  <div class="row g-4 align-items-end">
    <!-- Búsqueda -->
    <div class="col-md-3">
      <label class="form-label fw-semibold mb-2">
        <i class="bi bi-search me-1 text-warning" aria-hidden="true"></i>Buscar
      </label>
      <input 
        type="text" 
        class="form-control" 
        id="kpi-search" 
        placeholder="Proyecto, proceso o tarea..."
        style="border-radius: 12px; padding: 0.625rem 1rem; border: 1px solid #e2e8f0;"
      >
    </div>

    <!-- Colaborador -->
    <div class="col-md-3">
      <label class="form-label fw-semibold mb-2">
        <i class="bi bi-person me-1 text-warning" aria-hidden="true"></i>Colaborador
      </label>
      <select 
        class="form-select" 
        id="kpi-user"
        style="border-radius: 12px; padding: 0.625rem 1rem; border: 1px solid #e2e8f0;"
      >
        <option value="">Todos los colaboradores</option>
      </select>
    </div>

    <!-- Periodo -->
    <div class="col-md-3">
      <label class="form-label fw-semibold mb-2">
        <i class="bi bi-calendar-range me-1 text-warning" aria-hidden="true"></i>Periodo
      </label>
      <select 
        class="form-select" 
        id="kpi-time-filter"
        style="border-radius: 12px; padding: 0.625rem 1rem; border: 1px solid #e2e8f0;"
      >
        <option value="today">Hoy</option>
        <option value="week">Esta semana</option>
        <option value="month" selected>Este mes</option>
        <option value="all">Todo el tiempo</option>
        <option value="custom">Personalizado</option>
      </select>
    </div>

    <!-- Botones de Acción -->
    <div class="col-md-3">
      <div class="d-flex gap-2 justify-content-end">
        <button 
          class="btn btn-ghost flex-fill" 
          id="kpi-refresh"
        >
          <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
        </button>
        <button 
          class="btn btn-brand flex-fill" 
          id="kpi-export"
        >
          <i class="bi bi-download me-1"></i>Exportar
        </button>
      </div>
    </div>
  </div>

  <!-- Rango personalizado (oculto por defecto) -->
  <div id="kpi-custom-range" class="row g-3 mt-2" style="display: none;">
    <div class="col-md-6">
      <label class="form-label fw-semibold mb-2">
        <i class="bi bi-calendar-event me-1 text-warning" aria-hidden="true"></i>Desde
      </label>
      <input 
        type="date" 
        class="form-control" 
        id="kpi-date-from"
        style="border-radius: 12px; padding: 0.625rem 1rem; border: 1px solid #e2e8f0;"
      >
    </div>
    <div class="col-md-6">
      <label class="form-label fw-semibold mb-2">
        <i class="bi bi-calendar-check me-1 text-warning" aria-hidden="true"></i>Hasta
      </label>
      <input 
        type="date" 
        class="form-control" 
        id="kpi-date-to"
        style="border-radius: 12px; padding: 0.625rem 1rem; border: 1px solid #e2e8f0;"
      >
    </div>
  </div>
</div>

<!-- KPIs Summary (10 tarjetas principales) -->
<div id="kpi-cards" class="row g-4 mb-4">
  <!-- Productividad % -->
  <div class="col-xl-3 col-md-6">
    <div class="card-min reveal" style="cursor: pointer; transition: all 0.3s ease;">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <div class="text-muted mb-1" style="font-size: 0.875rem; font-weight: 500;">
            <i class="bi bi-speedometer2 me-1 text-warning" aria-hidden="true"></i>Productividad %
          </div>
          <div class="h2 fw-bold mb-0" id="kpi-productividad" style="color: #0f172a;">--%</div>
        </div>
        <div>
          <span class="badge" id="kpi-productividad-badge" style="border-radius: 8px; padding: 0.375rem 0.75rem;">
            --
          </span>
        </div>
      </div>
      <div class="text-muted" style="font-size: 0.875rem;">
        <i class="bi bi-info-circle me-1"></i>
        <span id="kpi-productividad-detail">Completadas / Asignadas</span>
      </div>
    </div>
  </div>

<!-- Token CSRF para AJAX (compat con kpis.js y otros scripts) -->
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

  <!-- Productividad Ponderada -->
  <div class="col-xl-3 col-md-6">
    <div class="card-min reveal" style="cursor: pointer; transition: all 0.3s ease;">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <div class="text-muted mb-1" style="font-size: 0.875rem; font-weight: 500;">
            <i class="bi bi-star me-1" style="color: #f59e0b;"></i>Productividad Ponderada
          </div>
          <div class="h2 fw-bold mb-0" id="kpi-ponderada" style="color: #0f172a;">--%</div>
        </div>
        <div>
          <span class="badge" id="kpi-ponderada-badge" style="border-radius: 8px; padding: 0.375rem 0.75rem;">
            --
          </span>
        </div>
      </div>
      <div class="text-muted" style="font-size: 0.875rem;">
        <i class="bi bi-info-circle me-1"></i>
        <span id="kpi-ponderada-detail">Con peso de tareas</span>
      </div>
    </div>
  </div>

  <!-- Total Tareas Asignadas -->
  <div class="col-xl-3 col-md-6">
    <div class="card-min reveal" style="cursor: pointer; transition: all 0.3s ease;">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <div class="text-muted mb-1" style="font-size: 0.875rem; font-weight: 500;">
            <i class="bi bi-list-check me-1 text-warning" aria-hidden="true"></i>Total Asignadas
          </div>
          <div class="h2 fw-bold mb-0" id="kpi-total" style="color: #0f172a;">--</div>
        </div>
      </div>
      <div class="text-muted" style="font-size: 0.875rem;">
        <i class="bi bi-info-circle me-1"></i>
        <span>En el periodo seleccionado</span>
      </div>
    </div>
  </div>

  <!-- Tareas Completadas -->
  <div class="col-xl-3 col-md-6">
    <div class="card-min reveal" style="cursor: pointer; transition: all 0.3s ease;">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <div class="text-muted mb-1" style="font-size: 0.875rem; font-weight: 500;">
            <i class="bi bi-check-circle me-1" style="color: #10b981;"></i>Completadas
          </div>
          <div class="h2 fw-bold mb-0" id="kpi-completadas" style="color: #10b981;">--</div>
        </div>
        <div>
          <span class="badge bg-success" id="kpi-completadas-pct" style="border-radius: 8px; padding: 0.375rem 0.75rem;">
            --%
          </span>
        </div>
      </div>
      <div class="text-muted" style="font-size: 0.875rem;">
        <i class="bi bi-info-circle me-1"></i>
        <span id="kpi-completadas-detail">--</span>
      </div>
    </div>
  </div>

  <!-- Tareas Vencidas -->
  <div class="col-xl-3 col-md-6">
    <div class="card-min reveal" style="cursor: pointer; transition: all 0.3s ease;">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <div class="text-muted mb-1" style="font-size: 0.875rem; font-weight: 500;">
            <i class="bi bi-exclamation-triangle me-1" style="color: #ef4444;"></i>Vencidas
          </div>
          <div class="h2 fw-bold mb-0" id="kpi-vencidas" style="color: #ef4444;">--</div>
        </div>
        <div>
          <span class="badge bg-danger" id="kpi-vencidas-pct" style="border-radius: 8px; padding: 0.625rem 0.75rem;">
            --%
          </span>
        </div>
      </div>
      <div class="text-muted" style="font-size: 0.875rem;">
        <i class="bi bi-info-circle me-1"></i>
        <span id="kpi-vencidas-detail">--</span>
      </div>
    </div>
  </div>

  <!-- Tareas En Proceso -->
  <div class="col-xl-3 col-md-6">
    <div class="card-min reveal" style="cursor: pointer; transition: all 0.3s ease;">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <div class="text-muted mb-1" style="font-size: 0.875rem; font-weight: 500;">
            <i class="bi bi-hourglass-split me-1" style="color: #f59e0b;"></i>En Proceso
          </div>
          <div class="h2 fw-bold mb-0" id="kpi-proceso" style="color: #f59e0b;">--</div>
        </div>
        <div>
          <span class="badge bg-warning" id="kpi-proceso-pct" style="border-radius: 8px; padding: 0.375rem 0.75rem;">
            --%
          </span>
        </div>
      </div>
      <div class="text-muted" style="font-size: 0.875rem;">
        <i class="bi bi-info-circle me-1"></i>
        <span id="kpi-proceso-detail">--</span>
      </div>
    </div>
  </div>

  <!-- Duración Promedio -->
  <div class="col-xl-3 col-md-6">
    <div class="card-min reveal" style="cursor: pointer; transition: all 0.3s ease;">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <div class="text-muted mb-1" style="font-size: 0.875rem; font-weight: 500;">
            <i class="bi bi-clock-history me-1" style="color: #8b5cf6;"></i>Duración Promedio
          </div>
          <div class="h2 fw-bold mb-0" id="kpi-duracion" style="color: #0f172a;">-- días</div>
        </div>
      </div>
      <div class="text-muted" style="font-size: 0.875rem;">
        <i class="bi bi-info-circle me-1"></i>
        <span>Tiempo hasta entrega</span>
      </div>
    </div>
  </div>

  <!-- Colaboradores Activos -->
  <div class="col-xl-3 col-md-6">
    <div class="card-min reveal" style="cursor: pointer; transition: all 0.3s ease;">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <div class="text-muted mb-1" style="font-size: 0.875rem; font-weight: 500;">
            <i class="bi bi-people me-1" style="color: #06b6d4;"></i>Colaboradores Activos
          </div>
          <div class="h2 fw-bold mb-0" id="kpi-colaboradores" style="color: #0f172a;">--</div>
        </div>
      </div>
      <div class="text-muted" style="font-size: 0.875rem;">
        <i class="bi bi-info-circle me-1"></i>
        <span>Con tareas asignadas</span>
      </div>
    </div>
  </div>

  <!-- Procesos Activos -->
  <div class="col-xl-3 col-md-6">
    <div class="card-min reveal" style="cursor: pointer; transition: all 0.3s ease;">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <div class="text-muted mb-1" style="font-size: 0.875rem; font-weight: 500;">
            <i class="bi bi-diagram-3 me-1" style="color: #ec4899;"></i>Procesos Activos
          </div>
          <div class="h2 fw-bold mb-0" id="kpi-procesos" style="color: #0f172a;">--</div>
        </div>
      </div>
      <div class="text-muted" style="font-size: 0.875rem;">
        <i class="bi bi-info-circle me-1"></i>
        <span>En el periodo</span>
      </div>
    </div>
  </div>

  <!-- Distribución por Prioridad -->
  <div class="col-12">
    <div class="card-min reveal" style="cursor: pointer; transition: all 0.3s ease;">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <div class="text-muted mb-1" style="font-size: 0.875rem; font-weight: 500;">
            <i class="bi bi-flag me-1 text-warning" aria-hidden="true"></i>Distribución por Prioridad
          </div>
        </div>
      </div>
      <div class="d-flex gap-4" id="kpi-prioridades">
        <div>
          <span class="badge bg-success" style="border-radius: 8px; padding: 0.5rem 0.875rem;">
            <i class="bi bi-flag-fill me-1"></i>Normal: <strong id="kpi-pri-normal">--</strong>
          </span>
        </div>
        <div>
          <span class="badge bg-warning" style="border-radius: 8px; padding: 0.5rem 0.875rem;">
            <i class="bi bi-flag-fill me-1"></i>Importante: <strong id="kpi-pri-importante">--</strong>
          </span>
        </div>
        <div>
          <span class="badge bg-danger" style="border-radius: 8px; padding: 0.5rem 0.875rem;">
            <i class="bi bi-flag-fill me-1"></i>Urgente: <strong id="kpi-pri-urgente">--</strong>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

  <!-- Empty State -->
  <div id="kpi-empty-state" class="text-center py-5" style="display: none;">
    <i class="bi bi-graph-up text-muted" style="font-size: 4rem;"></i>
    <h4 class="text-muted mt-3">Sin datos para mostrar</h4>
    <p class="text-muted">No se encontraron tareas en el periodo seleccionado</p>
    <button class="btn btn-brand mt-3" onclick="location.reload()">
      <i class="bi bi-arrow-clockwise me-1"></i>Recargar indicadores
    </button>
  </div>

  <!-- Gráficas principales -->
  <div class="row g-4 mb-4">
    <div class="col-lg-6">
      <div class="card-min reveal">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="fw-bold mb-0" style="color: #0f172a;">
            <i class="bi bi-person-check me-2 text-warning" aria-hidden="true"></i>Cumplimiento por Usuario
          </h6>
          <span class="badge bg-light text-dark" id="users-chart-count" style="border-radius: 8px; padding: 0.375rem 0.75rem;">
            0 usuarios
          </span>
        </div>
        <div class="chart-container" style="position: relative; height: 300px;">
          <canvas id="kpi-chart-users"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card-min reveal">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="fw-bold mb-0" style="color: #0f172a;">
            <i class="bi bi-diagram-3 me-2 text-warning" aria-hidden="true"></i>Procesos Activos por Flujo
          </h6>
          <span class="badge bg-light text-dark" id="flows-chart-count" style="border-radius: 8px; padding: 0.375rem 0.75rem;">
            0 flujos
          </span>
        </div>
        <div class="chart-container" style="position: relative; height: 300px;">
          <canvas id="kpi-chart-flows"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Gráficas adicionales -->
  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card-min reveal">
        <h6 class="fw-bold mb-3" style="color: #0f172a;">
          <i class="bi bi-pie-chart me-2 text-warning" aria-hidden="true"></i>Distribución de Estados
        </h6>
        <div class="chart-container" style="position: relative; height: 250px;">
          <canvas id="kpi-chart-status"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card-min reveal">
        <h6 class="fw-bold mb-3" style="color: #0f172a;">
          <i class="bi bi-flag me-2 text-warning" aria-hidden="true"></i>Prioridades de Tareas
        </h6>
        <div class="chart-container" style="position: relative; height: 250px;">
          <canvas id="kpi-chart-priority"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card-min reveal">
        <h6 class="fw-bold mb-3" style="color: #0f172a;">
          <i class="bi bi-graph-up me-2 text-warning" aria-hidden="true"></i>Tendencia Semanal
        </h6>
        <div class="chart-container" style="position: relative; height: 250px;">
          <canvas id="kpi-chart-trend"></canvas>
        </div>
      </div>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/modules/processes_tasks/js/kpis.js?v=<?php echo time(); ?>"></script>
