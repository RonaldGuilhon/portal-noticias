<?php
/**
 * Configuração do Banco de Dados
 * Portal de Notícias
 */

/**
 * Classe mock para simular PDO quando não há drivers disponíveis
 */
class MockPDO {
    private static $dataFile = __DIR__ . '/mock_data.json';
    private static $allData = null;
    
    private static function loadData() {
        if (self::$allData === null) {
            if (file_exists(self::$dataFile)) {
                self::$allData = json_decode(file_get_contents(self::$dataFile), true);
            } else {
                self::$allData = [
                    'usuarios' => [self::getDefaultUser()],
                    'categorias' => [],
                    'noticias' => []
                ];
                self::saveData();
            }
        }
        return self::$allData;
    }
    
    private static function getDefaultUser() {
        return [
            'id' => 1,
            'nome' => 'Administrador',
            'email' => 'admin@portalnoticias.com',
            'senha' => '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8', // Hash SHA1 de 'password'
            'tipo_usuario' => 'admin',
            'ativo' => 1,
            'email_verificado' => 1,
            'bio' => null,
            'foto_perfil' => null,
            'preferencias' => null
        ];
    }
    
    private static function saveData() {
        $dir = dirname(self::$dataFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents(self::$dataFile, json_encode(self::$allData, JSON_PRETTY_PRINT));
    }
    
    public function prepare($statement) {
        $data = self::loadData();
        
        // Retornar dados mock para queries específicas
        if (strpos($statement, 'usuarios') !== false && strpos($statement, 'SELECT') !== false) {
            return new MockPDOStatement($data['usuarios'], $statement);
        }
        if (strpos($statement, 'usuarios') !== false && strpos($statement, 'UPDATE') !== false) {
            return new MockPDOStatement([], $statement);
        }
        if (strpos($statement, 'categorias') !== false) {
            return new MockPDOStatement($data['categorias'] ?? [], $statement);
        }
        if (strpos($statement, 'noticias') !== false) {
            return new MockPDOStatement($data['noticias'] ?? [], $statement);
        }
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
    private $statement;
    private $params = [];
    private $filteredData = [];
    
    public function __construct($data = [], $statement = '') {
        $this->data = $data;
        $this->statement = $statement;
    }
    
    public function execute($params = []) {
        // Aplicar filtros baseados nos parâmetros
        $this->filteredData = $this->data;
        
        if (strpos($this->statement, 'SELECT') !== false && strpos($this->statement, 'usuarios') !== false) {
            // Filtrar por email se especificado
            if (isset($this->params[':email'])) {
                $email = $this->params[':email'];
                $this->filteredData = array_filter($this->data, function($user) use ($email) {
                    return $user['email'] === $email;
                });
            }
        }
        
        // Se é um UPDATE na tabela usuarios, simular a atualização
        if (strpos($this->statement, 'UPDATE') !== false && strpos($this->statement, 'usuarios') !== false) {
            // Simular atualização bem-sucedida
            $this->filteredData = [1]; // Simular que 1 linha foi afetada
        }
        return true;
    }
    
    public function fetch($fetch_style = null) {
        return array_shift($this->filteredData);
    }
    
    public function fetchAll($fetch_style = null) {
        return array_values($this->filteredData);
    }
    
    public function rowCount() {
        return count($this->filteredData);
    }
    
    public function bindParam($parameter, &$variable, $data_type = null) {
        $this->params[$parameter] = &$variable;
        return true;
    }
    
    public function bindValue($parameter, $value, $data_type = null) {
        $this->params[$parameter] = $value;
        return true;
    }
}

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    public $conn;
    private $use_sqlite = false; // Fallback para SQLite se MySQL não disponível
    private $force_mysql = false; // Forçar uso do MySQL

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
        $this->username = $_ENV['DB_USERNAME'] ?? 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? '';
        $this->charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
    }

    /**
     * Conecta ao banco de dados
     */
    public function getConnection() {
        $this->conn = null;

        try {
            // Forçar uso do MySQL se configurado
            if ($this->force_mysql) {
                // MySQL forçado - tentando conectar diretamente
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
                return $this->conn;
            }
            
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
            throw new Exception("Erro ao conectar com o banco de dados MySQL: " . $exception->getMessage());
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