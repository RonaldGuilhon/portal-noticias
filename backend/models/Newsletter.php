<?php
/**
 * Modelo Newsletter
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/config.php';

class Newsletter {
    private $conn;
    private $table = 'newsletter';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Criar nova inscrição
     */
    public function criar($dados) {
        $query = "INSERT INTO {$this->table} 
                  (email, nome, categorias_interesse, token_confirmacao, data_inscricao) 
                  VALUES (:email, :nome, :categorias_interesse, :token_confirmacao, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':email', $dados['email']);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':categorias_interesse', $dados['categorias_interesse']);
        $stmt->bindParam(':token_confirmacao', $dados['token_confirmacao']);
        
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    /**
     * Obter por ID
     */
    public function obterPorId($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obter por email
     */
    public function obterPorEmail($email) {
        $query = "SELECT * FROM {$this->table} WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obter por token
     */
    public function obterPorToken($token) {
        $query = "SELECT * FROM {$this->table} WHERE token_confirmacao = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Listar assinantes com paginação e filtros
     */
    public function listar($pagina = 1, $limite = 20, $filtros = []) {
        $offset = ($pagina - 1) * $limite;
        
        $where_conditions = [];
        $params = [];
        
        // Filtro por status
        if(!empty($filtros['status'])) {
            if($filtros['status'] === 'ativo') {
                $where_conditions[] = "ativo = 1";
            } elseif($filtros['status'] === 'inativo') {
                $where_conditions[] = "ativo = 0";
            } elseif($filtros['status'] === 'confirmado') {
                $where_conditions[] = "confirmado = 1";
            } elseif($filtros['status'] === 'pendente') {
                $where_conditions[] = "confirmado = 0";
            }
        }
        
        // Filtro de busca
        if(!empty($filtros['search'])) {
            $where_conditions[] = "(email LIKE :search OR nome LIKE :search)";
            $params[':search'] = '%' . $filtros['search'] . '%';
        }
        
        // Filtro por período
        if(!empty($filtros['periodo'])) {
            switch($filtros['periodo']) {
                case 'hoje':
                    $where_conditions[] = "DATE(data_inscricao) = CURDATE()";
                    break;
                case 'semana':
                    $where_conditions[] = "data_inscricao >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'mes':
                    $where_conditions[] = "data_inscricao >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                    break;
                case 'ano':
                    $where_conditions[] = "data_inscricao >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                    break;
            }
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Query principal
        $query = "SELECT id, email, nome, categorias_interesse, ativo, confirmado, 
                         data_inscricao, data_confirmacao, data_atualizacao
                  FROM {$this->table} 
                  {$where_clause}
                  ORDER BY data_inscricao DESC 
                  LIMIT :limite OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind dos parâmetros
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $assinantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Query para contar total
        $count_query = "SELECT COUNT(*) as total FROM {$this->table} {$where_clause}";
        $count_stmt = $this->conn->prepare($count_query);
        
        foreach($params as $key => $value) {
            $count_stmt->bindValue($key, $value);
        }
        
        $count_stmt->execute();
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return [
            'assinantes' => $assinantes,
            'total' => (int)$total,
            'pagina' => $pagina,
            'limite' => $limite,
            'total_paginas' => ceil($total / $limite)
        ];
    }

    /**
     * Obter todos os assinantes ativos
     */
    public function obterTodosAtivos() {
        $query = "SELECT id, email, nome FROM {$this->table} 
                  WHERE ativo = 1 AND confirmado = 1 
                  ORDER BY nome, email";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obter assinantes por IDs
     */
    public function obterPorIds($ids) {
        if(empty($ids) || !is_array($ids)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $query = "SELECT id, email, nome FROM {$this->table} 
                  WHERE id IN ({$placeholders}) AND ativo = 1 AND confirmado = 1 
                  ORDER BY nome, email";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($ids);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Confirmar inscrição
     */
    public function confirmar($id) {
        $query = "UPDATE {$this->table} 
                  SET confirmado = 1, ativo = 1, data_confirmacao = NOW(), data_atualizacao = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Reativar inscrição
     */
    public function reativar($id) {
        $query = "UPDATE {$this->table} 
                  SET ativo = 1, data_atualizacao = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Desativar inscrição
     */
    public function desativar($id) {
        $query = "UPDATE {$this->table} 
                  SET ativo = 0, data_atualizacao = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Atualizar assinante
     */
    public function atualizar($id, $dados) {
        $campos = [];
        $params = [':id' => $id];
        
        if(isset($dados['nome'])) {
            $campos[] = "nome = :nome";
            $params[':nome'] = $dados['nome'];
        }
        
        if(isset($dados['ativo'])) {
            $campos[] = "ativo = :ativo";
            $params[':ativo'] = $dados['ativo'] ? 1 : 0;
        }
        
        if(isset($dados['categorias_interesse'])) {
            $campos[] = "categorias_interesse = :categorias_interesse";
            $params[':categorias_interesse'] = $dados['categorias_interesse'];
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
     * Deletar assinante
     */
    public function deletar($id) {
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Obter estatísticas
     */
    public function obterEstatisticas() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) as ativos,
                    SUM(CASE WHEN confirmado = 1 THEN 1 ELSE 0 END) as confirmados,
                    SUM(CASE WHEN DATE(data_inscricao) = CURDATE() THEN 1 ELSE 0 END) as hoje,
                    SUM(CASE WHEN data_inscricao >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as semana,
                    SUM(CASE WHEN data_inscricao >= DATE_SUB(NOW(), INTERVAL 1 MONTH) THEN 1 ELSE 0 END) as mes
                  FROM {$this->table}";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Limpar tokens expirados (mais de 24 horas)
     */
    public function limparTokensExpirados() {
        $query = "UPDATE {$this->table} 
                  SET token_confirmacao = NULL 
                  WHERE token_confirmacao IS NOT NULL 
                  AND confirmado = 0 
                  AND data_inscricao < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }

    /**
     * Remover inscrições não confirmadas antigas (mais de 7 dias)
     */
    public function limparInscricoesNaoConfirmadas() {
        $query = "DELETE FROM {$this->table} 
                  WHERE confirmado = 0 
                  AND data_inscricao < DATE_SUB(NOW(), INTERVAL 7 DAY)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }
}
?>