<?php
require __DIR__ . '/bootstrap.php';
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
require __DIR__ . '/core/auth.php';

requireLogin();
?>
<div class="offcanvas offcanvas-end" tabindex="-1" id="menuOffcanvas">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Mi Alcance</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <form method="post" action="/admin/scope_set.php">
      <div class="mb-3">
        <label class="form-label">Empresa</label>
        <select name="company_id" class="form-select">
          <option value="">Actual</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Unidad</label>
        <select name="unit_id" class="form-select">
          <option value="">Todos</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Negocio</label>
        <select name="business_id" class="form-select">
          <option value="">Todos</option>
        </select>
      </div>
      <button class="btn btn-primary" type="submit">Guardar</button>
    </form>
  </div>
</div>
