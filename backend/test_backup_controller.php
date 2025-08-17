<?php
/**
 * Teste específico para BackupController
 */

require_once 'controllers/BackupController.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Teste do BackupController</h2>";

try {
    echo "<h3>1. Instanciando BackupController...</h3>";
    $controller = new BackupController();
    echo "✓ BackupController instanciado<br>";
    
    echo "<h3>2. Testando endpoint 'list'...</h3>";
    $response_list = $controller->handleRequest('GET', 'list');
    echo "✓ Endpoint 'list' executado<br>";
    echo "<h4>Resposta do endpoint 'list':</h4>";
    echo "<pre>" . $response_list . "</pre>";
    
    echo "<h3>3. Testando endpoint 'status'...</h3>";
    $response_status = $controller->handleRequest('GET', 'status');
    echo "✓ Endpoint 'status' executado<br>";
    echo "<h4>Resposta do endpoint 'status':</h4>";
    echo "<pre>" . $response_status . "</pre>";
    
    echo "<h3>4. Comparando respostas...</h3>";
    if ($response_list === $response_status) {
        echo "<p style='color: red;'>❌ As respostas são IDÊNTICAS - há um problema!</p>";
    } else {
        echo "<p style='color: green;'>✓ As respostas são DIFERENTES - funcionando corretamente!</p>";
    }
    
    echo "<h3>5. Analisando JSON das respostas...</h3>";
    $data_list = json_decode($response_list, true);
    $data_status = json_decode($response_status, true);
    
    echo "<h4>Estrutura da resposta 'list':</h4>";
    echo "<pre>" . print_r(array_keys($data_list), true) . "</pre>";
    
    echo "<h4>Estrutura da resposta 'status':</h4>";
    echo "<pre>" . print_r(array_keys($data_status), true) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
}

echo "<p><em>Teste executado em " . date('Y-m-d H:i:s') . "</em></p>";
?>