<?php
/**
 * Teste para verificar se o problema está na sessão
 */

echo "=== TESTE DE DEBUG DA SESSÃO ===\n\n";

require_once __DIR__ . '/../../config-local.php';
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/controllers/AuthController.php';
require_once __DIR__ . '/../../backend/middleware/AuthMiddleware.php';

// Resetar senha
$database = new Database();
$db = $database->getConnection();
$hash = hashPassword('teste123');
$stmt = $db->prepare('UPDATE usuarios SET senha = ? WHERE email = ?');
$stmt->execute([$hash, 'ronaldguilhon@gmail.com']);

echo "✅ Senha resetada para 'teste123'\n\n";

// 1. Fazer login via HTTP para obter token
echo "1. FAZENDO LOGIN VIA HTTP:\n";

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
echo "✅ Login realizado, token obtido\n";
echo "Token: " . substr($token, 0, 50) . "...\n\n";

// 2. Verificar se o token é válido usando AuthMiddleware
echo "2. VERIFICANDO TOKEN COM AUTHMIDDLEWARE:\n";

// Simular header Authorization
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

$authMiddleware = new AuthMiddleware();
$resultado = $authMiddleware->verificarToken();

if ($resultado && $resultado['valido']) {
    echo "✅ Token válido\n";
    echo "User ID do token: {$resultado['usuario']['id']}\n";
    echo "Email do token: {$resultado['usuario']['email']}\n\n";
    $user_id = $resultado['usuario']['id'];
} else {
    echo "❌ Token inválido\n";
    exit(1);
}

// 3. Simular requisição HTTP de alteração de senha
echo "3. SIMULANDO REQUISIÇÃO HTTP DE ALTERAÇÃO DE SENHA:\n";

$change_data = json_encode([
    'current_password' => 'teste123',
    'new_password' => 'novaSenha456',
    'confirm_password' => 'novaSenha456'
]);

echo "Dados enviados: {$change_data}\n";

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

echo "Código HTTP: {$http_code}\n";
echo "Resposta: {$response}\n\n";

// 4. Verificar se a senha foi alterada no banco
echo "4. VERIFICANDO SE A SENHA FOI ALTERADA NO BANCO:\n";

$stmt = $db->prepare('SELECT senha FROM usuarios WHERE email = ?');
$stmt->execute(['ronaldguilhon@gmail.com']);
$current_hash = $stmt->fetch(PDO::FETCH_ASSOC)['senha'];

echo "Hash atual no banco: " . substr($current_hash, 0, 50) . "...\n";

if (verifyPassword('novaSenha456', $current_hash)) {
    echo "✅ Nova senha funciona!\n";
} else if (verifyPassword('teste123', $current_hash)) {
    echo "❌ Senha não foi alterada (ainda é a original)\n";
} else {
    echo "❌ Hash no banco não corresponde a nenhuma senha conhecida\n";
}

// 5. Teste direto do modelo Usuario
echo "\n5. TESTE DIRETO DO MODELO USUARIO:\n";

require_once __DIR__ . '/../../backend/models/Usuario.php';

$usuario = new Usuario($db);
$usuario->id = $user_id;

echo "ID do usuário: {$usuario->id}\n";

$resultado_direto = $usuario->alterarSenha('teste123', 'senhaTesteDireto');
echo "Resultado alteração direta: " . ($resultado_direto ? 'TRUE' : 'FALSE') . "\n";

// Verificar se a alteração direta funcionou
$stmt = $db->prepare('SELECT senha FROM usuarios WHERE email = ?');
$stmt->execute(['ronaldguilhon@gmail.com']);
$hash_apos_direto = $stmt->fetch(PDO::FETCH_ASSOC)['senha'];

if (verifyPassword('senhaTesteDireto', $hash_apos_direto)) {
    echo "✅ Alteração direta funcionou!\n";
    
    // Restaurar senha original
    $hash_original = hashPassword('teste123');
    $stmt = $db->prepare('UPDATE usuarios SET senha = ? WHERE email = ?');
    $stmt->execute([$hash_original, 'ronaldguilhon@gmail.com']);
    echo "✅ Senha restaurada para 'teste123'\n";
} else {
    echo "❌ Alteração direta não funcionou\n";
}

echo "\n=== CONCLUSÃO ===\n";
echo "Se a alteração direta funcionou mas a via HTTP não, o problema está na integração HTTP/Sessão\n";
echo "Se ambas não funcionaram, o problema está no modelo Usuario\n";