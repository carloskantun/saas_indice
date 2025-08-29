## 1) config/.env.php y config/config.php
**Objetivo**: crear `.env.php` con APP_URL, DB, MAIL; `config.php` con `db(): PDO` y defines de entorno.

---

## 2) bootstrap.php
**Objetivo**: `define('APP_BOOTSTRAPPED',1); require __DIR__.'/config/config.php';`

---

## 3) .htaccess
**Contenido**:
Options -Indexes
RewriteEngine On
RewriteRule ^(core|database|config)/ - [F,L]

---

## 4) registro.php (indiceapp.com)
**Objetivo**: Wizard 4 pasos (cuenta → negocio → módulos/plan → pago stub) que crea/actualiza `signup_intents`.  
Al “pagar”: `status='paid'`, crea empresa, asocia owner `superadmin`, asigna módulos del plan (rol `manager`), redirige a APP_URL.  
**Reglas**:
- Cuenta Free válida (sin empresa).  
- Mismos nombres de campos que los prompts previos.  
- Estilo Bootstrap 5.

---

## 5) admin/invitations.php
**Objetivo**: Enviar invitaciones con validación de seats.  
**Acciones**:
- Si `seatsAvailable(company_id) <= 0` ⇒ bloquear.  
- Insertar invitación `pending`, `seat_reserved=1`, `modules JSON`, `proposed_role`, `proposed_visibility`, `scope JSON`.  
- Email con link a `admin/accept_invitation.php?token=...`.

---

## 6) admin/accept_invitation.php
**Objetivo**: Aceptar invitación.  
**Acciones**:
- Si usuario no existe ⇒ crearlo (Free).  
- Crear/activar `user_companies` con `role/visibility`.  
- Crear `user_company_scopes` y listas.  
- Crear/actualizar `user_company_module_roles`.  
- Marcar invitación `accepted`.

---

## 7) panel_root/modules.php
**Objetivo**: CRUD simple de `modules` (name, description, icon, badge_text, tier, sort_order, is_core, is_active).  
**Acceso**: sólo `root`.

---

## 8) index.php (app grid)
**Objetivo**: Pintar tarjetas de módulos (icon, name, badge), orden: favorito → sort_order → nombre.  
**Filtro**: sólo `is_core=1` o incluidos por plan, **y** asignados al usuario.  
**Acceso**: requiere login y empresa actual válida.

---

## 9) menu.php (offcanvas)
**Objetivo**: “Mi Alcance” (Empresa/Unidad/Negocio).  
- “Todos” sólo si flags `allow_all_*` lo permiten.  
- POST a `admin/scope_set.php` y recargar.

---

## 10) modules/expenses/index.php
**Objetivo**: Listado con filtros y rol de módulo.  
**Reglas**:
- `canAction('expenses','view', role, skill)`.  
- `scopeWhereClause($uc,$_GET,'e')`.  
- Misma query para export.

---

## 11) payments/webhook.php (stub)
**Objetivo**: Marcar `signup_intents.status='paid'` y llamar a `provisionCompanyFromIntent($intentId)` para crear empresa/owner.
