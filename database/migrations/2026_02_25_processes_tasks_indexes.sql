-- 2026_02_25_processes_tasks_indexes.sql
-- PR3: Performance index for enforcement-heavy queries in processes_tasks
-- Adds: idx_tasks_company_assignee ON tasks(company_id, usuario_delegado)
-- Idempotent, guarded by table/column existence.

SET @db := DATABASE();

-- Guard: table exists
SET @has_tasks := (
  SELECT COUNT(*)
  FROM information_schema.tables
  WHERE table_schema = @db
    AND table_name = 'tasks'
);

-- Guard: columns exist
SET @has_company_id := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = @db
    AND table_name = 'tasks'
    AND column_name = 'company_id'
);

SET @has_usuario_delegado := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = @db
    AND table_name = 'tasks'
    AND column_name = 'usuario_delegado'
);

-- ------------------------------------------------------------
-- tasks(company_id, usuario_delegado)
-- Acelera patrones tipo:
-- - WHERE company_id=? AND usuario_delegado IN (...)
-- - OR branch dentro de (usuario_delegado IN (...) OR usuario_creador = ?)
-- ------------------------------------------------------------
SET @has_idx := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = @db
    AND table_name = 'tasks'
    AND index_name = 'idx_tasks_company_assignee'
);

SET @sql := IF(
  @has_tasks = 1 AND @has_company_id = 1 AND @has_usuario_delegado = 1 AND @has_idx = 0,
  'ALTER TABLE tasks ADD INDEX idx_tasks_company_assignee (company_id, usuario_delegado)',
  'SELECT "SKIP: tasks/columns missing OR index already exists" AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- Opcional (follow-up): tasks(company_id, usuario_creador)
-- Agregar solo si EXPLAIN justifica el OR-cost.
-- ------------------------------------------------------------
/*
SET @has_usuario_creador := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = @db
    AND table_name = 'tasks'
    AND column_name = 'usuario_creador'
);

SET @has_idx := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = @db
    AND table_name = 'tasks'
    AND index_name = 'idx_tasks_company_creator'
);

SET @sql := IF(
  @has_tasks = 1 AND @has_company_id = 1 AND @has_usuario_creador = 1 AND @has_idx = 0,
  'ALTER TABLE tasks ADD INDEX idx_tasks_company_creator (company_id, usuario_creador)',
  'SELECT "SKIP: tasks/columns missing OR index already exists" AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
*/
