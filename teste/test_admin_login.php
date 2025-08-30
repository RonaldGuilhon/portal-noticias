<?php
// Teste de login do usuário admin@portal.com com senha Rede@@123
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/../backend/models/Usuario.php';

try {
    echo "=== TESTE DE LOGIN ADMIN ===\n";
    echo "Data: " . date('Y-m-d H:i:s') . "\n\n";
    
    $pdo = getConnection();
    
    // Verificar usuário no banco
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute(['admin@portal.com']);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        echo "✅ USUÁRIO ENCONTRADO:\n";
        echo "ID: " . $usuario['id'] . "\n";
        echo "Nome: " . ($usuario['nome'] ?: 'VAZIO') . "\n";
        echo "Email: " . $usuario['email'] . "\n";
        echo "Tipo: " . $usuario['tipo_usuario'] . "\n";
        echo "Bio: " . ($usuario['bio'] ?: 'VAZIO') . "\n";
        echo "Foto perfil: " . ($usuario['foto_perfil'] ?: 'VAZIO') . "\n";
        echo "Telefone: " . ($usuario['telefone'] ?: 'VAZIO') . "\n";
        echo "Cidade: " . ($usuario['cidade'] ?: 'VAZIO') . "\n";
        echo "Estado: " . ($usuario['estado'] ?: 'VAZIO') . "\n";
        
        // Testar senha Rede@@123
        echo "\n=== TESTE DE SENHA ===\n";
        $senhaTestada = 'Rede@@123';
        $senhaHash = $usuario['senha'];
        
        echo "Senha testada: $senhaTestada\n";
        echo "Hash no banco: $senhaHash\n";
        
        // Testar diferentes métodos de verificação
        $bcryptTest = password_verify($senhaTestada, $senhaHash);
        $md5Test = md5($senhaTestada) === $senhaHash;
        $sha1Test = sha1($senhaTestada) === $senhaHash;
        
        echo "BCrypt verify: " . ($bcryptTest ? '✅ SUCESSO' : '❌ FALHOU') . "\n";
        echo "MD5 match: " . ($md5Test ? '✅ SUCESSO' : '❌ FALHOU') . "\n";
        echo "SHA1 match: " . ($sha1Test ? '✅ SUCESSO' : '❌ FALHOU') . "\n";
        
        if ($bcryptTest || $md5Test || $sha1Test) {
            echo "\n✅ SENHA CORRETA! Login seria bem-sucedido.\n";
            
            // Testar modelo Usuario
            echo "\n=== TESTE DO MODELO USUARIO ===\n";
            $database = new Database();
            $conn = $database->getConnection();
            $usuarioModel = new Usuario($conn);
            $loginResult = $usuarioModel->login($usuario['email'], $senhaTestada);
            
            if ($loginResult) {
                echo "✅ Login via modelo Usuario: SUCESSO\n";
                echo "Dados retornados:\n";
                echo "- ID: " . $loginResult['id'] . "\n";
                echo "- Nome: " . $loginResult['nome'] . "\n";
                echo "- Email: " . $loginResult['email'] . "\n";
                echo "- Tipo: " . $loginResult['tipo_usuario'] . "\n";
            } else {
                echo "❌ Login via modelo Usuario: FALHOU\n";
            }
            
        } else {
            echo "\n❌ SENHA INCORRETA! Verificar se a senha foi alterada.\n";
        }
        
    } else {
        echo "❌ USUÁRIO NÃO ENCONTRADO\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
?>