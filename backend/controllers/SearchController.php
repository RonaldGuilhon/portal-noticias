<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Noticia.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Tag.php';

class SearchController {
    private $noticiaModel;
    private $categoriaModel;
    private $tagModel;
    
    public function __construct() {
        $this->noticiaModel = new Noticia();
        $this->categoriaModel = new Categoria();
        $this->tagModel = new Tag();
    }
    
    /**
     * Buscar notícias
     */
    public function buscar() {
        try {
            $termo = $_GET['q'] ?? '';
            $categoria = $_GET['categoria'] ?? null;
            $tag = $_GET['tag'] ?? null;
            $dataInicio = $_GET['data_inicio'] ?? null;
            $dataFim = $_GET['data_fim'] ?? null;
            $pagina = (int)($_GET['pagina'] ?? 1);
            $limite = (int)($_GET['limite'] ?? 10);
            $ordem = $_GET['ordem'] ?? 'relevancia';
            
            // Validar parâmetros
            if (empty($termo) && empty($categoria) && empty($tag)) {
                http_response_code(400);
                echo json_encode([
                    'erro' => true,
                    'mensagem' => 'É necessário informar pelo menos um termo de busca, categoria ou tag'
                ]);
                return;
            }
            
            // Validar limite
            if ($limite > 50) {
                $limite = 50;
            }
            
            // Realizar busca
            $resultados = $this->noticiaModel->buscar([
                'termo' => $termo,
                'categoria_id' => $categoria,
                'tag_id' => $tag,
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'pagina' => $pagina,
                'limite' => $limite,
                'ordem' => $ordem,
                'status' => 'publicado'
            ]);
            
            // Contar total de resultados
            $total = $this->noticiaModel->contarBusca([
                'termo' => $termo,
                'categoria_id' => $categoria,
                'tag_id' => $tag,
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'status' => 'publicado'
            ]);
            
            // Calcular paginação
            $totalPaginas = ceil($total / $limite);
            
            // Preparar resposta
            $resposta = [
                'sucesso' => true,
                'dados' => [
                    'resultados' => $resultados,
                    'total' => $total,
                    'pagina_atual' => $pagina,
                    'total_paginas' => $totalPaginas,
                    'limite' => $limite,
                    'termo_busca' => $termo,
                    'filtros' => [
                        'categoria' => $categoria,
                        'tag' => $tag,
                        'data_inicio' => $dataInicio,
                        'data_fim' => $dataFim,
                        'ordem' => $ordem
                    ]
                ]
            ];
            
            // Log da busca
            logInfo('Busca realizada', [
                'termo' => $termo,
                'categoria' => $categoria,
                'tag' => $tag,
                'resultados' => count($resultados),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            header('Content-Type: application/json');
            echo json_encode($resposta);
            
        } catch (Exception $e) {
            logError('Erro na busca: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'erro' => true,
                'mensagem' => 'Erro interno do servidor'
            ]);
        }
    }
    
    /**
     * Buscar sugestões (autocomplete)
     */
    public function sugestoes() {
        try {
            $termo = $_GET['q'] ?? '';
            $limite = (int)($_GET['limite'] ?? 5);
            
            if (strlen($termo) < 2) {
                echo json_encode([
                    'sucesso' => true,
                    'dados' => []
                ]);
                return;
            }
            
            // Buscar sugestões de títulos
            $sugestoesTitulos = $this->noticiaModel->buscarSugestoesTitulos($termo, $limite);
            
            // Buscar sugestões de categorias
            $sugestoesCategorias = $this->categoriaModel->buscarSugestoes($termo, 3);
            
            // Buscar sugestões de tags
            $sugestoesTags = $this->tagModel->buscarSugestoes($termo, 3);
            
            $resposta = [
                'sucesso' => true,
                'dados' => [
                    'noticias' => $sugestoesTitulos,
                    'categorias' => $sugestoesCategorias,
                    'tags' => $sugestoesTags
                ]
            ];
            
            header('Content-Type: application/json');
            echo json_encode($resposta);
            
        } catch (Exception $e) {
            logError('Erro ao buscar sugestões: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'erro' => true,
                'mensagem' => 'Erro interno do servidor'
            ]);
        }
    }
    
    /**
     * Buscar termos populares
     */
    public function termosPopulares() {
        try {
            $limite = (int)($_GET['limite'] ?? 10);
            $periodo = $_GET['periodo'] ?? '30'; // dias
            
            // Buscar termos mais buscados (simulado - seria necessário implementar log de buscas)
            $termosPopulares = [
                'política',
                'economia',
                'esportes',
                'tecnologia',
                'saúde',
                'educação',
                'meio ambiente',
                'cultura',
                'internacional',
                'brasil'
            ];
            
            // Limitar resultados
            $termosPopulares = array_slice($termosPopulares, 0, $limite);
            
            $resposta = [
                'sucesso' => true,
                'dados' => [
                    'termos' => $termosPopulares,
                    'periodo' => $periodo . ' dias'
                ]
            ];
            
            header('Content-Type: application/json');
            echo json_encode($resposta);
            
        } catch (Exception $e) {
            logError('Erro ao buscar termos populares: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'erro' => true,
                'mensagem' => 'Erro interno do servidor'
            ]);
        }
    }
    
    /**
     * Busca avançada
     */
    public function buscaAvancada() {
        try {
            $filtros = [
                'titulo' => $_GET['titulo'] ?? '',
                'conteudo' => $_GET['conteudo'] ?? '',
                'autor' => $_GET['autor'] ?? '',
                'categoria_id' => $_GET['categoria'] ?? null,
                'tags' => $_GET['tags'] ?? null,
                'data_inicio' => $_GET['data_inicio'] ?? null,
                'data_fim' => $_GET['data_fim'] ?? null,
                'status' => 'publicado'
            ];
            
            $pagina = (int)($_GET['pagina'] ?? 1);
            $limite = (int)($_GET['limite'] ?? 10);
            $ordem = $_GET['ordem'] ?? 'data_publicacao DESC';
            
            // Validar se pelo menos um filtro foi informado
            $filtrosPreenchidos = array_filter($filtros, function($valor) {
                return !empty($valor);
            });
            
            if (empty($filtrosPreenchidos)) {
                http_response_code(400);
                echo json_encode([
                    'erro' => true,
                    'mensagem' => 'É necessário informar pelo menos um filtro de busca'
                ]);
                return;
            }
            
            // Realizar busca avançada
            $resultados = $this->noticiaModel->buscaAvancada($filtros, $pagina, $limite, $ordem);
            $total = $this->noticiaModel->contarBuscaAvancada($filtros);
            
            $totalPaginas = ceil($total / $limite);
            
            $resposta = [
                'sucesso' => true,
                'dados' => [
                    'resultados' => $resultados,
                    'total' => $total,
                    'pagina_atual' => $pagina,
                    'total_paginas' => $totalPaginas,
                    'limite' => $limite,
                    'filtros' => $filtros
                ]
            ];
            
            header('Content-Type: application/json');
            echo json_encode($resposta);
            
        } catch (Exception $e) {
            logError('Erro na busca avançada: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'erro' => true,
                'mensagem' => 'Erro interno do servidor'
            ]);
        }
    }
}