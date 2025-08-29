# Indice SaaS

ERP SaaS multi-empresa y multi-usuario instalable en **cPanel** usando **PHP 8**, **MySQL**, **Bootstrap 5** y **JS vanilla**. El proyecto tiene dos superficies:

- **Registro** en `indiceapp.com/registro.php` — wizard por pasos y pago stub.
- **Aplicación** en `app.indiceapp.com` — grid de módulos, paneles e invitaciones.

Ambas comparten la misma base de datos.

## Requisitos
- PHP 8+ con extensiones PDO y mbstring.
- MySQL 5.7+.
- Hosting cPanel con acceso a un `public_html`.

## Instalación
1. Subir todos los archivos a `public_html/`.
2. Crear base de datos MySQL.
3. Configurar credenciales en `config/.env.php`.
4. Ejecutar los SQL de `database/migrations` y luego los de `database/seeds`.

## Estructura y seguridad
```
public_html/
├── bootstrap.php
├── .htaccess
├── config/
├── core/
├── admin/
├── panel_root/
├── modules/
└── database/
```
`.htaccess` protege `core/`, `config/` y `database/`:
```apache
Options -Indexes
RewriteEngine On
RewriteRule ^(core|database|config)/ - [F,L]
```
Todas las páginas incluyen `bootstrap.php`:
```php
<?php
require __DIR__.'/bootstrap.php';
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
```

## Configuración
`config/.env.php`:
```php
<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
return [
    'APP_URL' => 'https://indiceapp.com',
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'indice',
    'DB_USER' => 'username',
    'DB_PASS' => 'password',
    'MAIL_FROM' => 'no-reply@indiceapp.com'
];
```

## Flujos clave
### Registro
`registro.php` ejecuta un wizard de 4 pasos que crea `signup_intents`. Al pagar (stub) marca `status='paid'` y redirige a la aplicación.

### Invitaciones
`admin/invitations.php` valida cupo con `seatsAvailable()` y reserva asiento (`seat_reserved=1`). `admin/accept_invitation.php` crea/activa `user_companies` y marca la invitación como aceptada.

## Permisos y alcance
- Roles por empresa: `root`, `superadmin`, `admin`, `moderator`, `user`, `external`.
- Roles por módulo: `viewer`, `contributor`, `approver`, `manager` más `skill_level`.
- Visibilidad: `all`, `scope`, `assigned`. "Mi Alcance" permite elegir empresa/unidad/negocio.

## Grid de módulos
`index.php` muestra tarjetas de módulos ordenadas por favorito → `sort_order` → nombre. Se incluyen sólo los módulos activos permitidos por el plan y asignados al usuario.

## Módulos
Cada módulo vive en `modules/<slug>/` con `index.php` y `controller.php`. El ejemplo `expenses` aplica filtros de alcance y reutiliza la misma consulta para exportar CSV.

## Tareas comunes
- Crear plan: `panel_root/plans.php`.
- Editar módulos: `panel_root/modules.php`.
- Invitar usuarios: `admin/invitations.php` y `admin/accept_invitation.php`.

## Roadmap
- Integración con gateway de pago real.
- CSRF tokens en formularios.
- Auditoría de acciones.

## Licencia
MIT — © 2024 Indice SaaS.

