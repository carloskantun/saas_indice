-- ========================================
-- AÑADIR FOREIGN KEYS - Módulo Processes & Tasks
-- ========================================
-- EJECUTAR SOLO DESPUÉS de crear las tablas base
-- Y VERIFICAR que los tipos de columna coinciden

-- Paso 1: Verificar tipos (ejecutar primero diagnose_fk.php)

-- Paso 2: Descomentar y ejecutar las FKs que sean compatibles

-- ========================================
-- Foreign Keys para tabla TASKS
-- ========================================

-- FK a companies (descomentar si companies.id es INT UNSIGNED)
-- ALTER TABLE tasks 
--   ADD CONSTRAINT fk_tasks_company 
--   FOREIGN KEY (company_id) REFERENCES companies(id) 
--   ON DELETE CASCADE;

-- FK a businesses (descomentar si businesses.id es INT UNSIGNED)
-- ALTER TABLE tasks 
--   ADD CONSTRAINT fk_tasks_business 
--   FOREIGN KEY (business_id) REFERENCES businesses(id) 
--   ON DELETE SET NULL;

-- FK a units (descomentar si units.id es INT UNSIGNED)
-- ALTER TABLE tasks 
--   ADD CONSTRAINT fk_tasks_unit 
--   FOREIGN KEY (unit_id) REFERENCES units(id) 
--   ON DELETE SET NULL;

-- FK a users creador (descomentar si users.id es INT UNSIGNED)
-- ALTER TABLE tasks 
--   ADD CONSTRAINT fk_tasks_creador 
--   FOREIGN KEY (usuario_creador) REFERENCES users(id) 
--   ON DELETE SET NULL;

-- FK a users delegado (descomentar si users.id es INT UNSIGNED)
-- ALTER TABLE tasks 
--   ADD CONSTRAINT fk_tasks_delegado 
--   FOREIGN KEY (usuario_delegado) REFERENCES users(id) 
--   ON DELETE SET NULL;

-- ========================================
-- Foreign Keys para tabla TASK_AUDIT
-- ========================================

-- Ya tiene FK a tasks (se crea automáticamente)

-- FK a users (descomentar si users.id es INT UNSIGNED)
-- ALTER TABLE task_audit 
--   ADD CONSTRAINT fk_audit_user 
--   FOREIGN KEY (usuario_id) REFERENCES users(id) 
--   ON DELETE SET NULL;

-- ========================================
-- Foreign Keys para tabla TASK_FILES
-- ========================================

-- Ya tiene FK a tasks (se crea automáticamente)

-- FK a users (descomentar si users.id es INT UNSIGNED)
-- ALTER TABLE task_files 
--   ADD CONSTRAINT fk_files_user 
--   FOREIGN KEY (uploaded_by) REFERENCES users(id) 
--   ON DELETE SET NULL;

-- ========================================
-- Verificar FKs creadas
-- ========================================
-- SELECT 
--   TABLE_NAME,
--   CONSTRAINT_NAME,
--   REFERENCED_TABLE_NAME,
--   REFERENCED_COLUMN_NAME
-- FROM information_schema.KEY_COLUMN_USAGE
-- WHERE TABLE_SCHEMA = DATABASE()
--   AND TABLE_NAME IN ('tasks', 'task_audit', 'task_files')
--   AND REFERENCED_TABLE_NAME IS NOT NULL;
