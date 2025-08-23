<?php
/**
 * Teste de headers HTTP
 */

print "=== TESTE DE HEADERS HTTP ===\n";

// Simular requisição com token
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MSwiZW1haWwiOiJhZG1pbkBhZG1pbi5jb20iLCJ0aXBvIjoiYWRtaW4iLCJpYXQiOjE3MzQ0NzE0NzAsImV4cCI6MTczNDU1Nzg3MH0.test';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/api/user/profile';
$_SERVER['CONTENT_TYPE'] = 'application/json';

print "1. Testando função getallheaders()...\n";

if (function_exists('getallheaders')) {
    print "✓ Função getallheaders() existe\n";
    $headers = getallheaders();
    print "Headers encontrados:\n";
    foreach ($headers as $key => $value) {
        print "  $key: $value\n";
    }
} else {
    print "✗ Função getallheaders() não existe\n";
    print "Implementando função alternativa...\n";
    
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
    
    $headers = getallheaders();
    print "Headers encontrados (função alternativa):\n";
    foreach ($headers as $key => $value) {
        print "  $key: $value\n";
    }
}

print "\n2. Testando extração do token...\n";

$token = null;

// Verificar token no header Authorization
if (isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];
    print "Header Authorization encontrado: $authHeader\n";
    
    if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
        print "✓ Token extraído: " . substr($token, 0, 50) . "...\n";
    } else {
        print "✗ Padrão Bearer não encontrado\n";
    }
} else {
    print "✗ Header Authorization não encontrado\n";
    print "Headers disponíveis:\n";
    foreach ($headers as $key => $value) {
        print "  $key\n";
    }
}

print "\n3. Testando $_SERVER diretamente...\n";

if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    print "✓ \$_SERVER['HTTP_AUTHORIZATION'] encontrado: {$_SERVER['HTTP_AUTHORIZATION']}\n";
} else {
    print "✗ \$_SERVER['HTTP_AUTHORIZATION'] não encontrado\n";
}

print "\nVariáveis \$_SERVER relacionadas a Authorization:\n";
foreach ($_SERVER as $key => $value) {
    if (stripos($key, 'auth') !== false || stripos($key, 'bearer') !== false) {
        print "  $key: $value\n";
    }
}

print "\n=== TESTE CONCLUÍDO ===\n";