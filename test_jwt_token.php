<?php
require_once 'backend/utils/JWTHelper.php';
require_once 'backend/config/database.php';
require_once 'backend/models/Usuario.php';

try {
    $jwtHelper = new JWTHelper();
    
    // Gerar token para o usuário ID 1 (admin)
    $payload = [
        'id' => 1,
        'email' => 'admin@portal.com',
        'tipo_usuario' => 'admin'
    ];
    
    $token = $jwtHelper->gerarToken($payload);
    echo "Token gerado: " . $token . PHP_EOL;
    
    // Testar validação do token
    $validatedPayload = $jwtHelper->validarToken($token);
    echo "Token validado com sucesso!" . PHP_EOL;
    echo "Payload: " . json_encode($validatedPayload) . PHP_EOL;
    
    // Testar busca do usuário
    $db = new Database();
    $conn = $db->getConnection();
    $usuario = new Usuario($conn);
    $resultado = $usuario->buscarPorId($validatedPayload['id']);
    
    if ($resultado) {
        echo "Usuário encontrado: " . $usuario->nome . " (" . $usuario->email . ")" . PHP_EOL;
    } else {
        echo "Usuário não encontrado!" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . PHP_EOL;
}
?>