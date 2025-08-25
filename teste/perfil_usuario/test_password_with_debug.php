<?php
/**
 * Teste com debug detalhado do método alterarSenha
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== TESTE COM DEBUG DETALHADO ===\n\n";

try {
    require_once __DIR__ . '/../../config-local.php';
    require_once __DIR__ . '/../../backend/config/config.php';
    require_once __DIR__ . '/../../backend/config/database.php';
    require_once __DIR__ . '/../../backend/models/Usuario.php';
    require_once __DIR__ . '/../../backend/middleware/AuthMiddleware.php';
    require_once __DIR__ . '/../../backend/utils/JWTHelper.php';
    
    // 1. Fazer login para obter token
    echo "1. FAZENDO LOGIN VIA HTTP:\n";
    echo "--------------------------------------------------\n";
    
    $login_data = json_encode([
        'email' => 'ronaldguilhon@gmail.com',
        'senha' => 'teste123'
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8001/auth/login');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $login_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($login_data)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $login_result = json_decode($response, true);
    
    if (!$login_result || !isset($login_result['token'])) {
        echo "❌ Falha no login: {$response}\n";
        exit(1);
    }
    
    $token = $login_result['token'];
    $usuario_id = $login_result['usuario']['id'];
    
    echo "✅ Login realizado com sucesso!\n";
    echo "- Token: " . substr($token, 0, 20) . "...\n";
    echo "- Usuário ID: {$usuario_id}\n\n";
    
    // 2. Testar verificação do token
    echo "2. TESTANDO VERIFICAÇÃO DO TOKEN:\n";
    echo "--------------------------------------------------\n";
    
    $authMiddleware = new AuthMiddleware();
    
    // Simular headers de autorização
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
    
    $resultado = $authMiddleware->verificarToken();
    
    echo "- Token válido: " . ($resultado['valido'] ? 'SIM' : 'NÃO') . "\n";
    if ($resultado['valido']) {
        echo "- Usuário do token: {$resultado['usuario']['id']}\n";
        echo "- Email do token: {$resultado['usuario']['email']}\n";
    } else {
        echo "- Erro: {$resultado['erro']}\n";
    }
    echo "\n";
    
    // 3. Simular sessão
    echo "3. SIMULANDO SESSÃO:\n";
    echo "--------------------------------------------------\n";
    
    session_start();
    $_SESSION['usuario_id'] = $usuario_id;
    $_SESSION['usuario_nome'] = $login_result['usuario']['nome'];
    $_SESSION['usuario_email'] = $login_result['usuario']['email'];
    $_SESSION['usuario_tipo'] = $login_result['usuario']['tipo'];
    $_SESSION['logado'] = true;
    
    echo "- Sessão iniciada\n";
    echo "- Usuario ID na sessão: {$_SESSION['usuario_id']}\n\n";
    
    // 4. Testar método alterarSenha diretamente
    echo "4. TESTANDO MÉTODO alterarSenha DIRETAMENTE:\n";
    echo "--------------------------------------------------\n";
    
    $database = new Database();
    $db = $database->getConnection();
    $usuario = new Usuario($db);
    
    $usuario->id = $usuario_id;
    
    echo "- Usuario ID definido: {$usuario->id}\n";
    
    // Verificar senha atual no banco
    $query = "SELECT senha FROM usuarios WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $usuario->id);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "- Hash atual no banco: {$row['senha']}\n";
    echo "- Senha 'teste123' verifica: " . (verifyPassword('teste123', $row['senha']) ? 'SIM' : 'NÃO') . "\n";
    
    // Executar alteração
    $resultado_alteracao = $usuario->alterarSenha('teste123', 'novaSenha456');
    
    echo "- Resultado alterarSenha: " . ($resultado_alteracao ? 'TRUE' : 'FALSE') . "\n";
    
    // Verificar se alterou
    $stmt->execute();
    $row_after = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "- Hash após alteração: {$row_after['senha']}\n";
    echo "- Hash mudou: " . ($row['senha'] !== $row_after['senha'] ? 'SIM' : 'NÃO') . "\n";
    echo "- Nova senha verifica: " . (verifyPassword('novaSenha456', $row_after['senha']) ? 'SIM' : 'NÃO') . "\n\n";
    
    // 5. Simular requisição HTTP completa
    echo "5. SIMULANDO REQUISIÇÃO HTTP COMPLETA:\n";
    echo "--------------------------------------------------\n";
    
    // Resetar senha para teste
    $hash_reset = hashPassword('teste123');
    $query = "UPDATE usuarios SET senha = :senha WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':senha', $hash_reset);
    $stmt->bindParam(':id', $usuario_id);
    $stmt->execute();
    
    echo "- Senha resetada para 'teste123'\n";
    
    // Simular dados da requisição
    $change_data = json_encode([
        'current_password' => 'teste123',
        'new_password' => 'novaSenha456',
        'confirm_password' => 'novaSenha456'
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8001/api/user/profile?action=change-password');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $change_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
        'Content-Length: ' . strlen($change_data)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "- Código HTTP: {$http_code}\n";
    echo "- Resposta: {$response}\n";
    
    // Verificar resultado no banco
    $query = "SELECT senha FROM usuarios WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $usuario_id);
    $stmt->execute();
    $row_final = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "- Hash final no banco: {$row_final['senha']}\n";
    echo "- Nova senha verifica: " . (verifyPassword('novaSenha456', $row_final['senha']) ? 'SIM' : 'NÃO') . "\n";
    echo "- Senha antiga ainda funciona: " . (verifyPassword('teste123', $row_final['senha']) ? 'SIM' : 'NÃO') . "\n\n";
    
    echo "============================================================\n";
    echo "CONCLUSÃO:\n";
    
    if ($http_code === 200 && verifyPassword('novaSenha456', $row_final['senha'])) {
        echo "✅ SISTEMA FUNCIONANDO CORRETAMENTE!\n";
    } else {
        echo "❌ PROBLEMA CONFIRMADO!\n";
        echo "- API retorna código {$http_code}\n";
        echo "- Senha foi alterada: " . (verifyPassword('novaSenha456', $row_final['senha']) ? 'SIM' : 'NÃO') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "📋 Trace: " . $e->getTraceAsString() . "\n";
}