<?php
/**
 * Configuração do Banco de Dados
 * Portal de Notícias
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'portal_noticias';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    public $conn;

    /**
     * Conecta ao banco de dados
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $exception) {
            error_log("Erro de conexão: " . $exception->getMessage());
            throw new Exception("Erro ao conectar com o banco de dados");
        }

        return $this->conn;
    }

    /**
     * Fecha a conexão
     */
    public function closeConnection() {
        $this->conn = null;
    }

    /**
     * Inicia uma transação
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    /**
     * Confirma uma transação
     */
    public function commit() {
        return $this->conn->commit();
    }

    /**
     * Desfaz uma transação
     */
    public function rollback() {
        return $this->conn->rollback();
    }

    /**
     * Executa uma query preparada
     */
    public function executeQuery($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $exception) {
            error_log("Erro na query: " . $exception->getMessage());
            throw new Exception("Erro ao executar consulta");
        }
    }

    /**
     * Retorna o último ID inserido
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
}
?>