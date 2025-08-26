<?php
/**
 * Script alternativo para configurar MySQL via linha de comando
 * Portal de Notícias
 */

echo "<h1>🔧 Setup MySQL via Linha de Comando</h1>";
echo "<hr>";

// Configurações
$config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'portal_noticias'
];

echo "<h2>📋 Diagnóstico do Ambiente</h2>";

// Verificar se o comando mysql está disponível
echo "<h3>🔍 Verificando comando MySQL...</h3>";
$output = [];
$returnCode = 0;
exec('mysql --version 2>&1', $output, $returnCode);

if ($returnCode === 0) {
    echo "<p style='color:green;'>✅ Comando MySQL disponível: " . htmlspecialchars(implode(' ', $output)) . "</p>";
    $mysqlAvailable = true;
} else {
    echo "<p style='color:red;'>❌ Comando MySQL não encontrado no PATH</p>";
    echo "<p>Saída: " . htmlspecialchars(implode(' ', $output)) . "</p>";
    $mysqlAvailable = false;
}

// Verificar se os arquivos SQL existem
echo "<h3>📁 Verificando arquivos SQL...</h3>";
$structureFile = __DIR__ . '/database/portal_noticias.sql';
$dataFile = __DIR__ . '/dados_teste_completos.sql';

if (file_exists($structureFile)) {
    echo "<p style='color:green;'>✅ Arquivo de estrutura encontrado: portal_noticias.sql</p>";
} else {
    echo "<p style='color:red;'>❌ Arquivo de estrutura não encontrado: {$structureFile}</p>";
}

if (file_exists($dataFile)) {
    echo "<p style='color:green;'>✅ Arquivo de dados encontrado: dados_teste_completos.sql</p>";
} else {
    echo "<p style='color:red;'>❌ Arquivo de dados não encontrado: {$dataFile}</p>";
}

if ($mysqlAvailable && file_exists($structureFile) && file_exists($dataFile)) {
    echo "<hr>";
    echo "<h2>🚀 Executando Setup Automático</h2>";
    
    // Criar banco de dados
    echo "<h3>🗄️ Criando banco de dados...</h3>";
    $createDbCommand = "mysql -h {$config['host']} -u {$config['username']}";
    if (!empty($config['password'])) {
        $createDbCommand .= " -p{$config['password']}";
    }
    $createDbCommand .= " -e \"CREATE DATABASE IF NOT EXISTS {$config['database']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\"";
    
    $output = [];
    $returnCode = 0;
    exec($createDbCommand . ' 2>&1', $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "<p style='color:green;'>✅ Banco de dados '{$config['database']}' criado/verificado com sucesso!</p>";
    } else {
        echo "<p style='color:red;'>❌ Erro ao criar banco: " . htmlspecialchars(implode(' ', $output)) . "</p>";
    }
    
    // Executar estrutura
    echo "<h3>📋 Criando estrutura das tabelas...</h3>";
    $structureCommand = "mysql -h {$config['host']} -u {$config['username']}";
    if (!empty($config['password'])) {
        $structureCommand .= " -p{$config['password']}";
    }
    $structureCommand .= " {$config['database']} < \"" . str_replace('/', '\\', $structureFile) . "\"";
    
    $output = [];
    $returnCode = 0;
    exec($structureCommand . ' 2>&1', $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "<p style='color:green;'>✅ Estrutura das tabelas criada com sucesso!</p>";
    } else {
        echo "<p style='color:orange;'>⚠️ Aviso na criação da estrutura: " . htmlspecialchars(implode(' ', $output)) . "</p>";
    }
    
    // Executar dados de teste
    echo "<h3>🌱 Inserindo dados de teste...</h3>";
    $dataCommand = "mysql -h {$config['host']} -u {$config['username']}";
    if (!empty($config['password'])) {
        $dataCommand .= " -p{$config['password']}";
    }
    $dataCommand .= " {$config['database']} < \"" . str_replace('/', '\\', $dataFile) . "\"";
    
    $output = [];
    $returnCode = 0;
    exec($dataCommand . ' 2>&1', $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "<p style='color:green;'>✅ Dados de teste inseridos com sucesso!</p>";
    } else {
        echo "<p style='color:orange;'>⚠️ Aviso na inserção de dados: " . htmlspecialchars(implode(' ', $output)) . "</p>";
    }
    
    // Verificar resultado
    echo "<h3>🔍 Verificando resultado...</h3>";
    $checkCommand = "mysql -h {$config['host']} -u {$config['username']}";
    if (!empty($config['password'])) {
        $checkCommand .= " -p{$config['password']}";
    }
    $checkCommand .= " {$config['database']} -e \"SHOW TABLES;\"";
    
    $output = [];
    $returnCode = 0;
    exec($checkCommand . ' 2>&1', $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "<p style='color:green;'>✅ Tabelas criadas:</p>";
        echo "<ul>";
        foreach ($output as $line) {
            if (!empty(trim($line)) && !preg_match('/Tables_in_/', $line)) {
                echo "<li>" . htmlspecialchars(trim($line)) . "</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red;'>❌ Erro ao verificar tabelas: " . htmlspecialchars(implode(' ', $output)) . "</p>";
    }
    
    echo "<hr>";
    echo "<h2 style='color:green;'>🎉 Setup Concluído!</h2>";
    echo "<p><strong>Banco de dados:</strong> {$config['database']}</p>";
    echo "<p><strong>Host:</strong> {$config['host']}</p>";
    
} else {
    echo "<hr>";
    echo "<h2 style='color:orange;'>⚠️ Setup Manual Necessário</h2>";
    echo "<p>Como o ambiente não permite execução automática, siga os passos manuais:</p>";
    
    echo "<h3>📝 Comandos para executar no MySQL:</h3>";
    echo "<div style='background:#f5f5f5;padding:10px;border-radius:5px;font-family:monospace;'>";
    echo "-- 1. Conectar ao MySQL<br>";
    echo "mysql -u root -p<br><br>";
    
    echo "-- 2. Criar banco de dados<br>";
    echo "CREATE DATABASE IF NOT EXISTS portal_noticias CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;<br>";
    echo "USE portal_noticias;<br><br>";
    
    echo "-- 3. Executar estrutura<br>";
    echo "SOURCE " . str_replace('\\', '/', $structureFile) . ";<br><br>";
    
    echo "-- 4. Executar dados de teste<br>";
    echo "SOURCE " . str_replace('\\', '/', $dataFile) . ";<br><br>";
    
    echo "-- 5. Verificar<br>";
    echo "SHOW TABLES;<br>";
    echo "SELECT COUNT(*) FROM usuarios;<br>";
    echo "SELECT COUNT(*) FROM noticias;<br>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>🔧 Configuração do Backend</h3>";
echo "<p>Após criar o banco, edite o arquivo <code>backend/config/database.php</code> para usar MySQL real:</p>";
echo "<div style='background:#f5f5f5;padding:10px;border-radius:5px;font-family:monospace;'>";
echo "// Descomente as linhas de conexão MySQL<br>";
echo "// Comente as linhas de MockPDO<br>";
echo "</div>";

echo "<h3>👥 Usuários de Teste:</h3>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin@portal.com (senha: password)</li>";
echo "<li><strong>Editor:</strong> editor@portal.com (senha: password)</li>";
echo "<li><strong>Autor:</strong> autor@portal.com (senha: password)</li>";
echo "<li><strong>Leitor:</strong> leitor@portal.com (senha: password)</li>";
echo "</ul>";
?>