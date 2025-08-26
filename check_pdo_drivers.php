<?php
/**
 * Verificação de Drivers PDO Disponíveis
 * Portal de Notícias
 */

echo "<h2>Verificação de Drivers PDO</h2>";

// Verificar se PDO está disponível
if (!extension_loaded('pdo')) {
    echo "<p style='color: red;'>✗ Extensão PDO não está carregada</p>";
    exit;
}

echo "<p style='color: green;'>✓ Extensão PDO está carregada</p>";

// Listar drivers disponíveis
$drivers = PDO::getAvailableDrivers();
echo "<h3>Drivers PDO Disponíveis:</h3>";
echo "<ul>";
foreach ($drivers as $driver) {
    echo "<li>$driver</li>";
}
echo "</ul>";

// Verificar driver MySQL especificamente
if (in_array('mysql', $drivers)) {
    echo "<p style='color: green;'>✓ Driver MySQL está disponível</p>";
    
    // Tentar conexão simples
    try {
        $pdo = new PDO('mysql:host=localhost', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "<p style='color: green;'>✓ Conexão MySQL básica funcionando</p>";
        
        // Verificar se o banco portal_noticias existe
        $stmt = $pdo->query("SHOW DATABASES LIKE 'portal_noticias'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Banco 'portal_noticias' existe</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Banco 'portal_noticias' não existe</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ Erro na conexão MySQL: " . $e->getMessage() . "</p>";
        echo "<p><strong>Código:</strong> " . $e->getCode() . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Driver MySQL não está disponível</p>";
    echo "<p><strong>Solução:</strong> Instale a extensão php-mysql ou ative no php.ini</p>";
}

// Verificar outras extensões relacionadas
echo "<h3>Outras Extensões:</h3>";
$extensions = ['mysqli', 'mysqlnd', 'mbstring'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p style='color: green;'>✓ $ext está carregada</p>";
    } else {
        echo "<p style='color: red;'>✗ $ext não está carregada</p>";
    }
}

echo "<hr>";
echo "<p><em>Verificação concluída em " . date('Y-m-d H:i:s') . "</em></p>";
?>