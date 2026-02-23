<?php
/**
 * /modules/processes_tasks/includes/log_task_audit.php
 * Helper para registrar cambios en tareas
 */

/**
 * Registra un cambio en la tabla de auditoría
 */
function logTaskAudit($pdo, $taskId, $field, $oldValue, $newValue, $userId) {
    try {
        $stmt = $pdo->prepare("INSERT INTO task_audit (task_id, usuario_id, accion, field, old_value, new_value, created_at) VALUES (?, ?, 'update', ?, ?, ?, NOW())");

        $stmt->execute([
            (int)$taskId,
            $userId !== null ? (int)$userId : null,
            (string)$field,
            $oldValue ?? null,
            $newValue ?? null,
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error logging audit: " . $e->getMessage());
        return false;
    }
}
