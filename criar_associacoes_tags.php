<?php

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== CRIANDO ASSOCIAÇÕES ENTRE NOTÍCIAS E TAGS ===\n\n";
    
    // Buscar todas as notícias
    $stmt = $pdo->query("SELECT id, titulo, conteudo, categoria_id FROM noticias");
    $noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar todas as tags
    $stmt = $pdo->query("SELECT id, nome, slug FROM tags");
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Notícias encontradas: " . count($noticias) . "\n";
    echo "Tags disponíveis: " . count($tags) . "\n\n";
    
    // Mapeamento de palavras-chave para tags
    $mapeamentoTags = [
        'urgente' => ['urgente', 'importante', 'breaking', 'última hora'],
        'destaque' => ['destaque', 'principal', 'manchete'],
        'governo' => ['governo', 'congresso', 'lei', 'política', 'presidente', 'ministro', 'deputado', 'senador'],
        'mercado' => ['mercado', 'economia', 'dólar', 'inflação', 'fed', 'banco central', 'investimento'],
        'investimento' => ['investimento', 'ações', 'bolsa', 'fundos', 'renda fixa'],
        'esporte' => ['futebol', 'brasil', 'argentina', 'copa', 'jogo', 'vitória', 'derrota'],
        'tecnologia' => ['tecnologia', 'ia', 'inteligência artificial', 'google', 'gpt', 'inovação'],
        'saúde' => ['saúde', 'medicina', 'tratamento', 'câncer', 'hospital', 'médico'],
        'educação' => ['educação', 'escola', 'universidade', 'ensino', 'professor'],
        'meio-ambiente' => ['meio ambiente', 'sustentabilidade', 'clima', 'poluição'],
        'internacional' => ['internacional', 'mundo', 'global', 'países'],
        'cultura' => ['cultura', 'arte', 'música', 'cinema', 'teatro'],
        'segurança' => ['segurança', 'polícia', 'crime', 'violência'],
        'transporte' => ['transporte', 'mobilidade', 'trânsito', 'metrô', 'ônibus'],
        'habitação' => ['habitação', 'moradia', 'imóveis', 'casa própria']
    ];
    
    // Criar um índice de tags por slug para facilitar a busca
    $tagsIndex = [];
    foreach ($tags as $tag) {
        $tagsIndex[$tag['slug']] = $tag['id'];
    }
    
    $associacoesCriadas = 0;
    
    foreach ($noticias as $noticia) {
        $textoCompleto = strtolower($noticia['titulo'] . ' ' . $noticia['conteudo']);
        $tagsAssociadas = [];
        
        echo "Processando notícia ID {$noticia['id']}: {$noticia['titulo']}\n";
        
        // Verificar cada mapeamento de tag
        foreach ($mapeamentoTags as $tagSlug => $palavrasChave) {
            if (isset($tagsIndex[$tagSlug])) {
                foreach ($palavrasChave as $palavra) {
                    if (strpos($textoCompleto, strtolower($palavra)) !== false) {
                        $tagsAssociadas[] = $tagsIndex[$tagSlug];
                        echo "  - Tag encontrada: {$tagSlug} (palavra: {$palavra})\n";
                        break; // Não precisa verificar outras palavras desta tag
                    }
                }
            }
        }
        
        // Associações baseadas na categoria
        switch ($noticia['categoria_id']) {
            case 1: // Política
                if (isset($tagsIndex['governo'])) {
                    $tagsAssociadas[] = $tagsIndex['governo'];
                }
                break;
            case 2: // Economia
                if (isset($tagsIndex['mercado'])) {
                    $tagsAssociadas[] = $tagsIndex['mercado'];
                }
                break;
            case 3: // Esporte
                if (isset($tagsIndex['esporte'])) {
                    $tagsAssociadas[] = $tagsIndex['esporte'];
                }
                break;
            case 4: // Tecnologia
                if (isset($tagsIndex['tecnologia'])) {
                    $tagsAssociadas[] = $tagsIndex['tecnologia'];
                }
                break;
            case 5: // Saúde
                if (isset($tagsIndex['saude'])) {
                    $tagsAssociadas[] = $tagsIndex['saude'];
                }
                break;
        }
        
        // Remover duplicatas
        $tagsAssociadas = array_unique($tagsAssociadas);
        
        // Inserir as associações no banco
        foreach ($tagsAssociadas as $tagId) {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO noticia_tags (noticia_id, tag_id) VALUES (?, ?)");
                $stmt->execute([$noticia['id'], $tagId]);
                $associacoesCriadas++;
                echo "  - Associação criada: noticia_id={$noticia['id']}, tag_id={$tagId}\n";
            } catch (Exception $e) {
                echo "  - Erro ao criar associação: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n";
    }
    
    echo "=== RESUMO ===\n";
    echo "Total de associações criadas: {$associacoesCriadas}\n\n";
    
    // Verificar o resultado
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM noticia_tags");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total de associações no banco: {$total}\n";
    
    // Mostrar distribuição por tag
    echo "\n=== DISTRIBUIÇÃO POR TAG ===\n";
    $stmt = $pdo->query("
        SELECT t.nome, t.slug, COUNT(nt.noticia_id) as total_noticias
        FROM tags t
        LEFT JOIN noticia_tags nt ON t.id = nt.tag_id
        GROUP BY t.id, t.nome, t.slug
        ORDER BY total_noticias DESC
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['nome']} ({$row['slug']}): {$row['total_noticias']} notícias\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

?>