<?php
/**
 * Controlador de Administração
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Noticia.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Comentario.php';


class AdminController {
    private $db;
    private $usuario;
    private $noticia;
    private $categoria;
    private $comentario;
    private $jwtHelper;
    private $usuarioAtual;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->usuario = new Usuario($this->db);
        $this->noticia = new Noticia($this->db);
        $this->categoria = new Categoria($this->db);
        $this->comentario = new Comentario($this->db);
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
        $action = $_GET['action'] ?? 'dashboard';

        // Verificar autenticação para todas as ações admin
        if (!$this->verificarAutenticacao()) {
            jsonResponse([
                'erro' => 'Acesso negado',
                'mensagem' => 'Você precisa estar logado como administrador'
            ], 401);
            return;
        }

        switch($method) {
            case 'GET':
                switch($action) {
                    case 'dashboard':
                        $this->dashboard();
                        break;
                    case 'stats':
                    case 'estatisticas':
                        $this->estatisticas();
                        break;
                    case 'users':
                    case 'usuarios':
                        $this->listarUsuarios();
                        break;
                    case 'settings':
                    case 'configuracoes':
                        $this->configuracoes();
                        break;
                    case 'logs':
                        $this->logs();
                        break;
                    default:
                        $this->dashboard();
                }
                break;
            case 'POST':
                switch($action) {
                    case 'settings':
                    case 'configuracoes':
                        $this->salvarConfiguracoes();
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
     * Verificar se o usuário está autenticado e é admin/editor
     */
    private function verificarAutenticacao() {
        $headers = getallheaders();
        $token = null;

        // Verificar token no header Authorization
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        // Verificar token na sessão
        if (!$token && isset($_SESSION['token'])) {
            $token = $_SESSION['token'];
        }

        if (!$token) {
            return false;
        }

        try {
            $payload = $this->jwtHelper->validarToken($token);
            $resultado = $this->usuario->buscarPorId($payload['id']);
            
            if (!$resultado || !in_array($this->usuario->tipo_usuario, ['admin', 'editor'])) {
                return false;
            }

            // Armazenar dados do usuário para uso posterior
            $this->usuarioAtual = [
                'id' => $this->usuario->id,
                'nome' => $this->usuario->nome,
                'email' => $this->usuario->email,
                'tipo_usuario' => $this->usuario->tipo_usuario
            ];
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Dashboard principal
     */
    private function dashboard() {
        try {
            $stats = $this->obterEstatisticas();
            
            jsonResponse([
                'sucesso' => true,
                'dados' => $stats
            ]);
        } catch (Exception $e) {
            jsonResponse([
                'erro' => 'Erro ao carregar dashboard',
                'mensagem' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter estatísticas gerais
     */
    private function obterEstatisticas() {
        $stats = [];

        // Total de notícias
        $query = "SELECT COUNT(*) as total FROM noticias";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $stats['total_noticias'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Notícias publicadas
        $query = "SELECT COUNT(*) as total FROM noticias WHERE status = 'publicado'";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $stats['noticias_publicadas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Total de usuários
        $query = "SELECT COUNT(*) as total FROM usuarios";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $stats['total_usuarios'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Total de comentários
        $query = "SELECT COUNT(*) as total FROM comentarios";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $stats['total_comentarios'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Comentários pendentes
        $query = "SELECT COUNT(*) as total FROM comentarios WHERE aprovado = 0";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $stats['comentarios_pendentes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Total de categorias
        $query = "SELECT COUNT(*) as total FROM categorias WHERE ativa = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $stats['total_categorias'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        return $stats;
    }

    /**
     * Estatísticas detalhadas
     */
    private function estatisticas() {
        try {
            $stats = $this->obterEstatisticas();
            
            // Notícias mais visualizadas (últimos 30 dias)
            $query = "SELECT titulo, visualizacoes FROM noticias 
                     WHERE data_publicacao >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                     ORDER BY visualizacoes DESC LIMIT 10";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats['noticias_populares'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Usuários mais ativos
            $query = "SELECT u.nome, COUNT(n.id) as total_noticias 
                     FROM usuarios u 
                     LEFT JOIN noticias n ON u.id = n.autor_id 
                     WHERE u.tipo_usuario IN ('admin', 'editor') 
                     GROUP BY u.id 
                     ORDER BY total_noticias DESC LIMIT 5";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats['usuarios_ativos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            jsonResponse([
                'sucesso' => true,
                'dados' => $stats
            ]);
        } catch (Exception $e) {
            jsonResponse([
                'erro' => 'Erro ao carregar estatísticas',
                'mensagem' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar usuários
     */
    private function listarUsuarios() {
        try {
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['per_page'] ?? 25;
            $offset = ($page - 1) * $perPage;

            $query = "SELECT id, nome, email, tipo_usuario, ativo, data_criacao, ultimo_login 
                     FROM usuarios 
                     ORDER BY data_criacao DESC 
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Total de usuários
            $queryTotal = "SELECT COUNT(*) as total FROM usuarios";
            $stmtTotal = $this->db->prepare($queryTotal);
            $stmtTotal->execute();
            $total = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

            jsonResponse([
                'sucesso' => true,
                'dados' => $usuarios,
                'total' => $total,
                'pagina' => $page,
                'por_pagina' => $perPage
            ]);
        } catch (Exception $e) {
            jsonResponse([
                'erro' => 'Erro ao listar usuários',
                'mensagem' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter configurações
     */
    private function configuracoes() {
        try {
            $query = "SELECT chave, valor, descricao, tipo FROM configuracoes ORDER BY chave";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $configuracoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            jsonResponse([
                'sucesso' => true,
                'dados' => $configuracoes
            ]);
        } catch (Exception $e) {
            jsonResponse([
                'erro' => 'Erro ao carregar configurações',
                'mensagem' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Salvar configurações
     */
    private function salvarConfiguracoes() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['configuracoes'])) {
                jsonResponse(['erro' => 'Dados inválidos'], 400);
                return;
            }

            $this->db->beginTransaction();

            foreach ($input['configuracoes'] as $config) {
                $query = "UPDATE configuracoes SET valor = :valor WHERE chave = :chave";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':valor', $config['valor']);
                $stmt->bindParam(':chave', $config['chave']);
                $stmt->execute();
            }

            $this->db->commit();

            jsonResponse([
                'sucesso' => true,
                'mensagem' => 'Configurações salvas com sucesso'
            ]);
        } catch (Exception $e) {
            $this->db->rollBack();
            jsonResponse([
                'erro' => 'Erro ao salvar configurações',
                'mensagem' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logs do sistema (placeholder)
     */
    private function logs() {
        jsonResponse([
            'sucesso' => true,
            'dados' => [],
            'mensagem' => 'Sistema de logs não implementado'
        ]);
    }
}
?>