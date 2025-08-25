<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== VERIFICANDO SENHA DO ADMIN ===\n";
    
    $stmt = $pdo->prepare('SELECT id, nome, email, senha FROM usuarios WHERE email = ?');
    $stmt->execute(['admin@admin.com']);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "ID: {$admin['id']}\n";
        echo "Nome: {$admin['nome']}\n";
        echo "Email: {$admin['email']}\n";
        echo "Hash da senha: {$admin['senha']}\n";
        
        // Testar algumas senhas comuns
        $senhas_teste = ['admin123', 'admin', 'password', '123456', 'admin@admin.com'];
        
        echo "\n=== TESTANDO SENHAS ===\n";
        foreach ($senhas_teste as $senha) {
            if (password_verify($senha, $admin['senha'])) {
                echo "✅ Senha correta: {$senha}\n";
            } else {
                echo "❌ Senha incorreta: {$senha}\n";
            }
        }
        
        // Testar também com SHA1 (caso seja um hash antigo)
        echo "\n=== TESTANDO COM SHA1 ===\n";
        foreach ($senhas_teste as $senha) {
            $sha1_hash = sha1($senha);
            echo "SHA1 de '{$senha}': {$sha1_hash}\n";
            if ($sha1_hash === $admin['senha']) {
                echo "✅ Senha SHA1 correta: {$senha}\n";
            } else {
                echo "❌ Senha SHA1 incorreta: {$senha}\n";
            }
        }
        
        // Verificar se o hash é conhecido
        echo "\n=== VERIFICANDO HASH CONHECIDO ===\n";
        $hashes_conhecidos = [
            '3eb785574a9148c7237d0297e397282cb1624ca2' => 'Possível hash SHA1',
            '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8' => 'password (SHA1)',
            'aaf4c61ddcc5e8a2dabede0f3b482cd9aea9434d' => 'hello (SHA1)',
            '356a192b7913b04c54574d18c28d46e6395428ab' => '1 (SHA1)',
            'da39a3ee5e6b4b0d3255bfef95601890afd80709' => 'string vazia (SHA1)'
        ];
        
        if (isset($hashes_conhecidos[$admin['senha']])) {
            echo "Hash reconhecido: {$hashes_conhecidos[$admin['senha']]}\n";
        } else {
            echo "Hash não reconhecido: {$admin['senha']}\n";
        }
        
    } else {
        echo "❌ Usuário admin não encontrado\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>