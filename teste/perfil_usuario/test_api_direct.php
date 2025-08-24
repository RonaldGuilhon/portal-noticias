<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/config/database.php';

echo "=== TESTE DIRETO DA API DE ALTERAÇÃO DE SENHA ===\n\n";

// 1. Fazer login via cURL para obter token
echo "1. FAZENDO LOGIN VIA cURL:\n";

$login_data = [
    'email' => 'ronaldguilhon@gmail.com',
    'senha' => 'Rede@@123'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8001/auth/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($login_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, false);

$login_response = curl_exec($ch);
$login_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status HTTP: {$login_http_code}\n";
echo "Resposta: {$login_response}\n\n";

if ($login_http_code !== 200) {
    echo "✗ Erro no login, não é possível continuar\n";
    exit;
}

$login_result = json_decode($login_response, true);
if (!isset($login_result['token'])) {
    echo "✗ Token não encontrado na resposta\n";
    exit;
}

$token = $login_result['token'];
echo "✓ Token obtido: " . substr($token, 0, 50) . "...\n\n";

// 2. Verificar hash atual no banco
echo "2. VERIFICANDO HASH ATUAL NO BANCO:\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT senha FROM usuarios WHERE email = 'ronaldguilhon@gmail.com'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $hash_antes = $stmt->fetch(PDO::FETCH_ASSOC)['senha'];
    
    echo "Hash antes: {$hash_antes}\n";
    echo "Verifica senha atual: " . (verifyPassword('Rede@@123', $hash_antes) ? 'OK' : 'FALHOU') . "\n\n";
    
} catch (Exception $e) {
    echo "Erro ao verificar banco: " . $e->getMessage() . "\n";
    exit;
}

// 3. Tentar alterar senha via API
echo "3. ALTERANDO SENHA VIA API:\n";

$password_data = [
    'current_password' => 'Rede@@123',
    'new_password' => 'TesteSenha789',
    'confirm_password' => 'TesteSenha789'
];

echo "Dados enviados: " . json_encode($password_data) . "\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8001/api/user/change-password');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($password_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, false);

$password_response = curl_exec($ch);
$password_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status HTTP: {$password_http_code}\n";
echo "Resposta: {$password_response}\n\n";

// 4. Verificar hash após alteração
echo "4. VERIFICANDO HASH APÓS ALTERAÇÃO:\n";

try {
    $stmt->execute(); // Reutilizar query anterior
    $hash_depois = $stmt->fetch(PDO::FETCH_ASSOC)['senha'];
    
    echo "Hash depois: {$hash_depois}\n";
    echo "Hash mudou: " . ($hash_antes !== $hash_depois ? 'SIM' : 'NÃO') . "\n";
    
    if ($hash_antes !== $hash_depois) {
        echo "Verifica nova senha: " . (verifyPassword('TesteSenha789', $hash_depois) ? 'OK' : 'FALHOU') . "\n";
        echo "Verifica senha antiga: " . (verifyPassword('Rede@@123', $hash_depois) ? 'OK (PROBLEMA!)' : 'FALHOU (correto)') . "\n";
    }
    
} catch (Exception $e) {
    echo "Erro ao verificar banco: " . $e->getMessage() . "\n";
}

// 5. Testar login com nova senha
if ($password_http_code === 200) {
    echo "\n5. TESTANDO LOGIN COM NOVA SENHA:\n";
    
    $new_login_data = [
        'email' => 'ronaldguilhon@gmail.com',
        'senha' => 'TesteSenha789'
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
    } else {
        echo "✗ Login com nova senha falhou!\n";
    }
}

// 6. Restaurar senha original
echo "\n6. RESTAURANDO SENHA ORIGINAL:\n";

try {
    $original_hash = hashPassword('Rede@@123');
    $restore_query = "UPDATE usuarios SET senha = :senha WHERE email = 'ronaldguilhon@gmail.com'";
    $restore_stmt = $db->prepare($restore_query);
    $restore_stmt->bindParam(':senha', $original_hash);
    
    if ($restore_stmt->execute()) {
        echo "✓ Senha original restaurada\n";
    } else {
        echo "✗ Erro ao restaurar senha original\n";
    }
    
} catch (Exception $e) {
    echo "Erro ao restaurar: " . $e->getMessage() . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
?>