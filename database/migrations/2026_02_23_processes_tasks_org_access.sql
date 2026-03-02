-- 2026_02_23_processes_tasks_org_access.sql
-- FASE 2: Persistencia de accesos por organigrama (Processes & Tasks)
-- Tabla: processes_tasks_org_access
-- Registra qué usuarios (owner_user_id) pueden ver tareas de qué colaboradores (target_user_id)

CREATE TABLE IF NOT EXISTS `processes_tasks_org_access` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT NOT NULL,
  `owner_user_id` BIGINT NOT NULL,
  `target_user_id` BIGINT NOT NULL,
  `unit_id` BIGINT NULL,
  `business_id` BIGINT NULL,
  `created_by` BIGINT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ptoa_owner` (`company_id`, `owner_user_id`),
  KEY `idx_ptoa_owner_target` (`company_id`, `owner_user_id`, `target_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
