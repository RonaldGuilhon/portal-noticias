<?php
/**
 * Teste de Charset e Collation do Banco de Dados
 * Portal de Notícias
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2>Teste de Charset e Collation</h2>";
    
    // Verificar se é conexão MySQL real
    if (get_class($pdo) === 'PDO') {
        // Verificar charset da conexão
        $stmt = $pdo->query("SELECT @@character_set_connection, @@collation_connection");
        $result = $stmt->fetch();
        if ($result) {
            echo "<p><strong>Charset da Conexão:</strong> " . $result['@@character_set_connection'] . "</p>";
            echo "<p><strong>Collation da Conexão:</strong> " . $result['@@collation_connection'] . "</p>";
        }
    } else {
        echo "<p><strong>Aviso:</strong> Usando conexão mock (não MySQL real)</p>";
    }
    
    echo "<h3>Teste de Caracteres Especiais</h3>";
    
    // Teste simples de inserção e recuperação de caracteres especiais
    $textoTeste = "Teste com acentos: ação, coração, não, são, ção";
    $textoTeste2 = "Símbolos: €, ®, ©, ™, ñ, ü, ö";
    
    echo "<p><strong>Texto Original 1:</strong> $textoTeste</p>";
    echo "<p><strong>Texto Original 2:</strong> $textoTeste2</p>";
    
    // Verificar se conseguimos trabalhar com caracteres especiais
    $testData = [
        'titulo' => $textoTeste,
        'conteudo' => $textoTeste2,
        'autor' => 'José da Silva',
        'categoria' => 'Tecnologia'
    ];
    
    echo "<h4>Dados de Teste:</h4>";
    echo "<pre>" . print_r($testData, true) . "</pre>";
    
    // Simular encoding
    echo "<h4>Teste de Encoding:</h4>";
    echo "<p><strong>UTF-8:</strong> " . mb_convert_encoding($textoTeste, 'UTF-8') . "</p>";
    echo "<p><strong>Detecção de encoding:</strong> " . mb_detect_encoding($textoTeste) . "</p>";
    
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Erro de conexão:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><em>Teste concluído em " . date('Y-m-d H:i:s') . "</em></p>";
?>