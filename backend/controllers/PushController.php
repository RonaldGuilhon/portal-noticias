<?php
/**
 * Controlador de Push Notifications
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/JWTHelper.php';
require_once __DIR__ . '/../services/PushNotificationService.php';

class PushController {
    private $db;
    private $jwtHelper;
    private $pushService;
    private $usuarioAtual;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->jwtHelper = new JWTHelper();
        $this->pushService = new PushNotificationService($this->db);
        
        // Configurar headers para API
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Responder a requisições OPTIONS (preflight)
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    /**
     * Processar requisição
     */
    public function handleRequest($method, $action, $data = []) {
        switch ($method) {
            case 'GET':
                return $this->handleGet($action);
            case 'POST':
                return $this->handlePost($action, $data);
            case 'PUT':
                return $this->handlePut($action, $data);
            case 'DELETE':
                return $this->handleDelete($action, $data);
            default:
                return $this->jsonResponse([
                    'success' => false,
                    'erro' => 'Método não permitido'
                ], 405);
        }
    }

    /**
     * Processar requisições GET
     */
    private function handleGet($action) {
        switch($action) {
            case 'vapid':
                return $this->getVapidPublicKey();
            case 'subscriptions':
                return $this->getSubscriptions();
            case 'preferences':
                return $this->getPreferences();
            case 'stats':
                return $this->getStats();
            default:
                return $this->getInfo();
        }
    }

    /**
     * Processar requisições POST
     */
    private function handlePost($action, $data) {
        switch($action) {
            case 'subscribe':
                return $this->subscribe($data);
            case 'unsubscribe':
                return $this->unsubscribe($data);
            case 'send':
                return $this->sendNotification($data);
            case 'test':
                return $this->sendTestNotification($data);
            case 'click':
                return $this->registerClick($data);
            case 'close':
                return $this->registerClose($data);
            case 'sync':
                return $this->syncNotifications($data);
            default:
                return $this->jsonResponse([
                    'success' => false,
                    'erro' => 'Endpoint não encontrado'
                ], 404);
        }
    }

    /**
     * Processar requisições PUT
     */
    private function handlePut($action, $data) {
        switch($action) {
            case 'preferences':
                return $this->updatePreferences($data);
                break;
            default:
                return $this->jsonResponse([
                    'success' => false,
                    'erro' => 'Endpoint não encontrado'
                ], 404);
        }
    }

    /**
     * Processar requisições DELETE
     */
    private function handleDelete($action, $data) {
        switch($action) {
            case 'subscription':
                return $this->deleteSubscription($data);
            default:
                return $this->jsonResponse([
                    'success' => false,
                    'erro' => 'Endpoint não encontrado'
                ], 404);
        }
    }

    /**
     * Deletar subscription
     */
    private function deleteSubscription($data) {
        try {
            // Verificar autenticação
            if (!$this->verificarAutenticacao()) {
                return $this->jsonResponse([
                    'success' => false,
                    'erro' => 'Token de autenticação inválido ou ausente'
                ], 401);
            }

            // Validar dados obrigatórios
            if (!isset($data['endpoint'])) {
                return $this->jsonResponse([
                    'success' => false,
                    'erro' => 'Endpoint é obrigatório'
                ], 400);
            }

            // Usar o serviço para remover a subscription
            $resultado = $this->pushService->unsubscribe($this->usuarioAtual['id'], $data['endpoint']);
            
            if ($resultado) {
                return $this->jsonResponse([
                    'success' => true,
                    'mensagem' => 'Subscription removida com sucesso'
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'erro' => 'Subscription não encontrada ou já removida'
                ], 404);
            }
            
        } catch (Exception $e) {
            error_log("Erro ao deletar subscription: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'erro' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Processar requisições (método legado)
     */
    public function processarRequisicao() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        
        // Extrair partes da URI
        $path_parts = explode('/', trim(parse_url($uri, PHP_URL_PATH), '/'));
        $action = $path_parts[1] ?? 'info';
        
        // Obter dados da requisição
        $data = [];
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true) ?? [];
        }
        
        return $this->handleRequest($method, $action, $data);
    }

    /**
     * Verificar autenticação
     */
    private function verificarAutenticacao($obrigatorio = true) {
        $token = null;
        
        // Tentar obter o token de diferentes fontes
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['Authorization'])) {
                $token = str_replace('Bearer ', '', $headers['Authorization']);
            }
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
        }
        
        if (!$token && $obrigatorio) {
            return false;
        }
        
        if ($token) {
            $usuario = $this->jwtHelper->validarToken($token);
            if ($usuario) {
                $this->usuarioAtual = $usuario;
                return true;
            } elseif ($obrigatorio) {
                return false;
            }
        }
        
        return !$obrigatorio;
    }

    /**
     * Obter subscriptions
     */
    private function getSubscriptions() {
        try {
            // Verificar se foi especificado um usuário
            $userId = $_GET['user_id'] ?? null;
            
            // Obter subscriptions ativas do serviço
            $subscriptions = $this->pushService->getAllActiveSubscriptions($userId);
            
            return $this->jsonResponse([
                'success' => true,
                'subscriptions' => $subscriptions,
                'total' => count($subscriptions)
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao obter subscriptions: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'erro' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Obter informações do sistema de push
     */
    private function getInfo() {
        $this->responderSucesso([
            'sistema' => 'Push Notifications',
            'versao' => '1.0.0',
            'status' => 'ativo',
            'endpoints' => [
                'vapid' => '/push/vapid',
                'subscribe' => '/push/subscribe',
                'unsubscribe' => '/push/unsubscribe',
                'preferences' => '/push/preferences',
                'send' => '/push/send',
                'test' => '/push/test',
                'stats' => '/push/stats'
            ]
        ]);
    }

    /**
     * Obter chave pública VAPID
     */
    private function getVapidPublicKey() {
        try {
            $publicKey = $this->pushService->getVapidPublicKey();
            
            // Se não há chave configurada, gerar uma nova
            if (!$publicKey) {
                $keys = $this->pushService->generateVapidKeys();
                $publicKey = $keys['publicKey'];
            }
            
            return $this->responderSucesso([
                'publicKey' => $publicKey
            ]);
            
        } catch (Exception $e) {
            return $this->responderErro('Erro ao obter chave VAPID: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Inscrever usuário para push notifications
     */
    private function subscribe($data) {
        if (!$this->verificarAutenticacao()) {
            $this->responderErro('Token de acesso inválido ou expirado', 401);
            return;
        }
        
        try {
            // Usar dados passados como parâmetro ou ler do input
            $input = $data ?: json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['subscription'])) {
                $this->responderErro('Dados de subscription inválidos', 400);
                return;
            }
            
            $subscription = $input['subscription'];
            
            // Validar dados da subscription
            if (!isset($subscription['endpoint']) || 
                !isset($subscription['keys']['p256dh']) || 
                !isset($subscription['keys']['auth'])) {
                $this->responderErro('Dados de subscription incompletos', 400);
                return;
            }
            
            $result = $this->pushService->subscribe(
                $this->usuarioAtual['id'],
                $subscription['endpoint'],
                $subscription['keys']['p256dh'],
                $subscription['keys']['auth'],
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            
            if ($result) {
                $this->responderSucesso([
                    'mensagem' => 'Subscription criada com sucesso',
                    'id' => $result
                ]);
            } else {
                $this->responderErro('Erro ao criar subscription', 500);
            }
            
        } catch (Exception $e) {
            $this->responderErro('Erro ao processar subscription: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cancelar inscrição de push notifications
     */
    private function unsubscribe($data) {
        if (!$this->verificarAutenticacao()) {
            $this->responderErro('Token de acesso inválido ou expirado', 401);
            return;
        }
        
        try {
            // Usar dados passados como parâmetro ou ler do input
            $input = $data ?: json_decode(file_get_contents('php://input'), true);
            $endpoint = $input['endpoint'] ?? null;
            
            $result = $this->pushService->unsubscribe($this->usuarioAtual['id'], $endpoint);
            
            if ($result) {
                $this->responderSucesso([
                    'mensagem' => 'Subscription removida com sucesso'
                ]);
            } else {
                $this->responderErro('Subscription não encontrada', 404);
            }
            
        } catch (Exception $e) {
            $this->responderErro('Erro ao remover subscription: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Enviar notificação push
     */
    private function sendNotification($data) {
        if (!$this->verificarAutenticacao()) {
            $this->responderErro('Token de acesso inválido ou expirado', 401);
            return;
        }
        
        // Verificar se é admin
        if ($this->usuarioAtual['tipo_usuario'] !== 'admin') {
            $this->responderErro('Acesso negado', 403);
            return;
        }
        
        try {
            // Usar dados passados como parâmetro ou ler do input
            $input = $data ?: json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['title']) || !isset($input['body'])) {
                $this->responderErro('Título e mensagem são obrigatórios', 400);
                return;
            }
            
            $result = $this->pushService->sendNotification(
                $input['title'],
                $input['body'],
                $input['type'] ?? 'system',
                $input['url'] ?? null,
                $input['icon'] ?? null,
                $input['image'] ?? null,
                $input['userId'] ?? null,
                $input['categoryId'] ?? null
            );
            
            $this->responderSucesso([
                'mensagem' => 'Notificação enviada com sucesso',
                'enviadas' => $result['enviadas'],
                'erros' => $result['erros']
            ]);
            
        } catch (Exception $e) {
            $this->responderErro('Erro ao enviar notificação: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Enviar notificação de teste
     */
    private function sendTestNotification($data) {
        if (!$this->verificarAutenticacao()) {
            $this->responderErro('Token de acesso inválido ou expirado', 401);
            return;
        }
        
        try {
            $result = $this->pushService->sendTestNotification($this->usuarioAtual['id']);
            
            if ($result['enviadas'] > 0) {
                $this->responderSucesso([
                    'mensagem' => 'Notificação de teste enviada com sucesso'
                ]);
            } else {
                $this->responderErro('Nenhuma subscription ativa encontrada', 404);
            }
            
        } catch (Exception $e) {
            $this->responderErro('Erro ao enviar notificação de teste: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Registrar clique na notificação
     */
    private function registerClick($data) {
        try {
            // Usar dados passados como parâmetro ou ler do input
            $input = $data ?: json_decode(file_get_contents('php://input'), true);
            $notificationId = $input['notificationId'] ?? null;
            $action = $input['action'] ?? 'open';
            
            if ($notificationId) {
                $this->pushService->registerClick($notificationId, $action);
            }
            
            $this->responderSucesso(['mensagem' => 'Clique registrado']);
            
        } catch (Exception $e) {
            $this->responderErro('Erro ao registrar clique: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Registrar fechamento da notificação
     */
    private function registerClose($data) {
        try {
            // Usar dados passados como parâmetro ou ler do input
            $input = $data ?: json_decode(file_get_contents('php://input'), true);
            $notificationId = $input['notificationId'] ?? null;
            
            if ($notificationId) {
                $this->pushService->registerClose($notificationId);
            }
            
            $this->responderSucesso(['mensagem' => 'Fechamento registrado']);
            
        } catch (Exception $e) {
            $this->responderErro('Erro ao registrar fechamento: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obter preferências de push notifications
     */
    private function getPreferences() {
        if (!$this->verificarAutenticacao()) {
            $this->responderErro('Token de acesso inválido ou expirado', 401);
            return;
        }
        
        try {
            $preferences = $this->pushService->getPreferences($this->usuarioAtual['id']);
            $this->responderSucesso($preferences);
            
        } catch (Exception $e) {
            $this->responderErro('Erro ao obter preferências: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Atualizar preferências de push notifications
     */
    private function updatePreferences($data = null) {
        if (!$this->verificarAutenticacao()) {
            $this->responderErro('Token de acesso inválido ou expirado', 401);
            return;
        }
        
        try {
            // Usar dados fornecidos ou ler do php://input
            $input = $data ?? json_decode(file_get_contents('php://input'), true);
            
            $result = $this->pushService->updatePreferences(
                $this->usuarioAtual['id'],
                $input
            );
            
            if ($result) {
                $this->responderSucesso([
                    'mensagem' => 'Preferências atualizadas com sucesso'
                ]);
            } else {
                $this->responderErro('Erro ao atualizar preferências', 500);
            }
            
        } catch (Exception $e) {
            $this->responderErro('Erro ao atualizar preferências: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obter estatísticas de push notifications
     */
    private function getStats() {
        if (!$this->verificarAutenticacao()) {
            $this->responderErro('Token de acesso inválido ou expirado', 401);
            return;
        }
        
        // Verificar se é admin
        if ($this->usuarioAtual['tipo_usuario'] !== 'admin') {
            $this->responderErro('Acesso negado', 403);
            return;
        }
        
        try {
            $stats = $this->pushService->getStats();
            $this->responderSucesso($stats);
            
        } catch (Exception $e) {
            $this->responderErro('Erro ao obter estatísticas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Sincronizar notificações
     */
    private function syncNotifications($data) {
        try {
            $result = $this->pushService->syncPendingNotifications();
            $this->responderSucesso([
                'mensagem' => 'Sincronização concluída',
                'processadas' => $result
            ]);
            
        } catch (Exception $e) {
            $this->responderErro('Erro na sincronização: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Responder com JSON
     */
    private function jsonResponse($data, $statusCode = 200) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        return $data;
    }

    /**
     * Responder com sucesso
     */
    private function responderSucesso($dados, $codigo = 200) {
        return $this->jsonResponse([
            'success' => true,
            'data' => $dados
        ], $codigo);
    }

    /**
     * Responder com erro
     */
    private function responderErro($mensagem, $codigo = 400) {
        return $this->jsonResponse([
            'success' => false,
            'error' => $mensagem
        ], $codigo);
    }
}

// Executar o controller quando acessado diretamente
if (basename($_SERVER['SCRIPT_NAME']) === 'PushController.php') {
    try {
        $controller = new PushController();
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? 'info';
        
        // Obter dados do corpo da requisição para POST/PUT
        $data = [];
        if (in_array($method, ['POST', 'PUT'])) {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true) ?? [];
        }
        
        $controller->handleRequest($method, $action, $data);
        
    } catch (Exception $e) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'erro' => 'Erro interno: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}
?>