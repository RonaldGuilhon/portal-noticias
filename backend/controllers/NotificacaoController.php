<?php
/**
 * Controlador de Notificações
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Notificacao.php';


class NotificacaoController {
    private $db;
    private $notificacao;
    private $jwtHelper;
    private $usuarioAtual;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->notificacao = new Notificacao($this->db);
        $this->jwtHelper = new JWTHelper();
        
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
     * Processar requisições
     */
    public function processarRequisicao() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        
        // Extrair partes da URI
        $path_parts = explode('/', trim(parse_url($uri, PHP_URL_PATH), '/'));
        
        // Verificar autenticação para todas as rotas
        if (!$this->verificarAutenticacao()) {
            $this->responderErro('Token de acesso inválido ou expirado', 401);
            return;
        }

        switch($method) {
            case 'GET':
                if (isset($path_parts[2]) && $path_parts[2] === 'notifications') {
                    $this->listarNotificacoes();
                } else {
                    $this->responderErro('Endpoint não encontrado', 404);
                }
                break;
                
            case 'PUT':
                if (isset($path_parts[2]) && $path_parts[2] === 'notifications' && 
                    isset($path_parts[3]) && is_numeric($path_parts[3]) && 
                    isset($path_parts[4]) && $path_parts[4] === 'read') {
                    $this->marcarComoLida($path_parts[3]);
                } else {
                    $this->responderErro('Endpoint não encontrado', 404);
                }
                break;
                
            case 'POST':
                if (isset($path_parts[2]) && $path_parts[2] === 'notifications') {
                    $this->criarNotificacao();
                } else {
                    $this->responderErro('Endpoint não encontrado', 404);
                }
                break;
                
            case 'DELETE':
                if (isset($path_parts[2]) && $path_parts[2] === 'notifications' && 
                    isset($path_parts[3]) && is_numeric($path_parts[3])) {
                    $this->deletarNotificacao($path_parts[3]);
                } else {
                    $this->responderErro('Endpoint não encontrado', 404);
                }
                break;
                
            default:
                $this->responderErro('Método não permitido', 405);
        }
    }

    /**
     * Verificar autenticação
     */
    private function verificarAutenticacao() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        
        if (!$authHeader || !preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            return false;
        }
        
        $token = $matches[1];
        $userData = $this->jwtHelper->validarToken($token);
        
        if (!$userData) {
            return false;
        }
        
        // Verificar se é admin ou editor
        if (!in_array($userData['tipo_usuario'], ['admin', 'editor'])) {
            return false;
        }
        
        // Armazenar dados do usuário para uso posterior
        $this->usuarioAtual = $userData;
        
        return true;
    }

    /**
     * Listar notificações
     */
    private function listarNotificacoes() {
        try {
            $limite = (int)($_GET['limite'] ?? 10);
            $pagina = (int)($_GET['pagina'] ?? 1);
            $offset = ($pagina - 1) * $limite;
            
            // Listar notificações para administradores
            $notificacoes = $this->notificacao->listarParaAdmin($limite, $offset);
            
            // Contar total não lidas
            $naoLidas = $this->notificacao->contarNaoLidas();
            
            $this->responderSucesso([
                'notificacoes' => $notificacoes,
                'nao_lidas' => $naoLidas,
                'pagina' => $pagina,
                'limite' => $limite
            ]);
            
        } catch (Exception $e) {
            $this->responderErro('Erro ao listar notificações: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Marcar notificação como lida
     */
    private function marcarComoLida($id) {
        try {
            if ($this->notificacao->marcarComoLida($id)) {
                $this->responderSucesso(['mensagem' => 'Notificação marcada como lida com success']);
            } else {
                $this->responderErro('Erro ao marcar notificação como lida', 500);
            }
        } catch (Exception $e) {
            $this->responderErro('Erro ao marcar notificação como lida: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Criar nova notificação
     */
    private function criarNotificacao() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $this->responderErro('Dados inválidos', 400);
                return;
            }
            
            // Validar campos obrigatórios
            if (empty($input['titulo']) || empty($input['mensagem'])) {
                $this->responderErro('Título e mensagem são obrigatórios', 400);
                return;
            }
            
            $this->notificacao->titulo = $input['titulo'];
            $this->notificacao->mensagem = $input['mensagem'];
            $this->notificacao->tipo = $input['tipo'] ?? 'sistema';
            $this->notificacao->usuario_id = $input['usuario_id'] ?? null;
            $this->notificacao->url = $input['url'] ?? null;
            $this->notificacao->icone = $input['icone'] ?? null;
            
            if ($this->notificacao->criar()) {
                $this->responderSucesso([
                    'mensagem' => 'Notificação criada com success',
                    'id' => $this->notificacao->id
                ]);
            } else {
                $this->responderErro('Erro ao criar notificação', 500);
            }
            
        } catch (Exception $e) {
            $this->responderErro('Erro ao criar notificação: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Deletar notificação
     */
    private function deletarNotificacao($id) {
        try {
            if ($this->notificacao->deletar($id)) {
                $this->responderSucesso(['mensagem' => 'Notificação deletada com success']);
            } else {
                $this->responderErro('Erro ao deletar notificação', 500);
            }
        } catch (Exception $e) {
            $this->responderErro('Erro ao deletar notificação: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Responder com sucesso
     */
    private function responderSucesso($data, $codigo = 200) {
        http_response_code($codigo);
        echo json_encode([
            'success' => true,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Responder com erro
     */
    private function responderErro($mensagem, $codigo = 400) {
        http_response_code($codigo);
        echo json_encode([
            'success' => false,
            'mensagem' => $mensagem
        ], JSON_UNESCAPED_UNICODE);
    }
}

// Processar requisição se este arquivo for chamado diretamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $controller = new NotificacaoController();
    $controller->processarRequisicao();
}
?>