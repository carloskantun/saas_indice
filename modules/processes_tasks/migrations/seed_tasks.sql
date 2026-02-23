-- ==========================================
-- SEED DE DATOS: Tareas de Ejemplo
-- Sistema Índice SaaS - Módulo Procesos & Tareas
-- ==========================================

-- Este script inserta 6 tareas de ejemplo para probar
-- la funcionalidad del módulo en las vistas:
-- - tasks.php (Agenda)
-- - processes.php (Procesos)
-- - dashboard.php (Vista general)

-- IMPORTANTE: Ajustar los siguientes IDs según tu BD:
-- - company_id (1 por defecto)
-- - unit_id (usar IDs existentes de units)
-- - business_id (usar IDs existentes de businesses)
-- - usuario_creador / usuario_delegado (IDs de hr_employees)

SET @company_id = 1;
SET @unit_id_1 = (SELECT id FROM units WHERE company_id = @company_id LIMIT 1);
SET @business_id_1 = (SELECT id FROM businesses WHERE company_id = @company_id LIMIT 1);
SET @employee_id_1 = (SELECT id FROM hr_employees WHERE company_id = @company_id LIMIT 1);

-- ==========================================
-- TAREA 1: Revisión de Inventario (Urgente)
-- ==========================================
INSERT INTO tasks (
    company_id,
    unit_id,
    business_id,
    folio,
    titulo,
    descripcion,
    fecha_inicio,
    fecha_entrega,
    fecha_fin,
    usuario_creador,
    usuario_delegado,
    nivel,
    tipo,
    proyecto_asignado,
    ponderacion,
    status,
    created_at,
    updated_at
) VALUES (
    @company_id,
    @unit_id_1,
    @business_id_1,
    'T-2025-001',
    'Revisión mensual de inventario',
    'Realizar conteo físico de inventario y actualizar sistema con discrepancias encontradas. Prioridad alta por cierre de mes.',
    DATE_SUB(CURDATE(), INTERVAL 2 DAY),
    DATE_ADD(CURDATE(), INTERVAL 1 DAY),
    NULL,
    @employee_id_1,
    @employee_id_1,
    'Urgente',
    'Tarea',
    'Optimización Operativa Q1 2025',
    3,
    'En tiempo',
    NOW(),
    NOW()
);

-- ==========================================
-- TAREA 2: Actualizar documentación (Normal)
-- ==========================================
INSERT INTO tasks (
    company_id,
    unit_id,
    business_id,
    folio,
    titulo,
    descripcion,
    fecha_inicio,
    fecha_entrega,
    fecha_fin,
    usuario_creador,
    usuario_delegado,
    nivel,
    tipo,
    proyecto_asignado,
    ponderacion,
    status,
    created_at,
    updated_at
) VALUES (
    @company_id,
    @unit_id_1,
    @business_id_1,
    'T-2025-002',
    'Actualizar manual de procedimientos',
    'Revisar y actualizar la documentación del módulo de gastos según los cambios implementados en la última versión.',
    CURDATE(),
    DATE_ADD(CURDATE(), INTERVAL 7 DAY),
    NULL,
    @employee_id_1,
    NULL,
    'Normal',
    'Tarea',
    NULL,
    2,
    'En tiempo',
    NOW(),
    NOW()
);

-- ==========================================
-- TAREA 3: Capacitación de personal (Importante)
-- ==========================================
INSERT INTO tasks (
    company_id,
    unit_id,
    business_id,
    folio,
    titulo,
    descripcion,
    fecha_inicio,
    fecha_entrega,
    fecha_fin,
    usuario_creador,
    usuario_delegado,
    nivel,
    tipo,
    proyecto_asignado,
    ponderacion,
    status,
    created_at,
    updated_at
) VALUES (
    @company_id,
    @unit_id_1,
    @business_id_1,
    'T-2025-003',
    'Capacitación sobre nuevo sistema de tareas',
    'Impartir sesión de capacitación al equipo sobre el uso del nuevo módulo de procesos y tareas. Incluir ejemplos prácticos.',
    DATE_ADD(CURDATE(), INTERVAL 1 DAY),
    DATE_ADD(CURDATE(), INTERVAL 5 DAY),
    NULL,
    @employee_id_1,
    @employee_id_1,
    'Importante',
    'Proceso',
    'Transformación Digital 2025',
    2,
    'En tiempo',
    NOW(),
    NOW()
);

-- ==========================================
-- TAREA 4: Mantenimiento preventivo (Normal)
-- ==========================================
INSERT INTO tasks (
    company_id,
    unit_id,
    business_id,
    folio,
    titulo,
    descripcion,
    fecha_inicio,
    fecha_entrega,
    fecha_fin,
    usuario_creador,
    usuario_delegado,
    nivel,
    tipo,
    proyecto_asignado,
    ponderacion,
    status,
    created_at,
    updated_at
) VALUES (
    @company_id,
    @unit_id_1,
    @business_id_1,
    'T-2025-004',
    'Mantenimiento de servidores',
    'Realizar mantenimiento preventivo de servidores, incluyendo actualizaciones de seguridad y limpieza de logs.',
    DATE_ADD(CURDATE(), INTERVAL 3 DAY),
    DATE_ADD(CURDATE(), INTERVAL 10 DAY),
    NULL,
    @employee_id_1,
    NULL,
    'Normal',
    'Proceso',
    NULL,
    1,
    'En tiempo',
    NOW(),
    NOW()
);

-- ==========================================
-- TAREA 5: Auditoría interna (Importante)
-- ==========================================
INSERT INTO tasks (
    company_id,
    unit_id,
    business_id,
    folio,
    titulo,
    descripcion,
    fecha_inicio,
    fecha_entrega,
    fecha_fin,
    usuario_creador,
    usuario_delegado,
    nivel,
    tipo,
    proyecto_asignado,
    ponderacion,
    status,
    created_at,
    updated_at
) VALUES (
    @company_id,
    @unit_id_1,
    @business_id_1,
    'T-2025-005',
    'Auditoría de procesos financieros',
    'Realizar auditoría interna de los procesos financieros del primer trimestre. Generar reporte con hallazgos y recomendaciones.',
    DATE_SUB(CURDATE(), INTERVAL 5 DAY),
    DATE_ADD(CURDATE(), INTERVAL 2 DAY),
    NULL,
    @employee_id_1,
    @employee_id_1,
    'Importante',
    'Proceso',
    'Compliance 2025',
    3,
    'En tiempo',
    NOW(),
    NOW()
);

-- ==========================================
-- TAREA 6: Tarea completada (Ejemplo histórico)
-- ==========================================
INSERT INTO tasks (
    company_id,
    unit_id,
    business_id,
    folio,
    titulo,
    descripcion,
    fecha_inicio,
    fecha_entrega,
    fecha_fin,
    usuario_creador,
    usuario_delegado,
    nivel,
    tipo,
    proyecto_asignado,
    ponderacion,
    status,
    created_at,
    updated_at
) VALUES (
    @company_id,
    @unit_id_1,
    @business_id_1,
    'T-2025-006',
    'Migración de base de datos',
    'Completada migración exitosa de base de datos a nuevo servidor. Sin incidencias reportadas.',
    DATE_SUB(CURDATE(), INTERVAL 10 DAY),
    DATE_SUB(CURDATE(), INTERVAL 3 DAY),
    DATE_SUB(CURDATE(), INTERVAL 2 DAY),
    @employee_id_1,
    @employee_id_1,
    'Urgente',
    'Proceso',
    'Infraestructura Cloud',
    3,
    'Completada',
    DATE_SUB(NOW(), INTERVAL 10 DAY),
    DATE_SUB(NOW(), INTERVAL 2 DAY)
);

-- ==========================================
-- VERIFICACIÓN
-- ==========================================
SELECT 
    '✓ Seed completado' AS status,
    COUNT(*) AS tareas_insertadas,
    COUNT(CASE WHEN status = 'En tiempo' THEN 1 END) AS en_tiempo,
    COUNT(CASE WHEN status = 'Completada' THEN 1 END) AS completadas,
    COUNT(CASE WHEN nivel = 'Urgente' THEN 1 END) AS urgentes,
    COUNT(CASE WHEN nivel = 'Importante' THEN 1 END) AS importantes
FROM tasks
WHERE folio LIKE 'T-2025-%';

-- ==========================================
-- NOTAS DE USO
-- ==========================================
-- Para ejecutar este script:
-- 1. Verificar que existan registros en: units, businesses, hr_employees
-- 2. Ajustar @company_id si es diferente de 1
-- 3. Ejecutar en MySQL/MariaDB:
--    mysql -u usuario -p database < seed_tasks.sql
--
-- Para limpiar (eliminar tareas de prueba):
-- DELETE FROM tasks WHERE folio LIKE 'T-2025-%';
-- ==========================================
