<?php
/**
 * Script para verificar notícias em destaque e critérios de filtragem
 */

require_once 'config-unified.php';
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "=== VERIFICAÇÃO DE NOTÍCIAS E CRITÉRIOS DE DESTAQUE ===\n\n";
    
    // 1. Verificar estrutura da tabela noticias
    echo "1. ESTRUTURA DA TABELA NOTICIAS:\n";
    $query = "DESCRIBE noticias";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']} (Default: {$column['Default']})\n";
    }
    
    echo "\n";
    
    // 2. Verificar notícias existentes
    echo "2. NOTÍCIAS EXISTENTES (primeiras 10):\n";
    $query = "SELECT id, titulo, destaque, visualizacoes, categoria_id, status FROM noticias ORDER BY id LIMIT 10";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if(empty($noticias)) {
        echo "❌ NENHUMA NOTÍCIA ENCONTRADA NO BANCO!\n";
    } else {
        foreach($noticias as $noticia) {
            $destaque_status = $noticia['destaque'] ? '✅ SIM' : '❌ NÃO';
            echo "ID: {$noticia['id']} | Título: {$noticia['titulo']} | Destaque: {$destaque_status} | Views: {$noticia['visualizacoes']} | Categoria: {$noticia['categoria_id']} | Status: {$noticia['status']}\n";
        }
    }
    
    echo "\n";
    
    // 3. Verificar notícias em destaque
    echo "3. NOTÍCIAS EM DESTAQUE:\n";
    $query = "SELECT id, titulo, destaque, visualizacoes, categoria_id FROM noticias WHERE destaque = 1 AND status = 'publicado'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $destaques = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if(empty($destaques)) {
        echo "❌ NENHUMA NOTÍCIA EM DESTAQUE ENCONTRADA!\n";
    } else {
        echo "✅ Encontradas " . count($destaques) . " notícias em destaque:\n";
        foreach($destaques as $destaque) {
            echo "- ID: {$destaque['id']} | {$destaque['titulo']} | Views: {$destaque['visualizacoes']} | Categoria: {$destaque['categoria_id']}\n";
        }
    }
    
    echo "\n";
    
    // 4. Verificar notícias mais lidas (populares)
    echo "4. NOTÍCIAS MAIS LIDAS (TOP 5):\n";
    $query = "SELECT id, titulo, visualizacoes, categoria_id FROM noticias WHERE status = 'publicado' ORDER BY visualizacoes DESC LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $populares = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if(empty($populares)) {
        echo "❌ NENHUMA NOTÍCIA PUBLICADA ENCONTRADA!\n";
    } else {
        foreach($populares as $popular) {
            echo "- ID: {$popular['id']} | {$popular['titulo']} | Views: {$popular['visualizacoes']} | Categoria: {$popular['categoria_id']}\n";
        }
    }
    
    echo "\n";
    
    // 5. Verificar tags associadas às notícias
    echo "5. VERIFICAÇÃO DE TAGS:\n";
    $query = "SELECT COUNT(*) as total_tags FROM tags";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $total_tags = $stmt->fetch(PDO::FETCH_ASSOC)['total_tags'];
    
    $query = "SELECT COUNT(*) as total_associacoes FROM noticia_tags";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $total_associacoes = $stmt->fetch(PDO::FETCH_ASSOC)['total_associacoes'];
    
    echo "- Total de tags: {$total_tags}\n";
    echo "- Total de associações notícia-tag: {$total_associacoes}\n";
    
    // 6. Verificar algumas associações de tags
    if($total_associacoes > 0) {
        echo "\n6. EXEMPLOS DE ASSOCIAÇÕES NOTÍCIA-TAG:\n";
        $query = "SELECT n.id, n.titulo, t.nome as tag_nome 
                  FROM noticias n 
                  JOIN noticia_tags nt ON n.id = nt.noticia_id 
                  JOIN tags t ON nt.tag_id = t.id 
                  LIMIT 10";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $associacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($associacoes as $assoc) {
            echo "- Notícia ID {$assoc['id']}: '{$assoc['titulo']}' -> Tag: '{$assoc['tag_nome']}'\n";
        }
    }
    
    echo "\n";
    
    // 7. Verificar status das notícias
    echo "7. DISTRIBUIÇÃO POR STATUS:\n";
    $query = "SELECT status, COUNT(*) as total FROM noticias GROUP BY status";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $status_dist = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($status_dist as $status) {
        echo "- {$status['status']}: {$status['total']} notícias\n";
    }
    
    echo "\n=== DIAGNÓSTICO CONCLUÍDO ===\n";
    
} catch(Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
?>