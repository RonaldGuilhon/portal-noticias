<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/config/database.php';

echo "=== DEBUG ESPECÍFICO DA ALTERAÇÃO DE SENHA ===\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // 1. Verificar usuário específico
    echo "1. VERIFICANDO USUÁRIO ID 2:\n";
    $query = "SELECT id, nome, email, senha FROM usuarios WHERE id = 2";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "✗ Usuário não encontrado!\n";
        exit;
    }
    
    echo "- ID: {$user['id']}\n";
    echo "- Nome: {$user['nome']}\n";
    echo "- Email: {$user['email']}\n";
    echo "- Hash atual: {$user['senha']}\n\n";
    
    // 2. Testar verificação da senha atual
    echo "2. TESTANDO VERIFICAÇÃO DA SENHA ATUAL:\n";
    $senha_atual = 'Rede@@123';
    $verifica_atual = verifyPassword($senha_atual, $user['senha']);
    echo "- Senha testada: {$senha_atual}\n";
    echo "- Verificação: " . ($verifica_atual ? 'OK' : 'FALHOU') . "\n\n";
    
    if (!$verifica_atual) {
        echo "✗ Senha atual não confere! Testando outras senhas...\n";
        $senhas_teste = ['password', 'senha123', '123456', 'admin'];
        foreach ($senhas_teste as $teste) {
            if (verifyPassword($teste, $user['senha'])) {
                echo "✓ Senha correta encontrada: {$teste}\n";
                $senha_atual = $teste;
                break;
            }
        }
    }
    
    // 3. Testar geração de novo hash
    echo "3. TESTANDO GERAÇÃO DE NOVO HASH:\n";
    $nova_senha = 'NovaSenha123';
    $novo_hash = hashPassword($nova_senha);
    echo "- Nova senha: {$nova_senha}\n";
    echo "- Novo hash: {$novo_hash}\n";
    echo "- Verificação do novo hash: " . (verifyPassword($nova_senha, $novo_hash) ? 'OK' : 'FALHOU') . "\n\n";
    
    // 4. Simular alteração de senha
    echo "4. SIMULANDO ALTERAÇÃO DE SENHA:\n";
    $query_update = "UPDATE usuarios SET senha = :senha WHERE id = :id";
    $stmt_update = $db->prepare($query_update);
    $stmt_update->bindParam(':senha', $novo_hash);
    $stmt_update->bindParam(':id', $user['id']);
    
    if ($stmt_update->execute()) {
        echo "✓ Hash atualizado no banco\n";
        
        // Verificar se foi salvo corretamente
        $query_check = "SELECT senha FROM usuarios WHERE id = :id";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(':id', $user['id']);
        $stmt_check->execute();
        $hash_salvo = $stmt_check->fetch(PDO::FETCH_ASSOC)['senha'];
        
        echo "- Hash salvo: {$hash_salvo}\n";
        echo "- Hashes coincidem: " . ($novo_hash === $hash_salvo ? 'SIM' : 'NÃO') . "\n";
        echo "- Verificação da nova senha: " . (verifyPassword($nova_senha, $hash_salvo) ? 'OK' : 'FALHOU') . "\n\n";
        
        // 5. Testar login com nova senha
        echo "5. TESTANDO LOGIN COM NOVA SENHA:\n";
        require_once __DIR__ . '/../../backend/models/Usuario.php';
        $usuario_login = new Usuario($db);
        
        if ($usuario_login->login($user['email'], $nova_senha)) {
            echo "✓ Login com nova senha funcionou!\n";
        } else {
            echo "✗ Login com nova senha falhou!\n";
        }
        
        // 6. Restaurar senha original
        echo "\n6. RESTAURANDO SENHA ORIGINAL:\n";
        $stmt_restore = $db->prepare($query_update);
        $stmt_restore->bindParam(':senha', $user['senha']);
        $stmt_restore->bindParam(':id', $user['id']);
        
        if ($stmt_restore->execute()) {
            echo "✓ Senha original restaurada\n";
        } else {
            echo "✗ Erro ao restaurar senha original\n";
        }
        
    } else {
        echo "✗ Erro ao atualizar hash no banco\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

echo "\n=== DEBUG CONCLUÍDO ===\n";
?>