<?php
/**
 * Configuração do Banco de Dados
 * Portal de Notícias
 */

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    public $conn;

    public function __construct() {
        // Carregar configurações do .env
        $this->loadEnvConfig();
        // Estabelecer conexão
        $this->getConnection();
    }
    
    private function loadEnvConfig() {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }
        
        // Definir configurações do banco
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? 'portal_noticias';
        $this->username = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASS'] ?? '';
        $this->charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
    }

    /**
     * Conecta ao banco de dados MySQL
     */
    public function getConnection() {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            // Verificar se o driver MySQL está disponível
            $available_drivers = PDO::getAvailableDrivers();
            
            if (!in_array('mysql', $available_drivers)) {
                throw new Exception('Driver PDO MySQL não está disponível. Instale a extensão pdo_mysql.');
            }
            
            // Conectar ao MySQL
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            // Adicionar opção MySQL se disponível
            if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4";
            }

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            $error_msg = "Erro ao conectar com o banco de dados MySQL: " . $exception->getMessage();
            error_log($error_msg);
            throw new Exception($error_msg);
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
            throw $exception;
        }
    }

    /**
     * Verifica se a conexão está ativa
     */
    public function isConnected() {
        try {
            $this->conn->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Obter informações da conexão
     */
    public function getConnectionInfo() {
        return [
            'host' => $this->host,
            'database' => $this->db_name,
            'username' => $this->username,
            'charset' => $this->charset,
            'connected' => $this->isConnected()
        ];
    }
}

/**
 * Função auxiliar para obter conexão com o banco
 */
function getDatabase() {
    static $database = null;
    if ($database === null) {
        $database = new Database();
    }
    return $database;
}

/**
 * Função auxiliar para obter conexão PDO
 */
function getConnection() {
    return getDatabase()->getConnection();
}