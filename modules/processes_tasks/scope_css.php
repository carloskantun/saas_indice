<?php
/**
 * Script temporal para scopear el CSS del módulo processes_tasks
 */

$cssFile = __DIR__ . '/processes_tasks.css';
$outputFile = __DIR__ . '/processes_tasks_scoped.css';

if (!file_exists($cssFile)) {
    die("ERROR: No se encuentra el archivo CSS\n");
}

$css = file_get_contents($cssFile);

// Función para prefijar selectores
function scopeCSS($css, $scope = '#module-processes-tasks') {
    // Mantener comentarios
    $output = '';
    
    // Dividir en bloques (comentarios + reglas)
    $lines = explode("\n", $css);
    $inRule = false;
    $buffer = '';
    $commentBuffer = '';
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // Comentarios
        if (preg_match('/^\/\*/', $trimmed)) {
            $commentBuffer .= $line . "\n";
            continue;
        }
        
        // Línea vacía
        if (empty($trimmed)) {
            if ($commentBuffer) {
                $output .= $commentBuffer;
                $commentBuffer = '';
            }
            $output .= $line . "\n";
            continue;
        }
        
        // Selector (antes de {)
        if (!$inRule && preg_match('/\{/', $line)) {
            // Es selector + inicio de regla
            $parts = explode('{', $line, 2);
            $selector = trim($parts[0]);
            $restOfLine = '{' . $parts[1];
            
            // No prefijar @keyframes, @media, :root
            if (preg_match('/^(@keyframes|@media|:root)/', $selector)) {
                if ($commentBuffer) {
                    $output .= $commentBuffer;
                    $commentBuffer = '';
                }
                $output .= $line . "\n";
                $inRule = true;
                continue;
            }
            
            // Separar múltiples selectores
            $selectors = array_map('trim', explode(',', $selector));
            $scopedSelectors = [];
            
            foreach ($selectors as $sel) {
                // Si ya tiene el scope, no duplicar
                if (strpos($sel, $scope) === false) {
                    // Si es un pseudo-elemento o pseudo-clase que va después
                    if (preg_match('/^([^:]+)(::?[a-z-]+)$/i', $sel, $matches)) {
                        $scopedSelectors[] = $scope . ' ' . $matches[1] . $matches[2];
                    } else {
                        $scopedSelectors[] = $scope . ' ' . $sel;
                    }
                } else {
                    $scopedSelectors[] = $sel;
                }
            }
            
            if ($commentBuffer) {
                $output .= $commentBuffer;
                $commentBuffer = '';
            }
            
            $output .= implode(",\n", $scopedSelectors) . ' ' . $restOfLine . "\n";
            $inRule = true;
            continue;
        }
        
        // Dentro de una regla
        if ($inRule) {
            $output .= $line . "\n";
            // Cerrar regla
            if (preg_match('/\}/', $line)) {
                $inRule = false;
            }
            continue;
        }
        
        // Selector multilínea (selector sin {)
        if (!$inRule) {
            $buffer .= $line . "\n";
            continue;
        }
    }
    
    return $output;
}

$scopedCSS = scopeCSS($css);

// Agregar clase utilitaria
$scopedCSS .= "\n\n/* === UTILITY CLASSES === */\n";
$scopedCSS .= "#module-processes-tasks .pt-hidden { display: none !important; }\n";

file_put_contents($outputFile, $scopedCSS);

echo "✓ CSS scopeado guardado en: {$outputFile}\n";
echo "  Total caracteres: " . strlen($scopedCSS) . "\n";
