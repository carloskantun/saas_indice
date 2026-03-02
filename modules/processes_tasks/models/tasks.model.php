<?php
/**
 * Modelo de Tareas - Módulo Tareas
 * Sistema de gestión de tareas, agenda y asignaciones con PDO real
 * Compatible con el sistema de permisos de Índice ERP
 * 
 * @author Sistema Índice ERP 2025
 * @version 2.0.0 - Backend Real con PDO
 */

class TasksModel {
    
    /**
     * Listar tareas con filtros
     * @param array $filters Filtros disponibles: company_id, tipo, status, q (búsqueda), unit_id, business_id
     * @return array Lista de tareas
     */
    public static function list(array $filters = []): array {
        $pdo = db();
        
        $sql = "SELECT t.*,
                       b.name AS business_nombre,
                       u.name AS unit_nombre,
                       c.full_name AS creador_nombre,
                       d.full_name AS delegado_nombre,
                       0 AS archivos_count
                FROM tasks t
                LEFT JOIN businesses b ON b.id = t.business_id
                LEFT JOIN units u ON u.id = t.unit_id
                LEFT JOIN hr_employees c ON c.id = t.usuario_creador
                LEFT JOIN hr_employees d ON d.id = t.usuario_delegado
                WHERE t.company_id = ?";
        
        $params = [(int)($filters['company_id'] ?? 1)];
        
        // Filtro por tipo
        if (!empty($filters['tipo'])) {
            if (is_array($filters['tipo'])) {
                $placeholders = str_repeat('?,', count($filters['tipo']) - 1) . '?';
                $sql .= " AND t.tipo IN ($placeholders)";
                $params = array_merge($params, $filters['tipo']);
            } else {
                $sql .= " AND t.tipo = ?";
                $params[] = $filters['tipo'];
            }
        }
        
        // Filtro por status
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $placeholders = str_repeat('?,', count($filters['status']) - 1) . '?';
                $sql .= " AND t.status IN ($placeholders)";
                $params = array_merge($params, $filters['status']);
            } else {
                $sql .= " AND t.status = ?";
                $params[] = $filters['status'];
            }
        }
        
        // Búsqueda por texto
        if (!empty($filters['q']) || !empty($filters['search'])) {
            $searchText = $filters['search'] ?? $filters['q'];
            $sql .= " AND (t.titulo LIKE ? OR t.descripcion LIKE ? OR t.folio LIKE ?)";
            $searchTerm = "%{$searchText}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Filtro por período (para Agenda)
        if (!empty($filters['periodo'])) {
            $today = date('Y-m-d');
            switch ($filters['periodo']) {
                case 'hoy':
                    $sql .= " AND DATE(t.fecha_inicio) <= ? AND (t.fecha_fin IS NULL OR DATE(t.fecha_fin) >= ?)";
                    $params[] = $today;
                    $params[] = $today;
                    break;
                case 'semana':
                    $endOfWeek = date('Y-m-d', strtotime('+7 days'));
                    $sql .= " AND DATE(t.fecha_inicio) BETWEEN ? AND ?";
                    $params[] = $today;
                    $params[] = $endOfWeek;
                    break;
                case 'mes':
                    $endOfMonth = date('Y-m-t');
                    $sql .= " AND DATE(t.fecha_inicio) BETWEEN ? AND ?";
                    $params[] = $today;
                    $params[] = $endOfMonth;
                    break;
                case 'custom':
                    // Período personalizado con fechas específicas
                    if (!empty($filters['fecha_inicio']) && !empty($filters['fecha_fin'])) {
                        $sql .= " AND DATE(t.fecha_inicio) BETWEEN ? AND ?";
                        $params[] = $filters['fecha_inicio'];
                        $params[] = $filters['fecha_fin'];
                    }
                    break;
            }
        }
        
        // Filtro por unidad
        if (!empty($filters['unit_id'])) {
            $sql .= " AND t.unit_id = ?";
            $params[] = (int)$filters['unit_id'];
        }
        
        // Filtro por negocio
        if (!empty($filters['business_id'])) {
            $sql .= " AND t.business_id = ?";
            $params[] = (int)$filters['business_id'];
        }
        
        // Filtro por usuario delegado
        if (!empty($filters['delegated_to'])) {
            $sql .= " AND t.usuario_delegado = ?";
            $params[] = (int)$filters['delegated_to'];
        }
        
        // Filtro por proyecto
        if (!empty($filters['proyecto'])) {
            $sql .= " AND t.proyecto_asignado LIKE ?";
            $params[] = "%{$filters['proyecto']}%";
        }
        
        $sql .= " ORDER BY t.created_at DESC LIMIT 500";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener tareas para la agenda con filtros de fecha
     * @param array $filters
     * @return array
     */
    public static function getAgenda($filters = []) {
        $pdo = db();
        
        $sql = "SELECT t.*, 
                       uc.full_name AS creador_name, 
                       ud.full_name AS delegado_name,
                       e1.nombre_completo AS creador_nombre_rh,
                       e2.nombre_completo AS delegado_nombre_rh
                FROM tasks t
                LEFT JOIN users uc ON uc.id = t.usuario_creador
                LEFT JOIN users ud ON ud.id = t.usuario_delegado
                LEFT JOIN hr_employees e1 ON e1.user_id = t.usuario_creador AND e1.company_id = t.company_id
                LEFT JOIN hr_employees e2 ON e2.user_id = t.usuario_delegado AND e2.company_id = t.company_id
                WHERE t.company_id = :company_id";
        
        $params = [':company_id' => (int)($filters['company_id'] ?? 1)];
        
        // Filtros de fecha
        if (!empty($filters['date_filter'])) {
            $today = date('Y-m-d');
            
            switch ($filters['date_filter']) {
                case 'today':
                    $sql .= " AND (t.fecha_inicio <= :today AND (t.fecha_fin >= :today OR t.fecha_fin IS NULL))";
                    $params[':today'] = $today;
                    break;
                    
                case 'week':
                    $startWeek = date('Y-m-d', strtotime('monday this week'));
                    $endWeek = date('Y-m-d', strtotime('sunday this week'));
                    $sql .= " AND ((t.fecha_inicio BETWEEN :start AND :end) OR (t.fecha_fin BETWEEN :start AND :end))";
                    $params[':start'] = $startWeek;
                    $params[':end'] = $endWeek;
                    break;
                    
                case 'month':
                    $startMonth = date('Y-m-01');
                    $endMonth = date('Y-m-t');
                    $sql .= " AND ((t.fecha_inicio BETWEEN :start AND :end) OR (t.fecha_fin BETWEEN :start AND :end))";
                    $params[':start'] = $startMonth;
                    $params[':end'] = $endMonth;
                    break;
                    
                case 'custom':
                    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                        $sql .= " AND ((t.fecha_inicio BETWEEN :start AND :end) OR (t.fecha_fin BETWEEN :start AND :end))";
                        $params[':start'] = $filters['start_date'];
                        $params[':end'] = $filters['end_date'];
                    }
                    break;
            }
        }
        
        // Otros filtros
        if (!empty($filters['unit'])) {
            $sql .= " AND t.unit_id = ?";
            $params[] = $filters['unit'];
        }
        
        if (!empty($filters['business'])) {
            $sql .= " AND t.business_id = ?";
            $params[] = $filters['business'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = ?";
            $params[] = $filters['status'];
        }
        
        $sql .= " ORDER BY t.fecha_inicio ASC, t.created_at DESC LIMIT 100";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener todas las tareas (wrapper para list)
     */
    public static function getTasks($filters = []) {
        return self::list($filters);
    }
    
    /**
     * Obtener tarea por ID
     * @param int $id
     * @return array|null
     */
    public static function getTaskById($id) {
        $pdo = db();
        
        $stmt = $pdo->prepare("
            SELECT t.*, 
                   uc.full_name AS creador_name, 
                   ud.full_name AS delegado_name,
                   b.name AS business_name,
                   u.name AS unit_name
            FROM tasks t
            LEFT JOIN users uc ON uc.id = t.usuario_creador
            LEFT JOIN users ud ON ud.id = t.usuario_delegado
            LEFT JOIN businesses b ON b.id = t.business_id
            LEFT JOIN units u ON u.id = t.unit_id
            WHERE t.id = ?
            LIMIT 1
        ");
        
        $stmt->execute([(int)$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }
    
    /**
     * Crear nueva tarea
     * @param array $data
     * @return int ID de la tarea creada
     */
    public static function create(array $data): int {
        $pdo = db();
        
        // Validaciones
        if (empty($data['titulo'])) {
            throw new Exception('El título es requerido');
        }
        
        if (empty($data['company_id'])) {
            throw new Exception('El company_id es requerido');
        }
        
        // Generar folio único si no viene
        if (empty($data['folio'])) {
            $data['folio'] = self::generateFolio($data['company_id']);
        }
        
        $sql = "INSERT INTO tasks
            (company_id, business_id, unit_id, folio, titulo, descripcion, 
             fecha_inicio, fecha_fin, archivos, usuario_creador, usuario_delegado, 
             nivel, tipo, proyecto_asignado, ponderacion, status)
            VALUES
            (:company_id, :business_id, :unit_id, :folio, :titulo, :descripcion,
             :fecha_inicio, :fecha_fin, :archivos, :usuario_creador, :usuario_delegado,
             :nivel, :tipo, :proyecto_asignado, :ponderacion, :status)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':company_id' => (int)$data['company_id'],
            ':business_id' => !empty($data['business_id']) ? (int)$data['business_id'] : null,
            ':unit_id' => !empty($data['unit_id']) ? (int)$data['unit_id'] : null,
            ':folio' => $data['folio'],
            ':titulo' => $data['titulo'],
            ':descripcion' => $data['descripcion'] ?? null,
            ':fecha_inicio' => $data['fecha_inicio'] ?? null,
            ':fecha_fin' => $data['fecha_fin'] ?? null,
            ':archivos' => $data['archivos'] ?? null,
            ':usuario_creador' => !empty($data['usuario_creador']) ? (int)$data['usuario_creador'] : null,
            ':usuario_delegado' => !empty($data['usuario_delegado']) ? (int)$data['usuario_delegado'] : null,
            ':nivel' => $data['nivel'] ?? 'Normal',
            ':tipo' => $data['tipo'] ?? 'Tarea',
            ':proyecto_asignado' => $data['proyecto_asignado'] ?? null,
            ':ponderacion' => (int)($data['ponderacion'] ?? 1),
            ':status' => $data['status'] ?? 'En tiempo',
        ]);
        
        $id = (int)$pdo->lastInsertId();
        
        // Registrar auditoría
        self::audit($id, (int)($data['_actor'] ?? $data['usuario_creador'] ?? null), 'create', null, null, null);
        
        return $id;
    }
    
    /**
     * Crear tarea (alias para mantener compatibilidad)
     */
    public static function createTask($data) {
        try {
            $id = self::create($data);
            return ['success' => true, 'task_id' => $id, 'message' => 'Tarea creada correctamente'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Actualizar tarea (solo campos permitidos)
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool {
        $pdo = db();
        
        // Obtener valores actuales antes del cambio
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $oldData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$oldData) {
            throw new Exception('Tarea no encontrada');
        }
        
        $permitidos = [
            'titulo', 'descripcion', 'fecha_inicio', 'fecha_fin', 'archivos',
            'usuario_delegado', 'nivel', 'tipo', 'proyecto_asignado', 
            'ponderacion', 'status', 'business_id', 'unit_id'
        ];
        
        $sets = [];
        $params = [':id' => $id];
        $changes = []; // Registrar cambios
        
        foreach ($permitidos as $k) {
            if (array_key_exists($k, $data)) {
                $newValue = $data[$k];
                $oldValue = $oldData[$k] ?? null;
                
                // Solo actualizar si cambió
                if ($newValue != $oldValue) {
                    $sets[] = " $k = :$k ";
                    $params[":$k"] = $newValue;
                    $changes[$k] = ['old' => $oldValue, 'new' => $newValue];
                }
            }
        }
        
        if (empty($sets)) {
            return true; // No hay cambios reales
        }
        
        $sql = "UPDATE tasks SET " . implode(',', $sets) . " WHERE id = :id LIMIT 1";
        $ok = $pdo->prepare($sql)->execute($params);
        
        // Registrar auditoría detallada de cada cambio
        if ($ok && !empty($changes)) {
            $actorId = (int)($data['_actor'] ?? null);
            foreach ($changes as $field => $values) {
                self::logChange($id, $actorId, $field, $values['old'], $values['new']);
            }
        }
        
        return $ok;
    }
    
    /**
     * Registrar cambio individual en auditoría
     * @param int $taskId
     * @param int|null $actorId
     * @param string $field
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return void
     */
    private static function logChange(int $taskId, ?int $actorId, string $field, $oldValue, $newValue): void {
        $pdo = db();
        
        $stmt = $pdo->prepare("
            INSERT INTO task_audit (task_id, usuario_id, accion, field, old_value, new_value)
            VALUES (?, ?, 'update', ?, ?, ?)
        ");
        
        $stmt->execute([
            $taskId,
            $actorId,
            $field,
            is_array($oldValue) ? json_encode($oldValue) : (string)$oldValue,
            is_array($newValue) ? json_encode($newValue) : (string)$newValue
        ]);
    }
    
    /**
     * Actualizar tarea (alias para mantener compatibilidad)
     */
    public static function updateTask($id, $data) {
        try {
            $ok = self::update((int)$id, $data);
            return ['success' => $ok, 'message' => $ok ? 'Tarea actualizada correctamente' : 'No se pudo actualizar'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Eliminar tarea
     * @param int $id
     * @param int|null $actorId
     * @return bool
     */
    public static function delete(int $id, ?int $actorId = null): bool {
        $pdo = db();
        
        // Registrar auditoría antes de eliminar
        self::audit($id, $actorId, 'delete', null, null, null);
        
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id LIMIT 1");
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Eliminar tarea (alias para mantener compatibilidad)
     */
    public static function deleteTask($id) {
        try {
            $ok = self::delete((int)$id);
            return ['success' => $ok, 'message' => $ok ? 'Tarea eliminada correctamente' : 'No se pudo eliminar'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Duplicar tarea
     * @param int $id
     * @param int|null $actorId
     * @return int ID de la nueva tarea
     */
    public static function duplicate(int $id, ?int $actorId = null): int {
        $pdo = db();
        
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            throw new Exception('Tarea no encontrada');
        }
        
        // Remover campos que no se deben duplicar
        unset($row['id'], $row['created_at'], $row['updated_at']);
        
        // Generar nuevo folio
        $row['folio'] = 'DUP-' . uniqid() . '-' . substr($row['folio'], -4);
        $row['titulo'] = $row['titulo'] . ' (copia)';
        $row['status'] = 'En tiempo';
        $row['_actor'] = $actorId;
        
        return self::create($row);
    }
    
    /**
     * Duplicar tarea (alias para mantener compatibilidad)
     */
    public static function duplicateTask($taskId, $userId) {
        try {
            $newId = self::duplicate((int)$taskId, $userId);
            return ['success' => true, 'task_id' => $newId, 'message' => 'Tarea duplicada correctamente'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Completar tarea
     * @param int $id
     * @param int|null $actorId
     * @return bool
     */
    public static function complete(int $id, ?int $actorId = null): bool {
        return self::update($id, [
            'status' => 'Terminada',
            '_actor' => $actorId
        ]);
    }
    
    /**
     * Completar tarea (alias para mantener compatibilidad)
     */
    public static function completeTask($id) {
        try {
            $ok = self::complete((int)$id);
            return ['success' => $ok, 'message' => $ok ? 'Tarea completada' : 'No se pudo completar'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Asignar tarea a un usuario
     * @param int $taskId
     * @param int $userId
     * @return bool
     */
    public static function assign(int $taskId, int $userId): bool {
        return self::update($taskId, [
            'usuario_delegado' => $userId,
            '_actor' => $userId
        ]);
    }
    
    /**
     * Asignar tarea (alias para mantener compatibilidad)
     */
    public static function assignTask($taskId, $userId) {
        try {
            $ok = self::assign((int)$taskId, (int)$userId);
            return ['success' => $ok, 'message' => $ok ? 'Tarea asignada correctamente' : 'No se pudo asignar'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Auditar tarea (registrar revisión)
     * @param int $taskId
     * @param array $changes
     * @param int|null $userId
     * @return array
     */
    public static function auditTask($taskId, $changes, $userId) {
        try {
            // Actualizar status a auditada
            self::update((int)$taskId, [
                'status' => 'Auditada',
                '_actor' => $userId
            ]);
            
            // Registrar cada cambio
            if (is_array($changes)) {
                foreach ($changes as $field => $values) {
                    self::audit(
                        (int)$taskId,
                        $userId,
                        'audit',
                        $field,
                        $values['old'] ?? null,
                        $values['new'] ?? null
                    );
                }
            }
            
            return ['success' => true, 'message' => 'Tarea auditada correctamente'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Registrar auditoría de cambios
     * @param int $taskId
     * @param int|null $userId
     * @param string $accion
     * @param string|null $field
     * @param mixed $old
     * @param mixed $new
     * @return void
     */
    public static function audit(int $taskId, ?int $userId, string $accion, ?string $field = null, $old = null, $new = null): void {
        $pdo = db();
        
        $stmt = $pdo->prepare("
            INSERT INTO task_audit 
            (task_id, usuario_id, accion, field, old_value, new_value) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $taskId,
            $userId,
            $accion,
            $field,
            is_null($old) ? null : (string)$old,
            is_null($new) ? null : (string)$new
        ]);
    }
    
    /**
     * Generar folio único para una tarea
     * @param int $companyId
     * @return string
     */
    private static function generateFolio(int $companyId): string {
        $pdo = db();
        
        // Obtener el último folio de la empresa
        $stmt = $pdo->prepare("
            SELECT folio FROM tasks 
            WHERE company_id = ? 
            AND folio LIKE 'T-%'
            ORDER BY id DESC 
            LIMIT 1
        ");
        
        $stmt->execute([$companyId]);
        $lastFolio = $stmt->fetchColumn();
        
        if ($lastFolio && preg_match('/T-(\d+)/', $lastFolio, $matches)) {
            $nextNumber = (int)$matches[1] + 1;
        } else {
            $nextNumber = 1;
        }
        
        return 'T-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }
    
    /**
     * Obtener KPIs de tareas
     * @param int $companyId
     * @param array $filters
     * @return array
     */
    public static function getKPIs(int $companyId, array $filters = []): array {
        $pdo = db();
        
        // Si se solicita agrupación por negocio
        if (!empty($filters['group_by_business'])) {
            return self::getKPIsByBusiness($companyId, $filters);
        }
        
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'En tiempo' THEN 1 ELSE 0 END) as en_tiempo,
                    SUM(CASE WHEN status = 'En proceso' THEN 1 ELSE 0 END) as en_proceso,
                    SUM(CASE WHEN status = 'Terminada' THEN 1 ELSE 0 END) as terminadas,
                    SUM(CASE WHEN status = 'Vencida' THEN 1 ELSE 0 END) as vencidas,
                    SUM(CASE WHEN status = 'Auditada' THEN 1 ELSE 0 END) as auditadas
                FROM tasks
                WHERE company_id = ?";
        
        $params = [$companyId];
        
        // Aplicar filtros adicionales
        if (!empty($filters['business_id'])) {
            $sql .= " AND business_id = ?";
            $params[] = (int)$filters['business_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND fecha_inicio >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND fecha_fin <= ?";
            $params[] = $filters['date_to'];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Obtener KPIs agrupados por negocio
     * @param int $companyId
     * @param array $filters
     * @return array
     */
    private static function getKPIsByBusiness(int $companyId, array $filters = []): array {
        $pdo = db();
        
        $sql = "SELECT 
                    t.business_id,
                    b.name as business_name,
                    COUNT(*) as total,
                    SUM(CASE WHEN t.status = 'En tiempo' THEN 1 ELSE 0 END) as en_tiempo,
                    SUM(CASE WHEN t.status = 'En proceso' THEN 1 ELSE 0 END) as en_proceso,
                    SUM(CASE WHEN t.status = 'Terminada' THEN 1 ELSE 0 END) as terminadas,
                    SUM(CASE WHEN t.status = 'Vencida' THEN 1 ELSE 0 END) as vencidas,
                    SUM(CASE WHEN t.status = 'Auditada' THEN 1 ELSE 0 END) as auditadas
                FROM tasks t
                LEFT JOIN businesses b ON b.id = t.business_id
                WHERE t.company_id = ?
                GROUP BY t.business_id, b.name
                ORDER BY total DESC";
        
        $params = [$companyId];
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
