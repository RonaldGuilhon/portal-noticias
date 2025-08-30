<?php
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/models/Usuario.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Dados do usuário de teste (apenas campos que existem)
    $dadosUsuario = [
        'nome' => 'Usuário Teste',
        'email' => 'teste@portal.com',
        'senha' => password_hash('123456', PASSWORD_DEFAULT),
        'bio' => 'Biografia do usuário de teste para verificar carregamento de dados.',
        'telefone' => '(11) 99999-9999',
        'data_nascimento' => '1990-01-01',
        'genero' => 'masculino',
        'cidade' => 'São Paulo',
        'estado' => 'SP',
        'show_images' => 1,
        'auto_play_videos' => 1,
        'dark_mode' => 0,
        'email_newsletter' => 1,
        'email_breaking' => 1,
        'email_comments' => 1,
        'email_marketing' => 0,
        'push_breaking' => 1,
        'push_interests' => 1,
        'push_comments' => 1,
        'profile_public' => 1,
        'show_activity' => 1,
        'allow_messages' => 1,
        'favorite_categories' => json_encode([1, 2, 3]),
        'language_preference' => 'pt',
        'data_criacao' => date('Y-m-d H:i:s'),
        'ativo' => 1
    ];
    
    // Verificar se o usuário já existe
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$dadosUsuario['email']]);
    
    if ($stmt->rowCount() > 0) {
        echo "Usuário de teste já existe. Atualizando dados...\n";
        
        // Atualizar usuário existente
        $updateFields = [];
        $updateValues = [];
        
        foreach ($dadosUsuario as $campo => $valor) {
            if ($campo !== 'email') {
                $updateFields[] = "$campo = ?";
                $updateValues[] = $valor;
            }
        }
        $updateValues[] = $dadosUsuario['email'];
        
        $updateSql = "UPDATE usuarios SET " . implode(', ', $updateFields) . " WHERE email = ?";
        $updateStmt = $db->prepare($updateSql);
        
        if ($updateStmt->execute($updateValues)) {
            echo "✅ Usuário de teste atualizado com sucesso!\n";
        } else {
            echo "❌ Erro ao atualizar usuário de teste.\n";
            print_r($updateStmt->errorInfo());
        }
    } else {
        // Criar novo usuário
        $campos = implode(', ', array_keys($dadosUsuario));
        $placeholders = ':' . implode(', :', array_keys($dadosUsuario));
        
        $sql = "INSERT INTO usuarios ($campos) VALUES ($placeholders)";
        $stmt = $db->prepare($sql);
        
        if ($stmt->execute($dadosUsuario)) {
            echo "✅ Usuário de teste criado com sucesso!\n";
        } else {
            echo "❌ Erro ao criar usuário de teste.\n";
            print_r($stmt->errorInfo());
        }
    }
    
    // Verificar se o usuário foi criado/atualizado corretamente
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$dadosUsuario['email']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        echo "\n📋 Dados do usuário de teste:\n";
        echo "ID: {$usuario['id']}\n";
        echo "Nome: {$usuario['nome']}\n";
        echo "Email: {$usuario['email']}\n";
        echo "Bio: {$usuario['bio']}\n";
        echo "Telefone: {$usuario['telefone']}\n";
        echo "Cidade: {$usuario['cidade']}\n";
        echo "Estado: {$usuario['estado']}\n";
        echo "Foto perfil: {$usuario['foto_perfil']}\n";
        echo "Ativo: {$usuario['ativo']}\n";
        echo "\n✅ Usuário de teste está pronto para uso!\n";
        echo "\n🔑 Credenciais de login:\n";
        echo "Email: teste@portal.com\n";
        echo "Senha: 123456\n";
    } else {
        echo "❌ Erro: Usuário não foi encontrado após criação/atualização.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>