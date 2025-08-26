<?php
/**
 * Teste de Conexão com Banco de Dados
 * Portal de Notícias
 */

require_once 'backend/config/config.php';

echo "<h2>Teste de Conexão com Banco de Dados</h2>";
echo "<hr>";

// Informações de configuração
echo "<h3>Configurações:</h3>";
echo "Host: " . DB_HOST . "<br>";
echo "Database: " . DB_NAME . "<br>";
echo "User: " . DB_USER . "<br>";
echo "Password: " . (empty(DB_PASS) ? '[vazio]' : '[definida]') . "<br><br>";

// Teste 1: Verificar se extensão MySQL está disponível
echo "<h3>1. Verificação de Extensões PHP:</h3>";
if (extension_loaded('pdo')) {
    echo "✅ PDO: Disponível<br>";
} else {
    echo "❌ PDO: Não disponível<br>";
}

if (extension_loaded('pdo_mysql')) {
    echo "✅ PDO MySQL: Disponível<br>";
} else {
    echo "❌ PDO MySQL: Não disponível<br>";
}

if (extension_loaded('mysqli')) {
    echo "✅ MySQLi: Disponível<br>";
} else {
    echo "❌ MySQLi: Não disponível<br>";
}

echo "<br>";

// Teste 2: Tentar conexão com PDO
echo "<h3>2. Teste de Conexão PDO:</h3>";
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
    echo "✅ Conexão PDO: Sucesso<br>";
    
    // Teste de query simples
    $stmt = $pdo->query("SELECT VERSION() as version");
    $result = $stmt->fetch();
    echo "✅ Versão MySQL: " . $result['version'] . "<br>";
    
} catch (PDOException $e) {
    echo "❌ Erro na conexão PDO: " . $e->getMessage() . "<br>";
}

echo "<br>";

// Teste 3: Verificar se o banco de dados existe
echo "<h3>3. Verificação do Banco de Dados:</h3>";
try {
    $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
    $stmt->execute([DB_NAME]);
    
    if ($stmt->fetch()) {
        echo "✅ Banco de dados '" . DB_NAME . "': Existe<br>";
        
        // Conectar ao banco específico
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Verificar tabelas principais
        echo "<h4>Tabelas encontradas:</h4>";
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $expectedTables = ['usuarios', 'categorias', 'noticias', 'tags', 'comentarios'];
        
        foreach ($expectedTables as $table) {
            if (in_array($table, $tables)) {
                echo "✅ Tabela '$table': Existe<br>";
                
                // Contar registros
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $count = $stmt->fetch()['count'];
                echo "&nbsp;&nbsp;&nbsp;→ Registros: $count<br>";
            } else {
                echo "❌ Tabela '$table': Não encontrada<br>";
            }
        }
        
    } else {
        echo "❌ Banco de dados '" . DB_NAME . "': Não existe<br>";
        echo "<p><strong>Solução:</strong> Execute o script de instalação do banco de dados.</p>";
    }
    
} catch (PDOException $e) {
    echo "❌ Erro ao verificar banco: " . $e->getMessage() . "<br>";
}

echo "<br>";

// Teste 4: Testar uma consulta de usuário
echo "<h3>4. Teste de Consulta (Usuários):</h3>";
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $stmt = $pdo->query("SELECT id, nome, email, tipo_usuario FROM usuarios LIMIT 5");
    $users = $stmt->fetchAll();
    
    if ($users) {
        echo "✅ Consulta de usuários: Sucesso<br>";
        echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Tipo</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['nome']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['tipo_usuario']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "⚠️ Nenhum usuário encontrado na tabela<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Erro na consulta: " . $e->getMessage() . "<br>";
}

echo "<br>";

// Teste 5: Verificar se o backend está respondendo
echo "<h3>5. Teste de API Backend:</h3>";
$backendUrl = 'http://localhost:8001/api/test';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 5,
        'ignore_errors' => true
    ]
]);

$response = @file_get_contents($backendUrl, false, $context);

if ($response !== false) {
    echo "✅ Backend API: Respondendo<br>";
    echo "Resposta: " . htmlspecialchars(substr($response, 0, 200)) . "<br>";
} else {
    echo "❌ Backend API: Não está respondendo em $backendUrl<br>";
    echo "<p><strong>Verifique se o servidor backend está rodando:</strong><br>";
    echo "<code>php -S localhost:8001 -t backend backend/router.php</code></p>";
}

echo "<hr>";
echo "<p><strong>Teste concluído em:</strong> " . date('d/m/Y H:i:s') . "</p>";
?>