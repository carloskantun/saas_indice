# saas_indice

0) Estructura (cPanel, single-root)

Coloca estos archivos en:

public_html/
  bootstrap.php
  .htaccess
  index.php                         # grid de módulos
  registro.php                      # (en indiceapp.com) wizard de alta/checkout
  core/
    auth.php
    permissions.php
    scope.php
    plan.php
    notify.php
    mailer.php
  admin/
    invitations.php
    accept_invitation.php
    scope_set.php
    settings.php
  panel_root/
    modules.php
    plans.php                       # (si no lo tienes, lo añadimos después)
  modules/
    expenses/
      index.php
      controller.php
      config.php
  assets/
    css/app.css
    js/app.js
  config/
    config.php
    .env.php
  database/
    migrations/2025_08_29_core_additions.sql
    seeds/2025_08_29_seed_modules_and_plans.sql


bootstrap.php (asegura que nada interno cargue por HTTP directo):

<?php
define('APP_BOOTSTRAPPED', 1);
require __DIR__.'/config/config.php';


.htaccess (bloquear acceso web a carpetas internas):

Options -Indexes
RewriteEngine On
RewriteRule ^(core|database|config)/ - [F,L]

1) SQL — migraciones y seeds

database/migrations/2025_08_29_core_additions.sql

-- PERFIL DE USUARIO (datos que precargan invitaciones)
CREATE TABLE IF NOT EXISTS user_profiles (
  user_id INT PRIMARY KEY,
  full_name VARCHAR(150) NULL,
  birthday DATE NULL,
  phone VARCHAR(30) NULL,
  tax_id VARCHAR(50) NULL,   -- RFC/DNI
  address JSON NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- INTENTOS DE ALTA (wizard registro en indiceapp.com)
CREATE TABLE IF NOT EXISTS signup_intents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  form_data JSON NOT NULL,          -- empleados, unidades, módulos, etc.
  suggested_plan_id INT NULL,
  status ENUM('draft','awaiting_payment','paid','abandoned') DEFAULT 'draft',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- MÓDULOS (catálogo visual + badges)
CREATE TABLE IF NOT EXISTS modules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(64) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(255) NULL,
  icon VARCHAR(100) NULL,              -- ej: "bi bi-people"
  badge_text VARCHAR(32) NULL,         -- "Básico","Pro",...
  tier ENUM('basic','pro','enterprise') DEFAULT 'basic',
  sort_order INT DEFAULT 100,
  is_core TINYINT(1) DEFAULT 0,        -- 1=siempre visible si permiso
  is_active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- FAVORITOS DE MÓDULO (para ordenar el grid)
CREATE TABLE IF NOT EXISTS user_module_favorites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  module_id INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_module (user_id, module_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ATAJOS PERSONALES EN MENÚ
CREATE TABLE IF NOT EXISTS menu_shortcuts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  label VARCHAR(100) NOT NULL,
  url   VARCHAR(255) NOT NULL,
  sort_order INT DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- AJUSTES EN USER_COMPANIES (rol por empresa)
ALTER TABLE user_companies
  ADD COLUMN visibility ENUM('all','scope','assigned') DEFAULT 'scope' AFTER role,
  ADD COLUMN status ENUM('active','invited','suspended') DEFAULT 'active' AFTER visibility;

-- ALCANCE (Mi Alcance)
CREATE TABLE IF NOT EXISTS user_company_scopes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_company_id INT NOT NULL,
  allow_all_units TINYINT(1) DEFAULT 0,
  allow_all_businesses TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_company_scope_units (
  user_company_id INT NOT NULL,
  unit_id INT NOT NULL,
  UNIQUE KEY uniq_scope_unit (user_company_id, unit_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_company_scope_businesses (
  user_company_id INT NOT NULL,
  business_id INT NOT NULL,
  UNIQUE KEY uniq_scope_biz (user_company_id, business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ROLES/skills POR MÓDULO DENTRO DE LA EMPRESA
CREATE TABLE IF NOT EXISTS user_company_module_roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_company_id INT NOT NULL,
  module_slug VARCHAR(64) NOT NULL,
  module_role ENUM('viewer','contributor','approver','manager') NOT NULL,
  skill_level ENUM('basico','tecnico','avanzado','supervisor') DEFAULT 'basico',
  UNIQUE KEY uniq_ucm (user_company_id, module_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- INVITACIONES: reservar asiento y propuestas de permisos
ALTER TABLE invitations
  ADD COLUMN seat_reserved TINYINT(1) DEFAULT 0 AFTER status,
  ADD COLUMN modules JSON NULL AFTER seat_reserved,
  ADD COLUMN proposed_role VARCHAR(32) NULL AFTER modules,
  ADD COLUMN proposed_visibility ENUM('all','scope','assigned') NULL AFTER proposed_role,
  ADD COLUMN scope JSON NULL AFTER proposed_visibility;


database/seeds/2025_08_29_seed_modules_and_plans.sql

INSERT IGNORE INTO modules (slug,name,description,icon,badge_text,sort_order,is_core) VALUES
('human-resources','Recursos Humanos','Personas y permisos','bi bi-people','Básico',10,1),
('processes-tasks','Procesos y Tareas','Flujos operativos','bi bi-check2-square','Básico',20,0),
('pos','Punto de Venta','Cobros y cajas','bi bi-cash','Básico',30,0),
('crm','CRM','Relación con clientes','bi bi-handshake','Básico',40,0),
('expenses','Gastos','Control y reportes','bi bi-receipt','Básico',50,1),
('petty-cash','Caja Chica','Movimientos diarios','bi bi-wallet','Básico',60,0),
('settings','Configuración','Ajustes del negocio','bi bi-gear','Básico',70,1),
('kpis','KPIs','Indicadores y reportes','bi bi-graph-up','Básico',80,0);

-- EJEMPLO de planes si no existen
INSERT IGNORE INTO plans (id,name,description,price_monthly,users_max,units_max,businesses_max,storage_max_mb,modules_included,is_active)
VALUES
(1,'Free','Cuenta sin empresa ni módulos',0,0,0,0,100,'[]',1),
(2,'Control','Operación básica',25,5,3,5,2048,'["human-resources","expenses","settings"]',1),
(3,'Pro','Operación completa',75,25,10,25,5120,'["*"]',1);

2) Helpers core (PHP)

core/plan.php

<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

function getCompanyPlan(int $companyId): array {
  $sql = "SELECT p.* FROM companies c JOIN plans p ON p.id = c.plan_id WHERE c.id = ?";
  $st = db()->prepare($sql); $st->execute([$companyId]);
  return $st->fetch(PDO::FETCH_ASSOC) ?: [];
}

function planAllowsModule(int $companyId, string $slug): bool {
  $plan = getCompanyPlan($companyId);
  if (!$plan) return false;
  if ($plan['modules_included'] === '*') return true;
  $mods = json_decode($plan['modules_included'] ?? '[]', true) ?: [];
  return in_array($slug, $mods, true);
}

function countActiveUsersAndPendingInvites(int $companyId): array {
  $used = (int)db()->query("SELECT COUNT(*) FROM user_companies WHERE company_id = {$companyId} AND status='active'")->fetchColumn();
  $pending = (int)db()->query("SELECT COUNT(*) FROM invitations WHERE company_id = {$companyId} AND status='pending' AND seat_reserved=1")->fetchColumn();
  return [$used, $pending];
}

function seatsAvailable(int $companyId): int {
  $plan = getCompanyPlan($companyId);
  $max = (int)($plan['users_max'] ?? 0);
  [$used, $pending] = countActiveUsersAndPendingInvites($companyId);
  return max(0, $max - ($used + $pending));
}

function reserveSeat(int $invitationId): bool {
  $st = db()->prepare("UPDATE invitations SET seat_reserved=1 WHERE id=? AND seat_reserved=0");
  return $st->execute([$invitationId]);
}

function releaseSeat(int $invitationId): bool {
  $st = db()->prepare("UPDATE invitations SET seat_reserved=0 WHERE id=? AND seat_reserved=1");
  return $st->execute([$invitationId]);
}


core/permissions.php

<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

/** Rol→acciones permitidas por módulo (ajusta a gusto) */
function roleActionMap(): array {
  return [
    'viewer'      => ['view','export'],
    'contributor' => ['view','export','create','edit_self','complete_assigned'],
    'approver'    => ['view','export','create','edit','approve','close'],
    'manager'     => ['*']  // todo
  ];
}

/** Chequea acciones a partir del rol+skill (puedes condicionar por skill si quieres) */
function canAction(string $module, string $action, string $moduleRole, string $skillLevel='basico'): bool {
  $map = roleActionMap();
  $allowed = $map[$moduleRole] ?? [];
  if (in_array('*', $allowed, true)) return true;
  return in_array($action, $allowed, true);
}

/** Obtiene el rol de módulo del usuario en la empresa actual */
function getUserModuleRole(int $userCompanyId, string $moduleSlug): array {
  $st = db()->prepare("SELECT module_role, skill_level FROM user_company_module_roles WHERE user_company_id=? AND module_slug=?");
  $st->execute([$userCompanyId, $moduleSlug]);
  return $st->fetch(PDO::FETCH_ASSOC) ?: ['module_role' => 'viewer', 'skill_level' => 'basico'];
}


core/scope.php

<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

/** Devuelve user_company activo (empresa actual) */
function currentUserCompany(int $userId, int $companyId): ?array {
  $st = db()->prepare("SELECT * FROM user_companies WHERE user_id=? AND company_id=? AND status='active'");
  $st->execute([$userId, $companyId]);
  return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

/** WHERE y params de alcance/visibilidad */
function scopeWhereClause(array $userCompany, array $filters=[], string $alias='t'): array {
  // $alias: prefijo de tabla (ej. "e" para expenses)
  $visibility = $userCompany['visibility'] ?? 'scope';
  $ucId = (int)$userCompany['id'];

  // lee flags y listas
  $st = db()->prepare("SELECT allow_all_units, allow_all_businesses FROM user_company_scopes WHERE user_company_id=?");
  $st->execute([$ucId]);
  $flags = $st->fetch(PDO::FETCH_ASSOC) ?: ['allow_all_units'=>0, 'allow_all_businesses'=>0];

  $units = db()->prepare("SELECT unit_id FROM user_company_scope_units WHERE user_company_id=?");
  $units->execute([$ucId]); $unitIds = array_column($units->fetchAll(PDO::FETCH_ASSOC),'unit_id');

  $biz = db()->prepare("SELECT business_id FROM user_company_scope_businesses WHERE user_company_id=?");
  $biz->execute([$ucId]); $bizIds = array_column($biz->fetchAll(PDO::FETCH_ASSOC),'business_id');

  $where = "WHERE {$alias}.company_id = :companyId";
  $params = [':companyId' => $userCompany['company_id']];

  if (!$flags['allow_all_units']) {
    if ($unitIds) {
      $in = implode(',', array_fill(0, count($unitIds), '?'));
      $where .= " AND {$alias}.unit_id IN ($in)";
      $params = array_merge($params, $unitIds);
    } else {
      $where .= " AND 1=0"; // sin unidades asignadas
    }
  }
  if (!$flags['allow_all_businesses']) {
    if ($bizIds) {
      $in = implode(',', array_fill(0, count($bizIds), '?'));
      $where .= " AND {$alias}.business_id IN ($in)";
      $params = array_merge($params, $bizIds);
    } else {
      $where .= " AND 1=0"; // sin negocios asignados
    }
  }

  if ($visibility === 'assigned') {
    $where .= " AND {$alias}.assigned_to_user_id = :me";
    $params[':me'] = $_SESSION['user_id'];
  }

  // filtros específicos del módulo (opcionales)
  if (!empty($filters['date_from'])) { $where .= " AND {$alias}.created_at >= :df"; $params[':df']=$filters['date_from']; }
  if (!empty($filters['date_to']))   { $where .= " AND {$alias}.created_at <= :dt"; $params[':dt']=$filters['date_to']; }

  return [$where, $params];
}


core/auth.php (mínimo)

<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
function auth(): void {
  if (empty($_SESSION['user_id'])) { header('Location: /login.php'); exit; }
}


core/mailer.php (stub SMTP: usa config .env.php)

<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
function sendMail(string $to, string $subject, string $html): bool {
  // Implementa con PHPMailer o mail() según hosting
  return @mail($to, $subject, $html, "Content-Type: text/html; charset=UTF-8\r\n");
}


core/notify.php (notificaciones mínimas)

<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
function createNotification(int $userId, int $companyId, string $message): void {
  $st = db()->prepare("INSERT INTO notifications (user_id, company_id, message, created_at) VALUES (?,?,?,NOW())");
  $st->execute([$userId, $companyId, $message]);
}

3) Invitaciones (crear y aceptar)

admin/invitations.php (crear + reservar seat)

<?php require __DIR__.'/../bootstrap.php'; if(!defined('APP_BOOTSTRAPPED')) exit; session_start();
require __DIR__.'/../core/auth.php'; require __DIR__.'/../core/plan.php'; auth();

$companyId = (int)($_SESSION['company_id'] ?? 0);
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $email = trim($_POST['email'] ?? '');
  $role  = $_POST['role'] ?? 'external';
  $visibility = $_POST['visibility'] ?? 'assigned';
  $modules = $_POST['modules'] ?? [];            // array de slugs
  $scope = ['units'=>$_POST['units'] ?? [], 'businesses'=>$_POST['businesses'] ?? []];

  // seats
  if (seatsAvailable($companyId) <= 0) {
    echo "No hay asientos disponibles en tu plan."; exit;
  }

  $token = bin2hex(random_bytes(16));
  $st = db()->prepare("INSERT INTO invitations (email, company_id, role, token, status, sent_date, modules, proposed_visibility, proposed_role, scope, seat_reserved)
                       VALUES (?,?,?,?, 'pending', NOW(), ?, ?, ?, ?, 1)");
  $st->execute([$email,$companyId,$role,$token,json_encode($modules),$visibility,$role,json_encode($scope)]);

  $acceptUrl = APP_URL . "/admin/accept_invitation.php?token=".$token;
  sendMail($email, "Invitación a Indice", "<p>Fuiste invitado a colaborar. Acepta aquí: <a href='$acceptUrl'>$acceptUrl</a></p>");
  echo "Invitación enviada y asiento reservado.";
  exit;
}
?>
<!-- Sencillo formulario (ajusta a tu UI) -->
<form method="post">
  <input name="email" type="email" placeholder="correo@dominio.com" required>
  <select name="role">
    <option value="external">Externo (assigned)</option>
    <option value="user">Usuario</option>
    <option value="moderator">Moderador</option>
    <option value="admin">Admin</option>
  </select>
  <select name="visibility">
    <option value="assigned">Solo asignado</option>
    <option value="scope">Por alcance</option>
    <option value="all">Todo</option>
  </select>
  <!-- modules[] y scope[] deberían venir de selects/checks -->
  <button>Enviar invitación</button>
</form>


admin/accept_invitation.php (aplica invitación y crea relaciones)

<?php require __DIR__.'/../bootstrap.php'; if(!defined('APP_BOOTSTRAPPED')) exit; session_start();
require __DIR__.'/../core/plan.php';

$token = $_GET['token'] ?? '';
$st = db()->prepare("SELECT * FROM invitations WHERE token=? AND status='pending'");
$st->execute([$token]); $inv = $st->fetch(PDO::FETCH_ASSOC);
if (!$inv) { echo "Invitación inválida o expirada."; exit; }

$email = $inv['email'];
// si usuario existe, úsalo; si no, redirige a alta (o créalo aquí)
$u = db()->prepare("SELECT * FROM users WHERE email=?"); $u->execute([$email]); $user = $u->fetch(PDO::FETCH_ASSOC);
if (!$user) {
  // crea cuenta Free mínima
  $p = password_hash(bin2hex(random_bytes(6)), PASSWORD_DEFAULT);
  db()->prepare("INSERT INTO users (name,email,password_hash,is_active,created_at) VALUES (?,?,?,1,NOW())")
     ->execute([$email,$email,$p]);
  $userId = (int)db()->lastInsertId();
  db()->prepare("INSERT INTO user_profiles (user_id) VALUES (?)")->execute([$userId]);
} else { $userId = (int)$user['id']; }

// crear/activar user_company
db()->prepare("INSERT INTO user_companies (user_id, company_id, role, visibility, status)
              VALUES (?,?,?,?, 'active')
              ON DUPLICATE KEY UPDATE role=VALUES(role), visibility=VALUES(visibility), status='active'")
  ->execute([$userId, (int)$inv['company_id'], $inv['proposed_role'] ?? 'external', $inv['proposed_visibility'] ?? 'assigned']);

$ucId = (int)db()->lastInsertId();
if ($ucId===0) {
  // obtener id existente
  $q = db()->prepare("SELECT id FROM user_companies WHERE user_id=? AND company_id=?");
  $q->execute([$userId,(int)$inv['company_id']]); $ucId = (int)$q->fetchColumn();
}

// aplicar scope
$scope = json_decode($inv['scope'] ?? '{}', true) ?: [];
db()->prepare("INSERT INTO user_company_scopes (user_company_id, allow_all_units, allow_all_businesses) VALUES (?,0,0)
               ON DUPLICATE KEY UPDATE allow_all_units=VALUES(allow_all_units), allow_all_businesses=VALUES(allow_all_businesses)")
  ->execute([$ucId]);

if (!empty($scope['units'])) {
  $ins = db()->prepare("INSERT IGNORE INTO user_company_scope_units (user_company_id, unit_id) VALUES (?,?)");
  foreach ($scope['units'] as $uid) { $ins->execute([$ucId,(int)$uid]); }
}
if (!empty($scope['businesses'])) {
  $ins = db()->prepare("INSERT IGNORE INTO user_company_scope_businesses (user_company_id, business_id) VALUES (?,?)");
  foreach ($scope['businesses'] as $bid) { $ins->execute([$ucId,(int)$bid]); }
}

// módulos asignados
$mods = json_decode($inv['modules'] ?? '[]', true) ?: [];
if ($mods) {
  $ins = db()->prepare("INSERT INTO user_company_module_roles (user_company_id,module_slug,module_role,skill_level)
                        VALUES (?,?,?,?)
                        ON DUPLICATE KEY UPDATE module_role=VALUES(module_role), skill_level=VALUES(skill_level)");
  foreach ($mods as $slug) { $ins->execute([$ucId,$slug,'contributor','basico']); }
}

// cerrar invitación y mantener seat ocupado (usuario activo cuenta como seat)
db()->prepare("UPDATE invitations SET status='accepted' WHERE id=?")->execute([(int)$inv['id']]);

echo "Invitación aceptada. Ya puedes ingresar.";

4) Panel Root — módulos (badges/orden/activo)

panel_root/modules.php (CRUD simple)

<?php require __DIR__.'/../bootstrap.php'; if(!defined('APP_BOOTSTRAPPED')) exit; session_start();
require __DIR__.'/../core/auth.php'; auth(); /* valida rol root */

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $id = (int)$_POST['id'];
  $st = db()->prepare("UPDATE modules SET name=?, description=?, icon=?, badge_text=?, tier=?, sort_order=?, is_core=?, is_active=? WHERE id=?");
  $st->execute([
    $_POST['name'], $_POST['description'], $_POST['icon'], $_POST['badge_text'],
    $_POST['tier'], (int)$_POST['sort_order'], (int)($_POST['is_core']??0), (int)($_POST['is_active']??0), $id
  ]);
  header('Location: modules.php'); exit;
}
$mods = db()->query("SELECT * FROM modules ORDER BY sort_order, name")->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>Módulos</h2>
<table border="1" cellpadding="6">
  <tr><th>ID</th><th>Slug</th><th>Nombre</th><th>Badge</th><th>Tier</th><th>Orden</th><th>Core</th><th>Activo</th><th>Editar</th></tr>
  <?php foreach($mods as $m): ?>
  <tr>
    <td><?= $m['id'] ?></td>
    <td><?= htmlspecialchars($m['slug']) ?></td>
    <td><?= htmlspecialchars($m['name']) ?></td>
    <td><?= htmlspecialchars($m['badge_text']) ?></td>
    <td><?= htmlspecialchars($m['tier']) ?></td>
    <td><?= (int)$m['sort_order'] ?></td>
    <td><?= $m['is_core']?'Sí':'No' ?></td>
    <td><?= $m['is_active']?'Sí':'No' ?></td>
    <td>
      <form method="post">
        <input type="hidden" name="id" value="<?= $m['id'] ?>">
        <input name="name" value="<?= htmlspecialchars($m['name']) ?>">
        <input name="description" value="<?= htmlspecialchars($m['description']) ?>">
        <input name="icon" value="<?= htmlspecialchars($m['icon']) ?>">
        <input name="badge_text" value="<?= htmlspecialchars($m['badge_text']) ?>">
        <select name="tier">
          <option <?= $m['tier']=='basic'?'selected':'' ?> value="basic">basic</option>
          <option <?= $m['tier']=='pro'?'selected':'' ?> value="pro">pro</option>
          <option <?= $m['tier']=='enterprise'?'selected':'' ?> value="enterprise">enterprise</option>
        </select>
        <input name="sort_order" type="number" value="<?= (int)$m['sort_order'] ?>" style="width:80px">
        <label><input type="checkbox" name="is_core" <?= $m['is_core']?'checked':'' ?>> core</label>
        <label><input type="checkbox" name="is_active" <?= $m['is_active']?'checked':'' ?>> activo</label>
        <button>Guardar</button>
      </form>
    </td>
  </tr>
  <?php endforeach ?>
</table>

5) registro.php (wizard en indiceapp.com)

registro.php (mínimo funcional, 4 pasos: cuenta → negocio → módulos → pago stub)

<?php require __DIR__.'/bootstrap.php'; if(!defined('APP_BOOTSTRAPPED')) exit; session_start();
/*
  Este wizard crea/actualiza un signup_intent.
  Tras "pago" marca como paid, crea company + user_company(superadmin) y redirige a app.
*/
$step = (int)($_GET['step'] ?? 1);
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $email = trim($_POST['email'] ?? '');
  $intentId = (int)($_POST['intent_id'] ?? 0);
  $data = $_POST; unset($data['intent_id']);

  if ($step===1) {
    // crear intent
    db()->prepare("INSERT INTO signup_intents (email, form_data, status) VALUES (?,?, 'draft')")
      ->execute([$email, json_encode(['account'=>$data])]);
    $intentId = (int)db()->lastInsertId();
    header("Location: registro.php?step=2&intent={$intentId}"); exit;
  }

  if ($intentId>0) {
    $st = db()->prepare("SELECT * FROM signup_intents WHERE id=?"); $st->execute([$intentId]);
    $intent = $st->fetch(PDO::FETCH_ASSOC);
    $form = json_decode($intent['form_data'], true) ?: [];
    if ($step===2) $form['business'] = $data;
    if ($step===3) { $form['modules'] = $data['modules'] ?? []; $form['suggested_plan_id'] = (int)($data['plan_id'] ?? 2); }
    db()->prepare("UPDATE signup_intents SET form_data=?, suggested_plan_id=? WHERE id=?")
      ->execute([json_encode($form), (int)($form['suggested_plan_id'] ?? 2), $intentId]);

    if ($step<4) { header("Location: registro.php?step=".($step+1)."&intent={$intentId}"); exit; }

    // Paso 4: "pago" (stub)
    db()->prepare("UPDATE signup_intents SET status='paid' WHERE id=?")->execute([$intentId]);

    // Crear empresa + superadmin
    $email = $intent['email'];
    $form  = json_decode($intent['form_data'], true) ?: [];
    $planId = (int)($intent['suggested_plan_id'] ?? 2);
    // usuario owner (si no existe)
    $u = db()->prepare("SELECT * FROM users WHERE email=?"); $u->execute([$email]); $user = $u->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
      $pwd = password_hash($_POST['password'] ?? bin2hex(random_bytes(4)), PASSWORD_DEFAULT);
      db()->prepare("INSERT INTO users (name,email,password_hash,is_active,created_at) VALUES (?,?,?,1,NOW())")->execute([$form['account']['full_name']??$email, $email, $pwd]);
      $userId = (int)db()->lastInsertId();
      db()->prepare("INSERT INTO user_profiles (user_id,full_name,phone,tax_id) VALUES (?,?,?,?)")
        ->execute([$userId, $form['account']['full_name']??null, $form['account']['phone']??null, $form['account']['tax_id']??null]);
    } else { $userId = (int)$user['id']; }

    // empresa
    db()->prepare("INSERT INTO companies (name, plan_id, created_by, created_at) VALUES (?,?,?,NOW())")
      ->execute([$form['business']['company_name'] ?? 'Mi Empresa', $planId, $userId]);
    $companyId = (int)db()->lastInsertId();

    // superadmin
    db()->prepare("INSERT INTO user_companies (user_id, company_id, role, visibility, status) VALUES (?,?, 'superadmin','all','active')")
      ->execute([$userId, $companyId]);

    // módulos del plan: asignar manager por defecto
    $plan = db()->prepare("SELECT * FROM plans WHERE id=?"); $plan->execute([$planId]); $p = $plan->fetch(PDO::FETCH_ASSOC);
    $mods = ($p && $p['modules_included']==='*') ? array_column(db()->query("SELECT slug FROM modules WHERE is_active=1")->fetchAll(PDO::FETCH_ASSOC),'slug')
                                                : (json_decode($p['modules_included'] ?? '[]', true) ?: []);
    $q = db()->prepare("INSERT INTO user_company_module_roles (user_company_id,module_slug,module_role,skill_level) VALUES (?,?, 'manager','supervisor')");
    $ucId = (int)db()->lastInsertId(); // ojo: lastInsertId no sirve aquí; mejor recupéralo:
    $ucQ = db()->prepare("SELECT id FROM user_companies WHERE user_id=? AND company_id=?");
    $ucQ->execute([$userId,$companyId]); $ucId = (int)$ucQ->fetchColumn();
    foreach ($mods as $slug) { $q->execute([$ucId,$slug]); }

    // redirigir a app
    header("Location: https://app.indiceapp.com/"); exit;
  }
}

// Render de pasos
$intentId = (int)($_GET['intent'] ?? 0);
?>
<?php if ($step===1): ?>
<h2>Crear tu cuenta</h2>
<form method="post">
  <input type="hidden" name="intent_id" value="0">
  <input name="full_name" placeholder="Tu nombre completo" required>
  <input type="email" name="email" placeholder="Correo" required>
  <input type="password" name="password" placeholder="Contraseña" required>
  <input name="phone" placeholder="Teléfono">
  <input name="tax_id" placeholder="RFC/DNI">
  <button>Continuar</button>
</form>
<?php elseif ($step===2): ?>
<h2>Tu negocio</h2>
<form method="post">
  <input type="hidden" name="intent_id" value="<?= $intentId ?>">
  <input name="company_name" placeholder="Nombre de la empresa" required>
  <input name="employees" type="number" placeholder="Empleados estimados" required>
  <input name="units" type="number" placeholder="Unidades de negocio">
  <input name="locations" type="number" placeholder="Sucursales">
  <button>Continuar</button>
</form>
<?php elseif ($step===3): ?>
<h2>Módulos y plan</h2>
<form method="post">
  <input type="hidden" name="intent_id" value="<?= $intentId ?>">
  <label><input type="checkbox" name="modules[]" value="human-resources" checked> Recursos Humanos</label><br>
  <label><input type="checkbox" name="modules[]" value="expenses" checked> Gastos</label><br>
  <label><input type="checkbox" name="modules[]" value="settings" checked> Configuración</label><br>
  <select name="plan_id">
    <option value="2">Control (5 usuarios)</option>
    <option value="3">Pro (25 usuarios)</option>
  </select>
  <button>Ir a pago</button>
</form>
<?php else: ?>
<h2>Pago</h2>
<p>Stub de pago (integrar Stripe/MP/PayPal aquí). Al confirmar se creará tu empresa y tendrás acceso inmediato.</p>
<form method="post">
  <input type="hidden" name="intent_id" value="<?= $intentId ?>">
  <button>Pagar y crear empresa</button>
</form>
<?php endif; ?>


Cuando integremos la pasarela real, en el webhook marcamos signup_intents.status='paid' y ejecutamos la misma rutina de creación (puedo darte ese archivo cuando lo pidas).

6) Expenses — ejemplo de uso de alcance/visibilidad

En modules/expenses/index.php (esqueleto de consulta):

<?php require __DIR__.'/../../bootstrap.php'; if(!defined('APP_BOOTSTRAPPED')) exit; session_start();
require __DIR__.'/../../core/auth.php'; require __DIR__.'/../../core/scope.php'; require __DIR__.'/../../core/permissions.php'; auth();

$userId = (int)$_SESSION['user_id']; $companyId = (int)($_SESSION['company_id'] ?? 0);
$uc = currentUserCompany($userId, $companyId) ?: die('Sin acceso');
$role = getUserModuleRole((int)$uc['id'], 'expenses');

if (!canAction('expenses','view',$role['module_role'],$role['skill_level'])) { http_response_code(403); exit('Access denied'); }

[$where,$params] = scopeWhereClause($uc, $_GET, 'e');
$sql = "SELECT e.* FROM expenses e $where ORDER BY e.pay_date DESC LIMIT 200";
$st = db()->prepare($sql); $st->execute($params); $rows = $st->fetchAll(PDO::FETCH_ASSOC);

/* Renderiza tu tabla + filtros + exportaciones usando $rows */

¿Qué más necesitas para pegar y que Copilot ayude?

Archivos y rutas claras (arriba).

Reglas “no debatibles” para Copilot:

Nunca crear user_companies en cuenta Free.

Siempre validar seatsAvailable() antes de enviar invitaciones.

Grid = (plan ∩ módulos asignados).

Acciones = module_role (+ skill_level si aplica).

Visibilidad external = assigned.

Export PDF/Excel usa la misma query del listado (mismos WHERE).

Con esto Codex/Copilot deben seguir el patrón sin “salirse”.
