<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/config/database.php';

echo "=== DEBUG DO AUTHCONTROLLER - ALTERAÇÃO DE SENHA ===\n\n";

try {
    // 1. Simular login para obter token
    echo "1. SIMULANDO LOGIN PARA OBTER TOKEN:\n";
    
    $database = new Database();
    $db = $database->getConnection();
    
    require_once __DIR__ . '/../../backend/controllers/AuthController.php';
    
    // Simular dados de login
    $_POST = [
        'email' => 'ronaldguilhon@gmail.com',
        'senha' => 'Rede@@123'
    ];
    
    // Capturar output do login
    ob_start();
    $authController = new AuthController();
    
    // Simular requisição de login
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/api/auth/login';
    
    try {
        $authController->handleRequest();
    } catch (Exception $e) {
        // Capturar possível saída JSON
    }
    
    $login_output = ob_get_clean();
    echo "Resposta do login: {$login_output}\n";
    
    // Extrair token da resposta
    $login_data = json_decode($login_output, true);
    if (!isset($login_data['token'])) {
        echo "✗ Não foi possível obter token do login\n";
        exit;
    }
    
    $token = $login_data['token'];
    echo "✓ Token obtido: " . substr($token, 0, 50) . "...\n\n";
    
    // 2. Simular alteração de senha
    echo "2. SIMULANDO ALTERAÇÃO DE SENHA VIA AUTHCONTROLLER:\n";
    
    // Limpar dados anteriores
    unset($_POST);
    
    // Simular dados de alteração de senha
    $password_data = [
        'current_password' => 'Rede@@123',
        'new_password' => 'NovaSenha456',
        'confirm_password' => 'NovaSenha456'
    ];
    
    // Simular input JSON
    $json_input = json_encode($password_data);
    
    // Simular headers de autorização
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
    $_SERVER['REQUEST_METHOD'] = 'PUT';
    $_SERVER['REQUEST_URI'] = '/api/user/profile?action=change-password';
    
    // Mock do php://input
    $temp_file = tempnam(sys_get_temp_dir(), 'php_input');
    file_put_contents($temp_file, $json_input);
    
    // Usar stream_wrapper para simular php://input
    stream_wrapper_unregister('php');
    stream_wrapper_register('php', 'MockPhpInputStream');
    
    class MockPhpInputStream {
        private static $data;
        private $position = 0;
        
        public static function setData($data) {
            self::$data = $data;
        }
        
        public function stream_open($path, $mode, $options, &$opened_path) {
            if ($path === 'php://input') {
                return true;
            }
            return false;
        }
        
        public function stream_read($count) {
            $ret = substr(self::$data, $this->position, $count);
            $this->position += strlen($ret);
            return $ret;
        }
        
        public function stream_eof() {
            return $this->position >= strlen(self::$data);
        }
        
        public function stream_stat() {
            return [];
        }
    }
    
    MockPhpInputStream::setData($json_input);
    
    echo "Dados enviados: {$json_input}\n";
    echo "Token: " . substr($token, 0, 50) . "...\n";
    
    // Capturar output da alteração de senha
    ob_start();
    
    try {
        $authController2 = new AuthController();
        $authController2->handleRequest();
    } catch (Exception $e) {
        echo "Exceção capturada: " . $e->getMessage() . "\n";
    }
    
    $password_output = ob_get_clean();
    echo "Resposta da alteração: {$password_output}\n";
    
    // Restaurar stream wrapper
    stream_wrapper_restore('php');
    
    // 3. Verificar se a senha foi realmente alterada
    echo "\n3. VERIFICANDO SE A SENHA FOI ALTERADA NO BANCO:\n";
    
    $query = "SELECT senha FROM usuarios WHERE email = 'ronaldguilhon@gmail.com'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $current_hash = $stmt->fetch(PDO::FETCH_ASSOC)['senha'];
    
    echo "Hash atual no banco: {$current_hash}\n";
    
    // Testar se a nova senha funciona
    if (verifyPassword('NovaSenha456', $current_hash)) {
        echo "✓ Nova senha funciona!\n";
        
        // Restaurar senha original
        $original_hash = hashPassword('Rede@@123');
        $restore_query = "UPDATE usuarios SET senha = :senha WHERE email = 'ronaldguilhon@gmail.com'";
        $restore_stmt = $db->prepare($restore_query);
        $restore_stmt->bindParam(':senha', $original_hash);
        $restore_stmt->execute();
        echo "✓ Senha original restaurada\n";
        
    } else if (verifyPassword('Rede@@123', $current_hash)) {
        echo "✗ Senha não foi alterada (ainda é a original)\n";
    } else {
        echo "✗ Hash no banco não corresponde a nenhuma senha conhecida\n";
    }
    
} catch (Exception $e) {
    echo "ERRO GERAL: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== DEBUG DO AUTHCONTROLLER CONCLUÍDO ===\n";
?>