<?php
/**
 * Modelo de Comentário
 * Portal de Notícias
 */

class Comentario {
    private $db;
    private $table = 'comentarios';
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Criar novo comentário
     */
    public function criar($dados) {
        try {
            $sql = "INSERT INTO {$this->table} 
                    (noticia_id, usuario_id, comentario_pai_id, conteudo, autor_nome, autor_email, ip_address, user_agent) 
                    VALUES (:noticia_id, :usuario_id, :comentario_pai_id, :conteudo, :autor_nome, :autor_email, :ip_address, :user_agent)";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':noticia_id', $dados['noticia_id']);
            $stmt->bindParam(':usuario_id', $dados['usuario_id']);
            $stmt->bindParam(':comentario_pai_id', $dados['comentario_pai_id']);
            $stmt->bindParam(':conteudo', $dados['conteudo']);
            $stmt->bindParam(':autor_nome', $dados['autor_nome']);
            $stmt->bindParam(':autor_email', $dados['autor_email']);
            $stmt->bindParam(':ip_address', $dados['ip_address']);
            $stmt->bindParam(':user_agent', $dados['user_agent']);
            
            if($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch(PDOException $e) {
            logError('Erro ao criar comentário: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualizar comentário
     */
    public function atualizar($id, $dados) {
        try {
            $campos = [];
            $valores = [];
            
            foreach($dados as $campo => $valor) {
                if(in_array($campo, ['conteudo', 'status', 'moderado_por', 'moderado_em'])) {
                    $campos[] = "$campo = :$campo";
                    $valores[$campo] = $valor;
                }
            }
            
            if(empty($campos)) {
                return false;
            }
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $campos) . ", atualizado_em = NOW() WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);
            
            foreach($valores as $campo => $valor) {
                $stmt->bindParam(":$campo", $valor);
            }
            
            return $stmt->execute();
        } catch(PDOException $e) {
            logError('Erro ao atualizar comentário: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter comentário por ID
     */
    public function obterPorId($id) {
        try {
            $sql = "SELECT c.*, u.nome as usuario_nome, u.foto as usuario_foto,
                           n.titulo as noticia_titulo, n.slug as noticia_slug
                    FROM {$this->table} c
                    LEFT JOIN usuarios u ON c.usuario_id = u.id
                    LEFT JOIN noticias n ON c.noticia_id = n.id
                    WHERE c.id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $comentario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($comentario) {
                return $this->formatarComentario($comentario);
            }
            
            return null;
        } catch(PDOException $e) {
            logError('Erro ao obter comentário: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Listar comentários
     */
    public function listar($page = 1, $limit = 20, $filtros = []) {
        try {
            $offset = ($page - 1) * $limit;
            $where = ['1=1'];
            $params = [];
            
            // Filtros
            if(!empty($filtros['noticia_id'])) {
                $where[] = 'c.noticia_id = :noticia_id';
                $params['noticia_id'] = $filtros['noticia_id'];
            }
            
            if(!empty($filtros['usuario_id'])) {
                $where[] = 'c.usuario_id = :usuario_id';
                $params['usuario_id'] = $filtros['usuario_id'];
            }
            
            if(!empty($filtros['status'])) {
                $where[] = 'c.status = :status';
                $params['status'] = $filtros['status'];
            }
            
            if(!empty($filtros['search'])) {
                $where[] = '(c.conteudo LIKE :search OR c.autor_nome LIKE :search)';
                $params['search'] = '%' . $filtros['search'] . '%';
            }
            
            if(!empty($filtros['data_inicio'])) {
                $where[] = 'DATE(c.criado_em) >= :data_inicio';
                $params['data_inicio'] = $filtros['data_inicio'];
            }
            
            if(!empty($filtros['data_fim'])) {
                $where[] = 'DATE(c.criado_em) <= :data_fim';
                $params['data_fim'] = $filtros['data_fim'];
            }
            
            // Ordenação
            $order = 'c.criado_em DESC';
            if(!empty($filtros['order'])) {
                $orders_validos = ['criado_em', 'likes', 'autor_nome', 'status'];
                if(in_array($filtros['order'], $orders_validos)) {
                    $direction = (!empty($filtros['direction']) && $filtros['direction'] === 'ASC') ? 'ASC' : 'DESC';
                    $order = "c.{$filtros['order']} $direction";
                }
            }
            
            $sql = "SELECT c.*, u.nome as usuario_nome, u.foto as usuario_foto,
                           n.titulo as noticia_titulo, n.slug as noticia_slug
                    FROM {$this->table} c
                    LEFT JOIN usuarios u ON c.usuario_id = u.id
                    LEFT JOIN noticias n ON c.noticia_id = n.id
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY $order
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            foreach($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map([$this, 'formatarComentario'], $comentarios);
        } catch(PDOException $e) {
            logError('Erro ao listar comentários: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Contar comentários
     */
    public function contar($filtros = []) {
        try {
            $where = ['1=1'];
            $params = [];
            
            // Aplicar os mesmos filtros da listagem
            if(!empty($filtros['noticia_id'])) {
                $where[] = 'c.noticia_id = :noticia_id';
                $params['noticia_id'] = $filtros['noticia_id'];
            }
            
            if(!empty($filtros['usuario_id'])) {
                $where[] = 'c.usuario_id = :usuario_id';
                $params['usuario_id'] = $filtros['usuario_id'];
            }
            
            if(!empty($filtros['status'])) {
                $where[] = 'c.status = :status';
                $params['status'] = $filtros['status'];
            }
            
            if(!empty($filtros['search'])) {
                $where[] = '(c.conteudo LIKE :search OR c.autor_nome LIKE :search)';
                $params['search'] = '%' . $filtros['search'] . '%';
            }
            
            if(!empty($filtros['data_inicio'])) {
                $where[] = 'DATE(c.criado_em) >= :data_inicio';
                $params['data_inicio'] = $filtros['data_inicio'];
            }
            
            if(!empty($filtros['data_fim'])) {
                $where[] = 'DATE(c.criado_em) <= :data_fim';
                $params['data_fim'] = $filtros['data_fim'];
            }
            
            $sql = "SELECT COUNT(*) as total FROM {$this->table} c WHERE " . implode(' AND ', $where);
            
            $stmt = $this->db->prepare($sql);
            
            foreach($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)$result['total'];
        } catch(PDOException $e) {
            logError('Erro ao contar comentários: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obter comentários por notícia (com hierarquia)
     */
    public function obterPorNoticia($noticia_id, $status = 'aprovado', $limit = 50) {
        try {
            $sql = "SELECT c.*, u.nome as usuario_nome, u.foto as usuario_foto
                    FROM {$this->table} c
                    LEFT JOIN usuarios u ON c.usuario_id = u.id
                    WHERE c.noticia_id = :noticia_id AND c.status = :status
                    ORDER BY c.comentario_pai_id ASC, c.criado_em ASC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':noticia_id', $noticia_id);
            $stmt->bindParam(':status', $status);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $comentarios_formatados = array_map([$this, 'formatarComentario'], $comentarios);
            
            // Organizar em hierarquia
            return $this->organizarHierarquia($comentarios_formatados);
        } catch(PDOException $e) {
            logError('Erro ao obter comentários por notícia: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Excluir comentário
     */
    public function excluir($id) {
        try {
            $this->db->beginTransaction();
            
            // Excluir respostas primeiro
            $sql_respostas = "DELETE FROM {$this->table} WHERE comentario_pai_id = :id";
            $stmt_respostas = $this->db->prepare($sql_respostas);
            $stmt_respostas->bindParam(':id', $id);
            $stmt_respostas->execute();
            
            // Excluir comentário principal
            $sql = "DELETE FROM {$this->table} WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $resultado = $stmt->execute();
            
            $this->db->commit();
            return $resultado;
        } catch(PDOException $e) {
            $this->db->rollback();
            logError('Erro ao excluir comentário: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Moderar comentário (aprovar/rejeitar)
     */
    public function moderar($id, $status, $moderador_id) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET status = :status, moderado_por = :moderador_id, moderado_em = NOW() 
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':moderador_id', $moderador_id);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            logError('Erro ao moderar comentário: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Curtir/descurtir comentário
     */
    public function curtir($comentario_id, $usuario_id, $tipo = 'like') {
        try {
            $this->db->beginTransaction();
            
            // Verificar se já existe like/dislike do usuário
            $sql_check = "SELECT id, tipo FROM comentario_likes 
                         WHERE comentario_id = :comentario_id AND usuario_id = :usuario_id";
            $stmt_check = $this->db->prepare($sql_check);
            $stmt_check->bindParam(':comentario_id', $comentario_id);
            $stmt_check->bindParam(':usuario_id', $usuario_id);
            $stmt_check->execute();
            $like_existente = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if($like_existente) {
                if($like_existente['tipo'] === $tipo) {
                    // Remover like/dislike
                    $sql_delete = "DELETE FROM comentario_likes WHERE id = :id";
                    $stmt_delete = $this->db->prepare($sql_delete);
                    $stmt_delete->bindParam(':id', $like_existente['id']);
                    $stmt_delete->execute();
                } else {
                    // Alterar tipo
                    $sql_update = "UPDATE comentario_likes SET tipo = :tipo WHERE id = :id";
                    $stmt_update = $this->db->prepare($sql_update);
                    $stmt_update->bindParam(':tipo', $tipo);
                    $stmt_update->bindParam(':id', $like_existente['id']);
                    $stmt_update->execute();
                }
            } else {
                // Criar novo like/dislike
                $sql_insert = "INSERT INTO comentario_likes (comentario_id, usuario_id, tipo) 
                              VALUES (:comentario_id, :usuario_id, :tipo)";
                $stmt_insert = $this->db->prepare($sql_insert);
                $stmt_insert->bindParam(':comentario_id', $comentario_id);
                $stmt_insert->bindParam(':usuario_id', $usuario_id);
                $stmt_insert->bindParam(':tipo', $tipo);
                $stmt_insert->execute();
            }
            
            $this->db->commit();
            return true;
        } catch(PDOException $e) {
            $this->db->rollback();
            logError('Erro ao curtir comentário: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter estatísticas de comentários
     */
    public function obterEstatisticas() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_comentarios,
                        COUNT(CASE WHEN status = 'aprovado' THEN 1 END) as aprovados,
                        COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pendentes,
                        COUNT(CASE WHEN status = 'rejeitado' THEN 1 END) as rejeitados,
                        COUNT(CASE WHEN DATE(criado_em) = CURDATE() THEN 1 END) as hoje,
                        COUNT(CASE WHEN DATE(criado_em) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as ultima_semana,
                        COUNT(CASE WHEN DATE(criado_em) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as ultimo_mes
                    FROM {$this->table}";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            logError('Erro ao obter estatísticas de comentários: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Validar dados do comentário
     */
    public function validarDados($dados, $id = null) {
        $erros = [];
        
        // Validar conteúdo
        if(empty($dados['conteudo'])) {
            $erros[] = 'Conteúdo do comentário é obrigatório';
        } elseif(strlen($dados['conteudo']) < 3) {
            $erros[] = 'Conteúdo deve ter pelo menos 3 caracteres';
        } elseif(strlen($dados['conteudo']) > 2000) {
            $erros[] = 'Conteúdo não pode exceder 2000 caracteres';
        }
        
        // Validar notícia_id
        if(empty($dados['noticia_id'])) {
            $erros[] = 'ID da notícia é obrigatório';
        }
        
        // Validar autor (se não logado)
        if(empty($dados['usuario_id'])) {
            if(empty($dados['autor_nome'])) {
                $erros[] = 'Nome do autor é obrigatório';
            }
            if(empty($dados['autor_email']) || !isValidEmail($dados['autor_email'])) {
                $erros[] = 'Email válido é obrigatório';
            }
        }
        
        return [
            'valido' => empty($erros),
            'erros' => $erros
        ];
    }
    
    /**
     * Formatar dados do comentário
     */
    private function formatarComentario($comentario) {
        return [
            'id' => (int)$comentario['id'],
            'noticia_id' => (int)$comentario['noticia_id'],
            'usuario_id' => $comentario['usuario_id'] ? (int)$comentario['usuario_id'] : null,
            'comentario_pai_id' => $comentario['comentario_pai_id'] ? (int)$comentario['comentario_pai_id'] : null,
            'conteudo' => $comentario['conteudo'],
            'status' => $comentario['status'],
            'likes' => (int)$comentario['likes'],
            'dislikes' => (int)$comentario['dislikes'],
            'autor' => [
                'nome' => $comentario['usuario_nome'] ?: $comentario['autor_nome'],
                'email' => $comentario['autor_email'],
                'foto' => $comentario['usuario_foto']
            ],
            'noticia' => [
                'titulo' => $comentario['noticia_titulo'] ?? null,
                'slug' => $comentario['noticia_slug'] ?? null
            ],
            'criado_em' => $comentario['criado_em'],
            'atualizado_em' => $comentario['atualizado_em'],
            'moderado_em' => $comentario['moderado_em'],
            'respostas' => []
        ];
    }
    
    /**
     * Organizar comentários em hierarquia
     */
    private function organizarHierarquia($comentarios) {
        $hierarquia = [];
        $mapa = [];
        
        // Criar mapa de comentários
        foreach($comentarios as $comentario) {
            $mapa[$comentario['id']] = $comentario;
        }
        
        // Organizar hierarquia
        foreach($comentarios as $comentario) {
            if($comentario['comentario_pai_id'] === null) {
                // Comentário principal
                $hierarquia[] = $comentario;
            } else {
                // Resposta - adicionar ao comentário pai
                if(isset($mapa[$comentario['comentario_pai_id']])) {
                    $pai_id = $comentario['comentario_pai_id'];
                    // Encontrar o comentário pai na hierarquia e adicionar a resposta
                    foreach($hierarquia as &$item) {
                        if($item['id'] === $pai_id) {
                            $item['respostas'][] = $comentario;
                            break;
                        }
                    }
                }
            }
        }
        
        return $hierarquia;
    }
    
    /**
     * Exportar comentários para CSV
     */
    public function exportarParaCSV($filtros = []) {
        try {
            $where = ['1=1'];
            $params = [];
            
            // Aplicar filtros
            if(!empty($filtros['noticia_id'])) {
                $where[] = 'c.noticia_id = :noticia_id';
                $params['noticia_id'] = $filtros['noticia_id'];
            }
            
            if(!empty($filtros['status'])) {
                $where[] = 'c.status = :status';
                $params['status'] = $filtros['status'];
            }
            
            if(!empty($filtros['search'])) {
                $where[] = '(c.conteudo LIKE :search OR c.autor_nome LIKE :search)';
                $params['search'] = '%' . $filtros['search'] . '%';
            }
            
            if(!empty($filtros['data_inicio'])) {
                $where[] = 'DATE(c.criado_em) >= :data_inicio';
                $params['data_inicio'] = $filtros['data_inicio'];
            }
            
            if(!empty($filtros['data_fim'])) {
                $where[] = 'DATE(c.criado_em) <= :data_fim';
                $params['data_fim'] = $filtros['data_fim'];
            }
            
            $sql = "SELECT c.id, c.noticia_id, c.conteudo, c.status, c.likes, c.dislikes,
                           c.criado_em, c.atualizado_em, c.moderado_em, c.ip_address,
                           COALESCE(u.nome, c.autor_nome) as autor_nome,
                           c.autor_email, n.titulo as noticia_titulo,
                           CASE WHEN c.comentario_pai_id IS NULL THEN 'Principal' ELSE 'Resposta' END as tipo
                    FROM {$this->table} c
                    LEFT JOIN usuarios u ON c.usuario_id = u.id
                    LEFT JOIN noticias n ON c.noticia_id = n.id
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY c.criado_em DESC";
            
            $stmt = $this->db->prepare($sql);
            
            foreach($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            logError('Erro ao exportar comentários: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Exportar comentários para JSON
     */
    public function exportarParaJSON($filtros = []) {
        try {
            $comentarios = $this->listar(1, 10000, $filtros); // Buscar até 10k comentários
            return $comentarios;
        } catch(Exception $e) {
            logError('Erro ao exportar comentários para JSON: ' . $e->getMessage());
            return [];
        }
    }
}
?>