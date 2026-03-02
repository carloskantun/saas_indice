-- =================================================================
-- ANÁLISIS DE DATOS: tasks vs pt_tasks
-- Fecha: 22 de noviembre de 2025
-- Propósito: Comparar ambas tablas antes de la migración
-- =================================================================

-- 1. VERIFICAR ESTRUCTURA DE AMBAS TABLAS
-- -----------------------------------------------------------------
SELECT 'ESTRUCTURA DE TABLA: tasks' as info;
DESCRIBE tasks;

SELECT 'ESTRUCTURA DE TABLA: pt_tasks' as info;
DESCRIBE pt_tasks;

-- 2. CONTAR REGISTROS EN AMBAS TABLAS
-- -----------------------------------------------------------------
SELECT 
    'tasks' as tabla,
    COUNT(*) as total_registros,
    COUNT(DISTINCT company_id) as empresas_unicas,
    MIN(created_at) as fecha_mas_antigua,
    MAX(created_at) as fecha_mas_reciente
FROM tasks
UNION ALL
SELECT 
    'pt_tasks' as tabla,
    COUNT(*) as total_registros,
    COUNT(DISTINCT company_id) as empresas_unicas,
    MIN(created_at) as fecha_mas_antigua,
    MAX(created_at) as fecha_mas_reciente
FROM pt_tasks;

-- 3. VERIFICAR DISTRIBUCIÓN POR COMPANY_ID
-- -----------------------------------------------------------------
SELECT 
    'tasks' as tabla,
    company_id,
    COUNT(*) as total_tareas
FROM tasks
GROUP BY company_id
ORDER BY company_id;

SELECT 
    'pt_tasks' as tabla,
    company_id,
    COUNT(*) as total_tareas
FROM pt_tasks
GROUP BY company_id
ORDER BY company_id;

-- 4. VERIFICAR STATUS USADOS EN CADA TABLA
-- -----------------------------------------------------------------
SELECT 
    'tasks' as tabla,
    status,
    COUNT(*) as cantidad
FROM tasks
GROUP BY status
ORDER BY cantidad DESC;

SELECT 
    'pt_tasks' as tabla,
    status,
    COUNT(*) as cantidad
FROM pt_tasks
GROUP BY status
ORDER BY cantidad DESC;

-- 5. VERIFICAR PRIORIDADES/NIVELES
-- -----------------------------------------------------------------
SELECT 
    'tasks (nivel)' as tabla,
    nivel,
    COUNT(*) as cantidad
FROM tasks
GROUP BY nivel
ORDER BY cantidad DESC;

SELECT 
    'pt_tasks (priority)' as tabla,
    priority,
    COUNT(*) as cantidad
FROM pt_tasks
GROUP BY priority
ORDER BY cantidad DESC;

-- 6. VERIFICAR TIPOS DE TAREAS
-- -----------------------------------------------------------------
SELECT 
    'tasks (tipo)' as tabla,
    tipo,
    COUNT(*) as cantidad
FROM tasks
GROUP BY tipo
ORDER BY cantidad DESC;

-- 7. VERIFICAR RELACIONES CON HR_EMPLOYEES
-- -----------------------------------------------------------------
-- En tasks: usuario_delegado y usuario_creador son user_id (no hr_employee_id)
SELECT 
    'tasks - Usuarios Delegados' as info,
    COUNT(DISTINCT t.usuario_delegado) as usuarios_unicos,
    COUNT(*) as tareas_con_delegado
FROM tasks t
WHERE t.usuario_delegado IS NOT NULL;

-- En pt_tasks: assignee_hr_id y delegate_hr_id son hr_employee_id directo
SELECT 
    'pt_tasks - Assignees HR' as info,
    COUNT(DISTINCT pt.assignee_hr_id) as empleados_unicos,
    COUNT(*) as tareas_con_assignee
FROM pt_tasks pt
WHERE pt.assignee_hr_id IS NOT NULL;

-- 8. VERIFICAR SI HAY DUPLICADOS (MISMO TÍTULO Y FECHA)
-- -----------------------------------------------------------------
SELECT 
    'POSIBLES DUPLICADOS EN tasks' as info,
    titulo,
    company_id,
    fecha_inicio,
    COUNT(*) as repeticiones
FROM tasks
GROUP BY titulo, company_id, fecha_inicio
HAVING COUNT(*) > 1
ORDER BY repeticiones DESC
LIMIT 10;

-- 9. SAMPLE DE DATOS DE CADA TABLA
-- -----------------------------------------------------------------
SELECT 
    'SAMPLE tasks (primeras 5)' as info;
SELECT 
    id,
    company_id,
    folio,
    titulo,
    tipo,
    status,
    nivel,
    usuario_creador,
    usuario_delegado,
    fecha_inicio,
    created_at
FROM tasks
ORDER BY id DESC
LIMIT 5;

SELECT 
    'SAMPLE pt_tasks (primeras 5)' as info;
SELECT 
    id,
    company_id,
    title,
    status,
    priority,
    assignee_hr_id,
    delegate_hr_id,
    start_date,
    created_at
FROM pt_tasks
ORDER BY id DESC
LIMIT 5;

-- 10. VERIFICAR REFERENCIAS HUÉRFANAS
-- -----------------------------------------------------------------
-- Verificar si usuario_delegado existe en users
SELECT 
    'tasks - Usuarios delegados huérfanos' as info,
    COUNT(*) as total
FROM tasks t
LEFT JOIN users u ON u.id = t.usuario_delegado
WHERE t.usuario_delegado IS NOT NULL 
  AND u.id IS NULL;

-- Verificar si assignee_hr_id existe en hr_employees
SELECT 
    'pt_tasks - HR employees huérfanos' as info,
    COUNT(*) as total
FROM pt_tasks pt
LEFT JOIN hr_employees e ON e.id = pt.assignee_hr_id
WHERE pt.assignee_hr_id IS NOT NULL 
  AND e.id IS NULL;

-- =================================================================
-- FIN DEL ANÁLISIS
-- =================================================================
-- INSTRUCCIONES:
-- 1. Ejecuta este script completo
-- 2. Revisa los resultados para determinar:
--    - ¿Qué tabla tiene más datos?
--    - ¿Hay duplicados entre tablas?
--    - ¿Los status/prioridades están normalizados?
-- 3. Guarda los resultados antes de ejecutar la migración
-- =================================================================
