<?php
require_once __DIR__ . '/../config-local.php';

echo "=== ANÁLISE DE NORMALIZAÇÃO DAS TABELAS SOCIAL MEDIA ===\n\n";

try {
    // Conectar ao banco
    $db = $config['database'];
    $pdo = new PDO(
        "mysql:host={$db['host']};dbname={$db['dbname']};charset=utf8mb4",
        $db['username'],
        $db['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Conexão estabelecida\n\n";
    
    // Lista de tabelas de social media para analisar
    $socialTables = [
        'social_connections',
        'social_shares', 
        'social_webhooks',
        'user_social_settings'
    ];
    
    foreach ($socialTables as $tableName) {
        echo "=== ANÁLISE DA TABELA: $tableName ===\n";
        
        // Verificar se a tabela existe
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        $tableExists = $stmt->fetch();
        
        if (!$tableExists) {
            echo "✗ Tabela '$tableName' não encontrada\n\n";
            continue;
        }
        
        // Analisar estrutura da tabela
        echo "--- ESTRUTURA ATUAL ---\n";
        $stmt = $pdo->query("DESCRIBE $tableName");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            $key = $column['Key'] ? " [{$column['Key']}]" : "";
            echo "{$column['Field']}: {$column['Type']}{$key}\n";
        }
        
        // Verificar dados existentes
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM $tableName");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "\nTotal de registros: $total\n";
        
        // Verificar FKs existentes
        $stmt = $pdo->query("
            SELECT 
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = '{$db['dbname']}' 
            AND TABLE_NAME = '$tableName' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\n--- CHAVES ESTRANGEIRAS ---\n";
        if ($foreignKeys) {
            echo "Chaves estrangeiras encontradas:\n";
            foreach ($foreignKeys as $fk) {
                echo "  ✓ {$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
            }
        } else {
            echo "✗ Nenhuma chave estrangeira encontrada\n";
        }
        
        // Analisar colunas que deveriam ter FKs
        echo "\n--- ANÁLISE DE NORMALIZAÇÃO ---\n";
        $needsFKs = [];
        
        foreach ($columns as $column) {
            $fieldName = $column['Field'];
            
            // Verificar se é uma coluna que deveria ter FK
            if (preg_match('/_id$/', $fieldName) && $fieldName !== 'id') {
                // Tentar determinar a tabela de referência
                $referencedTable = str_replace('_id', '', $fieldName);
                if ($referencedTable === 'user') {
                    $referencedTable = 'usuarios';
                } elseif ($referencedTable === 'content') {
                    $referencedTable = 'noticias'; // ou outra tabela de conteúdo
                }
                
                // Verificar se já tem FK
                $hasFK = false;
                foreach ($foreignKeys as $fk) {
                    if ($fk['COLUMN_NAME'] === $fieldName) {
                        $hasFK = true;
                        break;
                    }
                }
                
                if (!$hasFK) {
                    $needsFKs[] = [
                        'column' => $fieldName,
                        'suggested_table' => $referencedTable
                    ];
                }
            }
        }
        
        if ($needsFKs) {
            echo "Colunas que precisam de FK:\n";
            foreach ($needsFKs as $fk) {
                echo "  ✗ {$fk['column']} -> {$fk['suggested_table']}.id\n";
            }
        } else {
            echo "✓ Todas as colunas _id possuem FKs apropriadas\n";
        }
        
        // Mostrar exemplos de dados se existirem
        if ($total > 0) {
            echo "\n--- EXEMPLOS DE DADOS ---\n";
            $stmt = $pdo->query("SELECT * FROM $tableName LIMIT 3");
            $examples = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($examples as $i => $row) {
                echo "Registro " . ($i + 1) . ":\n";
                foreach ($row as $field => $value) {
                    $displayValue = is_null($value) ? 'NULL' : (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value);
                    echo "  $field: $displayValue\n";
                }
                echo "\n";
            }
        }
        
        echo "\n" . str_repeat("-", 60) . "\n\n";
    }
    
    // Resumo geral
    echo "=== RESUMO GERAL DAS TABELAS SOCIAL MEDIA ===\n\n";
    
    $allRecommendations = [];
    
    foreach ($socialTables as $tableName) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        $tableExists = $stmt->fetch();
        
        if (!$tableExists) {
            continue;
        }
        
        // Verificar FKs faltantes novamente para o resumo
        $stmt = $pdo->query("DESCRIBE $tableName");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = '{$db['dbname']}' 
            AND TABLE_NAME = '$tableName' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $existingFKs = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($columns as $column) {
            $fieldName = $column['Field'];
            if (preg_match('/_id$/', $fieldName) && $fieldName !== 'id' && !in_array($fieldName, $existingFKs)) {
                $referencedTable = str_replace('_id', '', $fieldName);
                if ($referencedTable === 'user') {
                    $referencedTable = 'usuarios';
                } elseif ($referencedTable === 'content') {
                    $referencedTable = 'noticias';
                }
                
                $allRecommendations[] = "ALTER TABLE $tableName ADD FOREIGN KEY ($fieldName) REFERENCES $referencedTable(id);";
            }
        }
    }
    
    if ($allRecommendations) {
        echo "COMANDOS SQL RECOMENDADOS:\n";
        foreach ($allRecommendations as $sql) {
            echo "$sql\n";
        }
    } else {
        echo "✓ Todas as tabelas de social media estão adequadamente normalizadas!\n";
    }
    
    echo "\n=== ANÁLISE CONCLUÍDA ===\n";
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
}
?>