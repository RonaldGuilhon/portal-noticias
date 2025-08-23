<?php
require_once 'config-local.php';

try {
    $pdo = new PDO("mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8mb4", 
        $config['database']['username'], $config['database']['password'], $config['database']['options']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== ESTRUTURA DA TABELA USUARIOS ===\n\n";
    
    // Verificar estrutura da tabela
    $stmt = $pdo->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Colunas encontradas na tabela 'usuarios':\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-20s %-15s %-10s %-10s %-10s %-15s\n", "CAMPO", "TIPO", "NULL", "CHAVE", "PADRÃO", "EXTRA");
    echo str_repeat("-", 80) . "\n";
    
    $estado_exists = false;
    foreach ($columns as $column) {
        printf("%-20s %-15s %-10s %-10s %-10s %-15s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'], 
            $column['Key'], 
            $column['Default'] ?? 'NULL', 
            $column['Extra']
        );
        
        if ($column['Field'] === 'estado') {
            $estado_exists = true;
        }
    }
    
    echo str_repeat("-", 80) . "\n";
    echo "\n=== VERIFICAÇÃO DO CAMPO 'ESTADO' ===\n";
    
    if ($estado_exists) {
        echo "✓ Campo 'estado' JÁ EXISTE na tabela usuarios\n";
    } else {
        echo "✗ Campo 'estado' NÃO EXISTE na tabela usuarios\n";
        echo "\nSQL para criar o campo:\n";
        echo "ALTER TABLE usuarios ADD COLUMN estado VARCHAR(100) NULL AFTER cidade;\n";
    }
    
} catch(Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>