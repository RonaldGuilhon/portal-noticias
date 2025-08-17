<?php
/**
 * Modelo Configuracao
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/config.php';

class Configuracao {
    private $conn;
    private $table = 'configuracoes_sistema';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Criar nova configuração
     */
    public function criar($dados) {
        $query = "INSERT INTO {$this->table} 
                  (chave, valor, descricao, tipo, categoria, publica, editavel, usuario_id, data_criacao) 
                  VALUES (:chave, :valor, :descricao, :tipo, :categoria, :publica, :editavel, :usuario_id, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':chave', $dados['chave']);
        $stmt->bindParam(':valor', $dados['valor']);
        $stmt->bindParam(':descricao', $dados['descricao']);
        $stmt->bindParam(':tipo', $dados['tipo']);
        $stmt->bindParam(':categoria', $dados['categoria']);
        $stmt->bindParam(':publica', $dados['publica'], PDO::PARAM_BOOL);
        $stmt->bindParam(':editavel', $dados['editavel'], PDO::PARAM_BOOL);
        $stmt->bindParam(':usuario_id', $dados['usuario_id'], PDO::PARAM_INT);
        
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    /**
     * Obter por chave
     */
    public function obterPorChave($chave) {
        $query = "SELECT c.*, u.nome as autor_nome, ua.nome as atualizador_nome 
                  FROM {$this->table} c 
                  LEFT JOIN usuarios u ON c.usuario_id = u.id 
                  LEFT JOIN usuarios ua ON c.usuario_atualizacao_id = ua.id 
                  WHERE c.chave = :chave";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':chave', $chave);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($resultado) {
            // Converter valor baseado no tipo
            $resultado['valor'] = $this->converterValor($resultado['valor'], $resultado['tipo']);
        }
        
        return $resultado;
    }

    /**
     * Listar configurações
     */
    public function listar($categoria = '', $search = '') {
        $where_conditions = [];
        $params = [];
        
        if(!empty($categoria)) {
            $where_conditions[] = "c.categoria = :categoria";
            $params[':categoria'] = $categoria;
        }
        
        if(!empty($search)) {
            $where_conditions[] = "(c.chave LIKE :search OR c.descricao LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "SELECT c.*, u.nome as autor_nome, ua.nome as atualizador_nome 
                  FROM {$this->table} c 
                  LEFT JOIN usuarios u ON c.usuario_id = u.id 
                  LEFT JOIN usuarios ua ON c.usuario_atualizacao_id = ua.id 
                  {$where_clause}
                  ORDER BY c.categoria, c.chave";
        
        $stmt = $this->conn->prepare($query);
        
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $configuracoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Converter valores baseado no tipo
        foreach($configuracoes as &$config) {
            $config['valor'] = $this->converterValor($config['valor'], $config['tipo']);
        }
        
        return $configuracoes;
    }

    /**
     * Obter configurações públicas
     */
    public function obterPublicas() {
        $query = "SELECT chave, valor, tipo, descricao 
                  FROM {$this->table} 
                  WHERE publica = 1 
                  ORDER BY categoria, chave";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $configuracoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $resultado = [];
        foreach($configuracoes as $config) {
            $resultado[$config['chave']] = $this->converterValor($config['valor'], $config['tipo']);
        }
        
        return $resultado;
    }

    /**
     * Obter configurações do sistema
     */
    public function obterSistema() {
        $query = "SELECT chave, valor, tipo, descricao, categoria, editavel 
                  FROM {$this->table} 
                  WHERE categoria IN ('sistema', 'email', 'upload', 'cache', 'seguranca') 
                  ORDER BY categoria, chave";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $configuracoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $resultado = [];
        foreach($configuracoes as $config) {
            if(!isset($resultado[$config['categoria']])) {
                $resultado[$config['categoria']] = [];
            }
            $resultado[$config['categoria']][$config['chave']] = [
                'valor' => $this->converterValor($config['valor'], $config['tipo']),
                'tipo' => $config['tipo'],
                'descricao' => $config['descricao'],
                'editavel' => (bool)$config['editavel']
            ];
        }
        
        return $resultado;
    }

    /**
     * Atualizar configuração
     */
    public function atualizar($chave, $dados) {
        $campos = [];
        $params = [':chave' => $chave];
        
        if(isset($dados['valor'])) {
            $campos[] = "valor = :valor";
            $params[':valor'] = $dados['valor'];
        }
        
        if(isset($dados['descricao'])) {
            $campos[] = "descricao = :descricao";
            $params[':descricao'] = $dados['descricao'];
        }
        
        if(isset($dados['tipo'])) {
            $campos[] = "tipo = :tipo";
            $params[':tipo'] = $dados['tipo'];
        }
        
        if(isset($dados['categoria'])) {
            $campos[] = "categoria = :categoria";
            $params[':categoria'] = $dados['categoria'];
        }
        
        if(isset($dados['publica'])) {
            $campos[] = "publica = :publica";
            $params[':publica'] = $dados['publica'] ? 1 : 0;
        }
        
        if(isset($dados['editavel'])) {
            $campos[] = "editavel = :editavel";
            $params[':editavel'] = $dados['editavel'] ? 1 : 0;
        }
        
        if(isset($dados['usuario_atualizacao_id'])) {
            $campos[] = "usuario_atualizacao_id = :usuario_atualizacao_id";
            $params[':usuario_atualizacao_id'] = $dados['usuario_atualizacao_id'];
        }
        
        if(empty($campos)) {
            return false;
        }
        
        $campos[] = "data_atualizacao = NOW()";
        
        $query = "UPDATE {$this->table} SET " . implode(', ', $campos) . " WHERE chave = :chave";
        
        $stmt = $this->conn->prepare($query);
        
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }

    /**
     * Deletar configuração
     */
    public function deletar($chave) {
        $query = "DELETE FROM {$this->table} WHERE chave = :chave";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':chave', $chave);
        
        return $stmt->execute();
    }

    /**
     * Obter valor de configuração específica
     */
    public function obterValor($chave, $valor_padrao = null) {
        $query = "SELECT valor, tipo FROM {$this->table} WHERE chave = :chave";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':chave', $chave);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($resultado) {
            return $this->converterValor($resultado['valor'], $resultado['tipo']);
        }
        
        return $valor_padrao;
    }

    /**
     * Definir valor de configuração
     */
    public function definirValor($chave, $valor, $tipo = 'string') {
        // Verificar se já existe
        $existente = $this->obterPorChave($chave);
        
        if($existente) {
            return $this->atualizar($chave, ['valor' => $valor]);
        } else {
            $dados = [
                'chave' => $chave,
                'valor' => $valor,
                'tipo' => $tipo,
                'categoria' => 'geral',
                'publica' => false,
                'editavel' => true,
                'usuario_id' => 1 // Sistema
            ];
            return $this->criar($dados);
        }
    }

    /**
     * Obter categorias disponíveis
     */
    public function obterCategorias() {
        $query = "SELECT DISTINCT categoria, COUNT(*) as total 
                  FROM {$this->table} 
                  GROUP BY categoria 
                  ORDER BY categoria";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Resetar configurações para valores padrão
     */
    public function resetarPadrao($categoria = '') {
        $configuracoes_padrao = $this->obterConfiguracoesPadrao();
        
        $sucessos = 0;
        foreach($configuracoes_padrao as $config) {
            if(!empty($categoria) && $config['categoria'] !== $categoria) {
                continue;
            }
            
            $existente = $this->obterPorChave($config['chave']);
            if($existente) {
                if($this->atualizar($config['chave'], ['valor' => $config['valor']])) {
                    $sucessos++;
                }
            } else {
                if($this->criar($config)) {
                    $sucessos++;
                }
            }
        }
        
        return $sucessos > 0;
    }

    /**
     * Converter valor baseado no tipo
     */
    private function converterValor($valor, $tipo) {
        switch($tipo) {
            case 'boolean':
                return (bool)$valor;
            case 'integer':
                return (int)$valor;
            case 'float':
                return (float)$valor;
            case 'json':
                return json_decode($valor, true);
            case 'array':
                return is_string($valor) ? explode(',', $valor) : $valor;
            default:
                return $valor;
        }
    }

    /**
     * Obter configurações padrão do sistema
     */
    private function obterConfiguracoesPadrao() {
        return [
            // Configurações gerais
            [
                'chave' => 'site_nome',
                'valor' => 'Portal de Notícias',
                'descricao' => 'Nome do site',
                'tipo' => 'string',
                'categoria' => 'geral',
                'publica' => true,
                'editavel' => true,
                'usuario_id' => 1
            ],
            [
                'chave' => 'site_descricao',
                'valor' => 'Seu portal de notícias atualizado',
                'descricao' => 'Descrição do site',
                'tipo' => 'string',
                'categoria' => 'geral',
                'publica' => true,
                'editavel' => true,
                'usuario_id' => 1
            ],
            [
                'chave' => 'site_palavras_chave',
                'valor' => 'notícias, portal, informação, atualidades',
                'descricao' => 'Palavras-chave do site',
                'tipo' => 'string',
                'categoria' => 'geral',
                'publica' => true,
                'editavel' => true,
                'usuario_id' => 1
            ],
            [
                'chave' => 'site_email_contato',
                'valor' => 'contato@portalnoticias.com',
                'descricao' => 'Email de contato do site',
                'tipo' => 'string',
                'categoria' => 'geral',
                'publica' => true,
                'editavel' => true,
                'usuario_id' => 1
            ],
            
            // Configurações de exibição
            [
                'chave' => 'noticias_por_pagina',
                'valor' => '12',
                'descricao' => 'Número de notícias por página',
                'tipo' => 'integer',
                'categoria' => 'exibicao',
                'publica' => true,
                'editavel' => true,
                'usuario_id' => 1
            ],
            [
                'chave' => 'comentarios_habilitados',
                'valor' => '1',
                'descricao' => 'Habilitar comentários nas notícias',
                'tipo' => 'boolean',
                'categoria' => 'exibicao',
                'publica' => true,
                'editavel' => true,
                'usuario_id' => 1
            ],
            [
                'chave' => 'moderacao_comentarios',
                'valor' => '1',
                'descricao' => 'Moderar comentários antes de publicar',
                'tipo' => 'boolean',
                'categoria' => 'exibicao',
                'publica' => false,
                'editavel' => true,
                'usuario_id' => 1
            ],
            
            // Configurações de sistema
            [
                'chave' => 'manutencao_ativa',
                'valor' => '0',
                'descricao' => 'Site em manutenção',
                'tipo' => 'boolean',
                'categoria' => 'sistema',
                'publica' => false,
                'editavel' => true,
                'usuario_id' => 1
            ],
            [
                'chave' => 'cache_habilitado',
                'valor' => '1',
                'descricao' => 'Habilitar cache do sistema',
                'tipo' => 'boolean',
                'categoria' => 'sistema',
                'publica' => false,
                'editavel' => true,
                'usuario_id' => 1
            ],
            [
                'chave' => 'log_nivel',
                'valor' => 'info',
                'descricao' => 'Nível de log (debug, info, warning, error)',
                'tipo' => 'string',
                'categoria' => 'sistema',
                'publica' => false,
                'editavel' => true,
                'usuario_id' => 1
            ],
            
            // Configurações de segurança
            [
                'chave' => 'tentativas_login_max',
                'valor' => '5',
                'descricao' => 'Máximo de tentativas de login',
                'tipo' => 'integer',
                'categoria' => 'seguranca',
                'publica' => false,
                'editavel' => true,
                'usuario_id' => 1
            ],
            [
                'chave' => 'sessao_duracao',
                'valor' => '3600',
                'descricao' => 'Duração da sessão em segundos',
                'tipo' => 'integer',
                'categoria' => 'seguranca',
                'publica' => false,
                'editavel' => true,
                'usuario_id' => 1
            ],
            
            // Configurações de upload
            [
                'chave' => 'upload_tamanho_max',
                'valor' => '5242880',
                'descricao' => 'Tamanho máximo de upload em bytes (5MB)',
                'tipo' => 'integer',
                'categoria' => 'upload',
                'publica' => false,
                'editavel' => true,
                'usuario_id' => 1
            ],
            [
                'chave' => 'upload_tipos_permitidos',
                'valor' => 'jpg,jpeg,png,gif,webp,pdf,doc,docx',
                'descricao' => 'Tipos de arquivo permitidos para upload',
                'tipo' => 'array',
                'categoria' => 'upload',
                'publica' => false,
                'editavel' => true,
                'usuario_id' => 1
            ]
        ];
    }

    /**
     * Inicializar configurações padrão
     */
    public function inicializarPadrao() {
        $configuracoes_padrao = $this->obterConfiguracoesPadrao();
        
        foreach($configuracoes_padrao as $config) {
            $existente = $this->obterPorChave($config['chave']);
            if(!$existente) {
                $this->criar($config);
            }
        }
        
        return true;
    }
}
?>