<?php
/**
 * Script de Verificación Post-Unificación
 * Valida que todos los cambios funcionan correctamente
 * 
 * USO: Acceder desde navegador a:
 * https://tusitio.com/modules/processes_tasks/migrations/verify_unification.php
 * 
 * @author Sistema Índice ERP
 * @date 2025-11-22
 */

require __DIR__.'/../../../bootstrap.php';
require __DIR__.'/../../../core/auth.php';
require __DIR__.'/../../../core/permissions.php';
require __DIR__.'/../includes/enum_normalizer.php';

// Solo para administradores
requireLogin();
$ucId = currentUserCompany();
$userId = currentUserId();

if (!hasPermission($ucId, 'processes_tasks', 'view')) {
    http_response_code(403);
    die('⛔ Acceso denegado. Necesitas permiso "view" en processes_tasks');
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Unificación - Processes & Tasks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .test-pass { background: #d1f2eb; border-left: 4px solid #28a745; }
        .test-fail { background: #f8d7da; border-left: 4px solid #dc3545; }
        .test-warn { background: #fff3cd; border-left: 4px solid #ffc107; }
        .test-info { background: #d1ecf1; border-left: 4px solid #17a2b8; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-3">
                    <i class="bi bi-check2-circle text-success"></i>
                    Verificación de Unificación
                </h1>
                <p class="lead">Comprobando que la tabla <code>tasks</code> está funcionando correctamente</p>
                <hr>
            </div>
        </div>

        <?php
        $pdo = db();
        $tests = [];
        $passCount = 0;
        $failCount = 0;
        $warnCount = 0;

        // TEST 1: Verificar que existe la tabla tasks
        try {
            $stmt = $pdo->query('SELECT COUNT(*) as cnt FROM tasks LIMIT 1');
            $tests[] = [
                'name' => '1. Tabla tasks existe',
                'status' => 'pass',
                'message' => 'La tabla tasks está disponible en la base de datos'
            ];
            $passCount++;
        } catch (Exception $e) {
            $tests[] = [
                'name' => '1. Tabla tasks existe',
                'status' => 'fail',
                'message' => 'ERROR: ' . $e->getMessage()
            ];
            $failCount++;
        }

        // TEST 2: Contar registros en tasks
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM tasks WHERE company_id = ?');
            $stmt->execute([$ucId]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            if ($count > 0) {
                $tests[] = [
                    'name' => '2. Registros en tasks',
                    'status' => 'pass',
                    'message' => "Hay {$count} tareas en la tabla tasks para tu empresa"
                ];
                $passCount++;
            } else {
                $tests[] = [
                    'name' => '2. Registros en tasks',
                    'status' => 'warn',
                    'message' => 'No hay tareas todavía. Crea algunas para probar.'
                ];
                $warnCount++;
            }
        } catch (Exception $e) {
            $tests[] = [
                'name' => '2. Registros en tasks',
                'status' => 'fail',
                'message' => 'ERROR: ' . $e->getMessage()
            ];
            $failCount++;
        }

        // TEST 3: Verificar estructura de campos
        try {
            $stmt = $pdo->query('SHOW COLUMNS FROM tasks');
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $columnNames = array_column($columns, 'Field');
            
            $requiredFields = ['id', 'company_id', 'titulo', 'descripcion', 'usuario_creador', 'usuario_delegado', 'nivel', 'status', 'tipo'];
            $missing = array_diff($requiredFields, $columnNames);
            
            if (empty($missing)) {
                $tests[] = [
                    'name' => '3. Estructura de campos',
                    'status' => 'pass',
                    'message' => 'Todos los campos requeridos están presentes: ' . implode(', ', $requiredFields)
                ];
                $passCount++;
            } else {
                $tests[] = [
                    'name' => '3. Estructura de campos',
                    'status' => 'fail',
                    'message' => 'Faltan campos: ' . implode(', ', $missing)
                ];
                $failCount++;
            }
        } catch (Exception $e) {
            $tests[] = [
                'name' => '3. Estructura de campos',
                'status' => 'fail',
                'message' => 'ERROR: ' . $e->getMessage()
            ];
            $failCount++;
        }

        // TEST 4: Verificar helper de normalización
        try {
            $testValues = [
                ['input' => 'baja', 'expected' => 'Normal', 'function' => 'normalizePriority'],
                ['input' => 'media', 'expected' => 'Normal', 'function' => 'normalizePriority'],
                ['input' => 'alta', 'expected' => 'Importante', 'function' => 'normalizePriority'],
                ['input' => 'urgente', 'expected' => 'Urgente', 'function' => 'normalizePriority'],
                ['input' => 'pendiente', 'expected' => 'En tiempo', 'function' => 'normalizeStatus'],
                ['input' => 'en_proceso', 'expected' => 'En proceso', 'function' => 'normalizeStatus'],
                ['input' => 'completada', 'expected' => 'Terminada', 'function' => 'normalizeStatus'],
            ];
            
            $allPassed = true;
            $results = [];
            
            foreach ($testValues as $test) {
                $result = call_user_func($test['function'], $test['input']);
                $passed = $result === $test['expected'];
                $allPassed = $allPassed && $passed;
                $results[] = "{$test['input']} → {$result} " . ($passed ? '✓' : '✗ (esperado: '.$test['expected'].')');
            }
            
            if ($allPassed) {
                $tests[] = [
                    'name' => '4. Helper de normalización ENUM',
                    'status' => 'pass',
                    'message' => 'Todas las conversiones funcionan correctamente: <br><small>' . implode('<br>', $results) . '</small>'
                ];
                $passCount++;
            } else {
                $tests[] = [
                    'name' => '4. Helper de normalización ENUM',
                    'status' => 'fail',
                    'message' => 'Algunas conversiones fallan: <br><small>' . implode('<br>', $results) . '</small>'
                ];
                $failCount++;
            }
        } catch (Exception $e) {
            $tests[] = [
                'name' => '4. Helper de normalización ENUM',
                'status' => 'fail',
                'message' => 'ERROR: ' . $e->getMessage()
            ];
            $failCount++;
        }

        // TEST 5: Probar API de listado
        try {
            $apiUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . '/modules/processes_tasks/api/list.php';
            
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Cookie: ' . $_SERVER['HTTP_COOKIE']]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if ($data && isset($data['ok']) && $data['ok']) {
                    $tests[] = [
                        'name' => '5. API de listado (/api/list.php)',
                        'status' => 'pass',
                        'message' => 'API funciona correctamente. Retorna ' . count($data['items'] ?? []) . ' tareas'
                    ];
                    $passCount++;
                } else {
                    $tests[] = [
                        'name' => '5. API de listado (/api/list.php)',
                        'status' => 'warn',
                        'message' => 'API responde pero con error: ' . ($data['error'] ?? 'desconocido')
                    ];
                    $warnCount++;
                }
            } else {
                $tests[] = [
                    'name' => '5. API de listado (/api/list.php)',
                    'status' => 'fail',
                    'message' => "HTTP {$httpCode}: No se pudo conectar al API"
                ];
                $failCount++;
            }
        } catch (Exception $e) {
            $tests[] = [
                'name' => '5. API de listado (/api/list.php)',
                'status' => 'fail',
                'message' => 'ERROR: ' . $e->getMessage()
            ];
            $failCount++;
        }

        // TEST 6: Probar API de dashboard
        try {
            $apiUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . '/modules/processes_tasks/api/dashboard.php';
            
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Cookie: ' . $_SERVER['HTTP_COOKIE']]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if ($data && isset($data['ok']) && $data['ok']) {
                    $stats = $data['stats'] ?? [];
                    $tests[] = [
                        'name' => '6. API de dashboard (/api/dashboard.php)',
                        'status' => 'pass',
                        'message' => 'Dashboard funciona. Stats: ' . ($stats['total'] ?? 0) . ' total, ' . ($stats['pending'] ?? 0) . ' pendientes'
                    ];
                    $passCount++;
                } else {
                    $tests[] = [
                        'name' => '6. API de dashboard (/api/dashboard.php)',
                        'status' => 'warn',
                        'message' => 'API responde: ' . ($data['message'] ?? 'Sin tareas aún')
                    ];
                    $warnCount++;
                }
            } else {
                $tests[] = [
                    'name' => '6. API de dashboard (/api/dashboard.php)',
                    'status' => 'fail',
                    'message' => "HTTP {$httpCode}: No se pudo conectar al API"
                ];
                $failCount++;
            }
        } catch (Exception $e) {
            $tests[] = [
                'name' => '6. API de dashboard (/api/dashboard.php)',
                'status' => 'fail',
                'message' => 'ERROR: ' . $e->getMessage()
            ];
            $failCount++;
        }

        // TEST 7: Verificar relaciones con users y hr_employees
        try {
            $stmt = $pdo->prepare('
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN u.id IS NOT NULL THEN 1 ELSE 0 END) as creadores_validos,
                    SUM(CASE WHEN ud.id IS NOT NULL THEN 1 ELSE 0 END) as delegados_validos
                FROM tasks t
                LEFT JOIN users u ON u.id = t.usuario_creador
                LEFT JOIN users ud ON ud.id = t.usuario_delegado
                WHERE t.company_id = ?
            ');
            $stmt->execute([$ucId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['total'] > 0) {
                $creadorPercent = round(($result['creadores_validos'] / $result['total']) * 100);
                $delegadoPercent = round(($result['delegados_validos'] / $result['total']) * 100);
                
                if ($creadorPercent >= 80 && $delegadoPercent >= 80) {
                    $tests[] = [
                        'name' => '7. Relaciones con usuarios',
                        'status' => 'pass',
                        'message' => "Creadores válidos: {$creadorPercent}%, Delegados válidos: {$delegadoPercent}%"
                    ];
                    $passCount++;
                } else {
                    $tests[] = [
                        'name' => '7. Relaciones con usuarios',
                        'status' => 'warn',
                        'message' => "Algunos usuarios no vinculados. Creadores: {$creadorPercent}%, Delegados: {$delegadoPercent}%"
                    ];
                    $warnCount++;
                }
            } else {
                $tests[] = [
                    'name' => '7. Relaciones con usuarios',
                    'status' => 'info',
                    'message' => 'No hay tareas para verificar relaciones'
                ];
            }
        } catch (Exception $e) {
            $tests[] = [
                'name' => '7. Relaciones con usuarios',
                'status' => 'fail',
                'message' => 'ERROR: ' . $e->getMessage()
            ];
            $failCount++;
        }

        // Mostrar resultados
        $totalTests = count($tests);
        $successRate = $totalTests > 0 ? round(($passCount / $totalTests) * 100) : 0;
        ?>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-success"><?= $passCount ?></h3>
                        <small>Tests Exitosos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-danger"><?= $failCount ?></h3>
                        <small>Tests Fallidos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-warning"><?= $warnCount ?></h3>
                        <small>Advertencias</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3><?= $successRate ?>%</h3>
                        <small>Tasa de Éxito</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <h3>Resultados Detallados</h3>
                <?php foreach ($tests as $test): ?>
                    <div class="card mb-2 test-<?= $test['status'] ?>">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php
                                $icons = [
                                    'pass' => 'bi-check-circle text-success',
                                    'fail' => 'bi-x-circle text-danger',
                                    'warn' => 'bi-exclamation-triangle text-warning',
                                    'info' => 'bi-info-circle text-info'
                                ];
                                ?>
                                <i class="bi <?= $icons[$test['status']] ?>"></i>
                                <?= htmlspecialchars($test['name']) ?>
                            </h5>
                            <p class="card-text mb-0"><?= $test['message'] ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h5><i class="bi bi-info-circle"></i> Próximos Pasos</h5>
                    <ol class="mb-0">
                        <li>Si todos los tests pasan, puedes empezar a usar el módulo</li>
                        <li>Si hay advertencias, revisa los datos de tu company</li>
                        <li>Si hay errores, contacta al equipo de desarrollo</li>
                        <li>Ejecuta la migración de pt_tasks a tasks si aún no lo hiciste</li>
                    </ol>
                </div>
                <a href="/modules/processes_tasks/" class="btn btn-primary">
                    <i class="bi bi-arrow-left"></i> Volver al Módulo
                </a>
            </div>
        </div>
    </div>
</body>
</html>
