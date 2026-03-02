<?php
/**
 * Script de diagnóstico - Verificar estructura de tablas
 * para corregir Foreign Keys en módulo Processes & Tasks
 */

require_once __DIR__ . '/../../../bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = db();
    
    echo "=== DIAGNÓSTICO DE TABLAS PARA FOREIGN KEYS ===\n\n";
    
    $tables = ['companies', 'businesses', 'units', 'users'];
    
    foreach ($tables as $table) {
        echo "📋 Tabla: $table\n";
        echo str_repeat('-', 60) . "\n";
        
        // Verificar si existe
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            echo "❌ NO EXISTE\n\n";
            continue;
        }
        
        // Ver ENGINE
        $stmt = $pdo->query("SHOW TABLE STATUS LIKE '$table'");
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Engine: {$status['Engine']}\n";
        echo "Collation: {$status['Collation']}\n\n";
        
        // Ver estructura de columna 'id'
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $col) {
            if ($col['Field'] === 'id') {
                echo "Columna ID:\n";
                echo "  Type: {$col['Type']}\n";
                echo "  Null: {$col['Null']}\n";
                echo "  Key: {$col['Key']}\n";
                echo "  Default: {$col['Default']}\n";
                echo "  Extra: {$col['Extra']}\n";
            }
        }
        
        // Ver índices
        $stmt = $pdo->query("SHOW INDEX FROM $table WHERE Key_name = 'PRIMARY'");
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($indexes)) {
            echo "\nÍndice PRIMARY:\n";
            foreach ($indexes as $idx) {
                echo "  Column: {$idx['Column_name']}\n";
            }
        }
        
        echo "\n";
    }
    
    echo "\n=== RECOMENDACIONES ===\n\n";
    echo "Para crear la tabla tasks, usa estos tipos de columna:\n\n";
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            echo "-- {$table}: NO EXISTE (comentar FK)\n";
            continue;
        }
        
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $col) {
            if ($col['Field'] === 'id') {
                $nullable = ($col['Null'] === 'YES') ? 'NULL' : 'NOT NULL';
                echo "-- {$table}.id es: {$col['Type']} {$nullable}\n";
                
                // Generar tipo compatible
                $type = strtoupper($col['Type']);
                if (strpos($type, 'INT') !== false) {
                    // Usar el mismo tipo
                    $baseType = $col['Type'];
                } else {
                    $baseType = 'INT(11)';
                }
                
                $fkColumn = substr($table, 0, -1) === $table ? $table : rtrim($table, 's');
                if ($table === 'businesses') $fkColumn = 'business';
                if ($table === 'companies') $fkColumn = 'company';
                if ($table === 'units') $fkColumn = 'unit';
                if ($table === 'users') $fkColumn = 'usuario';
                
                echo "   -> {$fkColumn}_id {$baseType} NULL (puede ser NULL para tareas sin asignar)\n\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
