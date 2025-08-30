<?php
require_once 'backend/config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Verificar total de usuários
    $stmt = $conn->query('SELECT COUNT(*) as total FROM usuarios');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total de usuários: " . $result['total'] . PHP_EOL;
    
    // Listar alguns usuários
    $stmt = $conn->query('SELECT id, nome, email, ativo FROM usuarios LIMIT 5');
    echo "\nUsuários encontrados:\n";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']}, Nome: {$row['nome']}, Email: {$row['email']}, Ativo: {$row['ativo']}\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . PHP_EOL;
}
?>