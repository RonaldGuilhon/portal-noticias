<?php
/**
 * Script de teste para Rate Limiting
 * Portal de Notícias
 */

require_once __DIR__ . '/utils/RateLimiter.php';

echo "=== Teste do Sistema de Rate Limiting ===\n\n";

// Teste 1: Verificar funcionamento básico
echo "Teste 1: Funcionamento básico\n";
$rate_limiter = new RateLimiter(null, 5, 60); // 5 requisições por minuto
$identifier = 'test_user_1';

for ($i = 1; $i <= 7; $i++) {
    $allowed = $rate_limiter->isAllowed($identifier, 5, 60);
    $info = $rate_limiter->getLimitInfo($identifier, 5, 60);
    
    echo "Requisição $i: " . ($allowed ? 'PERMITIDA' : 'BLOQUEADA') . 
         " - Restantes: {$info['remaining']}/{$info['limit']}\n";
    
    if (!$allowed) {
        echo "Reset em: " . date('H:i:s', $info['reset_time']) . "\n";
    }
}

echo "\n";

// Teste 2: Diferentes identificadores
echo "Teste 2: Diferentes identificadores\n";
$rate_limiter2 = new RateLimiter(null, 3, 60);

$users = ['user_a', 'user_b', 'user_c'];
foreach ($users as $user) {
    for ($i = 1; $i <= 4; $i++) {
        $allowed = $rate_limiter2->isAllowed($user, 3, 60);
        $info = $rate_limiter2->getLimitInfo($user, 3, 60);
        
        echo "$user - Req $i: " . ($allowed ? 'OK' : 'BLOCKED') . 
             " ({$info['remaining']}/{$info['limit']})\n";
    }
}

echo "\n";

// Teste 3: Limpeza de dados antigos
echo "Teste 3: Limpeza de dados antigos\n";
$rate_limiter3 = new RateLimiter(null, 2, 5); // 2 requisições por 5 segundos
$test_user = 'cleanup_test';

echo "Fazendo 2 requisições...\n";
for ($i = 1; $i <= 2; $i++) {
    $allowed = $rate_limiter3->isAllowed($test_user, 2, 5);
    echo "Req $i: " . ($allowed ? 'OK' : 'BLOCKED') . "\n";
}

echo "Tentando 3ª requisição (deve ser bloqueada)...\n";
$allowed = $rate_limiter3->isAllowed($test_user, 2, 5);
echo "Req 3: " . ($allowed ? 'OK' : 'BLOCKED') . "\n";

echo "Aguardando 6 segundos para limpeza...\n";
sleep(6);

echo "Tentando nova requisição após limpeza...\n";
$allowed = $rate_limiter3->isAllowed($test_user, 2, 5);
echo "Nova req: " . ($allowed ? 'OK' : 'BLOCKED') . "\n";

echo "\n";

// Teste 4: Identificadores automáticos
echo "Teste 4: Identificadores automáticos\n";

// Simular diferentes cenários
$_SESSION = [];
$_SERVER['REMOTE_ADDR'] = '192.168.1.100';
echo "IP sem sessão: " . RateLimiter::getIdentifier() . "\n";

$_SESSION['usuario_id'] = 123;
echo "Usuário logado: " . RateLimiter::getIdentifier() . "\n";

// Simular header de autorização
function getallheaders() {
    return ['Authorization' => 'Bearer abc123def456'];
}
$_SESSION = [];
echo "Com token: " . RateLimiter::getIdentifier() . "\n";

echo "\n=== Teste concluído ===\n";

// Mostrar arquivo de dados se existir
$storage_path = __DIR__ . '/logs/rate_limits.json';
if (file_exists($storage_path)) {
    echo "\nConteúdo do arquivo de dados:\n";
    echo file_get_contents($storage_path);
    echo "\n";
}