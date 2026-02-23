-- ========================================
-- OPTIMIZACIONES SQL - Módulo Processes & Tasks
-- ========================================
-- Ejecutar después de crear las tablas base

-- Índice adicional para fecha de vencimiento
ALTER TABLE tasks 
ADD INDEX ix_tasks_fecha_fin (fecha_fin);

-- Índice compuesto para filtros comunes
ALTER TABLE tasks 
ADD INDEX ix_tasks_company_status (company_id, status);

-- Índice para búsqueda de texto
ALTER TABLE tasks 
ADD FULLTEXT INDEX fx_tasks_busqueda (titulo, descripcion);

-- Índice para auditoría por fecha
ALTER TABLE task_audit 
ADD INDEX ix_audit_created (created_at);

-- Verificar índices creados
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    INDEX_TYPE
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('tasks', 'task_audit', 'task_files')
ORDER BY TABLE_NAME, INDEX_NAME;

-- Estadísticas de uso de índices
-- SHOW INDEX FROM tasks;
