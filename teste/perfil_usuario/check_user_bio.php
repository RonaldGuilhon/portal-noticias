<?php
require_once 'config-local.php';
require_once 'backend/config/config.php';

try {
    $pdo = new PDO("mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8mb4", 
        $config['database']['username'], $config['database']['password'], $config['database']['options']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar usuário específico
    $stmt = $pdo->prepare("SELECT id, nome, email, bio, foto_perfil FROM usuarios WHERE email = ?");
    $stmt->execute(['ronaldguilhon@gmail.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Usuário encontrado:\n";
        echo "ID: " . $user['id'] . "\n";
        echo "Nome: " . $user['nome'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Bio: " . ($user['bio'] ? $user['bio'] : 'NULL/VAZIO') . "\n";
        echo "Foto: " . ($user['foto_perfil'] ? $user['foto_perfil'] : 'NULL/VAZIO') . "\n";
        
        // Verificar senha
        $stmt2 = $pdo->prepare("SELECT senha FROM usuarios WHERE email = ?");
        $stmt2->execute(['ronaldguilhon@gmail.com']);
        $userData = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        $senha_fornecida = 'Rede@@123';
        $hash_sha1_fornecida = sha1($senha_fornecida);
        
        echo "Hash SHA1 da senha fornecida: $hash_sha1_fornecida\n";
        echo "Hash armazenado no banco: {$userData['senha']}\n";
        
        if (verifyPassword($senha_fornecida, $userData['senha'])) {
            echo "Senha válida: SIM\n";
        } else {
            echo "Senha válida: NÃO\n";
        }
    } else {
        echo "Usuário não encontrado\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>