<?php
try {
    echo "=== TESTE DE CONEXÃO MYSQL ===\n";
    
    // Testar conexão direta ao MySQL
    $host = 'localhost';
    $dbname = 'portal_noticias';
    $username = 'root';
    $password = '';
    $charset = 'utf8mb4';
    
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    
    echo "DSN: $dsn\n";
    echo "Username: $username\n";
    echo "Password: " . (empty($password) ? '(vazio)' : '(definido)') . "\n";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "Conexão MySQL: SUCESSO\n";
    echo "Tipo de conexão: " . get_class($pdo) . "\n";
    
    // Testar query simples
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $result = $stmt->fetch();
    echo "Total de usuários na tabela: " . $result['total'] . "\n";
    
    // Testar busca do usuário admin
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute(['admin@portalnoticias.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "\nUsuário admin encontrado:\n";
        echo "ID: " . $user['id'] . "\n";
        echo "Nome: " . $user['nome'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Hash da senha: " . $user['senha'] . "\n";
        echo "Tipo: " . $user['tipo_usuario'] . "\n";
        echo "Ativo: " . $user['ativo'] . "\n";
    } else {
        echo "\nUsuário admin NÃO encontrado!\n";
    }
    
} catch (PDOException $e) {
    echo "ERRO na conexão MySQL: " . $e->getMessage() . "\n";
    echo "Código do erro: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "ERRO geral: " . $e->getMessage() . "\n";
}
?>