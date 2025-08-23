<?php
/**
 * Script para verificar colunas específicas: preferencias e favorite_categories
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Verificando colunas 'preferencias' e 'favorite_categories' ===\n\n";
    
    // Verificar estrutura das colunas específicas
    $query = "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT 
              FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = 'usuarios' 
              AND (COLUMN_NAME = 'preferencias' OR COLUMN_NAME = 'favorite_categories')
              ORDER BY COLUMN_NAME";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($columns)) {
        echo "❌ Nenhuma das colunas 'preferencias' ou 'favorite_categories' foi encontrada!\n";
    } else {
        foreach ($columns as $col) {
            echo "📋 Coluna: {$col['COLUMN_NAME']}\n";
            echo "   Tipo: {$col['DATA_TYPE']}\n";
            echo "   Permite NULL: {$col['IS_NULLABLE']}\n";
            echo "   Valor Padrão: " . ($col['COLUMN_DEFAULT'] ?? 'NULL') . "\n";
            echo "   Comentário: " . ($col['COLUMN_COMMENT'] ?? 'Sem comentário') . "\n";
            echo "   ---\n";
        }
    }
    
    // Verificar se existem dados nessas colunas
    echo "\n=== Verificando dados existentes ===\n";
    
    $data_query = "SELECT COUNT(*) as total_users,
                          COUNT(CASE WHEN preferencias IS NOT NULL AND preferencias != '' THEN 1 END) as users_with_preferencias,
                          COUNT(CASE WHEN favorite_categories IS NOT NULL AND favorite_categories != '' THEN 1 END) as users_with_favorite_categories
                   FROM usuarios";
    
    $stmt = $db->prepare($data_query);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "👥 Total de usuários: {$data['total_users']}\n";
    echo "📝 Usuários com preferencias preenchidas: {$data['users_with_preferencias']}\n";
    echo "⭐ Usuários com favorite_categories preenchidas: {$data['users_with_favorite_categories']}\n";
    
    // Mostrar exemplos de dados (se existirem)
    echo "\n=== Exemplos de dados ===\n";
    
    $sample_query = "SELECT id, nome, preferencias, favorite_categories 
                     FROM usuarios 
                     WHERE (preferencias IS NOT NULL AND preferencias != '') 
                        OR (favorite_categories IS NOT NULL AND favorite_categories != '')
                     LIMIT 3";
    
    $stmt = $db->prepare($sample_query);
    $stmt->execute();
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($samples)) {
        echo "ℹ️  Nenhum usuário com dados nessas colunas encontrado.\n";
    } else {
        foreach ($samples as $sample) {
            echo "🔍 Usuário ID {$sample['id']} ({$sample['nome']}):\n";
            echo "   Preferencias: " . ($sample['preferencias'] ?? 'NULL') . "\n";
            echo "   Favorite Categories: " . ($sample['favorite_categories'] ?? 'NULL') . "\n";
            echo "   ---\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n✅ Verificação concluída!\n";
?>