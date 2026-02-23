-- Migración: Agregar campo fecha_entrega a tasks
-- Fecha: 2025-01-10
-- Descripción: Añade el campo fecha_entrega entre descripcion y fecha_inicio para tracking de entregables

ALTER TABLE tasks 
ADD COLUMN fecha_entrega DATE NULL COMMENT 'Fecha compromiso de entrega del entregable' 
AFTER descripcion;

-- Crear índice para optimizar consultas por fecha de entrega
CREATE INDEX ix_tasks_fecha_entrega ON tasks(fecha_entrega);

-- Comentario de auditoría
SELECT 'Migración completada: fecha_entrega agregado a tasks' AS status;
