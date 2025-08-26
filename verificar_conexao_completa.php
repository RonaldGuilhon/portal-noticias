<?php
/**
 * Verificação Completa de Conexão - Multi Ambiente
 * Portal de Notícias
 */

require_once 'backend/config/database.php';
require_once 'backend/config/config.php';

echo "<h1>🔍 Verificação Completa de Conexão - Multi Ambiente</h1>";
echo "<hr>";

// Função para testar conexão com configurações específicas
function testarConexao($host, $dbname, $username, $password, $ambiente = 'Padrão') {
    echo "<h3>🌐 Testando Ambiente: $ambiente</h3>";
    echo "<p><strong>Host:</strong> $host</p>";
    echo "<p><strong>Database:</strong> $dbname</p>";
    echo "<p><strong>Username:</strong> $username</p>";
    echo "<p><strong>Password:</strong> " . (empty($password) ? '[vazio]' : '[definida]') . "</p>";
    
    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);
        
        echo "<p style='color:green;'>✅ <strong>Conexão estabelecida com sucesso!</strong></p>";
        
        // Informações do MySQL
        $stmt = $pdo->query("SELECT VERSION() as version, DATABASE() as current_db");
        $info = $stmt->fetch();
        echo "<p><strong>Versão MySQL:</strong> " . $info['version'] . "</p>";
        echo "<p><strong>Banco Atual:</strong> " . $info['current_db'] . "</p>";
        
        // Verificar tabelas
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p><strong>Tabelas encontradas:</strong> " . count($tables) . "</p>";
        
        if (count($tables) > 0) {
            echo "<ul>";
            foreach ($tables as $table) {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $count = $stmt->fetch()['count'];
                echo "<li>$table ($count registros)</li>";
            }
            echo "</ul>";
        }
        
        return true;
        
    } catch (PDOException $e) {
        echo "<p style='color:red;'>❌ <strong>Erro:</strong> " . $e->getMessage() . "</p>";
        return false;
    }
    
    echo "<hr>";
}

// Informações do sistema
echo "<h2>📋 Informações do Sistema</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>PDO Drivers:</strong> " . implode(', ', PDO::getAvailableDrivers()) . "</p>";
echo "<p><strong>Extensões MySQL:</strong> ";
if (extension_loaded('pdo_mysql')) echo "✅ PDO_MySQL ";
if (extension_loaded('mysqli')) echo "✅ MySQLi ";
echo "</p>";
echo "<hr>";

// Teste 1: Configuração do config.php
echo "<h2>🔧 Teste 1: Configuração Padrão (config.php)</h2>";
$sucesso1 = testarConexao(DB_HOST, DB_NAME, DB_USER, DB_PASS, 'Config.php');

// Teste 2: Configuração do .env (se existir)
echo "<h2>🔧 Teste 2: Configuração .env</h2>";
$envFile = '.env';
if (file_exists($envFile)) {
    $envVars = [];
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
            list($key, $value) = explode('=', $line, 2);
            $envVars[trim($key)] = trim($value);
        }
    }
    
    $host = $envVars['DB_HOST'] ?? 'localhost';
    $dbname = $envVars['DB_NAME'] ?? 'portal_noticias';
    $username = $envVars['DB_USERNAME'] ?? 'root';
    $password = $envVars['DB_PASSWORD'] ?? '';
    
    $sucesso2 = testarConexao($host, $dbname, $username, $password, '.env');
} else {
    echo "<p style='color:orange;'>⚠️ Arquivo .env não encontrado</p>";
    echo "<p>Copie o arquivo .env.example para .env e configure suas credenciais</p>";
    $sucesso2 = false;
}

// Teste 3: Configurações alternativas comuns
echo "<h2>🔧 Teste 3: Configurações Alternativas</h2>";

// XAMPP/WAMP padrão
echo "<h4>XAMPP/WAMP Padrão:</h4>";
$sucesso3a = testarConexao('localhost', 'portal_noticias', 'root', '', 'XAMPP/WAMP');

// MAMP padrão
echo "<h4>MAMP Padrão:</h4>";
$sucesso3b = testarConexao('localhost', 'portal_noticias', 'root', 'root', 'MAMP');

// Laragon padrão
echo "<h4>Laragon Padrão:</h4>";
$sucesso3c = testarConexao('localhost', 'portal_noticias', 'root', '', 'Laragon');

// Teste 4: Usando a classe Database do sistema
echo "<h2>🔧 Teste 4: Classe Database do Sistema</h2>";
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if ($pdo instanceof MockPDO) {
        echo "<p style='color:orange;'>⚠️ <strong>Sistema usando dados mockados</strong></p>";
        echo "<p>O sistema está funcionando com dados simulados (mock_data.json)</p>";
        echo "<p>Isso significa que nenhuma conexão MySQL real foi estabelecida</p>";
    } else {
        echo "<p style='color:green;'>✅ <strong>Classe Database funcionando com MySQL real!</strong></p>";
        
        // Teste de usuário admin
        $stmt = $pdo->prepare("SELECT id, nome, email, tipo_usuario FROM usuarios WHERE tipo_usuario = 'admin' LIMIT 1");
        $stmt->execute();
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo "<p><strong>Usuário Admin encontrado:</strong></p>";
            echo "<ul>";
            echo "<li><strong>ID:</strong> " . $admin['id'] . "</li>";
            echo "<li><strong>Nome:</strong> " . $admin['nome'] . "</li>";
            echo "<li><strong>Email:</strong> " . $admin['email'] . "</li>";
            echo "</ul>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ <strong>Erro na classe Database:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Resumo final
echo "<h2>📊 Resumo dos Testes</h2>";
echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
echo "<tr><th>Ambiente</th><th>Status</th><th>Recomendação</th></tr>";

echo "<tr>";
echo "<td>Config.php</td>";
echo "<td>" . ($sucesso1 ? "✅ Sucesso" : "❌ Falhou") . "</td>";
echo "<td>" . ($sucesso1 ? "Usar para produção" : "Verificar credenciais") . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td>.env</td>";
echo "<td>" . ($sucesso2 ? "✅ Sucesso" : "❌ Falhou") . "</td>";
echo "<td>" . ($sucesso2 ? "Usar para desenvolvimento" : "Criar arquivo .env") . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td>XAMPP/WAMP</td>";
echo "<td>" . ($sucesso3a ? "✅ Sucesso" : "❌ Falhou") . "</td>";
echo "<td>" . ($sucesso3a ? "Configuração padrão OK" : "Verificar MySQL") . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td>MAMP</td>";
echo "<td>" . ($sucesso3b ? "✅ Sucesso" : "❌ Falhou") . "</td>";
echo "<td>" . ($sucesso3b ? "Configuração Mac OK" : "Verificar senha root") . "</td>";
echo "</tr>";

echo "</table>";

echo "<hr>";
echo "<h3>🚀 Próximos Passos</h3>";
echo "<ol>";
echo "<li><strong>Se algum teste passou:</strong> Use essa configuração no seu .env</li>";
echo "<li><strong>Se todos falharam:</strong> Verifique se o MySQL está rodando</li>";
echo "<li><strong>Para dois ambientes:</strong> Crie dois arquivos .env diferentes</li>";
echo "<li><strong>Teste os servidores:</strong></li>";
echo "<ul>";
echo "<li>Frontend: <code>php -S localhost:8000 -t frontend</code></li>";
echo "<li>Backend: <code>php -S localhost:8001 -t backend backend/router.php</code></li>";
echo "</ul>";
echo "</ol>";

echo "<p><strong>Teste executado em:</strong> " . date('d/m/Y H:i:s') . "</p>";
?>