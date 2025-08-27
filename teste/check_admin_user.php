<?php
// Teste para verificar usuário admin@portal.com no banco de dados
require_once __DIR__ . '/../backend/config/database.php';

try {
    $pdo = getConnection();
    
    echo "=== VERIFICAÇÃO DO USUÁRIO ADMIN ===\n";
    echo "Data: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Verificar se o usuário existe
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute(['admin@portal.com']);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        echo "✅ USUÁRIO ENCONTRADO:\n";
        echo "ID: " . $usuario['id'] . "\n";
        echo "Nome: " . ($usuario['nome'] ?: 'VAZIO') . "\n";
        echo "Email: " . $usuario['email'] . "\n";
        echo "Tipo: " . $usuario['tipo_usuario'] . "\n";
        echo "Senha (hash): " . substr($usuario['senha'], 0, 20) . "...\n";
        echo "Data criação: " . $usuario['data_criacao'] . "\n";
        echo "Bio: " . ($usuario['bio'] ?: 'VAZIO') . "\n";
        echo "Foto perfil: " . ($usuario['foto_perfil'] ?: 'VAZIO') . "\n";
        echo "Telefone: " . ($usuario['telefone'] ?: 'VAZIO') . "\n";
        echo "Cidade: " . ($usuario['cidade'] ?: 'VAZIO') . "\n";
        echo "Estado: " . ($usuario['estado'] ?: 'VAZIO') . "\n";
        
        // Verificar senha
        echo "\n=== VERIFICAÇÃO DE SENHA ===\n";
        $senhaTestada = 'password';
        $senhaHash = $usuario['senha'];
        
        // Testar diferentes métodos de hash
        $md5Test = md5($senhaTestada);
        $sha1Test = sha1($senhaTestada);
        $bcryptTest = password_verify($senhaTestada, $senhaHash);
        
        echo "Senha testada: $senhaTestada\n";
        echo "Hash no banco: $senhaHash\n";
        echo "MD5 match: " . ($md5Test === $senhaHash ? '✅ SIM' : '❌ NÃO') . " ($md5Test)\n";
        echo "SHA1 match: " . ($sha1Test === $senhaHash ? '✅ SIM' : '❌ NÃO') . " ($sha1Test)\n";
        echo "BCrypt match: " . ($bcryptTest ? '✅ SIM' : '❌ NÃO') . "\n";
        
        // Verificar preferências
        echo "\n=== PREFERÊNCIAS ===\n";
        echo "Preferências: " . ($usuario['preferencias'] ?: 'VAZIO') . "\n";
        if ($usuario['preferencias']) {
            $prefs = json_decode($usuario['preferencias'], true);
            if ($prefs) {
                print_r($prefs);
            }
        }
        
    } else {
        echo "❌ USUÁRIO NÃO ENCONTRADO\n";
        echo "\n=== CRIANDO USUÁRIO ADMIN ===\n";
        
        // Criar usuário admin se não existir
        $senhaHash = md5('password'); // Usando MD5 como padrão do sistema
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome, email, senha, tipo_usuario, data_criacao) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute(['Admin', 'admin@portal.com', $senhaHash, 'admin'])) {
            echo "✅ Usuário admin criado com sucesso\n";
            echo "Email: admin@portal.com\n";
            echo "Senha: password\n";
            echo "Tipo: admin\n";
        } else {
            echo "❌ Erro ao criar usuário admin\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
?>