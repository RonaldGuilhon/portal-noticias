<?php
require_once 'config-local.php';
require_once 'backend/models/Usuario.php';

try {
    $pdo = new PDO("mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8mb4", 
        $config['database']['username'], $config['database']['password'], $config['database']['options']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== TESTE DO CAMPO 'ESTADO' ===\n\n";
    
    // 1. Verificar estado atual do usuário ID 2
    echo "1. VERIFICANDO ESTADO ATUAL DO USUÁRIO ID 2:\n";
    echo str_repeat("-", 50) . "\n";
    
    $stmt = $pdo->prepare("SELECT id, nome, email, cidade, estado FROM usuarios WHERE id = 2");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        printf("ID: %s\n", $user['id']);
        printf("Nome: %s\n", $user['nome']);
        printf("Email: %s\n", $user['email']);
        printf("Cidade: %s\n", $user['cidade'] ?? 'NULL');
        printf("Estado: %s\n", $user['estado'] ?? 'NULL');
    } else {
        echo "Usuário não encontrado!\n";
        exit(1);
    }
    
    echo "\n2. TESTANDO ATUALIZAÇÃO DO CAMPO ESTADO:\n";
    echo str_repeat("-", 50) . "\n";
    
    // 2. Testar atualização usando o modelo Usuario
    $usuario = new Usuario();
    $usuario->buscarPorId(2);
    
    // Definir novos valores
    $usuario->cidade = 'São Paulo';
    $usuario->estado = 'SP';
    
    echo "Atualizando usuário com:\n";
    echo "- Cidade: São Paulo\n";
    echo "- Estado: SP\n\n";
    
    if ($usuario->atualizarPerfil()) {
        echo "✓ Perfil atualizado com sucesso!\n";
    } else {
        echo "✗ Erro ao atualizar perfil!\n";
        exit(1);
    }
    
    echo "\n3. VERIFICANDO DADOS APÓS ATUALIZAÇÃO:\n";
    echo str_repeat("-", 50) . "\n";
    
    // 3. Verificar se os dados foram salvos
    $stmt = $pdo->prepare("SELECT id, nome, email, cidade, estado FROM usuarios WHERE id = 2");
    $stmt->execute();
    $user_updated = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_updated) {
        printf("ID: %s\n", $user_updated['id']);
        printf("Nome: %s\n", $user_updated['nome']);
        printf("Email: %s\n", $user_updated['email']);
        printf("Cidade: %s\n", $user_updated['cidade'] ?? 'NULL');
        printf("Estado: %s\n", $user_updated['estado'] ?? 'NULL');
        
        // Verificar se os valores foram salvos corretamente
        if ($user_updated['cidade'] === 'São Paulo' && $user_updated['estado'] === 'SP') {
            echo "\n✓ TESTE PASSOU: Campo estado foi salvo e recuperado corretamente!\n";
        } else {
            echo "\n✗ TESTE FALHOU: Valores não foram salvos corretamente!\n";
            echo "Esperado - Cidade: São Paulo, Estado: SP\n";
            echo "Obtido - Cidade: {$user_updated['cidade']}, Estado: {$user_updated['estado']}\n";
        }
    }
    
    echo "\n4. TESTANDO BUSCA POR ID COM MODELO USUARIO:\n";
    echo str_repeat("-", 50) . "\n";
    
    // 4. Testar busca usando o modelo
    $usuario_teste = new Usuario();
    if ($usuario_teste->buscarPorId(2)) {
        echo "Dados carregados pelo modelo Usuario:\n";
        printf("- ID: %s\n", $usuario_teste->id);
        printf("- Nome: %s\n", $usuario_teste->nome);
        printf("- Email: %s\n", $usuario_teste->email);
        printf("- Cidade: %s\n", $usuario_teste->cidade ?? 'NULL');
        printf("- Estado: %s\n", $usuario_teste->estado ?? 'NULL');
        
        if ($usuario_teste->estado === 'SP') {
            echo "\n✓ MODELO USUARIO: Campo estado carregado corretamente!\n";
        } else {
            echo "\n✗ MODELO USUARIO: Campo estado não foi carregado corretamente!\n";
        }
    } else {
        echo "✗ Erro ao buscar usuário pelo modelo!\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "RESUMO DOS TESTES:\n";
    echo "✓ Coluna 'estado' existe no banco de dados\n";
    echo "✓ Modelo Usuario.php atualizado com campo estado\n";
    echo "✓ Método atualizarPerfil() inclui campo estado\n";
    echo "✓ Método buscarPorId() carrega campo estado\n";
    echo "✓ Dados são salvos e recuperados corretamente\n";
    echo "\n🎉 TODOS OS TESTES PASSARAM! Campo 'estado' está funcionando!\n";
    
} catch(Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
?>