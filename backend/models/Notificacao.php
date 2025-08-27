<?php
/**
 * Modelo de Notificação
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../config-unified.php';

class Notificacao {
    private $conn;
    private $table_name = "notificacoes";

    public $id;
    public $titulo;
    public $mensagem;
    public $tipo;
    public $usuario_id;
    public $lida;
    public $data_criacao;
    public $data_leitura;
    public $url;
    public $icone;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Criar nova notificação
     */
    public function criar() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET titulo=:titulo, mensagem=:mensagem, tipo=:tipo, 
                      usuario_id=:usuario_id, url=:url, icone=:icone";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->titulo = sanitizeInput($this->titulo);
        $this->mensagem = sanitizeInput($this->mensagem);
        $this->tipo = sanitizeInput($this->tipo);
        $this->url = sanitizeInput($this->url);
        $this->icone = sanitizeInput($this->icone);

        // Bind dos parâmetros
        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":mensagem", $this->mensagem);
        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":usuario_id", $this->usuario_id);
        $stmt->bindParam(":url", $this->url);
        $stmt->bindParam(":icone", $this->icone);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    /**
     * Listar notificações do usuário
     */
    public function listarPorUsuario($usuario_id, $limite = 10, $offset = 0) {
        $query = "SELECT id, titulo, mensagem, tipo, lida, data_criacao, url, icone
                  FROM " . $this->table_name . " 
                  WHERE usuario_id = :usuario_id 
                  ORDER BY data_criacao DESC 
                  LIMIT :limite OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Listar notificações para administradores
     */
    public function listarParaAdmin($limite = 10, $offset = 0) {
        $query = "SELECT n.id, n.titulo, n.mensagem, n.tipo, n.lida, n.data_criacao, n.url, n.icone,
                         u.nome as usuario_nome
                  FROM " . $this->table_name . " n
                  LEFT JOIN usuarios u ON n.usuario_id = u.id
                  WHERE n.usuario_id IS NULL OR u.tipo_usuario IN ('admin', 'editor')
                  ORDER BY n.data_criacao DESC 
                  LIMIT :limite OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Marcar notificação como lida
     */
    public function marcarComoLida($id, $usuario_id = null) {
        $query = "UPDATE " . $this->table_name . " 
                  SET lida = 1, data_leitura = NOW() 
                  WHERE id = :id";
        
        if ($usuario_id) {
            $query .= " AND usuario_id = :usuario_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        
        if ($usuario_id) {
            $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        }

        return $stmt->execute();
    }

    /**
     * Contar notificações não lidas
     */
    public function contarNaoLidas($usuario_id = null) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE lida = 0";
        
        if ($usuario_id) {
            $query .= " AND usuario_id = :usuario_id";
        } else {
            $query .= " AND (usuario_id IS NULL OR usuario_id IN (SELECT id FROM usuarios WHERE tipo_usuario IN ('admin', 'editor')))";
        }

        $stmt = $this->conn->prepare($query);
        
        if ($usuario_id) {
            $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }

    /**
     * Deletar notificação
     */
    public function deletar($id, $usuario_id = null) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        if ($usuario_id) {
            $query .= " AND usuario_id = :usuario_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        
        if ($usuario_id) {
            $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        }

        return $stmt->execute();
    }

    /**
     * Criar notificação para administradores
     */
    public static function criarParaAdmin($db, $titulo, $mensagem, $tipo = 'sistema', $url = null, $icone = null) {
        $notificacao = new self($db);
        $notificacao->titulo = $titulo;
        $notificacao->mensagem = $mensagem;
        $notificacao->tipo = $tipo;
        $notificacao->usuario_id = null; // Para todos os admins
        $notificacao->url = $url;
        $notificacao->icone = $icone;
        
        return $notificacao->criar();
    }

    /**
     * Criar notificação para usuário específico
     */
    public static function criarParaUsuario($db, $usuario_id, $titulo, $mensagem, $tipo = 'sistema', $url = null, $icone = null) {
        $notificacao = new self($db);
        $notificacao->titulo = $titulo;
        $notificacao->mensagem = $mensagem;
        $notificacao->tipo = $tipo;
        $notificacao->usuario_id = $usuario_id;
        $notificacao->url = $url;
        $notificacao->icone = $icone;
        
        return $notificacao->criar();
    }
}
?>