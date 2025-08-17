<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Noticia.php';
require_once __DIR__ . '/../models/Categoria.php';

class SitemapController {
    private $db;
    private $noticiaModel;
    private $categoriaModel;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->noticiaModel = new Noticia($this->db);
        $this->categoriaModel = new Categoria($this->db);
    }
    
    /**
     * Gerar sitemap XML
     */
    public function gerarSitemap() {
        try {
            // Definir header XML
            header('Content-Type: application/xml; charset=utf-8');
            
            // Buscar todas as notícias publicadas
            $noticias = $this->noticiaModel->listar([
                'status' => 'publicado',
                'limite' => 1000,
                'ordem' => 'data_publicacao DESC'
            ]);
            
            // Buscar todas as categorias ativas
            $categorias = $this->categoriaModel->listar([
                'ativo' => 1,
                'limite' => 100
            ]);
            
            // Gerar XML do sitemap
            $xml = $this->gerarXMLSitemap($noticias, $categorias);
            
            echo $xml;
            
        } catch (Exception $e) {
            logError('Erro ao gerar sitemap: ' . $e->getMessage());
            http_response_code(500);
            echo '<?xml version="1.0" encoding="UTF-8"?><error>Erro interno do servidor</error>';
        }
    }
    
    /**
     * Gerar XML do sitemap
     */
    private function gerarXMLSitemap($noticias, $categorias) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // URL da página inicial
        $xml .= '<url>' . "\n";
        $xml .= '<loc>' . BASE_URL . '</loc>' . "\n";
        $xml .= '<lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
        $xml .= '<changefreq>daily</changefreq>' . "\n";
        $xml .= '<priority>1.0</priority>' . "\n";
        $xml .= '</url>' . "\n";
        
        // URLs das categorias
        foreach ($categorias as $categoria) {
            $xml .= '<url>' . "\n";
            $xml .= '<loc>' . BASE_URL . '/categoria/' . $categoria['slug'] . '</loc>' . "\n";
            $xml .= '<lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
            $xml .= '<changefreq>weekly</changefreq>' . "\n";
            $xml .= '<priority>0.8</priority>' . "\n";
            $xml .= '</url>' . "\n";
        }
        
        // URLs das notícias
        foreach ($noticias as $noticia) {
            $xml .= '<url>' . "\n";
            $xml .= '<loc>' . BASE_URL . '/noticia/' . $noticia['slug'] . '</loc>' . "\n";
            $xml .= '<lastmod>' . date('Y-m-d', strtotime($noticia['data_atualizacao'] ?? $noticia['data_publicacao'])) . '</lastmod>' . "\n";
            $xml .= '<changefreq>monthly</changefreq>' . "\n";
            $xml .= '<priority>0.6</priority>' . "\n";
            $xml .= '</url>' . "\n";
        }
        
        // URLs estáticas
        $paginasEstaticas = [
            '/login' => ['changefreq' => 'yearly', 'priority' => '0.3'],
            '/cadastro' => ['changefreq' => 'yearly', 'priority' => '0.3'],
            '/contato' => ['changefreq' => 'monthly', 'priority' => '0.5'],
            '/sobre' => ['changefreq' => 'monthly', 'priority' => '0.5'],
            '/politica-privacidade' => ['changefreq' => 'yearly', 'priority' => '0.3'],
            '/termos-uso' => ['changefreq' => 'yearly', 'priority' => '0.3']
        ];
        
        foreach ($paginasEstaticas as $url => $config) {
            $xml .= '<url>' . "\n";
            $xml .= '<loc>' . BASE_URL . $url . '</loc>' . "\n";
            $xml .= '<lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
            $xml .= '<changefreq>' . $config['changefreq'] . '</changefreq>' . "\n";
            $xml .= '<priority>' . $config['priority'] . '</priority>' . "\n";
            $xml .= '</url>' . "\n";
        }
        
        $xml .= '</urlset>' . "\n";
        
        return $xml;
    }
    
    /**
     * Gerar sitemap de notícias (Google News)
     */
    public function gerarSitemapNoticias() {
        try {
            header('Content-Type: application/xml; charset=utf-8');
            
            // Buscar notícias dos últimos 2 dias (Google News)
            $dataLimite = date('Y-m-d H:i:s', strtotime('-2 days'));
            $noticias = $this->noticiaModel->listar([
                'status' => 'publicado',
                'data_inicio' => $dataLimite,
                'limite' => 1000,
                'ordem' => 'data_publicacao DESC'
            ]);
            
            $xml = $this->gerarXMLSitemapNoticias($noticias);
            echo $xml;
            
        } catch (Exception $e) {
            logError('Erro ao gerar sitemap de notícias: ' . $e->getMessage());
            http_response_code(500);
            echo '<?xml version="1.0" encoding="UTF-8"?><error>Erro interno do servidor</error>';
        }
    }
    
    /**
     * Gerar XML do sitemap de notícias
     */
    private function gerarXMLSitemapNoticias($noticias) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";
        
        foreach ($noticias as $noticia) {
            $xml .= '<url>' . "\n";
            $xml .= '<loc>' . BASE_URL . '/noticia/' . $noticia['slug'] . '</loc>' . "\n";
            $xml .= '<news:news>' . "\n";
            $xml .= '<news:publication>' . "\n";
            $xml .= '<news:name>' . SITE_NAME . '</news:name>' . "\n";
            $xml .= '<news:language>pt</news:language>' . "\n";
            $xml .= '</news:publication>' . "\n";
            $xml .= '<news:publication_date>' . date('c', strtotime($noticia['data_publicacao'])) . '</news:publication_date>' . "\n";
            $xml .= '<news:title><![CDATA[' . $noticia['titulo'] . ']]></news:title>' . "\n";
            
            if (!empty($noticia['categoria_nome'])) {
                $xml .= '<news:keywords><![CDATA[' . $noticia['categoria_nome'] . ']]></news:keywords>' . "\n";
            }
            
            $xml .= '</news:news>' . "\n";
            $xml .= '</url>' . "\n";
        }
        
        $xml .= '</urlset>' . "\n";
        
        return $xml;
    }
    
    /**
     * Gerar índice de sitemaps
     */
    public function gerarSitemapIndex() {
        try {
            header('Content-Type: application/xml; charset=utf-8');
            
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            
            // Sitemap principal
            $xml .= '<sitemap>' . "\n";
            $xml .= '<loc>' . BASE_URL . '/sitemap.xml</loc>' . "\n";
            $xml .= '<lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
            $xml .= '</sitemap>' . "\n";
            
            // Sitemap de notícias
            $xml .= '<sitemap>' . "\n";
            $xml .= '<loc>' . BASE_URL . '/sitemap-news.xml</loc>' . "\n";
            $xml .= '<lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
            $xml .= '</sitemap>' . "\n";
            
            $xml .= '</sitemapindex>' . "\n";
            
            echo $xml;
            
        } catch (Exception $e) {
            logError('Erro ao gerar índice de sitemap: ' . $e->getMessage());
            http_response_code(500);
            echo '<?xml version="1.0" encoding="UTF-8"?><error>Erro interno do servidor</error>';
        }
    }
}