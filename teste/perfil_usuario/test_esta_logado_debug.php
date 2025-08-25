<?php
/**
 * Teste para verificar se o problema está no método estaLogado()
 */

echo "=== TESTE DEBUG DO MÉTODO ESTALOGADO ===\n\n";

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

// 1. Fazer login para obter token
echo "1. FAZENDO LOGIN PARA OBTER TOKEN:\n";

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
echo "✅ Login realizado, token obtido\n\n";

// 2. Testar método estaLogado() diretamente
echo "2. TESTANDO MÉTODO ESTALOGADO() DIRETAMENTE:\n";

// Simular headers
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
$_SERVER['REQUEST_METHOD'] = 'PUT';
$_SERVER['REQUEST_URI'] = '/api/user/profile?action=change-password';

// Criar instância do AuthController
$authController = new AuthController();

// Usar reflexão para acessar método privado estaLogado
$reflection = new ReflectionClass($authController);
$estaLogadoMethod = $reflection->getMethod('estaLogado');
$estaLogadoMethod->setAccessible(true);

$resultado_esta_logado = $estaLogadoMethod->invoke($authController);

echo "Resultado estaLogado(): " . ($resultado_esta_logado ? 'TRUE' : 'FALSE') . "\n";

if ($resultado_esta_logado) {
    echo "✅ Usuário está logado\n";
    echo "ID na sessão: " . ($_SESSION['usuario_id'] ?? 'não definido') . "\n";
    echo "Email na sessão: " . ($_SESSION['usuario_email'] ?? 'não definido') . "\n";
    echo "Nome na sessão: " . ($_SESSION['usuario_nome'] ?? 'não definido') . "\n\n";
} else {
    echo "❌ Usuário NÃO está logado\n";
    exit(1);
}

// 3. Testar método alterarSenha() diretamente
echo "3. TESTANDO MÉTODO ALTERARSENHA() DIRETAMENTE:\n";

// Simular dados de entrada
$password_data = [
    'current_password' => 'teste123',
    'new_password' => 'novaSenha456',
    'confirm_password' => 'novaSenha456'
];

// Mock do php://input
class MockInputStream {
    private static $data;
    
    public static function setData($data) {
        self::$data = $data;
    }
    
    public static function getData() {
        return self::$data;
    }
}

// Definir dados para o mock
MockInputStream::setData(json_encode($password_data));

// Substituir file_get_contents temporariamente
function file_get_contents_mock($filename) {
    if ($filename === 'php://input') {
        return MockInputStream::getData();
    }
    return file_get_contents($filename);
}

// Usar output buffering para capturar a resposta JSON
ob_start();

try {
    // Usar reflexão para acessar método privado alterarSenha
    $alterarSenhaMethod = $reflection->getMethod('alterarSenha');
    $alterarSenhaMethod->setAccessible(true);
    
    // Substituir temporariamente file_get_contents
    $original_function = 'file_get_contents';
    
    // Como não podemos redefinir funções built-in, vamos modificar o AuthController temporariamente
    echo "Simulando chamada do método alterarSenha...\n";
    
    // Verificar se o usuário está definido no modelo
    $usuarioProperty = $reflection->getProperty('usuario');
    $usuarioProperty->setAccessible(true);
    $usuario = $usuarioProperty->getValue($authController);
    
    echo "ID do usuário no modelo antes: " . ($usuario->id ?? 'não definido') . "\n";
    
    // Definir ID manualmente
    $usuario->id = $_SESSION['usuario_id'];
    echo "ID do usuário no modelo depois: " . $usuario->id . "\n";
    
    // Testar alteração direta
    $resultado_alteracao = $usuario->alterarSenha('teste123', 'novaSenha456');
    echo "Resultado alteração direta: " . ($resultado_alteracao ? 'TRUE' : 'FALSE') . "\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

$output = ob_get_clean();
echo $output;

// 4. Verificar se a senha foi alterada
echo "\n4. VERIFICANDO SE A SENHA FOI ALTERADA:\n";

$stmt = $db->prepare('SELECT senha FROM usuarios WHERE email = ?');
$stmt->execute(['ronaldguilhon@gmail.com']);
$current_hash = $stmt->fetch(PDO::FETCH_ASSOC)['senha'];

if (verifyPassword('novaSenha456', $current_hash)) {
    echo "✅ Nova senha funciona!\n";
    
    // Restaurar senha original
    $hash_original = hashPassword('teste123');
    $stmt = $db->prepare('UPDATE usuarios SET senha = ? WHERE email = ?');
    $stmt->execute([$hash_original, 'ronaldguilhon@gmail.com']);
    echo "✅ Senha restaurada\n";
} else if (verifyPassword('teste123', $current_hash)) {
    echo "❌ Senha não foi alterada (ainda é a original)\n";
} else {
    echo "❌ Hash no banco não corresponde a nenhuma senha conhecida\n";
}

echo "\n=== CONCLUSÃO ===\n";
echo "Este teste verifica se o problema está no método estaLogado() ou na definição do ID\n";