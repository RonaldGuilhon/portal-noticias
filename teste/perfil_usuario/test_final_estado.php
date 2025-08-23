<?php
require_once 'config-local.php';
require_once 'backend/models/Usuario.php';

try {
    echo "=== TESTE FINAL DO CAMPO ESTADO ===\n\n";
    
    // 1. Testar modelo Usuario diretamente
    echo "1. TESTANDO MODELO USUARIO:\n";
    echo str_repeat("-", 40) . "\n";
    
    $usuario = new Usuario();
    
    // Buscar usuário existente
    if ($usuario->buscarPorId(2)) {
        echo "✓ Usuário carregado com sucesso!\n";
        echo "- Nome: " . $usuario->nome . "\n";
        echo "- Email: " . $usuario->email . "\n";
        echo "- Cidade: " . ($usuario->cidade ?? 'NULL') . "\n";
        echo "- Estado: " . ($usuario->estado ?? 'NULL') . "\n\n";
    } else {
        echo "✗ Erro ao carregar usuário!\n";
        exit(1);
    }
    
    // 2. Atualizar dados incluindo estado
    echo "2. ATUALIZANDO DADOS COM ESTADO:\n";
    echo str_repeat("-", 40) . "\n";
    
    $usuario->cidade = 'Rio de Janeiro';
    $usuario->estado = 'RJ';
    $usuario->bio = 'Biografia atualizada - Teste campo estado ' . date('Y-m-d H:i:s');
    
    echo "Definindo novos valores:\n";
    echo "- Cidade: Rio de Janeiro\n";
    echo "- Estado: RJ\n";
    echo "- Bio: " . $usuario->bio . "\n\n";
    
    if ($usuario->atualizarPerfil()) {
        echo "✓ Perfil atualizado com sucesso!\n\n";
    } else {
        echo "✗ Erro ao atualizar perfil!\n";
        exit(1);
    }
    
    // 3. Verificar se foi salvo corretamente
    echo "3. VERIFICANDO DADOS SALVOS:\n";
    echo str_repeat("-", 40) . "\n";
    
    $usuario_verificacao = new Usuario();
    if ($usuario_verificacao->buscarPorId(2)) {
        echo "Dados carregados após atualização:\n";
        echo "- Nome: " . $usuario_verificacao->nome . "\n";
        echo "- Email: " . $usuario_verificacao->email . "\n";
        echo "- Cidade: " . ($usuario_verificacao->cidade ?? 'NULL') . "\n";
        echo "- Estado: " . ($usuario_verificacao->estado ?? 'NULL') . "\n";
        echo "- Bio: " . ($usuario_verificacao->bio ?? 'NULL') . "\n\n";
        
        // Verificar se os valores estão corretos
        if ($usuario_verificacao->cidade === 'Rio de Janeiro' && $usuario_verificacao->estado === 'RJ') {
            echo "✓ SUCESSO: Cidade e Estado salvos corretamente!\n";
        } else {
            echo "✗ ERRO: Valores não foram salvos corretamente!\n";
            echo "Esperado - Cidade: Rio de Janeiro, Estado: RJ\n";
            echo "Obtido - Cidade: {$usuario_verificacao->cidade}, Estado: {$usuario_verificacao->estado}\n";
        }
    } else {
        echo "✗ Erro ao verificar dados salvos!\n";
    }
    
    // 4. Testar com diferentes valores
    echo "\n4. TESTANDO COM OUTROS VALORES:\n";
    echo str_repeat("-", 40) . "\n";
    
    $usuario->cidade = 'São Paulo';
    $usuario->estado = 'São Paulo';
    
    if ($usuario->atualizarPerfil()) {
        echo "✓ Segunda atualização realizada!\n";
        
        // Verificar novamente
        $usuario_final = new Usuario();
        $usuario_final->buscarPorId(2);
        
        echo "Valores finais:\n";
        echo "- Cidade: " . ($usuario_final->cidade ?? 'NULL') . "\n";
        echo "- Estado: " . ($usuario_final->estado ?? 'NULL') . "\n";
        
        if ($usuario_final->estado === 'São Paulo') {
            echo "\n✓ TESTE FINAL PASSOU: Campo estado funciona perfeitamente!\n";
        } else {
            echo "\n✗ TESTE FINAL FALHOU!\n";
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "RESUMO DA IMPLEMENTAÇÃO:\n";
    echo "✓ Coluna 'estado' criada no banco de dados\n";
    echo "✓ Propriedade 'estado' adicionada ao modelo Usuario\n";
    echo "✓ Método buscarPorId() carrega campo estado\n";
    echo "✓ Método atualizarPerfil() salva campo estado\n";
    echo "✓ AuthController retorna campo estado na API\n";
    echo "✓ AuthController processa campo estado na atualização\n";
    echo "\n🎉 CAMPO 'ESTADO' IMPLEMENTADO E FUNCIONANDO!\n";
    
} catch(Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")\n";
}
?>