<?php
/**
 * Script para corrigir problemas nas colunas preferencias e favorite_categories
 * Portal de Notícias
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Correção das Colunas 'preferencias' e 'favorite_categories' ===\n\n";
    
    // 1. Adicionar comentário à coluna preferencias
    echo "📝 Adicionando comentário à coluna 'preferencias'...\n";
    $comment_query = "ALTER TABLE usuarios MODIFY COLUMN preferencias LONGTEXT 
                     COMMENT 'Preferências gerais do usuário em formato JSON'";
    
    if ($db->exec($comment_query) !== false) {
        echo "✅ Comentário adicionado com sucesso!\n";
    } else {
        echo "❌ Erro ao adicionar comentário\n";
        $errorInfo = $db->errorInfo();
        echo "Erro: " . $errorInfo[2] . "\n";
    }
    
    // 2. Verificar e corrigir dados inconsistentes
    echo "\n🔍 Verificando dados inconsistentes...\n";
    
    // Buscar registros com dados problemáticos
    $check_query = "SELECT id, nome, preferencias, favorite_categories 
                   FROM usuarios 
                   WHERE preferencias IN ('\"[]\"', '[]', '') 
                      OR favorite_categories IN ('\"[]\"', '[]', '')";
    
    $stmt = $db->prepare($check_query);
    $stmt->execute();
    $problematic_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 Encontrados " . count($problematic_records) . " registros com dados inconsistentes\n";
    
    if (!empty($problematic_records)) {
        foreach ($problematic_records as $record) {
            echo "   - Usuário ID {$record['id']} ({$record['nome']})\n";
            echo "     Preferencias: " . ($record['preferencias'] ?? 'NULL') . "\n";
            echo "     Favorite Categories: " . ($record['favorite_categories'] ?? 'NULL') . "\n";
        }
    }
    
    // 3. Limpar dados inconsistentes
    echo "\n🧹 Limpando dados inconsistentes...\n";
    
    // Corrigir preferencias vazias ou malformadas
    $fix_preferencias = "UPDATE usuarios 
                        SET preferencias = NULL 
                        WHERE preferencias IN ('\"[]\"', '[]', '')";
    
    $affected_pref = $db->exec($fix_preferencias);
    echo "✅ Corrigidos {$affected_pref} registros na coluna 'preferencias'\n";
    
    // Corrigir favorite_categories vazias ou malformadas
    $fix_categories = "UPDATE usuarios 
                      SET favorite_categories = NULL 
                      WHERE favorite_categories IN ('\"[]\"', '[]', '')";
    
    $affected_cat = $db->exec($fix_categories);
    echo "✅ Corrigidos {$affected_cat} registros na coluna 'favorite_categories'\n";
    
    // 4. Validar JSON existente
    echo "\n🔍 Validando JSON existente...\n";
    
    $validate_query = "SELECT id, nome, preferencias, favorite_categories 
                      FROM usuarios 
                      WHERE (preferencias IS NOT NULL AND preferencias != '') 
                         OR (favorite_categories IS NOT NULL AND favorite_categories != '')";
    
    $stmt = $db->prepare($validate_query);
    $stmt->execute();
    $records_with_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $invalid_json_count = 0;
    
    foreach ($records_with_data as $record) {
        $has_invalid = false;
        
        // Validar preferencias
        if (!empty($record['preferencias'])) {
            $decoded_pref = json_decode($record['preferencias']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo "❌ JSON inválido em preferencias - Usuário ID {$record['id']}\n";
                $has_invalid = true;
            }
        }
        
        // Validar favorite_categories
        if (!empty($record['favorite_categories'])) {
            $decoded_cat = json_decode($record['favorite_categories']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo "❌ JSON inválido em favorite_categories - Usuário ID {$record['id']}\n";
                $has_invalid = true;
            }
        }
        
        if ($has_invalid) {
            $invalid_json_count++;
        }
    }
    
    if ($invalid_json_count === 0) {
        echo "✅ Todos os JSONs existentes são válidos!\n";
    } else {
        echo "⚠️  Encontrados {$invalid_json_count} registros com JSON inválido\n";
    }
    
    // 5. Mostrar estrutura final
    echo "\n📋 Estrutura final das colunas:\n";
    
    $structure_query = "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT 
                       FROM INFORMATION_SCHEMA.COLUMNS 
                       WHERE TABLE_SCHEMA = DATABASE() 
                       AND TABLE_NAME = 'usuarios' 
                       AND (COLUMN_NAME = 'preferencias' OR COLUMN_NAME = 'favorite_categories')
                       ORDER BY COLUMN_NAME";
    
    $stmt = $db->prepare($structure_query);
    $stmt->execute();
    $final_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($final_structure as $col) {
        echo "📋 {$col['COLUMN_NAME']}:\n";
        echo "   Tipo: {$col['DATA_TYPE']}\n";
        echo "   NULL: {$col['IS_NULLABLE']}\n";
        echo "   Padrão: " . ($col['COLUMN_DEFAULT'] ?? 'NULL') . "\n";
        echo "   Comentário: " . ($col['COLUMN_COMMENT'] ?? 'Sem comentário') . "\n";
        echo "\n";
    }
    
    // 6. Estatísticas finais
    echo "📊 Estatísticas finais:\n";
    
    $stats_query = "SELECT 
                       COUNT(*) as total_users,
                       COUNT(CASE WHEN preferencias IS NOT NULL AND preferencias != '' THEN 1 END) as users_with_preferencias,
                       COUNT(CASE WHEN favorite_categories IS NOT NULL AND favorite_categories != '' THEN 1 END) as users_with_categories
                    FROM usuarios";
    
    $stmt = $db->prepare($stats_query);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   Total de usuários: {$stats['total_users']}\n";
    echo "   Com preferencias: {$stats['users_with_preferencias']}\n";
    echo "   Com categorias favoritas: {$stats['users_with_categories']}\n";
    
    echo "\n🎯 Próximos passos recomendados:\n";
    echo "   1. Implementar validação JSON no código PHP\n";
    echo "   2. Criar funções helper para manipulação de preferências\n";
    echo "   3. Padronizar formato de dados no frontend\n";
    echo "   4. Adicionar testes unitários para validação JSON\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n✅ Correção das colunas concluída!\n";
?>