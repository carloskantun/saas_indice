-- Processes & Tasks: gradual normalization to canonical users.id
--
-- Goal: convert tasks.usuario_creador / tasks.usuario_delegado to users.id where
-- current values are NOT a valid active membership users.id, but ARE resolvable
-- as hr_employees.id within the same company with active membership.
--
-- Rollback note: this migration updates IDs in-place.
-- Take a backup/snapshot of tasks (id, company_id, usuario_creador, usuario_delegado)
-- before applying in production.

-- =============================================================
-- BEFORE COUNTS
-- =============================================================

-- A) Delegado candidates to fix
SELECT
  COUNT(*) AS to_fix_usuario_delegado
FROM tasks t
LEFT JOIN user_companies uc_direct
  ON uc_direct.user_id = t.usuario_delegado
 AND uc_direct.company_id = t.company_id
 AND uc_direct.status = 'active'
JOIN hr_employees he
  ON he.id = t.usuario_delegado
 AND he.company_id = t.company_id
 AND he.user_id IS NOT NULL
JOIN user_companies uc_resolved
  ON uc_resolved.user_id = he.user_id
 AND uc_resolved.company_id = t.company_id
 AND uc_resolved.status = 'active'
WHERE t.usuario_delegado IS NOT NULL
  AND uc_direct.user_id IS NULL;

-- B) Creador candidates to fix
SELECT
  COUNT(*) AS to_fix_usuario_creador
FROM tasks t
LEFT JOIN user_companies uc_direct
  ON uc_direct.user_id = t.usuario_creador
 AND uc_direct.company_id = t.company_id
 AND uc_direct.status = 'active'
JOIN hr_employees he
  ON he.id = t.usuario_creador
 AND he.company_id = t.company_id
 AND he.user_id IS NOT NULL
JOIN user_companies uc_resolved
  ON uc_resolved.user_id = he.user_id
 AND uc_resolved.company_id = t.company_id
 AND uc_resolved.status = 'active'
WHERE t.usuario_creador IS NOT NULL
  AND uc_direct.user_id IS NULL;

-- =============================================================
-- UPDATE (SAFE)
-- =============================================================

-- 1) Delegado: convert hr_employees.id -> users.id
UPDATE tasks t
JOIN hr_employees he
  ON he.id = t.usuario_delegado
 AND he.company_id = t.company_id
 AND he.user_id IS NOT NULL
JOIN user_companies uc_resolved
  ON uc_resolved.user_id = he.user_id
 AND uc_resolved.company_id = t.company_id
 AND uc_resolved.status = 'active'
LEFT JOIN user_companies uc_direct
  ON uc_direct.user_id = t.usuario_delegado
 AND uc_direct.company_id = t.company_id
 AND uc_direct.status = 'active'
SET t.usuario_delegado = he.user_id
WHERE t.usuario_delegado IS NOT NULL
  AND uc_direct.user_id IS NULL;

-- 2) Creador: convert hr_employees.id -> users.id
UPDATE tasks t
JOIN hr_employees he
  ON he.id = t.usuario_creador
 AND he.company_id = t.company_id
 AND he.user_id IS NOT NULL
JOIN user_companies uc_resolved
  ON uc_resolved.user_id = he.user_id
 AND uc_resolved.company_id = t.company_id
 AND uc_resolved.status = 'active'
LEFT JOIN user_companies uc_direct
  ON uc_direct.user_id = t.usuario_creador
 AND uc_direct.company_id = t.company_id
 AND uc_direct.status = 'active'
SET t.usuario_creador = he.user_id
WHERE t.usuario_creador IS NOT NULL
  AND uc_direct.user_id IS NULL;

-- =============================================================
-- AFTER COUNTS (should decrease)
-- =============================================================

SELECT
  COUNT(*) AS remaining_to_fix_usuario_delegado
FROM tasks t
LEFT JOIN user_companies uc_direct
  ON uc_direct.user_id = t.usuario_delegado
 AND uc_direct.company_id = t.company_id
 AND uc_direct.status = 'active'
JOIN hr_employees he
  ON he.id = t.usuario_delegado
 AND he.company_id = t.company_id
 AND he.user_id IS NOT NULL
JOIN user_companies uc_resolved
  ON uc_resolved.user_id = he.user_id
 AND uc_resolved.company_id = t.company_id
 AND uc_resolved.status = 'active'
WHERE t.usuario_delegado IS NOT NULL
  AND uc_direct.user_id IS NULL;

SELECT
  COUNT(*) AS remaining_to_fix_usuario_creador
FROM tasks t
LEFT JOIN user_companies uc_direct
  ON uc_direct.user_id = t.usuario_creador
 AND uc_direct.company_id = t.company_id
 AND uc_direct.status = 'active'
JOIN hr_employees he
  ON he.id = t.usuario_creador
 AND he.company_id = t.company_id
 AND he.user_id IS NOT NULL
JOIN user_companies uc_resolved
  ON uc_resolved.user_id = he.user_id
 AND uc_resolved.company_id = t.company_id
 AND uc_resolved.status = 'active'
WHERE t.usuario_creador IS NOT NULL
  AND uc_direct.user_id IS NULL;

-- =============================================================
-- SUMMARY: % delegado normalized to active membership users.id
-- =============================================================

SELECT
  SUM(CASE WHEN t.usuario_delegado IS NOT NULL THEN 1 ELSE 0 END) AS total_with_delegado,
  SUM(CASE WHEN t.usuario_delegado IS NOT NULL AND uc.user_id IS NOT NULL THEN 1 ELSE 0 END) AS delegado_with_active_membership,
  ROUND(
    (SUM(CASE WHEN t.usuario_delegado IS NOT NULL AND uc.user_id IS NOT NULL THEN 1 ELSE 0 END) / NULLIF(SUM(CASE WHEN t.usuario_delegado IS NOT NULL THEN 1 ELSE 0 END), 0)) * 100,
    2
  ) AS pct_delegado_normalized
FROM tasks t
LEFT JOIN user_companies uc
  ON uc.user_id = t.usuario_delegado
 AND uc.company_id = t.company_id
 AND uc.status = 'active';
