<?php
require_once 'config-unified.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
                   DB_USER, 
                   DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Associações de tags para as novas notícias
    $associacoes = [
        // Festival de Cinema (Cultura)
        ['noticia_slug' => 'festival-cinema-nacional-sao-paulo', 'tag_ids' => [9, 8]], // Cultura, São Paulo
        
        // Exposição MASP (Cultura)
        ['noticia_slug' => 'exposicao-arte-contemporanea-masp', 'tag_ids' => [9, 8]], // Cultura, São Paulo
        
        // Acordo Brasil-UE (Internacional)
        ['noticia_slug' => 'acordo-comercial-brasil-uniao-europeia', 'tag_ids' => [4, 5]], // Mercado, Investimento
        
        // Crise Energética Europa (Internacional)
        ['noticia_slug' => 'crise-energetica-europa-mercados-globais', 'tag_ids' => [4, 5]], // Mercado, Investimento
        
        // Lei Transparência (Política)
        ['noticia_slug' => 'congresso-aprova-lei-transparencia-publica', 'tag_ids' => [3, 7]], // Governo, Política Nacional
        
        // PIB Crescimento (Economia)
        ['noticia_slug' => 'pib-brasileiro-cresce-trimestre', 'tag_ids' => [4, 5]] // Mercado, Investimento
    ];
    
    // Buscar IDs das notícias pelos slugs
    $stmt_noticia = $pdo->prepare("SELECT id FROM noticias WHERE slug = ?");
    $stmt_insert = $pdo->prepare("INSERT IGNORE INTO noticia_tags (noticia_id, tag_id) VALUES (?, ?)");
    
    $total_associacoes = 0;
    
    foreach ($associacoes as $assoc) {
        $stmt_noticia->execute([$assoc['noticia_slug']]);
        $noticia = $stmt_noticia->fetch(PDO::FETCH_ASSOC);
        
        if ($noticia) {
            $noticia_id = $noticia['id'];
            
            foreach ($assoc['tag_ids'] as $tag_id) {
                try {
                    $stmt_insert->execute([$noticia_id, $tag_id]);
                    if ($stmt_insert->rowCount() > 0) {
                        $total_associacoes++;
                        echo "✓ Associação criada: Notícia ID {$noticia_id} <-> Tag ID {$tag_id}\n";
                    } else {
                        echo "- Associação já existe: Notícia ID {$noticia_id} <-> Tag ID {$tag_id}\n";
                    }
                } catch (PDOException $e) {
                    echo "✗ Erro ao criar associação: Notícia ID {$noticia_id} <-> Tag ID {$tag_id}: {$e->getMessage()}\n";
                }
            }
        } else {
            echo "✗ Notícia não encontrada: {$assoc['noticia_slug']}\n";
        }
    }
    
    echo "\n=== RESUMO ===\n";
    echo "Total de associações criadas: $total_associacoes\n";
    
    // Verificar estado final da tabela noticia_tags
    $stmt_count = $pdo->query("SELECT COUNT(*) as total FROM noticia_tags");
    $count = $stmt_count->fetch(PDO::FETCH_ASSOC);
    echo "Total de associações na tabela: {$count['total']}\n";
    
} catch (PDOException $e) {
    echo "Erro de conexão: " . $e->getMessage() . "\n";
}
?>