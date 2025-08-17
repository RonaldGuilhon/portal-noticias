<?php
require_once 'backend/config/database.php';
require_once 'backend/config/config.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Hash SHA1 da senha 'password'
    $hash_sha1 = sha1('password');
    
    echo "Hash SHA1 para 'password': $hash_sha1\n";
    
    // Atualizar a senha do usuário admin
    $query = "UPDATE usuarios SET senha = :senha WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':senha', $hash_sha1);
    $stmt->bindParam(':email', $email);
    
    $email = 'admin@portalnoticias.com';
    
    if ($stmt->execute()) {
        echo "Senha do administrador atualizada com sucesso!\n";
        echo "Email: admin@portalnoticias.com\n";
        echo "Senha: password\n";
    } else {
        echo "Erro ao atualizar a senha.\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>