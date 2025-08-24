<?php
require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/../backend/config/database.php';

echo "=== DEBUG DOS MODELOS ===\n\n";

try {
    // Teste de conexão
    echo "1. Testando conexão...\n";
    $database = new Database();
    $conn = $database->getConnection();
    echo "✓ Conexão OK\n\n";
    
    // Teste do modelo Usuario
    echo "2. Testando modelo Usuario...\n";
    if (file_exists(__DIR__ . '/../backend/models/Usuario.php')) {
        echo "✓ Arquivo Usuario.php existe\n";
        require_once __DIR__ . '/../backend/models/Usuario.php';
        echo "✓ Arquivo Usuario.php carregado\n";
        
        $usuario = new Usuario();
        echo "✓ Classe Usuario instanciada\n";
        
        // Verificar se existe usuário com ID 2
        $stmt = $conn->prepare("SELECT id, nome, email FROM usuarios WHERE id = 2");
        $stmt->execute();
        $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($testUser) {
            echo "✓ Usuário ID 2 encontrado: {$testUser['nome']} ({$testUser['email']})\n";
        } else {
            echo "⚠ Usuário ID 2 não encontrado, testando com ID 1...\n";
            $stmt = $conn->prepare("SELECT id, nome, email FROM usuarios WHERE id = 1");
            $stmt->execute();
            $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($testUser) {
                echo "✓ Usuário ID 1 encontrado: {$testUser['nome']} ({$testUser['email']})\n";
            } else {
                echo "⚠ Nenhum usuário encontrado com ID 1 ou 2\n";
            }
        }
    } else {
        echo "✗ Arquivo Usuario.php não encontrado\n";
    }
    
    echo "\n3. Testando modelo Categoria...\n";
    if (file_exists(__DIR__ . '/../backend/models/Categoria.php')) {
        echo "✓ Arquivo Categoria.php existe\n";
        require_once __DIR__ . '/../backend/models/Categoria.php';
        echo "✓ Arquivo Categoria.php carregado\n";
        
        $categoria = new Categoria();
        echo "✓ Classe Categoria instanciada\n";
        
        $stmt = $conn->query("SELECT COUNT(*) FROM categorias");
        $count = $stmt->fetchColumn();
        echo "✓ {$count} categorias encontradas\n";
    } else {
        echo "✗ Arquivo Categoria.php não encontrado\n";
    }
    
    echo "\n4. Testando modelo Noticia...\n";
    if (file_exists(__DIR__ . '/../backend/models/Noticia.php')) {
        echo "✓ Arquivo Noticia.php existe\n";
        require_once __DIR__ . '/../backend/models/Noticia.php';
        echo "✓ Arquivo Noticia.php carregado\n";
        
        $noticia = new Noticia();
        echo "✓ Classe Noticia instanciada\n";
        
        $stmt = $conn->query("SELECT COUNT(*) FROM noticias");
        $count = $stmt->fetchColumn();
        echo "✓ {$count} notícias encontradas\n";
    } else {
        echo "✗ Arquivo Noticia.php não encontrado\n";
    }
    
    echo "\n✅ TESTE DE MODELOS CONCLUÍDO COM SUCESSO!\n";
    
} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    
    if ($e->getPrevious()) {
        echo "Erro anterior: " . $e->getPrevious()->getMessage() . "\n";
    }
    
    exit(1);
}

echo "\n=== FIM DO DEBUG ===\n";
?>