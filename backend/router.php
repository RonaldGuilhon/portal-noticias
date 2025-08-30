<?php
// Configurar charset UTF-8 globalmente
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config-unified.php';

// Função para configurar CORS dinâmico
function setupCORS() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    if (isOriginAllowed($origin)) {
        header("Access-Control-Allow-Origin: $origin");
    } else {
        // Fallback para o primeiro origin permitido
        $allowedOrigins = getCorsOrigins();
        header("Access-Control-Allow-Origin: " . $allowedOrigins[0]);
    }
    
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");
}

// Configurar CORS
setupCORS();

// Log de debug para requisições
error_log("[ROUTER DEBUG] Requisição recebida: {$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']}");
error_log("[ROUTER DEBUG] Origin: " . ($_SERVER['HTTP_ORIGIN'] ?? 'não definido'));

// Tratar requisições OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    error_log("[ROUTER DEBUG] Requisição OPTIONS processada");
    http_response_code(200);
    exit();
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Remove a barra inicial se existir
$uri = ltrim($uri, '/');

// Roteamento para API de usuário
if (preg_match('/^api\/user\/profile\/?$/', $uri)) {
    $_GET['action'] = 'profile';
    require_once 'controllers/AuthController.php';
    $controller = new AuthController();
    $controller->processarRequisicao();
    return;
}

// Roteamento para upload de avatar
if (preg_match('/^api\/user\/avatar\/?$/', $uri)) {
    $_GET['action'] = 'upload_avatar';
    require_once 'controllers/AuthController.php';
    $controller = new AuthController();
    $controller->processarRequisicao();
    return;
}

// Roteamento para alteração de senha
if (preg_match('/^api\/user\/change-password\/?$/', $uri)) {
    $_GET['action'] = 'change-password';
    require_once 'controllers/AuthController.php';
    $controller = new AuthController();
    $controller->processarRequisicao();
    return;
}

// Roteamento para preferências individuais
if (preg_match('/^api\/user\/preferences\/individual\/?$/', $uri)) {
    $_GET['action'] = 'individual-preference';
    require_once 'controllers/AuthController.php';
    $controller = new AuthController();
    $controller->processarRequisicao();
    return;
}

// Roteamento para preferências gerais
if (preg_match('/^api\/user\/preferences\/?$/', $uri)) {
    $_GET['action'] = 'preferences';
    require_once 'controllers/AuthController.php';
    $controller = new AuthController();
    $controller->processarRequisicao();
    return;
}

// Roteamento para categorias favoritas
if (preg_match('/^api\/user\/preferences\/categories\/?$/', $uri)) {
    $_GET['action'] = 'favorite-categories';
    require_once 'controllers/AuthController.php';
    $controller = new AuthController();
    $controller->processarRequisicao();
    return;
}

// Roteamento para autenticação
if (preg_match('/^auth\/login\/?$/', $uri)) {
    $_GET['action'] = 'login';
    require_once 'controllers/AuthController.php';
    $controller = new AuthController();
    $controller->processarRequisicao();
    return;
}

if (preg_match('/^auth\/register\/?$/', $uri)) {
    $_GET['action'] = 'register';
    require_once 'controllers/AuthController.php';
    $controller = new AuthController();
    $controller->processarRequisicao();
    return;
}

if (preg_match('/^auth\/logout\/?$/', $uri)) {
    $_GET['action'] = 'logout';
    require_once 'controllers/AuthController.php';
    $controller = new AuthController();
    $controller->processarRequisicao();
    return;
}

if (preg_match('/^auth\/check-auth\/?$/', $uri)) {
    $_GET['action'] = 'check-auth';
    require_once 'controllers/AuthController.php';
    $controller = new AuthController();
    $controller->processarRequisicao();
    return;
}

if (preg_match('/^auth\/check-email\/?$/', $uri)) {
    $_GET['action'] = 'check-email';
    require_once 'controllers/AuthController.php';
    $controller = new AuthController();
    $controller->processarRequisicao();
    return;
}

if (preg_match('/^auth\/forgot-password\/?$/', $uri)) {
    $_GET['action'] = 'forgotPassword';
    require_once 'controllers/AuthController.php';
    $controller = new AuthController();
    $controller->processarRequisicao();
    return;
}

// Roteamento para notícias
if (preg_match('/^noticias\/destaques\/?$/', $uri)) {
    error_log("[ROUTER DEBUG] Rota de notícias em destaque acessada");
    $_GET['action'] = 'featured';
    require_once 'controllers/NoticiaController.php';
    $controller = new NoticiaController();
    $controller->processarRequisicao();
    return;
}

if (preg_match('/^noticias\/populares\/?$/', $uri)) {
    $_GET['action'] = 'popular';
    require_once 'controllers/NoticiaController.php';
    $controller = new NoticiaController();
    $controller->processarRequisicao();
    return;
}

if (preg_match('/^noticias\/recentes\/?$/', $uri)) {
    $_GET['action'] = 'recent';
    require_once 'controllers/NoticiaController.php';
    $controller = new NoticiaController();
    $controller->processarRequisicao();
    return;
}

if (preg_match('/^noticias\/?$/', $uri)) {
    $_GET['action'] = 'list';
    require_once 'controllers/NoticiaController.php';
    $controller = new NoticiaController();
    $controller->processarRequisicao();
    return;
}

if (preg_match('/^noticias\/([0-9]+)\/?$/', $uri, $matches)) {
    $_GET['action'] = 'obter';
    $_GET['id'] = $matches[1];
    require_once 'controllers/NoticiaController.php';
    return;
}

if (preg_match('/^noticias\/categoria\/([^\/\?]+)\/?$/', $uri, $matches)) {
    $_GET['action'] = 'listarPorCategoria';
    $_GET['slug'] = $matches[1];
    require_once 'controllers/NoticiaController.php';
    $controller = new NoticiaController();
    $controller->processarRequisicao();
    return;
}

if (preg_match('/^noticias\/slug\/([^\/\?]+)\/?$/', $uri, $matches)) {
    $_GET['action'] = 'get';
    $_GET['slug'] = $matches[1];
    require_once 'controllers/NoticiaController.php';
    $controller = new NoticiaController();
    $controller->processarRequisicao();
    return;
}

if (preg_match('/^noticias\/([^\/]+)\/?$/', $uri, $matches)) {
    $_GET['action'] = 'obter';
    $_GET['slug'] = $matches[1];
    require_once 'controllers/NoticiaController.php';
    return;
}

// Roteamento para categorias
if (preg_match('/^categorias\/?$/', $uri)) {
    require_once 'controllers/CategoriaController.php';
    $controller = new CategoriaController();
    $controller->processarRequisicao();
    return;
}

if (preg_match('/^categorias\/([0-9]+)\/?$/', $uri, $matches)) {
    $_GET['action'] = 'get';
    $_GET['id'] = $matches[1];
    require_once 'controllers/CategoriaController.php';
    $controller = new CategoriaController();
    $controller->processarRequisicao();
    return;
}

if (preg_match('/^categorias\/slug\/([^\/]+)\/?$/', $uri, $matches)) {
    $_GET['action'] = 'get';
    $_GET['slug'] = $matches[1];
    require_once 'controllers/CategoriaController.php';
    $controller = new CategoriaController();
    $controller->processarRequisicao();
    return;
}

// Roteamento para tags
if (preg_match('/^tags\/?$/', $uri)) {
    require_once 'controllers/TagController.php';
    $controller = new TagController();
    $controller->processarRequisicao();
    return;
}

if (preg_match('/^tags\/([0-9]+)\/?$/', $uri, $matches)) {
    $_GET['action'] = 'obter';
    $_GET['id'] = $matches[1];
    require_once 'controllers/TagController.php';
    $controller = new TagController();
    $controller->processarRequisicao();
    return;
}

if (preg_match('/^tags\/([^\/]+)\/?$/', $uri, $matches)) {
    $_GET['action'] = 'obter';
    $_GET['slug'] = $matches[1];
    require_once 'controllers/TagController.php';
    $controller = new TagController();
    $controller->processarRequisicao();
    return;
}

// Roteamento para comentários
if (preg_match('/^comentarios\/?$/', $uri)) {
    $_GET['action'] = 'listar';
    require_once 'controllers/ComentarioController.php';
    return;
}

if (preg_match('/^comentarios\/noticia\/([0-9]+)\/?$/', $uri, $matches)) {
    $_GET['action'] = 'listarPorNoticia';
    $_GET['noticia_id'] = $matches[1];
    require_once 'controllers/ComentarioController.php';
    return;
}

// Roteamento para busca
if (preg_match('/^search\/?$/', $uri)) {
    $_GET['action'] = 'buscar';
    require_once 'controllers/SearchController.php';
    return;
}

// Roteamento para notificações
if (preg_match('/^notificacoes\/?$/', $uri)) {
    $_GET['action'] = 'listar';
    require_once 'controllers/NotificacaoController.php';
    $controller = new NotificacaoController();
    $controller->processarRequisicao();
    return;
}

if (preg_match('/^notificacoes\/([0-9]+)\/read\/?$/', $uri, $matches)) {
    $_GET['action'] = 'marcarComoLida';
    $_GET['id'] = $matches[1];
    require_once 'controllers/NotificacaoController.php';
    $controller = new NotificacaoController();
    $controller->processarRequisicao();
    return;
}

// Roteamento para admin dashboard
if (preg_match('/^admin\/dashboard\/?$/', $uri)) {
    $_GET['action'] = 'dashboard';
    require_once 'controllers/AdminController.php';
    $controller = new AdminController();
    $controller->processarRequisicao();
    return;
}

// Roteamento para admin notícias
if (preg_match('/^admin\/noticias\/?$/', $uri)) {
    $_GET['action'] = 'listar';
    require_once 'controllers/NoticiaController.php';
    $controller = new NoticiaController();
    $controller->processarRequisicao();
    return;
}

// Roteamento para admin activity
if (preg_match('/^admin\/activity\/?$/', $uri)) {
    $_GET['action'] = 'activity';
    require_once 'controllers/AdminController.php';
    $controller = new AdminController();
    $controller->processarRequisicao();
    return;
}

// Roteamento para servir fotos de perfil
if (preg_match('/^files\/profile\/(.+)$/', $uri, $matches)) {
    $_GET['action'] = 'serve_profile_photo';
    $_GET['file'] = $matches[1];
    require_once 'controllers/FileController.php';
    return;
}

// Roteamento para cache
if (preg_match('/^cache\/stats\/?$/', $uri)) {
    $_GET['action'] = 'stats';
    require_once 'controllers/CacheController.php';
    $controller = new CacheController();
    $controller->processarRequisicao();
    return;
}

if (preg_match('/^cache\/clear\/?$/', $uri)) {
    $_GET['action'] = 'clear';
    require_once 'controllers/CacheController.php';
    $controller = new CacheController();
    $controller->processarRequisicao();
    return;
}

if (preg_match('/^cache\/?$/', $uri)) {
    $_GET['action'] = 'stats';
    require_once 'controllers/CacheController.php';
    $controller = new CacheController();
    $controller->processarRequisicao();
    return;
}

// Roteamento para a raiz - redirecionar para frontend
if ($uri === '' || $uri === '/' || $uri === '/index.php') {
    header('Location: /frontend/');
    http_response_code(302);
    exit();
}

// Se não encontrou nenhuma rota, retorna false para que o servidor processe normalmente
return false;
?>