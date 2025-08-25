<?php
/**
 * Teste que simula exatamente o fluxo do frontend
 * Inclui autenticação JWT e verificação de sessão
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== SIMULAÇÃO COMPLETA DO FRONTEND ===\n\n";

try {
    // Simular requisição HTTP real
    $base_url = 'http://localhost:8001';
    
    // 1. Fazer login para obter token
    echo "1. FAZENDO LOGIN:\n";
    echo "--------------------------------------------------\n";
    
    $login_data = json_encode([
        'email' => 'ronaldguilhon@gmail.com',
        'senha' => 'teste123'
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $base_url . '/auth/login');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $login_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($login_data)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Separar headers e body
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    
    echo "- Código HTTP: {$http_code}\n";
    echo "- Resposta: {$body}\n";
    
    $login_result = json_decode($body, true);
    
    if (!$login_result || !isset($login_result['token'])) {
        echo "❌ Falha no login!\n";
        exit(1);
    }
    
    $token = $login_result['token'];
    $usuario_id = $login_result['usuario']['id'];
    
    echo "✅ Login realizado com sucesso!\n";
    echo "- Token: " . substr($token, 0, 20) . "...\n";
    echo "- Usuário ID: {$usuario_id}\n\n";
    
    // 2. Definir senha conhecida no banco
    echo "2. PREPARANDO SENHA NO BANCO:\n";
    echo "--------------------------------------------------\n";
    
    require_once __DIR__ . '/../../config-local.php';
    require_once __DIR__ . '/../../backend/config/config.php';
    require_once __DIR__ . '/../../backend/config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $senha_teste = 'teste123';
    $hash_teste = hashPassword($senha_teste);
    
    $query = "UPDATE usuarios SET senha = :senha WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':senha', $hash_teste);
    $stmt->bindParam(':id', $usuario_id);
    $stmt->execute();
    
    echo "✅ Senha definida: {$senha_teste}\n\n";
    
    // 3. Alterar senha via API (simulando frontend)
    echo "3. ALTERANDO SENHA VIA API:\n";
    echo "--------------------------------------------------\n";
    
    $change_data = json_encode([
        'current_password' => 'teste123',
        'new_password' => 'novaSenha456',
        'confirm_password' => 'novaSenha456'
    ]);
    
    echo "- Dados enviados:\n";
    echo "  current_password: teste123\n";
    echo "  new_password: novaSenha456\n";
    echo "  confirm_password: novaSenha456\n\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $base_url . '/api/user/profile?action=change-password');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $change_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
        'Content-Length: ' . strlen($change_data)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    echo "- Código HTTP: {$http_code}\n";
    if ($curl_error) {
        echo "- Erro cURL: {$curl_error}\n";
    }
    echo "- Resposta: {$response}\n";
    
    $change_result = json_decode($response, true);
    
    if ($http_code === 200 && isset($change_result['success'])) {
        echo "✅ API retornou sucesso!\n\n";
    } else {
        echo "❌ API retornou erro!\n\n";
    }
    
    // 4. Verificar se realmente alterou no banco
    echo "4. VERIFICANDO ALTERAÇÃO NO BANCO:\n";
    echo "--------------------------------------------------\n";
    
    $query = "SELECT senha FROM usuarios WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $usuario_id);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "- Hash no banco: {$row['senha']}\n";
    
    $nova_senha_verifica = verifyPassword('novaSenha456', $row['senha']);
    $senha_antiga_verifica = verifyPassword('teste123', $row['senha']);
    
    if ($nova_senha_verifica) {
        echo "✅ Nova senha verifica no banco!\n";
    } else {
        echo "❌ PROBLEMA: Nova senha NÃO verifica no banco!\n";
    }
    
    if ($senha_antiga_verifica) {
        echo "❌ PROBLEMA: Senha antiga ainda funciona!\n";
    } else {
        echo "✅ Senha antiga não funciona mais!\n";
    }
    
    echo "\n";
    
    // 5. Testar login com nova senha
    echo "5. TESTANDO LOGIN COM NOVA SENHA:\n";
    echo "--------------------------------------------------\n";
    
    $new_login_data = json_encode([
        'email' => 'ronaldguilhon@gmail.com',
        'senha' => 'novaSenha456'
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $base_url . '/auth/login');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $new_login_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($new_login_data)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $new_response = curl_exec($ch);
    $new_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($new_http_code === 200) {
        echo "✅ Login com nova senha funcionou!\n";
    } else {
        echo "❌ Login com nova senha FALHOU!\n";
        echo "- Resposta: {$new_response}\n";
    }
    
    echo "\n============================================================\n";
    echo "RESULTADO FINAL:\n";
    
    if ($nova_senha_verifica && $new_http_code === 200) {
        echo "✅ SISTEMA FUNCIONANDO CORRETAMENTE!\n\n";
        echo "📋 O problema reportado pode ser:\n";
        echo "1. Cache do navegador - Solução: Ctrl+F5\n";
        echo "2. Token expirado - Solução: Logout/Login\n";
        echo "3. JavaScript desabilitado\n";
        echo "4. Extensões do navegador interferindo\n";
        echo "5. Problema de conectividade temporário\n\n";
        echo "💡 RECOMENDAÇÕES:\n";
        echo "- Pedir ao usuário para limpar cache do navegador\n";
        echo "- Fazer logout e login novamente\n";
        echo "- Testar em modo incógnito\n";
        echo "- Verificar console do navegador (F12)\n";
    } else {
        echo "❌ PROBLEMA CONFIRMADO NO SISTEMA!\n";
        echo "- API retorna sucesso mas senha não é alterada\n";
        echo "- Necessário investigar mais profundamente\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "📋 Trace: " . $e->getTraceAsString() . "\n";
}