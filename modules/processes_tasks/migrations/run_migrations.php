<?php
/**
 * Runner de Migraciones - Módulo Processes & Tasks
 * Ejecuta archivos SQL de migración de forma segura
 * 
 * Uso:
 * - CLI: php run_migrations.php
 * - Web: /modules/processes_tasks/migrations/run_migrations.php?run=1
 */

require_once __DIR__ . '/../../../bootstrap.php';

// Verificar que tenemos PDO
if (!function_exists('db')) {
    // Fallback: intentar cargar conexión directamente
    if (file_exists(__DIR__ . '/../../../core/db.php')) {
        require_once __DIR__ . '/../../../core/db.php';
    }
}

// Obtener instancia PDO
try {
    $pdo = db();
} catch (Exception $e) {
    // Si no hay función db(), intentar obtener PDO de variable global
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        http_response_code(500);
        die(json_encode(['ok' => false, 'error' => 'PDO no inicializado: ' . $e->getMessage()]));
    }
}

header('Content-Type: application/json; charset=utf-8');

try {
    // Verificar que es MySQL
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver !== 'mysql') {
        throw new Exception("Driver incompatible: $driver. Se requiere MySQL.");
    }
    
    // Obtener archivos SQL de migración (priorizar *fixed* sobre originales)
    $dir = __DIR__;
    $files = glob($dir . '/*create*fixed*.sql');
    
    if (empty($files)) {
        // Fallback a archivos originales si no hay _fixed
        $files = glob($dir . '/*create*.sql');
    }
    
    if (empty($files)) {
        throw new Exception("No se encontraron archivos de migración en: $dir");
    }
    
    sort($files, SORT_NATURAL);
    
    $applied = [];
    $errors = [];
    
    // Intentar ejecutar en transacción (algunas DDL no soportan transacciones)
    $useTransaction = true;
    
    try {
        if ($useTransaction) {
            $pdo->beginTransaction();
        }
        
        foreach ($files as $file) {
            $filename = basename($file);
            
            try {
                $sql = file_get_contents($file);
                
                if ($sql === false || empty(trim($sql))) {
                    throw new Exception("No se pudo leer o el archivo está vacío");
                }
                
                // Ejecutar SQL (puede contener múltiples statements)
                $pdo->exec($sql);
                
                $applied[] = $filename;
                
            } catch (Exception $e) {
                $errors[] = [
                    'file' => $filename,
                    'error' => $e->getMessage()
                ];
                
                // Si hay error, detener ejecución
                throw new Exception("Error en $filename: " . $e->getMessage());
            }
        }
        
        if ($useTransaction && $pdo->inTransaction()) {
            $pdo->commit();
        }
        
        // Ejecutar seed si existe (opcional)
        $seedFile = $dir . '/20251109_seed_tasks.sql';
        if (file_exists($seedFile) && isset($_GET['seed'])) {
            $seedSql = file_get_contents($seedFile);
            if ($seedSql) {
                try {
                    $pdo->exec($seedSql);
                    $applied[] = 'seed_tasks.sql';
                } catch (Exception $e) {
                    // Ignorar errores de seed (pueden ser duplicados)
                    $errors[] = [
                        'file' => 'seed_tasks.sql',
                        'error' => 'Seed falló (posiblemente datos duplicados): ' . $e->getMessage()
                    ];
                }
            }
        }
        
    } catch (Exception $e) {
        if ($useTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    
    // Verificar tablas creadas
    $stmt = $pdo->query("SHOW TABLES LIKE 'tasks'");
    $tasksExists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'task_audit'");
    $auditExists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'task_files'");
    $filesExists = $stmt->rowCount() > 0;
    
    // Log de éxito
    $logEntry = '[' . date('c') . '] OK: ' . implode(', ', $applied) . PHP_EOL;
    file_put_contents($dir . '/migrations.log', $logEntry, FILE_APPEND);
    
    $response = [
        'ok' => true,
        'applied' => $applied,
        'results' => [
            'Tabla tasks: ' . ($tasksExists ? '✓ Creada' : '✗ No existe'),
            'Tabla task_audit: ' . ($auditExists ? '✓ Creada' : '✗ No existe'),
            'Tabla task_files: ' . ($filesExists ? '✓ Creada' : '✗ No existe')
        ],
        'timestamp' => date('c')
    ];
    
    if (!empty($errors)) {
        $response['warnings'] = array_map(function($err) {
            return $err['file'] . ': ' . $err['error'];
        }, $errors);
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
    
} catch (Throwable $e) {
    // Rollback si estamos en transacción
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log de error
    $logEntry = '[' . date('c') . '] ERROR: ' . $e->getMessage() . PHP_EOL;
    file_put_contents(__DIR__ . '/migrations.log', $logEntry, FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'applied' => $applied ?? [],
        'errors' => $errors ?? [],
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
    exit;
}
