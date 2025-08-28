<?php
require_once 'config-unified.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
                   DB_USER, 
                   DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Notícias para criar
    $noticias = [
        // Cultura (categoria_id = 6)
        [
            'titulo' => 'Festival de Cinema Nacional Movimenta São Paulo',
            'slug' => 'festival-cinema-nacional-sao-paulo',
            'conteudo' => '<p>O Festival de Cinema Nacional de São Paulo está movimentando a capital paulista com mais de 100 filmes nacionais em cartaz. O evento, que acontece até o final do mês, promete revelar novos talentos do cinema brasileiro.</p><p>Entre os destaques estão produções independentes de diretores estreantes e filmes de veteranos da sétima arte nacional. A programação inclui sessões especiais, debates e oficinas para o público.</p><p>Os ingressos estão disponíveis online e nas bilheterias dos cinemas participantes.</p>',
            'resumo' => 'Festival de Cinema Nacional apresenta mais de 100 filmes brasileiros em São Paulo, revelando novos talentos.',
            'imagem_destaque' => '/uploads/noticias/festival-cinema.jpg',
            'autor_id' => 1,
            'categoria_id' => 6,
            'status' => 'publicado',
            'destaque' => 0,
            'visualizacoes' => 245,
            'curtidas' => 18,
            'data_publicacao' => '2024-01-20 14:30:00'
        ],
        [
            'titulo' => 'Exposição de Arte Contemporânea Abre no MASP',
            'slug' => 'exposicao-arte-contemporanea-masp',
            'conteudo' => '<p>O Museu de Arte de São Paulo (MASP) inaugura nova exposição de arte contemporânea brasileira, reunindo obras de 30 artistas de diferentes regiões do país. A mostra "Vozes do Brasil" ficará em cartaz por três meses.</p><p>A curadoria destaca a diversidade da produção artística nacional, com pinturas, esculturas, instalações e arte digital. O projeto conta com apoio de importantes galerias e colecionadores.</p><p>A entrada é gratuita às terças-feiras para o público geral.</p>',
            'resumo' => 'MASP inaugura exposição "Vozes do Brasil" com obras de 30 artistas contemporâneos nacionais.',
            'imagem_destaque' => '/uploads/noticias/exposicao-masp.jpg',
            'autor_id' => 1,
            'categoria_id' => 6,
            'status' => 'publicado',
            'destaque' => 1,
            'visualizacoes' => 567,
            'curtidas' => 42,
            'data_publicacao' => '2024-01-18 10:15:00'
        ],
        // Internacional (categoria_id = 7)
        [
            'titulo' => 'Acordo Comercial Entre Brasil e União Europeia Avança',
            'slug' => 'acordo-comercial-brasil-uniao-europeia',
            'conteudo' => '<p>As negociações para o acordo comercial entre o Mercosul e a União Europeia registraram avanços significativos na última rodada de conversas em Bruxelas. O acordo pode facilitar o comércio bilateral e reduzir tarifas.</p><p>Representantes brasileiros destacaram o potencial de crescimento das exportações de produtos agrícolas e manufaturados. O setor automotivo também deve ser beneficiado com a redução de barreiras.</p><p>A expectativa é que o acordo seja finalizado ainda este ano, após mais de duas décadas de negociações.</p>',
            'resumo' => 'Negociações do acordo Mercosul-UE avançam com potencial de redução de tarifas e crescimento comercial.',
            'imagem_destaque' => '/uploads/noticias/acordo-ue-brasil.jpg',
            'autor_id' => 1,
            'categoria_id' => 7,
            'status' => 'publicado',
            'destaque' => 1,
            'visualizacoes' => 1234,
            'curtidas' => 89,
            'data_publicacao' => '2024-01-19 16:45:00'
        ],
        [
            'titulo' => 'Crise Energética na Europa Afeta Mercados Globais',
            'slug' => 'crise-energetica-europa-mercados-globais',
            'conteudo' => '<p>A crise energética que atinge diversos países europeus está gerando impactos nos mercados financeiros globais. Os preços do gás natural atingiram níveis recordes, pressionando a inflação na região.</p><p>Analistas econômicos alertam para possíveis efeitos em cadeia que podem afetar o comércio internacional e as cadeias de suprimento globais. Países emergentes podem ser especialmente vulneráveis.</p><p>Governos europeus estão implementando medidas de emergência para garantir o abastecimento energético durante o inverno.</p>',
            'resumo' => 'Crise energética europeia pressiona mercados globais com recordes nos preços do gás natural.',
            'imagem_destaque' => '/uploads/noticias/crise-energia-europa.jpg',
            'autor_id' => 1,
            'categoria_id' => 7,
            'status' => 'publicado',
            'destaque' => 0,
            'visualizacoes' => 892,
            'curtidas' => 67,
            'data_publicacao' => '2024-01-17 09:20:00'
        ],
        // Mais notícias para Política
        [
            'titulo' => 'Congresso Aprova Nova Lei de Transparência Pública',
            'slug' => 'congresso-aprova-lei-transparencia-publica',
            'conteudo' => '<p>O Congresso Nacional aprovou por unanimidade a nova Lei de Transparência Pública, que amplia o acesso dos cidadãos às informações governamentais. A legislação estabelece prazos mais rígidos para resposta a pedidos de informação.</p><p>A nova lei também cria mecanismos de controle social e prevê sanções para órgãos que não cumprirem os prazos estabelecidos. Organizações da sociedade civil celebraram a aprovação.</p><p>O texto segue agora para sanção presidencial, com expectativa de entrada em vigor nos próximos 90 dias.</p>',
            'resumo' => 'Nova Lei de Transparência Pública é aprovada por unanimidade, ampliando acesso a informações governamentais.',
            'imagem_destaque' => '/uploads/noticias/lei-transparencia.jpg',
            'autor_id' => 1,
            'categoria_id' => 1,
            'status' => 'publicado',
            'destaque' => 1,
            'visualizacoes' => 1567,
            'curtidas' => 123,
            'data_publicacao' => '2024-01-21 11:30:00'
        ],
        // Mais notícias para Economia
        [
            'titulo' => 'PIB Brasileiro Cresce 2,1% no Último Trimestre',
            'slug' => 'pib-brasileiro-cresce-trimestre',
            'conteudo' => '<p>O Produto Interno Bruto (PIB) brasileiro registrou crescimento de 2,1% no último trimestre, superando as expectativas dos analistas. O resultado foi impulsionado pelo setor de serviços e pela recuperação da indústria.</p><p>O consumo das famílias também apresentou alta significativa, refletindo a melhora do mercado de trabalho e o aumento da renda. Investimentos em infraestrutura contribuíram para o resultado positivo.</p><p>Economistas projetam manutenção do crescimento para os próximos meses, com perspectivas otimistas para o ano.</p>',
            'resumo' => 'PIB cresce 2,1% no trimestre, superando expectativas com alta em serviços e indústria.',
            'imagem_destaque' => '/uploads/noticias/pib-crescimento.jpg',
            'autor_id' => 1,
            'categoria_id' => 2,
            'status' => 'publicado',
            'destaque' => 1,
            'visualizacoes' => 2134,
            'curtidas' => 156,
            'data_publicacao' => '2024-01-22 08:45:00'
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO noticias (
            titulo, slug, conteudo, resumo, imagem_destaque, autor_id, categoria_id, 
            status, destaque, permitir_comentarios, visualizacoes, curtidas, data_publicacao
        ) VALUES (
            :titulo, :slug, :conteudo, :resumo, :imagem_destaque, :autor_id, :categoria_id,
            :status, :destaque, 1, :visualizacoes, :curtidas, :data_publicacao
        )
    ");
    
    $criadas = 0;
    foreach ($noticias as $noticia) {
        try {
            $stmt->execute($noticia);
            $criadas++;
            echo "✓ Notícia criada: {$noticia['titulo']}\n";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo "- Notícia já existe: {$noticia['titulo']}\n";
            } else {
                echo "✗ Erro ao criar notícia '{$noticia['titulo']}': {$e->getMessage()}\n";
            }
        }
    }
    
    echo "\n=== RESUMO ===\n";
    echo "Notícias criadas: $criadas\n";
    echo "Total de notícias processadas: " . count($noticias) . "\n";
    
} catch (PDOException $e) {
    echo "Erro de conexão: " . $e->getMessage() . "\n";
}
?>