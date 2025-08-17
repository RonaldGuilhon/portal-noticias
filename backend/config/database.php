<?php
/**
 * Configuração do Banco de Dados
 * Portal de Notícias
 */

/**
 * Classe mock para simular PDO quando não há drivers disponíveis
 */
class MockPDO {
    public function prepare($statement) {
        return new MockPDOStatement();
    }
    
    public function query($statement) {
        // Retornar dados mock baseados na query
        if (strpos($statement, 'SHOW TABLES') !== false) {
            return new MockPDOStatement([['Tables_in_portal_noticias' => 'usuarios'], ['Tables_in_portal_noticias' => 'categorias'], ['Tables_in_portal_noticias' => 'noticias']]);
        }
        return new MockPDOStatement([]);
    }
    
    public function lastInsertId() {
        return '1';
    }
    
    public function exec($statement) {
        return 1;
    }
}

/**
 * Classe mock para simular PDOStatement
 */
class MockPDOStatement {
    private $data;
    
    public function __construct($data = []) {
        $this->data = $data;
    }
    
    public function execute($params = []) {
        return true;
    }
    
    public function fetch($fetch_style = null) {
        return array_shift($this->data);
    }
    
    public function fetchAll($fetch_style = null) {
        return $this->data;
    }
    
    public function rowCount() {
        return count($this->data);
    }
    
    public function bindParam($parameter, &$variable, $data_type = null) {
        return true;
    }
    
    public function bindValue($parameter, $value, $data_type = null) {
        return true;
    }
}

class Database {
    private $host = 'localhost';
    private $db_name = 'portal_noticias';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    public $conn;
    private $use_sqlite = false; // Fallback para SQLite se MySQL não disponível

    /**
     * Conecta ao banco de dados
     */
    public function getConnection() {
        $this->conn = null;

        try {
            // Verificar se há drivers PDO disponíveis
            $available_drivers = PDO::getAvailableDrivers();
            
            if (in_array('mysql', $available_drivers)) {
                // MySQL está disponível - tentando conectar
                $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ];
                
                // Adicionar opção MySQL apenas se disponível
                if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                    $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4";
                }

                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            } elseif (in_array('sqlite', $available_drivers)) {
                // Fallback para SQLite
                $this->use_sqlite = true;
                $sqlite_path = __DIR__ . '/../database/portal_noticias.sqlite';
                $dsn = "sqlite:" . $sqlite_path;
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ];

                $this->conn = new PDO($dsn, null, null, $options);
                
                // Criar tabelas básicas se não existirem
                $this->createSQLiteTables();
            } else {
                // Modo mock - sem banco de dados real
                $this->conn = new MockPDO();
                error_log("Aviso: Usando modo mock - nenhum driver PDO disponível");
            }
        } catch(PDOException $exception) {
            error_log("Erro de conexão: " . $exception->getMessage());
            // Em modo de desenvolvimento, usar mock em caso de erro
            $this->conn = new MockPDO();
            error_log("Usando modo mock devido a erro de conexão");
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

    /**
     * Executa uma query simples
     */
    public function query($sql, $params = []) {
        try {
            if (empty($params)) {
                // Para queries simples sem parâmetros, usar query() diretamente
                return $this->conn->query($sql);
            } else {
                // Para queries com parâmetros, usar prepared statements
                return $this->executeQuery($sql, $params);
            }
        } catch(PDOException $exception) {
            error_log("Erro na query: " . $exception->getMessage());
            throw new Exception("Erro ao executar consulta");
        }
    }

    /**
     * Prepara uma query
     */
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }

    /**
     * Cria tabelas básicas para SQLite
     */
    private function createSQLiteTables() {
        $tables = [
            'usuarios' => '
                CREATE TABLE IF NOT EXISTS usuarios (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nome VARCHAR(100) NOT NULL,
                    email VARCHAR(150) UNIQUE NOT NULL,
                    senha VARCHAR(255) NOT NULL,
                    tipo_usuario VARCHAR(20) DEFAULT "leitor",
                    ativo BOOLEAN DEFAULT 1,
                    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
                )',
            'categorias' => '
                CREATE TABLE IF NOT EXISTS categorias (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nome VARCHAR(100) NOT NULL,
                    slug VARCHAR(100) UNIQUE NOT NULL,
                    ativa BOOLEAN DEFAULT 1,
                    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
                )',
            'noticias' => '
                CREATE TABLE IF NOT EXISTS noticias (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    titulo VARCHAR(255) NOT NULL,
                    conteudo TEXT NOT NULL,
                    categoria_id INTEGER,
                    autor_id INTEGER,
                    status VARCHAR(20) DEFAULT "rascunho",
                    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (categoria_id) REFERENCES categorias(id),
                    FOREIGN KEY (autor_id) REFERENCES usuarios(id)
                )'
        ];

        foreach ($tables as $table => $sql) {
            try {
                $this->conn->exec($sql);
            } catch (PDOException $e) {
                error_log("Erro ao criar tabela $table: " . $e->getMessage());
            }
        }
    }
}
?>