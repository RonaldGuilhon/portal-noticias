<?php
require_once __DIR__ . '/../backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar estrutura da tabela usuarios
    $stmt = $db->prepare("DESCRIBE usuarios");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Estrutura da tabela 'usuarios':\n";
    echo "=" . str_repeat("=", 50) . "\n";
    
    foreach ($columns as $column) {
        echo sprintf("%-25s | %-15s | %-8s | %-8s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'], 
            $column['Default'] ?? 'NULL'
        );
    }
    
    echo "\n📊 Total de colunas: " . count($columns) . "\n";
    
    // Verificar se existem usuários na tabela
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM usuarios");
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "👥 Total de usuários na tabela: {$total['total']}\n";
    
    // Listar alguns usuários existentes
    if ($total['total'] > 0) {
        $stmt = $db->prepare("SELECT id, nome, email, ativo FROM usuarios LIMIT 5");
        $stmt->execute();
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\n👤 Usuários existentes (primeiros 5):\n";
        foreach ($usuarios as $user) {
            echo "ID: {$user['id']} | Nome: {$user['nome']} | Email: {$user['email']} | Ativo: {$user['ativo']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>