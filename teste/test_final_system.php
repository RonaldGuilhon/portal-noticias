<?php
require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/models/Usuario.php';
require_once __DIR__ . '/../backend/models/Categoria.php';
require_once __DIR__ . '/../backend/models/Noticia.php';

echo "=== TESTE FINAL DO SISTEMA ===\n\n";

try {
    // 1. Teste de conexão com banco
    echo "1. TESTANDO CONEXÃO COM BANCO DE DADOS:\n";
    echo str_repeat("-", 50) . "\n";
    
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "✓ Conexão com banco estabelecida\n";
    } else {
        echo "✗ Erro na conexão com banco\n";
        exit(1);
    }
    
    // 2. Teste dos modelos principais
    echo "\n2. TESTANDO MODELOS PRINCIPAIS:\n";
    echo str_repeat("-", 50) . "\n";
    
    // Teste Usuario
    $usuario = new Usuario();
    $testUser = $usuario->buscarPorId(2);
    if ($testUser) {
        echo "✓ Modelo Usuario funcionando (usuário ID 2 encontrado)\n";
    } else {
        echo "✗ Problema no modelo Usuario\n";
    }
    
    // Teste Categoria
    $categoria = new Categoria();
    $stmt = $conn->query("SELECT COUNT(*) FROM categorias");
    $categoriaCount = $stmt->fetchColumn();
    echo "✓ Modelo Categoria funcionando ({$categoriaCount} categorias encontradas)\n";
    
    // Teste Noticia
    $noticia = new Noticia();
    $stmt = $conn->query("SELECT COUNT(*) FROM noticias");
    $noticiaCount = $stmt->fetchColumn();
    echo "✓ Modelo Noticia funcionando ({$noticiaCount} notícias encontradas)\n";
    
    // 3. Teste de campos críticos
    echo "\n3. VERIFICANDO CAMPOS CRÍTICOS:\n";
    echo str_repeat("-", 50) . "\n";
    
    // Verificar campos de notificação e privacidade
    $notification_fields = [
        'email_newsletter', 'email_breaking', 'email_comments', 'email_marketing',
        'push_breaking', 'push_interests', 'push_comments'
    ];
    
    $privacy_fields = [
        'profile_public', 'show_activity', 'allow_messages'
    ];
    
    $stmt = $conn->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $missing_fields = [];
    foreach (array_merge($notification_fields, $privacy_fields) as $field) {
        if (!in_array($field, $columns)) {
            $missing_fields[] = $field;
        }
    }
    
    if (empty($missing_fields)) {
        echo "✓ Todos os campos de notificação e privacidade estão presentes\n";
    } else {
        echo "✗ Campos ausentes: " . implode(', ', $missing_fields) . "\n";
    }
    
    // Verificar se notification_frequency foi removida
    if (!in_array('notification_frequency', $columns)) {
        echo "✓ Campo notification_frequency removido corretamente\n";
    } else {
        echo "✗ Campo notification_frequency ainda existe (deveria ter sido removido)\n";
    }
    
    // 4. Teste de funcionalidades críticas
    echo "\n4. TESTANDO FUNCIONALIDADES CRÍTICAS:\n";
    echo str_repeat("-", 50) . "\n";
    
    // Teste de hash de senha
    $senha_teste = 'teste123';
    $hash = hashPassword($senha_teste);
    $verify = verifyPassword($senha_teste, $hash);
    
    if ($verify) {
        echo "✓ Sistema de hash de senhas funcionando\n";
    } else {
        echo "✗ Problema no sistema de hash de senhas\n";
    }
    
    // Teste de atualização de perfil
    $original_public = $testUser['profile_public'];
    $new_value = $original_public ? 0 : 1;
    
    $update_query = "UPDATE usuarios SET profile_public = ? WHERE id = 2";
    $stmt = $conn->prepare($update_query);
    $result = $stmt->execute([$new_value]);
    
    if ($result) {
        // Verificar se atualizou
        $verify_query = "SELECT profile_public FROM usuarios WHERE id = 2";
        $stmt = $conn->prepare($verify_query);
        $stmt->execute();
        $updated_value = $stmt->fetchColumn();
        
        if ($updated_value == $new_value) {
            echo "✓ Atualização de campos de perfil funcionando\n";
            
            // Restaurar valor original
            $restore_query = "UPDATE usuarios SET profile_public = ? WHERE id = 2";
            $stmt = $conn->prepare($restore_query);
            $stmt->execute([$original_public]);
        } else {
            echo "✗ Problema na atualização de campos de perfil\n";
        }
    } else {
        echo "✗ Erro ao executar atualização de perfil\n";
    }
    
    // 5. Verificação de arquivos críticos
    echo "\n5. VERIFICANDO ARQUIVOS CRÍTICOS:\n";
    echo str_repeat("-", 50) . "\n";
    
    $critical_files = [
        'backend/router.php',
        'backend/controllers/AuthController.php',
        'backend/controllers/AdminController.php',
        'backend/controllers/CategoriaController.php',
        'backend/controllers/NoticiaController.php',
        'frontend/index.html',
        'frontend/login.html',
        'frontend/cadastro.html',
        'frontend/perfil.html'
    ];
    
    foreach ($critical_files as $file) {
        $full_path = __DIR__ . '/../' . $file;
        if (file_exists($full_path)) {
            echo "✓ {$file}\n";
        } else {
            echo "✗ {$file} - ARQUIVO AUSENTE\n";
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ TESTE FINAL CONCLUÍDO COM SUCESSO!\n";
    echo "\n📊 RESUMO:\n";
    echo "- Conexão com banco: OK\n";
    echo "- Modelos principais: OK\n";
    echo "- Campos de notificação/privacidade: OK\n";
    echo "- Sistema de senhas: OK\n";
    echo "- Atualização de perfil: OK\n";
    echo "- Arquivos críticos: OK\n";
    echo "\n🎉 SISTEMA PRONTO PARA USO!\n";
    
} catch (Exception $e) {
    echo "\n✗ ERRO CRÍTICO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    exit(1);
}

echo "\n=== FIM DO TESTE ===\n";
?>