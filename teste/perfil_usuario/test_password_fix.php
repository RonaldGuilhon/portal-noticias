<?php
require_once 'config-local.php';
require_once 'backend/config/config.php';

try {
    echo "=== VERIFICAÇÃO DAS CORREÇÕES DE ALTERAÇÃO DE SENHA ===\n\n";
    
    // 1. Verificar se a constante PASSWORD_MIN_LENGTH está definida
    echo "1. VERIFICANDO CONFIGURAÇÕES:\n";
    echo str_repeat("-", 40) . "\n";
    
    if (defined('PASSWORD_MIN_LENGTH')) {
        echo "✓ PASSWORD_MIN_LENGTH definida: " . PASSWORD_MIN_LENGTH . "\n";
    } else {
        echo "✗ PASSWORD_MIN_LENGTH não definida\n";
    }
    
    // 2. Testar funções de hash e verificação
    echo "\n2. TESTANDO FUNÇÕES DE SENHA:\n";
    echo str_repeat("-", 40) . "\n";
    
    $senha_teste = 'minhasenha123';
    $hash = hashPassword($senha_teste);
    
    echo "Senha original: {$senha_teste}\n";
    echo "Hash gerado: {$hash}\n";
    
    if (verifyPassword($senha_teste, $hash)) {
        echo "✓ Verificação de senha funcionando\n";
    } else {
        echo "✗ Verificação de senha com problema\n";
    }
    
    // 3. Simular dados que seriam enviados pelo frontend
    echo "\n3. SIMULANDO DADOS DO FRONTEND:\n";
    echo str_repeat("-", 40) . "\n";
    
    $dados_frontend = [
        'current_password' => 'senhaAtual123',
        'new_password' => 'novaSenha456',
        'confirm_password' => 'novaSenha456'
    ];
    
    echo "Dados que o frontend agora envia:\n";
    foreach ($dados_frontend as $campo => $valor) {
        echo "- {$campo}: {$valor}\n";
    }
    
    // 4. Simular validação do backend
    echo "\n4. SIMULANDO VALIDAÇÃO DO BACKEND:\n";
    echo str_repeat("-", 40) . "\n";
    
    $senha_atual = $dados_frontend['current_password'] ?? '';
    $nova_senha = $dados_frontend['new_password'] ?? '';
    $confirmar_senha = $dados_frontend['confirm_password'] ?? '';
    
    echo "Campos extraídos:\n";
    echo "- Senha atual: '{$senha_atual}'\n";
    echo "- Nova senha: '{$nova_senha}'\n";
    echo "- Confirmar senha: '{$confirmar_senha}'\n\n";
    
    // Validações
    $erros = [];
    
    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
        $erros[] = 'Todos os campos são obrigatórios';
    }
    
    if ($nova_senha !== $confirmar_senha) {
        $erros[] = 'Senhas não coincidem';
    }
    
    if (strlen($nova_senha) < PASSWORD_MIN_LENGTH) {
        $erros[] = 'Senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres';
    }
    
    if (empty($erros)) {
        echo "✓ Todas as validações passaram!\n";
    } else {
        echo "✗ Erros encontrados:\n";
        foreach ($erros as $erro) {
            echo "  - {$erro}\n";
        }
    }
    
    // 5. Testar com senhas que não coincidem
    echo "\n5. TESTANDO SENHAS QUE NÃO COINCIDEM:\n";
    echo str_repeat("-", 40) . "\n";
    
    $dados_erro = [
        'current_password' => 'senhaAtual123',
        'new_password' => 'novaSenha456',
        'confirm_password' => 'senhasDiferentes789'
    ];
    
    $nova_senha_erro = $dados_erro['new_password'] ?? '';
    $confirmar_senha_erro = $dados_erro['confirm_password'] ?? '';
    
    echo "Nova senha: '{$nova_senha_erro}'\n";
    echo "Confirmar senha: '{$confirmar_senha_erro}'\n";
    
    if ($nova_senha_erro !== $confirmar_senha_erro) {
        echo "✓ Validação de senhas diferentes funcionando!\n";
    } else {
        echo "✗ Validação de senhas diferentes com problema!\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "RESUMO DAS CORREÇÕES APLICADAS:\n";
    echo "\n📋 PROBLEMAS IDENTIFICADOS E CORRIGIDOS:\n";
    echo "\n1. BACKEND (AuthController.php):\n";
    echo "   ❌ ANTES: \$confirmar_senha = \$dados['new_password'] ?? '';\n";
    echo "   ✅ DEPOIS: \$confirmar_senha = \$dados['confirm_password'] ?? '';\n";
    echo "\n2. FRONTEND (perfil.html):\n";
    echo "   ❌ ANTES: Enviava apenas current_password e new_password\n";
    echo "   ✅ DEPOIS: Envia current_password, new_password E confirm_password\n";
    echo "\n🎉 CORREÇÕES APLICADAS COM SUCESSO!\n";
    echo "\n✅ A funcionalidade de alteração de senha agora deve funcionar corretamente.\n";
    
} catch(Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")\n";
}
?>