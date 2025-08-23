<?php
require_once 'config-local.php';

try {
    $pdo = new PDO("mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8mb4", 
        $config['database']['username'], $config['database']['password'], $config['database']['options']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== ADICIONANDO COLUNA 'ESTADO' NA TABELA USUARIOS ===\n\n";
    
    // Verificar se a coluna já existe
    $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'estado'");
    $column_exists = $stmt->fetch();
    
    if ($column_exists) {
        echo "✓ Coluna 'estado' já existe na tabela usuarios\n";
    } else {
        echo "Adicionando coluna 'estado'...\n";
        
        // Adicionar a coluna estado após a coluna cidade
        $sql = "ALTER TABLE usuarios ADD COLUMN estado VARCHAR(100) NULL AFTER cidade";
        $pdo->exec($sql);
        
        echo "✓ Coluna 'estado' adicionada com sucesso!\n";
    }
    
    echo "\n=== VERIFICANDO ESTRUTURA ATUALIZADA ===\n";
    
    // Verificar a estrutura atualizada
    $stmt = $pdo->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nColunas relacionadas a localização:\n";
    echo str_repeat("-", 60) . "\n";
    
    foreach ($columns as $column) {
        if (in_array($column['Field'], ['cidade', 'estado', 'telefone'])) {
            printf("%-15s %-15s %-10s %-15s\n", 
                $column['Field'], 
                $column['Type'], 
                $column['Null'], 
                $column['Default'] ?? 'NULL'
            );
        }
    }
    
    echo str_repeat("-", 60) . "\n";
    echo "\n✓ Operação concluída com sucesso!\n";
    
} catch(Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>