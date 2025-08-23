<?php

// Teste direto da API de alteração de senha
echo "=== TESTE DA API DE ALTERAÇÃO DE SENHA ===\n\n";

// 1. Primeiro fazer login para obter token
echo "1. FAZENDO LOGIN PARA OBTER TOKEN:\n";
echo str_repeat("-", 40) . "\n";

$login_data = [
    'email' => 'admin@portalnoticias.com',
    'senha' => 'password'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8001/auth/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($login_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$login_response = curl_exec($ch);
$login_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status HTTP: {$login_http_code}\n";
echo "Resposta: {$login_response}\n\n";

if ($login_http_code === 200) {
    $login_result = json_decode($login_response, true);
    
    if (isset($login_result['token'])) {
        $token = $login_result['token'];
        echo "✓ Token obtido: " . substr($token, 0, 20) . "...\n\n";
        
        // 2. Testar alteração de senha
        echo "2. TESTANDO ALTERAÇÃO DE SENHA:\n";
        echo str_repeat("-", 40) . "\n";
        
        $password_data = [
            'current_password' => 'password',
            'new_password' => 'novaSenha123',
            'confirm_password' => 'novaSenha123'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost:8001/api/user/profile?action=change-password');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($password_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $password_response = curl_exec($ch);
        $password_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "Status HTTP: {$password_http_code}\n";
        echo "Resposta: {$password_response}\n\n";
        
        if ($password_http_code === 200) {
            echo "✓ Senha alterada com sucesso!\n";
            
            // 3. Testar login com nova senha
            echo "\n3. TESTANDO LOGIN COM NOVA SENHA:\n";
            echo str_repeat("-", 40) . "\n";
            
            $new_login_data = [
                'email' => 'admin@portalnoticias.com',
                'senha' => 'novaSenha123'
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
                echo "✓ Login com nova senha funcionou!\n";
                
                // Restaurar senha original
                echo "\n4. RESTAURANDO SENHA ORIGINAL:\n";
                echo str_repeat("-", 40) . "\n";
                
                $new_token = json_decode($new_login_response, true)['token'];
                
                $restore_data = [
                    'current_password' => 'novaSenha123',
                    'new_password' => 'password',
                    'confirm_password' => 'password'
                ];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'http://localhost:8001/api/user/profile?action=change-password');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($restore_data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $new_token
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                
                $restore_response = curl_exec($ch);
                $restore_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                echo "Status HTTP: {$restore_http_code}\n";
                echo "Resposta: {$restore_response}\n";
                
                if ($restore_http_code === 200) {
                    echo "✓ Senha original restaurada!\n";
                } else {
                    echo "✗ Erro ao restaurar senha original\n";
                }
                
            } else {
                echo "✗ Login com nova senha falhou\n";
            }
            
        } else {
            echo "✗ Erro na alteração de senha\n";
            echo "Detalhes: {$password_response}\n";
        }
        
    } else {
        echo "✗ Token não encontrado na resposta\n";
    }
    
} else {
    echo "✗ Erro no login\n";
    echo "Detalhes: {$login_response}\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "TESTE DA API CONCLUÍDO\n";

?>