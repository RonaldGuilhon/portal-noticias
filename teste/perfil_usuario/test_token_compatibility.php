<?php
/**
 * Teste para verificar incompatibilidade entre tokens salvos no login e perfil
 * 
 * PROBLEMA IDENTIFICADO:
 * - login.html salva token em localStorage como 'portal-user' (objeto JSON)
 * - perfil.html procura token em localStorage como 'authToken' (string simples)
 * 
 * Isso explica por que o usuário consegue fazer login mas não acessa o perfil
 */

echo "=== TESTE DE COMPATIBILIDADE DE TOKENS ===\n\n";

// Simular dados salvos pelo login.html
$loginData = [
    'id' => 1,
    'nome' => 'Administrador',
    'email' => 'ronaldguilhon@gmail.com',
    'tipo' => 'admin',
    'token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.exemplo.token'
];

echo "1. DADOS SALVOS PELO LOGIN:\n";
echo "   Chave: 'portal-user'\n";
echo "   Valor: " . json_encode($loginData) . "\n\n";

echo "2. DADOS PROCURADOS PELO PERFIL:\n";
echo "   Chave: 'authToken'\n";
echo "   Valor esperado: string simples do token\n\n";

echo "3. PROBLEMA:\n";
echo "   ❌ perfil.html não encontra 'authToken'\n";
echo "   ❌ Usuário é redirecionado para login\n";
echo "   ❌ Loop de redirecionamento pode ocorrer\n\n";

echo "4. SOLUÇÕES POSSÍVEIS:\n";
echo "   A) Modificar perfil.html para usar 'portal-user'\n";
echo "   B) Modificar login.html para salvar também 'authToken'\n";
echo "   C) Criar função de compatibilidade\n\n";

echo "5. TESTE DE VERIFICAÇÃO:\n";

// Simular localStorage do navegador
$localStorage = [];

// Simular login
$localStorage['portal-user'] = json_encode($loginData);
echo "   ✅ Login realizado - token salvo em 'portal-user'\n";

// Simular acesso ao perfil
$authToken = $localStorage['authToken'] ?? null;
if (!$authToken) {
    echo "   ❌ Perfil não encontra 'authToken' - redirecionamento para login\n";
} else {
    echo "   ✅ Perfil encontra token - acesso permitido\n";
}

echo "\n=== CONCLUSÃO ===\n";
echo "O problema é a incompatibilidade entre as chaves usadas para armazenar o token.\n";
echo "É necessário padronizar o armazenamento entre login.html e perfil.html.\n";
?>