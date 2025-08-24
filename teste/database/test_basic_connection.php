<?php
echo "=== TESTE BÁSICO DE CONEXÃO ===\n\n";

try {
    // Testar se o arquivo de configuração existe
    $configFile = __DIR__ . '/../config-local.php';
    if (file_exists($configFile)) {
        echo "✓ Arquivo config-local.php encontrado\n";
        require_once $configFile;
    } else {
        echo "✗ Arquivo config-local.php não encontrado\n";
        exit(1);
    }
    
    // Testar conexão com banco
    $db = $config['database'];
    $pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset=utf8", $db['username'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Conexão com banco de dados estabelecida\n";
    
    // Verificar se a tabela usuarios existe e não tem notification_frequency
    $stmt = $pdo->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\n=== COLUNAS DA TABELA USUARIOS ===\n";
    foreach ($columns as $column) {
        echo "- $column\n";
    }
    
    if (in_array('notification_frequency', $columns)) {
        echo "\n✗ ERRO: Coluna notification_frequency ainda existe!\n";
    } else {
        echo "\n✓ Coluna notification_frequency removida com sucesso\n";
    }
    
    // Testar busca simples de usuário
    $stmt = $pdo->prepare("SELECT id, nome, email FROM usuarios WHERE id = ?");
    $stmt->execute([2]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "\n✓ Usuário ID 2 encontrado: {$user['nome']} ({$user['email']})\n";
    } else {
        echo "\n✗ Usuário ID 2 não encontrado\n";
    }
    
} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
?>