-- Seed: Datos de ejemplo para módulo Tareas
-- Fecha: 2025-11-09
-- Nota: Ajustar company_id, business_id, unit_id, usuario_creador, usuario_delegado según tu BD

INSERT INTO tasks (company_id, business_id, unit_id, folio, titulo, descripcion, fecha_inicio, fecha_fin, usuario_creador, usuario_delegado, nivel, tipo, proyecto_asignado, ponderacion, status)
VALUES 
(1, NULL, NULL, 'T-00001', 'Implementar dashboard de ventas', 'Crear dashboard con gráficas de ventas mensuales y KPIs principales', '2025-11-10', '2025-11-20', 1, 2, 'Importante', 'Tarea', 'Proyecto Alpha', 5, 'En proceso'),
(1, NULL, NULL, 'T-00002', 'Revisar proceso de facturación', 'Auditar y optimizar tiempos de emisión de facturas electrónicas', '2025-11-08', '2025-11-15', 1, 3, 'Urgente', 'Tarea', 'Mejora Continua', 4, 'En tiempo'),
(1, NULL, NULL, 'P-00001', 'Cierre mensual contable', 'Proceso recurrente: consolidación de estados financieros y cierre contable', '2025-11-01', '2025-11-30', 1, 2, 'Normal', 'Proceso', NULL, 3, 'En proceso');
