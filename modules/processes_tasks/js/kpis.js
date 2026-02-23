// ========================================
// kpis.js v4.0 — ENTERPRISE PATCH
// Índice ERP - Módulo de KPIs y Productividad
// Arquitectura: Module Pattern, Centralized Helpers, Chart.js
// ========================================

window.KPIModule = window.KPIModule || {};

// Configuration
const API_BASE = '/modules/processes_tasks/controllers/api.controller.php';

// ========================================
// STATE MANAGEMENT
// ========================================
KPIModule.state = {
    tasks: [],
    users: [],
    processes: [],
    filters: {
        period: 'month',
        dateFrom: null,
        dateTo: null,
        search: '',
        user: ''
    },
    charts: {

    /**
     * Obtain CSRF token from the DOM.
     * Priority:
     *  1) <input name="csrf_token" value="...">
     *  2) <meta name="csrf-token" content="...">
     */
    KPIModule.getCsrfToken = function () {
        const inputToken = document.querySelector('input[name="csrf_token"]')?.value;
        if (inputToken) return inputToken;

        const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (metaToken) return metaToken;

        return '';
    };
        users: null,
        flows: null,
        status: null,
        priority: null,
        trend: null
        const csrfToken = KPIModule.getCsrfToken();
// CENTRALIZED HELPERS
// ========================================

/**
 * Centralized logging with color coding
 */
KPIModule.log = function (msg, type = 'info') {
    const colors = {
        info: 'color: #3b82f6; font-weight: bold',
        success: 'color: #10b981; font-weight: bold',
        warning: 'color: #f59e0b; font-weight: bold',
        error: 'color: #ef4444; font-weight: bold'
    };
    console.log(`%c[KPIModule] ${msg}`, colors[type] || colors.info);
};

/**
 * Toast notifications with triple fallback
 */
KPIModule.toast = function (message, type = 'info') {
    KPIModule.log(`Toast: ${message}`, type);

    // Fallback 1: showNotification (global function)
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
        return;
    }

    // Fallback 2: showToast (from agenda/tasks)
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
        return;
    }

    // Fallback 3: Bootstrap Toast
    const toastContainer = document.getElementById('toast-container') || document.body;
    const toastId = 'toast-' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-primary';

    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();

    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
};

/**
 * Handle errors consistently
 */
KPIModule.handleError = function (error, context = '') {
    const message = error.message || error.error || 'Error desconocido';
    KPIModule.log(`Error en ${context}: ${message}`, 'error');
    KPIModule.toast(`Error: ${message}`, 'error');
    console.error('Full error:', error);
};

/**
 * Show loading spinner
 */
KPIModule.showLoading = function (selector = '#kpi-cards') {
    const el = document.querySelector(selector);
    if (el) {
        el.style.opacity = '0.5';
        el.style.pointerEvents = 'none';
    }
};

/**
 * Hide loading spinner
 */
KPIModule.hideLoading = function (selector = '#kpi-cards') {
    const el = document.querySelector(selector);
    if (el) {
        el.style.opacity = '1';
        el.style.pointerEvents = 'auto';
    }
};

/**
 * API wrapper with CSRF token
 */
KPIModule.api = async function (action, params = {}, method = 'POST') {
    const csrfToken =
        document.querySelector('input[name="csrf_token"]')?.value ||
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
        '';

    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken || ''
        }
    };

    const payload = { action, ...params };

    if (method === 'POST') {
        options.body = JSON.stringify(payload);
    }

    KPIModule.log(`API Call: ${action}`, 'info');

    try {
        const response = await fetch(API_BASE, options);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();

        if (data.success === false) {
            throw new Error(data.message || data.error || 'Error en la respuesta');
        }

        return data;

    } catch (error) {
        KPIModule.handleError(error, `api(${action})`);
        throw error;
    }
};

/**
 * Format number with thousand separators
 */
KPIModule.formatNumber = function (num) {
    return new Intl.NumberFormat('es-MX').format(num);
};

/**
 * Format percentage
 */
KPIModule.formatPercent = function (value) {
    return `${Math.round(value)}%`;
};

/**
 * Get badge class based on productivity percentage
 */
KPIModule.getBadgeClass = function (percent) {
    if (percent >= 90) return 'bg-success';
    if (percent >= 70) return 'bg-info';
    if (percent >= 40) return 'bg-warning';
    return 'bg-danger';
};

/**
 * Get badge text based on productivity percentage
 */
KPIModule.getBadgeText = function (percent) {
    if (percent >= 90) return 'Excelente';
    if (percent >= 70) return 'Bueno';
    if (percent >= 40) return 'Regular';
    return 'Bajo';
};

// ========================================
// DATA LOADING
// ========================================

/**
 * Load all tasks with filters
 */
KPIModule.loadTasksData = async function () {
    try {
        KPIModule.showLoading();
        KPIModule.log('Cargando datos de tareas...', 'info');

        const response = await KPIModule.api('getTasks', {
            dateFrom: KPIModule.state.filters.dateFrom,
            dateTo: KPIModule.state.filters.dateTo,
            user: KPIModule.state.filters.user,
            search: KPIModule.state.filters.search
        });

        if (response.success && response.data) {
            KPIModule.state.tasks = response.data.tasks || [];
            KPIModule.state.users = response.data.users || [];
            KPIModule.state.processes = response.data.processes || [];

            KPIModule.log(`Cargadas ${KPIModule.state.tasks.length} tareas`, 'success');

            // Render KPIs and charts
            KPIModule.renderKPIs();
            KPIModule.renderCharts();

            // Show/hide empty state
            const isEmpty = KPIModule.state.tasks.length === 0;
            document.getElementById('kpi-empty-state').style.display = isEmpty ? 'block' : 'none';
            document.getElementById('kpi-cards').style.display = isEmpty ? 'none' : 'flex';

        } else {
            throw new Error('No se pudieron cargar los datos');
        }

    } catch (error) {
        KPIModule.handleError(error, 'loadTasksData');
    } finally {
        KPIModule.hideLoading();
    }
};

/**
 * Load users for filter dropdown
 */
KPIModule.loadUsers = async function () {
    try {
        const response = await KPIModule.api('getUsers');

        if (response.success && response.data) {
            const select = document.getElementById('kpi-user');
            if (!select) return;

            // Clear existing options (except first)
            select.innerHTML = '<option value="">Todos los colaboradores</option>';

            response.data.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = user.nombre_completo || user.nombre;
                select.appendChild(option);
            });
        }
    } catch (error) {
        KPIModule.log('Error cargando usuarios', 'warning');
    }
};

// ========================================
// KPI CALCULATIONS (10 KPIs)
// ========================================

/**
 * 1. Productividad % = (Completadas / Asignadas) × 100
 */
KPIModule.calcProductividad = function () {
    const total = KPIModule.state.tasks.length;
    if (total === 0) return 0;

    const completadas = KPIModule.state.tasks.filter(t =>
        t.status === 'Completada' || t.estado === 'Completada'
    ).length;

    return (completadas / total) * 100;
};

/**
 * 2. Productividad Ponderada = Σ(pesos completadas) / Σ(pesos totales)
 */
KPIModule.calcProductividadPonderada = function () {
    const tasks = KPIModule.state.tasks;
    if (tasks.length === 0) return 0;

    let totalPeso = 0;
    let pesosCompletadas = 0;

    tasks.forEach(task => {
        const peso = parseFloat(task.peso || task.weight || 1);
        totalPeso += peso;

        if (task.status === 'Completada' || task.estado === 'Completada') {
            pesosCompletadas += peso;
        }
    });

    return totalPeso > 0 ? (pesosCompletadas / totalPeso) * 100 : 0;
};

/**
 * 3. Total Tareas Asignadas
 */
KPIModule.calcTotalTareas = function () {
    return KPIModule.state.tasks.length;
};

/**
 * 4. Tareas Completadas
 */
KPIModule.calcTareasCompletadas = function () {
    return KPIModule.state.tasks.filter(t =>
        t.status === 'Completada' || t.estado === 'Completada'
    ).length;
};

/**
 * 5. Tareas Vencidas
 */
KPIModule.calcTareasVencidas = function () {
    const now = new Date();
    return KPIModule.state.tasks.filter(t => {
        if (t.status === 'Completada' || t.estado === 'Completada') return false;

        const dueDate = new Date(t.due_date || t.fecha_entrega);
        return dueDate < now;
    }).length;
};

/**
 * 6. Tareas En Proceso
 */
KPIModule.calcTareasEnProceso = function () {
    return KPIModule.state.tasks.filter(t =>
        t.status === 'En proceso' || t.estado === 'En proceso'
    ).length;
};

/**
 * 7. Distribución por Prioridad
 */
KPIModule.calcDistribucionPrioridad = function () {
    const dist = { Normal: 0, Importante: 0, Urgente: 0 };

    KPIModule.state.tasks.forEach(task => {
        const priority = task.priority || task.prioridad || 'Normal';
        if (dist.hasOwnProperty(priority)) {
            dist[priority]++;
        }
    });

    return dist;
};

/**
 * 8. Duración Promedio (en días)
 */
KPIModule.calcDuracionPromedio = function () {
    const completadas = KPIModule.state.tasks.filter(t =>
        t.status === 'Completada' || t.estado === 'Completada'
    );

    if (completadas.length === 0) return 0;

    let totalDias = 0;
    let count = 0;

    completadas.forEach(task => {
        const start = new Date(task.start_date || task.fecha_inicio);
        const end = new Date(task.delivery_date || task.fecha_entrega);

        if (!isNaN(start.getTime()) && !isNaN(end.getTime())) {
            const dias = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
            totalDias += dias;
            count++;
        }
    });

    return count > 0 ? Math.round(totalDias / count) : 0;
};

/**
 * 9. Colaboradores Activos (con al menos 1 tarea)
 */
KPIModule.calcColaboradoresActivos = function () {
    const uniqueUsers = new Set();

    KPIModule.state.tasks.forEach(task => {
        const userId = task.assigned_to || task.responsable_id;
        if (userId) {
            uniqueUsers.add(userId);
        }
    });

    return uniqueUsers.size;
};

/**
 * 10. Procesos Activos
 */
KPIModule.calcProcesosActivos = function () {
    const uniqueProcesses = new Set();

    KPIModule.state.tasks.forEach(task => {
        const processId = task.process_id || task.proceso_id;
        if (processId) {
            uniqueProcesses.add(processId);
        }
    });

    return uniqueProcesses.size;
};

// ========================================
// UI RENDERING - KPIs
// ========================================

/**
 * Render all 10 KPI cards
 */
KPIModule.renderKPIs = function () {
    KPIModule.log('Renderizando KPIs...', 'info');

    const total = KPIModule.calcTotalTareas();
    const completadas = KPIModule.calcTareasCompletadas();
    const vencidas = KPIModule.calcTareasVencidas();
    const enProceso = KPIModule.calcTareasEnProceso();
    const productividad = KPIModule.calcProductividad();
    const ponderada = KPIModule.calcProductividadPonderada();
    const duracion = KPIModule.calcDuracionPromedio();
    const colaboradores = KPIModule.calcColaboradoresActivos();
    const procesos = KPIModule.calcProcesosActivos();
    const prioridad = KPIModule.calcDistribucionPrioridad();

    // 1. Productividad %
    document.getElementById('kpi-productividad').textContent = KPIModule.formatPercent(productividad);
    const prodBadge = document.getElementById('kpi-productividad-badge');
    prodBadge.className = 'badge ' + KPIModule.getBadgeClass(productividad);
    prodBadge.textContent = KPIModule.getBadgeText(productividad);
    document.getElementById('kpi-productividad-detail').textContent =
        `${completadas} de ${total} tareas`;

    // 2. Productividad Ponderada
    document.getElementById('kpi-ponderada').textContent = KPIModule.formatPercent(ponderada);
    const pondBadge = document.getElementById('kpi-ponderada-badge');
    pondBadge.className = 'badge ' + KPIModule.getBadgeClass(ponderada);
    pondBadge.textContent = KPIModule.getBadgeText(ponderada);

    // 3. Total Asignadas
    document.getElementById('kpi-total').textContent = KPIModule.formatNumber(total);

    // 4. Completadas
    const completadasPct = total > 0 ? (completadas / total * 100) : 0;
    document.getElementById('kpi-completadas').textContent = KPIModule.formatNumber(completadas);
    document.getElementById('kpi-completadas-pct').textContent = KPIModule.formatPercent(completadasPct);
    document.getElementById('kpi-completadas-detail').textContent =
        `${KPIModule.formatPercent(completadasPct)} del total`;

    // 5. Vencidas
    const vencidasPct = total > 0 ? (vencidas / total * 100) : 0;
    document.getElementById('kpi-vencidas').textContent = KPIModule.formatNumber(vencidas);
    document.getElementById('kpi-vencidas-pct').textContent = KPIModule.formatPercent(vencidasPct);
    document.getElementById('kpi-vencidas-detail').textContent =
        `${KPIModule.formatPercent(vencidasPct)} del total`;

    // 6. En Proceso
    const procesoPct = total > 0 ? (enProceso / total * 100) : 0;
    document.getElementById('kpi-proceso').textContent = KPIModule.formatNumber(enProceso);
    document.getElementById('kpi-proceso-pct').textContent = KPIModule.formatPercent(procesoPct);
    document.getElementById('kpi-proceso-detail').textContent =
        `${KPIModule.formatPercent(procesoPct)} del total`;

    // 7. Duración Promedio
    document.getElementById('kpi-duracion').textContent = `${duracion} días`;

    // 8. Colaboradores Activos
    document.getElementById('kpi-colaboradores').textContent = KPIModule.formatNumber(colaboradores);

    // 9. Procesos Activos
    document.getElementById('kpi-procesos').textContent = KPIModule.formatNumber(procesos);

    // 10. Distribución por Prioridad
    document.getElementById('kpi-pri-normal').textContent = KPIModule.formatNumber(prioridad.Normal);
    document.getElementById('kpi-pri-importante').textContent = KPIModule.formatNumber(prioridad.Importante);
    document.getElementById('kpi-pri-urgente').textContent = KPIModule.formatNumber(prioridad.Urgente);

    KPIModule.log('KPIs renderizados exitosamente', 'success');
};

// ========================================
// CHART RENDERING (5 charts)
// ========================================

/**
 * Render all charts
 */
KPIModule.renderCharts = function () {
    KPIModule.log('Renderizando gráficas...', 'info');

    KPIModule.createUsersChart();
    KPIModule.createFlowsChart();
    KPIModule.createStatusChart();
    KPIModule.createPriorityChart();
    KPIModule.createTrendChart();

    KPIModule.log('Gráficas renderizadas exitosamente', 'success');
};

/**
 * 1. Cumplimiento por Usuario (Bar Chart)
 */
KPIModule.createUsersChart = function () {
    const ctx = document.getElementById('kpi-chart-users');
    if (!ctx) return;

    // Destroy existing chart
    if (KPIModule.state.charts.users) {
        KPIModule.state.charts.users.destroy();
    }

    // Group tasks by user
    const userStats = {};

    KPIModule.state.tasks.forEach(task => {
        const userId = task.assigned_to || task.responsable_id;
        const userName = task.assigned_name || task.responsable_nombre || `Usuario ${userId}`;

        if (!userId) return;

        if (!userStats[userId]) {
            userStats[userId] = { name: userName, total: 0, completadas: 0 };
        }

        userStats[userId].total++;
        if (task.status === 'Completada' || task.estado === 'Completada') {
            userStats[userId].completadas++;
        }
    });

    // Convert to arrays
    const users = Object.values(userStats);
    const labels = users.map(u => u.name);
    const percentages = users.map(u => u.total > 0 ? (u.completadas / u.total * 100) : 0);

    // Update badge
    document.getElementById('users-chart-count').textContent = `${users.length} usuarios`;

    // Create chart
    KPIModule.state.charts.users = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Cumplimiento %',
                data: percentages,
                backgroundColor: '#3b82f6',
                borderColor: '#2563eb',
                borderWidth: 1,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return `${Math.round(context.parsed.y)}% de cumplimiento`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function (value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
};

/**
 * 2. Procesos Activos por Flujo (Bar Chart)
 */
KPIModule.createFlowsChart = function () {
    const ctx = document.getElementById('kpi-chart-flows');
    if (!ctx) return;

    // Destroy existing chart
    if (KPIModule.state.charts.flows) {
        KPIModule.state.charts.flows.destroy();
    }

    // Group processes by flow
    const flowStats = {};

    KPIModule.state.tasks.forEach(task => {
        const flowName = task.flow_name || task.flujo_nombre || 'Sin flujo';

        if (!flowStats[flowName]) {
            flowStats[flowName] = 0;
        }

        flowStats[flowName]++;
    });

    // Convert to arrays
    const labels = Object.keys(flowStats);
    const data = Object.values(flowStats);

    // Update badge
    document.getElementById('flows-chart-count').textContent = `${labels.length} flujos`;

    // Create chart
    KPIModule.state.charts.flows = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Tareas Activas',
                data: data,
                backgroundColor: '#10b981',
                borderColor: '#059669',
                borderWidth: 1,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
};

/**
 * 3. Distribución de Estados (Pie Chart)
 */
KPIModule.createStatusChart = function () {
    const ctx = document.getElementById('kpi-chart-status');
    if (!ctx) return;

    // Destroy existing chart
    if (KPIModule.state.charts.status) {
        KPIModule.state.charts.status.destroy();
    }

    // Count by status
    const statusCount = {};

    KPIModule.state.tasks.forEach(task => {
        const status = task.status || task.estado || 'Sin estado';
        statusCount[status] = (statusCount[status] || 0) + 1;
    });

    const labels = Object.keys(statusCount);
    const data = Object.values(statusCount);
    const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];

    // Create chart
    KPIModule.state.charts.status = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors.slice(0, labels.length),
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
};

/**
 * 4. Prioridad de Tareas (Pie Chart)
 */
KPIModule.createPriorityChart = function () {
    const ctx = document.getElementById('kpi-chart-priority');
    if (!ctx) return;

    // Destroy existing chart
    if (KPIModule.state.charts.priority) {
        KPIModule.state.charts.priority.destroy();
    }

    const dist = KPIModule.calcDistribucionPrioridad();

    // Create chart
    KPIModule.state.charts.priority = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Normal', 'Importante', 'Urgente'],
            datasets: [{
                data: [dist.Normal, dist.Importante, dist.Urgente],
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
};

/**
 * 5. Tendencia Semanal/Mensual (Line Chart)
 */
KPIModule.createTrendChart = function () {
    const ctx = document.getElementById('kpi-chart-trend');
    if (!ctx) return;

    // Destroy existing chart
    if (KPIModule.state.charts.trend) {
        KPIModule.state.charts.trend.destroy();
    }

    // Group tasks by week
    const weekStats = {};

    KPIModule.state.tasks.forEach(task => {
        const date = new Date(task.created_at || task.fecha_creacion);
        if (isNaN(date.getTime())) return;

        // Get week start (Monday)
        const weekStart = new Date(date);
        weekStart.setDate(date.getDate() - date.getDay() + 1);
        const weekKey = weekStart.toISOString().split('T')[0];

        if (!weekStats[weekKey]) {
            weekStats[weekKey] = { total: 0, completadas: 0 };
        }

        weekStats[weekKey].total++;
        if (task.status === 'Completada' || task.estado === 'Completada') {
            weekStats[weekKey].completadas++;
        }
    });

    // Convert to arrays and sort by date
    const weeks = Object.keys(weekStats).sort();
    const totals = weeks.map(w => weekStats[w].total);
    const completadas = weeks.map(w => weekStats[w].completadas);

    // Format labels
    const labels = weeks.map(w => {
        const date = new Date(w);
        return `${date.getDate()}/${date.getMonth() + 1}`;
    });

    // Create chart
    KPIModule.state.charts.trend = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Asignadas',
                    data: totals,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Completadas',
                    data: completadas,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
};

// ========================================
// FILTERS
// ========================================

/**
 * Apply filters and reload data
 */
KPIModule.applyFilters = function () {
    KPIModule.log('Aplicando filtros...', 'info');

    // Get filter values
    const period = document.getElementById('kpi-time-filter').value;
    const user = document.getElementById('kpi-user').value;
    const search = document.getElementById('kpi-search').value;

    // Calculate date range
    const now = new Date();
    let dateFrom = null;
    let dateTo = null;

    switch (period) {
        case 'today':
            dateFrom = new Date(now.setHours(0, 0, 0, 0));
            dateTo = new Date(now.setHours(23, 59, 59, 999));
            break;
        case 'week':
            dateFrom = new Date(now);
            dateFrom.setDate(now.getDate() - 7);
            dateTo = new Date();
            break;
        case 'month':
            dateFrom = new Date(now.getFullYear(), now.getMonth(), 1);
            dateTo = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            break;
        case 'custom':
            const customFrom = document.getElementById('kpi-date-from').value;
            const customTo = document.getElementById('kpi-date-to').value;
            if (customFrom) dateFrom = new Date(customFrom);
            if (customTo) dateTo = new Date(customTo);
            break;
        case 'all':
        default:
            dateFrom = null;
            dateTo = null;
    }

    // Update state
    KPIModule.state.filters = {
        period,
        dateFrom: dateFrom ? dateFrom.toISOString().split('T')[0] : null,
        dateTo: dateTo ? dateTo.toISOString().split('T')[0] : null,
        user,
        search
    };

    // Reload data
    KPIModule.loadTasksData();
};

/**
 * Clear all filters
 */
KPIModule.clearFilters = function () {
    document.getElementById('kpi-time-filter').value = 'month';
    document.getElementById('kpi-user').value = '';
    document.getElementById('kpi-search').value = '';
    document.getElementById('kpi-custom-range').style.display = 'none';

    KPIModule.applyFilters();
};

/**
 * Toggle custom date range
 */
KPIModule.toggleCustomRange = function () {
    const period = document.getElementById('kpi-time-filter').value;
    const customRange = document.getElementById('kpi-custom-range');

    if (customRange) {
        customRange.style.display = period === 'custom' ? 'flex' : 'none';
    }
};

// ========================================
// EVENT LISTENERS
// ========================================

/**
 * Setup all event listeners
 */
KPIModule.setupEvents = function () {
    KPIModule.log('Configurando eventos...', 'info');

    // Period filter
    const periodFilter = document.getElementById('kpi-time-filter');
    if (periodFilter) {
        periodFilter.addEventListener('change', function () {
            KPIModule.toggleCustomRange();
            if (this.value !== 'custom') {
                KPIModule.applyFilters();
            }
        });
    }

    // Custom date inputs
    const dateFrom = document.getElementById('kpi-date-from');
    const dateTo = document.getElementById('kpi-date-to');
    if (dateFrom) {
        dateFrom.addEventListener('change', () => KPIModule.applyFilters());
    }
    if (dateTo) {
        dateTo.addEventListener('change', () => KPIModule.applyFilters());
    }

    // User filter
    const userFilter = document.getElementById('kpi-user');
    if (userFilter) {
        userFilter.addEventListener('change', () => KPIModule.applyFilters());
    }

    // Search input (debounced)
    const searchInput = document.getElementById('kpi-search');
    if (searchInput) {
        let timeout;
        searchInput.addEventListener('input', function () {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                KPIModule.applyFilters();
            }, 500);
        });
    }

    // Refresh button
    const refreshBtn = document.getElementById('kpi-refresh');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => KPIModule.loadTasksData());
    }

    // Export button
    const exportBtn = document.getElementById('kpi-export');
    if (exportBtn) {
        exportBtn.addEventListener('click', () => {
            KPIModule.toast('Exportando KPIs...', 'info');
            // TODO: Implement export functionality
        });
    }

    KPIModule.log('Eventos configurados', 'success');
};

// ========================================
// INITIALIZATION
// ========================================

/**
 * Initialize KPI Module
 */
KPIModule.init = function () {
    KPIModule.log('=== INICIALIZANDO KPI MODULE v4.0 ===', 'info');

    // Setup events
    KPIModule.setupEvents();

    // Load users for filter
    KPIModule.loadUsers();

    // Set default period to current month
    const periodFilter = document.getElementById('kpi-time-filter');
    if (periodFilter && !periodFilter.value) {
        periodFilter.value = 'month';
    }

    // Load initial data
    KPIModule.applyFilters();

    // Setup reveal animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.reveal').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });

    KPIModule.log('=== KPI MODULE INICIALIZADO EXITOSAMENTE ===', 'success');
};

// ========================================
// AUTO-INIT
// ========================================

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('kpi-cards')) {
        KPIModule.init();
    }
});
