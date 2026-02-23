-- Migración: Sistema de Tareas y Procesos - VERSIÓN CORREGIDA
-- Fecha: 2025-11-09
-- Compatible con MySQL 5.7+
-- NOTA: Foreign Keys comentadas temporalmente - se añadirán después de verificar tipos

-- ========================================
-- Tasks (tabla única para tareas / procesos / tareas de proceso)
-- ========================================
CREATE TABLE IF NOT EXISTS tasks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id INT UNSIGNED NOT NULL,
  business_id INT UNSIGNED NULL,
  unit_id INT UNSIGNED NULL,
  folio VARCHAR(50) NOT NULL,
  titulo VARCHAR(255) NOT NULL,
  descripcion TEXT NULL,
  fecha_inicio DATE NULL,
  fecha_fin DATE NULL,
  archivos TEXT NULL COMMENT 'JSON de adjuntos o rutas serializadas',
  usuario_creador INT UNSIGNED NULL,
  usuario_delegado INT UNSIGNED NULL,
  nivel ENUM('Normal','Importante','Urgente') DEFAULT 'Normal',
  tipo ENUM('Tarea','Proceso','Tarea de proceso') DEFAULT 'Tarea',
  proyecto_asignado VARCHAR(150) NULL,
  ponderacion TINYINT UNSIGNED DEFAULT 1,
  status ENUM('En tiempo','En proceso','Terminada','Vencida','Auditada','Pausada') DEFAULT 'En tiempo',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  UNIQUE KEY ux_tasks_folio (company_id, folio),
  KEY ix_tasks_company (company_id),
  KEY ix_tasks_business (business_id),
  KEY ix_tasks_unit (unit_id),
  KEY ix_tasks_tipo (tipo),
  KEY ix_tasks_status (status),
  KEY ix_tasks_creador (usuario_creador),
  KEY ix_tasks_delegado (usuario_delegado)
  
  -- FOREIGN KEYS: Se añadirán manualmente después de verificar tipos
  -- TODO: Descomentar solo si las tablas referenciadas usan INT UNSIGNED
  -- CONSTRAINT fk_tasks_company  FOREIGN KEY (company_id)       REFERENCES companies(id)  ON DELETE CASCADE,
  -- CONSTRAINT fk_tasks_business FOREIGN KEY (business_id)      REFERENCES businesses(id) ON DELETE SET NULL,
  -- CONSTRAINT fk_tasks_unit     FOREIGN KEY (unit_id)          REFERENCES units(id)      ON DELETE SET NULL,
  -- CONSTRAINT fk_tasks_creador  FOREIGN KEY (usuario_creador)  REFERENCES users(id)      ON DELETE SET NULL,
  -- CONSTRAINT fk_tasks_delegado FOREIGN KEY (usuario_delegado) REFERENCES users(id)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Auditoría de cambios
-- ========================================
CREATE TABLE IF NOT EXISTS task_audit (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NULL,
  accion VARCHAR(50) NOT NULL COMMENT 'create|update|delete|duplicate|complete|audit',
  field VARCHAR(100) NULL,
  old_value TEXT NULL,
  new_value TEXT NULL,
  comentario TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  KEY ix_audit_task (task_id),
  KEY ix_audit_user (usuario_id),
  KEY ix_audit_accion (accion),
  
  CONSTRAINT fk_audit_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
  -- TODO: Verificar tipo de users.id antes de descomentar
  -- CONSTRAINT fk_audit_user FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Archivos adjuntos de tareas
-- ========================================
CREATE TABLE IF NOT EXISTS task_files (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_id INT UNSIGNED NOT NULL,
  ruta VARCHAR(500) NOT NULL,
  nombre_original VARCHAR(255) NULL,
  mime_type VARCHAR(100) NULL,
  size_bytes INT UNSIGNED NULL,
  uploaded_by INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  KEY ix_files_task (task_id),
  KEY ix_files_uploaded (uploaded_by),
  
  CONSTRAINT fk_files_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
  -- TODO: Verificar tipo de users.id antes de descomentar
  -- CONSTRAINT fk_files_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Verificación post-migración
-- ========================================
-- SELECT 
--   'tasks' as tabla, 
--   COUNT(*) as registros 
-- FROM tasks
-- UNION ALL
-- SELECT 'task_audit', COUNT(*) FROM task_audit
-- UNION ALL
-- SELECT 'task_files', COUNT(*) FROM task_files;
