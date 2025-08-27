<?php
/**
 * Teste Completo do Fluxo de Usuário
 * Verifica login, carregamento de dados e atualização do perfil
 * Usuário: admin@portal.com
 * Senha: password
 */

echo "=== TESTE COMPLETO DO FLUXO DE USUÁRIO ===\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n\n";

// Configurações
$baseUrl = 'http://localhost:8001';
$email = 'admin@portal.com';
$password = 'password';

// Função para fazer requisições cURL
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $httpCode,
        'error' => $error
    ];
}

// Função para extrair JSON válido da resposta
function extractValidJson($response) {
    $lines = explode("\n", trim($response));
    $lines = array_filter($lines, function($line) {
        return !empty(trim($line));
    });
    
    // Pega a última linha que deve ser o JSON válido
    $lastLine = end($lines);
    return json_decode($lastLine, true);
}

echo "1. TESTANDO LOGIN...\n";
echo "Email: $email\n";
echo "Senha: $password\n\n";

// 1. Teste de Login
$loginData = [
    'email' => $email,
    'senha' => $password
];

$loginResult = makeRequest(
    $baseUrl . '/auth/login',
    'POST',
    $loginData,
    ['Content-Type: application/json']
);

echo "Status HTTP: " . $loginResult['http_code'] . "\n";

if ($loginResult['error']) {
    echo "❌ Erro cURL: " . $loginResult['error'] . "\n";
    exit(1);
}

if ($loginResult['http_code'] !== 200) {
    echo "❌ Erro no login. Status: " . $loginResult['http_code'] . "\n";
    echo "Resposta: " . $loginResult['response'] . "\n";
    exit(1);
}

// Extrair dados do login
$loginResponse = extractValidJson($loginResult['response']);

if (!$loginResponse) {
    echo "❌ Erro ao decodificar resposta do login\n";
    echo "Resposta bruta: " . $loginResult['response'] . "\n";
    exit(1);
}

echo "✅ Login realizado com sucesso!\n";

// Extrair token
$token = null;
if (isset($loginResponse['dados']['token'])) {
    $token = $loginResponse['dados']['token'];
} elseif (isset($loginResponse['token'])) {
    $token = $loginResponse['token'];
} elseif (isset($loginResponse['data']['token'])) {
    $token = $loginResponse['data']['token'];
}

if (!$token) {
    echo "❌ Token não encontrado na resposta do login\n";
    echo "Resposta: " . json_encode($loginResponse, JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

echo "Token obtido: " . substr($token, 0, 20) . "...\n\n";

// 2. Teste de Carregamento do Perfil
echo "2. TESTANDO CARREGAMENTO DO PERFIL...\n";

$profileResult = makeRequest(
    $baseUrl . '/api/user/profile',
    'GET',
    null,
    [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]
);

echo "Status HTTP: " . $profileResult['http_code'] . "\n";

if ($profileResult['error']) {
    echo "❌ Erro cURL: " . $profileResult['error'] . "\n";
    exit(1);
}

if ($profileResult['http_code'] !== 200) {
    echo "❌ Erro ao carregar perfil. Status: " . $profileResult['http_code'] . "\n";
    echo "Resposta: " . $profileResult['response'] . "\n";
    exit(1);
}

// Extrair dados do perfil
$profileResponse = extractValidJson($profileResult['response']);

if (!$profileResponse) {
    echo "❌ Erro ao decodificar resposta do perfil\n";
    echo "Resposta bruta: " . $profileResult['response'] . "\n";
    exit(1);
}

echo "✅ Perfil carregado com sucesso!\n";

// Verificar se os dados estão presentes
$userData = null;
if (isset($profileResponse['data'])) {
    $userData = $profileResponse['data'];
} elseif (isset($profileResponse['dados'])) {
    $userData = $profileResponse['dados'];
} elseif (isset($profileResponse['usuario'])) {
    $userData = $profileResponse['usuario'];
}

if (!$userData) {
    echo "❌ Dados do usuário não encontrados na resposta\n";
    echo "Resposta: " . json_encode($profileResponse, JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

echo "Dados do usuário encontrados:\n";
echo "- ID: " . ($userData['id'] ?? 'N/A') . "\n";
echo "- Nome: " . ($userData['nome'] ?? 'N/A') . "\n";
echo "- Email: " . ($userData['email'] ?? 'N/A') . "\n";
echo "- Tipo: " . ($userData['tipo'] ?? 'N/A') . "\n";
echo "- Bio: " . ($userData['bio'] ?? 'N/A') . "\n";
echo "- Telefone: " . ($userData['telefone'] ?? 'N/A') . "\n\n";

// 3. Teste de Atualização do Perfil
echo "3. TESTANDO ATUALIZAÇÃO DO PERFIL...\n";

$updateData = [
    'nome' => 'Administrador Teste',
    'bio' => 'Biografia atualizada em ' . date('Y-m-d H:i:s'),
    'telefone' => '(11) 99999-9999',
    'cidade' => 'São Paulo',
    'estado' => 'SP'
];

echo "Dados para atualização:\n";
foreach ($updateData as $key => $value) {
    echo "- $key: $value\n";
}
echo "\n";

$updateResult = makeRequest(
    $baseUrl . '/api/user/profile',
    'PUT',
    $updateData,
    [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]
);

echo "Status HTTP: " . $updateResult['http_code'] . "\n";

if ($updateResult['error']) {
    echo "❌ Erro cURL: " . $updateResult['error'] . "\n";
} elseif ($updateResult['http_code'] === 200) {
    echo "✅ Perfil atualizado com sucesso!\n";
} else {
    echo "❌ Erro na atualização. Status: " . $updateResult['http_code'] . "\n";
    echo "Resposta: " . $updateResult['response'] . "\n";
}

echo "\n";

// 4. Verificar se as alterações foram salvas
echo "4. VERIFICANDO SE AS ALTERAÇÕES FORAM SALVAS...\n";

$verifyResult = makeRequest(
    $baseUrl . '/api/user/profile',
    'GET',
    null,
    [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]
);

if ($verifyResult['http_code'] === 200) {
    $verifyResponse = extractValidJson($verifyResult['response']);
    
    if ($verifyResponse && isset($verifyResponse['data'])) {
        $updatedUserData = $verifyResponse['data'];
        
        echo "✅ Dados após atualização:\n";
        echo "- Nome: " . ($updatedUserData['nome'] ?? 'N/A') . "\n";
        echo "- Bio: " . ($updatedUserData['bio'] ?? 'N/A') . "\n";
        echo "- Telefone: " . ($updatedUserData['telefone'] ?? 'N/A') . "\n";
        echo "- Cidade: " . ($updatedUserData['cidade'] ?? 'N/A') . "\n";
        echo "- Estado: " . ($updatedUserData['estado'] ?? 'N/A') . "\n";
        
        // Verificar se as alterações foram aplicadas
        $changesApplied = true;
        foreach ($updateData as $key => $expectedValue) {
            if (($updatedUserData[$key] ?? '') !== $expectedValue) {
                echo "❌ Campo '$key' não foi atualizado corretamente\n";
                echo "   Esperado: '$expectedValue'\n";
                echo "   Atual: '" . ($updatedUserData[$key] ?? '') . "'\n";
                $changesApplied = false;
            }
        }
        
        if ($changesApplied) {
            echo "\n✅ Todas as alterações foram aplicadas corretamente!\n";
        } else {
            echo "\n❌ Algumas alterações não foram aplicadas\n";
        }
    } else {
        echo "❌ Erro ao verificar dados atualizados\n";
    }
} else {
    echo "❌ Erro ao verificar alterações. Status: " . $verifyResult['http_code'] . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
?>