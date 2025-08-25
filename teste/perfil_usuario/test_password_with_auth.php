<?php
/**
 * Teste completo de alteração de senha com autenticação
 * Simula exatamente o fluxo do frontend
 */

require_once __DIR__ . '/../../config-local.php';
require_once __DIR__ . '/../../backend/controllers/AuthController.php';

try {
    echo "=== TESTE COMPLETO DE ALTERAÇÃO DE SENHA COM AUTENTICAÇÃO ===\n\n";
    
    // 1. Fazer login para obter token válido
    echo "1. FAZENDO LOGIN PARA OBTER TOKEN:\n";
    echo str_repeat("-", 50) . "\n";
    
    $authController = new AuthController();
    
    // Simular dados de login
    $_POST = [
        'email' => 'ronaldguilhon@gmail.com',
        'senha' => 'senhaFinal789' // Última senha do teste anterior
    ];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET['action'] = 'login';
    
    // Capturar output do login
    ob_start();
    $authController->processarRequisicao();
    $login_output = ob_get_clean();
    
    echo "Resposta do login: {$login_output}\n";
    
    $login_data = json_decode($login_output, true);
    
    if (!$login_data || !isset($login_data['success']) || !$login_data['success']) {
        echo "✗ Falha no login! Vou definir uma senha conhecida primeiro.\n\n";
        
        // Definir senha conhecida diretamente no banco
        $database = new Database();
        $conn = $database->getConnection();
        
        $senha_conhecida = 'teste123';
        $hash_conhecido = hashPassword($senha_conhecida);
        
        $query = "UPDATE usuarios SET senha = :senha WHERE email = 'ronaldguilhon@gmail.com'";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':senha', $hash_conhecido);
        $stmt->execute();
        
        echo "✓ Senha definida como: {$senha_conhecida}\n";
        
        // Tentar login novamente
        $_POST['senha'] = $senha_conhecida;
        
        ob_start();
        $authController->processarRequisicao();
        $login_output = ob_get_clean();
        
        echo "Nova resposta do login: {$login_output}\n";
        $login_data = json_decode($login_output, true);
    }
    
    if (!$login_data || !isset($login_data['success']) || !$login_data['success']) {
        echo "✗ Ainda não foi possível fazer login!\n";
        exit(1);
    }
    
    $token = $login_data['dados']['token'] ?? null;
    $usuario_id = $login_data['dados']['id'] ?? null;
    
    if (!$token) {
        echo "✗ Token não encontrado na resposta!\n";
        exit(1);
    }
    
    echo "✓ Login realizado com sucesso!\n";
    echo "- Token: {$token}\n";
    echo "- Usuário ID: {$usuario_id}\n\n";
    
    // 2. Simular requisição de alteração de senha
    echo "2. SIMULANDO ALTERAÇÃO DE SENHA VIA API:\n";
    echo str_repeat("-", 50) . "\n";
    
    // Limpar variáveis globais
    unset($_POST);
    $_SERVER['REQUEST_METHOD'] = 'PUT';
    $_GET['action'] = 'change-password';
    
    // Simular cabeçalho de autorização
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
    
    // Simular dados JSON do corpo da requisição
    $dados_senha = [
        'current_password' => 'teste123',
        'new_password' => 'novaSenha123',
        'confirm_password' => 'novaSenha123'
    ];
    
    echo "- Dados enviados:\n";
    echo "  current_password: {$dados_senha['current_password']}\n";
    echo "  new_password: {$dados_senha['new_password']}\n";
    echo "  confirm_password: {$dados_senha['confirm_password']}\n";
    
    // Simular php://input
    $temp_file = tempnam(sys_get_temp_dir(), 'php_input');
    file_put_contents($temp_file, json_encode($dados_senha));
    
    // Usar stream wrapper para simular php://input
    stream_wrapper_unregister('php');
    stream_wrapper_register('php', 'MockPhpInputStream');
    MockPhpInputStream::$data = json_encode($dados_senha);
    
    // Executar alteração de senha
    ob_start();
    $authController->processarRequisicao();
    $change_output = ob_get_clean();
    
    echo "\nResposta da alteração: {$change_output}\n";
    
    $change_data = json_decode($change_output, true);
    
    if ($change_data && isset($change_data['success']) && $change_data['success']) {
        echo "✓ API retornou sucesso!\n";
        
        // 3. Verificar se realmente mudou no banco
        echo "\n3. VERIFICANDO ALTERAÇÃO NO BANCO:\n";
        echo str_repeat("-", 50) . "\n";
        
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT senha FROM usuarios WHERE email = 'ronaldguilhon@gmail.com'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $hash_atual = $stmt->fetchColumn();
        
        if (verifyPassword($dados_senha['new_password'], $hash_atual)) {
            echo "✓ Nova senha verifica corretamente no banco!\n";
        } else {
            echo "✗ PROBLEMA: Nova senha NÃO verifica no banco!\n";
            echo "- Hash no banco: {$hash_atual}\n";
            echo "- Senha testada: {$dados_senha['new_password']}\n";
        }
        
        if (verifyPassword($dados_senha['current_password'], $hash_atual)) {
            echo "✗ PROBLEMA: Senha antiga ainda funciona!\n";
        } else {
            echo "✓ Senha antiga não funciona mais (correto)\n";
        }
        
    } else {
        echo "✗ API retornou erro!\n";
        if (isset($change_data['erro'])) {
            echo "- Erro: {$change_data['erro']}\n";
        }
    }
    
    // Restaurar stream wrapper
    stream_wrapper_restore('php');
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "CONCLUSÃO DO TESTE COMPLETO:\n";
    
    if ($change_data && isset($change_data['success']) && $change_data['success']) {
        echo "✅ Sistema funcionando corretamente!\n";
        echo "\n🔍 Se o usuário ainda vê o problema, pode ser:\n";
        echo "- Cache do navegador (Ctrl+F5 para limpar)\n";
        echo "- Token expirado (fazer logout/login)\n";
        echo "- JavaScript desabilitado\n";
        echo "- Problema de conectividade\n";
    } else {
        echo "❌ Problema identificado no sistema!\n";
    }
    
} catch(Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")\n";
}

/**
 * Classe para simular php://input
 */
class MockPhpInputStream {
    public static $data = '';
    private $position = 0;
    
    public function stream_open($path, $mode, $options, &$opened_path) {
        $this->position = 0;
        return true;
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
        return array();
    }
}
?>