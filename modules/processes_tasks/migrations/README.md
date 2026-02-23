# Migraciones - Módulo Processes & Tasks

## 📋 Descripción

Este directorio contiene las migraciones SQL para crear las tablas necesarias del módulo de Tareas y Procesos.

## 🗄️ Tablas Creadas

1. **`tasks`** - Tabla principal de tareas, procesos y tareas de proceso
   - 17 columnas + índices optimizados
   - Foreign keys a: companies, businesses, units, users

2. **`task_audit`** - Auditoría de cambios
   - Registra todas las modificaciones (create, update, delete, duplicate, complete, audit)
   - Mantiene historial completo con old_value y new_value

3. **`task_files`** - Archivos adjuntos
   - Gestión de attachments por tarea
   - Metadatos: nombre original, mime_type, size, uploader

## 🚀 Ejecutar Migraciones

### Opción 1: Desde navegador web

```
http://tu-dominio.com/modules/processes_tasks/migrations/run_migrations.php?run=1
```

Para incluir datos de ejemplo (seed):
```
http://tu-dominio.com/modules/processes_tasks/migrations/run_migrations.php?run=1&seed=1
```

### Opción 2: Desde línea de comandos (CLI)

```bash
cd /path/to/indice_saas/modules/processes_tasks/migrations
php run_migrations.php
```

## 📊 Verificación

Después de ejecutar las migraciones, verifica que las tablas fueron creadas:

```sql
-- Contar tablas creadas
SELECT COUNT(*) FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name IN ('tasks', 'task_audit', 'task_files');

-- Ver estructura de tasks
DESCRIBE tasks;

-- Verificar datos de ejemplo (si ejecutaste seed)
SELECT COUNT(*) FROM tasks;

-- Ver tareas con nombres de creador y delegado
SELECT 
    t.id, 
    t.folio, 
    t.titulo, 
    t.tipo, 
    t.status,
    creador.full_name AS creador,
    delegado.full_name AS delegado,
    t.created_at
FROM tasks t
LEFT JOIN users creador ON creador.id = t.usuario_creador
LEFT JOIN users delegado ON delegado.id = t.usuario_delegado
ORDER BY t.created_at DESC
LIMIT 20;
```

## 🔄 Rollback (Deshacer Migraciones)

Si necesitas eliminar las tablas creadas:

### Opción 1: Ejecutar script de rollback

```bash
mysql -u tu_usuario -p tu_base_datos < 20251109_drop_tasks.sql
```

### Opción 2: Manualmente desde MySQL

```sql
DROP TABLE IF EXISTS task_files;
DROP TABLE IF EXISTS task_audit;
DROP TABLE IF EXISTS tasks;
```

**⚠️ ADVERTENCIA**: El rollback eliminará TODOS los datos de las tablas. Haz backup antes de ejecutar.

## 📝 Log de Migraciones

El runner genera un archivo `migrations.log` con el historial de ejecuciones:

```
[2025-11-09T10:30:15-06:00] OK: 20251109_create_tasks.sql
[2025-11-09T10:30:15-06:00] OK: seed_tasks.sql (opcional)
```

En caso de error:
```
[2025-11-09T10:30:15-06:00] ERROR: SQLSTATE[42S01]: Base table or view already exists
```

## 🔐 Requisitos Previos

Las siguientes tablas deben existir antes de ejecutar las migraciones:

- `companies` - Empresas del sistema
- `businesses` - Negocios (opcional, puede ser NULL)
- `units` - Unidades organizacionales (opcional, puede ser NULL)
- `users` - Usuarios del sistema

Estas tablas son parte del core de Índice ERP y ya deberían estar creadas.

## ⚙️ Configuración

El runner de migraciones utiliza la conexión PDO configurada en:
- `/bootstrap.php`
- `/core/db.php` (fallback)
- `/config/config.php` (credenciales)

Asegúrate de que las credenciales de base de datos estén correctamente configuradas antes de ejecutar.

## 🆘 Troubleshooting

### Error: "PDO no inicializado"
- Verifica que `bootstrap.php` cargue correctamente
- Revisa las credenciales en `config/config.php`
- Asegura que la función `db()` esté disponible

### Error: "Foreign key constraint fails"
- Verifica que las tablas padre existan: companies, businesses, units, users
- Ejecuta las migraciones del core primero

### Error: "Table already exists"
- Las tablas ya fueron creadas previamente
- Ejecuta rollback si necesitas recrearlas
- O ignora el error (las migraciones son idempotentes)

## 📚 Archivos en este Directorio

- `20251109_create_tasks.sql` - Migración principal (CREATE TABLE)
- `20251109_drop_tasks.sql` - Rollback (DROP TABLE)
- `20251109_seed_tasks.sql` - Datos de ejemplo (opcional)
- `run_migrations.php` - Runner automatizado
- `migrations.log` - Log de ejecuciones
- `README.md` - Esta documentación

## 🔗 Referencias

- Documentación del módulo: `/modules/processes_tasks/BACKEND_README.md`
- API Endpoints: Ver `controllers/api.controller.php`
- Modelos: Ver `models/tasks.model.php`

---

**Fecha última actualización**: 2025-11-09  
**Versión del módulo**: 0.1.0  
**Compatible con**: MySQL 5.7+, MariaDB 10.2+
