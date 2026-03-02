<?php
/**
 * EJEMPLOS DE USO: enum_normalizer.php
 * 
 * Este archivo contiene ejemplos prácticos de todas las funciones del normalizador.
 * NO ejecutar en producción, solo para referencia de desarrolladores.
 * 
 * @package Índice ERP - Módulo Tareas y Procesos
 * @version 1.0.0
 * @author Sistema Índice
 */

require_once __DIR__ . '/enum_normalizer.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejemplos - Enum Normalizer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .card { border-radius: 16px; margin-bottom: 1.5rem; }
        .badge { border-radius: 8px; }
        pre { background: #f8f9fa; padding: 1rem; border-radius: 8px; }
        .example-output { background: #e3f2fd; padding: 1rem; border-radius: 8px; margin-top: 0.5rem; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4"><i class="bi bi-code-square me-2"></i>Ejemplos: Enum Normalizer</h1>
                <p class="lead">Guía práctica de uso de todas las funciones del normalizador de ENUMs</p>
                <hr>
            </div>
        </div>

        <!-- Ejemplo 1: Normalizar Prioridad -->
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-1-circle me-2"></i>normalizePriority()</h5>
                    </div>
                    <div class="card-body">
                        <p>Convierte valores del frontend a valores de la DB:</p>
                        <pre><code>normalizePriority('baja')     → 'Normal'
normalizePriority('alta')     → 'Importante'
normalizePriority('urgente')  → 'Urgente'
normalizePriority(3)          → 'Importante'
normalizePriority('high')     → 'Importante'</code></pre>
                        
                        <h6 class="mt-3">Resultado en vivo:</h6>
                        <div class="example-output">
                            <?php
                            $examples = ['baja', 'media', 'alta', 'urgente', 'high', 3, null];
                            foreach ($examples as $ex) {
                                $normalized = normalizePriority($ex);
                                echo "<div class='mb-2'>";
                                echo "<code>normalizePriority(" . var_export($ex, true) . ")</code> ";
                                echo "→ <strong>" . htmlspecialchars($normalized) . "</strong>";
                                echo "</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-2-circle me-2"></i>normalizeStatus()</h5>
                    </div>
                    <div class="card-body">
                        <p>Convierte valores del frontend a valores de la DB:</p>
                        <pre><code>normalizeStatus('pendiente')    → 'En tiempo'
normalizeStatus('en proceso')   → 'En proceso'
normalizeStatus('completada')   → 'Terminada'
normalizeStatus(3)              → 'Terminada'
normalizeStatus('delayed')      → 'Vencida'</code></pre>
                        
                        <h6 class="mt-3">Resultado en vivo:</h6>
                        <div class="example-output">
                            <?php
                            $examples = ['pendiente', 'en proceso', 'completada', 'vencida', 'paused', 2];
                            foreach ($examples as $ex) {
                                $normalized = normalizeStatus($ex);
                                echo "<div class='mb-2'>";
                                echo "<code>normalizeStatus(" . var_export($ex, true) . ")</code> ";
                                echo "→ <strong>" . htmlspecialchars($normalized) . "</strong>";
                                echo "</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ejemplo 3: Normalizar Tipo -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-3-circle me-2"></i>normalizeTaskType()</h5>
                    </div>
                    <div class="card-body">
                        <p>Convierte valores del frontend a valores de la DB:</p>
                        <div class="row">
                            <div class="col-md-6">
                                <pre><code>normalizeTaskType('task')        → 'Tarea'
normalizeTaskType('proceso')     → 'Proceso'
normalizeTaskType('subtarea')    → 'Tarea de proceso'
normalizeTaskType(2)             → 'Proceso'</code></pre>
                            </div>
                            <div class="col-md-6">
                                <h6>Resultado en vivo:</h6>
                                <div class="example-output">
                                    <?php
                                    $examples = ['task', 'proceso', 'subtarea', 'workflow', 2];
                                    foreach ($examples as $ex) {
                                        $normalized = normalizeTaskType($ex);
                                        echo "<div class='mb-2'>";
                                        echo "<code>normalizeTaskType(" . var_export($ex, true) . ")</code> ";
                                        echo "→ <strong>" . htmlspecialchars($normalized) . "</strong>";
                                        echo "</div>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ejemplo 4: Desnormalizar (para UI) -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-4-circle me-2"></i>denormalizeTasks() - Datos para UI</h5>
                    </div>
                    <div class="card-body">
                        <p>Convierte valores de DB a objetos ricos con metadatos visuales:</p>
                        
                        <h6 class="mt-3">Prioridades:</h6>
                        <div class="example-output">
                            <?php
                            $priorities = ['Normal', 'Importante', 'Urgente'];
                            foreach ($priorities as $p) {
                                $data = denormalizeTasks($p, 'priority');
                                echo "<div class='mb-3'>";
                                echo "<strong>" . htmlspecialchars($p) . "</strong> → ";
                                echo "<span class='badge bg-{$data['color']} ms-2'>";
                                echo "<i class='{$data['icon']} me-1'></i>{$data['label']}";
                                echo "</span>";
                                echo "<br><small class='text-muted'>{$data['description']}</small>";
                                echo "</div>";
                            }
                            ?>
                        </div>

                        <h6 class="mt-4">Status:</h6>
                        <div class="example-output">
                            <?php
                            $statuses = ['En tiempo', 'En proceso', 'Terminada', 'Vencida', 'Auditada', 'Pausada'];
                            foreach ($statuses as $s) {
                                $data = denormalizeTasks($s, 'status');
                                echo "<div class='mb-3'>";
                                echo "<strong>" . htmlspecialchars($s) . "</strong> → ";
                                echo "<span class='badge bg-{$data['color']} ms-2'>";
                                echo "<i class='{$data['icon']} me-1'></i>{$data['label']}";
                                echo "</span>";
                                echo "<br><small class='text-muted'>{$data['description']}</small>";
                                echo "</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ejemplo 5: Renderizar Badges HTML -->
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="bi bi-5-circle me-2"></i>renderPriorityBadge()</h5>
                    </div>
                    <div class="card-body">
                        <p>Genera HTML de badges para prioridades:</p>
                        <h6>Código:</h6>
                        <pre><code>&lt;?php
echo renderPriorityBadge('Urgente');
?&gt;</code></pre>
                        
                        <h6 class="mt-3">Resultado:</h6>
                        <div class="example-output">
                            <?php
                            echo renderPriorityBadge('Normal', true, 'md') . ' ';
                            echo renderPriorityBadge('Importante', true, 'md') . ' ';
                            echo renderPriorityBadge('Urgente', true, 'md');
                            ?>
                            <hr>
                            <small>Tamaño grande:</small><br>
                            <?php
                            echo renderPriorityBadge('Urgente', true, 'lg');
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-6-circle me-2"></i>renderStatusBadge()</h5>
                    </div>
                    <div class="card-body">
                        <p>Genera HTML de badges para status:</p>
                        <h6>Código:</h6>
                        <pre><code>&lt;?php
echo renderStatusBadge('Terminada');
?&gt;</code></pre>
                        
                        <h6 class="mt-3">Resultado:</h6>
                        <div class="example-output">
                            <?php
                            echo renderStatusBadge('En tiempo', true, 'sm') . ' ';
                            echo renderStatusBadge('En proceso', true, 'sm') . ' ';
                            echo renderStatusBadge('Terminada', true, 'sm') . '<br>';
                            echo renderStatusBadge('Vencida', true, 'sm') . ' ';
                            echo renderStatusBadge('Auditada', true, 'sm') . ' ';
                            echo renderStatusBadge('Pausada', true, 'sm');
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-7-circle me-2"></i>renderTypeBadge()</h5>
                    </div>
                    <div class="card-body">
                        <p>Genera HTML de badges para tipos:</p>
                        <h6>Código:</h6>
                        <pre><code>&lt;?php
echo renderTypeBadge('Proceso');
?&gt;</code></pre>
                        
                        <h6 class="mt-3">Resultado:</h6>
                        <div class="example-output">
                            <?php
                            echo renderTypeBadge('Tarea', true, 'md') . '<br>';
                            echo renderTypeBadge('Proceso', true, 'md') . '<br>';
                            echo renderTypeBadge('Tarea de proceso', true, 'md');
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ejemplo 6: Renderizar Selects -->
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-8-circle me-2"></i>renderPrioritySelect()</h5>
                    </div>
                    <div class="card-body">
                        <p>Genera selects HTML completos:</p>
                        <h6>Código:</h6>
                        <pre><code>&lt;?php
echo renderPrioritySelect('Importante', 'priority', 'priority-field', 'form-select');
?&gt;</code></pre>
                        
                        <h6 class="mt-3">Resultado:</h6>
                        <div class="example-output">
                            <?php echo renderPrioritySelect('Importante', 'priority', 'priority-field', 'form-select'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-9-circle me-2"></i>renderStatusSelect()</h5>
                    </div>
                    <div class="card-body">
                        <p>Genera selects HTML completos:</p>
                        <h6>Código:</h6>
                        <pre><code>&lt;?php
echo renderStatusSelect('En proceso', 'status', 'status-field', 'form-select');
?&gt;</code></pre>
                        
                        <h6 class="mt-3">Resultado:</h6>
                        <div class="example-output">
                            <?php echo renderStatusSelect('En proceso', 'status', 'status-field', 'form-select'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ejemplo 7: Validación -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-shield-check me-2"></i>isValidEnumValue() - Validación</h5>
                    </div>
                    <div class="card-body">
                        <p>Verifica que un valor sea válido ANTES de guardarlo en la DB:</p>
                        <div class="row">
                            <div class="col-md-6">
                                <pre><code>isValidEnumValue('Urgente', 'priority')    → true
isValidEnumValue('urgente', 'priority')    → false
isValidEnumValue('Terminada', 'status')    → true
isValidEnumValue('completada', 'status')   → false</code></pre>
                            </div>
                            <div class="col-md-6">
                                <h6>Resultado en vivo:</h6>
                                <div class="example-output">
                                    <?php
                                    $validations = [
                                        ['Urgente', 'priority'],
                                        ['urgente', 'priority'],
                                        ['Terminada', 'status'],
                                        ['completada', 'status'],
                                        ['Proceso', 'type'],
                                        ['proceso', 'type'],
                                    ];
                                    foreach ($validations as [$value, $field]) {
                                        $isValid = isValidEnumValue($value, $field);
                                        $badge = $isValid ? 'success' : 'danger';
                                        $icon = $isValid ? 'check-circle-fill' : 'x-circle-fill';
                                        echo "<div class='mb-2'>";
                                        echo "<code>isValidEnumValue('$value', '$field')</code> ";
                                        echo "<span class='badge bg-$badge'><i class='bi bi-$icon'></i> " . var_export($isValid, true) . "</span>";
                                        echo "</div>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ejemplo 8: Utilidades adicionales -->
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <h5 class="mb-0"><i class="bi bi-palette me-2"></i>getProgressColor()</h5>
                    </div>
                    <div class="card-body">
                        <p>Obtiene color según porcentaje de progreso:</p>
                        <div class="example-output">
                            <?php
                            $percentages = [10, 35, 60, 85];
                            foreach ($percentages as $p) {
                                $color = getProgressColor($p);
                                echo "<div class='mb-2'>";
                                echo "<div class='d-flex align-items-center'>";
                                echo "<span style='width: 80px;'><strong>$p%</strong></span>";
                                echo "<div class='progress flex-grow-1' style='height: 25px;'>";
                                echo "<div class='progress-bar bg-$color' style='width: $p%'>$p%</div>";
                                echo "</div>";
                                echo "</div>";
                                echo "</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>formatRelativeDate()</h5>
                    </div>
                    <div class="card-body">
                        <p>Formatea fechas de manera relativa:</p>
                        <div class="example-output">
                            <?php
                            $dates = [
                                date('Y-m-d H:i:s'), // Ahora
                                date('Y-m-d H:i:s', strtotime('-2 hours')),
                                date('Y-m-d H:i:s', strtotime('-1 day')),
                                date('Y-m-d H:i:s', strtotime('-5 days')),
                            ];
                            foreach ($dates as $d) {
                                $relative = formatRelativeDate($d);
                                echo "<div class='mb-2'>";
                                echo "<code>" . htmlspecialchars($d) . "</code> ";
                                echo "→ <strong>" . htmlspecialchars($relative) . "</strong>";
                                echo "</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie de página -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="alert alert-info">
                    <h5><i class="bi bi-info-circle me-2"></i>Notas importantes:</h5>
                    <ul class="mb-0">
                        <li><strong>NO modifica la base de datos:</strong> Todas las funciones son helpers de transformación</li>
                        <li><strong>Cache integrado:</strong> Las funciones usan cache estático para mejor performance</li>
                        <li><strong>Compatibilidad total:</strong> No rompe código existente, solo agrega funcionalidades</li>
                        <li><strong>Documentación PHPDoc:</strong> Todas las funciones tienen documentación completa</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
