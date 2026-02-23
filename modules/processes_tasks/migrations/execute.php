<?php
/**
 * Ejecutor de Migraciones - Módulo Processes & Tasks
 * Acceso: /modules/processes_tasks/migrations/execute.php
 */

// Deshabilitar límite de tiempo
set_time_limit(300);

require_once __DIR__ . '/../../../bootstrap.php';

// Solo permitir en desarrollo o con autenticación
if (!isset($_SESSION['user_id']) && APP_ENV !== 'development') {
    http_response_code(403);
    die('Acceso denegado');
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejecutar Migraciones - Processes & Tasks</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .button-group {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        button {
            flex: 1;
            padding: 15px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        .output {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            display: none;
        }
        .output.visible {
            display: block;
        }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
        .warning { color: #ffc107; }
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
            display: none;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .file-list {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .file-item {
            padding: 8px;
            border-bottom: 1px solid #f0f0f0;
        }
        .file-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Migraciones - Processes & Tasks</h1>
        <p class="subtitle">Sistema de gestión de tareas, procesos y proyectos</p>
        
        <div class="file-list">
            <strong>📄 Archivos de migración detectados:</strong>
            <?php
            $files = glob(__DIR__ . '/*create*fixed*.sql');
            if (empty($files)) {
                $files = glob(__DIR__ . '/*create*.sql');
            }
            if (empty($files)) {
                echo '<p style="color: #dc3545; margin-top: 10px;">⚠️ No se encontraron archivos SQL</p>';
            } else {
                echo '<div style="margin-top: 10px;">';
                foreach ($files as $file) {
                    $basename = basename($file);
                    $icon = strpos($basename, 'fixed') !== false ? '✅' : '📄';
                    echo '<div class="file-item">' . $icon . ' ' . $basename . '</div>';
                }
                echo '</div>';
            }
            ?>
        </div>
        
        <div class="button-group">
            <button class="btn-primary" onclick="runMigrations()">
                ▶️ Ejecutar Migraciones
            </button>
            <button class="btn-success" onclick="runMigrations(true)">
                🌱 Ejecutar con Seeds
            </button>
            <button class="btn-secondary" onclick="clearOutput()">
                🗑️ Limpiar Salida
            </button>
        </div>
        
        <div class="spinner" id="spinner"></div>
        <div class="output" id="output"></div>
    </div>

    <script>
        function runMigrations(withSeed = false) {
            const output = document.getElementById('output');
            const spinner = document.getElementById('spinner');
            
            output.classList.add('visible');
            output.innerHTML = '<div class="info">⏳ Ejecutando migraciones...</div>';
            spinner.style.display = 'block';
            
            const url = 'run_migrations.php?run=1' + (withSeed ? '&seed=1' : '');
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    spinner.style.display = 'none';
                    
                    if (data.ok) {
                        let html = '<div class="success">✅ Migraciones ejecutadas correctamente</div><br>';
                        
                        if (data.results && data.results.length > 0) {
                            html += '<strong>Resultados:</strong><br>';
                            data.results.forEach(result => {
                                html += `<div class="info">• ${result}</div>`;
                            });
                        }
                        
                        if (data.affected > 0) {
                            html += `<br><div class="success">📊 Filas afectadas: ${data.affected}</div>`;
                        }
                        
                        if (data.warnings && data.warnings.length > 0) {
                            html += '<br><strong class="warning">⚠️ Advertencias:</strong><br>';
                            data.warnings.forEach(warn => {
                                html += `<div class="warning">• ${warn}</div>`;
                            });
                        }
                        
                        output.innerHTML = html;
                    } else {
                        output.innerHTML = `<div class="error">❌ Error: ${data.error || 'Error desconocido'}</div>`;
                        if (data.details) {
                            output.innerHTML += `<br><div style="color: #666;">${data.details}</div>`;
                        }
                    }
                })
                .catch(error => {
                    spinner.style.display = 'none';
                    output.innerHTML = `<div class="error">❌ Error de red: ${error.message}</div>`;
                });
        }
        
        function clearOutput() {
            const output = document.getElementById('output');
            output.innerHTML = '';
            output.classList.remove('visible');
        }
    </script>
</body>
</html>
