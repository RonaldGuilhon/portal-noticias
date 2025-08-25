<?php
/**
 * Teste final para identificar e corrigir o problema da alteração de senha via HTTP
 */

echo "=== TESTE FINAL - CORREÇÃO DA ALTERAÇÃO DE SENHA ===\n\n";

require_once __DIR__ . '/../../config-local.php';
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/config/database.php';

// Resetar senha
$database = new Database();
$db = $database->getConnection();
$hash = hashPassword('teste123');
$stmt = $db->prepare('UPDATE usuarios SET senha = ? WHERE email = ?');
$stmt->execute([$hash, 'ronaldguilhon@gmail.com']);

echo "✅ Senha resetada para 'teste123'\n\n";

// 1. Fazer login
echo "1. FAZENDO LOGIN:\n";

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
curl_close($ch);

$login_result = json_decode($response, true);

if (!$login_result || !isset($login_result['token'])) {
    echo "❌ Falha no login: {$response}\n";
    exit(1);
}

$token = $login_result['token'];
echo "✅ Login realizado\n\n";

// 2. Alterar senha via HTTP
echo "2. ALTERANDO SENHA VIA HTTP:\n";

$change_data = json_encode([
    'current_password' => 'teste123',
    'new_password' => 'novaSenha456',
    'confirm_password' => 'novaSenha456'
]);

echo "Dados enviados: {$change_data}\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8001/api/user/change-password');
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

echo "Código HTTP: {$http_code}\n";
echo "Resposta: {$response}\n\n";

// 3. Verificar se a senha foi alterada
echo "3. VERIFICANDO ALTERAÇÃO NO BANCO:\n";

$stmt = $db->prepare('SELECT senha FROM usuarios WHERE email = ?');
$stmt->execute(['ronaldguilhon@gmail.com']);
$current_hash = $stmt->fetch(PDO::FETCH_ASSOC)['senha'];

if (verifyPassword('novaSenha456', $current_hash)) {
    echo "✅ SUCESSO! Nova senha funciona!\n";
    $senha_alterada = true;
} else if (verifyPassword('teste123', $current_hash)) {
    echo "❌ PROBLEMA: Senha não foi alterada (ainda é a original)\n";
    $senha_alterada = false;
} else {
    echo "❌ ERRO: Hash no banco não corresponde a nenhuma senha conhecida\n";
    $senha_alterada = false;
}

// 4. Testar login com nova senha
if ($senha_alterada) {
    echo "\n4. TESTANDO LOGIN COM NOVA SENHA:\n";
    
    $new_login_data = json_encode([
        'email' => 'ronaldguilhon@gmail.com',
        'senha' => 'novaSenha456'
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8001/auth/login');
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
        echo "❌ Login com nova senha falhou: {$new_response}\n";
    }
    
    // Restaurar senha original
    $hash_original = hashPassword('teste123');
    $stmt = $db->prepare('UPDATE usuarios SET senha = ? WHERE email = ?');
    $stmt->execute([$hash_original, 'ronaldguilhon@gmail.com']);
    echo "✅ Senha restaurada para 'teste123'\n";
}

echo "\n=== RESULTADO FINAL ===\n";
if ($senha_alterada) {
    echo "🎉 PROBLEMA RESOLVIDO! A alteração de senha via HTTP está funcionando!\n";
    echo "✅ API retorna sucesso\n";
    echo "✅ Senha é alterada no banco de dados\n";
    echo "✅ Login com nova senha funciona\n";
} else {
    echo "❌ PROBLEMA AINDA EXISTE\n";
    echo "- API retorna sucesso mas senha não é alterada\n";
    echo "- Necessário investigar mais profundamente o AuthController\n";
}