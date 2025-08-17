<?php
/**
 * Modelo Anuncio
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/config.php';

class Anuncio {
    private $conn;
    private $table = 'anuncios';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Criar novo anúncio
     */
    public function criar($dados) {
        $query = "INSERT INTO {$this->table} 
                  (titulo, descricao, tipo, posicao, conteudo, link_destino, imagem_url, 
                   data_inicio, data_fim, ativo, prioridade, target_blank, usuario_id, data_criacao) 
                  VALUES (:titulo, :descricao, :tipo, :posicao, :conteudo, :link_destino, :imagem_url, 
                          :data_inicio, :data_fim, :ativo, :prioridade, :target_blank, :usuario_id, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':titulo', $dados['titulo']);
        $stmt->bindParam(':descricao', $dados['descricao']);
        $stmt->bindParam(':tipo', $dados['tipo']);
        $stmt->bindParam(':posicao', $dados['posicao']);
        $stmt->bindParam(':conteudo', $dados['conteudo']);
        $stmt->bindParam(':link_destino', $dados['link_destino']);
        $stmt->bindParam(':imagem_url', $dados['imagem_url']);
        $stmt->bindParam(':data_inicio', $dados['data_inicio']);
        $stmt->bindParam(':data_fim', $dados['data_fim']);
        $stmt->bindParam(':ativo', $dados['ativo'], PDO::PARAM_BOOL);
        $stmt->bindParam(':prioridade', $dados['prioridade'], PDO::PARAM_INT);
        $stmt->bindParam(':target_blank', $dados['target_blank'], PDO::PARAM_BOOL);
        $stmt->bindParam(':usuario_id', $dados['usuario_id'], PDO::PARAM_INT);
        
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    /**
     * Obter por ID
     */
    public function obterPorId($id) {
        $query = "SELECT a.*, u.nome as autor_nome 
                  FROM {$this->table} a 
                  LEFT JOIN usuarios u ON a.usuario_id = u.id 
                  WHERE a.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Listar anúncios com paginação e filtros
     */
    public function listar($pagina = 1, $limite = 20, $filtros = []) {
        $offset = ($pagina - 1) * $limite;
        
        $where_conditions = [];
        $params = [];
        
        // Filtro por status
        if(!empty($filtros['status'])) {
            if($filtros['status'] === 'ativo') {
                $where_conditions[] = "a.ativo = 1";
            } elseif($filtros['status'] === 'inativo') {
                $where_conditions[] = "a.ativo = 0";
            } elseif($filtros['status'] === 'expirado') {
                $where_conditions[] = "a.data_fim < CURDATE()";
            } elseif($filtros['status'] === 'agendado') {
                $where_conditions[] = "a.data_inicio > CURDATE()";
            }
        }
        
        // Filtro por posição
        if(!empty($filtros['posicao'])) {
            $where_conditions[] = "a.posicao = :posicao";
            $params[':posicao'] = $filtros['posicao'];
        }
        
        // Filtro de busca
        if(!empty($filtros['search'])) {
            $where_conditions[] = "(a.titulo LIKE :search OR a.descricao LIKE :search)";
            $params[':search'] = '%' . $filtros['search'] . '%';
        }
        
        // Filtro por período
        if(!empty($filtros['periodo'])) {
            switch($filtros['periodo']) {
                case 'hoje':
                    $where_conditions[] = "DATE(a.data_criacao) = CURDATE()";
                    break;
                case 'semana':
                    $where_conditions[] = "a.data_criacao >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'mes':
                    $where_conditions[] = "a.data_criacao >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                    break;
                case 'ano':
                    $where_conditions[] = "a.data_criacao >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                    break;
            }
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Query principal
        $query = "SELECT a.*, u.nome as autor_nome,
                         (SELECT COUNT(*) FROM anuncio_impressoes ai WHERE ai.anuncio_id = a.id) as total_impressoes,
                         (SELECT COUNT(*) FROM anuncio_cliques ac WHERE ac.anuncio_id = a.id) as total_cliques
                  FROM {$this->table} a 
                  LEFT JOIN usuarios u ON a.usuario_id = u.id 
                  {$where_clause}
                  ORDER BY a.prioridade DESC, a.data_criacao DESC 
                  LIMIT :limite OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind dos parâmetros
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $anuncios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Query para contar total
        $count_query = "SELECT COUNT(*) as total FROM {$this->table} a {$where_clause}";
        $count_stmt = $this->conn->prepare($count_query);
        
        foreach($params as $key => $value) {
            $count_stmt->bindValue($key, $value);
        }
        
        $count_stmt->execute();
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return [
            'anuncios' => $anuncios,
            'total' => (int)$total,
            'pagina' => $pagina,
            'limite' => $limite,
            'total_paginas' => ceil($total / $limite)
        ];
    }

    /**
     * Obter anúncios ativos para exibição pública
     */
    public function obterAtivos($posicao = '', $limite = 10) {
        $where_conditions = [
            "a.ativo = 1",
            "(a.data_inicio IS NULL OR a.data_inicio <= CURDATE())",
            "(a.data_fim IS NULL OR a.data_fim >= CURDATE())"
        ];
        
        $params = [];
        
        if(!empty($posicao)) {
            $where_conditions[] = "a.posicao = :posicao";
            $params[':posicao'] = $posicao;
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        $query = "SELECT a.id, a.titulo, a.descricao, a.tipo, a.posicao, a.conteudo, 
                         a.link_destino, a.imagem_url, a.target_blank, a.prioridade
                  FROM {$this->table} a 
                  {$where_clause}
                  ORDER BY a.prioridade DESC, RAND() 
                  LIMIT :limite";
        
        $stmt = $this->conn->prepare($query);
        
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atualizar anúncio
     */
    public function atualizar($id, $dados) {
        $campos = [];
        $params = [':id' => $id];
        
        if(isset($dados['titulo'])) {
            $campos[] = "titulo = :titulo";
            $params[':titulo'] = $dados['titulo'];
        }
        
        if(isset($dados['descricao'])) {
            $campos[] = "descricao = :descricao";
            $params[':descricao'] = $dados['descricao'];
        }
        
        if(isset($dados['tipo'])) {
            $campos[] = "tipo = :tipo";
            $params[':tipo'] = $dados['tipo'];
        }
        
        if(isset($dados['posicao'])) {
            $campos[] = "posicao = :posicao";
            $params[':posicao'] = $dados['posicao'];
        }
        
        if(isset($dados['conteudo'])) {
            $campos[] = "conteudo = :conteudo";
            $params[':conteudo'] = $dados['conteudo'];
        }
        
        if(isset($dados['link_destino'])) {
            $campos[] = "link_destino = :link_destino";
            $params[':link_destino'] = $dados['link_destino'];
        }
        
        if(isset($dados['imagem_url'])) {
            $campos[] = "imagem_url = :imagem_url";
            $params[':imagem_url'] = $dados['imagem_url'];
        }
        
        if(isset($dados['data_inicio'])) {
            $campos[] = "data_inicio = :data_inicio";
            $params[':data_inicio'] = $dados['data_inicio'];
        }
        
        if(isset($dados['data_fim'])) {
            $campos[] = "data_fim = :data_fim";
            $params[':data_fim'] = $dados['data_fim'];
        }
        
        if(isset($dados['ativo'])) {
            $campos[] = "ativo = :ativo";
            $params[':ativo'] = $dados['ativo'] ? 1 : 0;
        }
        
        if(isset($dados['prioridade'])) {
            $campos[] = "prioridade = :prioridade";
            $params[':prioridade'] = $dados['prioridade'];
        }
        
        if(isset($dados['target_blank'])) {
            $campos[] = "target_blank = :target_blank";
            $params[':target_blank'] = $dados['target_blank'] ? 1 : 0;
        }
        
        if(empty($campos)) {
            return false;
        }
        
        $campos[] = "data_atualizacao = NOW()";
        
        $query = "UPDATE {$this->table} SET " . implode(', ', $campos) . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }

    /**
     * Deletar anúncio
     */
    public function deletar($id) {
        // Primeiro deletar registros relacionados
        $this->conn->beginTransaction();
        
        try {
            // Deletar impressões
            $query_impressoes = "DELETE FROM anuncio_impressoes WHERE anuncio_id = :id";
            $stmt_impressoes = $this->conn->prepare($query_impressoes);
            $stmt_impressoes->bindParam(':id', $id);
            $stmt_impressoes->execute();
            
            // Deletar cliques
            $query_cliques = "DELETE FROM anuncio_cliques WHERE anuncio_id = :id";
            $stmt_cliques = $this->conn->prepare($query_cliques);
            $stmt_cliques->bindParam(':id', $id);
            $stmt_cliques->execute();
            
            // Deletar anúncio
            $query = "DELETE FROM {$this->table} WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $this->conn->commit();
            return true;
        } catch(Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    /**
     * Registrar impressão
     */
    public function registrarImpressao($anuncio_id, $ip_usuario = null, $user_agent = null) {
        $ip_usuario = $ip_usuario ?: ($_SERVER['REMOTE_ADDR'] ?? '');
        $user_agent = $user_agent ?: ($_SERVER['HTTP_USER_AGENT'] ?? '');
        
        $query = "INSERT INTO anuncio_impressoes (anuncio_id, ip_usuario, user_agent, data_impressao) 
                  VALUES (:anuncio_id, :ip_usuario, :user_agent, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':anuncio_id', $anuncio_id);
        $stmt->bindParam(':ip_usuario', $ip_usuario);
        $stmt->bindParam(':user_agent', $user_agent);
        
        return $stmt->execute();
    }

    /**
     * Registrar clique
     */
    public function registrarClique($anuncio_id, $ip_usuario = null, $user_agent = null) {
        $ip_usuario = $ip_usuario ?: ($_SERVER['REMOTE_ADDR'] ?? '');
        $user_agent = $user_agent ?: ($_SERVER['HTTP_USER_AGENT'] ?? '');
        
        $query = "INSERT INTO anuncio_cliques (anuncio_id, ip_usuario, user_agent, data_clique) 
                  VALUES (:anuncio_id, :ip_usuario, :user_agent, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':anuncio_id', $anuncio_id);
        $stmt->bindParam(':ip_usuario', $ip_usuario);
        $stmt->bindParam(':user_agent', $user_agent);
        
        return $stmt->execute();
    }

    /**
     * Obter estatísticas
     */
    public function obterEstatisticas($periodo = 'mes', $anuncio_id = null) {
        $where_periodo = "";
        $where_anuncio = "";
        $params = [];
        
        // Filtro por período
        switch($periodo) {
            case 'hoje':
                $where_periodo = "AND DATE(ai.data_impressao) = CURDATE()";
                break;
            case 'semana':
                $where_periodo = "AND ai.data_impressao >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'mes':
                $where_periodo = "AND ai.data_impressao >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'ano':
                $where_periodo = "AND ai.data_impressao >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
        }
        
        // Filtro por anúncio específico
        if($anuncio_id) {
            $where_anuncio = "AND a.id = :anuncio_id";
            $params[':anuncio_id'] = $anuncio_id;
        }
        
        $query = "SELECT 
                    a.id,
                    a.titulo,
                    a.posicao,
                    COUNT(DISTINCT ai.id) as impressoes,
                    COUNT(DISTINCT ac.id) as cliques,
                    CASE 
                        WHEN COUNT(DISTINCT ai.id) > 0 
                        THEN ROUND((COUNT(DISTINCT ac.id) / COUNT(DISTINCT ai.id)) * 100, 2) 
                        ELSE 0 
                    END as ctr
                  FROM {$this->table} a
                  LEFT JOIN anuncio_impressoes ai ON a.id = ai.anuncio_id {$where_periodo}
                  LEFT JOIN anuncio_cliques ac ON a.id = ac.anuncio_id {$where_periodo}
                  WHERE 1=1 {$where_anuncio}
                  GROUP BY a.id, a.titulo, a.posicao
                  ORDER BY impressoes DESC";
        
        $stmt = $this->conn->prepare($query);
        
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $estatisticas_detalhadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Estatísticas gerais
        $query_geral = "SELECT 
                          COUNT(DISTINCT a.id) as total_anuncios,
                          SUM(CASE WHEN a.ativo = 1 THEN 1 ELSE 0 END) as anuncios_ativos,
                          COUNT(DISTINCT ai.id) as total_impressoes,
                          COUNT(DISTINCT ac.id) as total_cliques,
                          CASE 
                            WHEN COUNT(DISTINCT ai.id) > 0 
                            THEN ROUND((COUNT(DISTINCT ac.id) / COUNT(DISTINCT ai.id)) * 100, 2) 
                            ELSE 0 
                          END as ctr_geral
                        FROM {$this->table} a
                        LEFT JOIN anuncio_impressoes ai ON a.id = ai.anuncio_id {$where_periodo}
                        LEFT JOIN anuncio_cliques ac ON a.id = ac.anuncio_id {$where_periodo}
                        WHERE 1=1 {$where_anuncio}";
        
        $stmt_geral = $this->conn->prepare($query_geral);
        
        foreach($params as $key => $value) {
            $stmt_geral->bindValue($key, $value);
        }
        
        $stmt_geral->execute();
        $estatisticas_gerais = $stmt_geral->fetch(PDO::FETCH_ASSOC);
        
        return [
            'geral' => $estatisticas_gerais,
            'por_anuncio' => $estatisticas_detalhadas,
            'periodo' => $periodo
        ];
    }

    /**
     * Limpar estatísticas antigas (mais de 1 ano)
     */
    public function limparEstatisticasAntigas() {
        $this->conn->beginTransaction();
        
        try {
            // Limpar impressões antigas
            $query_impressoes = "DELETE FROM anuncio_impressoes 
                                WHERE data_impressao < DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            $stmt_impressoes = $this->conn->prepare($query_impressoes);
            $stmt_impressoes->execute();
            
            // Limpar cliques antigos
            $query_cliques = "DELETE FROM anuncio_cliques 
                             WHERE data_clique < DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            $stmt_cliques = $this->conn->prepare($query_cliques);
            $stmt_cliques->execute();
            
            $this->conn->commit();
            return true;
        } catch(Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
}
?>