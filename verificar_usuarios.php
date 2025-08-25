<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== USUÁRIOS NO BANCO DE DADOS ===\n";
    
    $stmt = $pdo->query('SELECT id, nome, email FROM usuarios ORDER BY id LIMIT 10');
    while($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Nome: {$row['nome']}, Email: {$row['email']}\n";
    }
    
    echo "\n=== TOTAL DE USUÁRIOS ===\n";
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM usuarios');
    $total = $stmt->fetch();
    echo "Total: {$total['total']} usuários\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>