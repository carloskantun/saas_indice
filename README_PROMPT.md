# Genera README.md para Indice SaaS (cPanel, PHP+MySQL, sin frameworks)

## Contenido requerido
1. **Resumen** del producto y superficies (registro en indiceapp.com vs app en app.indiceapp.com).
2. **Requisitos** (PHP 8+, extensiones, MySQL, cPanel).
3. **Instalación** (subir ZIP a `public_html`, crear BD, configurar `config/.env.php`, ejecutar SQL de `database/migrations` y `database/seeds`).
4. **Estructura de carpetas** (single-root) y **seguridad** (.htaccess + APP_BOOTSTRAPPED).
5. **Configuración** (`config/.env.php` con APP_URL/DB/MAIL).
6. **Flujos clave**:
   - Registro (wizard `registro.php` → pago stub → crea empresa y owner).
   - Invitaciones (validar seats, reservar asiento, aceptar).
7. **Permisos y alcance**:
   - Roles por empresa (root/superadmin/admin/moderator/user/external).
   - Roles por módulo (viewer/contributor/approver/manager) + `skill_level`.
   - Visibilidad (`all/scope/assigned`) y “Mi Alcance” (unidades/negocios).
8. **Grid de módulos** (intersección plan ∩ asignación).
9. **Módulos**: estructura estándar y patrón de filtros/export.
10. **Tareas comunes** (crear plan, editar módulos, invitar usuarios).
11. **Roadmap breve** (gateway de pago real, CSRF, audit log, etc.).
12. **Licencia** y crédito.

## Estilo
- Español neutro, directo, con bloques de código para ejemplos.
- Incluir snippets mínimos (ej. `.htaccess`, `bootstrap.php`, `.env.php`).
