<?php

/**
 * Normalizador de Valores ENUM - Módulo Processes & Tasks
 * 
 * Convierte valores del frontend a los valores exactos de la base de datos
 * Sin modificar las tablas ni los ENUMs existentes. Actúa como traductor
 * bidireccional entre el lenguaje del usuario y el lenguaje de la DB.
 * 
 * @package     Indice_ERP
 * @subpackage  ProcessesTasks
 * @category    Helper
 * @author      Sistema Índice ERP
 * @version     1.1.0
 * @date        2025-11-22
 * @license     Proprietary
 * 
 * IMPORTANTE: Este archivo NO modifica la estructura de la base de datos.
 * Solo traduce valores entre frontend y backend manteniendo compatibilidad total.
 * 
 * Ejemplos de uso:
 * ```php
 * // Normalizar prioridad del form
 * $dbPriority = normalizePriority($_POST['priority']); // "baja" → "Normal"
 * 
 * // Normalizar status
 * $dbStatus = normalizeStatus($_POST['status']); // "pendiente" → "En tiempo"
 * 
 * // Generar badge HTML
 * echo renderPriorityBadge('Urgente'); // <span class="badge bg-danger">...</span>
 * ```
 */

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('Direct access not allowed');
}

// Cache estático para mejorar rendimiento en requests largos
$_ENUM_CACHE = [
    'priority_map' => null,
    'status_map' => null,
    'type_map' => null
];

/**
 * Normalizar prioridad del frontend a valor DB
 * 
 * Acepta múltiples formatos de entrada y los convierte al ENUM esperado por la DB.
 * Frontend puede enviar: baja, media, alta, urgente (minúsculas, español/inglés)
 * DB espera: 'Normal', 'Importante', 'Urgente' (capitalizados, tal como están en el ENUM)
 * 
 * @param string|null|int $priority Valor del frontend (puede ser string, int o null)
 * @return string Valor normalizado para DB ('Normal', 'Importante' o 'Urgente')
 * 
 * @example normalizePriority('baja')      → 'Normal'
 * @example normalizePriority('alta')      → 'Importante'
 * @example normalizePriority('urgent')    → 'Urgente'
 * @example normalizePriority(null)        → 'Normal'
 * @example normalizePriority(1)           → 'Normal'
 * @example normalizePriority(3)           → 'Urgente'
 */
function normalizePriority($priority): string
{
    global $_ENUM_CACHE;

    // Si es null o vacío, retornar default
    if ($priority === null || $priority === '') {
        return 'Normal';
    }

    // Si es numérico (1=baja, 2=media, 3=alta, 4=urgente)
    if (is_numeric($priority)) {
        $numericMap = [1 => 'Normal', 2 => 'Normal', 3 => 'Importante', 4 => 'Urgente'];
        return $numericMap[(int)$priority] ?? 'Normal';
    }

    // Lazy load del cache
    if ($_ENUM_CACHE['priority_map'] === null) {
        $_ENUM_CACHE['priority_map'] = [
            // Español
            'baja' => 'Normal',
            'bajo' => 'Normal',
            'normal' => 'Normal',
            'media' => 'Normal',
            'medio' => 'Normal',
            'regular' => 'Normal',
            'alta' => 'Importante',
            'alto' => 'Importante',
            'importante' => 'Importante',
            'urgente' => 'Urgente',
            'crítica' => 'Urgente',
            'critica' => 'Urgente',
            'muy alta' => 'Urgente',
            'muy urgente' => 'Urgente',

            // Inglés
            'low' => 'Normal',
            'normal' => 'Normal',
            'medium' => 'Normal',
            'high' => 'Importante',
            'important' => 'Importante',
            'urgent' => 'Urgente',
            'critical' => 'Urgente',
            'very high' => 'Urgente',

            // Ya normalizados (por si acaso)
            'Normal' => 'Normal',
            'Importante' => 'Importante',
            'Urgente' => 'Urgente',
        ];
    }

    $priority = strtolower(trim($priority));
    return $_ENUM_CACHE['priority_map'][$priority] ?? 'Normal';
}

/**
 * Normalizar status del frontend a valor DB
 * 
 * Acepta múltiples formatos de entrada y los convierte al ENUM esperado por la DB.
 * Frontend puede enviar: pendiente, en_proceso, completada, etc.
 * DB espera: 'En tiempo', 'En proceso', 'Terminada', 'Vencida', 'Auditada', 'Pausada'
 * 
 * @param string|null|int $status Valor del frontend (puede ser string, int o null)
 * @return string Valor normalizado para DB
 * 
 * @example normalizeStatus('pendiente')   → 'En tiempo'
 * @example normalizeStatus('en_proceso')  → 'En proceso'
 * @example normalizeStatus('done')        → 'Terminada'
 * @example normalizeStatus(null)          → 'En tiempo'
 * @example normalizeStatus(1)             → 'En tiempo'
 */
function normalizeStatus($status): string
{
    global $_ENUM_CACHE;

    // Si es null o vacío, retornar default
    if ($status === null || $status === '') {
        return 'En tiempo';
    }

    // Si es numérico (1=pendiente, 2=en proceso, 3=terminada, etc.)
    if (is_numeric($status)) {
        $numericMap = [
            1 => 'En tiempo',
            2 => 'En proceso',
            3 => 'Terminada',
            4 => 'Vencida',
            5 => 'Auditada',
            6 => 'Pausada'
        ];
        return $numericMap[(int)$status] ?? 'En tiempo';
    }

    // Lazy load del cache
    if ($_ENUM_CACHE['status_map'] === null) {
        $_ENUM_CACHE['status_map'] = [
            // Español - Pendiente/En tiempo
            'pendiente' => 'En tiempo',
            'en tiempo' => 'En tiempo',
            'en_tiempo' => 'En tiempo',
            'nueva' => 'En tiempo',
            'nuevo' => 'En tiempo',
            'sin iniciar' => 'En tiempo',
            'por hacer' => 'En tiempo',

            // Español - En proceso
            'en proceso' => 'En proceso',
            'en_proceso' => 'En proceso',
            'enproceso' => 'En proceso',
            'progreso' => 'En proceso',
            'trabajando' => 'En proceso',
            'activa' => 'En proceso',

            // Español - Terminada
            'terminada' => 'Terminada',
            'completada' => 'Terminada',
            'completa' => 'Terminada',
            'finalizada' => 'Terminada',
            'hecha' => 'Terminada',
            'lista' => 'Terminada',
            'cerrada' => 'Terminada',

            // Español - Vencida
            'vencida' => 'Vencida',
            'atrasada' => 'Vencida',
            'retrasada' => 'Vencida',
            'fuera de tiempo' => 'Vencida',

            // Español - Auditada
            'auditada' => 'Auditada',
            'revisada' => 'Auditada',
            'verificada' => 'Auditada',
            'aprobada' => 'Auditada',

            // Español - Pausada
            'pausada' => 'Pausada',
            'detenida' => 'Pausada',
            'suspendida' => 'Pausada',
            'en espera' => 'Pausada',
            'standby' => 'Pausada',

            // Inglés
            'pending' => 'En tiempo',
            'new' => 'En tiempo',
            'to do' => 'En tiempo',
            'in progress' => 'En proceso',
            'progress' => 'En proceso',
            'working' => 'En proceso',
            'active' => 'En proceso',
            'completed' => 'Terminada',
            'done' => 'Terminada',
            'finished' => 'Terminada',
            'closed' => 'Terminada',
            'overdue' => 'Vencida',
            'late' => 'Vencida',
            'delayed' => 'Vencida',
            'audited' => 'Auditada',
            'reviewed' => 'Auditada',
            'approved' => 'Auditada',
            'paused' => 'Pausada',
            'on hold' => 'Pausada',
            'hold' => 'Pausada',
            'waiting' => 'Pausada',

            // Ya normalizados (por si acaso)
            'En tiempo' => 'En tiempo',
            'En proceso' => 'En proceso',
            'Terminada' => 'Terminada',
            'Vencida' => 'Vencida',
            'Auditada' => 'Auditada',
            'Pausada' => 'Pausada',
        ];
    }

    $status = strtolower(trim($status));
    return $_ENUM_CACHE['status_map'][$status] ?? 'En tiempo';
}

/**
 * Normalizar tipo de tarea
 * 
 * DB espera: ENUM('Tarea', 'Proceso', 'Tarea de proceso')
 * Frontend puede enviar: task, proceso, subtarea, subtask, etc.
 * 
 * Soporta entrada numérica: 1=Tarea, 2=Proceso, 3=Tarea de proceso
 * Usa cache en memoria para mejorar performance en ciclos largos.
 * 
 * @param string|int|null $type Valor del frontend (string, numérico o null)
 * @return string Valor normalizado para DB ('Tarea', 'Proceso', 'Tarea de proceso')
 * 
 * @example normalizeTaskType('task')            → 'Tarea'
 * @example normalizeTaskType('subtarea')        → 'Tarea de proceso'
 * @example normalizeTaskType(2)                 → 'Proceso'
 * @example normalizeTaskType(null)              → 'Tarea' (default)
 */
function normalizeTaskType($type): string
{
    global $_ENUM_CACHE;

    if (empty($type)) {
        return 'Tarea'; // Default
    }

    // Soporte para entrada numérica (1-3)
    if (is_numeric($type)) {
        $numericMap = [
            1 => 'Tarea',
            2 => 'Proceso',
            3 => 'Tarea de proceso'
        ];
        return $numericMap[(int)$type] ?? 'Tarea';
    }

    // Lazy-load del mapa de tipos con cache
    if ($_ENUM_CACHE['type_map'] === null) {
        $_ENUM_CACHE['type_map'] = [
            // Español - Tarea
            'tarea' => 'Tarea',
            'actividad' => 'Tarea',
            'trabajo' => 'Tarea',

            // Inglés - Tarea
            'task' => 'Tarea',
            'activity' => 'Tarea',
            'job' => 'Tarea',

            // Español - Proceso
            'proceso' => 'Proceso',
            'flujo' => 'Proceso',
            'procedimiento' => 'Proceso',
            'workflow' => 'Proceso',

            // Inglés - Proceso
            'process' => 'Proceso',
            'workflow' => 'Proceso',
            'procedure' => 'Proceso',

            // Español - Proyecto
            'proyecto' => 'Proyecto',
            'iniciativa' => 'Proyecto',
            'asignacion' => 'Proyecto',

            // Inglés - Proyecto
            'project' => 'Proyecto',
            'initiative' => 'Proyecto',
            'assignment' => 'Proyecto',

            // Español - Tarea de proceso
            'tarea de proceso' => 'Tarea de proceso',
            'tarea_de_proceso' => 'Tarea de proceso',
            'subtarea' => 'Tarea de proceso',
            'sub-tarea' => 'Tarea de proceso',
            'tarea secundaria' => 'Tarea de proceso',
            'actividad de proceso' => 'Tarea de proceso',

            // Inglés - Tarea de proceso
            'process task' => 'Tarea de proceso',
            'process_task' => 'Tarea de proceso',
            'subtask' => 'Tarea de proceso',
            'sub-task' => 'Tarea de proceso',
            'child task' => 'Tarea de proceso',

            // Ya normalizados (pass-through)
            'Tarea' => 'Tarea',
            'Proceso' => 'Proceso',
            'Proyecto' => 'Proyecto',
            'Tarea de proceso' => 'Tarea de proceso',
        ];
    }

    $type = strtolower(trim($type));
    return $_ENUM_CACHE['type_map'][$type] ?? 'Tarea';
}

/**
 * Convertir valor DB a valor amigable para el frontend con metadatos visuales
 * 
 * Transforma los valores de la base de datos en objetos ricos con metadatos
 * para renderizado en la UI: valor original, etiqueta, color Bootstrap, icono.
 * 
 * NO modifica la base de datos. Solo prepara datos para visualización.
 * Útil para generar badges, tarjetas, tooltips con estilos consistentes.
 * 
 * @param string $dbValue Valor almacenado en la DB ('Normal', 'En tiempo', 'Tarea', etc.)
 * @param string $field Tipo de campo ('priority'/'nivel', 'status', 'type'/'tipo')
 * @return array Asociativo con keys: 'value' (frontend), 'label' (UI), 'color' (Bootstrap), 'icon' (Bootstrap Icons), 'description' (tooltip)
 * 
 * @example denormalizeTasks('Urgente', 'priority') 
 *          → ['value'=>'urgente', 'label'=>'Urgente', 'color'=>'danger', 'icon'=>'bi-exclamation-triangle-fill', 'description'=>'Requiere atención inmediata']
 * 
 * @example denormalizeTasks('Terminada', 'status')
 *          → ['value'=>'completada', 'label'=>'Completada', 'color'=>'success', 'icon'=>'bi-check-circle-fill', 'description'=>'Tarea finalizada exitosamente']
 */
function denormalizeTasks($dbValue, $field): array
{
    $field = strtolower($field);

    // Prioridades con descripciones para tooltips
    if ($field === 'priority' || $field === 'nivel') {
        $priorityMap = [
            'Normal' => [
                'value' => 'normal',
                'label' => 'Normal',
                'color' => 'secondary',
                'icon' => 'bi-dash-circle',
                'description' => 'Prioridad estándar, sin urgencia'
            ],
            'Importante' => [
                'value' => 'alta',
                'label' => 'Alta',
                'color' => 'warning',
                'icon' => 'bi-exclamation-circle',
                'description' => 'Requiere atención prioritaria'
            ],
            'Urgente' => [
                'value' => 'urgente',
                'label' => 'Urgente',
                'color' => 'danger',
                'icon' => 'bi-exclamation-triangle-fill',
                'description' => 'Requiere atención inmediata'
            ],
        ];
        return $priorityMap[$dbValue] ?? $priorityMap['Normal'];
    }

    // Status con descripciones y metadatos extendidos
    if ($field === 'status') {
        $statusMap = [
            'En tiempo' => [
                'value' => 'pendiente',
                'label' => 'Pendiente',
                'color' => 'info',
                'icon' => 'bi-clock',
                'description' => 'Tarea asignada, dentro del plazo establecido'
            ],
            'En proceso' => [
                'value' => 'en_proceso',
                'label' => 'En Proceso',
                'color' => 'primary',
                'icon' => 'bi-arrow-repeat',
                'description' => 'Trabajo en curso, progresando activamente'
            ],
            'Terminada' => [
                'value' => 'completada',
                'label' => 'Completada',
                'color' => 'success',
                'icon' => 'bi-check-circle-fill',
                'description' => 'Tarea finalizada exitosamente'
            ],
            'Vencida' => [
                'value' => 'vencida',
                'label' => 'Vencida',
                'color' => 'danger',
                'icon' => 'bi-x-circle',
                'description' => 'Se excedió la fecha límite de entrega'
            ],
            'Auditada' => [
                'value' => 'auditada',
                'label' => 'Auditada',
                'color' => 'dark',
                'icon' => 'bi-shield-check',
                'description' => 'Revisada y aprobada por control de calidad'
            ],
            'Pausada' => [
                'value' => 'pausada',
                'label' => 'Pausada',
                'color' => 'secondary',
                'icon' => 'bi-pause-circle',
                'description' => 'Suspendida temporalmente, en espera'
            ],
        ];
        return $statusMap[$dbValue] ?? $statusMap['En tiempo'];
    }

    // Tipo de tarea con descripciones
    if ($field === 'type' || $field === 'tipo') {
        $typeMap = [
            'Tarea' => [
                'value' => 'tarea',
                'label' => 'Tarea',
                'color' => 'primary',
                'icon' => 'bi-check2-square',
                'description' => 'Actividad individual con objetivo específico'
            ],
            'Proceso' => [
                'value' => 'proceso',
                'label' => 'Proceso',
                'color' => 'info',
                'icon' => 'bi-arrow-repeat',
                'description' => 'Conjunto de tareas relacionadas secuencialmente'
            ],
            'Tarea de proceso' => [
                'value' => 'tarea_de_proceso',
                'label' => 'Subtarea',
                'color' => 'secondary',
                'icon' => 'bi-list-check',
                'description' => 'Tarea que forma parte de un proceso mayor'
            ],
        ];
        return $typeMap[$dbValue] ?? $typeMap['Tarea'];
    }

    // Fallback genérico para campos desconocidos
    return [
        'value' => strtolower($dbValue),
        'label' => $dbValue,
        'color' => 'secondary',
        'icon' => 'bi-circle',
        'description' => $dbValue
    ];
}

/**
 * Validar que un valor sea exactamente válido para un ENUM de la base de datos
 * 
 * Verifica que el valor proporcionado coincida EXACTAMENTE con uno de los valores
 * aceptados por el ENUM en la base de datos (case-sensitive).
 * 
 * Usa cache estático para evitar reconstruir arrays en cada llamada.
 * Útil antes de ejecutar INSERTs o UPDATEs para prevenir errores SQL.
 * 
 * @param string $value Valor a validar (debe ser el valor DB, no frontend)
 * @param string $field Campo a validar ('priority'/'nivel', 'status', 'type'/'tipo')
 * @return bool True si el valor es válido, false si no existe en el ENUM
 * 
 * @example isValidEnumValue('Urgente', 'priority')     → true
 * @example isValidEnumValue('urgente', 'priority')     → false (case-sensitive)
 * @example isValidEnumValue('Terminada', 'status')     → true
 * @example isValidEnumValue('completada', 'status')    → false (valor frontend, no DB)
 */
function isValidEnumValue($value, $field): bool
{
    static $_VALIDATION_CACHE = null;

    // Lazy-load del cache de validación
    if ($_VALIDATION_CACHE === null) {
        $_VALIDATION_CACHE = [
            'priority' => ['Normal', 'Importante', 'Urgente'],
            'nivel' => ['Normal', 'Importante', 'Urgente'],
            'status' => ['En tiempo', 'En proceso', 'Terminada', 'Vencida', 'Auditada', 'Pausada'],
            'type' => ['Tarea', 'Proceso', 'Tarea de proceso'],
            'tipo' => ['Tarea', 'Proceso', 'Tarea de proceso'],
        ];
    }

    $field = strtolower($field);
    return in_array($value, $_VALIDATION_CACHE[$field] ?? [], true);
}

/**
 * Obtener todos los valores válidos para un campo ENUM
 * 
 * Retorna los valores EXACTOS que espera la base de datos para un campo específico.
 * Útil para generar selects, validaciones o documentación.
 * 
 * @param string $field Campo (priority, status, type, nivel, tipo)
 * @return array Lista de valores válidos del ENUM en la DB
 * 
 * @example getValidEnumValues('priority') → ['Normal', 'Importante', 'Urgente']
 * @example getValidEnumValues('status')   → ['En tiempo', 'En proceso', ...]
 */
function getValidEnumValues($field): array
{
    $field = strtolower($field);

    $validValues = [
        'priority' => ['Normal', 'Importante', 'Urgente'],
        'nivel' => ['Normal', 'Importante', 'Urgente'],
        'status' => ['En tiempo', 'En proceso', 'Terminada', 'Vencida', 'Auditada', 'Pausada'],
        'type' => ['Tarea', 'Proceso', 'Tarea de proceso'],
        'tipo' => ['Tarea', 'Proceso', 'Tarea de proceso'],
    ];

    return $validValues[$field] ?? [];
}

// ============================================================================
// FUNCIONES ADICIONALES PARA RENDERIZADO HTML (NO MODIFICAN BACKEND)
// ============================================================================

/**
 * Generar badge HTML para prioridad con estilos de Índice ERP
 * 
 * NO modifica ningún dato. Solo genera HTML para mostrar en vistas.
 * Usa clases de Bootstrap 5 y colores institucionales.
 * 
 * @param string $priority Valor de prioridad ('Normal', 'Importante', 'Urgente')
 * @param bool $withIcon Incluir icono de Bootstrap Icons
 * @param string $size Tamaño del badge ('sm', 'md', 'lg')
 * @return string HTML del badge
 * 
 * @example renderPriorityBadge('Urgente') → '<span class="badge bg-danger">...</span>'
 */
function renderPriorityBadge($priority, $withIcon = true, $size = 'md'): string
{
    $info = denormalizeTasks($priority, 'priority');

    $sizeClasses = [
        'sm' => 'badge-sm',
        'md' => '',
        'lg' => 'badge-lg fs-6'
    ];

    $sizeClass = $sizeClasses[$size] ?? '';
    $icon = $withIcon ? "<i class='bi {$info['icon']} me-1'></i>" : '';

    return sprintf(
        '<span class="badge bg-%s %s" title="Prioridad: %s">%s%s</span>',
        htmlspecialchars($info['color'], ENT_QUOTES, 'UTF-8'),
        $sizeClass,
        htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8'),
        $icon,
        htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8')
    );
}

/**
 * Generar badge HTML para status con estilos de Índice ERP
 * 
 * NO modifica ningún dato. Solo genera HTML para mostrar en vistas.
 * Usa clases de Bootstrap 5 y colores institucionales.
 * 
 * @param string $status Valor de status ('En tiempo', 'En proceso', etc.)
 * @param bool $withIcon Incluir icono de Bootstrap Icons
 * @param string $size Tamaño del badge ('sm', 'md', 'lg')
 * @return string HTML del badge
 * 
 * @example renderStatusBadge('Terminada') → '<span class="badge bg-success">...</span>'
 */
function renderStatusBadge($status, $withIcon = true, $size = 'md'): string
{
    $info = denormalizeTasks($status, 'status');

    $sizeClasses = [
        'sm' => 'badge-sm',
        'md' => '',
        'lg' => 'badge-lg fs-6'
    ];

    $sizeClass = $sizeClasses[$size] ?? '';
    $icon = $withIcon ? "<i class='bi {$info['icon']} me-1'></i>" : '';

    return sprintf(
        '<span class="badge bg-%s %s" title="Estado: %s">%s%s</span>',
        htmlspecialchars($info['color'], ENT_QUOTES, 'UTF-8'),
        $sizeClass,
        htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8'),
        $icon,
        htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8')
    );
}

/**
 * Generar badge HTML para tipo de tarea con estilos de Índice ERP
 * 
 * NO modifica ningún dato. Solo genera HTML para mostrar en vistas.
 * 
 * @param string $type Valor de tipo ('Tarea', 'Proceso', 'Tarea de proceso')
 * @param bool $withIcon Incluir icono de Bootstrap Icons
 * @param string $size Tamaño del badge ('sm', 'md', 'lg')
 * @return string HTML del badge
 */
function renderTypeBadge($type, $withIcon = true, $size = 'md'): string
{
    $info = denormalizeTasks($type, 'type');

    $sizeClasses = [
        'sm' => 'badge-sm',
        'md' => '',
        'lg' => 'badge-lg fs-6'
    ];

    $sizeClass = $sizeClasses[$size] ?? '';
    $icon = $withIcon ? "<i class='bi {$info['icon']} me-1'></i>" : '';

    return sprintf(
        '<span class="badge bg-%s %s" title="Tipo: %s">%s%s</span>',
        htmlspecialchars($info['color'], ENT_QUOTES, 'UTF-8'),
        $sizeClass,
        htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8'),
        $icon,
        htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8')
    );
}

/**
 * Generar select HTML para prioridades
 * 
 * Genera un <select> con todas las opciones de prioridad válidas.
 * Útil para formularios de creación/edición de tareas.
 * 
 * @param string|null $selected Valor actualmente seleccionado
 * @param string $name Atributo name del select
 * @param string $id Atributo id del select
 * @param string $class Clases CSS adicionales
 * @return string HTML del select completo
 */
function renderPrioritySelect($selected = null, $name = 'priority', $id = 'priority', $class = 'form-select'): string
{
    $options = '';
    $validValues = getValidEnumValues('priority');

    foreach ($validValues as $value) {
        $info = denormalizeTasks($value, 'priority');
        $isSelected = ($value === $selected) ? 'selected' : '';
        $options .= sprintf(
            '<option value="%s" %s>%s</option>',
            htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
            $isSelected,
            htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8')
        );
    }

    return sprintf(
        '<select name="%s" id="%s" class="%s">%s</select>',
        htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($id, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($class, ENT_QUOTES, 'UTF-8'),
        $options
    );
}

/**
 * Generar select HTML para status
 * 
 * Genera un <select> con todas las opciones de status válidas.
 * Útil para formularios de creación/edición de tareas.
 * 
 * @param string|null $selected Valor actualmente seleccionado
 * @param string $name Atributo name del select
 * @param string $id Atributo id del select
 * @param string $class Clases CSS adicionales
 * @return string HTML del select completo
 */
function renderStatusSelect($selected = null, $name = 'status', $id = 'status', $class = 'form-select'): string
{
    $options = '';
    $validValues = getValidEnumValues('status');

    foreach ($validValues as $value) {
        $info = denormalizeTasks($value, 'status');
        $isSelected = ($value === $selected) ? 'selected' : '';
        $options .= sprintf(
            '<option value="%s" %s>%s</option>',
            htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
            $isSelected,
            htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8')
        );
    }

    return sprintf(
        '<select name="%s" id="%s" class="%s">%s</select>',
        htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($id, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($class, ENT_QUOTES, 'UTF-8'),
        $options
    );
}

/**
 * Obtener color de progreso basado en porcentaje de completitud
 * 
 * Útil para barras de progreso y visualizaciones.
 * 
 * @param int $percentage Porcentaje de completitud (0-100)
 * @return string Clase de color de Bootstrap ('success', 'warning', 'danger')
 */
function getProgressColor($percentage): string
{
    if ($percentage >= 80) return 'success';
    if ($percentage >= 50) return 'info';
    if ($percentage >= 25) return 'warning';
    return 'danger';
}

/**
 * Formatear fecha relativa (hace X días, hace X horas)
 * 
 * Convierte una fecha a formato relativo legible en español.
 * 
 * @param string|DateTime $date Fecha a formatear
 * @return string Fecha relativa ("Hace 2 días", "Hace 3 horas", etc.)
 */
function formatRelativeDate($date): string
{
    try {
        if (is_string($date)) {
            $date = new DateTime($date);
        }

        $now = new DateTime();
        $diff = $now->diff($date);

        if ($diff->y > 0) return $diff->y . ' año' . ($diff->y > 1 ? 's' : '');
        if ($diff->m > 0) return $diff->m . ' mes' . ($diff->m > 1 ? 'es' : '');
        if ($diff->d > 0) return 'Hace ' . $diff->d . ' día' . ($diff->d > 1 ? 's' : '');
        if ($diff->h > 0) return 'Hace ' . $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
        if ($diff->i > 0) return 'Hace ' . $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');

        return 'Ahora mismo';
    } catch (Exception $e) {
        return 'Fecha inválida';
    }
}
