# segundo
1) Archivos base (cPanel, una sola raíz)
public_html/config/.env.php (ejemplo)
<?php
return [
  // APP
  'APP_URL'   => 'https://app.indiceapp.com',
  'APP_ENV'   => 'production',
  'APP_DEBUG' => false,

  // DB
  'DB_HOST' => 'localhost',
  'DB_NAME' => 'indice_saas',
  'DB_USER' => 'usuario',
  'DB_PASS' => 'secreto',

  // MAIL (stub; puedes cambiar a PHPMailer)
  'MAIL_FROM_EMAIL' => 'no-reply@indiceapp.com',
  'MAIL_FROM_NAME'  => 'Indice SaaS',
];

public_html/config/config.php
<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$__ENV = require __DIR__.'/.env.php';

define('APP_URL',   $__ENV['APP_URL']   ?? 'http://localhost');
define('APP_ENV',   $__ENV['APP_ENV']   ?? 'local');
define('APP_DEBUG', (bool)($__ENV['APP_DEBUG'] ?? true));

define('DB_HOST', $__ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $__ENV['DB_NAME'] ?? 'indice_saas');
define('DB_USER', $__ENV['DB_USER'] ?? 'root');
define('DB_PASS', $__ENV['DB_PASS'] ?? '');

function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;
  $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
  $options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_PERSISTENT         => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
  ];
  $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
  return $pdo;
}

public_html/bootstrap.php
<?php
define('APP_BOOTSTRAPPED', 1);
require __DIR__.'/config/config.php';

public_html/.htaccess
Options -Indexes
RewriteEngine On
RewriteRule ^(core|database|config)/ - [F,L]

2) Migraciones y seeds (SQL)

Crea estos archivos:

public_html/database/migrations/2025_08_29_core_additions.sql

(ya integrado lo que acordamos: perfiles, intents de registro, módulos, favoritos, atajos, scopes, roles por módulo, extensión de invitaciones)

CREATE TABLE IF NOT EXISTS user_profiles (
  user_id INT PRIMARY KEY,
  full_name VARCHAR(150) NULL,
  birthday DATE NULL,
  phone VARCHAR(30) NULL,
  tax_id VARCHAR(50) NULL,
  address JSON NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS signup_intents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  form_data JSON NOT NULL,
  suggested_plan_id INT NULL,
  status ENUM('draft','awaiting_payment','paid','abandoned') DEFAULT 'draft',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS modules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(64) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(255) NULL,
  icon VARCHAR(100) NULL,
  badge_text VARCHAR(32) NULL,
  tier ENUM('basic','pro','enterprise') DEFAULT 'basic',
  sort_order INT DEFAULT 100,
  is_core TINYINT(1) DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_module_favorites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  module_id INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_module (user_id, module_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menu_shortcuts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  label VARCHAR(100) NOT NULL,
  url   VARCHAR(255) NOT NULL,
  sort_order INT DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE user_companies
  ADD COLUMN visibility ENUM('all','scope','assigned') DEFAULT 'scope' AFTER role,
  ADD COLUMN status ENUM('active','invited','suspended') DEFAULT 'active' AFTER visibility;

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

CREATE TABLE IF NOT EXISTS user_company_module_roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_company_id INT NOT NULL,
  module_slug VARCHAR(64) NOT NULL,
  module_role ENUM('viewer','contributor','approver','manager') NOT NULL,
  skill_level ENUM('basico','tecnico','avanzado','supervisor') DEFAULT 'basico',
  UNIQUE KEY uniq_ucm (user_company_id, module_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE invitations
  ADD COLUMN seat_reserved TINYINT(1) DEFAULT 0 AFTER status,
  ADD COLUMN modules JSON NULL AFTER seat_reserved,
  ADD COLUMN proposed_role VARCHAR(32) NULL AFTER modules,
  ADD COLUMN proposed_visibility ENUM('all','scope','assigned') NULL AFTER proposed_role,
  ADD COLUMN scope JSON NULL AFTER proposed_visibility;

public_html/database/seeds/2025_08_29_seed_modules_and_plans.sql
INSERT IGNORE INTO modules (slug,name,description,icon,badge_text,sort_order,is_core) VALUES
('human-resources','Recursos Humanos','Personas y permisos','bi bi-people','Básico',10,1),
('processes-tasks','Procesos y Tareas','bi bi-check2-square','Básico',20,0),
('pos','Punto de Venta','bi bi-cash','Básico',30,0),
('crm','CRM','bi bi-handshake','Básico',40,0),
('expenses','Gastos','bi bi-receipt','Básico',50,1),
('petty-cash','Caja Chica','bi bi-wallet','Básico',60,0),
('settings','Configuración','bi bi-gear','Básico',70,1),
('kpis','KPIs','bi bi-graph-up','Básico',80,0);

INSERT IGNORE INTO plans (id,name,description,price_monthly,users_max,units_max,businesses_max,storage_max_mb,modules_included,is_active) VALUES
(1,'Free','Cuenta sin empresa ni módulos',0,0,0,0,100,'[]',1),
(2,'Control','Operación básica',25,5,3,5,2048,'["human-resources","expenses","settings"]',1),
(3,'Pro','Operación completa',75,25,10,25,5120,'["*"]',1);

3) Helpers core
public_html/core/plan.php
<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

function getCompanyPlan(int $companyId): array {
  $st = db()->prepare("SELECT p.* FROM companies c JOIN plans p ON p.id = c.plan_id WHERE c.id = ?");
  $st->execute([$companyId]);
  return $st->fetch() ?: [];
}

function planAllowsModule(int $companyId, string $slug): bool {
  $plan = getCompanyPlan($companyId);
  if (!$plan) return false;
  if ($plan['modules_included'] === '*') return true;
  $mods = json_decode($plan['modules_included'] ?? '[]', true) ?: [];
  return in_array($slug, $mods, true);
}

function countActiveUsersAndPendingInvites(int $companyId): array {
  $used = (int)db()->query("SELECT COUNT(*) FROM user_companies WHERE company_id={$companyId} AND status='active'")->fetchColumn();
  $pending = (int)db()->query("SELECT COUNT(*) FROM invitations WHERE company_id={$companyId} AND status='pending' AND seat_reserved=1")->fetchColumn();
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

public_html/core/permissions.php
<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

function roleActionMap(): array {
  return [
    'viewer'      => ['view','export'],
    'contributor' => ['view','export','create','edit_self','complete_assigned'],
    'approver'    => ['view','export','create','edit','approve','close'],
    'manager'     => ['*'],
  ];
}

function canAction(string $module, string $action, string $moduleRole, string $skillLevel='basico'): bool {
  $map = roleActionMap();
  $allowed = $map[$moduleRole] ?? [];
  if (in_array('*', $allowed, true)) return true;
  return in_array($action, $allowed, true);
}

function getUserModuleRole(int $userCompanyId, string $moduleSlug): array {
  $st = db()->prepare("SELECT module_role, skill_level FROM user_company_module_roles WHERE user_company_id=? AND module_slug=?");
  $st->execute([$userCompanyId, $moduleSlug]);
  return $st->fetch() ?: ['module_role'=>'viewer','skill_level'=>'basico'];
}

public_html/core/scope.php
<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

function currentUserCompany(int $userId, int $companyId): ?array {
  $st = db()->prepare("SELECT * FROM user_companies WHERE user_id=? AND company_id=? AND status='active'");
  $st->execute([$userId, $companyId]);
  return $st->fetch() ?: null;
}

function scopeWhereClause(array $userCompany, array $filters=[], string $alias='t'): array {
  $visibility = $userCompany['visibility'] ?? 'scope';
  $ucId = (int)$userCompany['id'];

  $st = db()->prepare("SELECT allow_all_units, allow_all_businesses FROM user_company_scopes WHERE user_company_id=?");
  $st->execute([$ucId]);
  $flags = $st->fetch() ?: ['allow_all_units'=>0,'allow_all_businesses'=>0];

  $uQ = db()->prepare("SELECT unit_id FROM user_company_scope_units WHERE user_company_id=?");
  $uQ->execute([$ucId]); $unitIds = array_column($uQ->fetchAll(), 'unit_id');

  $bQ = db()->prepare("SELECT business_id FROM user_company_scope_businesses WHERE user_company_id=?");
  $bQ->execute([$ucId]); $bizIds = array_column($bQ->fetchAll(), 'business_id');

  $where = "WHERE {$alias}.company_id = :companyId";
  $params = [':companyId' => $userCompany['company_id']];

  if (!$flags['allow_all_units']) {
    if ($unitIds) { $in = implode(',', array_fill(0,count($unitIds),'?')); $where .= " AND {$alias}.unit_id IN ($in)"; $params = array_merge($params, $unitIds); }
    else { $where .= " AND 1=0"; }
  }
  if (!$flags['allow_all_businesses']) {
    if ($bizIds) { $in = implode(',', array_fill(0,count($bizIds),'?')); $where .= " AND {$alias}.business_id IN ($in)"; $params = array_merge($params, $bizIds); }
    else { $where .= " AND 1=0"; }
  }

  if ($visibility === 'assigned') {
    $where .= " AND {$alias}.assigned_to_user_id = :me";
    $params[':me'] = $_SESSION['user_id'];
  }

  return [$where, $params];
}


(Los auth.php, mailer.php, notify.php que ya te pasé antes siguen igual.)

4) public_html/registro.php (wizard + “pago” stub)

Este archivo vive en indiceapp.com (misma BD). Crea signup_intents, y al “pagar” crea la empresa y deja al usuario como superadmin.

<?php require __DIR__.'/bootstrap.php'; if(!defined('APP_BOOTSTRAPPED')) exit;

$step = (int)($_GET['step'] ?? 1);
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $intentId = (int)($_POST['intent_id'] ?? 0);
  if ($step===1) {
    $email = trim($_POST['email'] ?? '');
    $account = [
      'full_name' => $_POST['full_name'] ?? null,
      'phone'     => $_POST['phone'] ?? null,
      'tax_id'    => $_POST['tax_id'] ?? null,
      'email'     => $email,
    ];
    db()->prepare("INSERT INTO signup_intents (email, form_data, status) VALUES (?,?, 'draft')")
      ->execute([$email, json_encode(['account'=>$account])]);
    $intentId = (int)db()->lastInsertId();
    header("Location: registro.php?step=2&intent={$intentId}"); exit;
  }

  $st = db()->prepare("SELECT * FROM signup_intents WHERE id=?"); $st->execute([(int)$_POST['intent_id']]); $intent = $st->fetch();
  $form = json_decode($intent['form_data'], true) ?: [];

  if ($step===2) {
    $form['business'] = [
      'company_name' => $_POST['company_name'] ?? 'Mi Empresa',
      'employees'    => (int)($_POST['employees'] ?? 1),
      'units'        => (int)($_POST['units'] ?? 0),
      'locations'    => (int)($_POST['locations'] ?? 0),
    ];
  }
  if ($step===3) {
    $form['modules'] = $_POST['modules'] ?? [];
    $form['suggested_plan_id'] = (int)($_POST['plan_id'] ?? 2);
  }

  db()->prepare("UPDATE signup_intents SET form_data=?, suggested_plan_id=? WHERE id=?")
    ->execute([json_encode($form), (int)($form['suggested_plan_id'] ?? 2), (int)$_POST['intent_id']]);

  if ($step<4) { header("Location: registro.php?step=".($step+1)."&intent={$_POST['intent_id']}"); exit; }

  // Paso 4: pago stub → marcar paid y crear empresa/owner
  db()->prepare("UPDATE signup_intents SET status='paid' WHERE id=?")->execute([(int)$_POST['intent_id']]);
  $email = $intent['email']; $planId = (int)($intent['suggested_plan_id'] ?? 2);
  $form  = json_decode($intent['form_data'], true) ?: [];

  // Usuario (si no existe)
  $u = db()->prepare("SELECT * FROM users WHERE email=?"); $u->execute([$email]); $user = $u->fetch();
  if (!$user) {
    $pwd = password_hash($_POST['password'] ?? bin2hex(random_bytes(4)), PASSWORD_DEFAULT);
    db()->prepare("INSERT INTO users (name,email,password_hash,is_active,created_at) VALUES (?,?,?,1,NOW())")
      ->execute([$form['account']['full_name'] ?? $email, $email, $pwd]);
    $userId = (int)db()->lastInsertId();
    db()->prepare("INSERT INTO user_profiles (user_id,full_name,phone,tax_id) VALUES (?,?,?,?)")
      ->execute([$userId, $form['account']['full_name']??null, $form['account']['phone']??null, $form['account']['tax_id']??null]);
  } else { $userId = (int)$user['id']; }

  // Empresa
  db()->prepare("INSERT INTO companies (name, plan_id, created_by, created_at) VALUES (?,?,?,NOW())")
    ->execute([$form['business']['company_name'] ?? 'Mi Empresa', $planId, $userId]);
  $companyId = (int)db()->lastInsertId();

  // Owner superadmin
  db()->prepare("INSERT INTO user_companies (user_id, company_id, role, visibility, status) VALUES (?,?, 'superadmin','all','active')")
    ->execute([$userId, $companyId]);

  // Asignar todos los módulos del plan al owner (manager)
  $pQ = db()->prepare("SELECT * FROM plans WHERE id=?"); $pQ->execute([$planId]); $p = $pQ->fetch();
  $mods = ($p && $p['modules_included']==='*')
          ? array_column(db()->query("SELECT slug FROM modules WHERE is_active=1")->fetchAll(), 'slug')
          : (json_decode($p['modules_included'] ?? '[]', true) ?: []);
  $ucQ = db()->prepare("SELECT id FROM user_companies WHERE user_id=? AND company_id=?"); $ucQ->execute([$userId,$companyId]); $ucId = (int)$ucQ->fetchColumn();
  $ins = db()->prepare("INSERT INTO user_company_module_roles (user_company_id,module_slug,module_role,skill_level) VALUES (?,?, 'manager','supervisor')");
  foreach ($mods as $slug) { $ins->execute([$ucId,$slug]); }

  header("Location: ".APP_URL."/"); exit;
}

$step = max(1, min(4, (int)($_GET['step'] ?? 1)));
$intentId = (int)($_GET['intent'] ?? 0);
?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registro Indice</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-light">
<div class="container py-5">
  <div class="mb-4">
    <div class="progress">
      <div class="progress-bar" role="progressbar" style="width:<?= $step*25 ?>%"></div>
    </div>
  </div>

  <?php if ($step===1): ?>
  <h3 class="mb-3">Tu cuenta</h3>
  <form method="post" class="card card-body">
    <input type="hidden" name="intent_id" value="0">
    <div class="row g-3">
      <div class="col-md-6"><input name="full_name" class="form-control" placeholder="Nombre completo" required></div>
      <div class="col-md-6"><input name="phone" class="form-control" placeholder="Teléfono"></div>
      <div class="col-md-6"><input type="email" name="email" class="form-control" placeholder="Correo" required></div>
      <div class="col-md-6"><input type="password" name="password" class="form-control" placeholder="Contraseña" required></div>
      <div class="col-md-6"><input name="tax_id" class="form-control" placeholder="RFC / DNI"></div>
    </div>
    <div class="mt-3 text-end"><button class="btn btn-primary">Continuar</button></div>
  </form>

  <?php elseif ($step===2): ?>
  <h3 class="mb-3">Tu negocio</h3>
  <form method="post" class="card card-body">
    <input type="hidden" name="intent_id" value="<?= $intentId ?>">
    <div class="row g-3">
      <div class="col-md-6"><input name="company_name" class="form-control" placeholder="Nombre de la empresa" required></div>
      <div class="col-md-3"><input type="number" name="employees" class="form-control" placeholder="Empleados" required></div>
      <div class="col-md-3"><input type="number" name="units" class="form-control" placeholder="Unidades de negocio"></div>
      <div class="col-md-3"><input type="number" name="locations" class="form-control" placeholder="Sucursales"></div>
    </div>
    <div class="mt-3 text-end"><button class="btn btn-primary">Continuar</button></div>
  </form>

  <?php elseif ($step===3): ?>
  <h3 class="mb-3">Módulos y plan</h3>
  <form method="post" class="card card-body">
    <input type="hidden" name="intent_id" value="<?= $intentId ?>">
    <div class="row g-3">
      <div class="col-12">
        <label class="form-check"><input class="form-check-input" type="checkbox" name="modules[]" value="human-resources" checked> <span class="form-check-label">Recursos Humanos</span></label>
        <label class="form-check"><input class="form-check-input" type="checkbox" name="modules[]" value="expenses" checked> <span class="form-check-label">Gastos</span></label>
        <label class="form-check"><input class="form-check-input" type="checkbox" name="modules[]" value="settings" checked> <span class="form-check-label">Configuración</span></label>
      </div>
      <div class="col-md-6">
        <select name="plan_id" class="form-select">
          <option value="2">Control (5 usuarios)</option>
          <option value="3">Pro (25 usuarios)</option>
        </select>
      </div>
    </div>
    <div class="mt-3 text-end"><button class="btn btn-primary">Ir a pago</button></div>
  </form>

  <?php else: ?>
  <h3 class="mb-3">Pago</h3>
  <div class="card card-body">
    <p>Integrar aquí Stripe / Mercado Pago / PayPal. Por ahora es un stub.</p>
    <form method="post">
      <input type="hidden" name="intent_id" value="<?= $intentId ?>">
      <button class="btn btn-success">Pagar y crear empresa</button>
    </form>
  </div>
  <?php endif; ?>
</div>
</body></html>
