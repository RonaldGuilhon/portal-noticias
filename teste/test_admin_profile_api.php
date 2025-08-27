<?php
// Teste da API de perfil para usuário admin@portal.com

echo "=== TESTE DA API DE PERFIL ===\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n\n";

// Carregar token salvo
$tokenFile = __DIR__ . '/admin_token.txt';
if (!file_exists($tokenFile)) {
    echo "❌ ERRO: Token não encontrado. Execute primeiro o teste de login.\n";
    exit(1);
}

$token = trim(file_get_contents($tokenFile));
echo "🔑 Token carregado: " . substr($token, 0, 50) . "...\n\n";

// URL da API
$apiUrl = 'http://localhost:8001/api/user/profile';
echo "🌐 URL da API: $apiUrl\n\n";

// Configurar cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

echo "📡 Enviando requisição GET...\n";

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

// Limpar resposta - pegar apenas a última linha JSON válida
$lines = explode("\n", trim($response));
$jsonResponse = '';
for ($i = count($lines) - 1; $i >= 0; $i--) {
    $line = trim($lines[$i]);
    if (!empty($line) && (substr($line, 0, 1) === '{' || substr($line, 0, 1) === '[')) {
        $jsonResponse = $line;
        break;
    }
}

echo "📋 JSON limpo:\n";
echo $jsonResponse . "\n\n";

// Decodificar JSON
$responseData = json_decode($jsonResponse, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ ERRO JSON: " . json_last_error_msg() . "\n";
    exit(1);
}

echo "=== ANÁLISE DA RESPOSTA ===\n";

if ($httpCode === 200) {
    echo "✅ STATUS: Sucesso (200)\n";
    
    if (isset($responseData['success']) && $responseData['success']) {
        echo "✅ PERFIL: Dados obtidos com sucesso\n";
        
        // Verificar dados do usuário (pode estar em 'usuario', 'dados', 'data' ou 'dados.usuario')
        $usuario = null;
        if (isset($responseData['data'])) {
            $usuario = $responseData['data'];
        } elseif (isset($responseData['usuario'])) {
            $usuario = $responseData['usuario'];
        } elseif (isset($responseData['dados']['usuario'])) {
            $usuario = $responseData['dados']['usuario'];
        } elseif (isset($responseData['dados'])) {
            $usuario = $responseData['dados'];
        }
        
        if ($usuario) {
            echo "\n=== DADOS DO PERFIL ===\n";
            echo "ID: " . ($usuario['id'] ?? 'N/A') . "\n";
            echo "Nome: " . ($usuario['nome'] ?? 'N/A') . "\n";
            echo "Email: " . ($usuario['email'] ?? 'N/A') . "\n";
            echo "Tipo: " . ($usuario['tipo'] ?? 'N/A') . "\n";
            echo "Bio: " . ($usuario['bio'] ?? 'N/A') . "\n";
            echo "Foto perfil: " . ($usuario['foto_perfil'] ?? 'N/A') . "\n";
            echo "Telefone: " . ($usuario['telefone'] ?? 'N/A') . "\n";
            echo "Cidade: " . ($usuario['cidade'] ?? 'N/A') . "\n";
            echo "Estado: " . ($usuario['estado'] ?? 'N/A') . "\n";
            echo "Data criação: " . ($usuario['data_criacao'] ?? 'N/A') . "\n";
            
            // Verificar preferências
            if (isset($usuario['preferencias'])) {
                echo "\n=== PREFERÊNCIAS ===\n";
                if (is_string($usuario['preferencias'])) {
                    $prefs = json_decode($usuario['preferencias'], true);
                    if ($prefs) {
                        foreach ($prefs as $key => $value) {
                            echo "$key: $value\n";
                        }
                    } else {
                        echo "Preferências (string): " . $usuario['preferencias'] . "\n";
                    }
                } else {
                    echo "Preferências (array): " . print_r($usuario['preferencias'], true) . "\n";
                }
            } else {
                echo "\n❌ PREFERÊNCIAS: Não encontradas\n";
            }
            
        } else {
            echo "❌ DADOS: Não encontrados na resposta\n";
        }
        
    } else {
        echo "❌ PERFIL: Falha ao obter dados\n";
        if (isset($responseData['erro'])) {
            echo "❌ ERRO: " . $responseData['erro'] . "\n";
        }
        if (isset($responseData['mensagem'])) {
            echo "📝 MENSAGEM: " . $responseData['mensagem'] . "\n";
        }
    }
    
} else {
    echo "❌ STATUS: Erro ($httpCode)\n";
    if (isset($responseData['erro'])) {
        echo "❌ ERRO: " . $responseData['erro'] . "\n";
    }
    if (isset($responseData['mensagem'])) {
        echo "📝 MENSAGEM: " . $responseData['mensagem'] . "\n";
    }
}

echo "\n=== RESUMO ===\n";
echo "API Perfil: " . ($httpCode === 200 && isset($responseData['success']) && $responseData['success'] ? '✅ FUNCIONANDO' : '❌ COM PROBLEMAS') . "\n";
echo "Dados retornados: " . (isset($usuario) && $usuario ? '✅ SIM' : '❌ NÃO') . "\n";
echo "Campos principais: " . (isset($usuario['nome']) && isset($usuario['email']) ? '✅ SIM' : '❌ NÃO') . "\n";
echo "Campos extras: " . (isset($usuario['bio']) || isset($usuario['telefone']) || isset($usuario['cidade']) ? '✅ SIM' : '❌ NÃO') . "\n";

?>