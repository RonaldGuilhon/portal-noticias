<?php
/**
 * Teste de acesso ao perfil do usuário
 */

print "=== TESTE DE ACESSO AO PERFIL ===\n";

// Primeiro, fazer login para obter o token
print "1. Fazendo login para obter token...\n";

$loginData = [
    'email' => 'admin@admin.com',
    'senha' => 'Rede@@123',
    'lembrar' => false
];

$loginUrl = 'http://localhost:8001/auth/login';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$loginError = curl_error($ch);
curl_close($ch);

if ($loginError) {
    print "✗ Erro no login: $loginError\n";
    exit(1);
}

if ($loginHttpCode !== 200) {
    print "✗ Erro HTTP no login: $loginHttpCode\n";
    print "Resposta: $loginResponse\n";
    exit(1);
}

$loginData = json_decode($loginResponse, true);
if (!$loginData || !isset($loginData['token'])) {
    print "✗ Token não encontrado na resposta do login\n";
    print "Resposta: $loginResponse\n";
    exit(1);
}

$token = $loginData['token'];
print "✓ Login bem-sucedido, token obtido\n";
print "Token: " . substr($token, 0, 50) . "...\n\n";

// Agora testar acesso ao perfil
print "2. Testando acesso ao perfil...\n";

$profileUrl = 'http://localhost:8001/api/user/profile';

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $profileUrl);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_TIMEOUT, 30);
curl_setopt($ch2, CURLOPT_VERBOSE, true);

$profileResponse = curl_exec($ch2);
$profileHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$profileError = curl_error($ch2);
curl_close($ch2);

print "URL: $profileUrl\n";
print "Código HTTP: $profileHttpCode\n";

if ($profileError) {
    print "✗ Erro cURL: $profileError\n";
} else {
    print "Resposta: $profileResponse\n\n";
    
    $profileData = json_decode($profileResponse, true);
    
    if ($profileData) {
        if (isset($profileData['success']) && $profileData['success']) {
            print "✓ Perfil carregado com sucesso\n";
            
            if (isset($profileData['data'])) {
                $userData = $profileData['data'];
                print "  - ID: {$userData['id']}\n";
                print "  - Nome: {$userData['nome']}\n";
                print "  - Email: {$userData['email']}\n";
                print "  - Tipo: {$userData['tipo']}\n";
                
                if (isset($userData['bio'])) {
                    print "  - Bio: {$userData['bio']}\n";
                }
                
                if (isset($userData['telefone'])) {
                    print "  - Telefone: {$userData['telefone']}\n";
                }
                
                if (isset($userData['cidade'])) {
                    print "  - Cidade: {$userData['cidade']}\n";
                }
                
                if (isset($userData['estado'])) {
                    print "  - Estado: {$userData['estado']}\n";
                }
            } else {
                print "✗ Dados do usuário não encontrados na resposta\n";
            }
        } else {
            print "✗ Falha ao carregar perfil\n";
            if (isset($profileData['erro'])) {
                print "Erro: {$profileData['erro']}\n";
            }
        }
    } else {
        print "✗ Resposta JSON inválida\n";
    }
}

print "\n=== TESTE CONCLUÍDO ===\n";