-- ==========================================
-- SEED MÍNIMO - Procesos & Tareas
-- Solo 2 registros para validación rápida
-- ==========================================

INSERT INTO tasks 
(company_id, business_id, unit_id, folio, titulo, descripcion, 
 fecha_inicio, fecha_entrega, fecha_fin, usuario_creador, usuario_delegado, 
 nivel, tipo, proyecto_asignado, ponderacion, status)
VALUES
-- Tarea de prueba
(1, 1, 1, 'T-00006', 'Prueba tabla unificada', 'Registro seed para validar componente table_tasks.php', 
 CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 
 1, NULL, 'Normal', 'Tarea', NULL, 1, 'En tiempo'),

-- Proceso de prueba
(1, 1, 1, 'P-00003', 'Proceso mensual de prueba', 'Solo validación UI - componente reutilizable', 
 CURDATE(), NULL, NULL, 
 1, NULL, 'Importante', 'Proceso', NULL, 2, 'En proceso');

-- Verificación
SELECT 
    'SEED EJECUTADO' AS status,
    COUNT(*) AS total_insertado,
    MAX(id) AS ultimo_id
FROM tasks
WHERE folio IN ('T-00006', 'P-00003');
