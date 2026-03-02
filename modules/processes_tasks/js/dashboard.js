// /modules/processes_tasks/js/dashboard.js
// Dashboard analytics: KPIs, charts, and insights from localStorage data
console.log('[Processes & Tasks] DASHBOARD JS loaded');

(function () {
    let currentFlows = [];
    let currentProcesses = [];
    let currentTasks = [];
    let charts = {};

    // DOM selectors
    const refreshBtn = document.getElementById('dashboard-refresh');
    const timeRangeSelect = document.getElementById('dashboard-timerange');

    function esc(s) {
        return String(s || '').replace(/[&<>"]/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", "\"": "&quot;" }[c]));
    }

    // NUEVA FUNCIÓN: Cargar datos desde API real (no localStorage)
    async function loadData() {
        try {
            console.log('[Dashboard] Cargando datos desde API...');

            // Cargar estadísticas específicas para KPIs
            const statsResponse = await fetch('/modules/processes_tasks/api/stats.php?tipo=Tarea', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (statsResponse.ok) {
                const statsData = await statsResponse.json();
                if (statsData.ok) {
                    console.log('[Dashboard] Stats recibidas:', statsData.stats);
                    updateStatsFromAPI(statsData.stats);
                }
            }

            // Cargar datos del dashboard desde /api/dashboard.php
            const response = await fetch('/modules/processes_tasks/api/dashboard.php', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (!data.ok) {
                console.error('[Dashboard] API error:', data.error || 'Unknown error');
                // Fallback a localStorage solo si el API falla
                loadDataFromLocalStorage();
                return;
            }

            // Procesar datos del API
            currentTasks = data.tasks || [];

            // Mapear status de DB a frontend
            currentTasks = currentTasks.map(task => ({
                ...task,
                // Mapear status de DB ('En tiempo', 'En proceso', etc.) a frontend ('pending', 'in-progress', etc.)
                status: mapStatusToFrontend(task.status),
                priority: mapPriorityToFrontend(task.nivel || task.priority),
                name: task.title || task.titulo,
                assignedToName: task.delegado_nombre || task.assignee_name,
                creatorName: task.creador_nombre || task.creator_name,
                date: task.due_date || task.fecha_entrega,
                createdAt: task.created_at
            }));

            // Por ahora, flows y processes desde localStorage (o vacío)
            currentFlows = JSON.parse(localStorage.getItem('indice_flows_v1') || '[]');
            currentProcesses = JSON.parse(localStorage.getItem('indice_processes_v1') || '[]');

            console.log('[Dashboard] Datos cargados desde API:', {
                tasks: currentTasks.length,
                flows: currentFlows.length,
                processes: currentProcesses.length,
                stats: data.stats
            });

        } catch (error) {
            console.error('[Dashboard] Error cargando datos desde API:', error);
            // Fallback a localStorage
            loadDataFromLocalStorage();
        }
    }

    // Fallback: cargar desde localStorage si el API falla
    function loadDataFromLocalStorage() {
        console.warn('[Dashboard] Usando localStorage como fallback');
        try {
            currentFlows = JSON.parse(localStorage.getItem('indice_flows_v1') || '[]');
            currentProcesses = JSON.parse(localStorage.getItem('indice_processes_v1') || '[]');
            currentTasks = JSON.parse(localStorage.getItem('indice_tasks_v1') || '[]');
        } catch (error) {
            console.error('[Dashboard] Error en localStorage:', error);
            currentFlows = [];
            currentProcesses = [];
            currentTasks = [];
        }
    }

    // Mapear status de DB a valores de frontend
    function mapStatusToFrontend(dbStatus) {
        const map = {
            'En tiempo': 'pending',
            'En proceso': 'in-progress',
            'Terminada': 'completed',
            'Vencida': 'overdue',
            'Auditada': 'completed',
            'Pausada': 'paused'
        };
        return map[dbStatus] || 'pending';
    }

    // Mapear prioridad de DB a valores de frontend
    function mapPriorityToFrontend(dbPriority) {
        const map = {
            'Normal': 'low',
            'Importante': 'high',
            'Urgente': 'critical'
        };
        return map[dbPriority] || 'medium';
    }

    // Actualizar stats directamente desde el API
    function updateStatsFromAPI(stats) {
        const statMap = {
            'stat-pending': stats.pending || 0,
            'stat-overdue': stats.overdue || 0,
            'stat-completed': stats.completed || 0,
            'stat-total': stats.total || 0
        };

        Object.keys(statMap).forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                // Limpiar el spinner y mostrar el valor
                el.innerHTML = statMap[id];
                console.log(`[Dashboard] Updated ${id}: ${statMap[id]}`);
            } else {
                console.warn(`[Dashboard] Element with ID '${id}' not found`);
            }
        });

        // Completion percentage si existe
        const completionRate = stats.completion_percentage || 0;
        const rateEl = document.getElementById('stat-completion-rate');
        if (rateEl) rateEl.textContent = completionRate + '%';

        const progressBar = document.getElementById('completion-progress');
        if (progressBar) {
            progressBar.style.width = completionRate + '%';
            progressBar.setAttribute('aria-valuenow', completionRate);
        }

        console.log('[Dashboard] Stats updated from API:', stats);
    }

    function updateOverviewStats() {
        // Total counts
        document.getElementById('stat-total-flows').textContent = currentFlows.length;
        document.getElementById('stat-total-processes').textContent = currentProcesses.length;
        document.getElementById('stat-total-tasks').textContent = currentTasks.length;

        // Task status breakdown
        const pendingTasks = currentTasks.filter(t => t.status === 'pending').length;
        const inProgressTasks = currentTasks.filter(t => t.status === 'in-progress').length;
        const completedTasks = currentTasks.filter(t => t.status === 'completed').length;
        const overdueTasks = currentTasks.filter(t => isOverdue(t)).length;

        document.getElementById('stat-pending').textContent = pendingTasks;
        document.getElementById('stat-in-progress').textContent = inProgressTasks;
        document.getElementById('stat-completed').textContent = completedTasks;
        document.getElementById('stat-overdue').textContent = overdueTasks;

        // Process status breakdown  
        const activeProcesses = currentProcesses.filter(p => p.status === 'active').length;
        const draftProcesses = currentProcesses.filter(p => p.status === 'draft').length;
        const completedProcesses = currentProcesses.filter(p => p.status === 'completed').length;

        // Los KPIs globales ya son actualizados por updateStatsFromAPI()
        // No necesitamos actualizar elementos específicos de procesos aquí

        // Completion rate
        const completionRate = currentTasks.length > 0 ? Math.round((completedTasks / currentTasks.length) * 100) : 0;
        document.getElementById('stat-completion-rate').textContent = completionRate + '%';

        // Update progress bar
        const progressBar = document.getElementById('completion-progress');
        if (progressBar) {
            progressBar.style.width = completionRate + '%';
            progressBar.setAttribute('aria-valuenow', completionRate);
        }
    }

    function isOverdue(task) {
        if (task.status === 'completed' || task.status === 'cancelled') return false;
        const today = new Date();
        const taskDate = new Date(task.date);
        return taskDate < today;
    }

    function updatePriorityDistribution() {
        const priorities = ['low', 'medium', 'high', 'critical'];
        const priorityCount = {};

        priorities.forEach(p => priorityCount[p] = 0);
        currentTasks.forEach(task => {
            if (task.priority && priorityCount.hasOwnProperty(task.priority)) {
                priorityCount[task.priority]++;
            }
        });

        // Update DOM elements
        document.getElementById('priority-low').textContent = priorityCount.low;
        document.getElementById('priority-medium').textContent = priorityCount.medium;
        document.getElementById('priority-high').textContent = priorityCount.high;
        document.getElementById('priority-critical').textContent = priorityCount.critical;

        // Create simple progress bars
        const total = currentTasks.length || 1;
        ['low', 'medium', 'high', 'critical'].forEach(priority => {
            const percentage = Math.round((priorityCount[priority] / total) * 100);
            const progressBar = document.getElementById(`priority-${priority}-bar`);
            if (progressBar) {
                progressBar.style.width = percentage + '%';
            }
        });
    }

    function updateRecurrenceDistribution() {
        const recurrences = ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'on-demand'];
        const recurrenceCount = {};

        recurrences.forEach(r => recurrenceCount[r] = 0);
        currentTasks.forEach(task => {
            if (task.recurrence && recurrenceCount.hasOwnProperty(task.recurrence)) {
                recurrenceCount[task.recurrence]++;
            }
        });

        // Update DOM elements
        document.getElementById('recurrence-daily').textContent = recurrenceCount.daily;
        document.getElementById('recurrence-weekly').textContent = recurrenceCount.weekly;
        document.getElementById('recurrence-monthly').textContent = recurrenceCount.monthly;
        document.getElementById('recurrence-quarterly').textContent = recurrenceCount.quarterly;
        document.getElementById('recurrence-yearly').textContent = recurrenceCount.yearly;
        document.getElementById('recurrence-ondemand').textContent = recurrenceCount['on-demand'];
    }

    function updateRecentActivity() {
        // Get recent tasks (last 5)
        const recentTasks = [...currentTasks]
            .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
            .slice(0, 5);

        const recentActivityEl = document.getElementById('recent-activity');
        if (!recentActivityEl) return;

        if (recentTasks.length === 0) {
            recentActivityEl.innerHTML = `
        <div class="text-center text-muted py-3">
          <i class="bi bi-inbox fs-1 mb-2"></i>
          <div>No hay actividad reciente</div>
        </div>
      `;
            return;
        }

        const activityHtml = recentTasks.map(task => {
            const statusIcon = {
                'pending': 'bi-clock text-warning',
                'in-progress': 'bi-play-circle text-primary',
                'completed': 'bi-check-circle text-success',
                'overdue': 'bi-exclamation-triangle text-danger'
            }[task.status] || 'bi-circle text-muted';

            const timeAgo = getTimeAgo(task.createdAt);

            return `
        <div class="d-flex align-items-start gap-3 py-2 border-bottom">
          <i class="bi ${statusIcon} fs-5 mt-1"></i>
          <div class="flex-grow-1">
            <div class="fw-semibold small">${esc(task.name)}</div>
            <div class="text-muted small">
              Asignada a: ${esc(task.assignedToName || 'Sin asignar')}
            </div>
            <div class="text-muted small">${timeAgo}</div>
          </div>
        </div>
      `;
        }).join('');

        recentActivityEl.innerHTML = activityHtml;
    }

    function getTimeAgo(dateString) {
        try {
            const date = new Date(dateString);
            const now = new Date();
            const diffMinutes = Math.floor((now - date) / (1000 * 60));

            if (diffMinutes < 1) return 'Ahora mismo';
            if (diffMinutes < 60) return `Hace ${diffMinutes} min`;

            const diffHours = Math.floor(diffMinutes / 60);
            if (diffHours < 24) return `Hace ${diffHours}h`;

            const diffDays = Math.floor(diffHours / 24);
            if (diffDays < 7) return `Hace ${diffDays} días`;

            return date.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: 'short'
            });
        } catch (e) {
            return dateString;
        }
    }

    function updateUpcomingTasks() {
        // Get tasks due in the next 7 days
        const today = new Date();
        const nextWeek = new Date(today);
        nextWeek.setDate(today.getDate() + 7);

        const upcomingTasks = currentTasks
            .filter(task => {
                if (task.status === 'completed' || task.status === 'cancelled') return false;
                const taskDate = new Date(task.date);
                return taskDate >= today && taskDate <= nextWeek;
            })
            .sort((a, b) => new Date(a.date) - new Date(b.date))
            .slice(0, 5);

        const upcomingEl = document.getElementById('upcoming-tasks');
        if (!upcomingEl) return;

        if (upcomingTasks.length === 0) {
            upcomingEl.innerHTML = `
        <div class="text-center text-muted py-3">
          <i class="bi bi-calendar-check fs-1 mb-2"></i>
          <div>No hay tareas próximas</div>
        </div>
      `;
            return;
        }

        const upcomingHtml = upcomingTasks.map(task => {
            const taskDate = new Date(task.date);
            const daysUntil = Math.ceil((taskDate - today) / (1000 * 60 * 60 * 24));

            let dateLabel = '';
            if (daysUntil === 0) dateLabel = 'Hoy';
            else if (daysUntil === 1) dateLabel = 'Mañana';
            else dateLabel = `En ${daysUntil} días`;

            const priorityClass = {
                'critical': 'text-danger',
                'high': 'text-warning',
                'medium': 'text-info',
                'low': 'text-success'
            }[task.priority] || 'text-muted';

            return `
        <div class="d-flex align-items-start gap-3 py-2 border-bottom">
          <i class="bi bi-calendar ${priorityClass} fs-5 mt-1"></i>
          <div class="flex-grow-1">
            <div class="fw-semibold small">${esc(task.name)}</div>
            <div class="text-muted small">
              ${esc(task.assignedToName || 'Sin asignar')}
            </div>
            <div class="small ${priorityClass}">${dateLabel}</div>
          </div>
        </div>
      `;
        }).join('');

        upcomingEl.innerHTML = upcomingHtml;
    }

    function updateWorkflowInsights() {
        // Calculate average tasks per process
        const tasksPerProcess = currentProcesses.length > 0 ?
            (currentTasks.length / currentProcesses.length).toFixed(1) : '0';

        document.getElementById('avg-tasks-per-process').textContent = tasksPerProcess;

        // Most active assignee
        const assigneeTaskCount = {};
        currentTasks.forEach(task => {
            if (task.assignedToName) {
                assigneeTaskCount[task.assignedToName] = (assigneeTaskCount[task.assignedToName] || 0) + 1;
            }
        });

        const mostActiveAssignee = Object.keys(assigneeTaskCount).length > 0 ?
            Object.keys(assigneeTaskCount).reduce((a, b) =>
                assigneeTaskCount[a] > assigneeTaskCount[b] ? a : b
            ) : 'N/A';

        document.getElementById('most-active-assignee').textContent = mostActiveAssignee;

        // Process efficiency (percentage of active processes)
        const processEfficiency = currentProcesses.length > 0 ?
            Math.round((currentProcesses.filter(p => p.status === 'active').length / currentProcesses.length) * 100) : 0;

        document.getElementById('process-efficiency').textContent = processEfficiency + '%';
    }

    function showEmptyState() {
        const dashboardContent = document.getElementById('dashboard-content');
        const emptyState = document.getElementById('dashboard-empty');

        if (currentFlows.length === 0 && currentProcesses.length === 0 && currentTasks.length === 0) {
            if (dashboardContent) dashboardContent.style.display = 'none';
            if (emptyState) emptyState.style.display = 'block';
        } else {
            if (dashboardContent) dashboardContent.style.display = 'block';
            if (emptyState) emptyState.style.display = 'none';
        }
    }

    // ACTUALIZADO: Función async para cargar y actualizar stats
    async function updateAllStats() {
        // Mostrar loading indicator
        const loadingIndicator = document.getElementById('dashboard-loading');
        if (loadingIndicator) loadingIndicator.style.display = 'block';

        // Cargar datos desde API (async)
        await loadData();

        // Ocultar loading
        if (loadingIndicator) loadingIndicator.style.display = 'none';

        // Actualizar todas las secciones
        showEmptyState();
        updateOverviewStats();
        updatePriorityDistribution();
        updateRecurrenceDistribution();
        updateRecentActivity();
        updateUpcomingTasks();
        updateWorkflowInsights();
    }

    async function init() {
        console.log('[Dashboard] Inicializando con datos desde API...');

        // Cargar datos inicial
        await updateAllStats();

        // Event listeners
        if (refreshBtn) {
            refreshBtn.addEventListener('click', async () => {
                console.log('[Dashboard] Recargando datos...');
                await updateAllStats();
                window.showToast && window.showToast('Dashboard actualizado', 'success');
            });
        }

        if (timeRangeSelect) {
            timeRangeSelect.addEventListener('change', async () => {
                // TODO: Implement time range filtering en el API
                console.log('[Dashboard] Cambiando rango de tiempo:', timeRangeSelect.value);
                await updateAllStats();
            });
        }

        console.log('[Dashboard] Inicializado correctamente con datos de API');
    }

    // Auto-refresh every 60 seconds (incrementado para no sobrecargar)
    setInterval(() => {
        console.log('[Dashboard] Auto-refresh...');
        updateAllStats();
    }, 60000);

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
