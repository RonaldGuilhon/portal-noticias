<?php
/**
 * Teste de disponibilidade do driver PDO MySQL
 */

echo "<h1>🔍 Teste de Driver PDO MySQL</h1>";
echo "<hr>";

// Verificar se PDO está disponível
echo "<h2>📋 Verificando PDO...</h2>";
if (class_exists('PDO')) {
    echo "<p style='color:green;'>✅ PDO está disponível</p>";
} else {
    echo "<p style='color:red;'>❌ PDO não está disponível</p>";
    exit;
}

// Listar drivers PDO disponíveis
echo "<h2>🔌 Drivers PDO Disponíveis:</h2>";
$drivers = PDO::getAvailableDrivers();
echo "<ul>";
foreach ($drivers as $driver) {
    echo "<li>{$driver}</li>";
}
echo "</ul>";

// Verificar especificamente o MySQL
echo "<h2>🐬 Verificando MySQL...</h2>";
if (in_array('mysql', $drivers)) {
    echo "<p style='color:green;'>✅ Driver MySQL está disponível</p>";
    
    // Tentar conectar
    echo "<h3>🔗 Testando Conexão...</h3>";
    try {
        $pdo = new PDO('mysql:host=localhost', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "<p style='color:green;'>✅ Conexão com MySQL bem-sucedida!</p>";
        
        // Verificar versão do MySQL
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        echo "<p><strong>Versão do MySQL:</strong> {$version}</p>";
        
        // Verificar se o banco portal_noticias existe
        $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'portal_noticias'");
        $stmt->execute();
        $dbExists = $stmt->fetch();
        
        if ($dbExists) {
            echo "<p style='color:blue;'>ℹ️ Banco 'portal_noticias' já existe</p>";
        } else {
            echo "<p style='color:orange;'>⚠️ Banco 'portal_noticias' não existe</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color:red;'>❌ Erro na conexão: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
} else {
    echo "<p style='color:red;'>❌ Driver MySQL não está disponível</p>";
    echo "<p>Drivers disponíveis: " . implode(', ', $drivers) . "</p>";
}

// Verificar extensões relacionadas
echo "<h2>🧩 Extensões PHP Relacionadas:</h2>";
$extensions = ['mysql', 'mysqli', 'pdo_mysql', 'mysqlnd'];
echo "<ul>";
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? "✅" : "❌";
    echo "<li>{$status} {$ext}</li>";
}
echo "</ul>";

echo "<hr>";
echo "<h2>📝 Resumo</h2>";
if (in_array('mysql', $drivers)) {
    echo "<p style='color:green;'><strong>✅ Sistema pronto para MySQL!</strong></p>";
} else {
    echo "<p style='color:red;'><strong>❌ Driver MySQL não disponível</strong></p>";
    echo "<p>Para resolver, você precisa:</p>";
    echo "<ol>";
    echo "<li>Instalar a extensão pdo_mysql do PHP</li>";
    echo "<li>Ou usar XAMPP/WAMP que já inclui essas extensões</li>";
    echo "</ol>";
}
?>