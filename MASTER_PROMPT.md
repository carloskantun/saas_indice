# Indice SaaS — Master Prompt (cPanel • PHP+MySQL • sin frameworks)

## Objetivo
Construir un ERP SaaS **multi-empresa y multi-usuario con permisos**,
instalable en **cPanel** (un solo “public_html”), usando **PHP 8 + MySQL + Bootstrap 5 + Vanilla JS** (sin frameworks).
El sistema tiene dos superficies:
- **Marketing/Registro** en **indiceapp.com/registro.php** (wizard por pasos + pago stub / futuro gateway).
- **Aplicación** en **app.indiceapp.com** (grid de módulos, paneles, HR, invitaciones, etc.).
Ambas comparten **la misma base de datos**.

## Reglas inquebrantables
1) **Sin frameworks** (no Laravel, no Symfony). Sólo PHP nativo, MySQL, Bootstrap 5, JS vanilla.
2) **Estructura single-root** para cPanel. Archivos internos en `/core`, `/config`, `/database` **bloqueados** por `.htaccess`.
3) Toda página pública debe incluir primero `bootstrap.php` y verificar `APP_BOOTSTRAPPED`.
4) **Cuentas Free**: pueden registrarse, pero **no** crean empresa ni usan módulos. Sirven para invitaciones o demo.
5) **Owner** (paga en registro): crea su **empresa** y queda como **superadmin**.
6) **Invitaciones**: **validar seats** antes de enviar; al invitar, **reservar asiento**; al aceptar, el usuario queda **activo**.
7) **Grid de módulos** = intersección (**plan** de la empresa) ∩ (**módulos asignados** al usuario).
8) **Autorización dentro del módulo** = `module_role` (viewer/contributor/approver/manager) + opcional `skill_level`.
9) **Visibilidad**: `external` ⇒ `visibility='assigned'` (sólo ve lo asignado a él).  
10) **Scope (Mi Alcance)**: cada usuario por empresa puede tener “Todos” o listas de Unidades/Negocios.  
11) **Filtros globales** (empresa/alcance/visibilidad) se aplican a **todas** las consultas y también a **PDF/Excel**.
12) **Nunca** crear `user_companies` en cuentas **Free**. Sólo via pago (owner) o **aceptar invitación**.

## Estructura (cPanel, una sola raíz)
public_html/
bootstrap.php # define APP_BOOTSTRAPPED y carga config
.htaccess # bloquea core/config/database
index.php # grid de módulos (app)
registro.php # wizard (marketing/registro; misma BD)

core/ # helpers (NO accesibles por HTTP directo)
auth.php
permissions.php
scope.php
plan.php
notify.php
mailer.php

admin/ # panel de empresa
invitations.php
accept_invitation.php
scope_set.php
settings.php

panel_root/ # panel root (SaaS)
modules.php
plans.php # (si no existe, generarlo)

modules/ # cada módulo
expenses/
index.php
controller.php
config.php

assets/
css/app.css
js/app.js

config/
.env.php # variables (DB, APP_URL, MAIL)
config.php # función db(), defines APP_URL/ENV/DEBUG

database/
migrations/2025_08_29_core_additions.sql
seeds/2025_08_29_seed_modules_and_plans.sql

markdown
Copiar código

## Seguridad
- `.htaccess` debe bloquear **/core**, **/config**, **/database**.
- Todos los archivos internos **empiezan** con:
  `<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>`

## Dominio/BD/Sesiones
- **Misma BD** para `indiceapp.com` (registro) y `app.indiceapp.com` (app).
- Sesiones separadas por dominio; el **login real** opera en `app.*`.

## Esquema de datos (núcleo + añadidos)
- Núcleo existente: `users`, `companies`, `plans`, `user_companies`, `permissions`, `role_permissions`, `invitations`, `notifications`, `units`, `businesses`.
- Añadidos (ver migración):
  - `user_profiles` (datos comunes: nombre, phone, RFC/DNI, etc.)
  - `signup_intents` (wizard de registro: `draft`→`awaiting_payment`→`paid`)
  - `modules` (catálogo visual: `slug`, `icon`, `badge_text`, `tier`, `sort_order`, `is_core`, `is_active`)
  - `user_module_favorites`, `menu_shortcuts`
  - **Extensión** `user_companies`: `visibility`, `status`
  - **Scope** por empresa: `user_company_scopes`, `user_company_scope_units`, `user_company_scope_businesses`
  - **Rol por módulo**: `user_company_module_roles` (rol + `skill_level`)
  - **Extensión** `invitations`: `seat_reserved`, `modules JSON`, `proposed_role`, `proposed_visibility`, `scope JSON`

> **Sugerencia para tablas de cada módulo**: incluir siempre  
`company_id`, `unit_id`, `business_id`, `created_by`, `assigned_to_user_id`.

## Lógica de acceso (pipeline)
1) Elegir **empresa actual** (navbar; si sólo hay una, ocultar selector).
2) **Plan** de la empresa: módulos incluidos (`*` o lista).
3) **Asignación** HR: módulos del usuario + `module_role` + `skill_level`.
4) **Grid** = (plan ∩ módulos asignados).
5) Dentro de cada módulo:
   - `canAction($module,$action,$moduleRole,$skillLevel)`  
   - `[$where,$params] = scopeWhereClause($userCompany,$filters)`  
   - Si `visibility='assigned'` ⇒ `AND assigned_to_user_id = :me`.

## Flujo de registro (indiceapp.com/registro.php)
- Paso 1: cuenta (Free u owner si paga).  
- Paso 2: negocio (empleados, unidades, sucursales).  
- Paso 3: módulos y plan propuesto.  
- Paso 4: pago (stub hoy; futuro gateway).  
- Al **pagar**: crear **empresa**, asociar **owner superadmin**, asignar **módulos** del plan con rol `manager`.

## Invitaciones (app)
- Verificar `seatsAvailable(company_id) > 0` para **enviar**.
- Guardar `modules`, `proposed_role`, `proposed_visibility`, `scope` y `seat_reserved=1`.
- Email + notificación.  
- Al **aceptar**: crear/activar `user_companies`, aplicar `scope`, crear `user_company_module_roles`, marcar `accepted` (seat ocupado).

## UX
- **Navbar**: selector de empresa (si >1) + botón **Hamburguesa** (offcanvas “Mi Alcance”, Paneles, Notificaciones, Perfil, Salir).
- **Landing (index.php)**: **grid de módulos** (icono, nombre, `badge_text`), orden: favoritos DESC → `sort_order` → nombre.
- **HR (Personas)**: alta/invitación; rol por empresa; asignación de módulos (rol/skill); alcance (Todos/listas).
- **Módulos**: filtros específicos sobre filtros globales ya aplicados; exportar **mismas consultas**; **sticky totals** al pie.

## No hacer
- No usar frameworks ni tooling incompatible con cPanel.
- No mover archivos fuera de `public_html/`.
- No generar endpoints sin `bootstrap.php` ni controles de seguridad.
