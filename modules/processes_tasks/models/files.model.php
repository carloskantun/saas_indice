<?php
/**
 * Modelo de Archivos - Módulo Processes & Tasks
 * Gestión de adjuntos para tareas y procesos
 * 
 * @author Sistema Índice ERP 2025
 * @version 1.0.0
 */

class FilesModel {
    
    /**
     * Subir archivo y asociarlo a una tarea
     * @param int $taskId
     * @param array $file Información de $_FILES
     * @param int|null $uploadedBy
     * @return array ['ok' => bool, 'file_id' => int, 'error' => string]
     */
    public static function uploadFile(int $taskId, array $file, ?int $uploadedBy = null): array {
        // Validaciones
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'error' => 'Archivo no válido'];
        }
        
        // Validar tamaño (máx 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxSize) {
            return ['ok' => false, 'error' => 'Archivo demasiado grande (máx 10MB)'];
        }
        
        // Validar tipo MIME
        $allowedMimes = [
            'application/pdf',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/jpeg',
            'image/png',
            'image/gif',
            'text/plain',
            'text/csv'
        ];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedMimes)) {
            return ['ok' => false, 'error' => 'Tipo de archivo no permitido'];
        }
        
        // Crear directorio si no existe
        $uploadDir = __DIR__ . '/../../../uploads/tasks/' . date('Y/m');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generar nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('task_' . $taskId . '_') . '.' . $extension;
        $filepath = $uploadDir . '/' . $filename;
        
        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['ok' => false, 'error' => 'Error al guardar archivo'];
        }
        
        // Registrar en base de datos
        $pdo = db();
        $relativePath = '/uploads/tasks/' . date('Y/m') . '/' . $filename;
        
        $stmt = $pdo->prepare("
            INSERT INTO task_files (task_id, ruta, nombre_original, mime_type, size_bytes, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $taskId,
            $relativePath,
            $file['name'],
            $mimeType,
            $file['size'],
            $uploadedBy
        ]);
        
        return [
            'ok' => true,
            'file_id' => (int)$pdo->lastInsertId(),
            'filename' => $filename,
            'path' => $relativePath
        ];
    }
    
    /**
     * Obtener archivos de una tarea
     * @param int $taskId
     * @return array
     */
    public static function getFiles(int $taskId): array {
        $pdo = db();
        
        $stmt = $pdo->prepare("
            SELECT f.*,
                   u.full_name AS uploaded_by_name,
                   e.nombre_completo AS uploaded_by_nombre_rh
            FROM task_files f
            LEFT JOIN users u ON u.id = f.uploaded_by
            LEFT JOIN hr_employees e ON e.user_id = f.uploaded_by
            WHERE f.task_id = ?
            ORDER BY f.created_at DESC
        ");
        
        $stmt->execute([$taskId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Eliminar archivo
     * @param int $fileId
     * @return bool
     */
    public static function deleteFile(int $fileId): bool {
        $pdo = db();
        
        // Obtener info del archivo
        $stmt = $pdo->prepare("SELECT ruta FROM task_files WHERE id = ? LIMIT 1");
        $stmt->execute([$fileId]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$file) {
            return false;
        }
        
        // Eliminar archivo físico
        $fullPath = __DIR__ . '/../../../' . $file['ruta'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        
        // Eliminar registro
        $stmt = $pdo->prepare("DELETE FROM task_files WHERE id = ? LIMIT 1");
        return $stmt->execute([$fileId]);
    }
    
    /**
     * Descargar archivo
     * @param int $fileId
     * @return void
     */
    public static function downloadFile(int $fileId): void {
        $pdo = db();
        
        $stmt = $pdo->prepare("SELECT * FROM task_files WHERE id = ? LIMIT 1");
        $stmt->execute([$fileId]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$file) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Archivo no encontrado']);
            exit;
        }
        
        $fullPath = __DIR__ . '/../../../' . $file['ruta'];
        
        if (!file_exists($fullPath)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Archivo no existe en el servidor']);
            exit;
        }
        
        // Cabeceras de descarga
        header('Content-Type: ' . $file['mime_type']);
        header('Content-Disposition: attachment; filename="' . $file['nombre_original'] . '"');
        header('Content-Length: ' . $file['size_bytes']);
        header('Cache-Control: no-cache, must-revalidate');
        
        readfile($fullPath);
        exit;
    }
    
    /**
     * Obtener estadísticas de archivos por tarea
     * @param int $companyId
     * @return array
     */
    public static function getStats(int $companyId): array {
        $pdo = db();
        
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(f.id) as total_files,
                SUM(f.size_bytes) as total_size,
                COUNT(DISTINCT f.task_id) as tasks_with_files
            FROM task_files f
            INNER JOIN tasks t ON t.id = f.task_id
            WHERE t.company_id = ?
        ");
        
        $stmt->execute([$companyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}
