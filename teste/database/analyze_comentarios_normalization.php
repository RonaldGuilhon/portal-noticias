<?php
require_once __DIR__ . '/../config-local.php';

echo "=== ANÁLISE DE NORMALIZAÇÃO DA TABELA COMENTARIOS ===\n\n";

try {
    // Conectar ao banco
    $db = $config['database'];
    $pdo = new PDO(
        "mysql:host={$db['host']};dbname={$db['dbname']};charset=utf8mb4",
        $db['username'],
        $db['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Conexão estabelecida\n\n";
    
    // Analisar estrutura da tabela comentarios
    echo "=== ESTRUTURA ATUAL DA TABELA COMENTARIOS ===\n";
    $stmt = $pdo->query("DESCRIBE comentarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        $key = $column['Key'] ? " [{$column['Key']}]" : "";
        echo "{$column['Field']}: {$column['Type']}{$key}\n";
    }
    
    // Verificar dados existentes
    echo "\n=== DADOS EXISTENTES ===\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM comentarios");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total de comentários: $total\n\n";
    
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
            AND TABLE_NAME = 'comentarios' 
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
        
        // Verificar noticia_id
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM comentarios WHERE noticia_id IS NOT NULL");
        $comentariosComNoticia = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "Comentários com noticia_id: $comentariosComNoticia\n";
        
        // Verificar usuario_id
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM comentarios WHERE usuario_id IS NOT NULL");
        $comentariosComUsuario = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "Comentários com usuario_id: $comentariosComUsuario\n";
        
        // Verificar se os IDs referenciam registros válidos
        $stmt = $pdo->query("
            SELECT COUNT(*) as orfaos 
            FROM comentarios c 
            LEFT JOIN noticias n ON c.noticia_id = n.id 
            WHERE c.noticia_id IS NOT NULL AND n.id IS NULL
        ");
        $noticiasOrfaos = $stmt->fetch(PDO::FETCH_ASSOC)['orfaos'];
        echo "Comentários com noticia_id órfão: $noticiasOrfaos\n";
        
        $stmt = $pdo->query("
            SELECT COUNT(*) as orfaos 
            FROM comentarios c 
            LEFT JOIN usuarios u ON c.usuario_id = u.id 
            WHERE c.usuario_id IS NOT NULL AND u.id IS NULL
        ");
        $usuariosOrfaos = $stmt->fetch(PDO::FETCH_ASSOC)['orfaos'];
        echo "Comentários com usuario_id órfão: $usuariosOrfaos\n";
        
        // Mostrar exemplo de dados atuais com JOINs
        echo "\n--- EXEMPLO DE DADOS ATUAIS ---\n";
        $stmt = $pdo->query("
            SELECT 
                c.id, 
                c.conteudo, 
                u.nome as usuario_nome, 
                n.titulo as noticia_titulo, 
                c.status,
                c.data_criacao
            FROM comentarios c
            LEFT JOIN usuarios u ON c.usuario_id = u.id
            LEFT JOIN noticias n ON c.noticia_id = n.id
            ORDER BY c.data_criacao DESC
            LIMIT 5
        ");
        $exemplos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($exemplos as $comentario) {
            echo "ID: {$comentario['id']} | Usuário: {$comentario['usuario_nome']} | Notícia: {$comentario['noticia_titulo']}\n";
            echo "  Conteúdo: " . substr($comentario['conteudo'], 0, 50) . "...\n";
            echo "  Status: {$comentario['status']} | Data: {$comentario['data_criacao']}\n\n";
        }
    }
    
    // Verificar estrutura das tabelas relacionadas
    echo "=== ESTRUTURA DAS TABELAS RELACIONADAS ===\n\n";
    
    // Verificar tabela noticias
    echo "--- TABELA NOTICIAS ---\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM noticias");
    $totalNoticias = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total de notícias: $totalNoticias\n";
    
    if ($totalNoticias > 0) {
        $stmt = $pdo->query("SELECT id, titulo FROM noticias ORDER BY data_criacao DESC LIMIT 5");
        $noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Exemplos:\n";
        foreach ($noticias as $noticia) {
            echo "  ID: {$noticia['id']} - {$noticia['titulo']}\n";
        }
    }
    
    // Verificar tabela usuarios
    echo "\n--- TABELA USUARIOS ---\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $totalUsuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total de usuários: $totalUsuarios\n";
    
    if ($totalUsuarios > 0) {
        $stmt = $pdo->query("SELECT id, nome FROM usuarios ORDER BY data_criacao DESC LIMIT 5");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Exemplos:\n";
        foreach ($usuarios as $usuario) {
            echo "  ID: {$usuario['id']} - {$usuario['nome']}\n";
        }
    }
    
    // Análise e recomendações
    echo "\n=== ANÁLISE E RECOMENDAÇÕES ===\n\n";
    
    if (empty($foreignKeys)) {
        echo "MUDANÇAS NECESSÁRIAS NA TABELA COMENTARIOS:\n";
        echo "1. Adicionar FK para noticia_id -> noticias(id)\n";
        echo "2. Adicionar FK para usuario_id -> usuarios(id)\n";
        echo "3. Criar índices para melhor performance\n\n";
        
        echo "BENEFÍCIOS:\n";
        echo "- Integridade referencial\n";
        echo "- Prevenção de comentários órfãos\n";
        echo "- Cascata de exclusões\n";
        echo "- Melhora performance de JOINs\n\n";
        
        echo "PRÓXIMOS PASSOS:\n";
        echo "1. Verificar integridade dos dados existentes\n";
        echo "2. Criar script de migração para adicionar FKs\n";
        echo "3. Testar funcionalidades\n";
    } else {
        echo "✓ TABELA JÁ ESTÁ NORMALIZADA!\n";
        echo "A tabela comentarios já possui as chaves estrangeiras necessárias.\n";
    }
    
    echo "\n=== ANÁLISE CONCLUÍDA ===\n";
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
}
?>