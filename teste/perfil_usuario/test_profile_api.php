<?php
require_once 'config-local.php';
require_once 'backend/config/config.php';

// Iniciar sessão
session_start();

echo "=== TESTE DA API DE PERFIL ===\n\n";

// Simular login do usuário
$_SESSION['user_id'] = 2;
$_SESSION['user_email'] = 'ronaldguilhon@gmail.com';
$_SESSION['user_name'] = 'Ronald Christian Guilhon Simplicio';
$_SESSION['logged_in'] = true;

echo "Sessão iniciada para usuário ID: 2\n";
echo "Email: ronaldguilhon@gmail.com\n\n";

// Simular chamada para a API de perfil
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/api/user/profile';

// Incluir o controlador de autenticação
require_once 'backend/controllers/AuthController.php';

// Simular requisição GET para /api/user/profile
$_GET['action'] = 'profile';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Criar instância do controlador
$controller = new AuthController();

echo "Chamando método handleRequest()...\n\n";

// Capturar a saída
ob_start();
$controller->handleRequest();
$output = ob_get_clean();

echo "Resposta da API:\n";
echo $output . "\n";

// Decodificar JSON para análise
$response = json_decode($output, true);
if ($response) {
    echo "\n=== ANÁLISE DA RESPOSTA ===\n";
    if (isset($response['sucesso']) && $response['sucesso']) {
        echo "✓ API retornou sucesso\n";
        if (isset($response['usuario'])) {
            $usuario = $response['usuario'];
            echo "Nome: " . ($usuario['nome'] ?? 'N/A') . "\n";
            echo "Email: " . ($usuario['email'] ?? 'N/A') . "\n";
            echo "Bio: " . ($usuario['bio'] ?? 'NULL/VAZIO') . "\n";
            echo "Foto: " . ($usuario['foto_perfil'] ?? 'N/A') . "\n";
        }
    } else {
        echo "✗ API retornou erro: " . ($response['erro'] ?? 'Erro desconhecido') . "\n";
    }
} else {
    echo "✗ Resposta não é um JSON válido\n";
}
?>