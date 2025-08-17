<?php
/**
 * Controlador de Integração com Redes Sociais
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/SocialMediaService.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Logger.php';

class SocialController {
    private $db;
    private $social_service;
    private $usuario;
    private $auth_middleware;
    private $logger;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->social_service = new SocialMediaService();
        $this->usuario = new Usuario($this->db);
        $this->auth_middleware = new AuthMiddleware();
        $this->logger = new Logger();
    }
    
    public function processarRequisicao() {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $action = $_GET['action'] ?? '';
            
            // Log da requisição
            $this->logger->info('SocialController', [
                'method' => $method,
                'action' => $action,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            switch ($method) {
                case 'GET':
                    $this->handleGet($action);
                    break;
                case 'POST':
                    $this->handlePost($action);
                    break;
                default:
                    http_response_code(405);
                    echo json_encode(['error' => 'Método não permitido']);
            }
        } catch (Exception $e) {
            $this->logger->error('SocialController Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno do servidor']);
        }
    }
    
    private function handleGet($action) {
        switch ($action) {
            case 'auth-url':
                $this->getAuthUrl();
                break;
            case 'callback':
                $this->handleCallback();
                break;
            case 'share-stats':
                $this->getShareStats();
                break;
            case 'providers':
                $this->getProviders();
                break;
            case 'user-connections':
                $this->getUserConnections();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Ação não encontrada']);
        }
    }
    
    private function handlePost($action) {
        switch ($action) {
            case 'share':
                $this->shareContent();
                break;
            case 'connect':
                $this->connectAccount();
                break;
            case 'disconnect':
                $this->disconnectAccount();
                break;
            case 'bulk-share':
                $this->bulkShare();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Ação não encontrada']);
        }
    }
    
    /**
     * Obter URL de autenticação OAuth
     */
    private function getAuthUrl() {
        $provider = $_GET['provider'] ?? '';
        $redirect_uri = $_GET['redirect_uri'] ?? null;
        
        if (empty($provider)) {
            http_response_code(400);
            echo json_encode(['error' => 'Provider é obrigatório']);
            return;
        }
        
        if (!$this->social_service->isProviderConfigured($provider)) {
            http_response_code(400);
            echo json_encode(['error' => 'Provider não configurado: ' . $provider]);
            return;
        }
        
        try {
            $auth_url = $this->social_service->getAuthUrl($provider, $redirect_uri);
            
            echo json_encode([
                'success' => true,
                'auth_url' => $auth_url,
                'provider' => $provider
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Processar callback OAuth
     */
    private function handleCallback() {
        $provider = $_GET['provider'] ?? '';
        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';
        $error = $_GET['error'] ?? '';
        
        if ($error) {
            http_response_code(400);
            echo json_encode(['error' => 'Erro na autenticação: ' . $error]);
            return;
        }
        
        if (empty($provider) || empty($code)) {
            http_response_code(400);
            echo json_encode(['error' => 'Parâmetros inválidos']);
            return;
        }
        
        try {
            $user_data = $this->social_service->handleCallback($provider, $code, $state);
            
            // Verificar se usuário já existe
            $existing_user = $this->usuario->buscarPorEmail($user_data['email']);
            
            if ($existing_user) {
                // Atualizar dados de conexão social
                $this->usuario->atualizarConexaoSocial(
                    $existing_user['id'],
                    $provider,
                    $user_data['provider_id'],
                    $user_data['access_token']
                );
                
                $user_id = $existing_user['id'];
            } else {
                // Criar novo usuário
                $user_id = $this->usuario->criarUsuarioSocial([
                    'nome' => $user_data['name'],
                    'email' => $user_data['email'],
                    'avatar' => $user_data['avatar'],
                    'provider' => $provider,
                    'provider_id' => $user_data['provider_id'],
                    'access_token' => $user_data['access_token']
                ]);
            }
            
            // Criar sessão
            session_start();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_email'] = $user_data['email'];
            $_SESSION['user_name'] = $user_data['name'];
            
            // Gerar JWT token
            $jwt_payload = [
                'user_id' => $user_id,
                'email' => $user_data['email'],
                'exp' => time() + (24 * 60 * 60) // 24 horas
            ];
            
            $jwt_token = $this->generateJWT($jwt_payload);
            
            echo json_encode([
                'success' => true,
                'message' => 'Autenticação realizada com success',
                'user' => [
                    'id' => $user_id,
                    'name' => $user_data['name'],
                    'email' => $user_data['email'],
                    'avatar' => $user_data['avatar']
                ],
                'token' => $jwt_token,
                'provider' => $provider
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Compartilhar conteúdo
     */
    private function shareContent() {
        // Verificar autenticação
        if (!$this->auth_middleware->verificarToken()) {
            http_response_code(401);
            echo json_encode(['error' => 'Token inválido']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $provider = $input['provider'] ?? '';
        $content = $input['content'] ?? [];
        $user_id = $this->auth_middleware->getUserId();
        
        if (empty($provider) || empty($content)) {
            http_response_code(400);
            echo json_encode(['error' => 'Provider e conteúdo são obrigatórios']);
            return;
        }
        
        try {
            // Obter access token do usuário
            $social_connection = $this->usuario->obterConexaoSocial($user_id, $provider);
            
            if (!$social_connection) {
                http_response_code(400);
                echo json_encode(['error' => 'Conta não conectada ao ' . $provider]);
                return;
            }
            
            $result = $this->social_service->shareContent(
                $provider,
                $content,
                $social_connection['access_token']
            );
            
            // Registrar compartilhamento
            $this->usuario->registrarCompartilhamento([
                'user_id' => $user_id,
                'provider' => $provider,
                'content_type' => $content['type'] ?? 'post',
                'content_id' => $content['id'] ?? null,
                'response' => json_encode($result)
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Conteúdo compartilhado com success',
                'result' => $result
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Compartilhamento em lote
     */
    private function bulkShare() {
        // Verificar autenticação
        if (!$this->auth_middleware->verificarToken()) {
            http_response_code(401);
            echo json_encode(['error' => 'Token inválido']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $providers = $input['providers'] ?? [];
        $content = $input['content'] ?? [];
        $user_id = $this->auth_middleware->getUserId();
        
        if (empty($providers) || empty($content)) {
            http_response_code(400);
            echo json_encode(['error' => 'Providers e conteúdo são obrigatórios']);
            return;
        }
        
        $results = [];
        $errors = [];
        
        foreach ($providers as $provider) {
            try {
                $social_connection = $this->usuario->obterConexaoSocial($user_id, $provider);
                
                if (!$social_connection) {
                    $errors[$provider] = 'Conta não conectada';
                    continue;
                }
                
                $result = $this->social_service->shareContent(
                    $provider,
                    $content,
                    $social_connection['access_token']
                );
                
                $results[$provider] = $result;
                
                // Registrar compartilhamento
                $this->usuario->registrarCompartilhamento([
                    'user_id' => $user_id,
                    'provider' => $provider,
                    'content_type' => $content['type'] ?? 'post',
                    'content_id' => $content['id'] ?? null,
                    'response' => json_encode($result)
                ]);
                
            } catch (Exception $e) {
                $errors[$provider] = $e->getMessage();
            }
        }
        
        echo json_encode([
            'success' => !empty($results),
            'results' => $results,
            'errors' => $errors,
            'total_success' => count($results),
            'total_errors' => count($errors)
        ]);
    }
    
    /**
     * Obter estatísticas de compartilhamento
     */
    private function getShareStats() {
        $url = $_GET['url'] ?? '';
        
        if (empty($url)) {
            http_response_code(400);
            echo json_encode(['error' => 'URL é obrigatória']);
            return;
        }
        
        try {
            $stats = $this->social_service->getShareStats($url);
            
            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'url' => $url
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Obter providers disponíveis
     */
    private function getProviders() {
        $providers = [
            'facebook' => [
                'name' => 'Facebook',
                'configured' => $this->social_service->isProviderConfigured('facebook'),
                'features' => ['login', 'share', 'stats']
            ],
            'google' => [
                'name' => 'Google',
                'configured' => $this->social_service->isProviderConfigured('google'),
                'features' => ['login']
            ],
            'twitter' => [
                'name' => 'Twitter',
                'configured' => $this->social_service->isProviderConfigured('twitter'),
                'features' => ['share']
            ],
            'linkedin' => [
                'name' => 'LinkedIn',
                'configured' => $this->social_service->isProviderConfigured('linkedin'),
                'features' => ['login', 'share']
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'providers' => $providers
        ]);
    }
    
    /**
     * Obter conexões sociais do usuário
     */
    private function getUserConnections() {
        // Verificar autenticação
        if (!$this->auth_middleware->verificarToken()) {
            http_response_code(401);
            echo json_encode(['error' => 'Token inválido']);
            return;
        }
        
        $user_id = $this->auth_middleware->getUserId();
        
        try {
            $connections = $this->usuario->obterConexoesSociais($user_id);
            
            echo json_encode([
                'success' => true,
                'connections' => $connections
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Conectar conta social
     */
    private function connectAccount() {
        // Verificar autenticação
        if (!$this->auth_middleware->verificarToken()) {
            http_response_code(401);
            echo json_encode(['error' => 'Token inválido']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $provider = $input['provider'] ?? '';
        $code = $input['code'] ?? '';
        $user_id = $this->auth_middleware->getUserId();
        
        if (empty($provider) || empty($code)) {
            http_response_code(400);
            echo json_encode(['error' => 'Provider e código são obrigatórios']);
            return;
        }
        
        try {
            $user_data = $this->social_service->handleCallback($provider, $code);
            
            $this->usuario->atualizarConexaoSocial(
                $user_id,
                $provider,
                $user_data['provider_id'],
                $user_data['access_token']
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Conta conectada com success',
                'provider' => $provider
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Desconectar conta social
     */
    private function disconnectAccount() {
        // Verificar autenticação
        if (!$this->auth_middleware->verificarToken()) {
            http_response_code(401);
            echo json_encode(['error' => 'Token inválido']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $provider = $input['provider'] ?? '';
        $user_id = $this->auth_middleware->getUserId();
        
        if (empty($provider)) {
            http_response_code(400);
            echo json_encode(['error' => 'Provider é obrigatório']);
            return;
        }
        
        try {
            $this->usuario->removerConexaoSocial($user_id, $provider);
            
            echo json_encode([
                'success' => true,
                'message' => 'Conta desconectada com success',
                'provider' => $provider
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Gerar JWT Token
     */
    private function generateJWT($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode($payload);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, JWT_SECRET, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
}

// Processar requisição se chamado diretamente
if (basename($_SERVER['PHP_SELF']) === 'SocialController.php') {
    $controller = new SocialController();
    $controller->processarRequisicao();
}