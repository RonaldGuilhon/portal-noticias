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
define('BACKEND_PORT', $_ENV['BACKEND_PORT'] ?? '8080');
define('FRONTEND_URL', $_ENV['FRONTEND_URL'] ?? 'http://localhost:8000');
define('BACKEND_URL', $_ENV['BACKEND_URL'] ?? 'http://localhost:8080');

// CORS
define('CORS_ORIGINS', $_ENV['CORS_ORIGINS'] ?? 'http://localhost:8000');

// Upload
define('UPLOAD_PATH', $_ENV['UPLOAD_PATH'] ?? 'backend/uploads');
define('UPLOAD_URL', $_ENV['UPLOAD_URL'] ?? 'http://localhost:8080/backend/uploads');
define('UPLOAD_MAX_SIZE', $_ENV['UPLOAD_MAX_SIZE'] ?? '10485760');

// JWT
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'default_secret_key');
define('JWT_EXPIRATION', $_ENV['JWT_EXPIRATION'] ?? '3600');

// Email
define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? 'localhost');
define('MAIL_PORT', $_ENV['MAIL_PORT'] ?? '1025');
define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
define('MAIL_FROM', $_ENV['MAIL_FROM'] ?? 'noreply@localhost');

// Cache
define('CACHE_ENABLED', filter_var($_ENV['CACHE_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('CACHE_TTL', $_ENV['CACHE_TTL'] ?? '3600');

// Logs
define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'debug');
define('LOG_PATH', $_ENV['LOG_PATH'] ?? 'backend/logs');

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

echo "Configuração carregada para ambiente: " . APP_ENV . "\n";
echo "Frontend URL: " . FRONTEND_URL . "\n";
echo "Backend URL: " . BACKEND_URL . "\n";
echo "CORS Origins: " . CORS_ORIGINS . "\n";
?>