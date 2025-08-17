<?php
/**
 * Modelo de Tag
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/config.php';

class Tag {
    private $conn;
    private $table_name = "tags";
    
    // Propriedades da tag
    public $id;
    public $nome;
    public $slug;
    public $descricao;
    public $cor;
    public $ativo;
    public $data_criacao;
    public $data_atualizacao;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Criar nova tag
     */
    public function criar() {
        try {
            $this->conn->beginTransaction();
            
            // Gerar slug único
            $this->slug = $this->gerarSlugUnico($this->nome);
            
            $query = "INSERT INTO " . $this->table_name . " 
                      (nome, slug, descricao, cor, ativo) 
                      VALUES (:nome, :slug, :descricao, :cor, :ativo)";
            
            $stmt = $this->conn->prepare($query);
            
            // Sanitizar dados
            $this->nome = htmlspecialchars(strip_tags($this->nome));
            $this->descricao = htmlspecialchars(strip_tags($this->descricao));
            $this->cor = htmlspecialchars(strip_tags($this->cor));
            
            // Bind dos parâmetros
            $stmt->bindParam(':nome', $this->nome);
            $stmt->bindParam(':slug', $this->slug);
            $stmt->bindParam(':descricao', $this->descricao);
            $stmt->bindParam(':cor', $this->cor);
            $stmt->bindParam(':ativo', $this->ativo);
            
            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                $this->conn->commit();
                return true;
            }
            
            $this->conn->rollback();
            return false;
            
        } catch(Exception $e) {
            $this->conn->rollback();
            logError('Erro ao criar tag: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualizar tag
     */
    public function atualizar() {
        try {
            $this->conn->beginTransaction();
            
            // Gerar novo slug se o nome mudou
            if(!empty($this->nome)) {
                $this->slug = $this->gerarSlugUnico($this->nome, $this->id);
            }
            
            $query = "UPDATE " . $this->table_name . " SET 
                      nome = :nome,
                      slug = :slug,
                      descricao = :descricao,
                      cor = :cor,
                      ativo = :ativo,
                      data_atualizacao = NOW()
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            
            // Sanitizar dados
            $this->nome = htmlspecialchars(strip_tags($this->nome));
            $this->descricao = htmlspecialchars(strip_tags($this->descricao));
            $this->cor = htmlspecialchars(strip_tags($this->cor));
            
            // Bind dos parâmetros
            $stmt->bindParam(':id', $this->id);
            $stmt->bindParam(':nome', $this->nome);
            $stmt->bindParam(':slug', $this->slug);
            $stmt->bindParam(':descricao', $this->descricao);
            $stmt->bindParam(':cor', $this->cor);
            $stmt->bindParam(':ativo', $this->ativo);
            
            if($stmt->execute()) {
                $this->conn->commit();
                return true;
            }
            
            $this->conn->rollback();
            return false;
            
        } catch(Exception $e) {
            $this->conn->rollback();
            logError('Erro ao atualizar tag: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Buscar tag por ID
     */
    public function buscarPorId($id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->preencherPropriedades($row);
                return true;
            }
            
            return false;
            
        } catch(Exception $e) {
            logError('Erro ao buscar tag por ID: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Buscar tag por slug
     */
    public function buscarPorSlug($slug) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE slug = :slug AND ativo = 1 LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':slug', $slug);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->preencherPropriedades($row);
                return true;
            }
            
            return false;
            
        } catch(Exception $e) {
            logError('Erro ao buscar tag por slug: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Buscar tag por nome
     */
    public function buscarPorNome($nome) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE nome = :nome LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nome', $nome);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->preencherPropriedades($row);
                return true;
            }
            
            return false;
            
        } catch(Exception $e) {
            logError('Erro ao buscar tag por nome: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Listar tags
     */
    public function listar($filtros = []) {
        try {
            $where = [];
            $params = [];
            
            // Filtro por status ativo
            if(isset($filtros['ativo'])) {
                $where[] = "t.ativo = :ativo";
                $params[':ativo'] = $filtros['ativo'];
            }
            
            // Filtro por busca
            if(!empty($filtros['busca'])) {
                $where[] = "(t.nome LIKE :busca OR t.descricao LIKE :busca)";
                $params[':busca'] = '%' . $filtros['busca'] . '%';
            }
            
            // Construir query
            $query = "SELECT t.*, 
                             COUNT(nt.noticia_id) as total_noticias,
                             COUNT(CASE WHEN n.status = 'publicado' THEN 1 END) as noticias_publicadas
                      FROM " . $this->table_name . " t
                      LEFT JOIN noticia_tags nt ON t.id = nt.tag_id
                      LEFT JOIN noticias n ON nt.noticia_id = n.id";
            
            if(!empty($where)) {
                $query .= " WHERE " . implode(' AND ', $where);
            }
            
            $query .= " GROUP BY t.id";
            
            // Ordenação
            $ordem = $filtros['ordem'] ?? 'nome';
            switch($ordem) {
                case 'data':
                    $query .= " ORDER BY t.data_criacao DESC";
                    break;
                case 'noticias':
                    $query .= " ORDER BY total_noticias DESC";
                    break;
                case 'popularidade':
                    $query .= " ORDER BY noticias_publicadas DESC";
                    break;
                default:
                    $query .= " ORDER BY t.nome ASC";
            }
            
            // Paginação
            if(isset($filtros['limit'])) {
                $offset = ($filtros['page'] - 1) * $filtros['limit'];
                $query .= " LIMIT :limit OFFSET :offset";
                $params[':limit'] = $filtros['limit'];
                $params[':offset'] = $offset;
            }
            
            $stmt = $this->conn->prepare($query);
            
            // Bind dos parâmetros
            foreach($params as $key => $value) {
                if($key === ':limit' || $key === ':offset') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(Exception $e) {
            logError('Erro ao listar tags: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Contar tags
     */
    public function contar($filtros = []) {
        try {
            $where = [];
            $params = [];
            
            // Filtro por status ativo
            if(isset($filtros['ativo'])) {
                $where[] = "ativo = :ativo";
                $params[':ativo'] = $filtros['ativo'];
            }
            
            // Filtro por busca
            if(!empty($filtros['busca'])) {
                $where[] = "(nome LIKE :busca OR descricao LIKE :busca)";
                $params[':busca'] = '%' . $filtros['busca'] . '%';
            }
            
            $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
            
            if(!empty($where)) {
                $query .= " WHERE " . implode(' AND ', $where);
            }
            
            $stmt = $this->conn->prepare($query);
            
            foreach($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)$row['total'];
            
        } catch(Exception $e) {
            logError('Erro ao contar tags: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Excluir tag
     */
    public function excluir($id) {
        try {
            $this->conn->beginTransaction();
            
            // Remover associações com notícias
            $query = "DELETE FROM noticia_tags WHERE tag_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            // Excluir tag
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if($stmt->execute()) {
                $this->conn->commit();
                return true;
            }
            
            $this->conn->rollback();
            return false;
            
        } catch(Exception $e) {
            $this->conn->rollback();
            logError('Erro ao excluir tag: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ativar/Desativar tag
     */
    public function alterarStatus($id, $ativo) {
        try {
            $query = "UPDATE " . $this->table_name . " SET ativo = :ativo, data_atualizacao = NOW() WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':ativo', $ativo);
            
            return $stmt->execute();
            
        } catch(Exception $e) {
            logError('Erro ao alterar status da tag: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter tags mais utilizadas
     */
    public function obterMaisUtilizadas($limite = 20) {
        try {
            $query = "SELECT t.*, COUNT(nt.noticia_id) as total_noticias
                      FROM " . $this->table_name . " t
                      INNER JOIN noticia_tags nt ON t.id = nt.tag_id
                      INNER JOIN noticias n ON nt.noticia_id = n.id
                      WHERE t.ativo = 1 AND n.status = 'publicado'
                      GROUP BY t.id
                      ORDER BY total_noticias DESC
                      LIMIT :limite";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(Exception $e) {
            logError('Erro ao obter tags mais utilizadas: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter tags relacionadas
     */
    public function obterRelacionadas($tag_id, $limite = 10) {
        try {
            $query = "SELECT DISTINCT t2.*, COUNT(nt2.noticia_id) as relevancia
                      FROM tags t1
                      INNER JOIN noticia_tags nt1 ON t1.id = nt1.tag_id
                      INNER JOIN noticia_tags nt2 ON nt1.noticia_id = nt2.noticia_id
                      INNER JOIN tags t2 ON nt2.tag_id = t2.id
                      WHERE t1.id = :tag_id AND t2.id != :tag_id AND t2.ativo = 1
                      GROUP BY t2.id
                      ORDER BY relevancia DESC
                      LIMIT :limite";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':tag_id', $tag_id, PDO::PARAM_INT);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(Exception $e) {
            logError('Erro ao obter tags relacionadas: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Buscar tags por termo
     */
    public function buscarPorTermo($termo, $limite = 10) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                      WHERE ativo = 1 AND nome LIKE :termo 
                      ORDER BY nome ASC 
                      LIMIT :limite";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':termo', '%' . $termo . '%');
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(Exception $e) {
            logError('Erro ao buscar tags por termo: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Buscar sugestões de tags para autocomplete
     */
    public function buscarSugestoes($termo, $limite = 5) {
        try {
            $query = "SELECT id, nome, slug
                      FROM " . $this->table_name . "
                      WHERE ativo = 1 AND nome LIKE :termo
                      ORDER BY nome ASC
                      LIMIT :limite";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':termo', '%' . $termo . '%');
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(Exception $e) {
            logError('Erro ao buscar sugestões de tags: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Criar ou obter tag existente
     */
    public function criarOuObter($nome) {
        try {
            // Verificar se tag já existe
            if($this->buscarPorNome($nome)) {
                return $this->id;
            }
            
            // Criar nova tag
            $this->nome = $nome;
            $this->ativo = 1;
            
            if($this->criar()) {
                return $this->id;
            }
            
            return false;
            
        } catch(Exception $e) {
            logError('Erro ao criar ou obter tag: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Processar lista de tags (string separada por vírgulas)
     */
    public function processarLista($tags_string) {
        try {
            $tags_ids = [];
            
            if(empty($tags_string)) {
                return $tags_ids;
            }
            
            // Separar tags por vírgula
            $tags_nomes = array_map('trim', explode(',', $tags_string));
            
            foreach($tags_nomes as $nome) {
                if(!empty($nome)) {
                    $tag_id = $this->criarOuObter($nome);
                    if($tag_id) {
                        $tags_ids[] = $tag_id;
                    }
                }
            }
            
            return array_unique($tags_ids);
            
        } catch(Exception $e) {
            logError('Erro ao processar lista de tags: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter estatísticas da tag
     */
    public function obterEstatisticas($id = null) {
        try {
            $where = "";
            $params = [];
            
            if($id) {
                $where = "WHERE t.id = :id";
                $params[':id'] = $id;
            }
            
            $query = "SELECT 
                        t.id,
                        t.nome,
                        COUNT(nt.noticia_id) as total_noticias,
                        COUNT(CASE WHEN n.status = 'publicado' THEN 1 END) as noticias_publicadas,
                        COALESCE(SUM(n.visualizacoes), 0) as total_visualizacoes,
                        COALESCE(SUM(n.curtidas), 0) as total_curtidas,
                        COALESCE(AVG(n.visualizacoes), 0) as media_visualizacoes
                      FROM " . $this->table_name . " t
                      LEFT JOIN noticia_tags nt ON t.id = nt.tag_id
                      LEFT JOIN noticias n ON nt.noticia_id = n.id
                      $where
                      GROUP BY t.id, t.nome
                      ORDER BY total_noticias DESC";
            
            $stmt = $this->conn->prepare($query);
            
            foreach($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            
            if($id) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
        } catch(Exception $e) {
            logError('Erro ao obter estatísticas da tag: ' . $e->getMessage());
            return $id ? [] : [];
        }
    }
    
    /**
     * Obter nuvem de tags
     */
    public function obterNuvemTags($limite = 50) {
        try {
            $query = "SELECT t.*, 
                             COUNT(nt.noticia_id) as peso,
                             CASE 
                                WHEN COUNT(nt.noticia_id) >= 20 THEN 'xl'
                                WHEN COUNT(nt.noticia_id) >= 15 THEN 'lg'
                                WHEN COUNT(nt.noticia_id) >= 10 THEN 'md'
                                WHEN COUNT(nt.noticia_id) >= 5 THEN 'sm'
                                ELSE 'xs'
                             END as tamanho
                      FROM " . $this->table_name . " t
                      INNER JOIN noticia_tags nt ON t.id = nt.tag_id
                      INNER JOIN noticias n ON nt.noticia_id = n.id
                      WHERE t.ativo = 1 AND n.status = 'publicado'
                      GROUP BY t.id
                      ORDER BY peso DESC, t.nome ASC
                      LIMIT :limite";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(Exception $e) {
            logError('Erro ao obter nuvem de tags: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Validar dados da tag
     */
    public function validar() {
        $erros = [];
        
        // Nome obrigatório
        if(empty($this->nome)) {
            $erros[] = 'Nome da tag é obrigatório';
        } elseif(strlen($this->nome) < 2) {
            $erros[] = 'Nome da tag deve ter pelo menos 2 caracteres';
        } elseif(strlen($this->nome) > 50) {
            $erros[] = 'Nome da tag deve ter no máximo 50 caracteres';
        }
        
        // Validar cor (formato hexadecimal)
        if(!empty($this->cor) && !preg_match('/^#[a-fA-F0-9]{6}$/', $this->cor)) {
            $erros[] = 'Cor deve estar no formato hexadecimal (#RRGGBB)';
        }
        
        return $erros;
    }
    
    /**
     * Validar dados da tag (aceita dados como parâmetro)
     */
    public function validarDados($dados, $id = null) {
        $erros = [];
        
        // Nome obrigatório
        if(empty($dados['nome'])) {
            $erros[] = 'Nome da tag é obrigatório';
        } elseif(strlen($dados['nome']) < 2) {
            $erros[] = 'Nome da tag deve ter pelo menos 2 caracteres';
        } elseif(strlen($dados['nome']) > 50) {
            $erros[] = 'Nome da tag deve ter no máximo 50 caracteres';
        }
        
        // Verificar se nome já existe (exceto para o próprio registro)
        if(!empty($dados['nome'])) {
            $tag_existente = $this->buscarPorNome($dados['nome']);
            if($tag_existente && (!$id || $tag_existente['id'] != $id)) {
                $erros[] = 'Já existe uma tag com este nome';
            }
        }
        
        // Validar cor (formato hexadecimal)
        if(!empty($dados['cor']) && !preg_match('/^#[a-fA-F0-9]{6}$/', $dados['cor'])) {
            $erros[] = 'Cor deve estar no formato hexadecimal (#RRGGBB)';
        }
        
        // Validar descrição (opcional, mas com limite)
        if(!empty($dados['descricao']) && strlen($dados['descricao']) > 255) {
            $erros[] = 'Descrição deve ter no máximo 255 caracteres';
        }
        
        return [
            'valido' => empty($erros),
            'erros' => $erros
        ];
    }
    
    /**
     * Verificar se slug já existe
     */
    private function slugExiste($slug, $excluir_id = null) {
        try {
            $query = "SELECT id FROM " . $this->table_name . " WHERE slug = :slug";
            
            if($excluir_id) {
                $query .= " AND id != :excluir_id";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':slug', $slug);
            
            if($excluir_id) {
                $stmt->bindParam(':excluir_id', $excluir_id);
            }
            
            $stmt->execute();
            return $stmt->rowCount() > 0;
            
        } catch(Exception $e) {
            logError('Erro ao verificar slug: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gerar slug único
     */
    private function gerarSlugUnico($nome, $excluir_id = null) {
        $slug = gerarSlug($nome);
        $slug_original = $slug;
        $contador = 1;
        
        while($this->slugExiste($slug, $excluir_id)) {
            $slug = $slug_original . '-' . $contador;
            $contador++;
        }
        
        return $slug;
    }
    
    /**
     * Preencher propriedades do objeto
     */
    private function preencherPropriedades($row) {
        $this->id = $row['id'];
        $this->nome = $row['nome'];
        $this->slug = $row['slug'];
        $this->descricao = $row['descricao'];
        $this->cor = $row['cor'];
        $this->ativo = $row['ativo'];
        $this->data_criacao = $row['data_criacao'];
        $this->data_atualizacao = $row['data_atualizacao'];
    }
}
?>