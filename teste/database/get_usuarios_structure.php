<?php
/**
 * Script para obter estrutura da tabela usuarios
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obter estrutura da tabela
    $stmt = $db->prepare('DESCRIBE usuarios');
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== ESTRUTURA DA TABELA USUARIOS ===\n\n";
    
    foreach ($columns as $col) {
        echo "Campo: {$col['Field']}\n";
        echo "Tipo: {$col['Type']}\n";
        echo "Permite NULL: {$col['Null']}\n";
        echo "Padrão: " . ($col['Default'] ?? 'NULL') . "\n";
        echo "Extra: {$col['Extra']}\n";
        echo "---\n";
    }
    
    // Obter comentários das colunas
    echo "\n=== COMENTÁRIOS DAS COLUNAS ===\n\n";
    
    $comment_query = "SELECT COLUMN_NAME, COLUMN_COMMENT 
                     FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'usuarios' 
                     AND COLUMN_COMMENT != ''
                     ORDER BY ORDINAL_POSITION";
    
    $stmt = $db->prepare($comment_query);
    $stmt->execute();
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($comments as $comment) {
        echo "{$comment['COLUMN_NAME']}: {$comment['COLUMN_COMMENT']}\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>