<?php
/**
 * Middleware de Autenticação
 * Portal de Notícias
 */

require_once __DIR__ . '/../utils/JWTHelper.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../config/database.php';

class AuthMiddleware {
    private $jwtHelper;
    private $usuario;
    private $db;
    
    public function __construct() {
        $this->jwtHelper = new JWTHelper();
        $database = new Database();
        $this->db = $database->getConnection();
        $this->usuario = new Usuario($this->db);
    }
    
    /**
     * Verificar token JWT
     */
    public function verificarToken() {
        try {
            // Verificar se há token no header Authorization
            $headers = getallheaders();
            $token = null;
            
            if (isset($headers['Authorization'])) {
                $auth_header = $headers['Authorization'];
                if (preg_match('/Bearer\s+(\S+)/', $auth_header, $matches)) {
                    $token = $matches[1];
                }
            }
            
            // Se não há token no header, verificar cookie
            if (!$token && isset($_COOKIE['auth_token'])) {
                $token = $_COOKIE['auth_token'];
            }
            
            // Se não há token, verificar sessão
            if (!$token) {
                session_start();
                if (isset($_SESSION['usuario_id']) && isset($_SESSION['logado']) && $_SESSION['logado']) {
                    // Buscar dados do usuário na sessão
                    return [
                        'valido' => true,
                        'usuario' => [
                            'id' => $_SESSION['usuario_id'],
                            'nome' => $_SESSION['usuario_nome'] ?? '',
                            'email' => $_SESSION['usuario_email'] ?? '',
                            'tipo' => $_SESSION['usuario_tipo'] ?? 'leitor'
                        ]
                    ];
                }
                
                return [
                    'valido' => false,
                    'erro' => 'Token não fornecido'
                ];
            }
            
            // Verificar e decodificar token
            $payload = $this->jwtHelper->validarToken($token);
            
            if (!$payload) {
                return [
                    'valido' => false,
                    'erro' => 'Token inválido ou expirado'
                ];
            }
            
            // Buscar dados atualizados do usuário
            $this->usuario->buscarPorId($payload['id']);
            
            if (!$this->usuario->id) {
                return [
                    'valido' => false,
                    'erro' => 'Usuário não encontrado'
                ];
            }
            
            return [
                'valido' => true,
                'usuario' => [
                    'id' => $this->usuario->id,
                    'nome' => $this->usuario->nome,
                    'email' => $this->usuario->email,
                    'tipo' => $this->usuario->tipo_usuario
                ],
                'payload' => $payload
            ];
            
        } catch (Exception $e) {
            return [
                'valido' => false,
                'erro' => 'Erro na verificação do token: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar se usuário é administrador
     */
    public function isAdmin() {
        $auth_result = $this->verificarToken();
        
        if (!$auth_result['valido']) {
            return false;
        }
        
        return $auth_result['usuario']['tipo'] === 'admin';
    }
    
    /**
     * Verificar se usuário é editor ou admin
     */
    public function isEditor() {
        $auth_result = $this->verificarToken();
        
        if (!$auth_result['valido']) {
            return false;
        }
        
        $tipo = $auth_result['usuario']['tipo'];
        return in_array($tipo, ['admin', 'editor']);
    }
    
    /**
     * Middleware para proteger rotas
     */
    public function protegerRota($tipos_permitidos = ['admin', 'editor', 'leitor']) {
        $auth_result = $this->verificarToken();
        
        if (!$auth_result['valido']) {
            http_response_code(401);
            echo json_encode([
                'sucesso' => false,
                'erro' => 'Acesso negado. Faça login para continuar.',
                'codigo' => 'AUTH_REQUIRED'
            ]);
            exit;
        }
        
        $tipo_usuario = $auth_result['usuario']['tipo'];
        
        if (!in_array($tipo_usuario, $tipos_permitidos)) {
            http_response_code(403);
            echo json_encode([
                'sucesso' => false,
                'erro' => 'Acesso negado. Permissões insuficientes.',
                'codigo' => 'INSUFFICIENT_PERMISSIONS'
            ]);
            exit;
        }
        
        return $auth_result;
    }
    
    /**
     * Obter usuário atual
     */
    public function getUsuarioAtual() {
        $auth_result = $this->verificarToken();
        
        if ($auth_result['valido']) {
            return $auth_result['usuario'];
        }
        
        return null;
    }
    
    /**
     * Autenticar usuário (alias para getUsuarioAtual)
     */
    public function authenticate() {
        return $this->getUsuarioAtual();
    }
}

// Função auxiliar para obter headers em diferentes ambientes
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}
?>