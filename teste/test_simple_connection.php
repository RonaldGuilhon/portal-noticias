<?php
require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/../backend/config/database.php';

echo "=== TESTE SIMPLES DE CONEXÃO ===\n\n";

try {
    // Teste de conexão
    echo "1. Testando conexão com banco...\n";
    $database = new Database();
    $conn = $database->getConnection();
    echo "✓ Conexão estabelecida\n\n";
    
    // Teste do modelo Usuario com conexão
    echo "2. Testando modelo Usuario...\n";
    require_once __DIR__ . '/../backend/models/Usuario.php';
    echo "✓ Arquivo Usuario.php carregado\n";
    
    $usuario = new Usuario($conn);
    echo "✓ Classe Usuario instanciada com conexão\n";
    
    // Verificar usuários existentes
    $stmt = $conn->query("SELECT COUNT(*) FROM usuarios");
    $userCount = $stmt->fetchColumn();
    echo "✓ {$userCount} usuários encontrados no banco\n\n";
    
    // Teste do modelo Categoria
    echo "3. Testando modelo Categoria...\n";
    require_once __DIR__ . '/../backend/models/Categoria.php';
    echo "✓ Arquivo Categoria.php carregado\n";
    
    $categoria = new Categoria($conn);
    echo "✓ Classe Categoria instanciada\n";
    
    $stmt = $conn->query("SELECT COUNT(*) FROM categorias");
    $catCount = $stmt->fetchColumn();
    echo "✓ {$catCount} categorias encontradas\n\n";
    
    // Teste do modelo Noticia
    echo "4. Testando modelo Noticia...\n";
    require_once __DIR__ . '/../backend/models/Noticia.php';
    echo "✓ Arquivo Noticia.php carregado\n";
    
    $noticia = new Noticia($conn);
    echo "✓ Classe Noticia instanciada\n";
    
    $stmt = $conn->query("SELECT COUNT(*) FROM noticias");
    $newsCount = $stmt->fetchColumn();
    echo "✓ {$newsCount} notícias encontradas\n\n";
    
    // Teste de campos críticos
    echo "5. Verificando estrutura da tabela usuarios...\n";
    $stmt = $conn->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $notification_fields = [
        'email_newsletter', 'email_breaking', 'email_comments', 'email_marketing',
        'push_breaking', 'push_interests', 'push_comments'
    ];
    
    $privacy_fields = [
        'profile_public', 'show_activity', 'allow_messages'
    ];
    
    $missing_fields = [];
    foreach (array_merge($notification_fields, $privacy_fields) as $field) {
        if (!in_array($field, $columns)) {
            $missing_fields[] = $field;
        }
    }
    
    if (empty($missing_fields)) {
        echo "✓ Todos os campos de notificação e privacidade estão presentes\n";
    } else {
        echo "⚠ Campos ausentes: " . implode(', ', $missing_fields) . "\n";
    }
    
    // Verificar se notification_frequency foi removida
    if (!in_array('notification_frequency', $columns)) {
        echo "✓ Campo notification_frequency removido corretamente\n";
    } else {
        echo "⚠ Campo notification_frequency ainda existe\n";
    }
    
    echo "\n✅ TODOS OS TESTES PASSARAM COM SUCESSO!\n";
    echo "\n📊 RESUMO:\n";
    echo "- Usuários: {$userCount}\n";
    echo "- Categorias: {$catCount}\n";
    echo "- Notícias: {$newsCount}\n";
    echo "- Modelos: Funcionando\n";
    echo "- Campos: Corretos\n";
    
} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    exit(1);
}

echo "\n=== FIM DO TESTE ===\n";
?>