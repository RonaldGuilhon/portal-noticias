<?php
require_once 'backend/config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Verificar notícias
    $stmt = $conn->query('SELECT COUNT(*) as total FROM noticias');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo 'Total de notícias: ' . $result['total'] . PHP_EOL;
    
    // Verificar categorias
    $stmt = $conn->query('SELECT COUNT(*) as total FROM categorias');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo 'Total de categorias: ' . $result['total'] . PHP_EOL;
    
    // Verificar se há notícias publicadas
    $stmt = $conn->query("SELECT COUNT(*) as total FROM noticias WHERE status = 'publicado'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo 'Notícias publicadas: ' . $result['total'] . PHP_EOL;
    
    // Verificar se há notícias na categoria 1
    $stmt = $conn->query("SELECT COUNT(*) as total FROM noticias WHERE categoria_id = 1 AND status = 'publicado'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo 'Notícias na categoria 1: ' . $result['total'] . PHP_EOL;
    
} catch(Exception $e) {
    echo 'Erro: ' . $e->getMessage() . PHP_EOL;
}
?>