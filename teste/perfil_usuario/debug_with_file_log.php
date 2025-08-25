<?php
/**
 * Teste para debugar o AuthController com log em arquivo
 */

echo "=== ADICIONANDO DEBUG NO AUTHCONTROLLER COM LOG EM ARQUIVO ===\n\n";

// Definir arquivo de log temporário
$logFile = __DIR__ . '/debug_auth.log';
file_put_contents($logFile, ""); // Limpar log anterior

// Ler o arquivo AuthController
$authControllerPath = __DIR__ . '/../../backend/controllers/AuthController.php';
$content = file_get_contents($authControllerPath);

// Fazer backup
$backupPath = $authControllerPath . '.backup';
file_put_contents($backupPath, $content);

echo "✅ Backup criado: {$backupPath}\n";

// Adicionar logs de debug no método alterarSenha
$debugCode = '
            // DEBUG: Log de debug temporário
            file_put_contents(__DIR__ . "/../../teste/perfil_usuario/debug_auth.log", "[DEBUG] alterarSenha - Iniciando\n", FILE_APPEND);
            file_put_contents(__DIR__ . "/../../teste/perfil_usuario/debug_auth.log", "[DEBUG] alterarSenha - Usuario ID da sessão: " . ($_SESSION["usuario_id"] ?? "não definido") . "\n", FILE_APPEND);
            file_put_contents(__DIR__ . "/../../teste/perfil_usuario/debug_auth.log", "[DEBUG] alterarSenha - Dados recebidos: " . json_encode($dados) . "\n", FILE_APPEND);
            
            $this->usuario->id = $_SESSION["usuario_id"];
            file_put_contents(__DIR__ . "/../../teste/perfil_usuario/debug_auth.log", "[DEBUG] alterarSenha - Usuario ID definido no modelo: " . $this->usuario->id . "\n", FILE_APPEND);
            
            $resultado_alteracao = $this->usuario->alterarSenha($senha_atual, $nova_senha);
            file_put_contents(__DIR__ . "/../../teste/perfil_usuario/debug_auth.log", "[DEBUG] alterarSenha - Resultado do modelo: " . ($resultado_alteracao ? "TRUE" : "FALSE") . "\n", FILE_APPEND);
';

// Substituir a linha onde o ID é definido
$pattern = '/\$this->usuario->id = \$_SESSION\[\'usuario_id\'\];/';
$replacement = $debugCode;

$newContent = preg_replace($pattern, $replacement, $content);

if ($newContent === $content) {
    echo "❌ Não foi possível encontrar a linha para adicionar debug\n";
    exit(1);
}

// Salvar o arquivo modificado
file_put_contents($authControllerPath, $newContent);

echo "✅ Debug adicionado ao AuthController\n";
echo "📋 Log será salvo em: {$logFile}\n\n";

echo "=== EXECUTANDO TESTE COM DEBUG ===\n\n";

// Executar teste
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

// Fazer login
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
    echo "❌ Falha no login\n";
    exit(1);
}

$token = $login_result['token'];
echo "✅ Login realizado, token obtido\n\n";

// Alterar senha
echo "📋 Alterando senha via API...\n";

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
echo "- Resposta: {$response}\n\n";

// Aguardar um pouco para garantir que os logs foram escritos
sleep(1);

echo "=== LOGS DE DEBUG ===\n";
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    echo $logs;
} else {
    echo "❌ Arquivo de log não foi criado\n";
}

echo "\n=== VERIFICANDO SE A SENHA FOI ALTERADA ===\n";

// Verificar se a senha foi realmente alterada
$stmt = $db->prepare('SELECT senha FROM usuarios WHERE email = ?');
$stmt->execute(['ronaldguilhon@gmail.com']);
$current_hash = $stmt->fetch(PDO::FETCH_ASSOC)['senha'];

if (verifyPassword('novaSenha456', $current_hash)) {
    echo "✅ Nova senha funciona!\n";
} else if (verifyPassword('teste123', $current_hash)) {
    echo "❌ Senha não foi alterada (ainda é a original)\n";
} else {
    echo "❌ Hash no banco não corresponde a nenhuma senha conhecida\n";
}

echo "\n=== PARA RESTAURAR O ARQUIVO ORIGINAL ===\n";
echo "Execute: php -r \"copy('{$backupPath}', '{$authControllerPath}'); echo 'Arquivo restaurado';\"\n";