-- Migración: Sistema de Tareas y Procesos
-- Fecha: 2025-11-09
-- Compatible con MySQL 5.7+

-- Tasks (tabla única para tareas / procesos / tareas de proceso)
CREATE TABLE IF NOT EXISTS tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  business_id INT NULL,
  unit_id INT NULL,
  folio VARCHAR(50) NOT NULL,
  titulo VARCHAR(255) NOT NULL,
  descripcion TEXT NULL,
  fecha_inicio DATE NULL,
  fecha_fin DATE NULL,
  archivos TEXT NULL,            -- JSON de adjuntos o rutas serializadas
  usuario_creador INT NULL,
  usuario_delegado INT NULL,
  nivel ENUM('Normal','Importante','Urgente') DEFAULT 'Normal',
  tipo ENUM('Tarea','Proceso','Tarea de proceso') DEFAULT 'Tarea',
  proyecto_asignado VARCHAR(150) NULL,
  ponderacion TINYINT UNSIGNED DEFAULT 1,
  status ENUM('En tiempo','En proceso','Terminada','Vencida','Auditada') DEFAULT 'En tiempo',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY ux_tasks_folio (folio),
  KEY ix_tasks_company (company_id),
  KEY ix_tasks_business (business_id),
  KEY ix_tasks_unit (unit_id),
  KEY ix_tasks_tipo (tipo),
  KEY ix_tasks_status (status),
  CONSTRAINT fk_tasks_company  FOREIGN KEY (company_id)     REFERENCES companies(id)  ON DELETE CASCADE,
  CONSTRAINT fk_tasks_business FOREIGN KEY (business_id)    REFERENCES businesses(id) ON DELETE SET NULL,
  CONSTRAINT fk_tasks_unit     FOREIGN KEY (unit_id)        REFERENCES units(id)      ON DELETE SET NULL,
  CONSTRAINT fk_tasks_creador  FOREIGN KEY (usuario_creador) REFERENCES users(id)     ON DELETE SET NULL,
  CONSTRAINT fk_tasks_delegado FOREIGN KEY (usuario_delegado) REFERENCES users(id)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Auditoría de cambios
CREATE TABLE IF NOT EXISTS task_audit (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  usuario_id INT NULL,
  accion VARCHAR(50) NOT NULL,     -- create|update|delete|duplicate|complete|audit
  field VARCHAR(100) NULL,
  old_value TEXT NULL,
  new_value TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY ix_audit_task (task_id),
  CONSTRAINT fk_audit_task   FOREIGN KEY (task_id)   REFERENCES tasks(id) ON DELETE CASCADE,
  CONSTRAINT fk_audit_user   FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Archivos adjuntos de tareas
CREATE TABLE IF NOT EXISTS task_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  ruta VARCHAR(255) NOT NULL,
  nombre_original VARCHAR(255) NULL,
  uploaded_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY ix_files_task (task_id),
  CONSTRAINT fk_files_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  CONSTRAINT fk_files_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
