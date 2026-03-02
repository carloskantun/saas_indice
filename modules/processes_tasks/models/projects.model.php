<?php
/**
 * Modelo de Proyectos - Módulo Tareas
 * Sistema de gestión de proyectos y relación con tareas
 * 
 * @author Nahum Peña / Proyecto Índice 2025
 */

class ProjectsModel {
    
    /**
     * Obtener todos los proyectos con filtros opcionales
     */
    public static function getProjects($filters = []) {
        $allProjects = self::getAllProjects();
        
        // Aplicar filtros
        if (isset($filters['unit']) && $filters['unit'] !== '') {
            $allProjects = array_filter($allProjects, function($project) use ($filters) {
                return $project['unit'] === $filters['unit'];
            });
        }
        
        if (isset($filters['business']) && $filters['business'] !== '') {
            $allProjects = array_filter($allProjects, function($project) use ($filters) {
                return $project['business'] === $filters['business'];
            });
        }
        
        if (isset($filters['status']) && $filters['status'] !== '') {
            $allProjects = array_filter($allProjects, function($project) use ($filters) {
                return $project['status'] === $filters['status'];
            });
        }
        
        return array_values($allProjects);
    }
    
    /**
     * Obtener proyecto por ID
     */
    public static function getProjectById($id) {
        $projects = self::getAllProjects();
        
        foreach ($projects as $project) {
            if ($project['id'] == $id) {
                return $project;
            }
        }
        
        return null;
    }
    
    /**
     * Crear nuevo proyecto
     */
    public static function createProject($data) {
        // Validar datos requeridos
        if (empty($data['name'])) {
            return ['success' => false, 'message' => 'El nombre del proyecto es requerido'];
        }
        
        // TODO: Insertar en base de datos real
        
        $newProject = [
            'id' => self::generateId(),
            'name' => self::sanitize($data['name']),
            'unit' => self::sanitize($data['unit'] ?? ''),
            'business' => self::sanitize($data['business'] ?? ''),
            'description' => self::sanitize($data['description'] ?? ''),
            'status' => $data['status'] ?? 'active',
            'created_by' => $data['created_by'] ?? $_SESSION['user_id'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return ['success' => true, 'project_id' => $newProject['id'], 'data' => $newProject];
    }
    
    /**
     * Actualizar proyecto existente
     */
    public static function updateProject($id, $data) {
        $project = self::getProjectById($id);
        
        if (!$project) {
            return ['success' => false, 'message' => 'Proyecto no encontrado'];
        }
        
        // TODO: Actualizar en base de datos real
        
        return ['success' => true, 'message' => 'Proyecto actualizado correctamente'];
    }
    
    /**
     * Eliminar proyecto
     */
    public static function deleteProject($id) {
        $project = self::getProjectById($id);
        
        if (!$project) {
            return ['success' => false, 'message' => 'Proyecto no encontrado'];
        }
        
        // Verificar que no tenga tareas asociadas
        require_once __DIR__ . '/tasks.model.php';
        $tasks = TasksModel::getProjectTasks($id);
        
        if (count($tasks) > 0) {
            return ['success' => false, 'message' => 'No se puede eliminar un proyecto con tareas asociadas'];
        }
        
        // TODO: Eliminar de base de datos real
        
        return ['success' => true, 'message' => 'Proyecto eliminado correctamente'];
    }
    
    /**
     * Obtener tareas asociadas a un proyecto
     */
    public static function getProjectTasks($projectId) {
        require_once __DIR__ . '/tasks.model.php';
        return TasksModel::getProjectTasks($projectId);
    }
    
    /**
     * Obtener estadísticas de un proyecto
     */
    public static function getProjectStats($projectId) {
        $tasks = self::getProjectTasks($projectId);
        
        $stats = [
            'total_tasks' => count($tasks),
            'completed' => 0,
            'in_progress' => 0,
            'pending' => 0,
            'overdue' => 0,
            'completion_rate' => 0
        ];
        
        foreach ($tasks as $task) {
            switch ($task['status']) {
                case 'completed':
                    $stats['completed']++;
                    break;
                case 'in_progress':
                    $stats['in_progress']++;
                    break;
                case 'pending':
                    $stats['pending']++;
                    break;
                case 'overdue':
                    $stats['overdue']++;
                    break;
            }
        }
        
        if ($stats['total_tasks'] > 0) {
            $stats['completion_rate'] = round(($stats['completed'] / $stats['total_tasks']) * 100, 2);
        }
        
        return $stats;
    }
    
    /**
     * Obtener todos los proyectos (temporal - reemplazar con BD)
     */
    private static function getAllProjects() {
        // Datos de ejemplo - TODO: Reemplazar con consulta a BD
        return [
            [
                'id' => 1,
                'name' => 'Expansión Regional 2025',
                'unit' => 'CDMX',
                'business' => 'Operaciones',
                'description' => 'Apertura de nuevas unidades en zona centro',
                'status' => 'active',
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'name' => 'Campaña Digital Q4',
                'unit' => 'Monterrey',
                'business' => 'Marketing',
                'description' => 'Lanzamiento de campaña digital para último trimestre',
                'status' => 'active',
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s', strtotime('-15 days')),
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
