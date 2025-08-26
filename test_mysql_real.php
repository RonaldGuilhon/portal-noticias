<?php
/**
 * Teste de Conexão MySQL Real
 * Portal de Notícias
 */

// Carregar variáveis de ambiente
if (file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value, '"');
    }
}

echo "<h2>Teste de Conexão MySQL Real</h2>";

// Configurações do banco
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'portal_noticias';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

echo "<p><strong>Configurações:</strong></p>";
echo "<ul>";
echo "<li>Host: $host</li>";
echo "<li>Database: $dbname</li>";
echo "<li>Username: $username</li>";
echo "<li>Charset: $charset</li>";
echo "</ul>";

try {
    // Tentar conexão MySQL
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset"
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "<p style='color: green;'><strong>✓ Conexão MySQL estabelecida com sucesso!</strong></p>";
    
    // Verificar charset da conexão
    $stmt = $pdo->query("SELECT @@character_set_connection, @@collation_connection");
    $result = $stmt->fetch();
    
    echo "<p><strong>Charset da Conexão:</strong> " . $result['@@character_set_connection'] . "</p>";
    echo "<p><strong>Collation da Conexão:</strong> " . $result['@@collation_connection'] . "</p>";
    
    // Testar caracteres especiais
    echo "<h3>Teste de Caracteres Especiais</h3>";
    $textoTeste = "Notícia com acentuação: São Paulo, coração, ação, não";
    echo "<p><strong>Texto de teste:</strong> $textoTeste</p>";
    
    // Verificar se as tabelas existem
    $stmt = $pdo->query("SHOW TABLES LIKE 'noticias'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Tabela 'noticias' encontrada</p>";
        
        // Verificar charset da tabela
        $stmt = $pdo->query("SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$dbname' AND TABLE_NAME = 'noticias'");
        $result = $stmt->fetch();
        if ($result) {
            echo "<p><strong>Collation da tabela noticias:</strong> " . $result['TABLE_COLLATION'] . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ Tabela 'noticias' não encontrada - usando modo mock</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>✗ Erro de conexão MySQL:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Código do erro:</strong> " . $e->getCode() . "</p>";
    
    if ($e->getCode() == 1049) {
        echo "<p style='color: orange;'><strong>Sugestão:</strong> O banco de dados '$dbname' não existe. Execute o script de setup do MySQL.</p>";
    } elseif ($e->getCode() == 1045) {
        echo "<p style='color: orange;'><strong>Sugestão:</strong> Credenciais incorretas. Verifique username e password no arquivo .env</p>";
    } elseif ($e->getCode() == 2002) {
        echo "<p style='color: orange;'><strong>Sugestão:</strong> MySQL não está rodando. Inicie o XAMPP/WAMP ou servidor MySQL.</p>";
    }
    
    echo "<p><strong>Sistema funcionará em modo mock (sem banco real)</strong></p>";
}

echo "<hr>";
echo "<p><em>Teste concluído em " . date('Y-m-d H:i:s') . "</em></p>";
?>