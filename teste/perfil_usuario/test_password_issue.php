<?php
require_once 'config-local.php';
require_once 'backend/models/Usuario.php';

try {
    echo "=== TESTE SIMPLES DO PROBLEMA DE ALTERAÇÃO DE SENHA ===\n\n";
    
    // 1. Testar conexão com banco
    echo "1. TESTANDO CONEXÃO COM BANCO:\n";
    echo str_repeat("-", 40) . "\n";
    
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "✓ Conexão com banco estabelecida!\n\n";
    } else {
        echo "✗ Erro na conexão com banco!\n";
        exit(1);
    }
    
    // 2. Buscar usuário
    echo "2. BUSCANDO USUÁRIO ID 2:\n";
    echo str_repeat("-", 40) . "\n";
    
    $query = "SELECT id, nome, email, bio, cidade, estado, telefone FROM usuarios WHERE id = 2";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_data) {
        echo "✓ Usuário encontrado!\n";
        echo "- ID: " . $user_data['id'] . "\n";
        echo "- Nome: " . ($user_data['nome'] ?? 'NULL') . "\n";
        echo "- Email: " . ($user_data['email'] ?? 'NULL') . "\n";
        echo "- Bio: " . ($user_data['bio'] ?? 'NULL') . "\n";
        echo "- Cidade: " . ($user_data['cidade'] ?? 'NULL') . "\n";
        echo "- Estado: " . ($user_data['estado'] ?? 'NULL') . "\n";
        echo "- Telefone: " . ($user_data['telefone'] ?? 'NULL') . "\n\n";
    } else {
        echo "✗ Usuário não encontrado!\n";
        exit(1);
    }
    
    // 3. Testar modelo Usuario
    echo "3. TESTANDO MODELO USUARIO:\n";
    echo str_repeat("-", 40) . "\n";
    
    $usuario = new Usuario();
    
    if ($usuario->buscarPorId(2)) {
        echo "✓ Usuário carregado via modelo!\n";
        echo "- Nome: " . ($usuario->nome ?? 'NULL') . "\n";
        echo "- Email: " . ($usuario->email ?? 'NULL') . "\n";
        echo "- Bio: " . ($usuario->bio ?? 'NULL') . "\n\n";
    } else {
        echo "✗ Erro ao carregar usuário via modelo!\n";
        exit(1);
    }
    
    // 4. Testar alteração de senha diretamente no banco
    echo "4. TESTANDO ALTERAÇÃO DE SENHA DIRETA NO BANCO:\n";
    echo str_repeat("-", 40) . "\n";
    
    $senha_teste = 'senha123';
    $senha_hash = hashPassword($senha_teste);
    
    $query = "UPDATE usuarios SET senha = :senha WHERE id = 2";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':senha', $senha_hash);
    
    if ($stmt->execute()) {
        echo "✓ Senha atualizada diretamente no banco!\n";
        echo "- Nova senha: {$senha_teste}\n\n";
    } else {
        echo "✗ Erro ao atualizar senha no banco!\n";
        exit(1);
    }
    
    // 5. Verificar se outros dados foram preservados
    echo "5. VERIFICANDO PRESERVAÇÃO DOS DADOS:\n";
    echo str_repeat("-", 40) . "\n";
    
    $query = "SELECT id, nome, email, bio, cidade, estado, telefone FROM usuarios WHERE id = 2";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $user_data_after = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_data_after) {
        echo "✓ Dados após alteração de senha:\n";
        echo "- Nome: " . ($user_data_after['nome'] ?? 'NULL') . "\n";
        echo "- Email: " . ($user_data_after['email'] ?? 'NULL') . "\n";
        echo "- Bio: " . ($user_data_after['bio'] ?? 'NULL') . "\n";
        echo "- Cidade: " . ($user_data_after['cidade'] ?? 'NULL') . "\n";
        echo "- Estado: " . ($user_data_after['estado'] ?? 'NULL') . "\n";
        echo "- Telefone: " . ($user_data_after['telefone'] ?? 'NULL') . "\n\n";
        
        // Comparar
        echo "COMPARAÇÃO:\n";
        echo "- Nome: " . ($user_data['nome'] === $user_data_after['nome'] ? '✓ PRESERVADO' : '✗ ALTERADO') . "\n";
        echo "- Bio: " . ($user_data['bio'] === $user_data_after['bio'] ? '✓ PRESERVADO' : '✗ ALTERADO') . "\n";
        echo "- Cidade: " . ($user_data['cidade'] === $user_data_after['cidade'] ? '✓ PRESERVADO' : '✗ ALTERADO') . "\n";
        echo "- Estado: " . ($user_data['estado'] === $user_data_after['estado'] ? '✓ PRESERVADO' : '✗ ALTERADO') . "\n";
    }
    
    echo "\n=== TESTE CONCLUÍDO ===\n";
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>