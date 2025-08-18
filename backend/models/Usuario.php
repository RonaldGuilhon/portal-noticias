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
    public $data_nascimento;
    public $genero;
    public $telefone;
    public $cidade;

    
    // Configurações de exibição
    public $show_images;
    public $auto_play_videos;
    public $dark_mode;
    
    // Configurações de notificação
    public $email_newsletter;
    public $email_breaking;
    public $email_comments;
    public $email_marketing;
    public $push_breaking;
    public $push_interests;
    public $push_comments;
    public $notification_frequency;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Criar novo usuário
     */
    public function criar() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nome=:nome, email=:email, senha=:senha, tipo_usuario=:tipo_usuario, 
                      token_verificacao=:token_verificacao, provider=:provider, provider_id=:provider_id,
                      data_nascimento=:data_nascimento, genero=:genero, preferencias=:preferencias";

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
        $stmt->bindParam(":data_nascimento", $this->data_nascimento);
        $stmt->bindParam(":genero", $this->genero);

        $stmt->bindParam(":preferencias", $this->preferencias);

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
     * Buscar usuário por ID (preenche propriedades do objeto)
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
            $this->data_nascimento = $row['data_nascimento'];
            $this->genero = $row['genero'];
            $this->telefone = $row['telefone'];
            $this->cidade = $row['cidade'];
            
            // Configurações de exibição
            $this->show_images = $row['show_images'];
            $this->auto_play_videos = $row['auto_play_videos'];
            $this->dark_mode = $row['dark_mode'];
            
            // Configurações de notificação

            $this->email_newsletter = $row['email_newsletter'];
            $this->email_breaking = $row['email_breaking'];
            $this->email_comments = $row['email_comments'];
            $this->email_marketing = $row['email_marketing'];
            $this->push_breaking = $row['push_breaking'];
            $this->push_interests = $row['push_interests'];
            $this->push_comments = $row['push_comments'];
            $this->notification_frequency = $row['notification_frequency'];
            return true;
        }
        
        return false;
    }

    /**
     * Obter usuário por ID (retorna array associativo)
     */
    public function obterPorId($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return null;
    }

    /**
     * Atualizar perfil
     */
    public function atualizarPerfil() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nome=:nome, bio=:bio, foto_perfil=:foto_perfil, preferencias=:preferencias,
                      data_nascimento=:data_nascimento, genero=:genero, telefone=:telefone, cidade=:cidade,
                      show_images=:show_images, auto_play_videos=:auto_play_videos, dark_mode=:dark_mode,
                      email_newsletter=:email_newsletter, email_breaking=:email_breaking, 
                      email_comments=:email_comments, email_marketing=:email_marketing,
                      push_breaking=:push_breaking, push_interests=:push_interests, 
                      push_comments=:push_comments, notification_frequency=:notification_frequency
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->nome = sanitizeInput($this->nome);
        $this->bio = sanitizeInput($this->bio);
        $this->telefone = sanitizeInput($this->telefone);
        $this->cidade = sanitizeInput($this->cidade);

        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":bio", $this->bio);
        $stmt->bindParam(":foto_perfil", $this->foto_perfil);
        $stmt->bindParam(":preferencias", $this->preferencias);
        $stmt->bindParam(":data_nascimento", $this->data_nascimento);
        $stmt->bindParam(":genero", $this->genero);
        $stmt->bindParam(":telefone", $this->telefone);
        $stmt->bindParam(":cidade", $this->cidade);
        
        // Configurações de exibição
        $stmt->bindParam(":show_images", $this->show_images);
        $stmt->bindParam(":auto_play_videos", $this->auto_play_videos);
        $stmt->bindParam(":dark_mode", $this->dark_mode);
        
        // Notificações por email

        $stmt->bindParam(":email_newsletter", $this->email_newsletter);
        $stmt->bindParam(":email_breaking", $this->email_breaking);
        $stmt->bindParam(":email_comments", $this->email_comments);
        $stmt->bindParam(":email_marketing", $this->email_marketing);
        
        // Notificações push
        $stmt->bindParam(":push_breaking", $this->push_breaking);
        $stmt->bindParam(":push_interests", $this->push_interests);
        $stmt->bindParam(":push_comments", $this->push_comments);
        
        // Frequência de notificações
        $stmt->bindParam(":notification_frequency", $this->notification_frequency);
        
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
    
    /**
     * Criar usuário via login social
     */
    public function criarUsuarioSocial($dados) {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nome=:nome, email=:email, senha=:senha, foto_perfil=:foto_perfil,
                      provider=:provider, provider_id=:provider_id, email_verificado=1, ativo=1";
        
        $stmt = $this->conn->prepare($query);
        
        $senha_aleatoria = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        
        $stmt->bindParam(":nome", $dados['nome']);
        $stmt->bindParam(":email", $dados['email']);
        $stmt->bindParam(":senha", $senha_aleatoria);
        $stmt->bindParam(":foto_perfil", $dados['avatar']);
        $stmt->bindParam(":provider", $dados['provider']);
        $stmt->bindParam(":provider_id", $dados['provider_id']);
        
        if($stmt->execute()) {
            $user_id = $this->conn->lastInsertId();
            
            // Salvar access token na tabela de conexões sociais
            $this->salvarConexaoSocial($user_id, $dados['provider'], $dados['provider_id'], $dados['access_token']);
            
            return $user_id;
        }
        
        return false;
    }
    
    /**
     * Buscar usuário por email
     */
    public function buscarPorEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Atualizar conexão social do usuário
     */
    public function atualizarConexaoSocial($user_id, $provider, $provider_id, $access_token) {
        // Primeiro, verificar se a conexão já existe
        $query = "SELECT id FROM social_connections WHERE user_id = :user_id AND provider = :provider";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":provider", $provider);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            // Atualizar conexão existente
            $query = "UPDATE social_connections 
                      SET provider_id = :provider_id, access_token = :access_token, 
                          updated_at = NOW() 
                      WHERE user_id = :user_id AND provider = :provider";
        } else {
            // Criar nova conexão
            $query = "INSERT INTO social_connections 
                      SET user_id = :user_id, provider = :provider, provider_id = :provider_id, 
                          access_token = :access_token, created_at = NOW(), updated_at = NOW()";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":provider", $provider);
        $stmt->bindParam(":provider_id", $provider_id);
        $stmt->bindParam(":access_token", $access_token);
        
        return $stmt->execute();
    }
    
    /**
     * Salvar conexão social
     */
    private function salvarConexaoSocial($user_id, $provider, $provider_id, $access_token) {
        $query = "INSERT INTO social_connections 
                  SET user_id = :user_id, provider = :provider, provider_id = :provider_id, 
                      access_token = :access_token, created_at = NOW(), updated_at = NOW()";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":provider", $provider);
        $stmt->bindParam(":provider_id", $provider_id);
        $stmt->bindParam(":access_token", $access_token);
        
        return $stmt->execute();
    }
    
    /**
     * Obter conexão social do usuário
     */
    public function obterConexaoSocial($user_id, $provider) {
        $query = "SELECT * FROM social_connections 
                  WHERE user_id = :user_id AND provider = :provider";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":provider", $provider);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obter todas as conexões sociais do usuário
     */
    public function obterConexoesSociais($user_id) {
        $query = "SELECT provider, provider_id, created_at, updated_at 
                  FROM social_connections 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Remover conexão social
     */
    public function removerConexaoSocial($user_id, $provider) {
        $query = "DELETE FROM social_connections 
                  WHERE user_id = :user_id AND provider = :provider";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":provider", $provider);
        
        return $stmt->execute();
    }
    
    /**
     * Registrar compartilhamento
     */
    public function registrarCompartilhamento($dados) {
        $query = "INSERT INTO social_shares 
                  SET user_id = :user_id, provider = :provider, content_type = :content_type, 
                      content_id = :content_id, response = :response, created_at = NOW()";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $dados['user_id']);
        $stmt->bindParam(":provider", $dados['provider']);
        $stmt->bindParam(":content_type", $dados['content_type']);
        $stmt->bindParam(":content_id", $dados['content_id']);
        $stmt->bindParam(":response", $dados['response']);
        
        return $stmt->execute();
    }
    
    /**
     * Obter histórico de compartilhamentos
     */
    public function obterHistoricoCompartilhamentos($user_id, $limit = 50) {
        $query = "SELECT * FROM social_shares 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>