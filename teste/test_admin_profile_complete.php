<?php
// Teste completo da API de perfil para admin@portal.com
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/../backend/models/Usuario.php';
require_once __DIR__ . '/../backend/controllers/AuthController.php';

try {
    echo "=== TESTE COMPLETO DA API DE PERFIL ADMIN ===\n";
    echo "Data: " . date('Y-m-d H:i:s') . "\n\n";
    
    // 1. Fazer login via API
    echo "1. FAZENDO LOGIN VIA API:\n";
    echo str_repeat("-", 40) . "\n";
    
    $loginData = [
        'email' => 'admin@portal.com',
        'senha' => 'Rede@@123'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/auth/login');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $loginResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Status HTTP: $httpCode\n";
    echo "Resposta: $loginResponse\n\n";
    
    if ($httpCode === 200) {
        $loginResult = json_decode($loginResponse, true);
        
        if (isset($loginResult['success']) && $loginResult['success'] && isset($loginResult['token'])) {
            $token = $loginResult['token'];
            echo "✅ Login bem-sucedido! Token obtido.\n";
            echo "Token: " . substr($token, 0, 50) . "...\n\n";
            
            // 2. Testar API de perfil
            echo "2. TESTANDO API DE PERFIL:\n";
            echo str_repeat("-", 40) . "\n";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/user/profile');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $profileResponse = curl_exec($ch);
            $profileHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            echo "Status HTTP: $profileHttpCode\n";
            echo "Resposta: $profileResponse\n\n";
            
            if ($profileHttpCode === 200) {
                $profileResult = json_decode($profileResponse, true);
                
                if (isset($profileResult['success']) && $profileResult['success'] && isset($profileResult['data'])) {
                    echo "✅ API de perfil funcionando!\n";
                    $userData = $profileResult['data'];
                    
                    echo "\n=== DADOS DO PERFIL ===\n";
                    echo "ID: " . ($userData['id'] ?? 'N/A') . "\n";
                    echo "Nome: " . ($userData['nome'] ?? 'N/A') . "\n";
                    echo "Email: " . ($userData['email'] ?? 'N/A') . "\n";
                    echo "Tipo: " . ($userData['tipo'] ?? 'N/A') . "\n";
                    echo "Bio: " . ($userData['bio'] ?? 'N/A') . "\n";
                    echo "Foto perfil: " . ($userData['foto_perfil'] ?? 'N/A') . "\n";
                    echo "Telefone: " . ($userData['telefone'] ?? 'N/A') . "\n";
                    echo "Cidade: " . ($userData['cidade'] ?? 'N/A') . "\n";
                    echo "Estado: " . ($userData['estado'] ?? 'N/A') . "\n";
                    echo "Data criação: " . ($userData['data_criacao'] ?? 'N/A') . "\n";
                    
                    // Verificar se todos os campos essenciais estão presentes
                    $camposEssenciais = ['id', 'nome', 'email', 'tipo'];
                    $camposVazios = [];
                    
                    foreach ($camposEssenciais as $campo) {
                        if (empty($userData[$campo])) {
                            $camposVazios[] = $campo;
                        }
                    }
                    
                    if (empty($camposVazios)) {
                        echo "\n✅ TODOS OS CAMPOS ESSENCIAIS ESTÃO PREENCHIDOS!\n";
                    } else {
                        echo "\n❌ CAMPOS VAZIOS: " . implode(', ', $camposVazios) . "\n";
                    }
                    
                    // Verificar preferências
                    if (isset($userData['preferencias'])) {
                        echo "\n=== PREFERÊNCIAS ===\n";
                        if (is_array($userData['preferencias'])) {
                            print_r($userData['preferencias']);
                        } else {
                            echo "Preferências: " . $userData['preferencias'] . "\n";
                        }
                    } else {
                        echo "\n❌ PREFERÊNCIAS: Não encontradas\n";
                    }
                    
                } else {
                    echo "❌ Erro na estrutura da resposta da API de perfil\n";
                    if (isset($profileResult['erro'])) {
                        echo "Erro: " . $profileResult['erro'] . "\n";
                    }
                }
            } else {
                echo "❌ Erro HTTP na API de perfil: $profileHttpCode\n";
                echo "Resposta: $profileResponse\n";
            }
            
        } else {
            echo "❌ Erro na estrutura da resposta de login\n";
            if (isset($loginResult['erro'])) {
                echo "Erro: " . $loginResult['erro'] . "\n";
            }
        }
    } else {
        echo "❌ Erro HTTP no login: $httpCode\n";
        echo "Resposta: $loginResponse\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
?>