<?php
require_once 'config-local.php';
require_once 'backend/config/config.php';
require_once 'backend/models/Usuario.php';

// Iniciar sessão
session_start();

echo "=== TESTE SIMPLES DA API DE PERFIL ===\n\n";

// Simular login do usuário
$_SESSION['user_id'] = 2;
$_SESSION['user_email'] = 'ronaldguilhon@gmail.com';
$_SESSION['user_name'] = 'Ronald Christian Guilhon Simplicio';
$_SESSION['logged_in'] = true;

echo "Sessão iniciada para usuário ID: 2\n\n";

// Testar diretamente o modelo Usuario
try {
    $database = new Database();
    $db = $database->getConnection();
    $usuario = new Usuario($db);
    
    echo "Buscando dados do usuário ID 2...\n";
    
    // Buscar usuário por ID
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([2]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userData) {
        echo "\n=== DADOS DO USUÁRIO ===\n";
        echo "ID: {$userData['id']}\n";
        echo "Nome: {$userData['nome']}\n";
        echo "Email: {$userData['email']}\n";
        echo "Bio: " . ($userData['bio'] ?: 'NULL/VAZIO') . "\n";
        echo "Foto: " . ($userData['foto_perfil'] ?: 'N/A') . "\n";
        echo "Data criação: {$userData['data_criacao']}\n";
        
        // Simular resposta da API
        $apiResponse = [
            'success' => true,
            'data' => [
                'id' => $userData['id'],
                'nome' => $userData['nome'],
                'email' => $userData['email'],
                'bio' => $userData['bio'],
                'foto_perfil' => $userData['foto_perfil'],
                'data_criacao' => $userData['data_criacao']
            ]
        ];
        
        echo "\n=== RESPOSTA SIMULADA DA API ===\n";
        echo json_encode($apiResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
    } else {
        echo "✗ Usuário não encontrado\n";
    }
    
} catch(Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>