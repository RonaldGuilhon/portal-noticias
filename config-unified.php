<?php
/**
 * Configuração Unificada para Ambos os Ambientes
 * Carrega configurações do arquivo .env ativo
 */

// Função para carregar arquivo .env
function loadEnvFile($envFile) {
    if (!file_exists($envFile)) {
        throw new Exception("Arquivo .env não encontrado: {$envFile}");
    }
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Pular comentários
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Remover aspas se existirem
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            $value = $matches[1];
        }
        
        $_ENV[$name] = $value;
        putenv("$name=$value");
    }
}

// Detectar ambiente baseado no arquivo .env ativo
$envFile = '.env';
if (file_exists('.env')) {
    loadEnvFile('.env');
} else {
    throw new Exception('Arquivo .env não encontrado. Execute o script de inicialização primeiro.');
}

// Configurações baseadas nas variáveis de ambiente
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('APP_NAME', $_ENV['APP_NAME'] ?? 'Portal de Notícias');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost:8000');

// Banco de Dados
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'portal_noticias');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');

// URLs e Portas
define('FRONTEND_PORT', $_ENV['FRONTEND_PORT'] ?? '8000');
define('BACKEND_PORT', $_ENV['BACKEND_PORT'] ?? '8001');
define('FRONTEND_URL', $_ENV['FRONTEND_URL'] ?? 'http://localhost:8000');
define('BACKEND_URL', $_ENV['BACKEND_URL'] ?? 'http://localhost:8001');

// CORS
define('CORS_ORIGINS', $_ENV['CORS_ORIGINS'] ?? 'http://localhost:8000');

// Upload
define('UPLOAD_PATH', $_ENV['UPLOAD_PATH'] ?? 'backend/uploads');
define('UPLOAD_URL', $_ENV['UPLOAD_URL'] ?? 'http://localhost:8001/backend/uploads');
define('UPLOAD_MAX_SIZE', $_ENV['UPLOAD_MAX_SIZE'] ?? '10485760');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_VIDEO_TYPES', ['mp4', 'avi', 'mov', 'wmv']);
define('ALLOWED_AUDIO_TYPES', ['mp3', 'wav', 'ogg']);
define('ALLOWED_TYPES', array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_VIDEO_TYPES, ALLOWED_AUDIO_TYPES));

// JWT
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'default_secret_key');
define('JWT_EXPIRATION', $_ENV['JWT_EXPIRATION'] ?? '3600');

// Email SMTP
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? '587');
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? 'seu-email@gmail.com');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? 'sua-senha-app');
define('SMTP_SECURE', $_ENV['SMTP_SECURE'] ?? 'tls');
define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@portalnoticias.com');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'Portal de Notícias');
define('FROM_EMAIL', $_ENV['FROM_EMAIL'] ?? 'noreply@portalnoticias.com');
define('FROM_NAME', $_ENV['FROM_NAME'] ?? 'Portal de Notícias');

// Email
define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? 'localhost');
define('MAIL_PORT', $_ENV['MAIL_PORT'] ?? '1025');
define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
define('MAIL_FROM', $_ENV['MAIL_FROM'] ?? 'noreply@localhost');

// Cache
define('CACHE_ENABLED', filter_var($_ENV['CACHE_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('CACHE_TTL', $_ENV['CACHE_TTL'] ?? '3600');
define('CACHE_TIME', 3600); // 1 hora
define('CACHE_CONFIG', [
    'enabled' => true,
    'type' => 'file',
    'ttl' => 3600,
    'path' => __DIR__ . '/backend/cache'
]);

// Logs
define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'debug');
define('LOG_PATH', $_ENV['LOG_PATH'] ?? 'backend/logs');
define('LOGS_PATH', __DIR__ . '/backend/logs/');

// Configurações de erro baseadas no ambiente
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');

// Função para obter origens CORS como array
function getCorsOrigins() {
    return explode(',', CORS_ORIGINS);
}

// Função para verificar se uma origem é permitida
function isOriginAllowed($origin) {
    $allowedOrigins = getCorsOrigins();
    return in_array($origin, $allowedOrigins);
}

// Configurações adicionais baseadas no config antigo
define('PASSWORD_MIN_LENGTH', $_ENV['PASSWORD_MIN_LENGTH'] ?? '6');
define('MAX_LOGIN_ATTEMPTS', $_ENV['MAX_LOGIN_ATTEMPTS'] ?? '5');
define('LOCKOUT_DURATION', $_ENV['LOCKOUT_DURATION'] ?? '900');
define('ITEMS_PER_PAGE', 12);
define('MAX_ITEMS_PER_PAGE', 50);

/**
 * Função para sanitizar dados de entrada
 */
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map('sanitizeInput', $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Função para validar email
 */
if (!function_exists('isValidEmail')) {
    function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

/**
 * Função para gerar token seguro
 */
if (!function_exists('generateSecureToken')) {
    function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
}

/**
 * Função para hash de senha
 */
if (!function_exists('hashPassword')) {
    function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

/**
 * Função para verificar senha
 */
if (!function_exists('verifyPassword')) {
    function verifyPassword($password, $hash) {
        if (strpos($hash, '$2y$') === 0) {
            return password_verify($password, $hash);
        }
        return sha1($password) === $hash;
    }
}

/**
 * Função para gerar slug
 */
if (!function_exists('generateSlug')) {
    function generateSlug($text) {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-');
    }
}

/**
 * Função para log de erros
 */
if (!function_exists('logError')) {
    function logError($message, $file = 'error.log') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
        $logPath = LOG_PATH . '/' . $file;
        if (!is_dir(dirname($logPath))) {
            mkdir(dirname($logPath), 0755, true);
        }
        file_put_contents($logPath, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Função para resposta JSON
 */
if (!function_exists('jsonResponse')) {
    function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Configuração carregada silenciosamente
?>