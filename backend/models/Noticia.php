<?php
/**
 * Modelo de Notícia
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../config-unified.php';

class Noticia {
    private $conn;
    private $table_name = "noticias";

    public $id;
    public $titulo;
    public $slug;
    public $subtitulo;
    public $conteudo;
    public $resumo;
    public $imagem_destaque;
    public $alt_imagem;
    public $autor_id;
    public $categoria_id;
    public $status;
    public $destaque;
    public $permitir_comentarios;
    public $visualizacoes;
    public $curtidas;
    public $data_publicacao;
    public $data_criacao;
    public $data_atualizacao;
    public $meta_title;
    public $meta_description;
    public $meta_keywords;
    public $tags;
    
    // Propriedades relacionadas (preenchidas via JOIN)
    public $autor_nome;
    public $categoria_nome;
    public $categoria_slug;
    public $categoria_cor;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Criar nova notícia
     */
    public function criar() {
        $query = "INSERT INTO {$this->table_name} 
                  SET titulo=:titulo, slug=:slug, subtitulo=:subtitulo, conteudo=:conteudo, 
                      resumo=:resumo, imagem_destaque=:imagem_destaque, alt_imagem=:alt_imagem,
                      autor_id=:autor_id, categoria_id=:categoria_id, status=:status, 
                      destaque=:destaque, permitir_comentarios=:permitir_comentarios,
                      data_publicacao=:data_publicacao, meta_title=:meta_title, 
                      meta_description=:meta_description, meta_keywords=:meta_keywords";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->titulo = sanitizeInput($this->titulo);
        $this->slug = $this->gerarSlugUnico($this->titulo);
        $this->subtitulo = sanitizeInput($this->subtitulo);
        // Não sanitizar HTML do conteúdo
        $this->resumo = sanitizeInput($this->resumo);
        $this->alt_imagem = sanitizeInput($this->alt_imagem);
        $this->status = $this->status ?: 'rascunho';
        $this->destaque = $this->destaque ?: 0;
        $this->permitir_comentarios = $this->permitir_comentarios ?? 1;
        $this->meta_title = sanitizeInput($this->meta_title ?: $this->titulo);
        $this->meta_description = sanitizeInput($this->meta_description ?: $this->resumo);
        $this->meta_keywords = sanitizeInput($this->meta_keywords);
        
        // Se status é publicado, definir data de publicação
        if($this->status === 'publicado' && empty($this->data_publicacao)) {
            $this->data_publicacao = date('Y-m-d H:i:s');
        }

        // Bind dos parâmetros
        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":slug", $this->slug);
        $stmt->bindParam(":subtitulo", $this->subtitulo);
        $stmt->bindParam(":conteudo", $this->conteudo);
        $stmt->bindParam(":resumo", $this->resumo);
        $stmt->bindParam(":imagem_destaque", $this->imagem_destaque);
        $stmt->bindParam(":alt_imagem", $this->alt_imagem);
        $stmt->bindParam(":autor_id", $this->autor_id);
        $stmt->bindParam(":categoria_id", $this->categoria_id);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":destaque", $this->destaque);
        $stmt->bindParam(":permitir_comentarios", $this->permitir_comentarios);
        $stmt->bindParam(":data_publicacao", $this->data_publicacao);
        $stmt->bindParam(":meta_title", $this->meta_title);
        $stmt->bindParam(":meta_description", $this->meta_description);
        $stmt->bindParam(":meta_keywords", $this->meta_keywords);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            
            // Associar tags se fornecidas
            if(!empty($this->tags)) {
                $this->associarTags($this->tags);
            }
            
            return true;
        }

        return false;
    }

    /**
     * Atualizar notícia
     */
    public function atualizar() {
        $query = "UPDATE {$this->table_name} 
                  SET titulo=:titulo, slug=:slug, subtitulo=:subtitulo, conteudo=:conteudo, 
                      resumo=:resumo, imagem_destaque=:imagem_destaque, alt_imagem=:alt_imagem,
                      categoria_id=:categoria_id, status=:status, destaque=:destaque, 
                      permitir_comentarios=:permitir_comentarios, data_publicacao=:data_publicacao,
                      meta_title=:meta_title, meta_description=:meta_description, 
                      meta_keywords=:meta_keywords
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->titulo = sanitizeInput($this->titulo);
        $this->slug = $this->gerarSlugUnico($this->titulo, $this->id);
        $this->subtitulo = sanitizeInput($this->subtitulo);
        $this->resumo = sanitizeInput($this->resumo);
        $this->alt_imagem = sanitizeInput($this->alt_imagem);
        $this->meta_title = sanitizeInput($this->meta_title ?: $this->titulo);
        $this->meta_description = sanitizeInput($this->meta_description ?: $this->resumo);
        $this->meta_keywords = sanitizeInput($this->meta_keywords);
        
        // Se mudou para publicado, definir data de publicação
        if($this->status === 'publicado' && empty($this->data_publicacao)) {
            $this->data_publicacao = date('Y-m-d H:i:s');
        }

        // Bind dos parâmetros
        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":slug", $this->slug);
        $stmt->bindParam(":subtitulo", $this->subtitulo);
        $stmt->bindParam(":conteudo", $this->conteudo);
        $stmt->bindParam(":resumo", $this->resumo);
        $stmt->bindParam(":imagem_destaque", $this->imagem_destaque);
        $stmt->bindParam(":alt_imagem", $this->alt_imagem);
        $stmt->bindParam(":categoria_id", $this->categoria_id);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":destaque", $this->destaque);
        $stmt->bindParam(":permitir_comentarios", $this->permitir_comentarios);
        $stmt->bindParam(":data_publicacao", $this->data_publicacao);
        $stmt->bindParam(":meta_title", $this->meta_title);
        $stmt->bindParam(":meta_description", $this->meta_description);
        $stmt->bindParam(":meta_keywords", $this->meta_keywords);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            // Atualizar tags
            if(isset($this->tags)) {
                $this->removerTodasTags();
                if(!empty($this->tags)) {
                    $this->associarTags($this->tags);
                }
            }
            
            return true;
        }

        return false;
    }

    /**
     * Obter notícia por ID (retorna array)
     */
    public function obterPorId($id) {
        $query = "SELECT n.*, u.nome as autor_nome, c.nome as categoria_nome, c.slug as categoria_slug
                  FROM {$this->table_name} n
                  LEFT JOIN usuarios u ON n.autor_id = u.id
                  LEFT JOIN categorias c ON n.categoria_id = c.id
                  WHERE n.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $noticia = $stmt->fetch(PDO::FETCH_ASSOC);
            // Adicionar tags se necessário
            $noticia['tags'] = $this->obterTagsPorNoticia($id);
            return $noticia;
        }
        
        return null;
    }

    /**
     * Buscar notícia por ID (preenche propriedades do objeto)
     */
    public function buscarPorId($id) {
        $query = "SELECT n.*, u.nome as autor_nome, c.nome as categoria_nome, c.slug as categoria_slug
                  FROM {$this->table_name} n
                  LEFT JOIN usuarios u ON n.autor_id = u.id
                  LEFT JOIN categorias c ON n.categoria_id = c.id
                  WHERE n.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->preencherPropriedades($row);
            $this->tags = $this->obterTags();
            return true;
        }
        
        return false;
    }

    /**
     * Buscar notícia por slug
     */
    public function buscarPorSlug($slug) {
        $query = "SELECT n.*, u.nome as autor_nome, c.nome as categoria_nome, c.slug as categoria_slug
                  FROM {$this->table_name} n
                  LEFT JOIN usuarios u ON n.autor_id = u.id
                  LEFT JOIN categorias c ON n.categoria_id = c.id
                  WHERE n.slug = :slug AND n.status = 'publicado'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":slug", $slug);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->preencherPropriedades($row);
            $this->tags = $this->obterTags();
            
            // Incrementar visualizações
            $this->incrementarVisualizacoes();
            
            return true;
        }
        
        return false;
    }

    /**
     * Listar notícias
     */
    public function listar($filtros = []) {
        $where = ["n.status = 'publicado'"];
        $params = [];
        
        // Filtros
        if(!empty($filtros['categoria_id'])) {
            $where[] = "n.categoria_id = :categoria_id";
            $params[':categoria_id'] = $filtros['categoria_id'];
        }
        
        if(!empty($filtros['tag_id'])) {
            $where[] = "EXISTS (SELECT 1 FROM noticia_tags nt WHERE nt.noticia_id = n.id AND nt.tag_id = :tag_id)";
            $params[':tag_id'] = $filtros['tag_id'];
        }
        
        if(!empty($filtros['autor_id'])) {
            $where[] = "n.autor_id = :autor_id";
            $params[':autor_id'] = $filtros['autor_id'];
        }
        
        if(!empty($filtros['destaque'])) {
            $where[] = "n.destaque = 1";
        }
        
        if(!empty($filtros['busca'])) {
            $where[] = "MATCH(n.titulo, n.subtitulo, n.conteudo, n.resumo) AGAINST(:busca IN NATURAL LANGUAGE MODE)";
            $params[':busca'] = $filtros['busca'];
        }
        
        // Ordenação
        $orderBy = "n.data_publicacao DESC";
        $sort_param = $filtros['sort'] ?? $filtros['ordem'] ?? null;
        if(!empty($sort_param)) {
            switch($sort_param) {
                case 'recent':
                case 'recentes':
                    $orderBy = "n.data_publicacao DESC";
                    break;
                case 'oldest':
                case 'antigas':
                    $orderBy = "n.data_publicacao ASC";
                    break;
                case 'popular':
                case 'populares':
                case 'mais_lidas':
                    $orderBy = "n.visualizacoes DESC";
                    break;
                case 'views':
                case 'visualizadas':
                    $orderBy = "n.visualizacoes DESC";
                    break;
                case 'comments':
                case 'comentadas':
                case 'mais_curtidas':
                    $orderBy = "n.curtidas DESC";
                    break;
                case 'alfabetica':
                    $orderBy = "n.titulo ASC";
                    break;
            }
        }
        
        // Paginação
        $page = $filtros['page'] ?? 1;
        $limit = min($filtros['limit'] ?? ITEMS_PER_PAGE, MAX_ITEMS_PER_PAGE);
        $offset = ($page - 1) * $limit;
        
        $whereClause = implode(' AND ', $where);
        
        $query = "SELECT n.*, u.nome as autor_nome, c.nome as categoria_nome, c.slug as categoria_slug, c.cor as categoria_cor
                  FROM {$this->table_name} n
                  LEFT JOIN usuarios u ON n.autor_id = u.id
                  LEFT JOIN categorias c ON n.categoria_id = c.id
                  WHERE {$whereClause}
                  ORDER BY {$orderBy}
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Adicionar tags para cada notícia
        foreach($noticias as &$noticia) {
            $noticia['tags'] = $this->obterTagsPorNoticia($noticia['id']);
        }
        
        return $noticias;
    }

    /**
     * Contar notícias
     */
    public function contar($filtros = []) {
        $where = ["status = 'publicado'"];
        $params = [];
        
        // Aplicar mesmos filtros da listagem
        if(!empty($filtros['categoria_id'])) {
            $where[] = "categoria_id = :categoria_id";
            $params[':categoria_id'] = $filtros['categoria_id'];
        }
        
        if(!empty($filtros['tag_id'])) {
            $where[] = "EXISTS (SELECT 1 FROM noticia_tags nt WHERE nt.noticia_id = id AND nt.tag_id = :tag_id)";
            $params[':tag_id'] = $filtros['tag_id'];
        }
        
        if(!empty($filtros['autor_id'])) {
            $where[] = "autor_id = :autor_id";
            $params[':autor_id'] = $filtros['autor_id'];
        }
        
        if(!empty($filtros['destaque'])) {
            $where[] = "destaque = 1";
        }
        
        if(!empty($filtros['busca'])) {
            $where[] = "MATCH(titulo, subtitulo, conteudo, resumo) AGAINST(:busca IN NATURAL LANGUAGE MODE)";
            $params[':busca'] = $filtros['busca'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $query = "SELECT COUNT(*) as total FROM {$this->table_name} WHERE {$whereClause}";
        $stmt = $this->conn->prepare($query);
        
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }

    /**
     * Excluir notícia
     */
    public function excluir($id) {
        $query = "DELETE FROM {$this->table_name} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }

    /**
     * Incrementar visualizações
     */
    public function incrementarVisualizacoes() {
        $query = "UPDATE {$this->table_name} SET visualizacoes = visualizacoes + 1 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
    }

    /**
     * Curtir/Descurtir notícia
     */
    public function curtir($usuario_id, $tipo = 'curtida') {
        // Verificar se já curtiu
        $query = "SELECT id, tipo FROM curtidas_noticias WHERE noticia_id = :noticia_id AND usuario_id = :usuario_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":noticia_id", $this->id);
        $stmt->bindParam(":usuario_id", $usuario_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if($row['tipo'] === $tipo) {
                // Remover curtida/descurtida
                $query = "DELETE FROM curtidas_noticias WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":id", $row['id']);
                return $stmt->execute();
            } else {
                // Alterar tipo
                $query = "UPDATE curtidas_noticias SET tipo = :tipo WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":tipo", $tipo);
                $stmt->bindParam(":id", $row['id']);
                return $stmt->execute();
            }
        } else {
            // Inserir nova curtida
            $query = "INSERT INTO curtidas_noticias (noticia_id, usuario_id, tipo) VALUES (:noticia_id, :usuario_id, :tipo)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":noticia_id", $this->id);
            $stmt->bindParam(":usuario_id", $usuario_id);
            $stmt->bindParam(":tipo", $tipo);
            return $stmt->execute();
        }
    }

    /**
     * Obter notícias relacionadas
     */
    public function obterRelacionadas($limite = 4) {
        $query = "SELECT n.*, u.nome as autor_nome, c.nome as categoria_nome, c.slug as categoria_slug
                  FROM {$this->table_name} n
                  LEFT JOIN usuarios u ON n.autor_id = u.id
                  LEFT JOIN categorias c ON n.categoria_id = c.id
                  WHERE n.categoria_id = :categoria_id AND n.id != :id AND n.status = 'publicado'
                  ORDER BY n.data_publicacao DESC
                  LIMIT :limite";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":categoria_id", $this->categoria_id);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gerar slug único
     */
    private function gerarSlugUnico($titulo, $id = null) {
        $slug = generateSlug($titulo);
        $slug_original = $slug;
        $contador = 1;
        
        while($this->slugExiste($slug, $id)) {
            $slug = $slug_original . '-' . $contador;
            $contador++;
        }
        
        return $slug;
    }

    /**
     * Verificar se slug existe
     */
    private function slugExiste($slug, $id = null) {
        $query = "SELECT id FROM {$this->table_name} WHERE slug = :slug";
        if($id) {
            $query .= " AND id != :id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":slug", $slug);
        if($id) {
            $stmt->bindParam(":id", $id);
        }
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Associar tags à notícia
     */
    private function associarTags($tags) {
        if(!is_array($tags)) {
            $tags = explode(',', $tags);
        }
        
        foreach($tags as $tag_nome) {
            $tag_nome = trim($tag_nome);
            if(empty($tag_nome)) continue;
            
            // Buscar ou criar tag
            $tag_id = $this->obterOuCriarTag($tag_nome);
            
            // Associar à notícia
            $query = "INSERT IGNORE INTO noticia_tags (noticia_id, tag_id) VALUES (:noticia_id, :tag_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":noticia_id", $this->id);
            $stmt->bindParam(":tag_id", $tag_id);
            $stmt->execute();
        }
    }

    /**
     * Remover todas as tags da notícia
     */
    private function removerTodasTags() {
        $query = "DELETE FROM noticia_tags WHERE noticia_id = :noticia_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":noticia_id", $this->id);
        $stmt->execute();
    }

    /**
     * Obter ou criar tag
     */
    private function obterOuCriarTag($nome) {
        $slug = generateSlug($nome);
        
        // Verificar se tag existe
        $query = "SELECT id FROM tags WHERE slug = :slug";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":slug", $slug);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['id'];
        } else {
            // Criar nova tag
            $query = "INSERT INTO tags (nome, slug) VALUES (:nome, :slug)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":nome", $nome);
            $stmt->bindParam(":slug", $slug);
            $stmt->execute();
            
            return $this->conn->lastInsertId();
        }
    }

    /**
     * Obter tags da notícia
     */
    private function obterTags() {
        $query = "SELECT t.id, t.nome, t.slug FROM tags t 
                  INNER JOIN noticia_tags nt ON t.id = nt.tag_id 
                  WHERE nt.noticia_id = :noticia_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":noticia_id", $this->id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obter tags por ID da notícia
     */
    private function obterTagsPorNoticia($noticia_id) {
        $query = "SELECT t.id, t.nome, t.slug FROM tags t 
                  INNER JOIN noticia_tags nt ON t.id = nt.tag_id 
                  WHERE nt.noticia_id = :noticia_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":noticia_id", $noticia_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Preencher propriedades do objeto
     */
    private function preencherPropriedades($row) {
        $this->id = $row['id'];
        $this->titulo = $row['titulo'];
        $this->slug = $row['slug'];
        $this->subtitulo = $row['subtitulo'];
        $this->conteudo = $row['conteudo'];
        $this->resumo = $row['resumo'];
        $this->imagem_destaque = $row['imagem_destaque'];
        $this->alt_imagem = $row['alt_imagem'];
        $this->autor_id = $row['autor_id'];
        $this->categoria_id = $row['categoria_id'];
        $this->status = $row['status'];
        $this->destaque = $row['destaque'];
        $this->permitir_comentarios = $row['permitir_comentarios'];
        $this->visualizacoes = $row['visualizacoes'];
        $this->curtidas = $row['curtidas'];
        $this->data_publicacao = $row['data_publicacao'];
        $this->data_criacao = $row['data_criacao'];
        $this->data_atualizacao = $row['data_atualizacao'];
        $this->meta_title = $row['meta_title'];
        $this->meta_description = $row['meta_description'];
        $this->meta_keywords = $row['meta_keywords'];
        
        // Dados relacionados
        if(isset($row['autor_nome'])) {
            $this->autor_nome = $row['autor_nome'];
        }
        if(isset($row['categoria_nome'])) {
            $this->categoria_nome = $row['categoria_nome'];
            $this->categoria_slug = $row['categoria_slug'];
        }
        if(isset($row['categoria_cor'])) {
            $this->categoria_cor = $row['categoria_cor'];
        }
    }

    /**
     * Validar dados da notícia
     */
    public function validar() {
        $erros = [];

        if(empty($this->titulo)) {
            $erros[] = "Título é obrigatório";
        } elseif(strlen($this->titulo) < 10) {
            $erros[] = "Título deve ter pelo menos 10 caracteres";
        }

        if(empty($this->conteudo)) {
            $erros[] = "Conteúdo é obrigatório";
        } elseif(strlen($this->conteudo) < 100) {
            $erros[] = "Conteúdo deve ter pelo menos 100 caracteres";
        }

        if(empty($this->categoria_id)) {
            $erros[] = "Categoria é obrigatória";
        }

        if(empty($this->autor_id)) {
            $erros[] = "Autor é obrigatório";
        }

        return $erros;
    }

    /**
     * Buscar notícias com filtros avançados
     */
    public function buscar($filtros = []) {
        $where = ["n.status = 'publicado'"];
        $params = [];
        
        // Filtro por termo de busca
        if (!empty($filtros['termo'])) {
            $where[] = "MATCH(n.titulo, n.subtitulo, n.conteudo, n.resumo) AGAINST(:termo IN NATURAL LANGUAGE MODE)";
            $params[':termo'] = $filtros['termo'];
        }
        
        // Filtro por categoria
        if (!empty($filtros['categoria_id'])) {
            $where[] = "n.categoria_id = :categoria_id";
            $params[':categoria_id'] = $filtros['categoria_id'];
        }
        
        // Filtro por tag
        if (!empty($filtros['tag_id'])) {
            $where[] = "EXISTS (SELECT 1 FROM noticia_tags nt WHERE nt.noticia_id = n.id AND nt.tag_id = :tag_id)";
            $params[':tag_id'] = $filtros['tag_id'];
        }
        
        // Filtro por data
        if (!empty($filtros['data_inicio'])) {
            $where[] = "DATE(n.data_publicacao) >= :data_inicio";
            $params[':data_inicio'] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $where[] = "DATE(n.data_publicacao) <= :data_fim";
            $params[':data_fim'] = $filtros['data_fim'];
        }
        
        // Ordenação
        $orderBy = "n.data_publicacao DESC";
        if (!empty($filtros['ordem'])) {
            switch ($filtros['ordem']) {
                case 'relevancia':
                    if (!empty($filtros['termo'])) {
                        $orderBy = "MATCH(n.titulo, n.subtitulo, n.conteudo, n.resumo) AGAINST(:termo_order IN NATURAL LANGUAGE MODE) DESC, n.data_publicacao DESC";
                        $params[':termo_order'] = $filtros['termo'];
                    }
                    break;
                case 'data_asc':
                    $orderBy = "n.data_publicacao ASC";
                    break;
                case 'visualizacoes':
                    $orderBy = "n.visualizacoes DESC";
                    break;
                case 'curtidas':
                    $orderBy = "n.curtidas DESC";
                    break;
            }
        }
        
        // Paginação
        $pagina = max(1, $filtros['pagina'] ?? 1);
        $limite = min(50, max(1, $filtros['limite'] ?? 10));
        $offset = ($pagina - 1) * $limite;
        
        $whereClause = implode(' AND ', $where);
        
        $query = "SELECT n.*, u.nome as autor_nome, c.nome as categoria_nome, c.slug as categoria_slug, c.cor as categoria_cor
                  FROM {$this->table_name} n
                  LEFT JOIN usuarios u ON n.autor_id = u.id
                  LEFT JOIN categorias c ON n.categoria_id = c.id
                  WHERE {$whereClause}
                  ORDER BY {$orderBy}
                  LIMIT :limite OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Adicionar tags para cada notícia
        foreach ($noticias as &$noticia) {
            $noticia['tags'] = $this->obterTagsPorNoticia($noticia['id']);
        }
        
        return $noticias;
    }
    
    /**
     * Contar resultados de busca
     */
    public function contarBusca($filtros = []) {
        $where = ["status = 'publicado'"];
        $params = [];
        
        // Aplicar mesmos filtros da busca
        if (!empty($filtros['termo'])) {
            $where[] = "MATCH(titulo, subtitulo, conteudo, resumo) AGAINST(:termo IN NATURAL LANGUAGE MODE)";
            $params[':termo'] = $filtros['termo'];
        }
        
        if (!empty($filtros['categoria_id'])) {
            $where[] = "categoria_id = :categoria_id";
            $params[':categoria_id'] = $filtros['categoria_id'];
        }
        
        if (!empty($filtros['tag_id'])) {
            $where[] = "EXISTS (SELECT 1 FROM noticia_tags nt WHERE nt.noticia_id = id AND nt.tag_id = :tag_id)";
            $params[':tag_id'] = $filtros['tag_id'];
        }
        
        if (!empty($filtros['data_inicio'])) {
            $where[] = "DATE(data_publicacao) >= :data_inicio";
            $params[':data_inicio'] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $where[] = "DATE(data_publicacao) <= :data_fim";
            $params[':data_fim'] = $filtros['data_fim'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $query = "SELECT COUNT(*) as total FROM {$this->table_name} WHERE {$whereClause}";
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }
    
    /**
     * Buscar sugestões de títulos para autocomplete
     */
    public function buscarSugestoesTitulos($termo, $limite = 5) {
        $query = "SELECT id, titulo, slug
                  FROM {$this->table_name}
                  WHERE status = 'publicado' AND titulo LIKE :termo
                  ORDER BY data_publicacao DESC
                  LIMIT :limite";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':termo', '%' . $termo . '%');
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obter estatísticas da notícia
     */
    public function obterEstatisticas() {
        $query = "SELECT 
                    COUNT(*) as total_noticias,
                    SUM(CASE WHEN status = 'publicado' THEN 1 ELSE 0 END) as publicadas,
                    SUM(CASE WHEN status = 'rascunho' THEN 1 ELSE 0 END) as rascunhos,
                    SUM(CASE WHEN destaque = 1 THEN 1 ELSE 0 END) as destaques,
                    SUM(visualizacoes) as total_visualizacoes,
                    SUM(curtidas) as total_curtidas
                  FROM {$this->table_name}";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca avançada com múltiplos filtros
     */
    public function buscaAvancada($filtros = [], $pagina = 1, $limite = 10, $ordem = 'data_publicacao DESC') {
        $where = ["n.status = 'publicado'"];
        $params = [];
        
        // Filtro por título
        if (!empty($filtros['titulo'])) {
            $where[] = "n.titulo LIKE :titulo";
            $params[':titulo'] = '%' . $filtros['titulo'] . '%';
        }
        
        // Filtro por conteúdo
        if (!empty($filtros['conteudo'])) {
            $where[] = "n.conteudo LIKE :conteudo";
            $params[':conteudo'] = '%' . $filtros['conteudo'] . '%';
        }
        
        // Filtro por autor
        if (!empty($filtros['autor'])) {
            $where[] = "u.nome LIKE :autor";
            $params[':autor'] = '%' . $filtros['autor'] . '%';
        }
        
        // Filtro por categoria
        if (!empty($filtros['categoria_id'])) {
            $where[] = "n.categoria_id = :categoria_id";
            $params[':categoria_id'] = $filtros['categoria_id'];
        }
        
        // Filtro por tags
        if (!empty($filtros['tags'])) {
            if (is_array($filtros['tags'])) {
                $tagPlaceholders = [];
                foreach ($filtros['tags'] as $index => $tagId) {
                    $placeholder = ':tag_' . $index;
                    $tagPlaceholders[] = $placeholder;
                    $params[$placeholder] = $tagId;
                }
                $where[] = "EXISTS (SELECT 1 FROM noticia_tags nt WHERE nt.noticia_id = n.id AND nt.tag_id IN (" . implode(',', $tagPlaceholders) . "))";
            } else {
                $where[] = "EXISTS (SELECT 1 FROM noticia_tags nt WHERE nt.noticia_id = n.id AND nt.tag_id = :tag_id)";
                $params[':tag_id'] = $filtros['tags'];
            }
        }
        
        // Filtro por data
        if (!empty($filtros['data_inicio'])) {
            $where[] = "DATE(n.data_publicacao) >= :data_inicio";
            $params[':data_inicio'] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $where[] = "DATE(n.data_publicacao) <= :data_fim";
            $params[':data_fim'] = $filtros['data_fim'];
        }
        
        // Paginação
        $pagina = max(1, $pagina);
        $limite = min(50, max(1, $limite));
        $offset = ($pagina - 1) * $limite;
        
        $whereClause = implode(' AND ', $where);
        
        $query = "SELECT n.*, u.nome as autor_nome, c.nome as categoria_nome, c.slug as categoria_slug, c.cor as categoria_cor
                  FROM {$this->table_name} n
                  LEFT JOIN usuarios u ON n.autor_id = u.id
                  LEFT JOIN categorias c ON n.categoria_id = c.id
                  WHERE {$whereClause}
                  ORDER BY {$ordem}
                  LIMIT :limite OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Adicionar tags para cada notícia
        foreach ($noticias as &$noticia) {
            $noticia['tags'] = $this->obterTagsPorNoticia($noticia['id']);
        }
        
        return $noticias;
    }
    
    /**
     * Contar resultados de busca avançada
     */
    public function contarBuscaAvancada($filtros = []) {
        $where = ["n.status = 'publicado'"];
        $params = [];
        
        // Aplicar mesmos filtros da busca avançada
        if (!empty($filtros['titulo'])) {
            $where[] = "n.titulo LIKE :titulo";
            $params[':titulo'] = '%' . $filtros['titulo'] . '%';
        }
        
        if (!empty($filtros['conteudo'])) {
            $where[] = "n.conteudo LIKE :conteudo";
            $params[':conteudo'] = '%' . $filtros['conteudo'] . '%';
        }
        
        if (!empty($filtros['autor'])) {
            $where[] = "u.nome LIKE :autor";
            $params[':autor'] = '%' . $filtros['autor'] . '%';
        }
        
        if (!empty($filtros['categoria_id'])) {
            $where[] = "n.categoria_id = :categoria_id";
            $params[':categoria_id'] = $filtros['categoria_id'];
        }
        
        if (!empty($filtros['tags'])) {
            if (is_array($filtros['tags'])) {
                $tagPlaceholders = [];
                foreach ($filtros['tags'] as $index => $tagId) {
                    $placeholder = ':tag_' . $index;
                    $tagPlaceholders[] = $placeholder;
                    $params[$placeholder] = $tagId;
                }
                $where[] = "EXISTS (SELECT 1 FROM noticia_tags nt WHERE nt.noticia_id = n.id AND nt.tag_id IN (" . implode(',', $tagPlaceholders) . "))";
            } else {
                $where[] = "EXISTS (SELECT 1 FROM noticia_tags nt WHERE nt.noticia_id = n.id AND nt.tag_id = :tag_id)";
                $params[':tag_id'] = $filtros['tags'];
            }
        }
        
        if (!empty($filtros['data_inicio'])) {
            $where[] = "DATE(n.data_publicacao) >= :data_inicio";
            $params[':data_inicio'] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $where[] = "DATE(n.data_publicacao) <= :data_fim";
            $params[':data_fim'] = $filtros['data_fim'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $query = "SELECT COUNT(*) as total 
                  FROM {$this->table_name} n
                  LEFT JOIN usuarios u ON n.autor_id = u.id
                  WHERE {$whereClause}";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }
}
?>