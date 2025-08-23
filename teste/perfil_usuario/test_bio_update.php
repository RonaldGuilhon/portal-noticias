<?php
require_once 'config-local.php';
require_once 'backend/config/config.php';

try {
    $pdo = new PDO("mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8mb4", 
        $config['database']['username'], $config['database']['password'], $config['database']['options']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== TESTE DE ATUALIZAÇÃO DA BIOGRAFIA ===\n\n";
    
    // 1. Verificar estado atual
    echo "1. Estado atual da biografia:\n";
    $stmt = $pdo->prepare("SELECT id, nome, email, bio FROM usuarios WHERE email = ?");
    $stmt->execute(['ronaldguilhon@gmail.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "ID: {$user['id']}\n";
        echo "Nome: {$user['nome']}\n";
        echo "Email: {$user['email']}\n";
        echo "Bio atual: " . ($user['bio'] ?: 'NULL/VAZIO') . "\n\n";
        
        // 2. Tentar atualizar a biografia
        echo "2. Atualizando biografia...\n";
        $nova_bio = "Esta é uma biografia de teste atualizada em " . date('Y-m-d H:i:s');
        
        $stmt_update = $pdo->prepare("UPDATE usuarios SET bio = ? WHERE id = ?");
        $resultado = $stmt_update->execute([$nova_bio, $user['id']]);
        
        if ($resultado) {
            echo "✓ Atualização executada com sucesso\n";
            echo "Linhas afetadas: " . $stmt_update->rowCount() . "\n\n";
            
            // 3. Verificar se foi salvo
            echo "3. Verificando se foi salvo:\n";
            $stmt_check = $pdo->prepare("SELECT bio FROM usuarios WHERE id = ?");
            $stmt_check->execute([$user['id']]);
            $bio_atualizada = $stmt_check->fetchColumn();
            
            echo "Bio após atualização: " . ($bio_atualizada ?: 'NULL/VAZIO') . "\n";
            
            if ($bio_atualizada === $nova_bio) {
                echo "✓ Biografia foi salva corretamente!\n";
            } else {
                echo "✗ Biografia NÃO foi salva corretamente\n";
            }
        } else {
            echo "✗ Erro ao executar atualização\n";
        }
        
    } else {
        echo "✗ Usuário não encontrado\n";
    }
    
} catch(Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>