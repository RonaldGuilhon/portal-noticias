<?php
/**
 * Teste para debugar o AuthController
 * Adiciona logs temporários para identificar o problema
 */

echo "=== ADICIONANDO DEBUG NO AUTHCONTROLLER ===\n\n";

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
            error_log("[DEBUG] alterarSenha - Iniciando");
            error_log("[DEBUG] alterarSenha - Usuario ID da sessão: " . ($_SESSION["usuario_id"] ?? "não definido"));
            error_log("[DEBUG] alterarSenha - Dados recebidos: " . json_encode($dados));
            
            $this->usuario->id = $_SESSION["usuario_id"];
            error_log("[DEBUG] alterarSenha - Usuario ID definido no modelo: " . $this->usuario->id);
            
            $resultado_alteracao = $this->usuario->alterarSenha($senha_atual, $nova_senha);
            error_log("[DEBUG] alterarSenha - Resultado do modelo: " . ($resultado_alteracao ? "TRUE" : "FALSE"));
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
echo "📋 Agora execute uma requisição de alteração de senha e verifique os logs\n";
echo "📋 Para restaurar o arquivo original, execute:\n";
echo "   cp {$backupPath} {$authControllerPath}\n\n";

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

echo "📋 Verifique os logs de erro do PHP para ver os debugs\n";
echo "📋 No Windows, geralmente em: C:\\php\\logs\\php_errors.log\n";
echo "📋 Ou execute: tail -f /path/to/php/error.log\n\n";

echo "=== PARA RESTAURAR O ARQUIVO ORIGINAL ===\n";
echo "Execute: php -r \"copy('{$backupPath}', '{$authControllerPath}'); echo 'Arquivo restaurado';\"\n";