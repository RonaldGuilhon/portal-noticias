<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Noticia.php';
require_once __DIR__ . '/../models/Categoria.php';

class RSSController {
    private $noticiaModel;
    private $categoriaModel;
    
    public function __construct() {
        $this->noticiaModel = new Noticia();
        $this->categoriaModel = new Categoria();
    }
    
    /**
     * Gerar feed RSS
     */
    public function gerarFeed() {
        try {
            // Definir header XML
            header('Content-Type: application/rss+xml; charset=utf-8');
            
            // Buscar últimas notícias
            $noticias = $this->noticiaModel->listar([
                'limite' => 20,
                'status' => 'publicado',
                'ordem' => 'data_publicacao DESC'
            ]);
            
            // Gerar XML do RSS
            $xml = $this->gerarXMLRSS($noticias);
            
            echo $xml;
            
        } catch (Exception $e) {
            logError('Erro ao gerar RSS: ' . $e->getMessage());
            http_response_code(500);
            echo '<?xml version="1.0" encoding="UTF-8"?><error>Erro interno do servidor</error>';
        }
    }
    
    /**
     * Gerar XML do RSS
     */
    private function gerarXMLRSS($noticias) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= '<channel>' . "\n";
        
        // Informações do canal
        $xml .= '<title>Portal de Notícias</title>' . "\n";
        $xml .= '<link>' . BASE_URL . '</link>' . "\n";
        $xml .= '<description>As principais notícias do Brasil e do mundo</description>' . "\n";
        $xml .= '<language>pt-BR</language>' . "\n";
        $xml .= '<lastBuildDate>' . date('r') . '</lastBuildDate>' . "\n";
        $xml .= '<atom:link href="' . BASE_URL . '/rss" rel="self" type="application/rss+xml" />' . "\n";
        
        // Itens do feed
        foreach ($noticias as $noticia) {
            $xml .= '<item>' . "\n";
            $xml .= '<title><![CDATA[' . $noticia['titulo'] . ']]></title>' . "\n";
            $xml .= '<link>' . BASE_URL . '/noticia/' . $noticia['slug'] . '</link>' . "\n";
            $xml .= '<description><![CDATA[' . $this->limitarTexto($noticia['resumo'] ?? $noticia['conteudo'], 300) . ']]></description>' . "\n";
            $xml .= '<pubDate>' . date('r', strtotime($noticia['data_publicacao'])) . '</pubDate>' . "\n";
            $xml .= '<guid>' . BASE_URL . '/noticia/' . $noticia['slug'] . '</guid>' . "\n";
            
            if (!empty($noticia['categoria_nome'])) {
                $xml .= '<category><![CDATA[' . $noticia['categoria_nome'] . ']]></category>' . "\n";
            }
            
            if (!empty($noticia['imagem_destaque'])) {
                $xml .= '<enclosure url="' . BASE_URL . '/uploads/' . $noticia['imagem_destaque'] . '" type="image/jpeg" />' . "\n";
            }
            
            $xml .= '</item>' . "\n";
        }
        
        $xml .= '</channel>' . "\n";
        $xml .= '</rss>' . "\n";
        
        return $xml;
    }
    
    /**
     * Limitar texto
     */
    private function limitarTexto($texto, $limite) {
        if (strlen($texto) <= $limite) {
            return $texto;
        }
        
        return substr($texto, 0, $limite) . '...';
    }
    
    /**
     * Gerar feed por categoria
     */
    public function gerarFeedCategoria($categoriaId) {
        try {
            header('Content-Type: application/rss+xml; charset=utf-8');
            
            $categoria = $this->categoriaModel->buscarPorId($categoriaId);
            if (!$categoria) {
                http_response_code(404);
                echo '<?xml version="1.0" encoding="UTF-8"?><error>Categoria não encontrada</error>';
                return;
            }
            
            $noticias = $this->noticiaModel->listar([
                'categoria_id' => $categoriaId,
                'limite' => 20,
                'status' => 'publicado',
                'ordem' => 'data_publicacao DESC'
            ]);
            
            $xml = $this->gerarXMLRSSCategoria($noticias, $categoria);
            echo $xml;
            
        } catch (Exception $e) {
            logError('Erro ao gerar RSS da categoria: ' . $e->getMessage());
            http_response_code(500);
            echo '<?xml version="1.0" encoding="UTF-8"?><error>Erro interno do servidor</error>';
        }
    }
    
    /**
     * Gerar XML do RSS para categoria específica
     */
    private function gerarXMLRSSCategoria($noticias, $categoria) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= '<channel>' . "\n";
        
        $xml .= '<title>Portal de Notícias - ' . $categoria['nome'] . '</title>' . "\n";
        $xml .= '<link>' . BASE_URL . '/categoria/' . $categoria['slug'] . '</link>' . "\n";
        $xml .= '<description>Notícias da categoria ' . $categoria['nome'] . '</description>' . "\n";
        $xml .= '<language>pt-BR</language>' . "\n";
        $xml .= '<lastBuildDate>' . date('r') . '</lastBuildDate>' . "\n";
        $xml .= '<atom:link href="' . BASE_URL . '/rss/categoria/' . $categoria['id'] . '" rel="self" type="application/rss+xml" />' . "\n";
        
        foreach ($noticias as $noticia) {
            $xml .= '<item>' . "\n";
            $xml .= '<title><![CDATA[' . $noticia['titulo'] . ']]></title>' . "\n";
            $xml .= '<link>' . BASE_URL . '/noticia/' . $noticia['slug'] . '</link>' . "\n";
            $xml .= '<description><![CDATA[' . $this->limitarTexto($noticia['resumo'] ?? $noticia['conteudo'], 300) . ']]></description>' . "\n";
            $xml .= '<pubDate>' . date('r', strtotime($noticia['data_publicacao'])) . '</pubDate>' . "\n";
            $xml .= '<guid>' . BASE_URL . '/noticia/' . $noticia['slug'] . '</guid>' . "\n";
            $xml .= '<category><![CDATA[' . $categoria['nome'] . ']]></category>' . "\n";
            
            if (!empty($noticia['imagem_destaque'])) {
                $xml .= '<enclosure url="' . BASE_URL . '/uploads/' . $noticia['imagem_destaque'] . '" type="image/jpeg" />' . "\n";
            }
            
            $xml .= '</item>' . "\n";
        }
        
        $xml .= '</channel>' . "\n";
        $xml .= '</rss>' . "\n";
        
        return $xml;
    }
}