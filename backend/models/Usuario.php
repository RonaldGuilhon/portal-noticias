<?php
/**
 * Modelo de Usuário
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

class Usuario {
    private $conn;
    private $table_name = "usuarios";

    public $id;
    public $nome;
    public $email;
    public $senha;
    public $foto_perfil;
    public $bio;
    public $tipo_usuario;
    public $ativo;
    public $email_verificado;
    public $token_verificacao;
    public $token_recuperacao;
    public $data_criacao;
    public $data_atualizacao;
    public $ultimo_login;
    public $preferencias;
    public $provider;
    public $provider_id;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Criar novo usuário
     */
    public function criar() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nome=:nome, email=:email, senha=:senha, tipo_usuario=:tipo_usuario, 
                      token_verificacao=:token_verificacao, provider=:provider, provider_id=:provider_id";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->nome = sanitizeInput($this->nome);
        $this->email = sanitizeInput($this->email);
        $this->senha = hashPassword($this->senha);
        $this->tipo_usuario = $this->tipo_usuario ?: 'leitor';
        $this->token_verificacao = generateSecureToken();
        $this->provider = $this->provider ?: 'local';

        // Bind dos parâmetros
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":senha", $this->senha);
        $stmt->bindParam(":tipo_usuario", $this->tipo_usuario);
        $stmt->bindParam(":token_verificacao", $this->token_verificacao);
        $stmt->bindParam(":provider", $this->provider);
        $stmt->bindParam(":provider_id", $this->provider_id);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    /**
     * Fazer login
     */
    public function login($email, $senha) {
        $query = "SELECT id, nome, email, senha, tipo_usuario, ativo, email_verificado 
                  FROM " . $this->table_name . " 
                  WHERE email = :email AND ativo = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(verifyPassword($senha, $row['senha'])) {
                $this->id = $row['id'];
                $this->nome = $row['nome'];
                $this->email = $row['email'];
                $this->tipo_usuario = $row['tipo_usuario'];
                $this->ativo = $row['ativo'];
                $this->email_verificado = $row['email_verificado'];
                
                // Atualizar último login
                $this->atualizarUltimoLogin();
                
                return true;
            }
        }

        return false;
    }

    /**
     * Login social (Google/Facebook)
     */
    public function loginSocial($email, $nome, $provider, $provider_id) {
        // Verificar se usuário já existe
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE email = :email OR (provider = :provider AND provider_id = :provider_id)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":provider", $provider);
        $stmt->bindParam(":provider_id", $provider_id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            // Usuário existe, fazer login
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->nome = $row['nome'];
            $this->email = $row['email'];
            $this->tipo_usuario = $row['tipo_usuario'];
            $this->atualizarUltimoLogin();
            return true;
        } else {
            // Criar novo usuário
            $this->nome = $nome;
            $this->email = $email;
            $this->senha = generateSecureToken(); // Senha aleatória
            $this->provider = $provider;
            $this->provider_id = $provider_id;
            $this->email_verificado = 1; // Email já verificado pelo provider
            
            return $this->criar();
        }
    }

    /**
     * Verificar se email existe
     */
    public function emailExiste($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Buscar usuário por ID
     */
    public function buscarPorId($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->nome = $row['nome'];
            $this->email = $row['email'];
            $this->foto_perfil = $row['foto_perfil'];
            $this->bio = $row['bio'];
            $this->tipo_usuario = $row['tipo_usuario'];
            $this->ativo = $row['ativo'];
            $this->email_verificado = $row['email_verificado'];
            $this->data_criacao = $row['data_criacao'];
            $this->preferencias = $row['preferencias'];
            return true;
        }
        
        return false;
    }

    /**
     * Atualizar perfil
     */
    public function atualizarPerfil() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nome=:nome, bio=:bio, foto_perfil=:foto_perfil, preferencias=:preferencias 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->nome = sanitizeInput($this->nome);
        $this->bio = sanitizeInput($this->bio);

        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":bio", $this->bio);
        $stmt->bindParam(":foto_perfil", $this->foto_perfil);
        $stmt->bindParam(":preferencias", $this->preferencias);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    /**
     * Alterar senha
     */
    public function alterarSenha($senha_atual, $nova_senha) {
        // Verificar senha atual
        $query = "SELECT senha FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!verifyPassword($senha_atual, $row['senha'])) {
            return false;
        }

        // Atualizar senha
        $query = "UPDATE " . $this->table_name . " SET senha=:senha WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        
        $nova_senha_hash = hashPassword($nova_senha);
        $stmt->bindParam(":senha", $nova_senha_hash);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    /**
     * Solicitar recuperação de senha
     */
    public function solicitarRecuperacaoSenha($email) {
        $query = "UPDATE " . $this->table_name . " 
                  SET token_recuperacao=:token 
                  WHERE email=:email AND ativo=1";

        $stmt = $this->conn->prepare($query);
        
        $token = generateSecureToken();
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":email", $email);

        if($stmt->execute() && $stmt->rowCount() > 0) {
            return $token;
        }
        
        return false;
    }

    /**
     * Recuperar senha com token
     */
    public function recuperarSenha($token, $nova_senha) {
        $query = "UPDATE " . $this->table_name . " 
                  SET senha=:senha, token_recuperacao=NULL 
                  WHERE token_recuperacao=:token";

        $stmt = $this->conn->prepare($query);
        
        $senha_hash = hashPassword($nova_senha);
        $stmt->bindParam(":senha", $senha_hash);
        $stmt->bindParam(":token", $token);

        return $stmt->execute() && $stmt->rowCount() > 0;
    }

    /**
     * Verificar email com token
     */
    public function verificarEmail($token) {
        $query = "UPDATE " . $this->table_name . " 
                  SET email_verificado=1, token_verificacao=NULL 
                  WHERE token_verificacao=:token";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);

        return $stmt->execute() && $stmt->rowCount() > 0;
    }

    /**
     * Listar usuários (admin)
     */
    public function listar($page = 1, $limit = 20, $search = '') {
        $offset = ($page - 1) * $limit;
        
        $where = "";
        if(!empty($search)) {
            $where = "WHERE nome LIKE :search OR email LIKE :search";
        }
        
        $query = "SELECT id, nome, email, tipo_usuario, ativo, email_verificado, data_criacao, ultimo_login 
                  FROM " . $this->table_name . " 
                  {$where}
                  ORDER BY data_criacao DESC 
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        
        if(!empty($search)) {
            $search_param = "%{$search}%";
            $stmt->bindParam(":search", $search_param);
        }
        
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Contar usuários
     */
    public function contar($search = '') {
        $where = "";
        if(!empty($search)) {
            $where = "WHERE nome LIKE :search OR email LIKE :search";
        }
        
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " {$where}";
        $stmt = $this->conn->prepare($query);
        
        if(!empty($search)) {
            $search_param = "%{$search}%";
            $stmt->bindParam(":search", $search_param);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }

    /**
     * Ativar/Desativar usuário
     */
    public function alterarStatus($id, $ativo) {
        $query = "UPDATE " . $this->table_name . " SET ativo=:ativo WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":ativo", $ativo, PDO::PARAM_BOOL);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }

    /**
     * Atualizar último login
     */
    private function atualizarUltimoLogin() {
        $query = "UPDATE " . $this->table_name . " SET ultimo_login=NOW() WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
    }

    /**
     * Validar dados do usuário
     */
    public function validar() {
        $erros = [];

        if(empty($this->nome)) {
            $erros[] = "Nome é obrigatório";
        } elseif(strlen($this->nome) < 2) {
            $erros[] = "Nome deve ter pelo menos 2 caracteres";
        }

        if(empty($this->email)) {
            $erros[] = "Email é obrigatório";
        } elseif(!isValidEmail($this->email)) {
            $erros[] = "Email inválido";
        }

        if(!empty($this->senha) && strlen($this->senha) < PASSWORD_MIN_LENGTH) {
            $erros[] = "Senha deve ter pelo menos " . PASSWORD_MIN_LENGTH . " caracteres";
        }

        return $erros;
    }

    /**
     * Obter estatísticas de usuários
     */
    public function obterEstatisticas() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) as ativos,
                    SUM(CASE WHEN tipo_usuario = 'admin' THEN 1 ELSE 0 END) as admins,
                    SUM(CASE WHEN tipo_usuario = 'editor' THEN 1 ELSE 0 END) as editores,
                    SUM(CASE WHEN tipo_usuario = 'leitor' THEN 1 ELSE 0 END) as leitores,
                    SUM(CASE WHEN email_verificado = 1 THEN 1 ELSE 0 END) as verificados
                  FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>