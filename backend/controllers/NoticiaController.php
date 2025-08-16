<?php
/**
 * Controlador de Notícias
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Noticia.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../services/UploadService.php';

class NoticiaController {
    private $db;
    private $noticia;
    private $usuario;
    private $uploadService;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->noticia = new Noticia($this->db);
        $this->usuario = new Usuario($this->db);
        $this->uploadService = new UploadService();
        
        // Iniciar sessão se não estiver iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Processar requisições
     */
    public function processarRequisicao() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        $id = $_GET['id'] ?? null;
        $slug = $_GET['slug'] ?? null;

        switch($method) {
            case 'GET':
                switch($action) {
                    case 'list':
                        $this->listar();
                        break;
                    case 'get':
                        if($id) {
                            $this->obterPorId($id);
                        } elseif($slug) {
                            $this->obterPorSlug($slug);
                        } else {
                            jsonResponse(['erro' => 'ID ou slug é obrigatório'], 400);
                        }
                        break;
                    case 'search':
                        $this->buscar();
                        break;
                    case 'related':
                        $this->obterRelacionadas($id);
                        break;
                    case 'popular':
                        $this->obterPopulares();
                        break;
                    case 'recent':
                        $this->obterRecentes();
                        break;
                    case 'featured':
                        $this->obterDestaques();
                        break;
                    case 'by-category':
                        $this->obterPorCategoria();
                        break;
                    case 'by-tag':
                        $this->obterPorTag();
                        break;
                    case 'by-author':
                        $this->obterPorAutor();
                        break;
                    case 'stats':
                        $this->obterEstatisticas();
                        break;
                    default:
                        jsonResponse(['erro' => 'Ação não encontrada'], 404);
                }
                break;
            case 'POST':
                switch($action) {
                    case 'create':
                        $this->criar();
                        break;
                    case 'like':
                        $this->curtir($id);
                        break;
                    case 'view':
                        $this->registrarVisualizacao($id);
                        break;
                    default:
                        jsonResponse(['erro' => 'Ação não encontrada'], 404);
                }
                break;
            case 'PUT':
                switch($action) {
                    case 'update':
                        $this->atualizar($id);
                        break;
                    case 'publish':
                        $this->publicar($id);
                        break;
                    case 'unpublish':
                        $this->despublicar($id);
                        break;
                    case 'feature':
                        $this->destacar($id);
                        break;
                    default:
                        jsonResponse(['erro' => 'Ação não encontrada'], 404);
                }
                break;
            case 'DELETE':
                switch($action) {
                    case 'delete':
                        $this->excluir($id);
                        break;
                    default:
                        jsonResponse(['erro' => 'Ação não encontrada'], 404);
                }
                break;
            default:
                jsonResponse(['erro' => 'Método não permitido'], 405);
        }
    }

    /**
     * Listar notícias
     */
    private function listar() {
        try {
            $filtros = [
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => min((int)($_GET['limit'] ?? ITEMS_PER_PAGE), MAX_ITEMS_PER_PAGE),
                'categoria_id' => $_GET['categoria_id'] ?? null,
                'tag_id' => $_GET['tag_id'] ?? null,
                'autor_id' => $_GET['autor_id'] ?? null,
                'destaque' => $_GET['destaque'] ?? null,
                'busca' => $_GET['busca'] ?? null,
                'ordem' => $_GET['ordem'] ?? 'recentes'
            ];

            $noticias = $this->noticia->listar($filtros);
            $total = $this->noticia->contar($filtros);
            $total_paginas = ceil($total / $filtros['limit']);

            jsonResponse([
                'noticias' => $noticias,
                'paginacao' => [
                    'pagina_atual' => $filtros['page'],
                    'total_paginas' => $total_paginas,
                    'total_itens' => $total,
                    'itens_por_pagina' => $filtros['limit']
                ]
            ]);
        } catch(Exception $e) {
            logError('Erro ao listar notícias: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Obter notícia por ID
     */
    private function obterPorId($id) {
        try {
            if($this->noticia->buscarPorId($id)) {
                jsonResponse([
                    'noticia' => [
                        'id' => $this->noticia->id,
                        'titulo' => $this->noticia->titulo,
                        'slug' => $this->noticia->slug,
                        'subtitulo' => $this->noticia->subtitulo,
                        'conteudo' => $this->noticia->conteudo,
                        'resumo' => $this->noticia->resumo,
                        'imagem_destaque' => $this->noticia->imagem_destaque,
                        'alt_imagem' => $this->noticia->alt_imagem,
                        'autor_id' => $this->noticia->autor_id,
                        'autor_nome' => $this->noticia->autor_nome,
                        'categoria_id' => $this->noticia->categoria_id,
                        'categoria_nome' => $this->noticia->categoria_nome,
                        'categoria_slug' => $this->noticia->categoria_slug,
                        'status' => $this->noticia->status,
                        'destaque' => $this->noticia->destaque,
                        'permitir_comentarios' => $this->noticia->permitir_comentarios,
                        'visualizacoes' => $this->noticia->visualizacoes,
                        'curtidas' => $this->noticia->curtidas,
                        'data_publicacao' => $this->noticia->data_publicacao,
                        'data_criacao' => $this->noticia->data_criacao,
                        'meta_title' => $this->noticia->meta_title,
                        'meta_description' => $this->noticia->meta_description,
                        'meta_keywords' => $this->noticia->meta_keywords,
                        'tags' => $this->noticia->tags
                    ]
                ]);
            } else {
                jsonResponse(['erro' => 'Notícia não encontrada'], 404);
            }
        } catch(Exception $e) {
            logError('Erro ao obter notícia: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Obter notícia por slug
     */
    private function obterPorSlug($slug) {
        try {
            if($this->noticia->buscarPorSlug($slug)) {
                // Obter notícias relacionadas
                $relacionadas = $this->noticia->obterRelacionadas(4);
                
                jsonResponse([
                    'noticia' => [
                        'id' => $this->noticia->id,
                        'titulo' => $this->noticia->titulo,
                        'slug' => $this->noticia->slug,
                        'subtitulo' => $this->noticia->subtitulo,
                        'conteudo' => $this->noticia->conteudo,
                        'resumo' => $this->noticia->resumo,
                        'imagem_destaque' => $this->noticia->imagem_destaque,
                        'alt_imagem' => $this->noticia->alt_imagem,
                        'autor_nome' => $this->noticia->autor_nome,
                        'categoria_nome' => $this->noticia->categoria_nome,
                        'categoria_slug' => $this->noticia->categoria_slug,
                        'visualizacoes' => $this->noticia->visualizacoes,
                        'curtidas' => $this->noticia->curtidas,
                        'data_publicacao' => $this->noticia->data_publicacao,
                        'permitir_comentarios' => $this->noticia->permitir_comentarios,
                        'meta_title' => $this->noticia->meta_title,
                        'meta_description' => $this->noticia->meta_description,
                        'meta_keywords' => $this->noticia->meta_keywords,
                        'tags' => $this->noticia->tags
                    ],
                    'relacionadas' => $relacionadas
                ]);
            } else {
                jsonResponse(['erro' => 'Notícia não encontrada'], 404);
            }
        } catch(Exception $e) {
            logError('Erro ao obter notícia por slug: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Buscar notícias
     */
    private function buscar() {
        try {
            $termo = $_GET['q'] ?? '';
            
            if(empty($termo)) {
                jsonResponse(['erro' => 'Termo de busca é obrigatório'], 400);
            }

            $filtros = [
                'busca' => $termo,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => min((int)($_GET['limit'] ?? ITEMS_PER_PAGE), MAX_ITEMS_PER_PAGE)
            ];

            $noticias = $this->noticia->listar($filtros);
            $total = $this->noticia->contar($filtros);
            $total_paginas = ceil($total / $filtros['limit']);

            jsonResponse([
                'noticias' => $noticias,
                'termo_busca' => $termo,
                'paginacao' => [
                    'pagina_atual' => $filtros['page'],
                    'total_paginas' => $total_paginas,
                    'total_itens' => $total,
                    'itens_por_pagina' => $filtros['limit']
                ]
            ]);
        } catch(Exception $e) {
            logError('Erro na busca: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Obter notícias relacionadas
     */
    private function obterRelacionadas($id) {
        try {
            if(!$id) {
                jsonResponse(['erro' => 'ID da notícia é obrigatório'], 400);
            }

            if($this->noticia->buscarPorId($id)) {
                $relacionadas = $this->noticia->obterRelacionadas(6);
                jsonResponse(['noticias' => $relacionadas]);
            } else {
                jsonResponse(['erro' => 'Notícia não encontrada'], 404);
            }
        } catch(Exception $e) {
            logError('Erro ao obter relacionadas: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Obter notícias populares
     */
    private function obterPopulares() {
        try {
            $limite = min((int)($_GET['limit'] ?? 10), 20);
            
            $filtros = [
                'ordem' => 'mais_lidas',
                'limit' => $limite,
                'page' => 1
            ];

            $noticias = $this->noticia->listar($filtros);
            jsonResponse(['noticias' => $noticias]);
        } catch(Exception $e) {
            logError('Erro ao obter populares: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Obter notícias recentes
     */
    private function obterRecentes() {
        try {
            $limite = min((int)($_GET['limit'] ?? 10), 20);
            
            $filtros = [
                'ordem' => 'recentes',
                'limit' => $limite,
                'page' => 1
            ];

            $noticias = $this->noticia->listar($filtros);
            jsonResponse(['noticias' => $noticias]);
        } catch(Exception $e) {
            logError('Erro ao obter recentes: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Obter notícias em destaque
     */
    private function obterDestaques() {
        try {
            $limite = min((int)($_GET['limit'] ?? 5), 10);
            
            $filtros = [
                'destaque' => true,
                'limit' => $limite,
                'page' => 1
            ];

            $noticias = $this->noticia->listar($filtros);
            jsonResponse(['noticias' => $noticias]);
        } catch(Exception $e) {
            logError('Erro ao obter destaques: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Obter notícias por categoria
     */
    private function obterPorCategoria() {
        try {
            $categoria_id = $_GET['categoria_id'] ?? null;
            
            if(!$categoria_id) {
                jsonResponse(['erro' => 'ID da categoria é obrigatório'], 400);
            }

            $filtros = [
                'categoria_id' => $categoria_id,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => min((int)($_GET['limit'] ?? ITEMS_PER_PAGE), MAX_ITEMS_PER_PAGE)
            ];

            $noticias = $this->noticia->listar($filtros);
            $total = $this->noticia->contar($filtros);
            $total_paginas = ceil($total / $filtros['limit']);

            jsonResponse([
                'noticias' => $noticias,
                'paginacao' => [
                    'pagina_atual' => $filtros['page'],
                    'total_paginas' => $total_paginas,
                    'total_itens' => $total,
                    'itens_por_pagina' => $filtros['limit']
                ]
            ]);
        } catch(Exception $e) {
            logError('Erro ao obter por categoria: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Obter notícias por tag
     */
    private function obterPorTag() {
        try {
            $tag_id = $_GET['tag_id'] ?? null;
            
            if(!$tag_id) {
                jsonResponse(['erro' => 'ID da tag é obrigatório'], 400);
            }

            $filtros = [
                'tag_id' => $tag_id,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => min((int)($_GET['limit'] ?? ITEMS_PER_PAGE), MAX_ITEMS_PER_PAGE)
            ];

            $noticias = $this->noticia->listar($filtros);
            $total = $this->noticia->contar($filtros);
            $total_paginas = ceil($total / $filtros['limit']);

            jsonResponse([
                'noticias' => $noticias,
                'paginacao' => [
                    'pagina_atual' => $filtros['page'],
                    'total_paginas' => $total_paginas,
                    'total_itens' => $total,
                    'itens_por_pagina' => $filtros['limit']
                ]
            ]);
        } catch(Exception $e) {
            logError('Erro ao obter por tag: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Obter notícias por autor
     */
    private function obterPorAutor() {
        try {
            $autor_id = $_GET['autor_id'] ?? null;
            
            if(!$autor_id) {
                jsonResponse(['erro' => 'ID do autor é obrigatório'], 400);
            }

            $filtros = [
                'autor_id' => $autor_id,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => min((int)($_GET['limit'] ?? ITEMS_PER_PAGE), MAX_ITEMS_PER_PAGE)
            ];

            $noticias = $this->noticia->listar($filtros);
            $total = $this->noticia->contar($filtros);
            $total_paginas = ceil($total / $filtros['limit']);

            jsonResponse([
                'noticias' => $noticias,
                'paginacao' => [
                    'pagina_atual' => $filtros['page'],
                    'total_paginas' => $total_paginas,
                    'total_itens' => $total,
                    'itens_por_pagina' => $filtros['limit']
                ]
            ]);
        } catch(Exception $e) {
            logError('Erro ao obter por autor: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Criar nova notícia
     */
    private function criar() {
        try {
            if(!$this->verificarPermissao(['admin', 'editor'])) {
                jsonResponse(['erro' => 'Não autorizado'], 401);
            }

            $dados = json_decode(file_get_contents('php://input'), true);
            
            $this->noticia->titulo = $dados['titulo'] ?? '';
            $this->noticia->subtitulo = $dados['subtitulo'] ?? '';
            $this->noticia->conteudo = $dados['conteudo'] ?? '';
            $this->noticia->resumo = $dados['resumo'] ?? '';
            $this->noticia->categoria_id = $dados['categoria_id'] ?? null;
            $this->noticia->autor_id = $_SESSION['usuario_id'];
            $this->noticia->status = $dados['status'] ?? 'rascunho';
            $this->noticia->destaque = $dados['destaque'] ?? 0;
            $this->noticia->permitir_comentarios = $dados['permitir_comentarios'] ?? 1;
            $this->noticia->meta_title = $dados['meta_title'] ?? '';
            $this->noticia->meta_description = $dados['meta_description'] ?? '';
            $this->noticia->meta_keywords = $dados['meta_keywords'] ?? '';
            $this->noticia->tags = $dados['tags'] ?? [];
            
            // Upload de imagem se fornecida
            if(!empty($dados['imagem_destaque'])) {
                $this->noticia->imagem_destaque = $this->uploadService->processarUpload($dados['imagem_destaque'], 'images');
                $this->noticia->alt_imagem = $dados['alt_imagem'] ?? '';
            }

            // Validar dados
            $erros = $this->noticia->validar();
            if(!empty($erros)) {
                jsonResponse(['erro' => implode(', ', $erros)], 400);
            }

            if($this->noticia->criar()) {
                jsonResponse([
                    'sucesso' => true,
                    'mensagem' => 'Notícia criada com sucesso',
                    'noticia_id' => $this->noticia->id
                ], 201);
            } else {
                jsonResponse(['erro' => 'Erro ao criar notícia'], 500);
            }
        } catch(Exception $e) {
            logError('Erro ao criar notícia: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Atualizar notícia
     */
    private function atualizar($id) {
        try {
            if(!$this->verificarPermissao(['admin', 'editor'])) {
                jsonResponse(['erro' => 'Não autorizado'], 401);
            }

            if(!$id) {
                jsonResponse(['erro' => 'ID da notícia é obrigatório'], 400);
            }

            // Verificar se notícia existe
            if(!$this->noticia->buscarPorId($id)) {
                jsonResponse(['erro' => 'Notícia não encontrada'], 404);
            }

            // Verificar se usuário pode editar esta notícia
            if($_SESSION['usuario_tipo'] !== 'admin' && $this->noticia->autor_id != $_SESSION['usuario_id']) {
                jsonResponse(['erro' => 'Você não pode editar esta notícia'], 403);
            }

            $dados = json_decode(file_get_contents('php://input'), true);
            
            $this->noticia->id = $id;
            $this->noticia->titulo = $dados['titulo'] ?? $this->noticia->titulo;
            $this->noticia->subtitulo = $dados['subtitulo'] ?? $this->noticia->subtitulo;
            $this->noticia->conteudo = $dados['conteudo'] ?? $this->noticia->conteudo;
            $this->noticia->resumo = $dados['resumo'] ?? $this->noticia->resumo;
            $this->noticia->categoria_id = $dados['categoria_id'] ?? $this->noticia->categoria_id;
            $this->noticia->status = $dados['status'] ?? $this->noticia->status;
            $this->noticia->destaque = $dados['destaque'] ?? $this->noticia->destaque;
            $this->noticia->permitir_comentarios = $dados['permitir_comentarios'] ?? $this->noticia->permitir_comentarios;
            $this->noticia->meta_title = $dados['meta_title'] ?? $this->noticia->meta_title;
            $this->noticia->meta_description = $dados['meta_description'] ?? $this->noticia->meta_description;
            $this->noticia->meta_keywords = $dados['meta_keywords'] ?? $this->noticia->meta_keywords;
            
            if(isset($dados['tags'])) {
                $this->noticia->tags = $dados['tags'];
            }
            
            // Upload de nova imagem se fornecida
            if(!empty($dados['imagem_destaque'])) {
                $this->noticia->imagem_destaque = $this->uploadService->processarUpload($dados['imagem_destaque'], 'images');
                $this->noticia->alt_imagem = $dados['alt_imagem'] ?? $this->noticia->alt_imagem;
            }

            // Validar dados
            $erros = $this->noticia->validar();
            if(!empty($erros)) {
                jsonResponse(['erro' => implode(', ', $erros)], 400);
            }

            if($this->noticia->atualizar()) {
                jsonResponse([
                    'sucesso' => true,
                    'mensagem' => 'Notícia atualizada com sucesso'
                ]);
            } else {
                jsonResponse(['erro' => 'Erro ao atualizar notícia'], 500);
            }
        } catch(Exception $e) {
            logError('Erro ao atualizar notícia: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Excluir notícia
     */
    private function excluir($id) {
        try {
            if(!$this->verificarPermissao(['admin'])) {
                jsonResponse(['erro' => 'Não autorizado'], 401);
            }

            if(!$id) {
                jsonResponse(['erro' => 'ID da notícia é obrigatório'], 400);
            }

            if($this->noticia->excluir($id)) {
                jsonResponse([
                    'sucesso' => true,
                    'mensagem' => 'Notícia excluída com sucesso'
                ]);
            } else {
                jsonResponse(['erro' => 'Erro ao excluir notícia'], 500);
            }
        } catch(Exception $e) {
            logError('Erro ao excluir notícia: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Curtir notícia
     */
    private function curtir($id) {
        try {
            if(!$this->estaLogado()) {
                jsonResponse(['erro' => 'Login necessário'], 401);
            }

            if(!$id) {
                jsonResponse(['erro' => 'ID da notícia é obrigatório'], 400);
            }

            $dados = json_decode(file_get_contents('php://input'), true);
            $tipo = $dados['tipo'] ?? 'curtida';

            $this->noticia->id = $id;
            
            if($this->noticia->curtir($_SESSION['usuario_id'], $tipo)) {
                jsonResponse([
                    'sucesso' => true,
                    'mensagem' => 'Ação realizada com sucesso'
                ]);
            } else {
                jsonResponse(['erro' => 'Erro ao processar ação'], 500);
            }
        } catch(Exception $e) {
            logError('Erro ao curtir notícia: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Registrar visualização
     */
    private function registrarVisualizacao($id) {
        try {
            if(!$id) {
                jsonResponse(['erro' => 'ID da notícia é obrigatório'], 400);
            }

            // Registrar na tabela de estatísticas
            $query = "INSERT INTO estatisticas_acesso (noticia_id, ip_address, user_agent, referer) 
                      VALUES (:noticia_id, :ip, :user_agent, :referer)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':noticia_id', $id);
            $stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
            $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
            $stmt->bindParam(':referer', $_SERVER['HTTP_REFERER'] ?? '');
            $stmt->execute();

            jsonResponse(['sucesso' => true]);
        } catch(Exception $e) {
            logError('Erro ao registrar visualização: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Publicar notícia
     */
    private function publicar($id) {
        try {
            if(!$this->verificarPermissao(['admin', 'editor'])) {
                jsonResponse(['erro' => 'Não autorizado'], 401);
            }

            $query = "UPDATE noticias SET status='publicado', data_publicacao=NOW() WHERE id=:id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if($stmt->execute()) {
                jsonResponse(['sucesso' => true, 'mensagem' => 'Notícia publicada com sucesso']);
            } else {
                jsonResponse(['erro' => 'Erro ao publicar notícia'], 500);
            }
        } catch(Exception $e) {
            logError('Erro ao publicar notícia: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Despublicar notícia
     */
    private function despublicar($id) {
        try {
            if(!$this->verificarPermissao(['admin', 'editor'])) {
                jsonResponse(['erro' => 'Não autorizado'], 401);
            }

            $query = "UPDATE noticias SET status='rascunho' WHERE id=:id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if($stmt->execute()) {
                jsonResponse(['sucesso' => true, 'mensagem' => 'Notícia despublicada com sucesso']);
            } else {
                jsonResponse(['erro' => 'Erro ao despublicar notícia'], 500);
            }
        } catch(Exception $e) {
            logError('Erro ao despublicar notícia: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Destacar/Desdestacar notícia
     */
    private function destacar($id) {
        try {
            if(!$this->verificarPermissao(['admin'])) {
                jsonResponse(['erro' => 'Não autorizado'], 401);
            }

            $dados = json_decode(file_get_contents('php://input'), true);
            $destaque = $dados['destaque'] ?? 1;

            $query = "UPDATE noticias SET destaque=:destaque WHERE id=:id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':destaque', $destaque);
            $stmt->bindParam(':id', $id);
            
            if($stmt->execute()) {
                $mensagem = $destaque ? 'Notícia destacada com sucesso' : 'Destaque removido com sucesso';
                jsonResponse(['sucesso' => true, 'mensagem' => $mensagem]);
            } else {
                jsonResponse(['erro' => 'Erro ao alterar destaque'], 500);
            }
        } catch(Exception $e) {
            logError('Erro ao destacar notícia: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Obter estatísticas
     */
    private function obterEstatisticas() {
        try {
            if(!$this->verificarPermissao(['admin', 'editor'])) {
                jsonResponse(['erro' => 'Não autorizado'], 401);
            }

            $stats = $this->noticia->obterEstatisticas();
            jsonResponse(['estatisticas' => $stats]);
        } catch(Exception $e) {
            logError('Erro ao obter estatísticas: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Verificar se usuário está logado
     */
    private function estaLogado() {
        return isset($_SESSION['logado']) && $_SESSION['logado'] === true;
    }

    /**
     * Verificar permissão do usuário
     */
    private function verificarPermissao($tipos_permitidos) {
        if(!$this->estaLogado()) {
            return false;
        }
        
        return in_array($_SESSION['usuario_tipo'], $tipos_permitidos);
    }
}

// Processar requisição se chamado diretamente
if(basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $controller = new NoticiaController();
    $controller->processarRequisicao();
}
?>