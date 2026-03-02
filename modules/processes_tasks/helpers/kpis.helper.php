<?php
/**
 * Helper de KPIs - Módulo Tareas
 * Cálculo de métricas y estadísticas del módulo
 * 
 * @author Nahum Peña / Proyecto Índice 2025
 */

class KPIsHelper {
    
    /**
     * Obtener total de tareas
     */
    public static function getTasksCount($filters = []) {
        require_once __DIR__ . '/../models/tasks.model.php';
        $tasks = TasksModel::getTasks($filters);
        return count($tasks);
    }
    
    /**
     * Obtener tareas completadas
     */
    public static function getCompletedTasksCount($filters = []) {
        $filters['status'] = 'completed';
        return self::getTasksCount($filters);
    }
    
    /**
     * Obtener tareas pendientes
     */
    public static function getPendingTasksCount($filters = []) {
        $filters['status'] = 'pending';
        return self::getTasksCount($filters);
    }
    
    /**
     * Obtener tareas en progreso
     */
    public static function getInProgressTasksCount($filters = []) {
        $filters['status'] = 'in_progress';
        return self::getTasksCount($filters);
    }
    
    /**
     * Obtener tareas vencidas
     */
    public static function getOverdueTasksCount($filters = []) {
        require_once __DIR__ . '/../models/tasks.model.php';
        $tasks = TasksModel::getTasks($filters);
        $today = date('Y-m-d');
        
        $overdue = array_filter($tasks, function($task) use ($today) {
            return $task['due_date'] && 
                   $task['due_date'] < $today && 
                   $task['status'] !== 'completed';
        });
        
        return count($overdue);
    }
    
    /**
     * Obtener total de procesos
     */
    public static function getProcessesCount($filters = []) {
        require_once __DIR__ . '/../models/processes.model.php';
        $processes = ProcessesModel::getProcesses($filters);
        return count($processes);
    }
    
    /**
     * Obtener procesos activos
     */
    public static function getActiveProcessesCount() {
        return self::getProcessesCount(['active' => 1]);
    }
    
    /**
     * Obtener total de proyectos
     */
    public static function getProjectsCount($filters = []) {
        require_once __DIR__ . '/../models/projects.model.php';
        $projects = ProjectsModel::getProjects($filters);
        return count($projects);
    }
    
    /**
     * Obtener proyectos activos
     */
    public static function getActiveProjectsCount() {
        return self::getProjectsCount(['status' => 'active']);
    }
    
    /**
     * Obtener carga de trabajo por usuario
     */
    public static function getLoadByUser($userId = null) {
        require_once __DIR__ . '/../models/tasks.model.php';
        
        if ($userId) {
            $tasks = TasksModel::getTasks(['delegated_to' => $userId]);
        } else {
            $tasks = TasksModel::getTasks();
        }
        
        $load = [];
        
        foreach ($tasks as $task) {
            $user = $task['delegated_to'] ?? 'Sin asignar';
            
            if (!isset($load[$user])) {
                $load[$user] = [
                    'user_id' => $user,
                    'total' => 0,
                    'pending' => 0,
                    'in_progress' => 0,
                    'completed' => 0,
                    'overdue' => 0
                ];
            }
            
            $load[$user]['total']++;
            
            if ($task['status'] === 'completed') {
                $load[$user]['completed']++;
            } elseif ($task['status'] === 'in_progress') {
                $load[$user]['in_progress']++;
            } elseif ($task['status'] === 'pending') {
                $load[$user]['pending']++;
            }
            
            // Verificar si está vencida
            if ($task['due_date'] && $task['due_date'] < date('Y-m-d') && $task['status'] !== 'completed') {
                $load[$user]['overdue']++;
            }
        }
        
        return $userId ? ($load[$userId] ?? null) : $load;
    }
    
    /**
     * Obtener carga de trabajo por unidad de negocio
     */
    public static function getLoadByUnit($unit = null) {
        require_once __DIR__ . '/../models/tasks.model.php';
        
        if ($unit) {
            $tasks = TasksModel::getTasks(['unit' => $unit]);
        } else {
            $tasks = TasksModel::getTasks();
        }
        
        $load = [];
        
        foreach ($tasks as $task) {
            $taskUnit = $task['unit'] ?? 'Sin unidad';
            
            if (!isset($load[$taskUnit])) {
                $load[$taskUnit] = [
                    'unit' => $taskUnit,
                    'total' => 0,
                    'pending' => 0,
                    'in_progress' => 0,
                    'completed' => 0,
                    'overdue' => 0
                ];
            }
            
            $load[$taskUnit]['total']++;
            $load[$taskUnit][$task['status']]++;
            
            // Verificar si está vencida
            if ($task['due_date'] && $task['due_date'] < date('Y-m-d') && $task['status'] !== 'completed') {
                $load[$taskUnit]['overdue']++;
            }
        }
        
        return $unit ? ($load[$unit] ?? null) : $load;
    }
    
    /**
     * Calcular tasa de productividad (% de tareas completadas)
     */
    public static function getProductivityRate($filters = []) {
        $total = self::getTasksCount($filters);
        
        if ($total === 0) {
            return 0;
        }
        
        $completed = self::getCompletedTasksCount($filters);
        
        return round(($completed / $total) * 100, 2);
    }
    
    /**
     * Obtener rendimiento semanal
     */
    public static function getWeeklyPerformance() {
        require_once __DIR__ . '/../models/tasks.model.php';
        
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
        
        $tasks = TasksModel::getTasks();
        $weekTasks = array_filter($tasks, function($task) use ($startOfWeek, $endOfWeek) {
            $taskDate = $task['start_date'];
            return $taskDate >= $startOfWeek && $taskDate <= $endOfWeek;
        });
        
        $performance = [
            'week_start' => $startOfWeek,
            'week_end' => $endOfWeek,
            'total' => count($weekTasks),
            'completed' => 0,
            'in_progress' => 0,
            'pending' => 0,
            'overdue' => 0,
            'completion_rate' => 0
        ];
        
        foreach ($weekTasks as $task) {
            $performance[$task['status']]++;
        }
        
        if ($performance['total'] > 0) {
            $performance['completion_rate'] = round(($performance['completed'] / $performance['total']) * 100, 2);
        }
        
        return $performance;
    }
    
    /**
     * Obtener rendimiento mensual
     */
    public static function getMonthlyPerformance() {
        require_once __DIR__ . '/../models/tasks.model.php';
        
        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-t');
        
        $tasks = TasksModel::getTasks();
        $monthTasks = array_filter($tasks, function($task) use ($startOfMonth, $endOfMonth) {
            $taskDate = $task['start_date'];
            return $taskDate >= $startOfMonth && $taskDate <= $endOfMonth;
        });
        
        $performance = [
            'month' => date('F Y'),
            'month_start' => $startOfMonth,
            'month_end' => $endOfMonth,
            'total' => count($monthTasks),
            'completed' => 0,
            'in_progress' => 0,
            'pending' => 0,
            'overdue' => 0,
            'completion_rate' => 0
        ];
        
        foreach ($monthTasks as $task) {
            $performance[$task['status']]++;
        }
        
        if ($performance['total'] > 0) {
            $performance['completion_rate'] = round(($performance['completed'] / $performance['total']) * 100, 2);
        }
        
        return $performance;
    }
    
    /**
     * Obtener promedio de tiempo de completación
     */
    public static function getAverageCompletionTime($filters = []) {
        require_once __DIR__ . '/../models/tasks.model.php';
        $filters['status'] = 'completed';
        $tasks = TasksModel::getTasks($filters);
        
        if (empty($tasks)) {
            return 0;
        }
        
        $totalDays = 0;
        $count = 0;
        
        foreach ($tasks as $task) {
            if (isset($task['completed_at']) && $task['start_date']) {
                $start = strtotime($task['start_date']);
                $end = strtotime($task['completed_at']);
                $days = ($end - $start) / (60 * 60 * 24);
                $totalDays += $days;
                $count++;
            }
        }
        
        return $count > 0 ? round($totalDays / $count, 1) : 0;
    }
    
    /**
     * Obtener distribución de tareas por prioridad
     */
    public static function getTasksByPriority() {
        require_once __DIR__ . '/../models/tasks.model.php';
        $tasks = TasksModel::getTasks();
        
        $distribution = [
            'low' => 0,
            'medium' => 0,
            'high' => 0,
            'critical' => 0
        ];
        
        foreach ($tasks as $task) {
            $priority = strtolower($task['priority'] ?? 'medium');
            if (isset($distribution[$priority])) {
                $distribution[$priority]++;
            }
        }
        
        return $distribution;
    }
    
    /**
     * Obtener KPIs generales del dashboard
     */
    public static function getDashboardKPIs($filters = []) {
        return [
            'total_tasks' => self::getTasksCount($filters),
            'pending' => self::getPendingTasksCount($filters),
            'in_progress' => self::getInProgressTasksCount($filters),
            'completed' => self::getCompletedTasksCount($filters),
            'overdue' => self::getOverdueTasksCount($filters),
            'total_projects' => self::getProjectsCount(),
            'active_projects' => self::getActiveProjectsCount(),
            'total_processes' => self::getProcessesCount(),
            'active_processes' => self::getActiveProcessesCount(),
            'productivity_rate' => self::getProductivityRate($filters),
            'avg_completion_time' => self::getAverageCompletionTime($filters)
        ];
    }
}
