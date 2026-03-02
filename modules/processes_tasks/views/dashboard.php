<?php

/**
 * Vista Dashboard - Agenda del Día
 * Versión Limpia y Funcional - Patrón tasks.php
 */

// Asegurar que el token CSRF existe
if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!-- Header de sección (igual RH) -->
<div class="hr-header">
  <div class="hr-header-left">
    <h1>Agenda</h1>
    <p class="subtitle">Visualiza y gestiona el trabajo del equipo.</p>
  </div>
  <div class="hr-header-right">
    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalColumns_ptAgendaTable" id="btnColumnsPT_ptAgendaTable">
      <i class="bi bi-columns-gap"></i> Columnas
    </button>
    <a class="btn btn-primary" href="?tab=tasks&open_create=1" aria-label="Agregar nueva tarea">
      <i class="bi bi-plus-circle"></i> Nueva tarea
    </a>
  </div>
</div>

<!-- Filtros (igual RH) -->
<div class="hr-filter-card reveal">
  <form class="row g-3 align-items-end" id="ptAgendaFilters">
    <div class="col-12 col-md-6 col-xl-2">
      <label class="form-label">Periodo</label>
      <select name="periodo" id="ptAgendaPeriodo" class="form-select">
        <option value="por_realizar">Por realizar</option>
        <option value="hoy">Hoy</option>
        <option value="semana">Esta semana</option>
        <option value="mes">Este mes</option>
        <option value="pendientes">Pendientes</option>
        <option value="personalizada">Personalizada</option>
      </select>
    </div>
    <div class="col-12 col-md-6 col-xl-2">
      <label class="form-label">Unidad</label>
      <select name="unit_id" id="ptAgendaUnit" class="form-select">
        <option value="">Todas</option>
      </select>
    </div>
    <div class="col-12 col-md-6 col-xl-2">
      <label class="form-label">Negocio</label>
      <select name="business_id" id="ptAgendaBusiness" class="form-select">
        <option value="">Todos</option>
      </select>
    </div>
    <div class="col-12 col-md-6 col-xl-2">
      <label class="form-label">Colaborador</label>
      <div class="dropdown" data-bs-auto-close="outside">
        <button type="button" class="form-select text-start" id="ptAgendaAssigneeBtn" data-bs-toggle="dropdown" aria-expanded="false">
          Todos
        </button>
        <div class="dropdown-menu w-100 p-2" id="ptAgendaAssigneeMenu" aria-labelledby="ptAgendaAssigneeBtn" style="max-height: 280px; overflow: auto;">
          <button type="button" class="dropdown-item" data-action="clear">Todos</button>
          <div><hr class="dropdown-divider"></div>
          <div class="px-2 text-muted small">Cargando...</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl-2">
      <label class="form-label">Estado</label>
      <div class="dropdown" data-bs-auto-close="outside">
        <button type="button" class="form-select text-start" id="ptAgendaStatusBtn" data-bs-toggle="dropdown" aria-expanded="false">
          Todos
        </button>
        <div class="dropdown-menu w-100 p-2" id="ptAgendaStatusMenu" aria-labelledby="ptAgendaStatusBtn">
          <button type="button" class="dropdown-item" data-action="clear">Todos</button>
          <div><hr class="dropdown-divider"></div>

          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="En tiempo" id="ptAgendaStatus_en_tiempo">
            <label class="form-check-label" for="ptAgendaStatus_en_tiempo">En tiempo</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="En proceso" id="ptAgendaStatus_en_proceso">
            <label class="form-check-label" for="ptAgendaStatus_en_proceso">En proceso</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="Pausada" id="ptAgendaStatus_pausada">
            <label class="form-check-label" for="ptAgendaStatus_pausada">Pausada</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="Vencida" id="ptAgendaStatus_vencida">
            <label class="form-check-label" for="ptAgendaStatus_vencida">Vencida</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="Terminada" id="ptAgendaStatus_terminada">
            <label class="form-check-label" for="ptAgendaStatus_terminada">Terminada</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="Auditada" id="ptAgendaStatus_auditada">
            <label class="form-check-label" for="ptAgendaStatus_auditada">Auditada</label>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl-2 d-grid">
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-funnel"></i> Aplicar filtros
      </button>
    </div>

    <div class="col-12 col-md-6 col-xl-2" id="ptAgendaCustomFromWrap" style="display:none;">
      <label class="form-label">Desde</label>
      <input type="date" class="form-control" id="ptAgendaDateFrom" name="date_from" value="">
    </div>
    <div class="col-12 col-md-6 col-xl-2" id="ptAgendaCustomToWrap" style="display:none;">
      <label class="form-label">Hasta</label>
      <input type="date" class="form-control" id="ptAgendaDateTo" name="date_to" value="">
    </div>
  </form>
</div>

<!-- Modal selector de columnas (igual RH) -->
<div class="modal fade" id="modalColumns_ptAgendaTable" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content hr-modal">
      <div class="modal-header hr-modal">
        <h5 class="modal-title hr-modal">Mostrar / Ocultar columnas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body hr-modal">
        <p class="text-muted mb-2">Elige qué columnas quieres ver en la tabla.</p>
        <div id="columnsChecklist_ptAgendaTable" class="row g-2"><!-- se llena por JS --></div>
      </div>
      <div class="modal-footer hr-modal">
        <button class="btn btn-ghost" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-brand" id="saveColumns_ptAgendaTable">Aplicar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Ver (Agenda) -->
<div class="modal fade" id="modalAgendaView" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content hr-modal">
      <div class="modal-header hr-modal">
        <h5 class="modal-title hr-modal">Detalle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body hr-modal" id="ptAgendaViewBody">
        <div class="text-center py-5">
          <div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div>
        </div>
      </div>
      <div class="modal-footer hr-modal">
        <button class="btn btn-ghost" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Editar (Agenda) -->
<div class="modal fade" id="modalAgendaEdit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content hr-modal">
      <div class="modal-header hr-modal">
        <h5 class="modal-title hr-modal">Editar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form id="ptAgendaEditForm">
        <div class="modal-body hr-modal">
          <input type="hidden" name="task_id" id="ptAgendaEditTaskId" value="">

          <div class="row g-3">
            <div class="col-12 col-md-4">
              <label class="form-label">Tipo</label>
              <input type="text" class="form-control" id="ptAgendaEditTipo" value="" readonly>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Estado</label>
              <select class="form-select" name="status" id="ptAgendaEditStatus">
                <option value="En tiempo">En tiempo</option>
                <option value="En proceso">En proceso</option>
                <option value="Pausada">Pausada</option>
                <option value="Vencida">Vencida</option>
                <option value="Terminada">Terminada</option>
                <option value="Auditada">Auditada</option>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Prioridad</label>
              <select class="form-select" name="priority" id="ptAgendaEditPriority">
                <option value="Normal">Normal</option>
                <option value="Importante">Importante</option>
                <option value="Urgente">Urgente</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Título</label>
              <input type="text" class="form-control" name="title" id="ptAgendaEditTitle" required>
            </div>
            <div class="col-12">
              <label class="form-label">Descripción</label>
              <textarea class="form-control" name="description" id="ptAgendaEditDescription" rows="3"></textarea>
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Inicia</label>
              <input type="date" class="form-control" name="start_date" id="ptAgendaEditStartDate">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Vence</label>
              <input type="date" class="form-control" name="due_date" id="ptAgendaEditDueDate">
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label">Unidad</label>
              <select class="form-select" name="unit_id" id="ptAgendaEditUnit">
                <option value="">—</option>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Negocio</label>
              <select class="form-select" name="business_id" id="ptAgendaEditBusiness">
                <option value="">—</option>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Responsable</label>
              <select class="form-select" name="assignee" id="ptAgendaEditAssignee">
                <option value="">—</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer hr-modal">
          <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-brand" id="ptAgendaEditSaveBtn">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Tabla (igual RH) -->
<div class="hr-table-wrapper reveal">
  <div class="ix-table-card">
    <div class="table-responsive ix-pt-table-scroll" id="ptAgendaTableScroll">
      <table class="table table-hover align-middle ix-pt-table" id="ptAgendaTable">
        <thead>
          <tr>
            <th scope="col" data-col-key="sel" class="text-center"><input type="checkbox" id="ptAgendaSelectAll" aria-label="Seleccionar todos"></th>
            <th scope="col" data-col-key="id" class="text-center">ID</th>
            <th scope="col" data-col-key="company_id" class="text-center">Empresa</th>
            <th scope="col" data-col-key="type" class="text-center">Tipo</th>
            <th scope="col" data-col-key="folio">Folio</th>
            <th scope="col" data-col-key="title">Título</th>
            <th scope="col" data-col-key="description">Descripción</th>
            <th scope="col" data-col-key="priority" class="text-center">Prioridad</th>
            <th scope="col" data-col-key="project">Proyecto</th>
            <th scope="col" data-col-key="weight" class="text-center">Ponderación</th>
            <th scope="col" data-col-key="unit_id" class="text-center">Unidad ID</th>
            <th scope="col" data-col-key="unit" class="text-center">Unidad</th>
            <th scope="col" data-col-key="business_id" class="text-center">Negocio ID</th>
            <th scope="col" data-col-key="biz" class="text-center">Negocio</th>
            <th scope="col" data-col-key="start">Inicia</th>
            <th scope="col" data-col-key="due">Vence</th>
            <th scope="col" data-col-key="end">Finaliza</th>
            <th scope="col" data-col-key="state" class="text-center">Estado</th>
            <th scope="col" data-col-key="creator_id" class="text-center">Creador ID</th>
            <th scope="col" data-col-key="creator" class="text-center">Creador</th>
            <th scope="col" data-col-key="assignee_id" class="text-center">Responsable ID</th>
            <th scope="col" data-col-key="assignee" class="text-center">Responsable</th>
            <th scope="col" data-col-key="files" class="text-center">Archivos</th>
            <th scope="col" data-col-key="created_at">Creado</th>
            <th scope="col" data-col-key="updated_at">Actualizado</th>
            <th scope="col" data-col-key="actions" class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody id="ptAgendaTbody">
          <tr>
            <td colspan="26" class="text-center py-5">
              <div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div>
              <p class="text-muted mt-3 mb-0">Cargando datos...</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  (function() {
    'use strict';

    let currentAgendaFilters = {};
    let agendaDefaultMode = true; // Al entrar: hoy + pendientes. Se desactiva al aplicar filtros.
    let currentAgendaItems = [];
    let agendaCatalogs = { units: [], businesses: [], employees: [], assignableUsers: [] };
    let agendaAssigneeSelected = new Set();
    let agendaStatusSelected = new Set();

    const PT_CTX = (() => {
      const b = document.body;
      const userId = b?.getAttribute('data-user-id') || '0';
      const companyId = b?.getAttribute('data-company-id') || '0';
      return { userId, companyId };
    })();

    function storageKey(base) {
      return `pt:${PT_CTX.companyId}:${PT_CTX.userId}:${base}`;
    }

    function storageGet(key, fallbackKey = '') {
      try {
        const v = localStorage.getItem(key);
        if (v !== null && v !== undefined) return v;
      } catch (_) {}
      if (fallbackKey) {
        try { return localStorage.getItem(fallbackKey); } catch (_) {}
      }
      return null;
    }

    function storageSet(key, value) {
      try { localStorage.setItem(key, value); } catch (_) {}
    }

    // Función para escapar HTML
    function escapeHtml(text) {
      if (!text) return '';
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };
      return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    // Función para formatear fechas
    function formatDate(dateStr) {
      if (!dateStr) return '-';
      try {
        const date = new Date(dateStr);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
      } catch (e) {
        return dateStr;
      }
    }

    function formatDateTime(dateStr) {
      if (!dateStr) return '-';
      try {
        const date = new Date(dateStr);
        const t = date.getTime();
        if (!Number.isFinite(t)) return String(dateStr);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        const hh = String(date.getHours()).padStart(2, '0');
        const mm = String(date.getMinutes()).padStart(2, '0');
        return `${day}/${month}/${year} ${hh}:${mm}`;
      } catch (e) {
        return String(dateStr);
      }
    }

    function displayPersonLabel(nameOrEmpty, emailOrEmpty) {
      const name = String(nameOrEmpty || '').trim();
      if (name) {
        // Si viene en formato "Nombre (email)", mostrar solo "Nombre"
        const m = name.match(/^(.+?)\s*\([^\)]*\)\s*$/);
        const cleaned = (m && m[1]) ? String(m[1]).trim() : name;
        // Si lo que quedó parece email, mostrar alias
        if (cleaned.includes('@')) {
          const parts = cleaned.split('@');
          if (parts[0]) return parts[0];
        }
        return cleaned;
      }

      const email = String(emailOrEmpty || '').trim();
      if (email && email.includes('@')) {
        return email.split('@')[0] || email;
      }
      return email || '-';
    }

    function getPriorityRank(nivel) {
      const value = String(nivel || '').trim();
      const order = { 'Urgente': 0, 'Importante': 1, 'Normal': 2 };
      return order[value] !== undefined ? order[value] : 99;
    }

    function getFilesCount(archivosRaw) {
      if (archivosRaw === null || archivosRaw === undefined) return 0;
      if (Array.isArray(archivosRaw)) return archivosRaw.length;
      const s = String(archivosRaw || '').trim();
      if (!s) return 0;
      try {
        const parsed = JSON.parse(s);
        if (Array.isArray(parsed)) return parsed.length;
        if (parsed && typeof parsed === 'object') return Object.keys(parsed).length;
      } catch (_) {}
      return 1;
    }

    function getLocalTodayYmd() {
      const now = new Date();
      const y = now.getFullYear();
      const m = String(now.getMonth() + 1).padStart(2, '0');
      const d = String(now.getDate()).padStart(2, '0');
      return `${y}-${m}-${d}`;
    }

    function dateObjToYmd(dateObj) {
      if (!(dateObj instanceof Date) || Number.isNaN(dateObj.getTime())) return '';
      const y = dateObj.getFullYear();
      const m = String(dateObj.getMonth() + 1).padStart(2, '0');
      const d = String(dateObj.getDate()).padStart(2, '0');
      return `${y}-${m}-${d}`;
    }

    function getWeekRangeMondaySundayYmd() {
      const now = new Date();
      const day = now.getDay(); // 0=Dom..6=Sáb
      const diffToMonday = (day + 6) % 7;
      const monday = new Date(now);
      monday.setDate(now.getDate() - diffToMonday);
      const sunday = new Date(monday);
      sunday.setDate(monday.getDate() + 6);
      return { from: dateObjToYmd(monday), to: dateObjToYmd(sunday) };
    }

    function getMonthRangeYmd() {
      const now = new Date();
      const first = new Date(now.getFullYear(), now.getMonth(), 1);
      const last = new Date(now.getFullYear(), now.getMonth() + 1, 0);
      return { from: dateObjToYmd(first), to: dateObjToYmd(last) };
    }

    function getStatusPill(status) {
      const value = (status || '').trim();
      const variant = (() => {
        switch (value) {
          case 'Terminada':
          case 'Auditada':
          case 'En tiempo':
            return 'ok';
          case 'En proceso':
            return 'info';
          case 'Pausada':
            return 'warn';
          case 'Vencida':
            return 'danger';
          default:
            return 'muted';
        }
      })();
      return `<span class="ix-pill ix-pill--${variant}">${escapeHtml(value || 'Desconocido')}</span>`;
    }

    function getTypePill(tipo) {
      const value = (tipo || '').trim() || 'Tarea';
      const variant = value === 'Tarea' ? 'info' : value === 'Proyecto' ? 'muted' : value === 'Proceso' ? 'warn' : 'muted';
      return `<span class="ix-pill ix-pill--${variant}">${escapeHtml(value)}</span>`;
    }

    function getStatusRank(status) {
      const value = (status || '').trim();
      const order = {
        'Vencida': 0,
        'En tiempo': 1,
        'En proceso': 2,
        'Terminada': 3,
        'Auditada': 4,
        'Pausada': 5
      };
      return order[value] !== undefined ? order[value] : 99;
    }

    function parseDateToTs(dateStr) {
      if (!dateStr) return null;
      const d = new Date(dateStr);
      const t = d.getTime();
      return Number.isFinite(t) ? t : null;
    }

    function getSortValue(item, colKey) {
      const it = item || {};
      switch (colKey) {
        case 'id': return Number(it.id || 0);
        case 'company_id': return Number(it.company_id || 0);
        case 'type': return String(it.tipo || '');
        case 'folio': return String(it.folio || it.id || '');
        case 'title': return String(it.titulo || it.title || '');
        case 'description': return String(it.descripcion || it.description || '');
        case 'priority': return getPriorityRank(it.nivel);
        case 'project': return String(it.proyecto_asignado || '');
        case 'weight': return Number(it.ponderacion || 0);
        case 'unit_id': return Number(it.unit_id || 0);
        case 'unit': return String(it.unit_nombre || '');
        case 'business_id': return Number(it.business_id || 0);
        case 'biz': return String(it.business_nombre || '');
        case 'start': return parseDateToTs(it.fecha_inicio || it.start_date);
        case 'due': return parseDateToTs(it.fecha_entrega || it.due_date);
        case 'end': return parseDateToTs(it.fecha_fin);
        case 'state': return getStatusRank(it.status);
        case 'creator_id': return Number(it.usuario_creador || 0);
        case 'creator': return String(it.creador_nombre || it.creador_email || '');
        case 'assignee_id': return Number(it.usuario_delegado || 0);
        case 'assignee': return String(it.delegado_nombre || it.delegado_email || '');
        case 'files': return getFilesCount(it.archivos);
        case 'created_at': return parseDateToTs(it.created_at);
        case 'updated_at': return parseDateToTs(it.updated_at);
        default: return String(it[colKey] ?? '');
      }
    }

    function getCsrfToken() {
      return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function reloadAgenda() {
      const filters = { ...(currentAgendaFilters || {}) };
      if (filters.__agenda_default) {
        filters.__agenda_date = getLocalTodayYmd();
      }
      return cargarAgenda(filters);
    }

    async function apiPost(url, formData) {
      const fd = formData instanceof FormData ? formData : new FormData();
      const csrf = getCsrfToken();
      if (!fd.get('csrf_token')) fd.append('csrf_token', csrf);
      const headers = { 'Accept': 'application/json' };
      if (csrf) headers['X-CSRF-Token'] = csrf;
      const res = await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers });
      const contentType = String(res.headers.get('content-type') || '');
      const raw = await res.text().catch(() => '');
      let data = {};
      try {
        data = raw ? JSON.parse(raw) : {};
      } catch {
        data = {};
      }
      if (!res.ok || !data || !data.ok) {
        const err = data?.error || data?.message || (!contentType.includes('application/json') ? 'Respuesta inesperada del servidor (no JSON)' : `HTTP ${res.status}`);
        throw new Error(String(err));
      }
      return data;
    }

    async function apiGetJson(url) {
      const res = await fetch(url, { method: 'GET', credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
      const contentType = String(res.headers.get('content-type') || '');
      const raw = await res.text().catch(() => '');
      let data = {};
      try {
        data = raw ? JSON.parse(raw) : {};
      } catch {
        data = {};
      }
      if (!res.ok || !data || !data.ok) {
        const err = data?.error || data?.message || (!contentType.includes('application/json') ? 'Respuesta inesperada del servidor (no JSON)' : `HTTP ${res.status}`);
        throw new Error(String(err));
      }
      return data;
    }

    function ymdFromAnyDate(val) {
      const s = String(val || '').trim();
      if (!s) return '';
      const m = s.match(/^(\d{4}-\d{2}-\d{2})/);
      return m ? m[1] : '';
    }

    function getAgendaItemById(taskId) {
      const idStr = String(taskId || '');
      const items = Array.isArray(currentAgendaItems) ? currentAgendaItems : [];
      return items.find((it) => String(it?.id || '') === idStr) || null;
    }

    function setAgendaItemFields(taskId, patch = {}) {
      const idStr = String(taskId || '');
      if (!idStr) return;
      currentAgendaItems = (Array.isArray(currentAgendaItems) ? currentAgendaItems : []).map((it) => {
        if (String(it?.id || '') !== idStr) return it;
        return { ...(it || {}), ...(patch || {}) };
      });
    }

    function formatDateCellFromYmd(ymd) {
      const s = String(ymd || '').trim();
      if (!s) return '-';
      return formatDate(s);
    }

    async function agendaInlineUpdateField(taskId, field, value) {
      const fd = new FormData();
      fd.append('task_id', String(taskId));
      fd.append('field', String(field));
      fd.append('value', value === null || value === undefined ? '' : String(value));
      return apiPost('/modules/processes_tasks/api/update_field.php', fd);
    }

    function createInlineSelect(options, currentValue) {
      const sel = document.createElement('select');
      sel.className = 'form-select form-select-sm w-100';
      (options || []).forEach((opt) => {
        const o = document.createElement('option');
        o.value = String(opt.value);
        o.textContent = String(opt.label);
        sel.appendChild(o);
      });
      sel.value = currentValue !== null && currentValue !== undefined ? String(currentValue) : '';
      return sel;
    }

    function createInlineInput(type, currentValue) {
      const input = document.createElement('input');
      input.type = type;
      input.className = 'form-control form-control-sm w-100';
      input.value = currentValue !== null && currentValue !== undefined ? String(currentValue) : '';
      return input;
    }

    function getEmployeeUserId(emp) {
      const uid = Number(emp?.user_id ?? 0);
      return Number.isFinite(uid) && uid > 0 ? String(uid) : '';
    }

    function normalizeAssignableUsers(list) {
      const arr = Array.isArray(list) ? list : [];
      const seen = new Set();
      const out = [];
      for (const u of arr) {
        const uid = Number(u?.user_id ?? u?.id ?? 0);
        if (!Number.isFinite(uid) || uid <= 0) continue;
        const key = String(uid);
        if (seen.has(key)) continue;
        seen.add(key);
        const name = String(u?.name ?? u?.full_name ?? u?.email ?? key).trim() || key;
        out.push({ user_id: key, name });
      }
      out.sort((a, b) => String(a.name).localeCompare(String(b.name), 'es', { sensitivity: 'base', numeric: true }));
      return out;
    }

    function deriveAssigneesFromAgendaItems() {
      const items = Array.isArray(currentAgendaItems) ? currentAgendaItems : [];
      const map = new Map();
      items.forEach((it) => {
        const uid = Number(it?.usuario_delegado ?? 0);
        if (!Number.isFinite(uid) || uid <= 0) return;
        const label = String(it?.delegado_nombre || it?.delegado_email || uid);
        if (!map.has(String(uid))) map.set(String(uid), label);
      });
      return Array.from(map.entries())
        .map(([value, label]) => ({ value, label }))
        .sort((a, b) => String(a.label).localeCompare(String(b.label), 'es', { sensitivity: 'base', numeric: true }));
    }

    function initAgendaInlineEditing() {
      const tbody = document.getElementById('ptAgendaTbody');
      if (!tbody) return;

      let active = null; // { td, originalHtml, taskId, colKey }
      let inFlight = false;

      const cleanup = (snapshot = null) => {
        const a = snapshot || active;
        if (!a) return;
        const { td, originalHtml } = a;
        if (td && typeof originalHtml === 'string') td.innerHTML = originalHtml;
        if (!snapshot) active = null;
      };

      const commit = async (newValue, ctx = {}) => {
        if (!active || inFlight) return;
        inFlight = true;
        const snapshot = active;
        active = null;

        const { td, taskId, colKey } = snapshot;
        const tr = td?.closest('tr');
        if (!td || !taskId) {
          inFlight = false;
          return cleanup(snapshot);
        }

        td.classList.add('opacity-50');

        let updatedAtFromApi = '';

        try {
          if (colKey === 'assignee') {
            const res = await agendaInlineUpdateField(taskId, 'assigneeId', newValue);
            updatedAtFromApi = String(res?.item?.updated_at || '');
            const assignable = normalizeAssignableUsers(agendaCatalogs.assignableUsers);
            const employees = Array.isArray(agendaCatalogs.employees) ? agendaCatalogs.employees : [];
            const finalId = (res?.item?.usuario_delegado ?? res?.item?.assignee_id ?? null);
            const finalIdStr = finalId ? String(finalId) : (newValue ? String(newValue) : '');
            const chosen = assignable.find((u) => String(u?.user_id ?? '') === String(finalIdStr || ''))
              || assignable.find((u) => String(u?.user_id ?? '') === String(newValue || ''))
              || employees.find((e) => String(e?.user_id ?? '') === String(finalIdStr || ''))
              || employees.find((e) => String(e?.user_id ?? '') === String(newValue || ''));
            const chosenName = chosen?.name ? String(chosen.name) : '';
            const label = chosenName
              ? displayPersonLabel(chosenName, '')
              : (finalIdStr ? String(finalIdStr) : '-');
            const idCell = tr?.querySelector('[data-col-key="assignee_id"]');
            const nameCell = tr?.querySelector('[data-col-key="assignee"]');
            if (idCell) idCell.textContent = finalIdStr ? String(finalIdStr) : '-';
            if (nameCell) nameCell.textContent = label;

            setAgendaItemFields(taskId, {
              usuario_delegado: finalIdStr ? Number(finalIdStr) : (newValue ? Number(newValue) : null),
              delegado_nombre: chosen?.name ? String(chosen.name) : null,
              delegado_email: null
            });
          } else if (colKey === 'priority') {
            const res = await agendaInlineUpdateField(taskId, 'priority', newValue);
            updatedAtFromApi = String(res?.item?.updated_at || '');
            const nivel = res?.item?.nivel ?? newValue;
            td.textContent = String(nivel || '-');
            setAgendaItemFields(taskId, { nivel: String(nivel || 'Normal') });
          } else if (colKey === 'weight') {
            const sendVal = newValue === '' ? '' : String(newValue);
            const res = await agendaInlineUpdateField(taskId, 'auditScore', sendVal);
            updatedAtFromApi = String(res?.item?.updated_at || '');
            const ponder = res?.item?.ponderacion;
            const display = (ponder === null || ponder === undefined || ponder === '') ? '-' : String(ponder);
            td.textContent = display;
            setAgendaItemFields(taskId, { ponderacion: ponder === null || ponder === undefined || ponder === '' ? null : Number(ponder) });
          } else if (colKey === 'end') {
            const sendVal = String(newValue || '').trim();
            const res = await agendaInlineUpdateField(taskId, 'end', sendVal);
            updatedAtFromApi = String(res?.item?.updated_at || '');
            const ymd = ymdFromAnyDate(res?.item?.fecha_fin ?? sendVal);
            td.textContent = formatDateCellFromYmd(ymd);
            setAgendaItemFields(taskId, { fecha_fin: ymd || null });
          }

          // Siempre actualizar Updated At si existe
          const updCell = tr?.querySelector('[data-col-key="updated_at"]');
          const updatedAt = ymdFromAnyDate(updatedAtFromApi) ? String(updatedAtFromApi) : (updatedAtFromApi || '');
          if (updatedAt) {
            if (updCell) updCell.textContent = formatDateTime(updatedAt);
            setAgendaItemFields(taskId, { updated_at: updatedAt });
          }

        } catch (err) {
          console.error('Inline update error', err);
          try {
            td.classList.add('pt-inline-save-error');
            td.setAttribute('title', 'No se pudo guardar');
            setTimeout(() => {
              td.classList.remove('pt-inline-save-error');
            }, 1800);
          } catch (_) {}
          cleanup(snapshot);
          return;
        } finally {
          td.classList.remove('opacity-50');
          inFlight = false;
        }
      };

      tbody.addEventListener('dblclick', async (e) => {
        const td = e.target?.closest?.('td[data-col-key]');
        if (!td) return;
        if (td.querySelector('select, input, textarea, button, a')) return;
        const colKey = td.getAttribute('data-col-key');
        if (!['assignee', 'priority', 'weight', 'end'].includes(colKey)) return;

        const tr = td.closest('tr');
        const taskId = tr?.getAttribute('data-pt-id') || '';
        if (!taskId || !/^[0-9]+$/.test(String(taskId))) return;

        // Cerrar editor anterior si existe
        cleanup();

        active = { td, originalHtml: td.innerHTML, taskId, colKey };
        const item = getAgendaItemById(taskId) || {};

        e.preventDefault();
        e.stopPropagation();

        if (colKey === 'assignee') {
          // Asegurar catálogo disponible (por si el usuario edita muy rápido)
          if ((!Array.isArray(agendaCatalogs.assignableUsers) || agendaCatalogs.assignableUsers.length === 0)
              && (!Array.isArray(agendaCatalogs.employees) || agendaCatalogs.employees.length === 0)) {
            await cargarCatalogosFiltros(true);
          }

          const assignable = normalizeAssignableUsers(agendaCatalogs.assignableUsers);
          const optsUsers = assignable.map((u) => ({ value: String(u.user_id), label: String(u.name) }));

          let finalOpts = optsUsers;

          // Fallback seguro: employees, pero solo con user_id válido (users.id)
          if (finalOpts.length === 0) {
            const employees = Array.isArray(agendaCatalogs.employees) ? agendaCatalogs.employees : [];
            const seen = new Set();
            finalOpts = employees.map((emp) => {
              const uid = getEmployeeUserId(emp);
              if (!uid || seen.has(uid)) return null;
              seen.add(uid);
              return { value: uid, label: String(emp?.name ?? uid) };
            }).filter(Boolean);
          }

          // Si no hay opciones válidas, evita ofrecer IDs legacy
          if (finalOpts.length === 0) {
            console.warn('⚠️ Inline edit Responsable: sin opciones válidas (assignable_users vacío y employees sin user_id).');
            cleanup();
            return;
          }

          const opts = [{ value: '', label: '-' }].concat(finalOpts);
          const current = item?.usuario_delegado ?? '';
          const originalValue = String(current ?? '');
          const sel = createInlineSelect(opts, current);
          td.innerHTML = '';
          td.appendChild(sel);
          sel.focus();
          sel.addEventListener('change', () => commit(sel.value));
          sel.addEventListener('blur', () => {
            // Si no cambió el valor, solo cerrar.
            if (!active) return;
            if (String(sel.value || '') === originalValue) {
              cleanup();
              return;
            }
            commit(sel.value);
          });
          sel.addEventListener('keydown', (ev) => {
            if (ev.key === 'Escape') { ev.preventDefault(); cleanup(); }
            if (ev.key === 'Enter') { ev.preventDefault(); commit(sel.value); }
          });
          return;
        }

        if (colKey === 'priority') {
          const opts = [
            { value: 'Normal', label: 'Normal' },
            { value: 'Importante', label: 'Importante' },
            { value: 'Urgente', label: 'Urgente' }
          ];
          const current = String(item?.nivel || td.textContent || 'Normal').trim() || 'Normal';
          const sel = createInlineSelect(opts, current);
          td.innerHTML = '';
          td.appendChild(sel);
          sel.focus();
          sel.addEventListener('change', () => commit(sel.value));
          sel.addEventListener('keydown', (ev) => {
            if (ev.key === 'Escape') { ev.preventDefault(); cleanup(); }
            if (ev.key === 'Enter') { ev.preventDefault(); commit(sel.value); }
          });
          return;
        }

        if (colKey === 'weight') {
          const current = (item?.ponderacion === null || item?.ponderacion === undefined) ? '' : String(item.ponderacion);
          const input = createInlineInput('number', current);
          input.min = '0';
          input.step = '1';
          td.innerHTML = '';
          td.appendChild(input);
          input.focus();
          input.select();
          input.addEventListener('blur', () => commit(input.value));
          input.addEventListener('keydown', (ev) => {
            if (ev.key === 'Escape') { ev.preventDefault(); cleanup(); }
            if (ev.key === 'Enter') { ev.preventDefault(); commit(input.value); }
          });
          return;
        }

        if (colKey === 'end') {
          const ymd = ymdFromAnyDate(item?.fecha_fin);
          const input = createInlineInput('date', ymd);
          td.innerHTML = '';
          td.appendChild(input);
          input.focus();
          input.addEventListener('change', () => commit(input.value));
          input.addEventListener('blur', () => commit(input.value));
          input.addEventListener('keydown', (ev) => {
            if (ev.key === 'Escape') { ev.preventDefault(); cleanup(); }
            if (ev.key === 'Enter') { ev.preventDefault(); commit(input.value); }
          });
        }
      });

      // Nota: no cancelamos por click fuera; blur/change maneja el guardado.
    }

    function ensureEditSelectOptions() {
      const unitSel = document.getElementById('ptAgendaEditUnit');
      const bizSel = document.getElementById('ptAgendaEditBusiness');
      const empSel = document.getElementById('ptAgendaEditAssignee');
      if (unitSel) populateSelectEl(unitSel, agendaCatalogs.units, 'id', 'name');
      if (bizSel) populateSelectEl(bizSel, agendaCatalogs.businesses, 'id', 'name');
      if (empSel) populateSelectEl(empSel, agendaCatalogs.employees, 'user_id', 'name');
    }

    async function agendaView(id) {
      const body = document.getElementById('ptAgendaViewBody');
      const modalEl = document.getElementById('modalAgendaView');
      if (!body || !modalEl) return;

      body.innerHTML = `<div class="text-center py-5"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div></div>`;
      try { new bootstrap.Modal(modalEl).show(); } catch (_) {}

      try {
        const data = await apiGetJson(`/modules/processes_tasks/api/read.php?id=${encodeURIComponent(String(id))}`);
        const it = data.data || {};
        body.innerHTML = `
          <div class="row g-3">
            <div class="col-12">
              <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                <div>
                  <div class="text-muted">${escapeHtml(it.tipo || '')} · ${escapeHtml(it.folio || it.id || '')}</div>
                  <div class="h5 mb-0">${escapeHtml(it.titulo || it.title || '-') }</div>
                </div>
                <div class="text-end">${getStatusPill(it.status)}</div>
              </div>
            </div>
            <div class="col-12"><hr class="my-0"></div>
            <div class="col-12 col-md-6"><div class="text-muted small">Unidad</div><div>${escapeHtml(it.unit_nombre || '-') }</div></div>
            <div class="col-12 col-md-6"><div class="text-muted small">Negocio</div><div>${escapeHtml(it.business_nombre || '-') }</div></div>
            <div class="col-12 col-md-6"><div class="text-muted small">Inicia</div><div>${formatDate(it.fecha_inicio || it.start_date) }</div></div>
            <div class="col-12 col-md-6"><div class="text-muted small">Vence</div><div>${formatDate(it.fecha_entrega || it.due_date) }</div></div>
            <div class="col-12 col-md-6"><div class="text-muted small">Responsable</div><div>${escapeHtml(it.delegado_nombre || it.delegado_email || '-') }</div></div>
            <div class="col-12 col-md-6"><div class="text-muted small">Prioridad</div><div>${escapeHtml(it.nivel || '-') }</div></div>
            <div class="col-12"><div class="text-muted small">Descripción</div><div class="text-muted">${escapeHtml(it.descripcion || it.description || '-') }</div></div>
          </div>
        `;
      } catch (e) {
        body.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(e?.message || 'Error al cargar el detalle')}</div>`;
      }
    }

    async function agendaEdit(id) {
      const modalEl = document.getElementById('modalAgendaEdit');
      if (!modalEl) return;
      try { new bootstrap.Modal(modalEl).show(); } catch (_) {}

      try {
        ensureEditSelectOptions();
        const data = await apiGetJson(`/modules/processes_tasks/api/read.php?id=${encodeURIComponent(String(id))}`);
        const it = data.data || {};

        document.getElementById('ptAgendaEditTaskId').value = String(it.id || id);
        document.getElementById('ptAgendaEditTipo').value = String(it.tipo || '');
        document.getElementById('ptAgendaEditTitle').value = String(it.titulo || it.title || '');
        document.getElementById('ptAgendaEditDescription').value = String(it.descripcion || it.description || '');
        document.getElementById('ptAgendaEditStartDate').value = ymdFromAnyDate(it.fecha_inicio || it.start_date);
        document.getElementById('ptAgendaEditDueDate').value = ymdFromAnyDate(it.fecha_entrega || it.due_date);
        document.getElementById('ptAgendaEditStatus').value = String(it.status || 'En tiempo');
        document.getElementById('ptAgendaEditPriority').value = String(it.nivel || 'Normal');
        document.getElementById('ptAgendaEditUnit').value = String(it.unit_id || '');
        document.getElementById('ptAgendaEditBusiness').value = String(it.business_id || '');
        document.getElementById('ptAgendaEditAssignee').value = String(it.usuario_delegado || '');
      } catch (e) {
        alert('Error al cargar para edición: ' + (e?.message || 'error'));
      }
    }

    async function agendaDuplicate(id) {
      if (!confirm('¿Quieres crear una copia de este elemento?')) return;
      try {
        const fd = new FormData();
        fd.append('task_id', String(id));
        await apiPost('/modules/processes_tasks/api/duplicate.php', fd);
        await reloadAgenda();
      } catch (e) {
        alert('Error al duplicar: ' + (e?.message || 'error'));
      }
    }

    async function agendaDelete(id) {
      if (!confirm('¿Estás seguro de que quieres eliminar este elemento?')) return;
      try {
        const fd = new FormData();
        fd.append('task_id', String(id));
        await apiPost('/modules/processes_tasks/api/delete.php', fd);
        await reloadAgenda();
      } catch (e) {
        alert('Error al eliminar: ' + (e?.message || 'error'));
      }
    }

    async function agendaComplete(id) {
      if (!confirm('¿Marcar como Terminada?')) return;
      try {
        const fd = new FormData();
        fd.append('task_id', String(id));
        fd.append('status', 'Terminada');
        await apiPost('/modules/processes_tasks/api/update.php', fd);
        await reloadAgenda();
      } catch (e) {
        alert('Error al completar: ' + (e?.message || 'error'));
      }
    }

    async function agendaExecute(id) {
      if (!confirm('¿Quieres ejecutar este proceso? Se pondrá en "En proceso".')) return;
      try {
        const fd = new FormData();
        fd.append('task_id', String(id));
        fd.append('status', 'En proceso');
        await apiPost('/modules/processes_tasks/api/update.php', fd);
        await reloadAgenda();
      } catch (e) {
        alert('Error al ejecutar: ' + (e?.message || 'error'));
      }
    }

    const sortState = (() => {
      const raw = storageGet(storageKey('ptAgendaTable.sort.v1'));
      if (!raw) return { key: '', dir: 'asc' };
      try {
        const p = JSON.parse(raw);
        const key = typeof p.key === 'string' ? p.key : '';
        const dir = p.dir === 'desc' ? 'desc' : 'asc';
        return { key, dir };
      } catch {
        return { key: '', dir: 'asc' };
      }
    })();

    function persistSortState() {
      storageSet(storageKey('ptAgendaTable.sort.v1'), JSON.stringify(sortState));
    }

    function applySort(items) {
      const key = (sortState.key || '').trim();
      if (!key) return items;

      const dir = sortState.dir === 'desc' ? -1 : 1;
      const sorted = (items || []).slice();
      sorted.sort((a, b) => {
        const va = getSortValue(a, key);
        const vb = getSortValue(b, key);

        const aNull = (va === null || va === undefined || va === '');
        const bNull = (vb === null || vb === undefined || vb === '');
        if (aNull && bNull) return 0;
        if (aNull) return 1;
        if (bNull) return -1;

        if (typeof va === 'number' && typeof vb === 'number') {
          return (va - vb) * dir;
        }
        return String(va).localeCompare(String(vb), 'es', { sensitivity: 'base', numeric: true }) * dir;
      });
      return sorted;
    }

    function updateSortIndicators(tableId) {
      const table = document.getElementById(tableId);
      if (!table) return;
      const ths = table.querySelectorAll('thead th[data-col-key]');
      ths.forEach((th) => {
        const k = th.getAttribute('data-col-key') || '';
        const labelRaw = th.getAttribute('data-col-label') || th.textContent.trim();
        const label = String(labelRaw || '').trim();
        if (!th.getAttribute('data-col-label')) th.setAttribute('data-col-label', label);

        // No sort para columnas bloqueadas
        if (k === 'sel' || k === 'actions') {
          th.removeAttribute('role');
          th.removeAttribute('tabindex');
          th.removeAttribute('aria-sort');
          th.classList.remove('pt-sortable');

          // Mantener contenido original (p.ej. checkbox select-all)
          if (k === 'sel') {
            if (!th.querySelector('#ptAgendaSelectAll')) {
              th.innerHTML = '<input type="checkbox" id="ptAgendaSelectAll" aria-label="Seleccionar todos">';
            }
            if (!th.getAttribute('data-col-label') || !th.getAttribute('data-col-label').trim()) {
              th.setAttribute('data-col-label', 'Seleccionar');
            }
          } else {
            th.textContent = label;
          }
          return;
        }

        th.setAttribute('role', 'button');
        th.setAttribute('tabindex', '0');
        th.classList.add('pt-sortable');

        const isActive = sortState.key === k;
        const dir = isActive ? sortState.dir : '';
        const aria = isActive ? (dir === 'desc' ? 'descending' : 'ascending') : 'none';
        th.setAttribute('aria-sort', aria);

        const icon = !isActive
          ? '<i class="bi bi-arrow-down-up ms-1 text-muted" aria-hidden="true"></i>'
          : (dir === 'desc'
              ? '<i class="bi bi-sort-down ms-1" aria-hidden="true"></i>'
              : '<i class="bi bi-sort-up ms-1" aria-hidden="true"></i>');

        th.innerHTML = `<span class="d-inline-flex align-items-center">${escapeHtml(label)}${icon}</span>`;
      });
    }

    function setAgendaMultiDropdownLabel(btnEl, selectedLabels) {
      if (!btnEl) return;
      const labels = Array.isArray(selectedLabels) ? selectedLabels.filter(Boolean) : [];
      if (labels.length === 0) {
        btnEl.textContent = 'Todos';
      } else if (labels.length <= 2) {
        btnEl.textContent = labels.join(', ');
      } else {
        btnEl.textContent = `${labels.length} seleccionados`;
      }
    }

    function updateAgendaAssigneeButtonLabel() {
      const btn = document.getElementById('ptAgendaAssigneeBtn');
      const idToName = new Map((agendaCatalogs.employees || []).map((e) => {
        const filterId = String(e?.user_id ?? e?.id ?? '').trim();
        return [filterId, String(e?.name ?? filterId)];
      }));
      const labels = Array.from(agendaAssigneeSelected).map((id) => idToName.get(String(id)) || String(id));
      setAgendaMultiDropdownLabel(btn, labels);
    }

    function updateAgendaStatusButtonLabel() {
      const btn = document.getElementById('ptAgendaStatusBtn');
      setAgendaMultiDropdownLabel(btn, Array.from(agendaStatusSelected));
    }

    function readAgendaFiltersFromUI() {
      return {
        periodo: document.getElementById('ptAgendaPeriodo')?.value || 'por_realizar',
        unit_id: document.getElementById('ptAgendaUnit')?.value || '',
        business_id: document.getElementById('ptAgendaBusiness')?.value || '',
        assignee: Array.from(agendaAssigneeSelected),
        status: Array.from(agendaStatusSelected),
        date_from: document.getElementById('ptAgendaDateFrom')?.value || '',
        date_to: document.getElementById('ptAgendaDateTo')?.value || ''
      };
    }

    function buildAgendaRequestFilters(uiFilters) {
      const filters = { ...(uiFilters || {}) };
      const periodo = (filters.periodo || 'por_realizar').trim();
      const today = getLocalTodayYmd();

      // limpiar campos computados
      delete filters.__agenda_default;
      delete filters.__agenda_date;
      delete filters.agenda_pending_overdue;
      delete filters.agenda_today;

      if (periodo === 'por_realizar') {
        filters.__agenda_default = true;
        filters.__agenda_date = today;
        filters.date_from = '';
        filters.date_to = '';
        return filters;
      }

      if (periodo === 'hoy') {
        filters.date_from = today;
        filters.date_to = today;
        return filters;
      }

      if (periodo === 'semana') {
        const r = getWeekRangeMondaySundayYmd();
        filters.date_from = r.from;
        filters.date_to = r.to;
        return filters;
      }

      if (periodo === 'mes') {
        const r = getMonthRangeYmd();
        filters.date_from = r.from;
        filters.date_to = r.to;
        return filters;
      }

      if (periodo === 'pendientes') {
        filters.agenda_pending_overdue = '1';
        filters.agenda_today = today;
        filters.date_from = '';
        filters.date_to = '';
        return filters;
      }

      // personalizada
      filters.date_from = ymdFromAnyDate(filters.date_from);
      filters.date_to = ymdFromAnyDate(filters.date_to);
      return filters;
    }

    function syncAgendaCustomDateVisibility() {
      const periodo = document.getElementById('ptAgendaPeriodo')?.value || 'por_realizar';
      const show = periodo === 'personalizada';

      const fromWrap = document.getElementById('ptAgendaCustomFromWrap');
      const toWrap = document.getElementById('ptAgendaCustomToWrap');
      if (fromWrap) fromWrap.style.display = show ? '' : 'none';
      if (toWrap) toWrap.style.display = show ? '' : 'none';

      if (!show) {
        const from = document.getElementById('ptAgendaDateFrom');
        const to = document.getElementById('ptAgendaDateTo');
        if (from) from.value = '';
        if (to) to.value = '';
      }
    }

    function populateSelectEl(selectEl, items, valueKey, labelKey) {
      if (!selectEl) return;
      const keepFirst = selectEl.querySelector('option')?.outerHTML || '';
      selectEl.innerHTML = keepFirst;
      (items || []).forEach((item) => {
        if (!item || item[valueKey] === undefined || item[valueKey] === null) return;
        const option = document.createElement('option');
        option.value = String(item[valueKey]);
        option.textContent = String(item[labelKey] ?? item[valueKey]);
        selectEl.appendChild(option);
      });
    }

    function renderAgendaAssigneeMenu() {
      const menu = document.getElementById('ptAgendaAssigneeMenu');
      if (!menu) return;

      const header = `
        <button type="button" class="dropdown-item" data-action="clear">Todos</button>
        <div><hr class="dropdown-divider"></div>
      `;

      const assignable = normalizeAssignableUsers(agendaCatalogs.assignableUsers);
      let list = assignable;

      if (!list.length) {
        const employees = Array.isArray(agendaCatalogs.employees) ? agendaCatalogs.employees : [];
        const seen = new Set();
        list = employees.map((e) => {
          const uid = getEmployeeUserId(e);
          if (!uid || seen.has(uid)) return null;
          seen.add(uid);
          return { user_id: uid, name: String(e?.name ?? uid) };
        }).filter(Boolean);
      }

      if (!list.length) {
        menu.innerHTML = header + '<div class="px-2 text-muted small">Sin colaboradores</div>';
        return;
      }

      const rows = list.map((e) => {
        const id = String(e?.user_id ?? '').trim();
        if (!id) return '';
        const name = String(e?.name ?? id);
        const safeId = id.replace(/[^a-zA-Z0-9_-]/g, '_');
        const inputId = `ptAgendaAssignee_${safeId}`;
        const checked = agendaAssigneeSelected.has(id) ? 'checked' : '';
        return `
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="${escapeHtml(id)}" id="${escapeHtml(inputId)}" ${checked}>
            <label class="form-check-label" for="${escapeHtml(inputId)}">${escapeHtml(name)}</label>
          </div>
        `;
      }).join('');

      menu.innerHTML = header + (rows || '<div class="px-2 text-muted small">Sin colaboradores</div>');
    }

    function wireAgendaMultiDropdownEvents() {
      const assigneeMenu = document.getElementById('ptAgendaAssigneeMenu');
      if (assigneeMenu) {
        assigneeMenu.addEventListener('click', (e) => {
          const clearBtn = e.target.closest('button[data-action="clear"]');
          if (!clearBtn) return;
          agendaAssigneeSelected.clear();
          assigneeMenu.querySelectorAll('input[type="checkbox"]').forEach((cb) => { cb.checked = false; });
          updateAgendaAssigneeButtonLabel();
          e.preventDefault();
        });

        assigneeMenu.addEventListener('change', (e) => {
          const cb = e.target.closest('input[type="checkbox"]');
          if (!cb) return;
          const v = String(cb.value || '').trim();
          if (!v) return;
          if (cb.checked) agendaAssigneeSelected.add(v);
          else agendaAssigneeSelected.delete(v);
          updateAgendaAssigneeButtonLabel();
        });
      }

      const statusMenu = document.getElementById('ptAgendaStatusMenu');
      if (statusMenu) {
        statusMenu.addEventListener('click', (e) => {
          const clearBtn = e.target.closest('button[data-action="clear"]');
          if (!clearBtn) return;
          agendaStatusSelected.clear();
          statusMenu.querySelectorAll('input[type="checkbox"]').forEach((cb) => { cb.checked = false; });
          updateAgendaStatusButtonLabel();
          e.preventDefault();
        });

        statusMenu.addEventListener('change', (e) => {
          const cb = e.target.closest('input[type="checkbox"]');
          if (!cb) return;
          const v = String(cb.value || '').trim();
          if (!v) return;
          if (cb.checked) agendaStatusSelected.add(v);
          else agendaStatusSelected.delete(v);
          updateAgendaStatusButtonLabel();
        });
      }
    }

    async function cargarCatalogosFiltros(force = false) {
      const unitEl = document.getElementById('ptAgendaUnit');
      const businessEl = document.getElementById('ptAgendaBusiness');
      const assigneeMenuEl = document.getElementById('ptAgendaAssigneeMenu');
      if (!force && !unitEl && !businessEl && !assigneeMenuEl) return;
      try {
        const response = await fetch('/modules/processes_tasks/api/form_data_simple.php', {
          method: 'GET',
          credentials: 'same-origin',
          headers: { 'Accept': 'application/json' }
        });
        if (!response.ok) return;
        const contentType = String(response.headers.get('content-type') || '');
        const raw = await response.text().catch(() => '');
        let data = {};
        try {
          data = raw ? JSON.parse(raw) : {};
        } catch {
          console.warn('⚠️ Catálogos: respuesta no JSON', { contentType, sample: String(raw || '').slice(0, 200) });
          return;
        }
        if (!data.ok) return;
        agendaCatalogs = {
          units: Array.isArray(data.units) ? data.units : [],
          businesses: Array.isArray(data.businesses) ? data.businesses : [],
          employees: Array.isArray(data.employees) ? data.employees : [],
          assignableUsers: Array.isArray(data.assignable_users) ? data.assignable_users : []
        };
        populateSelectEl(unitEl, data.units, 'id', 'name');
        populateSelectEl(businessEl, data.businesses, 'id', 'name');
        renderAgendaAssigneeMenu();
        updateAgendaAssigneeButtonLabel();
      } catch (e) {
        console.warn('⚠️ No se pudieron cargar catálogos de filtros:', e);
      }
    }

    function applyStoredColumnVisibility(tableId) {
      const table = document.getElementById(tableId);
      if (!table) return;
      const STORAGE_KEY = storageKey(`${tableId}.columns.hidden.v1`);
      const LEGACY_KEY = `pt.${tableId}.columns.v1`;
      let hidden;
      try {
        hidden = new Set(JSON.parse(storageGet(STORAGE_KEY, LEGACY_KEY) || '[]'));
      } catch {
        hidden = new Set();
      }

      const ths = [...table.querySelectorAll('thead th')];
      const idxToKey = ths.map((th, idx) => th.getAttribute('data-col-key') || `c${idx}`);
      const LOCKED = new Set(['sel', 'actions']);

      [...table.querySelectorAll('tr')].forEach((tr) => {
        [...tr.children].forEach((cell, i) => {
          const key = (cell.getAttribute && cell.getAttribute('data-col-key')) || idxToKey[i];
          if (!key) return;
          if (LOCKED.has(key)) {
            cell.style.display = '';
            return;
          }
          cell.style.display = hidden.has(key) ? 'none' : '';
        });
      });
    }

    function applyStoredColumnOrder(tableId) {
      const table = document.getElementById(tableId);
      if (!table) return;
      const theadRow = table.querySelector('thead tr');
      if (!theadRow) return;

      const STORAGE_KEY = storageKey(`${tableId}.columns.order.v1`);
      const raw = storageGet(STORAGE_KEY);
      let preferred = [];
      try {
        preferred = raw ? JSON.parse(raw) : [];
      } catch {
        preferred = [];
      }
      preferred = Array.isArray(preferred) ? preferred.filter(k => typeof k === 'string') : [];

      const LOCKED_FIRST = 'sel';
      const LOCKED_LAST = 'actions';
      const allKeys = [...theadRow.querySelectorAll('th')].map((th, idx) => th.getAttribute('data-col-key') || `c${idx}`);
      const reorderable = allKeys.filter(k => k && k !== LOCKED_FIRST && k !== LOCKED_LAST);
      const normalizedPref = preferred
        .filter(k => reorderable.includes(k))
        .concat(reorderable.filter(k => !preferred.includes(k)));

      const newKeys = [LOCKED_FIRST, ...normalizedPref, LOCKED_LAST].filter(Boolean);
      const oldKeys = allKeys.slice();

      // Mapear celdas por key usando el orden actual
      const rows = table.querySelectorAll('tr');
      rows.forEach((tr) => {
        const cells = Array.from(tr.children);
        const map = {};
        oldKeys.forEach((k, i) => {
          const cell = cells[i];
          if (!cell) return;
          const ck = cell.getAttribute && cell.getAttribute('data-col-key');
          if (ck) map[ck] = cell;
          else map[k] = cell;
        });
        const next = newKeys.map(k => map[k]).filter(Boolean);
        next.forEach((cell) => tr.appendChild(cell));
      });
    }

    function applyStoredColumnWidths(tableId) {
      const table = document.getElementById(tableId);
      if (!table) return;

      const STORAGE_KEY = storageKey(`${tableId}.columns.widths.v1`);
      let widths = {};
      try {
        widths = JSON.parse(storageGet(STORAGE_KEY) || '{}') || {};
      } catch {
        widths = {};
      }

      const ths = [...table.querySelectorAll('thead th')];
      const keys = ths.map((th, idx) => th.getAttribute('data-col-key') || `c${idx}`);
      const rows = [...table.querySelectorAll('tr')];

      keys.forEach((key, idx) => {
        const raw = widths[key];
        const px = Number.parseInt(String(raw || ''), 10);
        if (!Number.isFinite(px) || px <= 0) return;
        const w = Math.max(60, px);
        rows.forEach((tr) => {
          const cell = (tr.querySelector && tr.querySelector(`[data-col-key="${key}"]`)) || tr.children[idx];
          if (!cell) return;
          cell.style.width = `${w}px`;
          cell.style.minWidth = `${w}px`;
          cell.style.maxWidth = `${w}px`;
        });
      });
    }

    function initResizableColumns(tableId) {
      const table = document.getElementById(tableId);
      if (!table) return;
      const theadRow = table.querySelector('thead tr');
      if (!theadRow) return;

      const LOCKED = new Set(['sel', 'actions']);
      const STORAGE_KEY = storageKey(`${tableId}.columns.widths.v1`);

      const readWidths = () => {
        try {
          return JSON.parse(storageGet(STORAGE_KEY) || '{}') || {};
        } catch {
          return {};
        }
      };
      const saveWidth = (key, px) => {
        const widths = readWidths();
        widths[key] = px;
        storageSet(STORAGE_KEY, JSON.stringify(widths));
      };

      const getKeys = () => [...table.querySelectorAll('thead th')]
        .map((th, idx) => th.getAttribute('data-col-key') || `c${idx}`);

      const setWidthByIndex = (idx, px) => {
        const w = Math.max(60, px);
        const rows = table.querySelectorAll('tr');
        rows.forEach((tr) => {
          const cell = tr.children[idx];
          if (!cell) return;
          cell.style.width = `${w}px`;
          cell.style.minWidth = `${w}px`;
          cell.style.maxWidth = `${w}px`;
        });
      };

      [...theadRow.querySelectorAll('th[data-col-key]')].forEach((th, idx) => {
        const key = th.getAttribute('data-col-key') || `c${idx}`;
        if (!key || LOCKED.has(key)) return;

        th.classList.add('pt-resizable');
        if (th.querySelector('.pt-col-resizer')) return;

        const handle = document.createElement('span');
        handle.className = 'pt-col-resizer';
        handle.setAttribute('role', 'separator');
        handle.setAttribute('aria-orientation', 'vertical');
        handle.setAttribute('aria-label', 'Cambiar ancho de columna');
        handle.dataset.colKey = key;

        handle.addEventListener('pointerdown', (e) => {
          e.preventDefault();
          e.stopPropagation();

          const keys = getKeys();
          const colIndex = keys.indexOf(key);
          if (colIndex < 0) return;

          const startX = e.clientX;
          const startWidth = th.getBoundingClientRect().width;
          const pointerId = e.pointerId;
          try { handle.setPointerCapture(pointerId); } catch (_) {}

          const onMove = (ev) => {
            const dx = ev.clientX - startX;
            const next = Math.round(startWidth + dx);
            setWidthByIndex(colIndex, next);
          };

          const onEnd = () => {
            handle.removeEventListener('pointermove', onMove);
            handle.removeEventListener('pointerup', onEnd);
            handle.removeEventListener('pointercancel', onEnd);
            const finalWidth = Math.round(th.getBoundingClientRect().width);
            saveWidth(key, Math.max(60, finalWidth));
          };

          handle.addEventListener('pointermove', onMove);
          handle.addEventListener('pointerup', onEnd);
          handle.addEventListener('pointercancel', onEnd);
        });

        th.appendChild(handle);
      });
    }

    // Cargar items (Tareas + Proyectos + Procesos)
    async function cargarAgenda(filtros = {}) {
      try {
        const tipos = filtros.tipo ? [filtros.tipo] : ['Tarea', 'Proyecto', 'Proceso'];
        const useDefault = !!filtros.__agenda_default;
        const agendaDate = filtros.__agenda_date || getLocalTodayYmd();
        const requests = tipos.map((tipo) => {
          const params = new URLSearchParams();
          params.append('tipo', tipo);
          if (filtros.unit_id) params.append('unit_id', filtros.unit_id);
          if (filtros.business_id) params.append('business_id', filtros.business_id);

          if (Array.isArray(filtros.status) && filtros.status.length) {
            filtros.status.forEach((s) => params.append('status[]', s));
          } else if (typeof filtros.status === 'string' && filtros.status.trim()) {
            params.append('status', filtros.status.trim());
          }

          if (Array.isArray(filtros.assignee) && filtros.assignee.length) {
            filtros.assignee.forEach((u) => params.append('assignee[]', u));
          } else if (typeof filtros.assignee === 'string' && filtros.assignee.trim()) {
            params.append('assignee', filtros.assignee.trim());
          }

          if (!useDefault) {
            if (filtros.date_from) params.append('date_from', filtros.date_from);
            if (filtros.date_to) params.append('date_to', filtros.date_to);
            if (filtros.agenda_pending_overdue) params.append('agenda_pending_overdue', String(filtros.agenda_pending_overdue));
            if (filtros.agenda_today) params.append('agenda_today', String(filtros.agenda_today));
          }
          if (useDefault) {
            params.append('agenda_default', '1');
            params.append('agenda_date', agendaDate);
          }
          return fetch(`/modules/processes_tasks/api/list.php?${params.toString()}`, { credentials: 'same-origin' });
        });

        const responses = await Promise.all(requests);
        const jsons = await Promise.all(responses.map((r) => r.json().catch(() => ({}))));

        let items = [];
        jsons.forEach((data) => {
          if (data && data.ok && Array.isArray(data.items)) {
            items = items.concat(data.items);
          }
        });

        // Orden base (consistente con backend) si el usuario no eligió sort
        if (!sortState.key) {
          items.sort((a, b) => {
            const ra = getStatusRank(a.status);
            const rb = getStatusRank(b.status);
            if (ra !== rb) return ra - rb;
            const da = parseDateToTs(a.fecha_entrega);
            const db = parseDateToTs(b.fecha_entrega);
            if (da !== null && db !== null) return da - db;
            if (da !== null) return -1;
            if (db !== null) return 1;
            return 0;
          });
        }

        currentAgendaItems = items;
        const finalItems = applySort(currentAgendaItems);
        renderizarTabla(finalItems, { defaultMode: useDefault });
        updateSortIndicators('ptAgendaTable');

      } catch (error) {
        console.error('Error al cargar datos:', error);
        mostrarError('Error al cargar los datos');
      }
    }

    // Renderizar tabla
    function renderizarTabla(tasks, opts = {}) {
      const defaultMode = !!opts.defaultMode;
      const tbody = document.getElementById('ptAgendaTbody');
      const totalColumns = document.querySelectorAll('#ptAgendaTable thead th').length || 1;
      if (!tbody) return;

      // Estado: cargando (cuando no llega un array)
      if (!Array.isArray(tasks)) {
        tbody.innerHTML = `
          <tr>
            <td colspan="${totalColumns}" class="text-center py-4">
              <div class="d-flex justify-content-center align-items-center gap-2">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <span>Cargando...</span>
              </div>
            </td>
          </tr>
        `;
        applyStoredColumnOrder('ptAgendaTable');
        applyStoredColumnVisibility('ptAgendaTable');
        applyStoredColumnWidths('ptAgendaTable');
        updateSortIndicators('ptAgendaTable');
        initResizableColumns('ptAgendaTable');
        return;
      }

      // Estado: sin resultados
      if (tasks.length === 0) {
        const msg = defaultMode
          ? 'Sin resultados para hoy + pendientes.'
          : 'Sin resultados para los filtros aplicados.';
        tbody.innerHTML = `
          <tr>
            <td colspan="${totalColumns}" class="text-center py-5">
              <p class="text-muted mb-0">${escapeHtml(msg)}</p>
            </td>
          </tr>
        `;
        applyStoredColumnOrder('ptAgendaTable');
        applyStoredColumnVisibility('ptAgendaTable');
        applyStoredColumnWidths('ptAgendaTable');
        updateSortIndicators('ptAgendaTable');
        initResizableColumns('ptAgendaTable');
        return;
      }

      tbody.innerHTML = tasks.map((task) => {
        const tipo = String(task.tipo || 'Tarea').trim() || 'Tarea';
        const id = task.id;
        const extraAction = (() => {
          if (tipo === 'Tarea') {
            return `<button type="button" class="btn btn-sm btn-icon ix-action-btn" data-pt-action="complete" data-pt-id="${escapeHtml(id)}" data-pt-tipo="${escapeHtml(tipo)}" title="Completar" aria-label="Completar"><i class="bi bi-check2-circle"></i></button>`;
          }
          if (tipo === 'Proceso') {
            return `<button type="button" class="btn btn-sm btn-icon ix-action-btn" data-pt-action="execute" data-pt-id="${escapeHtml(id)}" data-pt-tipo="${escapeHtml(tipo)}" title="Ejecutar" aria-label="Ejecutar"><i class="bi bi-play-fill"></i></button>`;
          }
          return '';
        })();

        const title = task.titulo || task.title || '-';
        const description = task.descripcion || task.description || '-';
        const priority = task.nivel || '-';
        const project = task.proyecto_asignado || '-';
        const weight = (task.ponderacion === null || task.ponderacion === undefined || task.ponderacion === '') ? '-' : task.ponderacion;
        const creatorId = (task.usuario_creador === null || task.usuario_creador === undefined || task.usuario_creador === '') ? '-' : task.usuario_creador;
        const assigneeId = (task.usuario_delegado === null || task.usuario_delegado === undefined || task.usuario_delegado === '') ? '-' : task.usuario_delegado;
        const creatorLabel = task.creador_nombre || task.creador_email || '-';
        const assigneeLabel = displayPersonLabel(task.delegado_nombre, task.delegado_email);
        const filesCount = getFilesCount(task.archivos);

        return `
          <tr data-pt-id="${escapeHtml(id)}" data-pt-tipo="${escapeHtml(tipo)}">
            <td data-col-key="sel" class="text-center"><input type="checkbox" class="pt-agenda-check" value="${escapeHtml(task.id)}" aria-label="Seleccionar"></td>
            <td data-col-key="id" class="text-center">${escapeHtml(task.id ?? '-')}</td>
            <td data-col-key="company_id" class="text-center">${escapeHtml(task.company_id ?? '-')}</td>
            <td data-col-key="type" class="text-center">${getTypePill(tipo)}</td>
            <td data-col-key="folio">${escapeHtml(task.folio || '-')}</td>
            <td data-col-key="title" class="fw-semibold">${escapeHtml(title)}</td>
            <td data-col-key="description">${escapeHtml(description)}</td>
            <td data-col-key="priority" class="text-center pt-inline-editable" data-pt-inline="1" title="Doble clic para editar">${escapeHtml(priority)}</td>
            <td data-col-key="project">${escapeHtml(project)}</td>
            <td data-col-key="weight" class="text-center pt-inline-editable" data-pt-inline="1" title="Doble clic para editar">${escapeHtml(String(weight))}</td>
            <td data-col-key="unit_id" class="text-center">${escapeHtml(task.unit_id ?? '-')}</td>
            <td data-col-key="unit" class="text-center">${escapeHtml(task.unit_nombre || '-')}</td>
            <td data-col-key="business_id" class="text-center">${escapeHtml(task.business_id ?? '-')}</td>
            <td data-col-key="biz" class="text-center">${escapeHtml(task.business_nombre || '-')}</td>
            <td data-col-key="start">${formatDate(task.fecha_inicio)}</td>
            <td data-col-key="due">${formatDate(task.fecha_entrega)}</td>
            <td data-col-key="end" class="pt-inline-editable" data-pt-inline="1" title="Doble clic para editar">${formatDate(task.fecha_fin)}</td>
            <td data-col-key="state" class="text-center">${getStatusPill(task.status)}</td>
            <td data-col-key="creator_id" class="text-center">${escapeHtml(String(creatorId))}</td>
            <td data-col-key="creator" class="text-center">${escapeHtml(creatorLabel)}</td>
            <td data-col-key="assignee_id" class="text-center">${escapeHtml(String(assigneeId))}</td>
            <td data-col-key="assignee" class="text-center pt-inline-editable" data-pt-inline="1" title="Doble clic para editar">${escapeHtml(assigneeLabel)}</td>
            <td data-col-key="files" class="text-center">${escapeHtml(String(filesCount || 0))}</td>
            <td data-col-key="created_at">${formatDateTime(task.created_at)}</td>
            <td data-col-key="updated_at">${formatDateTime(task.updated_at)}</td>
            <td data-col-key="actions" class="text-end">
              <div class="ix-row-actions">
                <button type="button" class="btn btn-sm btn-icon ix-action-btn ix-action-btn--view" data-pt-action="view" data-pt-id="${escapeHtml(id)}" data-pt-tipo="${escapeHtml(tipo)}" title="Ver" aria-label="Ver"><i class="bi bi-eye"></i></button>
                <button type="button" class="btn btn-sm btn-icon ix-action-btn ix-action-btn--edit" data-pt-action="edit" data-pt-id="${escapeHtml(id)}" data-pt-tipo="${escapeHtml(tipo)}" title="Editar" aria-label="Editar"><i class="bi bi-pencil"></i></button>
                ${extraAction}
                <button type="button" class="btn btn-sm btn-icon ix-action-btn ix-action-btn--dup" data-pt-action="duplicate" data-pt-id="${escapeHtml(id)}" data-pt-tipo="${escapeHtml(tipo)}" title="Duplicar" aria-label="Duplicar"><i class="bi bi-copy"></i></button>
                <button type="button" class="btn btn-sm btn-icon ix-action-btn ix-action-btn--danger" data-pt-action="delete" data-pt-id="${escapeHtml(id)}" data-pt-tipo="${escapeHtml(tipo)}" title="Eliminar" aria-label="Eliminar"><i class="bi bi-trash"></i></button>
              </div>
            </td>
          </tr>
        `;
      }).join('');

      applyStoredColumnOrder('ptAgendaTable');
      applyStoredColumnVisibility('ptAgendaTable');
      applyStoredColumnWidths('ptAgendaTable');
      updateSortIndicators('ptAgendaTable');
      initResizableColumns('ptAgendaTable');
    }

    // Inline edit (doble clic) para columnas específicas
    // Responsable, Prioridad, Ponderación, Finaliza
    initAgendaInlineEditing();

    // Mostrar error
    function mostrarError(mensaje) {
      const tbody = document.getElementById('ptAgendaTbody');
      const totalColumns = document.querySelectorAll('#ptAgendaTable thead th').length || 1;
      tbody.innerHTML = `
            <tr>
          <td colspan="${totalColumns}" class="text-center py-5">
                    <i class="bi bi-exclamation-triangle fs-1 text-danger d-block mb-3"></i>
                    <p class="text-danger mb-0">${escapeHtml(mensaje)}</p>
                </td>
            </tr>
        `;
    }

    // Nota: Las acciones de Agenda se ejecutan inline (sin cambiar de pestaña).

    function initColumnsModal(tableId) {
      const modal = document.getElementById(`modalColumns_${tableId}`);
      const checklist = document.getElementById(`columnsChecklist_${tableId}`);
      const btnSave = document.getElementById(`saveColumns_${tableId}`);
      const table = document.getElementById(tableId);
      if (!modal || !checklist || !btnSave || !table) return;

      const STORAGE_HIDDEN_KEY = storageKey(`${tableId}.columns.hidden.v1`);
      const LEGACY_HIDDEN_KEY = `pt.${tableId}.columns.v1`;
      const STORAGE_ORDER_KEY = storageKey(`${tableId}.columns.order.v1`);
      const hidden = new Set(JSON.parse(storageGet(STORAGE_HIDDEN_KEY, LEGACY_HIDDEN_KEY) || '[]'));

      let preferredOrder = [];
      try {
        const rawOrder = storageGet(STORAGE_ORDER_KEY);
        preferredOrder = rawOrder ? JSON.parse(rawOrder) : [];
      } catch {
        preferredOrder = [];
      }
      preferredOrder = Array.isArray(preferredOrder) ? preferredOrder.filter(k => typeof k === 'string') : [];

      const ths = [...table.querySelectorAll('thead th')];
      const cols = ths.map((th, idx) => ({
        key: th.getAttribute('data-col-key') || `c${idx}`,
        label: (th.getAttribute('data-col-label') || th.textContent).trim() || `Col ${idx + 1}`,
        index: idx
      }));

      const LOCKED = new Set(['sel', 'actions']);
      const reorderableKeys = cols.map(c => c.key).filter(k => k && !LOCKED.has(k));
      const normalizedOrder = preferredOrder
        .filter(k => reorderableKeys.includes(k))
        .concat(reorderableKeys.filter(k => !preferredOrder.includes(k)));

      function renderChecklist(orderKeys) {
        checklist.innerHTML = orderKeys.map((key) => {
          const c = cols.find(x => x.key === key);
          if (!c || LOCKED.has(c.key)) return '';
          const checked = hidden.has(c.key) ? '' : 'checked';
          const inputId = `col_${tableId}_${c.key}`;
          return `<div class="col-12">
            <div class="d-flex align-items-center justify-content-between border rounded-3 px-2 py-2">
              <div class="form-check m-0">
                <input class="form-check-input" type="checkbox" id="${inputId}" data-col-key="${c.key}" ${checked}>
                <label class="form-check-label" for="${inputId}">${escapeHtml(c.label)}</label>
              </div>
              <div class="btn-group btn-group-sm" role="group" aria-label="Orden de columna">
                <button type="button" class="btn btn-outline-secondary" data-move="up" data-col-key="${c.key}" aria-label="Subir">
                  <i class="bi bi-arrow-up" aria-hidden="true"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" data-move="down" data-col-key="${c.key}" aria-label="Bajar">
                  <i class="bi bi-arrow-down" aria-hidden="true"></i>
                </button>
              </div>
            </div>
          </div>`;
        }).join('');
      }

      let liveOrder = normalizedOrder.slice();
      renderChecklist(liveOrder);

      checklist.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-move][data-col-key]');
        if (!btn) return;
        e.preventDefault();
        const move = btn.getAttribute('data-move');
        const key = btn.getAttribute('data-col-key');
        const idx = liveOrder.indexOf(key);
        if (idx < 0) return;
        if (move === 'up' && idx > 0) {
          const tmp = liveOrder[idx - 1];
          liveOrder[idx - 1] = liveOrder[idx];
          liveOrder[idx] = tmp;
          renderChecklist(liveOrder);
        }
        if (move === 'down' && idx < liveOrder.length - 1) {
          const tmp = liveOrder[idx + 1];
          liveOrder[idx + 1] = liveOrder[idx];
          liveOrder[idx] = tmp;
          renderChecklist(liveOrder);
        }
      });

      function applyVisibility() {
        const currentHidden = new Set(JSON.parse(storageGet(STORAGE_HIDDEN_KEY, LEGACY_HIDDEN_KEY) || '[]'));
        const idxToKey = cols.map(c => c.key);
        [...table.querySelectorAll('tr')].forEach(tr => {
          [...tr.children].forEach((cell, i) => {
            const key = idxToKey[i];
            if (!key) return;
            if (LOCKED.has(key)) { cell.style.display = ''; return; }
            cell.style.display = currentHidden.has(key) ? 'none' : '';
          });
        });
      }

      applyVisibility();

      btnSave.addEventListener('click', (e) => {
        e.preventDefault();
        const inputs = checklist.querySelectorAll('input[type="checkbox"][data-col-key]');
        const newHidden = [];
        inputs.forEach(inp => { if (!inp.checked) newHidden.push(inp.getAttribute('data-col-key')); });
        storageSet(STORAGE_HIDDEN_KEY, JSON.stringify(newHidden));
        storageSet(STORAGE_ORDER_KEY, JSON.stringify(liveOrder));
        applyStoredColumnOrder(tableId);
        applyStoredColumnVisibility(tableId);
        applyStoredColumnWidths(tableId);
        updateSortIndicators(tableId);
        initResizableColumns(tableId);
        try { bootstrap.Modal.getInstance(modal)?.hide(); } catch (_) {}
      });
    }

    async function init() {
      initColumnsModal('ptAgendaTable');
      wireAgendaMultiDropdownEvents();
      updateAgendaAssigneeButtonLabel();
      updateAgendaStatusButtonLabel();
      await cargarCatalogosFiltros();

      // Submit del modal de edición
      const editForm = document.getElementById('ptAgendaEditForm');
      if (editForm) {
        editForm.addEventListener('submit', async (e) => {
          e.preventDefault();
          const saveBtn = document.getElementById('ptAgendaEditSaveBtn');
          const originalText = saveBtn ? saveBtn.innerHTML : '';
          try {
            if (saveBtn) {
              saveBtn.disabled = true;
              saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
            }
            const fd = new FormData(editForm);
            await apiPost('/modules/processes_tasks/api/update.php', fd);
            try { bootstrap.Modal.getInstance(document.getElementById('modalAgendaEdit'))?.hide(); } catch (_) {}
            await reloadAgenda();
          } catch (err) {
            alert('Error al guardar: ' + (err?.message || 'error'));
          } finally {
            if (saveBtn) {
              saveBtn.disabled = false;
              saveBtn.innerHTML = originalText || 'Guardar';
            }
          }
        });
      }

      // Orden/visibilidad/sort inicial
      applyStoredColumnOrder('ptAgendaTable');
      applyStoredColumnVisibility('ptAgendaTable');
      applyStoredColumnWidths('ptAgendaTable');
      updateSortIndicators('ptAgendaTable');
      initResizableColumns('ptAgendaTable');

      // Acciones inline (delegación)
      const tbody = document.getElementById('ptAgendaTbody');
      if (tbody) {
        tbody.addEventListener('click', async (e) => {
          const btn = e.target.closest('button[data-pt-action][data-pt-id]');
          if (!btn) return;
          if (e.target && e.target.closest('input[type="checkbox"]')) return;

          const action = btn.getAttribute('data-pt-action') || '';
          const id = Number.parseInt(btn.getAttribute('data-pt-id') || '0', 10);
          if (!Number.isFinite(id) || id <= 0) return;

          e.preventDefault();

          switch (action) {
            case 'view': await agendaView(id); break;
            case 'edit': await agendaEdit(id); break;
            case 'duplicate': await agendaDuplicate(id); break;
            case 'delete': await agendaDelete(id); break;
            case 'complete': await agendaComplete(id); break;
            case 'execute': await agendaExecute(id); break;
            default: break;
          }
        });
      }

      // Click en headers: ordenar asc/desc
      const table = document.getElementById('ptAgendaTable');
      if (table) {
        const onSort = (th) => {
          const key = th.getAttribute('data-col-key') || '';
          if (!key || key === 'sel' || key === 'actions') return;
          if (sortState.key === key) {
            sortState.dir = (sortState.dir === 'asc') ? 'desc' : 'asc';
          } else {
            sortState.key = key;
            sortState.dir = 'asc';
          }
          persistSortState();
          const sorted = applySort(currentAgendaItems);
          renderizarTabla(sorted, { defaultMode: agendaDefaultMode });
        };

        table.querySelector('thead')?.addEventListener('click', (e) => {
          const th = e.target.closest('th[data-col-key]');
          if (!th) return;
          // Evitar click del checkbox select-all
          if (e.target && e.target.closest('input[type="checkbox"]')) return;
          onSort(th);
        });

        table.querySelector('thead')?.addEventListener('keydown', (e) => {
          if (e.key !== 'Enter' && e.key !== ' ') return;
          const th = e.target.closest('th[data-col-key]');
          if (!th) return;
          e.preventDefault();
          onSort(th);
        });
      }

      const filtersForm = document.getElementById('ptAgendaFilters');
      if (filtersForm) {
        filtersForm.addEventListener('submit', function(e) {
          e.preventDefault();
          const uiFilters = readAgendaFiltersFromUI();
          if (uiFilters.periodo === 'personalizada' && !uiFilters.date_from && !uiFilters.date_to) {
            alert('Selecciona al menos una fecha en el periodo personalizado.');
            return;
          }
          const requestFilters = buildAgendaRequestFilters(uiFilters);
          agendaDefaultMode = !!requestFilters.__agenda_default;
          currentAgendaFilters = requestFilters;
          cargarAgenda(requestFilters);
        });
      }

      document.getElementById('ptAgendaPeriodo')?.addEventListener('change', function() {
        syncAgendaCustomDateVisibility();
      });

      const selectAll = document.getElementById('ptAgendaSelectAll');
      if (selectAll) {
        selectAll.addEventListener('change', function() {
          document.querySelectorAll('#ptAgendaTbody .pt-agenda-check').forEach((cb) => {
            cb.checked = !!selectAll.checked;
          });
        });
      }

      syncAgendaCustomDateVisibility();
      currentAgendaFilters = buildAgendaRequestFilters(readAgendaFiltersFromUI());
      agendaDefaultMode = !!currentAgendaFilters.__agenda_default;
      await cargarAgenda(currentAgendaFilters);
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
    } else {
      init();
    }
  })();
</script>