# Agentes para Codex/Copilot — Instrucciones

## 0) Convenciones globales (para **todos** los agentes)
- Stack: **PHP 8 + MySQL + Bootstrap 5 + JS vanilla**.
- **cPanel, single-root**: todo bajo `public_html/`. Bloquear `/core`, `/config`, `/database` en `.htaccess`.
- Cada archivo interno empieza con `if (!defined('APP_BOOTSTRAPPED')) exit;`.
- Usar `db(): PDO` de `config/config.php`.
- Respetar **Reglas Inquebrantables** del `MASTER_PROMPT.md`.

---

## A) Scaffold Agent
**Objetivo**: crear estructura de carpetas/archivos vacíos y colocar protectores .htaccess y `bootstrap.php`.

**Entradas**: `MASTER_PROMPT.md` (árbol), `.env.php` de ejemplo.  
**Salidas**:
- `bootstrap.php`, `.htaccess`, `config/config.php`, `config/.env.php` (si faltan).
- Carpetas con `index.php` vacíos mínimos en `admin/`, `panel_root/`, `modules/expenses/`.

**No hacer**: frameworks, composer extra; no mover fuera de `public_html`.

---

## B) DB Agent
**Objetivo**: aplicar **migraciones** y **seeds** tal como están en `database/`.

**Entradas**: SQL provistos.  
**Salidas**: DB con tablas y datos de módulos/planes.

**Checks**: columnas nuevas en `user_companies`, tablas de scope y roles por módulo, `modules`, seeds de planes.

---

## C) Auth & Session Agent
**Objetivo**: asegurar que páginas llamen a `bootstrap.php`, gestionar `session_start()`, y flujo mínimo de login/logout (si no existe, crear `login.php` simple).

**Checks**: redirecciones a `login.php` cuando falta `$_SESSION['user_id']`.

---

## D) Panel Root Agent
**Objetivo**: `panel_root/modules.php` y `panel_root/plans.php` (CRUDs simples).  
**Entradas**: tablas `modules` y `plans`.  
**Salidas**: edición de `badge_text`, `tier`, `sort_order`, `is_core`, `is_active`; edición de `plans.modules_included`.

**Checks**: sólo accesible con rol **root**.

---

## E) HR/Invitations Agent
**Objetivo**: `admin/invitations.php` (crear y reservar seat) y `admin/accept_invitation.php` (aceptar).  
**Entradas**: `plan.seatsAvailable()`, `reserveSeat()`, `releaseSeat()`, `invitations` extendida.  
**Salidas**:
- Enviar invitación (valida cupo, guarda `modules`, `proposed_role`, `proposed_visibility`, `scope`, `seat_reserved=1`).
- Aceptar (crea/actualiza `user_companies`, scopes, roles de módulo, marca `accepted`).  
**No hacer**: exceder el cupo.

---

## F) UI Agent — Grid & Menú
**Objetivo**: `index.php` (grid) + `menu.php` (offcanvas “Mi Alcance”).  
**Entradas**: `modules`, `user_module_favorites`, helpers `planAllowsModule()`, `hasPermission()`/`getUserModuleRole()`.  
**Salidas**:
- Grid = intersección (plan ∩ asignación), ordenado por favorito → `sort_order` → nombre.
- Offcanvas con selectores de Empresa/Unidad/Negocio; “Todos” sólo si flags lo permiten; POST a `admin/scope_set.php`.

---

## G) Module Agent — Expenses ejemplo
**Objetivo**: `modules/expenses/index.php` y `controller.php`.  
**Reglas**:
- `auth()`, `currentUserCompany()`, `getUserModuleRole(uc_id,'expenses')`.
- `if (!canAction('expenses','view', role, skill))` ⇒ 403.
- `[$where,$params] = scopeWhereClause($uc,$_GET,'e')`.
- Query: `SELECT e.* FROM expenses e $where ORDER BY e.pay_date DESC LIMIT 200`.
- Export PDF/Excel **reutiliza** la misma query (mismos `WHERE`).

---

## H) Payments Agent (stub + webhook)
**Objetivo**: mantén stub en `registro.php` y crea `payments/webhook.php`.  
**Salida**: `provisionCompanyFromIntent($intentId)` para crear empresa y owner al recibir `paid`.

---

## I) Exports Agent
**Objetivo**: helper mínimo para CSV/XLSX/PDF desde la **misma** consulta de listado.

---

## J) QA Agent
**Checklist**:
- `.htaccess` bloquea `/core`, `/config`, `/database`.
- Todas las páginas llaman a `bootstrap.php`.
- Grid cumple (plan ∩ asignación).
- Invitaciones respetan cupo.
- `external` sólo ve `assigned`.
- Export = misma query que listado.
