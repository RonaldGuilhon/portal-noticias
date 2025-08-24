<?php
require_once 'config-local.php';
require_once 'backend/config/config.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "=== USUÁRIOS NO BANCO DE DADOS ===\n\n";
    
    $stmt = $conn->prepare('SELECT id, nome, email FROM usuarios LIMIT 5');
    $stmt->execute();
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']}, Nome: {$row['nome']}, Email: {$row['email']}\n";
    }
    
} catch(Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>