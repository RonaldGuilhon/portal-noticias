<?php
/**
 * Controlador de Autenticação
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../services/EmailService.php';

class AuthController {
    private $db;
    private $usuario;
    private $emailService;
    private $jwtHelper;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->usuario = new Usuario($this->db);
        $this->emailService = new EmailService();
        $this->jwtHelper = new JWTHelper();
        
        // Iniciar sessão se não estiver iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Processar requisições
     */
    public function processarRequisicao() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';

        switch($method) {
            case 'POST':
                switch($action) {
                    case 'login':
                        $this->login();
                        break;
                    case 'register':
                        $this->registrar();
                        break;
                    case 'forgot-password':
                        $this->esqueceuSenha();
                        break;
                    case 'reset-password':
                        $this->redefinirSenha();
                        break;
                    case 'verify-email':
                        $this->verificarEmail();
                        break;
                    case 'logout':
                        $this->logout();
                        break;
                    case 'social-login':
                        $this->loginSocial();
                        break;
                    default:
                        jsonResponse(['erro' => 'Ação não encontrada'], 404);
                }
                break;
            case 'GET':
                switch($action) {
                    case 'verify':
                        $this->verificarEmailGet();
                        break;
                    case 'check-auth':
                        $this->verificarAutenticacao();
                        break;
                    case 'profile':
                        $this->obterPerfil();
                        break;
                    case 'activity':
                        $this->obterAtividade();
                        break;
                    case 'comments':
                        $this->obterComentarios();
                        break;
                    case 'favorite-categories':
                        $this->obterCategoriasFavoritas();
                        break;
                    default:
                        jsonResponse(['erro' => 'Ação não encontrada'], 404);
                }
                break;
            case 'PUT':
                switch($action) {
                    case 'profile':
                        $this->atualizarPerfil();
                        break;
                    case 'password':
                    case 'change-password':
                        $this->alterarSenha();
                        break;
                    case 'preferences':
                        $this->atualizarPreferencias();
                        break;
                    case 'notifications':
                        $this->atualizarNotificacoes();
                        break;
                    default:
                        jsonResponse(['erro' => 'Ação não encontrada'], 404);
                }
                break;
            default:
                jsonResponse(['erro' => 'Método não permitido'], 405);
        }
    }

    /**
     * Login de usuário
     */
    private function login() {
        try {
            $dados = json_decode(file_get_contents('php://input'), true);
            
            if(empty($dados['email']) || empty($dados['senha'])) {
                jsonResponse(['erro' => 'Email e senha são obrigatórios'], 400);
            }

            $email = sanitizeInput($dados['email']);
            $senha = $dados['senha'];
            $lembrar = $dados['lembrar'] ?? false;

            // Verificar tentativas de login
            if($this->verificarTentativasLogin($email)) {
                jsonResponse(['erro' => 'Muitas tentativas de login. Tente novamente em 15 minutos.'], 429);
            }

            if($this->usuario->login($email, $senha)) {
                // Limpar tentativas de login
                $this->limparTentativasLogin($email);
                
                // Criar sessão
                $_SESSION['usuario_id'] = $this->usuario->id;
                $_SESSION['usuario_nome'] = $this->usuario->nome;
                $_SESSION['usuario_email'] = $this->usuario->email;
                $_SESSION['usuario_tipo'] = $this->usuario->tipo_usuario;
                $_SESSION['logado'] = true;

                // Gerar JWT token com expiração de 24 horas
                $token = $this->jwtHelper->gerarToken([
                    'id' => $this->usuario->id,
                    'email' => $this->usuario->email,
                    'tipo' => $this->usuario->tipo_usuario
                ], 86400); // 24 horas

                // Cookie de lembrar
                if($lembrar) {
                    setcookie('auth_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                }

                jsonResponse([
                    'sucesso' => true,
                    'mensagem' => 'Login realizado com sucesso',
                    'usuario' => [
                        'id' => $this->usuario->id,
                        'nome' => $this->usuario->nome,
                        'email' => $this->usuario->email,
                        'tipo' => $this->usuario->tipo_usuario,
                        'email_verificado' => $this->usuario->email_verificado
                    ],
                    'token' => $token
                ]);
            } else {
                // Registrar tentativa de login
                $this->registrarTentativaLogin($email);
                jsonResponse(['erro' => 'Email ou senha incorretos'], 401);
            }
        } catch(Exception $e) {
            logError('Erro no login: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Registrar novo usuário
     */
    private function registrar() {
        try {
            $dados = json_decode(file_get_contents('php://input'), true);
            
            $this->usuario->nome = $dados['nome'] ?? '';
            $this->usuario->email = $dados['email'] ?? '';
            $this->usuario->senha = $dados['senha'] ?? '';
            $confirmar_senha = $dados['confirmar_senha'] ?? '';

            // Validar dados
            $erros = $this->usuario->validar();
            
            if($this->usuario->senha !== $confirmar_senha) {
                $erros[] = 'Senhas não coincidem';
            }
            
            if($this->usuario->emailExiste($this->usuario->email)) {
                $erros[] = 'Email já está em uso';
            }

            if(!empty($erros)) {
                jsonResponse(['erro' => implode(', ', $erros)], 400);
            }

            if($this->usuario->criar()) {
                // Enviar email de verificação (temporariamente desabilitado)
                // $this->enviarEmailVerificacao($this->usuario->email, $this->usuario->token_verificacao);
                
                jsonResponse([
                    'sucesso' => true,
                    'mensagem' => 'Usuário criado com sucesso.',
                    'usuario_id' => $this->usuario->id
                ], 201);
            } else {
                jsonResponse(['erro' => 'Erro ao criar usuário'], 500);
            }
        } catch(Exception $e) {
            logError('Erro no registro: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Esqueceu senha
     */
    private function esqueceuSenha() {
        try {
            $dados = json_decode(file_get_contents('php://input'), true);
            $email = sanitizeInput($dados['email'] ?? '');

            if(empty($email) || !isValidEmail($email)) {
                jsonResponse(['erro' => 'Email válido é obrigatório'], 400);
            }

            $token = $this->usuario->solicitarRecuperacaoSenha($email);
            
            if($token) {
                $this->enviarEmailRecuperacao($email, $token);
                jsonResponse([
                    'sucesso' => true,
                    'mensagem' => 'Email de recuperação enviado com sucesso'
                ]);
            } else {
                jsonResponse(['erro' => 'Email não encontrado'], 404);
            }
        } catch(Exception $e) {
            logError('Erro na recuperação de senha: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Redefinir senha
     */
    private function redefinirSenha() {
        try {
            $dados = json_decode(file_get_contents('php://input'), true);
            
            $token = $dados['token'] ?? '';
            $nova_senha = $dados['nova_senha'] ?? '';
            $confirmar_senha = $dados['confirmar_senha'] ?? '';

            if(empty($token) || empty($nova_senha) || empty($confirmar_senha)) {
                jsonResponse(['erro' => 'Todos os campos são obrigatórios'], 400);
            }

            if($nova_senha !== $confirmar_senha) {
                jsonResponse(['erro' => 'Senhas não coincidem'], 400);
            }

            if(strlen($nova_senha) < PASSWORD_MIN_LENGTH) {
                jsonResponse(['erro' => 'Senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres'], 400);
            }

            if($this->usuario->recuperarSenha($token, $nova_senha)) {
                jsonResponse([
                    'sucesso' => true,
                    'mensagem' => 'Senha redefinida com sucesso'
                ]);
            } else {
                jsonResponse(['erro' => 'Token inválido ou expirado'], 400);
            }
        } catch(Exception $e) {
            logError('Erro na redefinição de senha: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Verificar email (POST)
     */
    private function verificarEmail() {
        try {
            $dados = json_decode(file_get_contents('php://input'), true);
            $token = $dados['token'] ?? '';

            if(empty($token)) {
                jsonResponse(['erro' => 'Token é obrigatório'], 400);
            }

            if($this->usuario->verificarEmail($token)) {
                jsonResponse([
                    'sucesso' => true,
                    'mensagem' => 'Email verificado com sucesso'
                ]);
            } else {
                jsonResponse(['erro' => 'Token inválido'], 400);
            }
        } catch(Exception $e) {
            logError('Erro na verificação de email: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Verificar email (GET)
     */
    private function verificarEmailGet() {
        try {
            $token = $_GET['token'] ?? '';

            if(empty($token)) {
                header('Location: /login.html?erro=token_invalido');
                exit;
            }

            if($this->usuario->verificarEmail($token)) {
                header('Location: /login.html?sucesso=email_verificado');
            } else {
                header('Location: /login.html?erro=token_invalido');
            }
            exit;
        } catch(Exception $e) {
            logError('Erro na verificação de email: ' . $e->getMessage());
            header('Location: /login.html?erro=erro_interno');
            exit;
        }
    }

    /**
     * Login social
     */
    private function loginSocial() {
        try {
            $dados = json_decode(file_get_contents('php://input'), true);
            
            $email = sanitizeInput($dados['email'] ?? '');
            $nome = sanitizeInput($dados['nome'] ?? '');
            $provider = sanitizeInput($dados['provider'] ?? '');
            $provider_id = sanitizeInput($dados['provider_id'] ?? '');

            if(empty($email) || empty($nome) || empty($provider) || empty($provider_id)) {
                jsonResponse(['erro' => 'Dados incompletos'], 400);
            }

            if($this->usuario->loginSocial($email, $nome, $provider, $provider_id)) {
                // Criar sessão
                $_SESSION['usuario_id'] = $this->usuario->id;
                $_SESSION['usuario_nome'] = $this->usuario->nome;
                $_SESSION['usuario_email'] = $this->usuario->email;
                $_SESSION['usuario_tipo'] = $this->usuario->tipo_usuario;
                $_SESSION['logado'] = true;

                // Gerar JWT token com expiração de 24 horas
                $token = $this->jwtHelper->gerarToken([
                    'id' => $this->usuario->id,
                    'email' => $this->usuario->email,
                    'tipo' => $this->usuario->tipo_usuario
                ], 86400); // 24 horas

                jsonResponse([
                    'sucesso' => true,
                    'mensagem' => 'Login social realizado com sucesso',
                    'usuario' => [
                        'id' => $this->usuario->id,
                        'nome' => $this->usuario->nome,
                        'email' => $this->usuario->email,
                        'tipo' => $this->usuario->tipo_usuario
                    ],
                    'token' => $token
                ]);
            } else {
                jsonResponse(['erro' => 'Erro no login social'], 500);
            }
        } catch(Exception $e) {
            logError('Erro no login social: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Logout
     */
    private function logout() {
        try {
            // Destruir sessão
            session_destroy();
            
            // Remover cookie
            setcookie('auth_token', '', time() - 3600, '/');
            
            jsonResponse([
                'sucesso' => true,
                'mensagem' => 'Logout realizado com sucesso'
            ]);
        } catch(Exception $e) {
            logError('Erro no logout: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Verificar autenticação
     */
    private function verificarAutenticacao() {
        try {
            if($this->estaLogado()) {
                $this->usuario->buscarPorId($_SESSION['usuario_id']);
                
                jsonResponse([
                    'logado' => true,
                    'usuario' => [
                        'id' => $this->usuario->id,
                        'nome' => $this->usuario->nome,
                        'email' => $this->usuario->email,
                        'tipo' => $this->usuario->tipo_usuario,
                        'foto_perfil' => $this->usuario->foto_perfil
                    ]
                ]);
            } else {
                jsonResponse(['logado' => false]);
            }
        } catch(Exception $e) {
            logError('Erro na verificação de autenticação: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Obter perfil do usuário
     */
    private function obterPerfil() {
        try {
            if(!$this->estaLogado()) {
                jsonResponse(['erro' => 'Não autorizado'], 401);
            }

            $this->usuario->buscarPorId($_SESSION['usuario_id']);
            
            jsonResponse([
                'usuario' => [
                    'id' => $this->usuario->id,
                    'nome' => $this->usuario->nome,
                    'email' => $this->usuario->email,
                    'bio' => $this->usuario->bio,
                    'foto_perfil' => $this->usuario->foto_perfil,
                    'tipo' => $this->usuario->tipo_usuario,
                    'data_criacao' => $this->usuario->data_criacao,
                    'preferencias' => json_decode($this->usuario->preferencias, true)
                ]
            ]);
        } catch(Exception $e) {
            logError('Erro ao obter perfil: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Atualizar perfil
     */
    private function atualizarPerfil() {
        try {
            if(!$this->estaLogado()) {
                jsonResponse(['erro' => 'Não autorizado'], 401);
            }

            $dados = json_decode(file_get_contents('php://input'), true);
            
            $this->usuario->id = $_SESSION['usuario_id'];
            $this->usuario->nome = $dados['nome'] ?? '';
            $this->usuario->bio = $dados['bio'] ?? '';
            $this->usuario->preferencias = json_encode($dados['preferencias'] ?? []);
            
            // Upload de foto se fornecida
            if(!empty($dados['foto_perfil'])) {
                $this->usuario->foto_perfil = $this->processarUploadFoto($dados['foto_perfil']);
            }

            if($this->usuario->atualizarPerfil()) {
                // Atualizar sessão
                $_SESSION['usuario_nome'] = $this->usuario->nome;
                
                jsonResponse([
                    'sucesso' => true,
                    'mensagem' => 'Perfil atualizado com sucesso'
                ]);
            } else {
                jsonResponse(['erro' => 'Erro ao atualizar perfil'], 500);
            }
        } catch(Exception $e) {
            logError('Erro ao atualizar perfil: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Alterar senha
     */
    private function alterarSenha() {
        try {
            if(!$this->estaLogado()) {
                jsonResponse(['erro' => 'Não autorizado'], 401);
            }

            $dados = json_decode(file_get_contents('php://input'), true);
            
            $senha_atual = $dados['senha_atual'] ?? '';
            $nova_senha = $dados['nova_senha'] ?? '';
            $confirmar_senha = $dados['confirmar_senha'] ?? '';

            if(empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
                jsonResponse(['erro' => 'Todos os campos são obrigatórios'], 400);
            }

            if($nova_senha !== $confirmar_senha) {
                jsonResponse(['erro' => 'Senhas não coincidem'], 400);
            }

            if(strlen($nova_senha) < PASSWORD_MIN_LENGTH) {
                jsonResponse(['erro' => 'Senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres'], 400);
            }

            $this->usuario->id = $_SESSION['usuario_id'];
            
            if($this->usuario->alterarSenha($senha_atual, $nova_senha)) {
                jsonResponse([
                    'sucesso' => true,
                    'mensagem' => 'Senha alterada com sucesso'
                ]);
            } else {
                jsonResponse(['erro' => 'Senha atual incorreta'], 400);
            }
        } catch(Exception $e) {
            logError('Erro ao alterar senha: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Verificar se usuário está logado
     */
    private function estaLogado() {
        return isset($_SESSION['logado']) && $_SESSION['logado'] === true;
    }

    /**
     * Verificar tentativas de login
     */
    private function verificarTentativasLogin($email) {
        $arquivo = LOGS_PATH . 'login_attempts.json';
        
        if(!file_exists($arquivo)) {
            return false;
        }
        
        $tentativas = json_decode(file_get_contents($arquivo), true) ?: [];
        
        if(isset($tentativas[$email])) {
            $dados = $tentativas[$email];
            
            // Verificar se ainda está no período de bloqueio
            if($dados['count'] >= MAX_LOGIN_ATTEMPTS && 
               (time() - $dados['last_attempt']) < LOGIN_LOCKOUT_TIME) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Registrar tentativa de login
     */
    private function registrarTentativaLogin($email) {
        $arquivo = LOGS_PATH . 'login_attempts.json';
        $tentativas = [];
        
        if(file_exists($arquivo)) {
            $tentativas = json_decode(file_get_contents($arquivo), true) ?: [];
        }
        
        if(!isset($tentativas[$email])) {
            $tentativas[$email] = ['count' => 0, 'last_attempt' => 0];
        }
        
        $tentativas[$email]['count']++;
        $tentativas[$email]['last_attempt'] = time();
        
        file_put_contents($arquivo, json_encode($tentativas));
    }

    /**
     * Limpar tentativas de login
     */
    private function limparTentativasLogin($email) {
        $arquivo = LOGS_PATH . 'login_attempts.json';
        
        if(file_exists($arquivo)) {
            $tentativas = json_decode(file_get_contents($arquivo), true) ?: [];
            unset($tentativas[$email]);
            file_put_contents($arquivo, json_encode($tentativas));
        }
    }

    /**
     * Enviar email de verificação
     */
    private function enviarEmailVerificacao($email, $token) {
        $link = BASE_URL . "/backend/controllers/AuthController.php?action=verify&token={$token}";
        
        $assunto = "Verificação de Email - Portal de Notícias";
        $mensagem = "
        <h2>Bem-vindo ao Portal de Notícias!</h2>
        <p>Para ativar sua conta, clique no link abaixo:</p>
        <p><a href='{$link}' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Verificar Email</a></p>
        <p>Se você não se cadastrou em nosso site, ignore este email.</p>
        ";
        
        $this->emailService->enviar($email, $assunto, $mensagem);
    }

    /**
     * Verificar token de autenticação
     */
    private function verificarToken() {
        if (!$this->estaLogado()) {
            jsonResponse(['erro' => 'Não autorizado'], 401);
            return false;
        }
        
        // Buscar dados do usuário
        $this->usuario->buscarPorId($_SESSION['usuario_id']);
        
        return [
            'id' => $this->usuario->id,
            'nome' => $this->usuario->nome,
            'email' => $this->usuario->email
        ];
    }

    /**
     * Obter atividade do usuário
     */
    private function obterAtividade() {
        $usuario = $this->verificarToken();
        if (!$usuario) return;

        try {
            // Buscar estatísticas do usuário
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(DISTINCT c.id) as total_comments,
                    COUNT(DISTINCT n.id) as total_views,
                    0 as total_likes,
                    0 as total_shares
                FROM usuarios u
                LEFT JOIN comentarios c ON c.usuario_id = u.id
                LEFT JOIN noticias n ON n.autor_id = u.id
                WHERE u.id = ?
            ");
            $stmt->execute([$usuario['id']]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Buscar atividades recentes
            $stmt = $this->db->prepare("
                SELECT 'comment' as tipo, c.data_comentario as data_atividade, 
                       n.titulo as titulo_noticia, n.id as noticia_id
                FROM comentarios c
                JOIN noticias n ON n.id = c.noticia_id
                WHERE c.usuario_id = ?
                ORDER BY c.data_comentario DESC
                LIMIT 10
            ");
            $stmt->execute([$usuario['id']]);
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

            jsonResponse([
                'sucesso' => true,
                'data' => [
                    'stats' => $stats,
                    'activities' => $activities
                ]
            ]);
        } catch (Exception $e) {
            logError("Erro ao obter atividade: " . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Obter comentários do usuário
     */
    private function obterComentarios() {
        $usuario = $this->verificarToken();
        if (!$usuario) return;

        try {
            $stmt = $this->db->prepare("
                SELECT c.*, n.titulo as titulo_noticia, n.id as noticia_id
                FROM comentarios c
                JOIN noticias n ON n.id = c.noticia_id
                WHERE c.usuario_id = ?
                ORDER BY c.data_comentario DESC
                LIMIT 10
            ");
            $stmt->execute([$usuario['id']]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            jsonResponse([
                'success' => true,
                'data' => $comments
            ]);
        } catch (Exception $e) {
            logError("Erro ao obter comentários: " . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Obter categorias favoritas
     */
    private function obterCategoriasFavoritas() {
        $usuario = $this->verificarToken();
        if (!$usuario) return;

        try {
            $stmt = $this->db->prepare("
                SELECT c.id, c.nome, c.slug, COUNT(n.id) as total_leituras
                FROM categorias c
                LEFT JOIN noticias n ON n.categoria_id = c.id
                GROUP BY c.id, c.nome, c.slug
                ORDER BY total_leituras DESC
                LIMIT 5
            ");
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            jsonResponse([
                'success' => true,
                'data' => $categories
            ]);
        } catch (Exception $e) {
            logError("Erro ao obter categorias favoritas: " . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Atualizar preferências do usuário
     */
    private function atualizarPreferencias() {
        $usuario = $this->verificarToken();
        if (!$usuario) return;

        $input = json_decode(file_get_contents('php://input'), true);
        
        try {
            // Atualizar configurações do usuário
            $configuracoes = [
                'show_images' => $input['show-images'] ?? true,
                'auto_play_videos' => $input['auto-play-videos'] ?? false,
                'dark_mode' => $input['dark-mode'] ?? false
            ];

            $stmt = $this->db->prepare("
                UPDATE usuarios 
                SET configuracoes = ?
                WHERE id = ?
            ");
            $stmt->execute([json_encode($configuracoes), $usuario['id']]);

            jsonResponse([
                'success' => true,
                'message' => 'Preferências atualizadas com sucesso'
            ]);
        } catch (Exception $e) {
            logError("Erro ao atualizar preferências: " . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Atualizar configurações de notificação
     */
    private function atualizarNotificacoes() {
        $usuario = $this->verificarToken();
        if (!$usuario) return;

        $input = json_decode(file_get_contents('php://input'), true);
        
        try {
            // Atualizar configurações de notificação
            $stmt = $this->db->prepare("
                UPDATE usuarios 
                SET email_newsletter = ?, 
                    notificacoes_email = ?,
                    notificacoes_push = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $input['email-newsletter'] ?? false,
                $input['email-notifications'] ?? false,
                $input['push-notifications'] ?? false,
                $usuario['id']
            ]);

            jsonResponse([
                'success' => true,
                'message' => 'Configurações de notificação atualizadas'
            ]);
        } catch (Exception $e) {
            logError("Erro ao atualizar notificações: " . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Enviar email de recuperação
     */
    private function enviarEmailRecuperacao($email, $token) {
        $link = BASE_URL . "/reset-password.html?token={$token}";
        
        $assunto = "Recuperação de Senha - Portal de Notícias";
        $mensagem = "
        <h2>Recuperação de Senha</h2>
        <p>Você solicitou a recuperação de sua senha. Clique no link abaixo para redefinir:</p>
        <p><a href='{$link}' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Redefinir Senha</a></p>
        <p>Este link expira em 1 hora.</p>
        <p>Se você não solicitou esta recuperação, ignore este email.</p>
        ";
        
        $this->emailService->enviar($email, $assunto, $mensagem);
    }

    /**
     * Processar upload de foto
     */
    private function processarUploadFoto($foto_base64) {
        // Implementar upload de foto em base64
        // Por simplicidade, retornando null aqui
        return null;
    }
}

// Processar requisição se chamado diretamente
if(basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $controller = new AuthController();
    $controller->processarRequisicao();
}
?>