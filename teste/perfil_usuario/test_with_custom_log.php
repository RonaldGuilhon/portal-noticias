<?php
/**
 * Teste com log customizado para capturar os logs de debug
 */

echo "=== TESTE COM LOG CUSTOMIZADO ===\n\n";

// Definir arquivo de log customizado
$log_file = __DIR__ . '/debug_password.log';
if (file_exists($log_file)) {
    unlink($log_file);
}

// Configurar error_log para nosso arquivo
ini_set('error_log', $log_file);
ini_set('log_errors', 1);

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

// 3. Aguardar um pouco para garantir que os logs foram escritos
sleep(1);

// 4. Ler e exibir os logs
echo "3. LOGS DE DEBUG:\n";
echo str_repeat("=", 50) . "\n";

if (file_exists($log_file)) {
    $logs = file_get_contents($log_file);
    echo $logs;
} else {
    echo "❌ Arquivo de log não foi criado\n";
}

echo str_repeat("=", 50) . "\n\n";

// 5. Verificar se a senha foi alterada
echo "4. VERIFICANDO ALTERAÇÃO NO BANCO:\n";

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

echo "\n=== RESULTADO FINAL ===\n";
if ($senha_alterada) {
    echo "🎉 PROBLEMA RESOLVIDO!\n";
} else {
    echo "❌ PROBLEMA AINDA EXISTE - Verifique os logs acima\n";
}

// Limpar arquivo de log
if (file_exists($log_file)) {
    unlink($log_file);
}