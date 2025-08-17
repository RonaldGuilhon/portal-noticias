<?php
/**
 * Arquivo principal da API - Roteador
 * Portal de Notícias
 */

require_once __DIR__ . '/config/config.php';

// Headers CORS e de segurança
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Responder a requisições OPTIONS (preflight)
if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Iniciar sessão
session_start();

// Capturar a URI e método da requisição
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];
$script_name = dirname($_SERVER['SCRIPT_NAME']);

// Remover o caminho base da URI
$path = str_replace($script_name, '', $request_uri);
$path = trim($path, '/');

// Remover query string
if(($pos = strpos($path, '?')) !== false) {
    $path = substr($path, 0, $pos);
}

// Dividir o caminho em segmentos
$segments = explode('/', $path);
$resource = $segments[0] ?? '';
$action = $segments[1] ?? '';
$id = $segments[2] ?? null;

// Log da requisição
logError("API Request: $request_method $path");

try {
    // Roteamento principal
    switch($resource) {
        case 'auth':
        case 'usuario':
        case 'usuarios':
            require_once __DIR__ . '/controllers/AuthController.php';
            $controller = new AuthController();
            
            // Definir ação baseada no endpoint
            switch($action) {
                case 'login':
                    $_GET['action'] = 'login';
                    break;
                case 'register':
                case 'cadastro':
                    $_GET['action'] = 'register';
                    break;
                case 'logout':
                    $_GET['action'] = 'logout';
                    break;
                case 'profile':
                case 'perfil':
                    $_GET['action'] = 'profile';
                    break;
                case 'forgot-password':
                case 'esqueci-senha':
                    $_GET['action'] = 'forgot_password';
                    break;
                case 'reset-password':
                case 'redefinir-senha':
                    $_GET['action'] = 'reset_password';
                    break;
                case 'verify-email':
                case 'verificar-email':
                    $_GET['action'] = 'verify_email';
                    break;
                case 'change-password':
                case 'alterar-senha':
                    $_GET['action'] = 'change_password';
                    break;
                case 'update-profile':
                case 'atualizar-perfil':
                    $_GET['action'] = 'update_profile';
                    break;
                default:
                    $_GET['action'] = $action ?: 'profile';
            }
            
            if($id) $_GET['id'] = $id;
            $controller->processarRequisicao();
            break;
            
        case 'noticias':
        case 'news':
            require_once __DIR__ . '/controllers/NoticiaController.php';
            $controller = new NoticiaController();
            
            // Definir ação baseada no endpoint
            switch($action) {
                case 'create':
                case 'criar':
                    $_GET['action'] = 'create';
                    break;
                case 'update':
                case 'atualizar':
                    $_GET['action'] = 'update';
                    break;
                case 'delete':
                case 'excluir':
                    $_GET['action'] = 'delete';
                    break;
                case 'publish':
                case 'publicar':
                    $_GET['action'] = 'publish';
                    break;
                case 'unpublish':
                case 'despublicar':
                    $_GET['action'] = 'unpublish';
                    break;
                case 'feature':
                case 'destacar':
                    $_GET['action'] = 'feature';
                    break;
                case 'like':
                case 'curtir':
                    $_GET['action'] = 'like';
                    break;
                case 'view':
                case 'visualizar':
                    $_GET['action'] = 'view';
                    break;
                case 'search':
                case 'buscar':
                    $_GET['action'] = 'search';
                    break;
                case 'related':
                case 'relacionadas':
                    $_GET['action'] = 'related';
                    break;
                case 'popular':
                case 'populares':
                    $_GET['action'] = 'popular';
                    break;
                case 'recent':
                case 'recentes':
                    $_GET['action'] = 'recent';
                    break;
                case 'featured':
                case 'destaques':
                    $_GET['action'] = 'featured';
                    break;
                case 'by-category':
                case 'por-categoria':
                    $_GET['action'] = 'by-category';
                    break;
                case 'by-tag':
                case 'por-tag':
                    $_GET['action'] = 'by-tag';
                    break;
                case 'by-author':
                case 'por-autor':
                    $_GET['action'] = 'by-author';
                    break;
                case 'stats':
                case 'estatisticas':
                    $_GET['action'] = 'stats';
                    break;
                case 'list':
                case 'listar':
                    $_GET['action'] = 'list';
                    break;
                default:
                    if($action && !is_numeric($action)) {
                        // Se action não é numérico, pode ser um slug
                        $_GET['action'] = 'get';
                        $_GET['slug'] = $action;
                    } elseif($action) {
                        // Se é numérico, é um ID
                        $_GET['action'] = 'get';
                        $_GET['id'] = $action;
                    } else {
                        $_GET['action'] = 'list';
                    }
            }
            
            if($id) $_GET['id'] = $id;
            $controller->processarRequisicao();
            break;
            
        case 'categorias':
        case 'categories':
            require_once __DIR__ . '/controllers/CategoriaController.php';
            $controller = new CategoriaController();
            
            switch($action) {
                case 'create':
                case 'criar':
                    $_GET['action'] = 'create';
                    break;
                case 'update':
                case 'atualizar':
                    $_GET['action'] = 'update';
                    break;
                case 'delete':
                case 'excluir':
                    $_GET['action'] = 'delete';
                    break;
                case 'status':
                    $_GET['action'] = 'status';
                    break;
                case 'reorder':
                case 'reordenar':
                    $_GET['action'] = 'reorder';
                    break;
                case 'stats':
                case 'estatisticas':
                    $_GET['action'] = 'stats';
                    break;
                default:
                    if($action && !is_numeric($action)) {
                        $_GET['action'] = 'get';
                        $_GET['slug'] = $action;
                    } elseif($action) {
                        $_GET['action'] = 'get';
                        $_GET['id'] = $action;
                    } else {
                        $_GET['action'] = 'list';
                    }
            }
            
            if($id) $_GET['id'] = $id;
            $controller->processarRequisicao();
            break;
            
        case 'tags':
            require_once __DIR__ . '/controllers/TagController.php';
            $controller = new TagController();
            
            switch($action) {
                case 'create':
                case 'criar':
                    $_GET['action'] = 'create';
                    break;
                case 'update':
                case 'atualizar':
                    $_GET['action'] = 'update';
                    break;
                case 'delete':
                case 'excluir':
                    $_GET['action'] = 'delete';
                    break;
                case 'search':
                case 'buscar':
                    $_GET['action'] = 'search';
                    break;
                case 'popular':
                case 'populares':
                    $_GET['action'] = 'popular';
                    break;
                case 'cloud':
                case 'nuvem':
                    $_GET['action'] = 'cloud';
                    break;
                case 'related':
                case 'relacionadas':
                    $_GET['action'] = 'related';
                    break;
                default:
                    if($action && !is_numeric($action)) {
                        $_GET['action'] = 'get';
                        $_GET['slug'] = $action;
                    } elseif($action) {
                        $_GET['action'] = 'get';
                        $_GET['id'] = $action;
                    } else {
                        $_GET['action'] = 'list';
                    }
            }
            
            if($id) $_GET['id'] = $id;
            $controller->processarRequisicao();
            break;
            
        case 'comentarios':
        case 'comments':
            require_once __DIR__ . '/controllers/ComentarioController.php';
            $controller = new ComentarioController();
            
            switch($action) {
                case 'create':
                case 'criar':
                    $_GET['action'] = 'create';
                    break;
                case 'update':
                case 'atualizar':
                    $_GET['action'] = 'update';
                    break;
                case 'delete':
                case 'excluir':
                    $_GET['action'] = 'delete';
                    break;
                case 'approve':
                case 'aprovar':
                    $_GET['action'] = 'approve';
                    break;
                case 'reject':
                case 'rejeitar':
                    $_GET['action'] = 'reject';
                    break;
                case 'like':
                case 'curtir':
                    $_GET['action'] = 'like';
                    break;
                case 'by-news':
                case 'por-noticia':
                    $_GET['action'] = 'by-news';
                    break;
                default:
                    $_GET['action'] = $action ?: 'list';
            }
            
            if($id) $_GET['id'] = $id;
            $controller->processarRequisicao();
            break;
            
        case 'upload':
            require_once __DIR__ . '/controllers/UploadController.php';
            /** @var UploadController $controller */
            $controller = new UploadController();
            
            switch($action) {
                case 'image':
                case 'imagem':
                    $_GET['action'] = 'image';
                    break;
                case 'video':
                    $_GET['action'] = 'video';
                    break;
                case 'audio':
                    $_GET['action'] = 'audio';
                    break;
                case 'document':
                case 'documento':
                    $_GET['action'] = 'document';
                    break;
                case 'multiple':
                case 'multiplo':
                    $_GET['action'] = 'multiple';
                    break;
                case 'delete':
                case 'excluir':
                    $_GET['action'] = 'delete';
                    break;
                case 'list':
                case 'listar':
                    $_GET['action'] = 'list';
                    break;
                default:
                    $_GET['action'] = 'image'; // Padrão para imagem
            }
            
            $controller->processarRequisicao();
            break;
            
        case 'admin':
            require_once __DIR__ . '/controllers/AdminController.php';
            $controller = new AdminController();
            
            switch($action) {
                case 'dashboard':
                    $_GET['action'] = 'dashboard';
                    break;
                case 'stats':
                case 'estatisticas':
                    $_GET['action'] = 'stats';
                    break;
                case 'users':
                case 'usuarios':
                    $_GET['action'] = 'users';
                    break;
                case 'settings':
                case 'configuracoes':
                    $_GET['action'] = 'settings';
                    break;
                case 'logs':
                    $_GET['action'] = 'logs';
                    break;
                default:
                    $_GET['action'] = 'dashboard';
            }
            
            if($id) $_GET['id'] = $id;
            $controller->processarRequisicao();
            break;
            
        case 'notificacoes':
        case 'notifications':
            require_once __DIR__ . '/controllers/NotificacaoController.php';
            $controller = new NotificacaoController();
            
            switch($action) {
                case 'list':
                case 'listar':
                    $_GET['action'] = 'list';
                    break;
                case 'read':
                case 'ler':
                    $_GET['action'] = 'read';
                    break;
                case 'create':
                case 'criar':
                    $_GET['action'] = 'create';
                    break;
                case 'delete':
                case 'excluir':
                    $_GET['action'] = 'delete';
                    break;
                case 'count':
                case 'contar':
                    $_GET['action'] = 'count';
                    break;
                default:
                    $_GET['action'] = $action ?: 'list';
            }
            
            if($id) $_GET['id'] = $id;
            $controller->processarRequisicao();
            break;
            
        case 'newsletter':
            require_once __DIR__ . '/controllers/NewsletterController.php';
            $controller = new NewsletterController();
            
            switch($action) {
                case 'subscribe':
                case 'inscrever':
                    $_GET['action'] = 'subscribe';
                    break;
                case 'unsubscribe':
                case 'desinscrever':
                    $_GET['action'] = 'unsubscribe';
                    break;
                case 'confirm':
                case 'confirmar':
                    $_GET['action'] = 'confirm';
                    break;
                default:
                    $_GET['action'] = $action ?: 'subscribe';
            }
            
            $controller->processarRequisicao();
            break;
            
        case 'rss':
        case 'feed':
            require_once __DIR__ . '/controllers/RSSController.php';
            $controller = new RSSController();
            $controller->gerarFeed();
            break;
            
        case 'sitemap':
        case 'sitemap.xml':
            require_once __DIR__ . '/controllers/SitemapController.php';
            $controller = new SitemapController();
            $controller->gerarSitemap();
            break;
            
        case 'search':
        case 'busca':
            require_once __DIR__ . '/controllers/SearchController.php';
            $controller = new SearchController();
            $controller->buscar();
            break;
            
        case 'api':
            // Endpoint para informações da API
            jsonResponse([
                'nome' => 'Portal de Notícias API',
                'versao' => '1.0.0',
                'status' => 'ativo',
                'endpoints' => [
                    'auth' => '/auth/{action}',
                    'noticias' => '/noticias/{action}',
                    'categorias' => '/categorias/{action}',
                    'tags' => '/tags/{action}',
                    'comentarios' => '/comentarios/{action}',
                    'upload' => '/upload/{action}',
                    'admin' => '/admin/{action}',
                    'notificacoes' => '/notificacoes/{action}',
                    'newsletter' => '/newsletter/{action}',
                    'rss' => '/rss',
                    'sitemap' => '/sitemap',
                    'search' => '/search'
                ],
                'documentacao' => BASE_URL . '/docs'
            ]);
            break;
            
        case 'health':
        case 'status':
            // Endpoint para verificar saúde da API
            try {
                $database = new Database();
                $db = $database->getConnection();
                $db->query('SELECT 1');
                
                jsonResponse([
                    'status' => 'ok',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'database' => 'conectado',
                    'version' => '1.0.0'
                ]);
            } catch(Exception $e) {
                jsonResponse([
                    'status' => 'erro',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'database' => 'erro de conexão',
                    'error' => $e->getMessage()
                ], 500);
            }
            break;
            
        case '':
            // Página inicial da API
            jsonResponse([
                'mensagem' => 'Bem-vindo à API do Portal de Notícias',
                'versao' => '1.0.0',
                'documentacao' => BASE_URL . '/docs',
                'endpoints_principais' => [
                    'noticias' => BASE_URL . '/noticias',
                    'categorias' => BASE_URL . '/categorias',
                    'tags' => BASE_URL . '/tags',
                    'auth' => BASE_URL . '/auth'
                ]
            ]);
            break;
            
        default:
            jsonResponse([
                'erro' => 'Endpoint não encontrado',
                'recurso_solicitado' => $resource,
                'endpoints_disponiveis' => [
                    'auth', 'noticias', 'categorias', 'tags', 
                    'comentarios', 'upload', 'admin', 'notificacoes', 
                    'newsletter', 'rss', 'sitemap', 'search', 'api', 'health'
                ]
            ], 404);
    }
    
} catch(Exception $e) {
    logError('Erro na API: ' . $e->getMessage());
    jsonResponse([
        'erro' => 'Erro interno do servidor',
        'mensagem' => APP_CONFIG['environment'] === 'development' ? $e->getMessage() : 'Tente novamente mais tarde'
    ], 500);
}
?>