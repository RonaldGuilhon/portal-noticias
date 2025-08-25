<?php
/**
 * Teste completo de persistência e retorno das preferências favorite_categories e language_preference
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== TESTE DE PERSISTÊNCIA E RETORNO DAS PREFERÊNCIAS ===\n\n";

// Configuração do teste
$baseUrl = 'http://localhost:8000';
$email = 'admin@admin.com';
$senha = 'admin123';

// Função para fazer requisições HTTP usando cURL
function fazerRequisicao($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['response' => $response, 'http_code' => $httpCode];
}

try {
    // 1. Login
    echo "1️⃣ FAZENDO LOGIN...\n";
    $loginData = json_encode([
        'email' => $email,
        'senha' => $senha
    ]);
    
    $loginResult = fazerRequisicao(
          $baseUrl . '/auth/login',
          'POST',
          $loginData,
          ['Content-Type: application/json']
      );
     
     echo "Resposta do login: " . $loginResult['response'] . "\n";
     $loginResponse = json_decode($loginResult['response'], true);
     
     if (!$loginResponse || !isset($loginResponse['success']) || !$loginResponse['success']) {
         echo "❌ ERRO: Falha no login: " . ($loginResponse['mensagem'] ?? 'Erro desconhecido') . "\n";
         echo "Teste interrompido.\n";
         return;
     }
     
     $token = $loginResponse['token'];
    echo "✅ Login realizado com sucesso\n";
    echo "Token: " . substr($token, 0, 20) . "...\n\n";
    
    // 2. OBTER PERFIL INICIAL
    echo "2️⃣ OBTENDO PERFIL INICIAL...\n";
    
    $profileResult = fazerRequisicao(
        $baseUrl . '/api/user/profile',
        'GET',
        null,
        [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]
    );
    
    echo "Resposta do perfil: " . $profileResult['response'] . "\n";
    $profileResponse = json_decode($profileResult['response'], true);
    
    if (!$profileResponse || !isset($profileResponse['success']) || !$profileResponse['success']) {
        throw new Exception('Falha ao obter perfil: ' . ($profileResponse['message'] ?? 'Resposta inválida'));
    }
    
    $initialProfile = $profileResponse['data'];
    echo "✅ Perfil obtido com sucesso\n";
    echo "favorite_categories inicial: " . json_encode($initialProfile['favorite_categories']) . "\n";
    echo "language_preference inicial: " . $initialProfile['language_preference'] . "\n\n";
    
    // 3. ATUALIZAR favorite_categories
    echo "3️⃣ ATUALIZANDO FAVORITE_CATEGORIES...\n";
    $newCategories = ["tecnologia", "ciencia", "educacao"];
    $updateCategoriesData = json_encode([
        'favorite_categories' => $newCategories
    ]);
    
    $updateResult = fazerRequisicao(
         $baseUrl . '/api/user/profile',
         'PUT',
         $updateCategoriesData,
         [
             'Content-Type: application/json',
             'Authorization: Bearer ' . $token
         ]
     );
     
     echo "Resposta da atualização de categorias: " . $updateResult['response'] . "\n";
     $updateResponse = json_decode($updateResult['response'], true);
     
     if (!$updateResponse || !isset($updateResponse['success']) || !$updateResponse['success']) {
         throw new Exception('Falha ao atualizar categorias: ' . ($updateResponse['message'] ?? 'Resposta inválida'));
     }
    
    echo "✅ Categorias atualizadas com sucesso\n";
    echo "Novas categorias enviadas: " . json_encode($newCategories) . "\n\n";
    
    // 4. ATUALIZAR language_preference
    echo "4️⃣ ATUALIZANDO LANGUAGE_PREFERENCE...\n";
    $newLanguage = 'en-US';
    $updateLanguageData = json_encode([
        'language_preference' => $newLanguage
    ]);
    
    $updateLangResult = fazerRequisicao(
        $baseUrl . '/api/user/profile',
        'PUT',
        $updateLanguageData,
        [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]
    );
    
    echo "Resposta da atualização de idioma: " . $updateLangResult['response'] . "\n";
    $updateLangResponse = json_decode($updateLangResult['response'], true);
    
    if (!$updateLangResponse || !isset($updateLangResponse['success']) || !$updateLangResponse['success']) {
        throw new Exception('Falha ao atualizar idioma: ' . ($updateLangResponse['message'] ?? 'Resposta inválida'));
    }
    
    echo "✅ Idioma atualizado com sucesso\n";
    echo "Novo idioma enviado: $newLanguage\n\n";
    
    // 5. VERIFICAR PERSISTÊNCIA VIA API
    echo "5️⃣ VERIFICANDO PERSISTÊNCIA VIA API...\n";
    $verifyResult = fazerRequisicao(
        $baseUrl . '/api/user/profile',
        'GET',
        null,
        [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]
    );
    
    $verifyResponse = json_decode($verifyResult['response'], true);
    
    if (!$verifyResponse['success']) {
        throw new Exception('Falha ao verificar perfil: ' . $verifyResponse['message']);
    }
    
    $updatedProfile = $verifyResponse['data'];
    echo "✅ Perfil verificado via API\n";
    echo "favorite_categories retornado: " . json_encode($updatedProfile['favorite_categories']) . "\n";
    echo "language_preference retornado: " . $updatedProfile['language_preference'] . "\n\n";
    
    // 6. VERIFICAR PERSISTÊNCIA NO BANCO DE DADOS
    echo "6️⃣ VERIFICANDO PERSISTÊNCIA NO BANCO...\n";
    
    $pdo = new PDO('mysql:host=localhost;dbname=portal_noticias', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare('SELECT favorite_categories, language_preference FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $dbData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "✅ Dados verificados no banco\n";
    echo "favorite_categories no banco: " . $dbData['favorite_categories'] . "\n";
    echo "language_preference no banco: " . $dbData['language_preference'] . "\n\n";
    
    // 7. VALIDAÇÃO DOS RESULTADOS
    echo "7️⃣ VALIDAÇÃO DOS RESULTADOS...\n";
    
    // Validar favorite_categories
    $expectedCategories = ["tecnologia", "ciencia", "educacao"];
    $categoriesMatch = json_encode($updatedProfile['favorite_categories']) === json_encode($expectedCategories);
    echo ($categoriesMatch ? "✅" : "❌") . " favorite_categories API vs Enviado: " . 
         ($categoriesMatch ? "CORRETO" : "INCORRETO") . "\n";
    
    $dbCategoriesDecoded = json_decode($dbData['favorite_categories'], true);
    $categoriesDbMatch = json_encode($dbCategoriesDecoded) === json_encode($expectedCategories);
    echo ($categoriesDbMatch ? "✅" : "❌") . " favorite_categories Banco vs Enviado: " . 
         ($categoriesDbMatch ? "CORRETO" : "INCORRETO") . "\n";
    
    // Validar language_preference
    $languageMatch = $updatedProfile['language_preference'] === $newLanguage;
    echo ($languageMatch ? "✅" : "❌") . " language_preference API vs Enviado: " . 
         ($languageMatch ? "CORRETO" : "INCORRETO") . "\n";
    
    $languageDbMatch = $dbData['language_preference'] === $newLanguage;
    echo ($languageDbMatch ? "✅" : "❌") . " language_preference Banco vs Enviado: " . 
         ($languageDbMatch ? "CORRETO" : "INCORRETO") . "\n\n";
    
    // 8. RESULTADO FINAL
    echo "8️⃣ RESULTADO FINAL\n";
    $allTestsPassed = $categoriesMatch && $categoriesDbMatch && $languageMatch && $languageDbMatch;
    
    if ($allTestsPassed) {
        echo "🎉 TODOS OS TESTES PASSARAM! \n";
        echo "✅ Persistência: FUNCIONANDO\n";
        echo "✅ Retorno via API: FUNCIONANDO\n";
        echo "✅ Consistência Banco/API: FUNCIONANDO\n";
    } else {
        echo "❌ ALGUNS TESTES FALHARAM!\n";
        echo "Verifique os detalhes acima.\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Teste interrompido.\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>