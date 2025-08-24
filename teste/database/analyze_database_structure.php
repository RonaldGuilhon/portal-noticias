<?php
require_once __DIR__ . '/../config-local.php';

echo "=== ANÁLISE DA ESTRUTURA DO BANCO DE DADOS ===\n\n";

try {
    $db = $config['database'];
    $pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset=utf8", $db['username'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Conexão estabelecida com o banco '{$db['dbname']}'\n\n";
    
    // Listar todas as tabelas
    echo "=== TABELAS EXISTENTES ===\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    echo "\nTotal de tabelas: " . count($tables) . "\n\n";
    
    // Analisar estrutura de cada tabela
    echo "=== ESTRUTURA DAS TABELAS ===\n\n";
    
    foreach ($tables as $table) {
        echo "--- TABELA: $table ---\n";
        
        // Obter colunas
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Colunas:\n";
        foreach ($columns as $column) {
            $key = $column['Key'] ? " [{$column['Key']}]" : '';
            echo "  - {$column['Field']}: {$column['Type']}{$key}\n";
        }
        
        // Verificar chaves estrangeiras
        $stmt = $pdo->query("
            SELECT 
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = '{$db['dbname']}' 
            AND TABLE_NAME = '$table' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($foreignKeys) {
            echo "Chaves Estrangeiras:\n";
            foreach ($foreignKeys as $fk) {
                echo "  - {$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
            }
        } else {
            echo "Chaves Estrangeiras: Nenhuma\n";
        }
        
        // Contar registros
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM $table");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "Registros: $count\n";
        
        echo "\n";
    }
    
    // Identificar possíveis problemas de normalização
    echo "=== ANÁLISE DE NORMALIZAÇÃO ===\n\n";
    
    // Verificar tabelas sem chaves estrangeiras (possível desnormalização)
    echo "Tabelas sem relacionamentos (chaves estrangeiras):\n";
    foreach ($tables as $table) {
        $stmt = $pdo->query("
            SELECT COUNT(*) as fk_count
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = '{$db['dbname']}' 
            AND TABLE_NAME = '$table' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $fkCount = $stmt->fetch(PDO::FETCH_ASSOC)['fk_count'];
        
        if ($fkCount == 0 && $table != 'usuarios') { // usuarios é tabela principal
            echo "  - $table (pode precisar de relacionamentos)\n";
        }
    }
    
    // Verificar colunas que poderiam ser normalizadas
    echo "\nColunas que podem indicar desnormalização:\n";
    foreach ($tables as $table) {
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            $field = $column['Field'];
            $type = $column['Type'];
            
            // Verificar campos que terminam com _id mas não são FK
            if (preg_match('/_id$/', $field) && $column['Key'] != 'PRI') {
                $stmt2 = $pdo->query("
                    SELECT COUNT(*) as is_fk
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = '{$db['dbname']}' 
                    AND TABLE_NAME = '$table' 
                    AND COLUMN_NAME = '$field'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                $isFk = $stmt2->fetch(PDO::FETCH_ASSOC)['is_fk'];
                
                if ($isFk == 0) {
                    echo "  - $table.$field (campo _id sem FK)\n";
                }
            }
            
            // Verificar campos TEXT/VARCHAR longos que podem conter dados estruturados
            if (preg_match('/text|varchar\(255\)|varchar\(500\)/', strtolower($type))) {
                if (in_array($field, ['preferencias', 'configuracoes', 'metadados', 'dados_extras'])) {
                    echo "  - $table.$field (possível JSON que pode ser normalizado)\n";
                }
            }
        }
    }
    
    echo "\n=== RECOMENDAÇÕES ===\n";
    echo "1. Verificar se tabelas independentes podem ser relacionadas\n";
    echo "2. Normalizar campos JSON em tabelas separadas se necessário\n";
    echo "3. Adicionar chaves estrangeiras onde apropriado\n";
    echo "4. Considerar índices para melhor performance\n";
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== ANÁLISE CONCLUÍDA ===\n";
?>