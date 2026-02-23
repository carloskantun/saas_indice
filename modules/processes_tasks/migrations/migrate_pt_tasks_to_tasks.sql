-- =================================================================
-- MIGRACIÓN: pt_tasks → tasks (UNIFICACIÓN)
-- Fecha: 22 de noviembre de 2025
-- Autor: Sistema Índice ERP
-- =================================================================
-- ADVERTENCIA: ¡HACER BACKUP ANTES DE EJECUTAR!
-- =================================================================

-- PASO 1: CREAR TABLA DE RESPALDO
-- -----------------------------------------------------------------
DROP TABLE IF EXISTS pt_tasks_backup_20251122;
CREATE TABLE pt_tasks_backup_20251122 LIKE pt_tasks;
INSERT INTO pt_tasks_backup_20251122 SELECT * FROM pt_tasks;

SELECT CONCAT('✅ Backup creado: ', COUNT(*), ' registros') as status 
FROM pt_tasks_backup_20251122;

-- PASO 2: CREAR TABLA TEMPORAL PARA MAPEO DE IDS
-- -----------------------------------------------------------------
-- Necesitamos mapear hr_employee_id → user_id
DROP TEMPORARY TABLE IF EXISTS temp_hr_to_user_map;
CREATE TEMPORARY TABLE temp_hr_to_user_map AS
SELECT 
    e.id as hr_employee_id,
    e.user_id,
    e.company_id,
    e.full_name,
    e.status as employee_status
FROM hr_employees e
WHERE e.user_id IS NOT NULL;

SELECT CONCAT('✅ Mapeo HR→User: ', COUNT(*), ' empleados mapeados') as status 
FROM temp_hr_to_user_map;

-- PASO 3: MIGRAR DATOS DE pt_tasks A tasks
-- -----------------------------------------------------------------
-- Insertar solo los registros que NO existen ya en tasks
-- (evitar duplicados por título + company + fecha)

INSERT INTO tasks (
    company_id,
    business_id,
    unit_id,
    folio,
    titulo,
    descripcion,
    fecha_inicio,
    fecha_fin,
    fecha_entrega,
    archivos,
    usuario_creador,
    usuario_delegado,
    nivel,
    tipo,
    proyecto_asignado,
    ponderacion,
    status,
    created_at,
    updated_at
)
SELECT 
    pt.company_id,
    NULL as business_id,  -- pt_tasks no tiene business_id directo
    NULL as unit_id,      -- pt_tasks no tiene unit_id directo
    
    -- Generar folio único si no existe
    CONCAT('PT-', LPAD(pt.id, 6, '0')) as folio,
    
    -- Campos de texto (normalizar nombres)
    pt.title as titulo,
    pt.description as descripcion,
    
    -- Fechas
    pt.start_date as fecha_inicio,
    pt.due_date as fecha_fin,
    pt.due_date as fecha_entrega,  -- Usar due_date como fecha_entrega
    
    -- Archivos (pt_tasks no tiene este campo JSON)
    NULL as archivos,
    
    -- MAPEO CRÍTICO: hr_employee_id → user_id
    -- delegate_hr_id en pt_tasks = quien DELEGA/CREA = usuario_creador
    COALESCE(map_delegate.user_id, pt.delegate_hr_id) as usuario_creador,
    
    -- assignee_hr_id en pt_tasks = a quien se ASIGNA = usuario_delegado
    COALESCE(map_assignee.user_id, pt.assignee_hr_id) as usuario_delegado,
    
    -- NORMALIZAR PRIORIDAD: priority → nivel
    CASE 
        WHEN LOWER(pt.priority) IN ('baja', 'low', 'bajo') THEN 'Normal'
        WHEN LOWER(pt.priority) IN ('media', 'medium', 'medio') THEN 'Normal'
        WHEN LOWER(pt.priority) IN ('alta', 'high', 'importante') THEN 'Importante'
        WHEN LOWER(pt.priority) IN ('urgente', 'urgent', 'critica', 'critical') THEN 'Urgente'
        ELSE 'Normal'
    END as nivel,
    
    -- Tipo de tarea
    CASE 
        WHEN pt.process_id IS NOT NULL THEN 'Tarea de proceso'
        ELSE 'Tarea'
    END as tipo,
    
    -- Proyecto
    COALESCE(pt.project_name, pt.project_id) as proyecto_asignado,
    
    -- Ponderación
    COALESCE(pt.audit_score, 1) as ponderacion,
    
    -- NORMALIZAR STATUS
    CASE 
        WHEN LOWER(pt.status) IN ('pendiente', 'pending', 'en tiempo') THEN 'En tiempo'
        WHEN LOWER(pt.status) IN ('en proceso', 'en_proceso', 'in progress', 'progress') THEN 'En proceso'
        WHEN LOWER(pt.status) IN ('completada', 'completed', 'terminada', 'done') THEN 'Terminada'
        WHEN LOWER(pt.status) IN ('vencida', 'overdue', 'late') THEN 'Vencida'
        WHEN LOWER(pt.status) IN ('auditada', 'audited') THEN 'Auditada'
        WHEN LOWER(pt.status) IN ('pausada', 'paused', 'on hold') THEN 'Pausada'
        ELSE 'En tiempo'
    END as status,
    
    -- Timestamps
    pt.created_at,
    pt.updated_at

FROM pt_tasks pt

-- JOIN para obtener user_id del delegado (creador)
LEFT JOIN temp_hr_to_user_map map_delegate 
    ON map_delegate.hr_employee_id = pt.delegate_hr_id 
    AND map_delegate.company_id = pt.company_id

-- JOIN para obtener user_id del assignee (delegado)
LEFT JOIN temp_hr_to_user_map map_assignee 
    ON map_assignee.hr_employee_id = pt.assignee_hr_id 
    AND map_assignee.company_id = pt.company_id

-- EVITAR DUPLICADOS: No insertar si ya existe una tarea similar
WHERE NOT EXISTS (
    SELECT 1 
    FROM tasks t 
    WHERE t.titulo = pt.title 
      AND t.company_id = pt.company_id 
      AND DATE(t.fecha_inicio) = DATE(pt.start_date)
);

-- Mostrar resultado
SELECT CONCAT('✅ Migradas: ', ROW_COUNT(), ' tareas de pt_tasks a tasks') as status;

-- PASO 4: VERIFICAR MIGRACIÓN
-- -----------------------------------------------------------------
SELECT 
    '=== RESULTADO DE MIGRACIÓN ===' as info;

SELECT 
    'tasks' as tabla,
    COUNT(*) as total_tareas,
    COUNT(DISTINCT company_id) as empresas,
    MIN(created_at) as desde,
    MAX(created_at) as hasta
FROM tasks
UNION ALL
SELECT 
    'pt_tasks (original)' as tabla,
    COUNT(*) as total_tareas,
    COUNT(DISTINCT company_id) as empresas,
    MIN(created_at) as desde,
    MAX(created_at) as hasta
FROM pt_tasks;

-- PASO 5: VERIFICAR MAPEO DE USUARIOS
-- -----------------------------------------------------------------
SELECT 
    '=== VERIFICACIÓN DE MAPEO DE USUARIOS ===' as info;

-- Contar cuántos usuarios se mapearon correctamente
SELECT 
    'Usuarios creadores mapeados' as tipo,
    COUNT(DISTINCT t.usuario_creador) as usuarios_unicos,
    COUNT(*) as total_tareas
FROM tasks t
WHERE t.usuario_creador IS NOT NULL
  AND EXISTS (SELECT 1 FROM users u WHERE u.id = t.usuario_creador);

SELECT 
    'Usuarios delegados mapeados' as tipo,
    COUNT(DISTINCT t.usuario_delegado) as usuarios_unicos,
    COUNT(*) as total_tareas
FROM tasks t
WHERE t.usuario_delegado IS NOT NULL
  AND EXISTS (SELECT 1 FROM users u WHERE u.id = t.usuario_delegado);

-- PASO 6: VERIFICAR NORMALIZACIÓN DE STATUS
-- -----------------------------------------------------------------
SELECT 
    '=== DISTRIBUCIÓN DE STATUS DESPUÉS DE MIGRACIÓN ===' as info;

SELECT 
    status,
    COUNT(*) as cantidad,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM tasks), 2) as porcentaje
FROM tasks
GROUP BY status
ORDER BY cantidad DESC;

-- PASO 7: VERIFICAR NORMALIZACIÓN DE PRIORIDAD
-- -----------------------------------------------------------------
SELECT 
    '=== DISTRIBUCIÓN DE PRIORIDAD DESPUÉS DE MIGRACIÓN ===' as info;

SELECT 
    nivel,
    COUNT(*) as cantidad,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM tasks), 2) as porcentaje
FROM tasks
GROUP BY nivel
ORDER BY 
    CASE nivel
        WHEN 'Urgente' THEN 1
        WHEN 'Importante' THEN 2
        WHEN 'Normal' THEN 3
        ELSE 4
    END;

-- =================================================================
-- OPCIONAL: RENOMBRAR pt_tasks PARA DESACTIVARLA (NO ELIMINAR)
-- =================================================================
-- ⚠️ SOLO EJECUTAR DESPUÉS DE VERIFICAR QUE TODO FUNCIONA ⚠️
-- RENAME TABLE pt_tasks TO pt_tasks_deprecated_20251122;
-- SELECT '✅ Tabla pt_tasks renombrada a pt_tasks_deprecated_20251122' as status;

-- =================================================================
-- FIN DE LA MIGRACIÓN
-- =================================================================
-- INSTRUCCIONES POST-MIGRACIÓN:
-- 1. Verificar que todos los conteos sean correctos
-- 2. Probar el módulo en desarrollo
-- 3. Verificar que las APIs funcionan correctamente
-- 4. Una vez validado TODO, ejecutar el RENAME de pt_tasks
-- 5. Guardar este script y el backup por si hay que revertir
-- =================================================================
