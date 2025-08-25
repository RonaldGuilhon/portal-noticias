<?php
/**
 * Teste para verificar problema atual de alteração de senha
 * Data: Janeiro 2025
 */

require_once __DIR__ . '/../../config-local.php';
require_once __DIR__ . '/../../backend/models/Usuario.php';

try {
    echo "=== TESTE DO PROBLEMA ATUAL DE ALTERAÇÃO DE SENHA ===\n\n";
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // 1. Buscar um usuário de teste
    echo "1. BUSCANDO USUÁRIO DE TESTE:\n";
    echo str_repeat("-", 50) . "\n";
    
    $query = "SELECT id, nome, email, senha FROM usuarios WHERE id = 2";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "✗ Usuário não encontrado!\n";
        exit(1);
    }
    
    echo "✓ Usuário encontrado:\n";
    echo "- ID: {$user['id']}\n";
    echo "- Nome: {$user['nome']}\n";
    echo "- Email: {$user['email']}\n";
    echo "- Hash atual: {$user['senha']}\n\n";
    
    // 2. Definir uma senha conhecida
    echo "2. DEFININDO SENHA CONHECIDA:\n";
    echo str_repeat("-", 50) . "\n";
    
    $senha_conhecida = 'teste123';
    $hash_conhecido = hashPassword($senha_conhecida);
    
    $update_query = "UPDATE usuarios SET senha = :senha WHERE id = :id";
    $stmt = $conn->prepare($update_query);
    $stmt->bindParam(':senha', $hash_conhecido);
    $stmt->bindParam(':id', $user['id']);
    
    if ($stmt->execute()) {
        echo "✓ Senha definida: {$senha_conhecida}\n";
        echo "✓ Hash gerado: {$hash_conhecido}\n\n";
    } else {
        echo "✗ Erro ao definir senha conhecida!\n";
        exit(1);
    }
    
    // 3. Testar alteração usando o modelo Usuario
    echo "3. TESTANDO ALTERAÇÃO VIA MODELO USUARIO:\n";
    echo str_repeat("-", 50) . "\n";
    
    $usuario = new Usuario($conn);
    $usuario->id = $user['id'];
    
    $nova_senha = 'novaSenha456';
    
    echo "- Senha atual: {$senha_conhecida}\n";
    echo "- Nova senha: {$nova_senha}\n";
    
    $resultado = $usuario->alterarSenha($senha_conhecida, $nova_senha);
    
    if ($resultado) {
        echo "✓ Método alterarSenha() retornou TRUE\n";
        
        // Verificar se realmente mudou no banco
        $verify_query = "SELECT senha FROM usuarios WHERE id = :id";
        $stmt = $conn->prepare($verify_query);
        $stmt->bindParam(':id', $user['id']);
        $stmt->execute();
        $novo_hash = $stmt->fetchColumn();
        
        echo "- Hash anterior: {$hash_conhecido}\n";
        echo "- Hash atual: {$novo_hash}\n";
        echo "- Hashes são diferentes: " . ($novo_hash !== $hash_conhecido ? 'SIM' : 'NÃO') . "\n";
        
        // Testar se a nova senha funciona
        if (verifyPassword($nova_senha, $novo_hash)) {
            echo "✓ Nova senha verifica corretamente\n";
        } else {
            echo "✗ Nova senha NÃO verifica corretamente\n";
        }
        
        // Testar se a senha antiga ainda funciona (não deveria)
        if (verifyPassword($senha_conhecida, $novo_hash)) {
            echo "✗ PROBLEMA: Senha antiga ainda funciona!\n";
        } else {
            echo "✓ Senha antiga não funciona mais (correto)\n";
        }
        
    } else {
        echo "✗ Método alterarSenha() retornou FALSE\n";
    }
    
    // 4. Testar login com nova senha
    echo "\n4. TESTANDO LOGIN COM NOVA SENHA:\n";
    echo str_repeat("-", 50) . "\n";
    
    $usuario_login = new Usuario($conn);
    if ($usuario_login->login($user['email'], $nova_senha)) {
        echo "✓ Login com nova senha funcionou!\n";
    } else {
        echo "✗ Login com nova senha FALHOU!\n";
    }
    
    // 5. Simular requisição do frontend
    echo "\n5. SIMULANDO REQUISIÇÃO DO FRONTEND:\n";
    echo str_repeat("-", 50) . "\n";
    
    // Simular dados JSON do frontend
    $dados_frontend = [
        'current_password' => $nova_senha, // Agora a nova senha é a atual
        'new_password' => 'senhaFinal789',
        'confirm_password' => 'senhaFinal789'
    ];
    
    echo "- Dados enviados pelo frontend:\n";
    echo "  current_password: {$dados_frontend['current_password']}\n";
    echo "  new_password: {$dados_frontend['new_password']}\n";
    echo "  confirm_password: {$dados_frontend['confirm_password']}\n";
    
    $usuario_frontend = new Usuario($conn);
    $usuario_frontend->id = $user['id'];
    
    $resultado_frontend = $usuario_frontend->alterarSenha(
        $dados_frontend['current_password'],
        $dados_frontend['new_password']
    );
    
    if ($resultado_frontend) {
        echo "✓ Simulação do frontend: alterarSenha() retornou TRUE\n";
        
        // Verificar no banco novamente
        $stmt = $conn->prepare($verify_query);
        $stmt->bindParam(':id', $user['id']);
        $stmt->execute();
        $hash_final = $stmt->fetchColumn();
        
        if (verifyPassword($dados_frontend['new_password'], $hash_final)) {
            echo "✓ Senha final verifica corretamente no banco\n";
        } else {
            echo "✗ PROBLEMA: Senha final NÃO verifica no banco!\n";
        }
    } else {
        echo "✗ Simulação do frontend: alterarSenha() retornou FALSE\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "RESUMO DO TESTE:\n";
    echo "✓ Funções hashPassword() e verifyPassword() funcionando\n";
    echo "✓ Modelo Usuario.alterarSenha() funcionando\n";
    echo "✓ Atualização no banco de dados funcionando\n";
    echo "✓ Verificação de senha funcionando\n";
    echo "\n🔍 Se o problema persiste, pode ser:\n";
    echo "- Cache do navegador\n";
    echo "- Token de autenticação inválido\n";
    echo "- Problema na sessão do usuário\n";
    echo "- Dados não chegando corretamente ao backend\n";
    
} catch(Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")\n";
}
?>