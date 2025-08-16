<?php
/**
 * Modelo de Categoria
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/config.php';

class Categoria {
    private $conn;
    private $table_name = "categorias";
    
    // Propriedades da categoria
    public $id;
    public $nome;
    public $slug;
    public $descricao;
    public $cor;
    public $icone;
    public $ativo;
    public $ordem;
    public $meta_title;
    public $meta_description;
    public $data_criacao;
    public $data_atualizacao;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Criar nova categoria
     */
    public function criar() {
        try {
            $this->conn->beginTransaction();
            
            // Gerar slug único
            $this->slug = $this->gerarSlugUnico($this->nome);
            
            $query = "INSERT INTO " . $this->table_name . " 
                      (nome, slug, descricao, cor, icone, ativo, ordem, meta_title, meta_description) 
                      VALUES (:nome, :slug, :descricao, :cor, :icone, :ativo, :ordem, :meta_title, :meta_description)";
            
            $stmt = $this->conn->prepare($query);
            
            // Sanitizar dados
            $this->nome = htmlspecialchars(strip_tags($this->nome));
            $this->descricao = htmlspecialchars(strip_tags($this->descricao));
            $this->cor = htmlspecialchars(strip_tags($this->cor));
            $this->icone = htmlspecialchars(strip_tags($this->icone));
            $this->meta_title = htmlspecialchars(strip_tags($this->meta_title));
            $this->meta_description = htmlspecialchars(strip_tags($this->meta_description));
            
            // Bind dos parâmetros
            $stmt->bindParam(':nome', $this->nome);
            $stmt->bindParam(':slug', $this->slug);
            $stmt->bindParam(':descricao', $this->descricao);
            $stmt->bindParam(':cor', $this->cor);
            $stmt->bindParam(':icone', $this->icone);
            $stmt->bindParam(':ativo', $this->ativo);
            $stmt->bindParam(':ordem', $this->ordem);
            $stmt->bindParam(':meta_title', $this->meta_title);
            $stmt->bindParam(':meta_description', $this->meta_description);
            
            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                $this->conn->commit();
                return true;
            }
            
            $this->conn->rollback();
            return false;
            
        } catch(Exception $e) {
            $this->conn->rollback();
            logError('Erro ao criar categoria: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualizar categoria
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
                      icone = :icone,
                      ativo = :ativo,
                      ordem = :ordem,
                      meta_title = :meta_title,
                      meta_description = :meta_description,
                      data_atualizacao = NOW()
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            
            // Sanitizar dados
            $this->nome = htmlspecialchars(strip_tags($this->nome));
            $this->descricao = htmlspecialchars(strip_tags($this->descricao));
            $this->cor = htmlspecialchars(strip_tags($this->cor));
            $this->icone = htmlspecialchars(strip_tags($this->icone));
            $this->meta_title = htmlspecialchars(strip_tags($this->meta_title));
            $this->meta_description = htmlspecialchars(strip_tags($this->meta_description));
            
            // Bind dos parâmetros
            $stmt->bindParam(':id', $this->id);
            $stmt->bindParam(':nome', $this->nome);
            $stmt->bindParam(':slug', $this->slug);
            $stmt->bindParam(':descricao', $this->descricao);
            $stmt->bindParam(':cor', $this->cor);
            $stmt->bindParam(':icone', $this->icone);
            $stmt->bindParam(':ativo', $this->ativo);
            $stmt->bindParam(':ordem', $this->ordem);
            $stmt->bindParam(':meta_title', $this->meta_title);
            $stmt->bindParam(':meta_description', $this->meta_description);
            
            if($stmt->execute()) {
                $this->conn->commit();
                return true;
            }
            
            $this->conn->rollback();
            return false;
            
        } catch(Exception $e) {
            $this->conn->rollback();
            logError('Erro ao atualizar categoria: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Buscar categoria por ID
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
            logError('Erro ao buscar categoria por ID: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Buscar categoria por slug
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
            logError('Erro ao buscar categoria por slug: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Listar categorias
     */
    public function listar($filtros = []) {
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
            
            // Construir query
            $query = "SELECT c.*, 
                             COUNT(n.id) as total_noticias,
                             COUNT(CASE WHEN n.status = 'publicado' THEN 1 END) as noticias_publicadas
                      FROM " . $this->table_name . " c
                      LEFT JOIN noticias n ON c.id = n.categoria_id";
            
            if(!empty($where)) {
                $query .= " WHERE " . implode(' AND ', $where);
            }
            
            $query .= " GROUP BY c.id";
            
            // Ordenação
            $ordem = $filtros['ordem'] ?? 'ordem';
            switch($ordem) {
                case 'nome':
                    $query .= " ORDER BY c.nome ASC";
                    break;
                case 'data':
                    $query .= " ORDER BY c.data_criacao DESC";
                    break;
                case 'noticias':
                    $query .= " ORDER BY total_noticias DESC";
                    break;
                default:
                    $query .= " ORDER BY c.ordem ASC, c.nome ASC";
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
            logError('Erro ao listar categorias: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Contar categorias
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
            logError('Erro ao contar categorias: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Excluir categoria
     */
    public function excluir($id) {
        try {
            $this->conn->beginTransaction();
            
            // Verificar se há notícias associadas
            $query = "SELECT COUNT(*) as total FROM noticias WHERE categoria_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($row['total'] > 0) {
                $this->conn->rollback();
                return 'Não é possível excluir categoria com notícias associadas';
            }
            
            // Excluir categoria
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
            logError('Erro ao excluir categoria: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ativar/Desativar categoria
     */
    public function alterarStatus($id, $ativo) {
        try {
            $query = "UPDATE " . $this->table_name . " SET ativo = :ativo, data_atualizacao = NOW() WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':ativo', $ativo);
            
            return $stmt->execute();
            
        } catch(Exception $e) {
            logError('Erro ao alterar status da categoria: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reordenar categorias
     */
    public function reordenar($ordenacao) {
        try {
            $this->conn->beginTransaction();
            
            foreach($ordenacao as $ordem => $id) {
                $query = "UPDATE " . $this->table_name . " SET ordem = :ordem WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':ordem', $ordem);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
            }
            
            $this->conn->commit();
            return true;
            
        } catch(Exception $e) {
            $this->conn->rollback();
            logError('Erro ao reordenar categorias: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter categorias mais utilizadas
     */
    public function obterMaisUtilizadas($limite = 10) {
        try {
            $query = "SELECT c.*, COUNT(n.id) as total_noticias
                      FROM " . $this->table_name . " c
                      INNER JOIN noticias n ON c.id = n.categoria_id
                      WHERE c.ativo = 1 AND n.status = 'publicado'
                      GROUP BY c.id
                      ORDER BY total_noticias DESC
                      LIMIT :limite";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(Exception $e) {
            logError('Erro ao obter categorias mais utilizadas: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter estatísticas da categoria
     */
    public function obterEstatisticas($id = null) {
        try {
            $where = "";
            $params = [];
            
            if($id) {
                $where = "WHERE c.id = :id";
                $params[':id'] = $id;
            }
            
            $query = "SELECT 
                        c.id,
                        c.nome,
                        COUNT(n.id) as total_noticias,
                        COUNT(CASE WHEN n.status = 'publicado' THEN 1 END) as noticias_publicadas,
                        COUNT(CASE WHEN n.status = 'rascunho' THEN 1 END) as noticias_rascunho,
                        COUNT(CASE WHEN n.destaque = 1 THEN 1 END) as noticias_destaque,
                        COALESCE(SUM(n.visualizacoes), 0) as total_visualizacoes,
                        COALESCE(SUM(n.curtidas), 0) as total_curtidas,
                        COALESCE(AVG(n.visualizacoes), 0) as media_visualizacoes
                      FROM " . $this->table_name . " c
                      LEFT JOIN noticias n ON c.id = n.categoria_id
                      $where
                      GROUP BY c.id, c.nome
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
            logError('Erro ao obter estatísticas da categoria: ' . $e->getMessage());
            return $id ? [] : [];
        }
    }
    
    /**
     * Validar dados da categoria
     */
    public function validar() {
        $erros = [];
        
        // Nome obrigatório
        if(empty($this->nome)) {
            $erros[] = 'Nome da categoria é obrigatório';
        } elseif(strlen($this->nome) < 2) {
            $erros[] = 'Nome da categoria deve ter pelo menos 2 caracteres';
        } elseif(strlen($this->nome) > 100) {
            $erros[] = 'Nome da categoria deve ter no máximo 100 caracteres';
        }
        
        // Validar cor (formato hexadecimal)
        if(!empty($this->cor) && !preg_match('/^#[a-fA-F0-9]{6}$/', $this->cor)) {
            $erros[] = 'Cor deve estar no formato hexadecimal (#RRGGBB)';
        }
        
        // Validar ordem
        if(!empty($this->ordem) && (!is_numeric($this->ordem) || $this->ordem < 0)) {
            $erros[] = 'Ordem deve ser um número positivo';
        }
        
        // Validar meta title
        if(!empty($this->meta_title) && strlen($this->meta_title) > 60) {
            $erros[] = 'Meta title deve ter no máximo 60 caracteres';
        }
        
        // Validar meta description
        if(!empty($this->meta_description) && strlen($this->meta_description) > 160) {
            $erros[] = 'Meta description deve ter no máximo 160 caracteres';
        }
        
        return $erros;
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
        $this->icone = $row['icone'];
        $this->ativo = $row['ativo'];
        $this->ordem = $row['ordem'];
        $this->meta_title = $row['meta_title'];
        $this->meta_description = $row['meta_description'];
        $this->data_criacao = $row['data_criacao'];
        $this->data_atualizacao = $row['data_atualizacao'];
    }
}
?>