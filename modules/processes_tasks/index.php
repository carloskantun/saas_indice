<?php

require __DIR__ . '/../../bootstrap.php';
// Helper para establecer status sin romper si ya hay salida
if (!function_exists('safe_http_status')) {
    function safe_http_status(int $code): void {
        if (!headers_sent()) { http_response_code($code); }
    }
}
if (!defined('APP_BOOTSTRAPPED')) { safe_http_status(403); exit; }

// Prevenir caché del navegador para desarrollo
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require __DIR__ . '/../../core/auth.php';
require __DIR__ . '/../../core/permissions.php';
require __DIR__ . '/../../core/module_loader.php';
require_once __DIR__ . '/../_shared/module_layout.php';

// Cargar helpers básicos necesarios para las vistas
if (file_exists(__DIR__ . '/includes/csrf_helper.php')) {
    require_once __DIR__ . '/includes/csrf_helper.php';
}
if (file_exists(__DIR__ . '/includes/validation_helper.php')) {
    require_once __DIR__ . '/includes/validation_helper.php';
}

// Mostrar errores en desarrollo (seguro en front)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

requireLogin();
$user = auth();
$ucId = currentUserCompany();
if (!$ucId) { header('Location: /login.php'); exit; }

$userCompany = is_array($user) ? getUserCompany((int)($user['id'] ?? 0)) : null;
$companyRole = (string)($userCompany['role'] ?? '');
$companyId = (int)($userCompany['company_id'] ?? 0);
if (!$companyId) {
    // Fallback conservador (compat): algunos installs usan ucId como company_id
    $companyId = (int)$ucId;
}

// Mapa de módulos accesibles (para renderizar barra de Favoritos entre módulos)
$accessible = function_exists('listAccessibleModules') ? listAccessibleModules($companyId, $companyRole) : [];
$modulesMap = [];
foreach ($accessible as $slug => $meta) {
    $slug = (string)$slug;
    $moduleIndexPath = __DIR__ . "/../{$slug}/index.php";
    $url = is_file($moduleIndexPath)
        ? "/modules/{$slug}/index.php"
        : "/modules/modulo-en-construccion.php?slug=" . rawurlencode($slug);

    $modulesMap[$slug] = [
        'slug' => $slug,
        'name' => (string)($meta['name'] ?? $slug),
        'icon' => (string)($meta['icon'] ?? 'bi-grid'),
        'url'  => $url,
    ];
}

if (!hasPermission($ucId, 'processes_tasks', 'view')) {
    error_log('[processes_tasks] Sin permiso view para ucId=' . $ucId);
    echo "<div class='alert alert-warning m-3'>Sin permiso 'view' en processes_tasks</div>";
    safe_http_status(403);
    exit;
}

// Generar token CSRF si no existe
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$tabs = [
    'dashboard'       => '📅 Agenda',
    'tasks'           => '✅ Tareas',
    'projects'        => '🚀 Proyectos',
    'kpis'            => '📈 KPIs',
    'organigrama'     => '🧠 Organigrama',
    'processes'       => '⚙️ Procesos'
];
$rawTab = $_GET['tab'] ?? '';
// Predeterminar a "dashboard" si no es válido; si quieres recordar la última pestaña, puedes leer localStorage via JS
$tab = array_key_exists($rawTab, $tabs) ? $rawTab : 'dashboard';

?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Processes &amp; Tasks – Índice ERP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo h($_SESSION['csrf_token']); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/indice-theme.css">
    <link rel="stylesheet" href="/assets/css/reveal.css">
    <link rel="stylesheet" href="/modules/processes_tasks/assets/processes_tasks_redesign.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/modules/processes_tasks/assets/pt_rh_skin_yellow.css?v=<?php echo (int)@filemtime(__DIR__ . '/assets/pt_rh_skin_yellow.css'); ?>">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    
    <style>
        .tab-content { min-height: 400px; }
        .alert { margin: 1rem; padding: 1rem; }
    </style>
</head>
<body class="pt-module pt-cc-skin" data-user-id="<?php echo (int)($user['id'] ?? 0); ?>" data-company-id="<?php echo (int)$companyId; ?>" data-module-slug="processes_tasks" data-pt-tab="<?php echo h($tab); ?>">
    <div id="module-processes-tasks" data-module="processes-tasks">
    <div class="container-xxl py-4" id="pt-container">
            <!-- HEADER INSTITUCIONAL (estilo RH) -->
            <div class="module-header reveal mb-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="module-icon" aria-hidden="true"><i class="bi bi-gear-wide-connected"></i></div>
                    <div>
                        <h1 class="module-title mb-1">Procesos y Tareas</h1>
                        <p class="module-subtitle mb-0">Agenda, tareas, proyectos, KPIs y procesos recurrentes.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-ghost" type="button" id="btn-aprendiz" aria-pressed="false" aria-label="Activar modo aprendiz" title="Modo aprendiz" data-feature="aprendiz" data-feature-ready="1">
                        <i class="bi bi-mortarboard-fill" aria-hidden="true"></i>
                    </button>

                    <button class="btn btn-ghost" type="button" id="langBtn" aria-label="Seleccionar idioma" title="Idioma (próximamente)" data-feature="language" data-feature-ready="0">
                        <span class="me-1" id="langFlag" aria-hidden="true">🇲🇽</span>
                        <i class="bi bi-translate" aria-hidden="true"></i>
                    </button>

                    <button class="btn btn-ghost position-relative" type="button" id="btn-notifications" aria-label="Ver notificaciones" title="Notificaciones (próximamente)" data-feature="notifications" data-feature-ready="0">
                        <i class="bi bi-bell" aria-hidden="true"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge d-none" id="notificationCount">0</span>
                    </button>

                    <a href="/index.php" class="btn btn-ghost" aria-label="Volver al dashboard" title="Volver al dashboard"><i class="bi bi-arrow-left-circle" aria-hidden="true"></i></a>
                </div>
            </div>

            <!-- Modo aprendiz (mismo layout que RH) -->
            <link href="/modules/human_resources/assets/aprendiz.css?v=<?php echo (int)@filemtime(__DIR__ . '/../human_resources/assets/aprendiz.css'); ?>" rel="stylesheet">
            <div class="mb-3" id="modoAprendizWrap">
                <div class="card border-primary reveal" id="modoAprendizPanel" role="region" aria-label="Modo aprendiz" style="display:none;">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-gear-fill text-primary fs-3 me-3" aria-hidden="true"></i>
                                <div>
                                    <h5 class="card-title mb-1" id="ptAprendizTitle">🧭 Bienvenido al módulo Procesos y Tareas</h5>
                                    <p class="card-text mb-0 text-muted" id="ptAprendizSubtitle">Aquí conviertes la operación diaria en procesos y tareas medibles.</p>
                                </div>
                            </div>

                            <button class="btn btn-outline-primary btn-sm" id="hideAprendizBtn" type="button" aria-label="Ocultar modo aprendiz">
                                <i class="bi bi-eye-slash me-1" aria-hidden="true"></i>
                                <span>Ocultar</span>
                            </button>
                        </div>

                        <div class="mt-3 p-3 bg-light rounded" id="aprendizPanelBox">
                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                <div class="flex-grow-1">
                                    <div class="small text-muted" id="ptAprendizProgress" aria-live="polite">STEP 1 — Agenda</div>
                                    <div class="fw-semibold" id="ptAprendizMessage">📅 Agenda — Prioriza lo importante hoy</div>
                                    <div class="text-muted small" id="ptAprendizMicro">Ve pendientes, vencidas y completadas para decidir rápido.</div>
                                </div>

                                <div class="d-flex gap-2 flex-wrap">
                                    <button class="btn btn-outline-primary btn-sm" id="ptAprendizPrev" type="button" aria-label="Anterior">
                                        <i class="bi bi-chevron-left me-1" aria-hidden="true"></i>
                                        <span>Anterior</span>
                                    </button>
                                    <button class="btn btn-primary btn-sm" id="ptAprendizNext" type="button" aria-label="Siguiente">
                                        <span>Siguiente</span>
                                        <i class="bi bi-chevron-right ms-1" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mt-3" id="ptAprendizContext" aria-live="polite"></div>

                            <div class="mt-2">
                                <div class="small text-muted" id="ptAprendizStepLine">Paso 1 de 6</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Barra de Favoritos (módulos marcados en el dashboard) -->
            <div id="ptFavsBar" class="hr-favs-bar reveal" hidden>
                <div class="hr-favs-label"><i class="bi bi-star-fill me-1" aria-hidden="true"></i>Favoritos</div>
                <div id="ptFavsList" class="hr-favs-scroll" aria-label="Módulos favoritos"></div>
            </div>

            <!-- NAV DEL MÓDULO (estilo RH) -->
            <ul class="nav nav-tabs nav-tabs-min mb-3 module-tabs module-nav" role="tablist" aria-label="Pestañas de Procesos y Tareas">
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?php echo $tab === 'dashboard' ? 'active' : ''; ?>" href="?tab=dashboard" role="tab" aria-current="<?php echo $tab === 'dashboard' ? 'page' : 'false'; ?>">Agenda</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?php echo $tab === 'tasks' ? 'active' : ''; ?>" href="?tab=tasks" role="tab" aria-current="<?php echo $tab === 'tasks' ? 'page' : 'false'; ?>">Tareas</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?php echo $tab === 'projects' ? 'active' : ''; ?>" href="?tab=projects" role="tab" aria-current="<?php echo $tab === 'projects' ? 'page' : 'false'; ?>">Proyectos</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?php echo $tab === 'processes' ? 'active' : ''; ?>" href="?tab=processes" role="tab" aria-current="<?php echo $tab === 'processes' ? 'page' : 'false'; ?>">Procesos</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?php echo $tab === 'kpis' ? 'active' : ''; ?>" href="?tab=kpis" role="tab" aria-current="<?php echo $tab === 'kpis' ? 'page' : 'false'; ?>">KPIs</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?php echo $tab === 'organigrama' ? 'active' : ''; ?>" href="?tab=organigrama" role="tab" aria-current="<?php echo $tab === 'organigrama' ? 'page' : 'false'; ?>">Organigrama</a>
                </li>
            </ul>

        <div class="tab-content">
            <?php
            $viewFile = __DIR__ . "/views/{$tab}.php";
            if (function_exists('opcache_reset')) { @opcache_reset(); }

            if (!is_file($viewFile)) {
                safe_http_status(500);
                echo "<div class='alert alert-danger m-3'>Vista no encontrada: <code>".h($viewFile)."</code></div>";
            } elseif (!is_readable($viewFile)) {
                safe_http_status(500);
                echo "<div class='alert alert-danger m-3'>Vista sin permisos de lectura: <code>".h($viewFile)."</code></div>";
            } else {
                $sz = @filesize($viewFile);
                echo "<!-- DEBUG view=".h($tab)." size=".h((string)$sz)." path=".h($viewFile)." -->";
                try {
                    // Exponer $ucId a vistas que lo requieran (namespacing localStorage)
                    $GLOBALS['ucId'] = $ucId;
                    require $viewFile;
                    echo "<!-- DEBUG view-required-ok -->";
                } catch (Throwable $e) {
                    safe_http_status(500);
                    $errMsg  = $e->getMessage();
                    $errFile = method_exists($e,'getFile') ? $e->getFile() : $viewFile;
                    $errLine = method_exists($e,'getLine') ? (int)$e->getLine() : 0;
                    error_log('[processes_tasks:view-error] msg='.$errMsg.' file='.$errFile.' line='.$errLine);

                    // Construir snippet de contexto ~5 líneas alrededor
                    $snippetHtml = '';
                    $snippetJs   = '';
                    $srcFileForContext = is_file($viewFile) ? $viewFile : $errFile;
                    $srcLines = @file($srcFileForContext);
                    if ($srcLines && $errLine > 0) {
                        $total = count($srcLines);
                        $from = max(1, $errLine - 5);
                        $to   = min($total, $errLine + 5);
                        $snippetHtml .= "<pre class='m-0' style='white-space:pre-wrap'><code>";
                        for ($i=$from; $i<=$to; $i++) {
                            $ln = str_pad((string)$i, 4, ' ', STR_PAD_LEFT);
                            $line = rtrim((string)$srcLines[$i-1], "\r\n");
                            $isErr = ($i === $errLine);
                            $snippetHtml .= ($isErr? '&gt;&gt; ' : '    ').h($ln.' '.$line)."\n";
                            if ($isErr) { $snippetJs .= $ln.' '.$line."\n"; }
                        }
                        $snippetHtml .= "</code></pre>";
                    }

                    echo "<div class='alert alert-danger m-3'>".
                         "<div class='fw-semibold mb-2'>Error en la vista <code>".h($viewFile)."</code></div>".
                         "<div class='mb-2'><strong>Mensaje:</strong> <code>".h($errMsg)."</code></div>".
                         ($errLine? "<div class='mb-2'><strong>Línea:</strong> ".h((string)$errLine)."</div>" : '').
                         ($snippetHtml? "<details class='mt-2'><summary>Ver contexto</summary>".$snippetHtml."</details>" : '').
                         "</div>";

                    // Log para Chrome DevTools
                    $jsMsg = json_encode($errMsg);
                    $jsFile = json_encode($errFile);
                    $jsLine = json_encode($errLine);
                    $jsSnippet = json_encode($snippetJs);
                    echo "<script>console.error('[ProcessesTasks] View error', {message: $jsMsg, file: $jsFile, line: $jsLine, snippet: $jsSnippet});</script>";
                }
            }
            ?>
        </div>

        <!-- Contenedores locales para modals y toasts -->
        <div id="pt-modals-root"></div>
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
            <div id="pt-toast-root" class="toast-container"></div>
        </div>
    </div><!-- /container-xxl -->
        <script>
            window.IX_ACCESSIBLE_MODULES = <?php echo json_encode($modulesMap, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
        </script>

        <script>
        (function initPtFavoritesBar(){
            const bar = document.getElementById('ptFavsBar');
            const list = document.getElementById('ptFavsList');
            if (!bar || !list) return;

            const userId = document.body.getAttribute('data-user-id') || '0';
            const companyId = document.body.getAttribute('data-company-id') || '0';
            const currentSlug = document.body.getAttribute('data-module-slug') || '';
            const key = `indice:favorites:${companyId}:${userId}`;

            let favSlugs = [];
            try {
                const raw = localStorage.getItem(key);
                const parsed = raw ? JSON.parse(raw) : [];
                favSlugs = Array.isArray(parsed) ? parsed : [];
            } catch {
                favSlugs = [];
            }

            const map = (window.IX_ACCESSIBLE_MODULES && typeof window.IX_ACCESSIBLE_MODULES === 'object') ? window.IX_ACCESSIBLE_MODULES : {};
            const filtered = favSlugs
                .filter(slug => typeof slug === 'string' && map[slug])
                .filter((slug, idx, arr) => arr.indexOf(slug) === idx);

            if (!filtered.length) {
                bar.hidden = true;
                return;
            }

            const iconClass = (icon) => {
                if (!icon) return 'bi bi-grid';
                const s = String(icon);
                return s.includes(' ') ? s : `bi ${s}`;
            };

            list.innerHTML = '';
            for (const slug of filtered) {
                const meta = map[slug];
                const a = document.createElement('a');
                a.className = 'hr-favs-chip';
                a.href = meta.url || `/modules/${slug}/index.php`;
                a.setAttribute('data-slug', slug);
                a.setAttribute('title', meta.name || slug);

                if (slug === currentSlug) {
                    a.classList.add('is-active');
                    a.setAttribute('aria-current', 'page');
                }

                const icon = document.createElement('i');
                icon.className = iconClass(meta.icon);
                icon.setAttribute('aria-hidden', 'true');

                const name = document.createElement('span');
                name.className = 'hr-favs-name';
                name.textContent = meta.name || slug;

                a.appendChild(icon);
                a.appendChild(name);
                list.appendChild(a);
            }

            bar.hidden = false;
        })();
        </script>

        <script>
        (function initPtAprendiz(){
            'use strict';

            const $ = (sel, root=document) => root.querySelector(sel);
            const userId = document.body?.getAttribute('data-user-id') || '0';
            const currentTabRaw = (document.body?.getAttribute('data-pt-tab') || '').trim();
            const currentTab = currentTabRaw || 'dashboard';

            const panel = $('#modoAprendizPanel');
            const wrap = $('#modoAprendizWrap');
            const btnHeader = $('#btn-aprendiz');
            const btnHide = $('#hideAprendizBtn');
            const btnPrev = $('#ptAprendizPrev');
            const btnNext = $('#ptAprendizNext');

            const titleEl = $('#ptAprendizTitle');
            const subtitleEl = $('#ptAprendizSubtitle');
            const progressEl = $('#ptAprendizProgress');
            const messageEl = $('#ptAprendizMessage');
            const microEl = $('#ptAprendizMicro');
            const contextEl = $('#ptAprendizContext');
            const stepLineEl = $('#ptAprendizStepLine');

            if (!panel || !wrap) return;

            const hiddenKey = `idx:apprentice:processes_tasks:hidden:${userId}`;

            function storageGet(key){
                try { return window.localStorage ? window.localStorage.getItem(key) : null; } catch { return null; }
            }
            function storageSet(key, val){
                try { if (window.localStorage) window.localStorage.setItem(key, val); } catch { }
            }
            function storageDel(key){
                try { if (window.localStorage) window.localStorage.removeItem(key); } catch { }
            }

            const stepsBase = [
                {
                    tab: 'dashboard',
                    label: 'Agenda',
                    message: '📅 Agenda — Prioriza lo importante hoy',
                    micro: 'Ve pendientes, vencidas y completadas para decidir rápido.',
                    contextHtml: `
                        <div class="text-muted small">
                            <div class="fw-semibold text-body">¿Para qué sirve?</div>
                            <ul class="mb-2">
                                <li>Centraliza las tareas del día del equipo.</li>
                                <li>Detecta vencidas antes de que escalen.</li>
                                <li>Da visibilidad inmediata del estado operativo.</li>
                            </ul>
                            <div class="fw-semibold text-body">📌 Consejo Índice:</div>
                            <div>La agenda te ayuda a decidir qué atacar primero (impacto vs urgencia).</div>
                        </div>
                    `
                },
                {
                    tab: 'tasks',
                    label: 'Tareas',
                    message: '✅ Tareas — Ejecuta y da seguimiento',
                    micro: 'Crea, delega, audita y cierra tareas con orden.',
                    contextHtml: `
                        <div class="text-muted small">
                            <div class="fw-semibold text-body">Incluye:</div>
                            <ul class="mb-2">
                                <li>Asignación por responsable.</li>
                                <li>Prioridad y fechas.</li>
                                <li>Estatus y auditoría.</li>
                            </ul>
                            <div class="fw-semibold text-body">Impacto real</div>
                            <div>Sin tareas claras no hay operación controlada; sin control no hay mejora.</div>
                        </div>
                    `
                },
                {
                    tab: 'projects',
                    label: 'Proyectos',
                    message: '🚀 Proyectos — Agrupa trabajo por objetivo',
                    micro: 'Usa proyectos como contenedores para planear y medir.',
                    contextHtml: `
                        <div class="text-muted small">
                            <div class="fw-semibold text-body">¿Para qué sirve?</div>
                            <ul class="mb-2">
                                <li>Agrupar tareas relacionadas.</li>
                                <li>Dar seguimiento por responsable y fechas.</li>
                                <li>Medir avance por entregables.</li>
                            </ul>
                            <div class="fw-semibold text-body">📌 Consejo Índice:</div>
                            <div>Si todo es “tarea suelta”, no puedes ver prioridades por iniciativa.</div>
                        </div>
                    `
                },
                {
                    tab: 'processes',
                    label: 'Procesos',
                    message: '⚙️ Procesos — Automatiza lo repetible',
                    micro: 'Convierte rutinas en flujos recurrentes para no depender de memoria.',
                    contextHtml: `
                        <div class="text-muted small">
                            <div class="fw-semibold text-body">¿Para qué sirve?</div>
                            <ul class="mb-2">
                                <li>Definir procesos recurrentes (semanales/mensuales).</li>
                                <li>Generar tareas a partir de un flujo.</li>
                                <li>Reducir errores por omisión.</li>
                            </ul>
                            <div class="fw-semibold text-body">Impacto real</div>
                            <div>La operación madura se apoya en procesos, no en “recordatorios”.</div>
                        </div>
                    `
                },
                {
                    tab: 'kpis',
                    label: 'KPIs',
                    message: '📈 KPIs — Mide para mejorar',
                    micro: 'Convierte ejecución en métricas: cumplimiento, productividad y tendencias.',
                    contextHtml: `
                        <div class="text-muted small">
                            <div class="fw-semibold text-body">¿Para qué sirve?</div>
                            <ul class="mb-2">
                                <li>Medir cumplimiento por usuario.</li>
                                <li>Detectar cuellos de botella.</li>
                                <li>Tomar decisiones con datos.</li>
                            </ul>
                            <div>Lo que no se mide, no se mejora.</div>
                        </div>
                    `
                },
                {
                    tab: 'organigrama',
                    label: 'Organigrama',
                    message: '🧠 Organigrama — Alinea roles y responsabilidades',
                    micro: 'Define puestos y estructura para asignar mejor el trabajo.',
                    contextHtml: `
                        <div class="text-muted small">
                            <div class="fw-semibold text-body">¿Para qué sirve?</div>
                            <ul class="mb-2">
                                <li>Claridad de estructura y jerarquías.</li>
                                <li>Mejor delegación y seguimiento.</li>
                                <li>Menos duplicidad y conflictos.</li>
                            </ul>
                            <div class="fw-semibold text-body">📌 Consejo Índice:</div>
                            <div>Si el rol no está claro, la tarea también se vuelve ambigua.</div>
                        </div>
                    `
                }
            ];

            function tabExists(tab) {
                try {
                    const links = Array.from(document.querySelectorAll('.module-nav a.nav-link'));
                    return links.some(a => {
                        try {
                            const u = new URL(a.getAttribute('href') || '', window.location.href);
                            return u.searchParams.get('tab') === tab;
                        } catch {
                            return false;
                        }
                    });
                } catch {
                    return true;
                }
            }

            const steps = stepsBase
                .filter(s => tabExists(s.tab))
                .map((s, idx) => ({
                    ...s,
                    step: idx + 1,
                    stepLabel: `STEP ${idx + 1} — ${s.label}`
                }));

            function findStepByTab(tab){
                return steps.find(s => s.tab === tab) || null;
            }

            function stepIndex(tab){
                const s = findStepByTab(tab);
                return s ? s.step : 0;
            }

            function goToTab(tab){
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                window.location.href = url.toString();
            }

            function setVisible(visible){
                panel.style.display = visible ? '' : 'none';
                // El panel usa `.reveal` (opacity:0) y puede iniciar oculto (display:none);
                // forzamos `.in` al mostrar para evitar que quede invisible por IntersectionObserver.
                panel.classList.toggle('in', !!visible);
                if (btnHeader) btnHeader.setAttribute('aria-pressed', visible ? 'true' : 'false');
                if (visible) document.body.setAttribute('data-aprendiz', 'on');
                else document.body.removeAttribute('data-aprendiz');
            }

            function render(tab){
                const s = findStepByTab(tab);
                if (!s) {
                    setVisible(false);
                    return;
                }

                if (progressEl) progressEl.textContent = s.stepLabel;
                if (messageEl) messageEl.textContent = s.message;
                if (microEl) microEl.textContent = s.micro;
                if (contextEl) contextEl.innerHTML = s.contextHtml;
                if (stepLineEl) stepLineEl.textContent = `Paso ${s.step} de ${steps.length}`;

                if (btnPrev) btnPrev.disabled = s.step <= 1;
                if (btnNext) btnNext.disabled = s.step >= steps.length;
            }

            function prevTab(tab){
                const n = stepIndex(tab);
                if (n <= 1) return null;
                const prev = steps.find(s => s.step === n - 1);
                return prev ? prev.tab : null;
            }

            function nextTab(tab){
                const n = stepIndex(tab);
                if (n <= 0 || n >= steps.length) return null;
                const next = steps.find(s => s.step === n + 1);
                return next ? next.tab : null;
            }

            function hide(){
                storageSet(hiddenKey, '1');
                setVisible(false);
            }

            function toggle(){
                const hidden = storageGet(hiddenKey) === '1';
                if (hidden) {
                    storageDel(hiddenKey);
                    setVisible(true);
                    render(currentTab);
                } else {
                    hide();
                }
            }

            // Estado inicial: visible salvo que el usuario lo haya ocultado
            const isHidden = storageGet(hiddenKey) === '1';
            setVisible(!isHidden);
            render(currentTab);

            btnHide?.addEventListener('click', hide);
            btnHeader?.addEventListener('click', toggle);

            btnPrev?.addEventListener('click', () => {
                const t = prevTab(currentTab);
                if (t) goToTab(t);
            });

            btnNext?.addEventListener('click', () => {
                const t = nextTab(currentTab);
                if (t) goToTab(t);
            });
        })();
        </script>

    <!-- Cargar Bootstrap solo si no está disponible -->
    <script>
    if (typeof bootstrap === 'undefined') {
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';
        script.onload = function() {
            console.log('[ProcessesTasksApp] Bootstrap cargado');
        };
        document.head.appendChild(script);
    } else {
        console.log('[ProcessesTasksApp] Bootstrap ya está disponible');
    }
    </script>
    
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/es.js"></script>
    <!-- JS DEBUGGING: Comentar scripts problemáticos -->
    <!-- <script src="/modules/processes_tasks/js/demo_seed.js?v=dev"></script> -->
    <script>
    // CSRF Token Helper
    (function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        if (csrfToken) {
            // Helper global para agregar token CSRF a requests
            window.getCSRFToken = function() { return csrfToken; };
            
            // Configurar jQuery para enviar token en todas las peticiones AJAX
            if (window.jQuery) {
                $.ajaxSetup({
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-CSRF-Token', csrfToken);
                    }
                });
            }
            
            // Configurar fetch para incluir token (override global)
            const originalFetch = window.fetch;
            window.fetch = function(url, options = {}) {
                options.headers = options.headers || {};
                if (typeof options.headers.append === 'function') {
                    options.headers.append('X-CSRF-Token', csrfToken);
                } else {
                    options.headers['X-CSRF-Token'] = csrfToken;
                }
                return originalFetch(url, options);
            };
        }
    })();
    
    // Persistir última pestaña clickeada (para UX); lectura se hace del lado cliente
    document.addEventListener('click', function(e){
        var a = e.target.closest('a.nav-link[href*="?tab="]');
        if (!a) return;
        try { localStorage.setItem('pt_last_tab', new URL(a.href, location.href).searchParams.get('tab')); } catch(_){ }
    });

    // Mover cualquier .modal dentro de .tab-content hacia #pt-modals-root
    (function(){
        var bucket = document.getElementById('pt-modals-root');
        var content = document.querySelector('#module-processes-tasks .tab-content');
        if (!bucket || !content) return;
        content.querySelectorAll('.modal').forEach(function(m){ bucket.appendChild(m); });
    })();

    // Helper de toasts (Bootstrap)
    window.showToast = function(message, variant){
        variant = variant || 'primary';
        var container = document.getElementById('pt-toast-root');
        if (!container) return;
        var el = document.createElement('div');
        el.className = 'toast align-items-center text-bg-' + variant + ' border-0';
        el.setAttribute('role','status');
        el.innerHTML = '<div class="d-flex"><div class="toast-body">'+ (message||'') +'</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button></div>';
        container.appendChild(el);
        var t = new bootstrap.Toast(el, { delay: 3000 });
        t.show();
        el.addEventListener('hidden.bs.toast', function(){ el.remove(); });
    };
    </script>

    <?php if ($tab === 'organigrama'): ?>
      <!-- Carga defensiva: sólo si la vista no lo incluyó ya -->
      <script>
      (function(){
        var already = !!document.querySelector('script[src*="/modules/processes_tasks/js/organigrama-autosize.js"]');
        if (!already) {
          var s=document.createElement('script');
          s.src='/modules/processes_tasks/js/organigrama-autosize.js?v=2025-10-23';
          document.head.appendChild(s);
        }
      })();
      </script>
            <script>
            (function(){
                // Cargar controles flotantes (zoom/centrar/atajos) si no están aún
                var already = !!document.querySelector('script[src*="/modules/processes_tasks/js/organigrama-controls.js"]');
                if (!already) {
                    var s=document.createElement('script');
                    s.src='/modules/processes_tasks/js/organigrama-controls.js?v=2025-10-23';
                    document.head.appendChild(s);
                }
            })();
            </script>
    <?php endif; ?>

        <?php if ($tab === 'tasks'): ?>
            <script>
            (function(){
                // Avoid double-loading if the view already included tasks.js
                var already = !!document.querySelector('script[src*="/modules/processes_tasks/js/tasks.js"]');
                if (!already) {
                    var s=document.createElement('script');
                    s.src='/modules/processes_tasks/js/tasks.js?v=2025-10-23';
                    document.head.appendChild(s);
                }
            })();
            </script>
        <?php endif; ?>

    </div><!-- /module-processes-tasks -->
</body>
</html>