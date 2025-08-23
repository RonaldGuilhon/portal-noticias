<?php
require_once 'backend/config/database.php';
require_once 'backend/config/config.php';
require_once 'backend/models/Usuario.php';

try {
    echo "=== DEBUG LOGIN ===\n";
    
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Tipo de conexão: " . get_class($conn) . "\n";
    
    // Verificar se o usuário existe
    $query = "SELECT id, nome, email, senha, tipo_usuario, ativo FROM usuarios WHERE email = :email";
    $stmt = $conn->prepare($query);
    $email = 'admin@portalnoticias.com';
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    echo "Número de resultados: " . $stmt->rowCount() . "\n";
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Usuário encontrado:\n";
        echo "ID: " . $row['id'] . "\n";
        echo "Nome: " . $row['nome'] . "\n";
        echo "Email: " . $row['email'] . "\n";
        echo "Hash da senha: " . $row['senha'] . "\n";
        echo "Tipo: " . $row['tipo_usuario'] . "\n";
        echo "Ativo: " . $row['ativo'] . "\n";
        
        // Testar verificação de senha
        $senha_teste = 'password';
        $hash_sha1 = sha1($senha_teste);
        echo "\nTeste de senha:\n";
        echo "Senha testada: $senha_teste\n";
        echo "SHA1 da senha: $hash_sha1\n";
        echo "Hash no banco: " . $row['senha'] . "\n";
        echo "Senhas coincidem: " . ($hash_sha1 === $row['senha'] ? 'SIM' : 'NÃO') . "\n";
        
        // Testar com a função verifyPassword
        echo "verifyPassword result: " . (verifyPassword($senha_teste, $row['senha']) ? 'SIM' : 'NÃO') . "\n";
        
    } else {
        echo "Usuário não encontrado!\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>