-- /modules/processes_tasks/includes/migrate_tasks_table.sql
-- Script de migración para crear tabla tasks y task_audit

-- Crear tabla tasks si no existe
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(20) UNIQUE NOT NULL,
    unidad VARCHAR(50),
    negocio VARCHAR(100),
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    fecha_inicio DATE,
    fecha_fin DATE,
    archivos_count INT DEFAULT 0,
    creador VARCHAR(100),
    delegado VARCHAR(100),
    nivel ENUM('Importante','Urgente','Normal','Bajo') DEFAULT 'Normal',
    tipo ENUM('Tarea','Proceso','Tarea de proceso') DEFAULT 'Tarea',
    proyecto VARCHAR(100),
    ponderacion TINYINT DEFAULT 3 CHECK (ponderacion BETWEEN 1 AND 5),
    status ENUM('En tiempo','En proceso','Terminada','Vencida','Auditada') DEFAULT 'En proceso',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    
    INDEX idx_folio (folio),
    INDEX idx_unidad (unidad),
    INDEX idx_status (status),
    INDEX idx_tipo (tipo),
    INDEX idx_fecha_inicio (fecha_inicio),
    INDEX idx_fecha_fin (fecha_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla task_audit si no existe
CREATE TABLE IF NOT EXISTS task_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    field VARCHAR(50) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    user_id INT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_task (task_id),
    INDEX idx_changed_at (changed_at),
    INDEX idx_user (user_id),
    
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar datos de ejemplo (opcional - comentar si no es necesario)
INSERT INTO tasks (folio, unidad, negocio, titulo, descripcion, fecha_inicio, fecha_fin, creador, delegado, nivel, tipo, proyecto, ponderacion, status)
VALUES 
('T-00001', 'Comercial', 'Netflix', 'Implementar nuevo dashboard', 'Dashboard con métricas de ventas y KPIs', '2024-01-15', '2024-02-15', 'Juan Pérez', 'Ana López', 'Importante', 'Tarea', 'Proyecto Alpha', 5, 'En proceso'),
('T-00002', 'Operaciones', 'Disney+', 'Optimizar proceso de facturación', 'Reducir tiempos de emisión de facturas', '2024-01-20', '2024-03-01', 'María García', 'Carlos Ruiz', 'Urgente', 'Proceso', 'Proyecto Beta', 4, 'En tiempo'),
('T-00003', 'RRHH', 'HBO Max', 'Actualizar políticas de trabajo remoto', 'Revisión y actualización del manual', '2024-02-01', '2024-02-28', 'Luis Martínez', 'Sofía Torres', 'Normal', 'Tarea', 'Proyecto Gamma', 3, 'Terminada'),
('T-00004', 'Finanzas', 'Paramount+', 'Cerrar mes contable', 'Consolidación de estados financieros', '2024-01-25', '2024-01-31', 'Andrea Sánchez', 'Roberto Díaz', 'Importante', 'Tarea de proceso', 'Proyecto Delta', 5, 'Vencida'),
('T-00005', 'Comercial', 'Netflix', 'Auditoria trimestral', 'Revisar compliance y procesos', '2024-01-10', '2024-01-20', 'Pedro Gómez', 'Laura Castro', 'Urgente', 'Proceso', 'Proyecto Epsilon', 4, 'Auditada');
