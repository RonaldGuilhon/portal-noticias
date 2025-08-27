<?php
// Teste da API de login para usuário admin@portal.com

echo "=== TESTE DE LOGIN DA API ===\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n\n";

// Dados de login
$loginData = [
    'email' => 'admin@portal.com',
    'senha' => 'password'
];

echo "📧 Email: " . $loginData['email'] . "\n";
echo "🔑 Senha: " . $loginData['senha'] . "\n\n";

// URL da API
$apiUrl = 'http://localhost:8001/auth/login';
echo "🌐 URL da API: $apiUrl\n\n";

// Configurar cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

echo "📡 Enviando requisição...\n";

// Executar requisição
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📊 Código HTTP: $httpCode\n";

if ($error) {
    echo "❌ ERRO cURL: $error\n";
    exit(1);
}

if ($response === false) {
    echo "❌ ERRO: Resposta vazia\n";
    exit(1);
}

echo "📋 Resposta bruta:\n";
echo $response . "\n\n";

// Decodificar JSON
$responseData = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ ERRO JSON: " . json_last_error_msg() . "\n";
    exit(1);
}

echo "=== ANÁLISE DA RESPOSTA ===\n";

if ($httpCode === 200) {
    echo "✅ STATUS: Sucesso (200)\n";
    
    if (isset($responseData['success']) && $responseData['success']) {
        echo "✅ LOGIN: Bem-sucedido\n";
        
        // Verificar token (pode estar em 'token' ou 'dados.token')
        $token = null;
        if (isset($responseData['token'])) {
            $token = $responseData['token'];
        } elseif (isset($responseData['dados']['token'])) {
            $token = $responseData['dados']['token'];
        }
        
        if ($token) {
            echo "✅ TOKEN: Recebido\n";
            echo "🔑 Token (primeiros 50 chars): " . substr($token, 0, 50) . "...\n";
            
            // Salvar token para próximos testes
            file_put_contents(__DIR__ . '/admin_token.txt', $token);
            echo "💾 Token salvo em admin_token.txt\n";
        } else {
            echo "❌ TOKEN: Não encontrado na resposta\n";
        }
        
        // Verificar dados do usuário (pode estar em 'usuario' ou 'dados.usuario')
        $usuario = null;
        if (isset($responseData['usuario'])) {
            $usuario = $responseData['usuario'];
        } elseif (isset($responseData['dados']['usuario'])) {
            $usuario = $responseData['dados']['usuario'];
        }
        
        if ($usuario) {
            echo "\n=== DADOS DO USUÁRIO ===\n";
            echo "ID: " . ($usuario['id'] ?? 'N/A') . "\n";
            echo "Nome: " . ($usuario['nome'] ?? 'N/A') . "\n";
            echo "Email: " . ($usuario['email'] ?? 'N/A') . "\n";
            echo "Tipo: " . ($usuario['tipo'] ?? 'N/A') . "\n";
        }
        
    } else {
        echo "❌ LOGIN: Falhou\n";
        if (isset($responseData['erro'])) {
            echo "❌ ERRO: " . $responseData['erro'] . "\n";
        }
    }
    
} else {
    echo "❌ STATUS: Erro ($httpCode)\n";
    if (isset($responseData['erro'])) {
        echo "❌ ERRO: " . $responseData['erro'] . "\n";
    }
}

echo "\n=== RESUMO ===\n";
echo "Login API: " . ($httpCode === 200 && isset($responseData['success']) && $responseData['success'] ? '✅ FUNCIONANDO' : '❌ COM PROBLEMAS') . "\n";
echo "Token gerado: " . (isset($token) && $token ? '✅ SIM' : '❌ NÃO') . "\n";
echo "Dados do usuário: " . (isset($usuario) && $usuario ? '✅ SIM' : '❌ NÃO') . "\n";

?>