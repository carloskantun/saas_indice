<?php
/**
 * Modelo de Procesos - Módulo Tareas
 * Sistema de gestión de procesos recurrentes
 * 
 * @author Nahum Peña / Proyecto Índice 2025
 */

class ProcessesModel {
    
    /**
     * Obtener todos los procesos con filtros opcionales
     */
    public static function getProcesses($filters = []) {
        $allProcesses = self::getAllProcesses();
        
        // Aplicar filtros
        if (isset($filters['unit']) && $filters['unit'] !== '') {
            $allProcesses = array_filter($allProcesses, function($process) use ($filters) {
                return $process['unit'] === $filters['unit'];
            });
        }
        
        if (isset($filters['business']) && $filters['business'] !== '') {
            $allProcesses = array_filter($allProcesses, function($process) use ($filters) {
                return $process['business'] === $filters['business'];
            });
        }
        
        if (isset($filters['active']) !== null) {
            $allProcesses = array_filter($allProcesses, function($process) use ($filters) {
                return $process['active'] == $filters['active'];
            });
        }
        
        if (isset($filters['frequency']) && $filters['frequency'] !== '') {
            $allProcesses = array_filter($allProcesses, function($process) use ($filters) {
                return $process['frequency'] === $filters['frequency'];
            });
        }
        
        return array_values($allProcesses);
    }
    
    /**
     * Obtener proceso por ID
     */
    public static function getProcessById($id) {
        $processes = self::getAllProcesses();
        
        foreach ($processes as $process) {
            if ($process['id'] == $id) {
                return $process;
            }
        }
        
        return null;
    }
    
    /**
     * Crear nuevo proceso
     */
    public static function createProcess($data) {
        // Validar datos requeridos
        if (empty($data['name'])) {
            return ['success' => false, 'message' => 'El nombre del proceso es requerido'];
        }
        
        if (empty($data['frequency'])) {
            return ['success' => false, 'message' => 'La frecuencia es requerida'];
        }
        
        // TODO: Insertar en base de datos real
        
        $newProcess = [
            'id' => self::generateId(),
            'name' => self::sanitize($data['name']),
            'unit' => self::sanitize($data['unit'] ?? ''),
            'business' => self::sanitize($data['business'] ?? ''),
            'description' => self::sanitize($data['description'] ?? ''),
            'frequency' => $data['frequency'], // daily, weekly, monthly, quarterly, annual
            'active' => $data['active'] ?? 1,
            'start_date' => $data['start_date'] ?? date('Y-m-d'),
            'delegated_to' => $data['delegated_to'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'created_by' => $data['created_by'] ?? $_SESSION['user_id'] ?? 1,
            'last_executed' => null,
            'next_execution' => self::calculateNextExecution($data['frequency'], $data['start_date'] ?? date('Y-m-d')),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return ['success' => true, 'process_id' => $newProcess['id'], 'data' => $newProcess];
    }
    
    /**
     * Actualizar proceso existente
     */
    public static function updateProcess($id, $data) {
        $process = self::getProcessById($id);
        
        if (!$process) {
            return ['success' => false, 'message' => 'Proceso no encontrado'];
        }
        
        // TODO: Actualizar en base de datos real
        
        return ['success' => true, 'message' => 'Proceso actualizado correctamente'];
    }
    
    /**
     * Eliminar proceso
     */
    public static function deleteProcess($id) {
        $process = self::getProcessById($id);
        
        if (!$process) {
            return ['success' => false, 'message' => 'Proceso no encontrado'];
        }
        
        // TODO: Eliminar de base de datos real
        
        return ['success' => true, 'message' => 'Proceso eliminado correctamente'];
    }
    
    /**
     * Obtener tareas generadas por un proceso
     */
    public static function getProcessTasks($processId) {
        require_once __DIR__ . '/tasks.model.php';
        return TasksModel::getProcessTasks($processId);
    }
    
    /**
     * Ejecutar proceso (genera una nueva tarea)
     */
    public static function executeProcess($processId) {
        $process = self::getProcessById($processId);
        
        if (!$process) {
            return ['success' => false, 'message' => 'Proceso no encontrado'];
        }
        
        if (!$process['active']) {
            return ['success' => false, 'message' => 'El proceso está inactivo'];
        }
        
        // Crear tarea desde el proceso
        require_once __DIR__ . '/tasks.model.php';
        
        $taskData = [
            'title' => $process['name'],
            'description' => $process['description'],
            'unit' => $process['unit'],
            'business' => $process['business'],
            'status' => 'pending',
            'priority' => $process['priority'],
            'start_date' => date('Y-m-d'),
            'due_date' => self::calculateDueDate($process['frequency']),
            'created_by' => $process['created_by'],
            'delegated_to' => $process['delegated_to'],
            'type' => 'process_task',
            'process_id' => $processId
        ];
        
        $result = TasksModel::createTask($taskData);
        
        if ($result['success']) {
            // Actualizar última ejecución y próxima ejecución
            self::updateProcess($processId, [
                'last_executed' => date('Y-m-d H:i:s'),
                'next_execution' => self::calculateNextExecution($process['frequency'])
            ]);
        }
        
        return $result;
    }
    
    /**
     * Calcular próxima ejecución basada en frecuencia
     */
    private static function calculateNextExecution($frequency, $fromDate = null) {
        $baseDate = $fromDate ? strtotime($fromDate) : time();
        
        switch ($frequency) {
            case 'daily':
            case 'Diario':
                return date('Y-m-d', strtotime('+1 day', $baseDate));
            case 'weekly':
            case 'Semanal':
                return date('Y-m-d', strtotime('+1 week', $baseDate));
            case 'biweekly':
            case 'Quincenal':
                return date('Y-m-d', strtotime('+2 weeks', $baseDate));
            case 'monthly':
            case 'Mensual':
                return date('Y-m-d', strtotime('+1 month', $baseDate));
            case 'quarterly':
            case 'Trimestral':
                return date('Y-m-d', strtotime('+3 months', $baseDate));
            case 'annual':
            case 'Anual':
                return date('Y-m-d', strtotime('+1 year', $baseDate));
            default:
                return date('Y-m-d', strtotime('+1 month', $baseDate));
        }
    }
    
    /**
     * Calcular fecha de vencimiento basada en frecuencia
     */
    private static function calculateDueDate($frequency) {
        return self::calculateNextExecution($frequency);
    }
    
    /**
     * Obtener procesos que deben ejecutarse hoy
     */
    public static function getProcessesDueToday() {
        $allProcesses = self::getAllProcesses();
        $today = date('Y-m-d');
        
        return array_filter($allProcesses, function($process) use ($today) {
            return $process['active'] == 1 && 
                   $process['next_execution'] <= $today;
        });
    }
    
    /**
     * Obtener todos los procesos (temporal - reemplazar con BD)
     */
    private static function getAllProcesses() {
        // Datos de ejemplo - TODO: Reemplazar con consulta a BD
        return [
            [
                'id' => 1,
                'name' => 'Reporte de Ventas Semanal',
                'unit' => 'CDMX',
                'business' => 'Ventas',
                'description' => 'Generar reporte de ventas de la semana',
                'frequency' => 'Semanal',
                'active' => 1,
                'start_date' => date('Y-m-d', strtotime('-30 days')),
                'delegated_to' => 2,
                'priority' => 'high',
                'created_by' => 1,
                'last_executed' => date('Y-m-d', strtotime('-7 days')),
                'next_execution' => date('Y-m-d'),
                'created_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'name' => 'Inventario Mensual',
                'unit' => 'Monterrey',
                'business' => 'Operaciones',
                'description' => 'Conteo físico de inventario',
                'frequency' => 'Mensual',
                'active' => 1,
                'start_date' => date('Y-m-01'),
                'delegated_to' => 3,
                'priority' => 'medium',
                'created_by' => 1,
                'last_executed' => date('Y-m-d', strtotime('-30 days')),
                'next_execution' => date('Y-m-d', strtotime('+5 days')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-60 days')),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];
    }
    
    /**
     * Utilidades
     */
    
    private static function generateId() {
        return time() . rand(1000, 9999);
    }
    
    private static function sanitize($value) {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }
}
