<?php
require_once __DIR__ . '/../config-local.php';

echo "=== ANÁLISE DE NORMALIZAÇÃO DA TABELA NOTICIAS ===\n\n";

try {
    $db = $config['database'];
    $pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']};charset=utf8", $db['username'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Conexão estabelecida\n\n";
    
    // Analisar estrutura atual da tabela noticias
    echo "=== ESTRUTURA ATUAL DA TABELA NOTICIAS ===\n";
    $stmt = $pdo->query("DESCRIBE noticias");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        $key = $column['Key'] ? " [{$column['Key']}]" : '';
        echo "{$column['Field']}: {$column['Type']}{$key}\n";
    }
    
    // Verificar dados existentes
    echo "\n=== DADOS EXISTENTES ===\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM noticias");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total de notícias: $total\n\n";
    
    if ($total > 0) {
        // Verificar relacionamentos existentes
        echo "--- ANÁLISE DE RELACIONAMENTOS ---\n";
        
        // Verificar FKs existentes
        $stmt = $pdo->query("
            SELECT 
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = '{$db['dbname']}' 
            AND TABLE_NAME = 'noticias' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($foreignKeys) {
            echo "Chaves estrangeiras encontradas:\n";
            foreach ($foreignKeys as $fk) {
                echo "  ✓ {$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
            }
        } else {
            echo "✗ Nenhuma chave estrangeira encontrada\n";
        }
        
        // Verificar dados dos relacionamentos
        echo "\nVerificando integridade dos dados:\n";
        
        // Verificar autor_id
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM noticias WHERE autor_id IS NOT NULL");
        $noticiasComAutor = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "Notícias com autor_id: $noticiasComAutor\n";
        
        // Verificar categoria_id
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM noticias WHERE categoria_id IS NOT NULL");
        $noticiasComCategoria = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "Notícias com categoria_id: $noticiasComCategoria\n";
        
        // Verificar se os IDs referenciam registros válidos
        $stmt = $pdo->query("
            SELECT COUNT(*) as orfaos 
            FROM noticias n 
            LEFT JOIN usuarios u ON n.autor_id = u.id 
            WHERE n.autor_id IS NOT NULL AND u.id IS NULL
        ");
        $autoresOrfaos = $stmt->fetch(PDO::FETCH_ASSOC)['orfaos'];
        echo "Notícias com autor_id órfão: $autoresOrfaos\n";
        
        $stmt = $pdo->query("
            SELECT COUNT(*) as orfaos 
            FROM noticias n 
            LEFT JOIN categorias c ON n.categoria_id = c.id 
            WHERE n.categoria_id IS NOT NULL AND c.id IS NULL
        ");
        $categoriasOrfaos = $stmt->fetch(PDO::FETCH_ASSOC)['orfaos'];
        echo "Notícias com categoria_id órfão: $categoriasOrfaos\n";
        
        // Mostrar exemplo de dados atuais com JOINs
        echo "\n--- EXEMPLO DE DADOS ATUAIS ---\n";
        $stmt = $pdo->query("
            SELECT 
                n.id, 
                n.titulo, 
                u.nome as autor_nome, 
                c.nome as categoria_nome, 
                n.status 
            FROM noticias n
            LEFT JOIN usuarios u ON n.autor_id = u.id
            LEFT JOIN categorias c ON n.categoria_id = c.id
            LIMIT 3
        ");
        $exemplos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($exemplos as $noticia) {
            echo "ID: {$noticia['id']} | Título: {$noticia['titulo']} | Autor: {$noticia['autor_nome']} | Categoria: {$noticia['categoria_nome']} | Status: {$noticia['status']}\n";
        }
    }
    
    // Verificar estrutura das tabelas relacionadas
    echo "=== ESTRUTURA DAS TABELAS RELACIONADAS ===\n\n";
    
    echo "--- TABELA USUARIOS ---\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $totalUsuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total de usuários: $totalUsuarios\n";
    
    $stmt = $pdo->query("SELECT id, nome FROM usuarios LIMIT 5");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Exemplos:\n";
    foreach ($usuarios as $usuario) {
        echo "  ID: {$usuario['id']} - {$usuario['nome']}\n";
    }
    
    echo "\n--- TABELA CATEGORIAS ---\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM categorias");
    $totalCategorias = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total de categorias: $totalCategorias\n";
    
    $stmt = $pdo->query("SELECT id, nome FROM categorias LIMIT 5");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Exemplos:\n";
    foreach ($categorias as $categoria) {
        echo "  ID: {$categoria['id']} - {$categoria['nome']}\n";
    }
    
    // Propor estrutura normalizada
    echo "\n=== PROPOSTA DE NORMALIZAÇÃO ===\n\n";
    
    echo "MUDANÇAS NECESSÁRIAS NA TABELA NOTICIAS:\n";
    echo "1. Adicionar coluna 'autor_id' INT com FK para usuarios(id)\n";
    echo "2. Adicionar coluna 'categoria_id' INT com FK para categorias(id)\n";
    echo "3. Migrar dados de 'autor_nome' para 'autor_id'\n";
    echo "4. Migrar dados de 'categoria_nome' para 'categoria_id'\n";
    echo "5. Remover colunas 'autor_nome', 'categoria_nome', 'categoria_slug', 'categoria_cor'\n";
    echo "6. Criar índices para melhor performance\n\n";
    
    echo "BENEFÍCIOS:\n";
    echo "- Integridade referencial\n";
    echo "- Redução de redundância\n";
    echo "- Facilita atualizações em cascata\n";
    echo "- Melhora performance de consultas\n";
    echo "- Padronização de dados\n\n";
    
    echo "PRÓXIMOS PASSOS:\n";
    echo "1. Criar script de migração\n";
    echo "2. Fazer backup dos dados\n";
    echo "3. Executar migração\n";
    echo "4. Atualizar código PHP\n";
    echo "5. Testar funcionalidades\n";
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== ANÁLISE CONCLUÍDA ===\n";
?>