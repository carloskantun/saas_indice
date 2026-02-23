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
  </div>
</div>

<!-- Filtros (igual RH) -->
<div class="hr-filter-card reveal">
  <form class="row g-3 align-items-end" id="ptAgendaFilters">
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
      <label class="form-label">Tipo</label>
      <select name="tipo" id="ptAgendaType" class="form-select">
        <option value="">Todos</option>
        <option value="Tarea">Tareas</option>
        <option value="Proyecto">Proyectos</option>
        <option value="Proceso">Procesos</option>
      </select>
    </div>
    <div class="col-12 col-md-6 col-xl-2">
      <label class="form-label">Estado</label>
      <select name="status" id="ptAgendaStatus" class="form-select">
        <option value="">Todos</option>
        <option value="En tiempo">En tiempo</option>
        <option value="En proceso">En proceso</option>
        <option value="Pausada">Pausada</option>
        <option value="Vencida">Vencida</option>
        <option value="Terminada">Terminada</option>
        <option value="Auditada">Auditada</option>
      </select>
    </div>
    <div class="col-12 col-md-6 col-xl-2 ms-xl-auto d-grid">
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-funnel"></i> Aplicar filtros
      </button>
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

<!-- Tabla (igual RH) -->
<div class="hr-table-wrapper reveal">
  <div class="ix-table-card">
    <div class="table-responsive">
      <table class="table table-hover align-middle ix-pt-table" id="ptAgendaTable">
        <thead>
          <tr>
            <th scope="col" data-col-key="sel" class="text-center"><input type="checkbox" id="ptAgendaSelectAll" aria-label="Seleccionar todos"></th>
            <th scope="col" data-col-key="type" class="text-center">Tipo</th>
            <th scope="col" data-col-key="folio">Folio</th>
            <th scope="col" data-col-key="title">Título</th>
            <th scope="col" data-col-key="unit" class="text-center">Unidad</th>
            <th scope="col" data-col-key="biz" class="text-center">Negocio</th>
            <th scope="col" data-col-key="start">Inicia</th>
            <th scope="col" data-col-key="due">Vence</th>
            <th scope="col" data-col-key="state" class="text-center">Estado</th>
            <th scope="col" data-col-key="assignee" class="text-center">Responsable</th>
            <th scope="col" data-col-key="actions" class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody id="ptAgendaTbody">
          <tr>
            <td colspan="11" class="text-center py-5">
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

    function readAgendaFiltersFromUI() {
      return {
        unit_id: document.getElementById('ptAgendaUnit')?.value || '',
        business_id: document.getElementById('ptAgendaBusiness')?.value || '',
        tipo: document.getElementById('ptAgendaType')?.value || '',
        status: document.getElementById('ptAgendaStatus')?.value || ''
      };
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

    async function cargarCatalogosFiltros() {
      const unitEl = document.getElementById('ptAgendaUnit');
      const businessEl = document.getElementById('ptAgendaBusiness');
      if (!unitEl || !businessEl) return;
      try {
        const response = await fetch('/modules/processes_tasks/api/form_data_simple.php', {
          method: 'GET',
          credentials: 'same-origin',
          headers: { 'Accept': 'application/json' }
        });
        if (!response.ok) return;
        const data = await response.json();
        if (!data.ok) return;
        populateSelectEl(unitEl, data.units, 'id', 'name');
        populateSelectEl(businessEl, data.businesses, 'id', 'name');
      } catch (e) {
        console.warn('⚠️ No se pudieron cargar catálogos de filtros:', e);
      }
    }

    // Cargar items (Tareas + Proyectos + Procesos)
    async function cargarAgenda(filtros = {}) {
      try {
        const tipos = filtros.tipo ? [filtros.tipo] : ['Tarea', 'Proyecto', 'Proceso'];
        const requests = tipos.map((tipo) => {
          const params = new URLSearchParams();
          params.append('tipo', tipo);
          if (filtros.unit_id) params.append('unit_id', filtros.unit_id);
          if (filtros.business_id) params.append('business_id', filtros.business_id);
          if (filtros.status) params.append('status', filtros.status);
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

        // Ordenar por fecha de entrega
        items.sort((a, b) => {
          // Prioridad: Vencidas primero, luego por fecha
          const statusOrder = {
            'Vencida': 0,
            'En proceso': 1,
            'En tiempo': 2,
            'Terminada': 3,
            'Auditada': 4,
            'Pausada': 5
          };
          const statusA = statusOrder[a.status] !== undefined ? statusOrder[a.status] : 99;
          const statusB = statusOrder[b.status] !== undefined ? statusOrder[b.status] : 99;

          if (statusA !== statusB) return statusA - statusB;

          // Si tienen el mismo status, ordenar por fecha de entrega
          if (a.fecha_entrega && b.fecha_entrega) {
            return new Date(a.fecha_entrega) - new Date(b.fecha_entrega);
          }

          return 0;
        });

        renderizarTabla(items);

      } catch (error) {
        console.error('Error al cargar datos:', error);
        mostrarError('Error al cargar los datos');
      }
    }

    // Renderizar tabla
    function renderizarTabla(tasks) {
      const tbody = document.getElementById('ptAgendaTbody');

      if (!tasks || tasks.length === 0) {
        tbody.innerHTML = `
                <tr>
                    <td colspan="11" class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                        <p class="text-muted mb-0">No hay datos para mostrar en la agenda</p>
                    </td>
                </tr>
            `;
        return;
      }

      tbody.innerHTML = tasks.map(task => {
        return `
            <tr>
                <td class="text-center"><input type="checkbox" class="pt-agenda-check" value="${escapeHtml(task.id)}" aria-label="Seleccionar"></td>
                <td class="text-center">${getTypePill(task.tipo)}</td>
                <td>${escapeHtml(task.folio || '-')}</td>
                <td class="fw-semibold">${escapeHtml(task.titulo || '-')}</td>
                <td class="text-center">${escapeHtml(task.unit_nombre || '-')}</td>
                <td class="text-center">${escapeHtml(task.business_nombre || '-')}</td>
                <td>${formatDate(task.fecha_inicio)}</td>
                <td>${formatDate(task.fecha_entrega)}</td>
                <td class="text-center">${getStatusPill(task.status)}</td>
                <td class="text-center">${escapeHtml(task.delegado_nombre || task.delegado_email || '-')}</td>
                <td class="text-end">
                  <div class="ix-row-actions">
                    <button type="button" class="btn btn-sm btn-icon ix-action-btn ix-action-btn--view" onclick="verDetalle(${task.id}, '${escapeHtml(task.tipo || 'Tarea')}')" title="Ver" aria-label="Ver"><i class="bi bi-eye"></i></button>
                  </div>
                </td>
            </tr>
            `;
      }).join('');
    }

    // Mostrar error
    function mostrarError(mensaje) {
      const tbody = document.getElementById('ptAgendaTbody');
      tbody.innerHTML = `
            <tr>
                <td colspan="11" class="text-center py-5">
                    <i class="bi bi-exclamation-triangle fs-1 text-danger d-block mb-3"></i>
                    <p class="text-danger mb-0">${escapeHtml(mensaje)}</p>
                </td>
            </tr>
        `;
    }

    window.verDetalle = function(id, tipo) {
      alert('Ver detalle #' + id + ' (' + (tipo || 'Tarea') + ')\n\nFuncionalidad en desarrollo');
    };

    function initColumnsModal(tableId) {
      const modal = document.getElementById(`modalColumns_${tableId}`);
      const checklist = document.getElementById(`columnsChecklist_${tableId}`);
      const btnSave = document.getElementById(`saveColumns_${tableId}`);
      const table = document.getElementById(tableId);
      if (!modal || !checklist || !btnSave || !table) return;

      const STORAGE_KEY = `pt.${tableId}.columns.v1`;
      const hidden = new Set(JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'));

      const ths = [...table.querySelectorAll('thead th')];
      const cols = ths.map((th, idx) => ({
        key: th.getAttribute('data-col-key') || `c${idx}`,
        label: th.textContent.trim() || `Col ${idx + 1}`,
        index: idx
      }));

      const LOCKED = new Set(['sel', 'actions']);
      checklist.innerHTML = cols.map(c => {
        if (!c.key) return '';
        if (LOCKED.has(c.key)) return '';
        const checked = hidden.has(c.key) ? '' : 'checked';
        const inputId = `col_${tableId}_${c.key}`;
        return `<div class="col-6"><div class="form-check">
          <input class="form-check-input" type="checkbox" id="${inputId}" data-col-key="${c.key}" ${checked}>
          <label class="form-check-label" for="${inputId}">${escapeHtml(c.label)}</label>
        </div></div>`;
      }).join('');

      function applyVisibility() {
        const currentHidden = new Set(JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'));
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
        localStorage.setItem(STORAGE_KEY, JSON.stringify(newHidden));
        applyVisibility();
        try { bootstrap.Modal.getInstance(modal)?.hide(); } catch (_) {}
      });
    }

    async function init() {
      initColumnsModal('ptAgendaTable');
      await cargarCatalogosFiltros();

      const filtersForm = document.getElementById('ptAgendaFilters');
      if (filtersForm) {
        filtersForm.addEventListener('submit', function(e) {
          e.preventDefault();
          currentAgendaFilters = readAgendaFiltersFromUI();
          cargarAgenda(currentAgendaFilters);
        });
      }

      const selectAll = document.getElementById('ptAgendaSelectAll');
      if (selectAll) {
        selectAll.addEventListener('change', function() {
          document.querySelectorAll('#ptAgendaTbody .pt-agenda-check').forEach((cb) => {
            cb.checked = !!selectAll.checked;
          });
        });
      }

      currentAgendaFilters = readAgendaFiltersFromUI();
      await cargarAgenda(currentAgendaFilters);
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
    } else {
      init();
    }
  })();
</script>