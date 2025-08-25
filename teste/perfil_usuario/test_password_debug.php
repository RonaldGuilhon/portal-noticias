<?php
/**
 * Teste de debug para alteração de senha
 * Verifica cada etapa do processo
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== TESTE DE DEBUG - ALTERAÇÃO DE SENHA ===\n\n";

try {
    require_once __DIR__ . '/../../config-local.php';
    require_once __DIR__ . '/../../backend/config/config.php';
    require_once __DIR__ . '/../../backend/config/database.php';
    require_once __DIR__ . '/../../backend/models/Usuario.php';
    
    $database = new Database();
    $db = $database->getConnection();
    $usuario = new Usuario($db);
    
    // 1. Buscar usuário de teste
    $email = 'ronaldguilhon@gmail.com';
    $query = "SELECT id, email, senha FROM usuarios WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_data) {
        echo "❌ Usuário não encontrado!\n";
        exit(1);
    }
    
    echo "✅ Usuário encontrado: {$user_data['email']}\n";
    echo "📋 ID: {$user_data['id']}\n";
    echo "📋 Hash atual: {$user_data['senha']}\n\n";
    
    // 2. Definir senha conhecida
    $senha_teste = 'teste123';
    $nova_senha_hash = hashPassword($senha_teste);
    
    $query = "UPDATE usuarios SET senha = :senha WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':senha', $nova_senha_hash);
    $stmt->bindParam(':id', $user_data['id']);
    $result = $stmt->execute();
    
    echo "✅ Senha definida para: {$senha_teste}\n";
    echo "📋 Hash: {$nova_senha_hash}\n";
    echo "📋 Rows affected: " . $stmt->rowCount() . "\n\n";
    
    // 3. Testar método alterarSenha com debug
    $usuario->id = $user_data['id'];
    
    echo "=== TESTANDO MÉTODO alterarSenha ===\n";
    echo "📋 Usuario ID: {$usuario->id}\n";
    echo "📋 Senha atual: {$senha_teste}\n";
    echo "📋 Nova senha: novaSenha456\n\n";
    
    // Verificar senha atual primeiro
    $query = "SELECT senha FROM usuarios WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $usuario->id);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "📋 Hash no banco antes: {$row['senha']}\n";
    echo "📋 Verificação senha atual: " . (verifyPassword($senha_teste, $row['senha']) ? 'OK' : 'FALHA') . "\n\n";
    
    // Executar alteração
    $resultado = $usuario->alterarSenha($senha_teste, 'novaSenha456');
    
    echo "📋 Resultado alterarSenha: " . ($resultado ? 'TRUE' : 'FALSE') . "\n\n";
    
    // Verificar se realmente alterou
    $query = "SELECT senha FROM usuarios WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $usuario->id);
    $stmt->execute();
    $row_after = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "📋 Hash no banco depois: {$row_after['senha']}\n";
    echo "📋 Hash mudou: " . ($row['senha'] !== $row_after['senha'] ? 'SIM' : 'NÃO') . "\n";
    echo "📋 Nova senha verifica: " . (verifyPassword('novaSenha456', $row_after['senha']) ? 'SIM' : 'NÃO') . "\n";
    echo "📋 Senha antiga ainda funciona: " . (verifyPassword($senha_teste, $row_after['senha']) ? 'SIM' : 'NÃO') . "\n\n";
    
    // 4. Teste manual da query UPDATE
    echo "=== TESTE MANUAL DA QUERY UPDATE ===\n";
    $nova_senha_manual = 'senhaManual789';
    $hash_manual = hashPassword($nova_senha_manual);
    
    $query = "UPDATE usuarios SET senha = :senha WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':senha', $hash_manual);
    $stmt->bindParam(':id', $usuario->id);
    $result_manual = $stmt->execute();
    $rows_affected = $stmt->rowCount();
    
    echo "📋 Query executada: " . ($result_manual ? 'OK' : 'FALHA') . "\n";
    echo "📋 Rows affected: {$rows_affected}\n";
    
    // Verificar resultado
    $query = "SELECT senha FROM usuarios WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $usuario->id);
    $stmt->execute();
    $row_manual = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "📋 Hash após update manual: {$row_manual['senha']}\n";
    echo "📋 Senha manual verifica: " . (verifyPassword($nova_senha_manual, $row_manual['senha']) ? 'SIM' : 'NÃO') . "\n\n";
    
    echo "=== CONCLUSÃO ===\n";
    if ($resultado && verifyPassword('novaSenha456', $row_after['senha'])) {
        echo "✅ Método alterarSenha está funcionando corretamente!\n";
    } else {
        echo "❌ Problema identificado no método alterarSenha!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "📋 Trace: " . $e->getTraceAsString() . "\n";
}