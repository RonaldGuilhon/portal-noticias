<?php
/**
 * Script para verificar, criar e popular o banco de dados MySQL
 * Portal de Notícias
 */

// Configurações do banco de dados
$config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'portal_noticias'
];

echo "<h1>🔧 Setup Completo do Banco de Dados MySQL</h1>";
echo "<hr>";

try {
    // Conectar ao MySQL sem especificar banco de dados
    echo "<h2>📡 Conectando ao MySQL...</h2>";
    $pdo = new PDO(
        "mysql:host={$config['host']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    echo "<p style='color:green;'>✅ Conectado ao MySQL com sucesso!</p>";

    // Verificar se o banco de dados existe
    echo "<h2>🗄️ Verificando banco de dados...</h2>";
    $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
    $stmt->execute([$config['database']]);
    $dbExists = $stmt->fetch();

    if (!$dbExists) {
        echo "<p style='color:orange;'>⚠️ Banco de dados '{$config['database']}' não existe. Criando...</p>";
        $pdo->exec("CREATE DATABASE `{$config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<p style='color:green;'>✅ Banco de dados '{$config['database']}' criado com sucesso!</p>";
    } else {
        echo "<p style='color:blue;'>ℹ️ Banco de dados '{$config['database']}' já existe.</p>";
    }

    // Conectar ao banco de dados específico
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Verificar se as tabelas existem
    echo "<h2>📋 Verificando estrutura das tabelas...</h2>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredTables = [
        'usuarios', 'categorias', 'tags', 'noticias', 'noticia_tags',
        'comentarios', 'curtidas_noticias', 'curtidas_comentarios',
        'estatisticas_acesso', 'newsletter', 'anuncios', 'configuracoes',
        'midias', 'notificacoes'
    ];

    $missingTables = array_diff($requiredTables, $tables);

    if (!empty($missingTables)) {
        echo "<p style='color:orange;'>⚠️ Tabelas faltando: " . implode(', ', $missingTables) . "</p>";
        echo "<p>📥 Executando script de estrutura do banco...</p>";
        
        // Ler e executar o arquivo SQL de estrutura
        $sqlFile = __DIR__ . '/database/portal_noticias.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            
            // Dividir o SQL em comandos individuais
            $commands = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($commands as $command) {
                if (!empty($command) && !preg_match('/^\s*--/', $command)) {
                    try {
                        $pdo->exec($command);
                    } catch (PDOException $e) {
                        // Ignorar erros de tabelas que já existem
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            echo "<p style='color:red;'>❌ Erro ao executar comando: " . htmlspecialchars($e->getMessage()) . "</p>";
                        }
                    }
                }
            }
            echo "<p style='color:green;'>✅ Estrutura do banco criada com sucesso!</p>";
        } else {
            echo "<p style='color:red;'>❌ Arquivo de estrutura não encontrado: {$sqlFile}</p>";
        }
    } else {
        echo "<p style='color:green;'>✅ Todas as tabelas necessárias já existem.</p>";
    }

    // Verificar se há dados nas tabelas
    echo "<h2>📊 Verificando dados existentes...</h2>";
    $hasData = false;
    
    foreach ($requiredTables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `{$table}`");
            $count = $stmt->fetch()['count'];
            echo "<p>📋 Tabela '{$table}': {$count} registros</p>";
            if ($count > 0) {
                $hasData = true;
            }
        } catch (PDOException $e) {
            echo "<p style='color:orange;'>⚠️ Erro ao verificar tabela '{$table}': " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    // Inserir dados de teste se necessário
    if (!$hasData) {
        echo "<h2>🌱 Inserindo dados de teste...</h2>";
        
        $dataFile = __DIR__ . '/dados_teste_completos.sql';
        if (file_exists($dataFile)) {
            $sql = file_get_contents($dataFile);
            
            // Dividir o SQL em comandos individuais
            $commands = array_filter(array_map('trim', explode(';', $sql)));
            
            $successCount = 0;
            $errorCount = 0;
            
            foreach ($commands as $command) {
                if (!empty($command) && !preg_match('/^\s*--/', $command)) {
                    try {
                        $pdo->exec($command);
                        $successCount++;
                    } catch (PDOException $e) {
                        $errorCount++;
                        echo "<p style='color:orange;'>⚠️ Aviso: " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                }
            }
            
            echo "<p style='color:green;'>✅ Dados de teste inseridos! Comandos executados: {$successCount}, Avisos: {$errorCount}</p>";
        } else {
            echo "<p style='color:red;'>❌ Arquivo de dados de teste não encontrado: {$dataFile}</p>";
        }
    } else {
        echo "<p style='color:blue;'>ℹ️ Banco já contém dados. Pulando inserção de dados de teste.</p>";
    }

    // Verificação final
    echo "<h2>🔍 Verificação Final</h2>";
    foreach ($requiredTables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `{$table}`");
            $count = $stmt->fetch()['count'];
            $status = $count > 0 ? "✅" : "⚠️";
            echo "<p>{$status} {$table}: {$count} registros</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red;'>❌ {$table}: Erro - " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    echo "<hr>";
    echo "<h2 style='color:green;'>🎉 Setup Concluído!</h2>";
    echo "<p><strong>Banco de dados:</strong> {$config['database']}</p>";
    echo "<p><strong>Host:</strong> {$config['host']}</p>";
    echo "<p><strong>Status:</strong> Pronto para uso!</p>";
    
    echo "<h3>👥 Usuários de Teste Criados:</h3>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@portal.com (senha: password)</li>";
    echo "<li><strong>Editor:</strong> editor@portal.com (senha: password)</li>";
    echo "<li><strong>Autor:</strong> autor@portal.com (senha: password)</li>";
    echo "<li><strong>Leitor:</strong> leitor@portal.com (senha: password)</li>";
    echo "</ul>";
    
    echo "<p style='background:#e8f5e8;padding:10px;border-radius:5px;'>";
    echo "<strong>✅ Próximos passos:</strong><br>";
    echo "1. Configure o arquivo backend/config/database.php para usar MySQL<br>";
    echo "2. Reinicie os servidores PHP<br>";
    echo "3. Acesse o portal em http://localhost:8000/";
    echo "</p>";

} catch (PDOException $e) {
    echo "<h2 style='color:red;'>❌ Erro de Conexão</h2>";
    echo "<p>Não foi possível conectar ao MySQL:</p>";
    echo "<p style='color:red;'><strong>" . htmlspecialchars($e->getMessage()) . "</strong></p>";
    echo "<p>Verifique se:</p>";
    echo "<ul>";
    echo "<li>O MySQL está rodando</li>";
    echo "<li>As credenciais estão corretas</li>";
    echo "<li>O usuário 'root' tem permissões adequadas</li>";
    echo "</ul>";
} catch (Exception $e) {
    echo "<h2 style='color:red;'>❌ Erro Geral</h2>";
    echo "<p style='color:red;'><strong>" . htmlspecialchars($e->getMessage()) . "</strong></p>";
}
?>