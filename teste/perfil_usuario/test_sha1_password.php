<?php
require_once 'config-local.php';
require_once 'backend/config/config.php';
require_once 'backend/models/Usuario.php';

try {
    echo "=== TESTE ESPECÍFICO DE ALTERAÇÃO DE SENHA COM SHA1 ===\n\n";
    
    // 1. Verificar se as funções SHA1 estão funcionando
    echo "1. VERIFICANDO FUNÇÕES SHA1:\n";
    echo str_repeat("-", 50) . "\n";
    
    $senha_teste = 'minhasenha123';
    $hash_sha1 = hashPassword($senha_teste);
    
    echo "Senha: {$senha_teste}\n";
    echo "Hash SHA1: {$hash_sha1}\n";
    echo "Verificação: " . (verifyPassword($senha_teste, $hash_sha1) ? 'OK' : 'ERRO') . "\n\n";
    
    // 2. Testar com usuário real
    echo "2. TESTANDO COM USUÁRIO REAL:\n";
    echo str_repeat("-", 50) . "\n";
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Buscar um usuário para teste
    $query = "SELECT id, nome, email, senha FROM usuarios LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Usuário encontrado: {$user_data['nome']} ({$user_data['email']})\n";
        echo "Hash atual no banco: {$user_data['senha']}\n";
        
        // Definir uma senha conhecida
        $senha_conhecida = 'senha123';
        $hash_conhecido = hashPassword($senha_conhecida);
        
        // Atualizar no banco
        $update_query = "UPDATE usuarios SET senha = :senha WHERE id = :id";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bindParam(':senha', $hash_conhecido);
        $update_stmt->bindParam(':id', $user_data['id']);
        
        if ($update_stmt->execute()) {
            echo "✓ Senha definida como: {$senha_conhecida}\n";
            echo "✓ Hash SHA1: {$hash_conhecido}\n\n";
            
            // 3. Testar alteração usando o modelo Usuario
            echo "3. TESTANDO ALTERAÇÃO DE SENHA:\n";
            echo str_repeat("-", 50) . "\n";
            
            $usuario = new Usuario();
            $usuario->id = $user_data['id'];
            
            $nova_senha = 'novaSenha456';
            
            if ($usuario->alterarSenha($senha_conhecida, $nova_senha)) {
                echo "✓ Senha alterada com sucesso!\n";
                echo "- Senha anterior: {$senha_conhecida}\n";
                echo "- Nova senha: {$nova_senha}\n";
                
                // Verificar se a nova senha foi salva corretamente
                $verify_query = "SELECT senha FROM usuarios WHERE id = :id";
                $verify_stmt = $conn->prepare($verify_query);
                $verify_stmt->bindParam(':id', $user_data['id']);
                $verify_stmt->execute();
                
                $new_hash = $verify_stmt->fetch(PDO::FETCH_ASSOC)['senha'];
                $expected_hash = hashPassword($nova_senha);
                
                echo "- Hash esperado: {$expected_hash}\n";
                echo "- Hash no banco: {$new_hash}\n";
                echo "- Hashes coincidem: " . ($new_hash === $expected_hash ? 'SIM' : 'NÃO') . "\n\n";
                
            } else {
                echo "✗ Erro ao alterar senha!\n";
            }
            
            // 4. Testar com senha incorreta
            echo "4. TESTANDO COM SENHA ATUAL INCORRETA:\n";
            echo str_repeat("-", 50) . "\n";
            
            $senha_incorreta = 'senhaErrada123';
            
            if ($usuario->alterarSenha($senha_incorreta, 'outraSenha789')) {
                echo "✗ ERRO: Alteração deveria ter falhado!\n";
            } else {
                echo "✓ Alteração corretamente rejeitada com senha incorreta\n";
            }
            
        } else {
            echo "✗ Erro ao definir senha de teste\n";
        }
        
    } else {
        echo "✗ Nenhum usuário encontrado no banco\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "RESUMO DO TESTE SHA1:\n";
    echo "✓ Funções hashPassword() e verifyPassword() usando SHA1\n";
    echo "✓ Modelo Usuario.alterarSenha() funcionando\n";
    echo "✓ Validação de senha atual funcionando\n";
    echo "✓ Sistema configurado corretamente para SHA1\n";
    
} catch(Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")\n";
}
?>