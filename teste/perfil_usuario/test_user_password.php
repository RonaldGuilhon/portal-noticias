<?php

// Teste específico com as credenciais do usuário
echo "=== TESTE DE ALTERAÇÃO DE SENHA - USUÁRIO ESPECÍFICO ===\n\n";

// 1. Fazer login com as credenciais fornecidas
echo "1. FAZENDO LOGIN COM CREDENCIAIS DO USUÁRIO:\n";
echo str_repeat("-", 50) . "\n";

$login_data = [
    'email' => 'ronaldguilhon@gmail.com',
    'senha' => 'Rede@@123'
];

echo "Email: {$login_data['email']}\n";
echo "Senha: {$login_data['senha']}\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8001/auth/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($login_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$login_response = curl_exec($ch);
$login_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "Status HTTP: {$login_http_code}\n";
if ($curl_error) {
    echo "Erro cURL: {$curl_error}\n";
}
echo "Resposta: {$login_response}\n\n";

if ($login_http_code === 200) {
    $login_result = json_decode($login_response, true);
    
    if (isset($login_result['token'])) {
        $token = $login_result['token'];
        echo "✓ Login realizado com sucesso!\n";
        echo "Token: " . substr($token, 0, 30) . "...\n\n";
        
        // 2. Tentar alterar a senha
        echo "2. TENTANDO ALTERAR SENHA:\n";
        echo str_repeat("-", 50) . "\n";
        
        $password_data = [
            'current_password' => 'Rede@@123',
            'new_password' => 'Rede@@3645',
            'confirm_password' => 'Rede@@3645'
        ];
        
        echo "Senha atual: {$password_data['current_password']}\n";
        echo "Nova senha: {$password_data['new_password']}\n";
        echo "Confirmação: {$password_data['confirm_password']}\n\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost:8001/api/user/profile?action=change-password');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($password_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        
        $password_response = curl_exec($ch);
        $password_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        echo "Status HTTP: {$password_http_code}\n";
        if ($curl_error) {
            echo "Erro cURL: {$curl_error}\n";
        }
        echo "Resposta: {$password_response}\n\n";
        
        if ($password_http_code === 200) {
            $password_result = json_decode($password_response, true);
            if (isset($password_result['success']) && $password_result['success']) {
                echo "✓ Senha alterada com sucesso!\n\n";
                
                // 3. Testar login com nova senha
                echo "3. TESTANDO LOGIN COM NOVA SENHA:\n";
                echo str_repeat("-", 50) . "\n";
                
                $new_login_data = [
                    'email' => 'ronaldguilhon@gmail.com',
                    'senha' => 'Rede@@3645'
                ];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'http://localhost:8001/auth/login');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($new_login_data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json'
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                
                $new_login_response = curl_exec($ch);
                $new_login_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                echo "Status HTTP: {$new_login_http_code}\n";
                echo "Resposta: {$new_login_response}\n";
                
                if ($new_login_http_code === 200) {
                    echo "✓ Login com nova senha funcionou perfeitamente!\n";
                } else {
                    echo "✗ Erro no login com nova senha\n";
                }
                
            } else {
                echo "✗ Erro na alteração de senha\n";
                if (isset($password_result['erro'])) {
                    echo "Motivo: {$password_result['erro']}\n";
                }
            }
        } else {
            echo "✗ Erro HTTP na alteração de senha\n";
            echo "Detalhes da resposta: {$password_response}\n";
        }
        
    } else {
        echo "✗ Token não encontrado na resposta de login\n";
        if (isset($login_result['erro'])) {
            echo "Erro: {$login_result['erro']}\n";
        }
    }
    
} else {
    echo "✗ Erro no login inicial\n";
    echo "Detalhes: {$login_response}\n";
    
    // Verificar se o usuário existe no banco
    echo "\n4. VERIFICANDO SE USUÁRIO EXISTE NO BANCO:\n";
    echo str_repeat("-", 50) . "\n";
    
    try {
        require_once 'config-local.php';
        require_once 'backend/config/config.php';
        
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->prepare('SELECT id, nome, email FROM usuarios WHERE email = ?');
        $stmt->execute(['ronaldguilhon@gmail.com']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "✓ Usuário encontrado no banco:\n";
            echo "ID: {$user['id']}\n";
            echo "Nome: {$user['nome']}\n";
            echo "Email: {$user['email']}\n";
        } else {
            echo "✗ Usuário não encontrado no banco de dados\n";
        }
        
    } catch(Exception $e) {
        echo "Erro ao verificar banco: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "TESTE CONCLUÍDO\n";

?>