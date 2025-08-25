<?php
require_once 'backend/config/database.php';
require_once 'backend/config/config.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== REDEFININDO SENHA DO ADMIN ===\n";
    
    $nova_senha = 'admin123';
    $hash_senha = hashPassword($nova_senha);
    
    echo "Nova senha: {$nova_senha}\n";
    echo "Hash gerado: {$hash_senha}\n";
    
    $stmt = $pdo->prepare('UPDATE usuarios SET senha = ? WHERE email = ?');
    $resultado = $stmt->execute([$hash_senha, 'admin@admin.com']);
    
    if ($resultado) {
        echo "✅ Senha do admin atualizada com sucesso!\n";
        
        // Verificar se a atualização funcionou
        $stmt = $pdo->prepare('SELECT senha FROM usuarios WHERE email = ?');
        $stmt->execute(['admin@admin.com']);
        $admin = $stmt->fetch();
        
        echo "Hash no banco: {$admin['senha']}\n";
        
        // Testar a verificação
        if (verifyPassword($nova_senha, $admin['senha'])) {
            echo "✅ Verificação de senha funcionando corretamente!\n";
        } else {
            echo "❌ Erro na verificação de senha\n";
        }
        
    } else {
        echo "❌ Erro ao atualizar a senha\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>