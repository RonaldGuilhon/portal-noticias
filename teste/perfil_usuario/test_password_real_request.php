<?php
/**
 * Teste real de alteração de senha via requisições HTTP
 * Simula exatamente o que o frontend faz
 */

try {
    echo "=== TESTE REAL DE ALTERAÇÃO DE SENHA VIA HTTP ===\n\n";
    
    $base_url = 'http://localhost:8001';
    
    // 1. Primeiro, definir uma senha conhecida no banco
    echo "1. DEFININDO SENHA CONHECIDA NO BANCO:\n";
    echo str_repeat("-", 50) . "\n";
    
    require_once __DIR__ . '/../../config-local.php';
    require_once __DIR__ . '/../../backend/config/config.php';
    require_once __DIR__ . '/../../backend/config/database.php';
    
    $database = new Database();
    $conn = $database->getConnection();
    
    $email_teste = 'ronaldguilhon@gmail.com';
    $senha_conhecida = 'teste123';
    $hash_conhecido = hashPassword($senha_conhecida);
    
    $query = "UPDATE usuarios SET senha = :senha WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':senha', $hash_conhecido);
    $stmt->bindParam(':email', $email_teste);
    
    if ($stmt->execute()) {
        echo "✓ Senha definida: {$senha_conhecida}\n";
        echo "✓ Email: {$email_teste}\n\n";
    } else {
        echo "✗ Erro ao definir senha!\n";
        exit(1);
    }
    
    // 2. Fazer login via HTTP para obter token
    echo "2. FAZENDO LOGIN VIA HTTP:\n";
    echo str_repeat("-", 50) . "\n";
    
    $login_data = [
        'email' => $email_teste,
        'senha' => $senha_conhecida
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $base_url . '/auth/login');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($login_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Origin: http://localhost:8000'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $login_response = curl_exec($ch);
    $login_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "- Código HTTP: {$login_http_code}\n";
    echo "- Resposta: {$login_response}\n";
    
    $login_result = json_decode($login_response, true);
    
    if (!$login_result || !isset($login_result['success']) || !$login_result['success']) {
        echo "✗ Falha no login!\n";
        exit(1);
    }
    
    $token = $login_result['token'] ?? null;
    $usuario_id = $login_result['usuario']['id'] ?? null;
    
    if (!$token) {
        echo "✗ Token não encontrado!\n";
        exit(1);
    }
    
    echo "✓ Login realizado com sucesso!\n";
    echo "- Token: " . substr($token, 0, 20) . "...\n";
    echo "- Usuário ID: {$usuario_id}\n\n";
    
    // 3. Alterar senha via HTTP
    echo "3. ALTERANDO SENHA VIA HTTP:\n";
    echo str_repeat("-", 50) . "\n";
    
    $nova_senha = 'novaSenha456';
    $change_data = [
        'current_password' => $senha_conhecida,
        'new_password' => $nova_senha,
        'confirm_password' => $nova_senha
    ];
    
    echo "- Dados enviados:\n";
    echo "  current_password: {$change_data['current_password']}\n";
    echo "  new_password: {$change_data['new_password']}\n";
    echo "  confirm_password: {$change_data['confirm_password']}\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $base_url . '/api/user/profile?action=change-password');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($change_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
        'Origin: http://localhost:8000'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $change_response = curl_exec($ch);
    $change_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "\n- Código HTTP: {$change_http_code}\n";
    echo "- Resposta: {$change_response}\n";
    
    $change_result = json_decode($change_response, true);
    
    if ($change_result && isset($change_result['success']) && $change_result['success']) {
        echo "✓ API retornou sucesso!\n";
        
        // 4. Verificar se realmente mudou no banco
        echo "\n4. VERIFICANDO ALTERAÇÃO NO BANCO:\n";
        echo str_repeat("-", 50) . "\n";
        
        $query = "SELECT senha FROM usuarios WHERE email = :email";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':email', $email_teste);
        $stmt->execute();
        $hash_atual = $stmt->fetchColumn();
        
        echo "- Hash no banco: {$hash_atual}\n";
        
        if (verifyPassword($nova_senha, $hash_atual)) {
            echo "✓ Nova senha verifica corretamente no banco!\n";
        } else {
            echo "✗ PROBLEMA: Nova senha NÃO verifica no banco!\n";
        }
        
        if (verifyPassword($senha_conhecida, $hash_atual)) {
            echo "✗ PROBLEMA: Senha antiga ainda funciona!\n";
        } else {
            echo "✓ Senha antiga não funciona mais (correto)\n";
        }
        
        // 5. Testar login com nova senha
        echo "\n5. TESTANDO LOGIN COM NOVA SENHA:\n";
        echo str_repeat("-", 50) . "\n";
        
        $new_login_data = [
            'email' => $email_teste,
            'senha' => $nova_senha
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $base_url . '/auth/login');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($new_login_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Origin: http://localhost:8000'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $new_login_response = curl_exec($ch);
        $new_login_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $new_login_result = json_decode($new_login_response, true);
        
        if ($new_login_result && isset($new_login_result['success']) && $new_login_result['success']) {
            echo "✓ Login com nova senha funcionou!\n";
        } else {
            echo "✗ Login com nova senha FALHOU!\n";
            echo "- Resposta: {$new_login_response}\n";
        }
        
    } else {
        echo "✗ API retornou erro!\n";
        if (isset($change_result['erro'])) {
            echo "- Erro: {$change_result['erro']}\n";
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "CONCLUSÃO DO TESTE REAL:\n";
    
    if ($change_result && isset($change_result['success']) && $change_result['success']) {
        echo "✅ SISTEMA FUNCIONANDO CORRETAMENTE!\n";
        echo "\n📋 O problema reportado pode ser:\n";
        echo "1. Cache do navegador - Solução: Ctrl+F5\n";
        echo "2. Token expirado - Solução: Logout/Login\n";
        echo "3. JavaScript desabilitado\n";
        echo "4. Extensões do navegador interferindo\n";
        echo "5. Problema de conectividade temporário\n";
        echo "\n💡 RECOMENDAÇÕES:\n";
        echo "- Pedir ao usuário para limpar cache do navegador\n";
        echo "- Fazer logout e login novamente\n";
        echo "- Testar em modo incógnito\n";
        echo "- Verificar console do navegador (F12)\n";
    } else {
        echo "❌ PROBLEMA IDENTIFICADO NO SISTEMA!\n";
        echo "\n🔧 INVESTIGAR:\n";
        echo "- Logs do servidor\n";
        echo "- Configuração de autenticação\n";
        echo "- Middleware de autenticação\n";
    }
    
} catch(Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")\n";
}
?>