<?php
/**
 * Configuração para Desenvolvimento Local
 * Portal de Notícias
 * 
 * Este arquivo contém configurações específicas para o ambiente de desenvolvimento.
 * Copie este arquivo e ajuste as configurações conforme necessário.
 */

// Configurações de erro para desenvolvimento
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Configurações de upload
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

// Configurações do banco de dados para desenvolvimento
define('DB_CONFIG', [
    'host' => 'localhost',
    'port' => '3306',
    'dbname' => 'portal_noticias',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
]);

// Configurações da aplicação
define('APP_CONFIG', [
    'name' => 'Portal de Notícias - DEV',
    'version' => '1.0.0-dev',
    'environment' => 'development',
    'debug' => true,
    'timezone' => 'America/Sao_Paulo',
    'locale' => 'pt_BR',
    'base_url' => 'http://localhost/portal-noticias',
    'admin_email' => 'admin@localhost.dev'
]);

// Configurações de segurança (desenvolvimento)
define('SECURITY_CONFIG', [
    'jwt_secret' => 'dev-jwt-secret-key-change-in-production',
    'encryption_key' => 'dev-encryption-key-32-chars-long',
    'session_name' => 'PORTAL_DEV_SESSION',
    'csrf_token_name' => 'csrf_token',
    'password_min_length' => 6, // Menor para desenvolvimento
    'login_attempts_max' => 10, // Mais tentativas para desenvolvimento
    'login_lockout_time' => 300, // 5 minutos
    'session_lifetime' => 7200 // 2 horas
]);

// Configurações de email (desenvolvimento - usar Mailtrap ou similar)
define('EMAIL_CONFIG', [
    'method' => 'smtp', // smtp ou mail
    'smtp_host' => 'smtp.mailtrap.io',
    'smtp_port' => 2525,
    'smtp_username' => '', // Configurar no .env
    'smtp_password' => '', // Configurar no .env
    'smtp_secure' => 'tls',
    'from_email' => 'noreply@localhost.dev',
    'from_name' => 'Portal de Notícias DEV',
    'reply_to' => 'admin@localhost.dev'
]);

// Configurações de upload
define('UPLOAD_CONFIG', [
    'path' => __DIR__ . '/backend/uploads',
    'url' => APP_CONFIG['base_url'] . '/backend/uploads',
    'max_size' => 10 * 1024 * 1024, // 10MB
    'allowed_types' => [
        'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'documents' => ['pdf', 'doc', 'docx', 'txt'],
        'archives' => ['zip', 'rar']
    ],
    'image_quality' => 85,
    'create_thumbnails' => true,
    'thumbnail_sizes' => [
        'small' => [150, 150],
        'medium' => [300, 300],
        'large' => [800, 600]
    ]
]);

// Configurações de cache (desenvolvimento - desabilitado)
define('CACHE_CONFIG', [
    'enabled' => false,
    'type' => 'file', // file, redis, memcached
    'ttl' => 3600,
    'path' => __DIR__ . '/backend/cache'
]);

// Configurações de log
define('LOG_CONFIG', [
    'enabled' => true,
    'level' => 'debug', // debug, info, warning, error
    'path' => __DIR__ . '/backend/logs',
    'max_files' => 30,
    'max_size' => 10 * 1024 * 1024 // 10MB
]);

// Configurações de API
define('API_CONFIG', [
    'rate_limit' => 1000, // requests per hour
    'cors_enabled' => true,
    'cors_origins' => ['http://localhost', 'http://127.0.0.1'],
    'api_key_required' => false // Para desenvolvimento
]);

// Configurações específicas do portal
define('PORTAL_CONFIG', [
    'news_per_page' => 12,
    'comments_enabled' => true,
    'comments_moderation' => false, // Sem moderação em dev
    'newsletter_enabled' => true,
    'ads_enabled' => true,
    'maintenance_mode' => false,
    'registration_enabled' => true,
    'social_login' => false // Desabilitado para desenvolvimento
]);

// Configurações de desenvolvimento específicas
define('DEV_CONFIG', [
    'show_debug_bar' => true,
    'log_queries' => true,
    'fake_email_sending' => true, // Não enviar emails reais
    'seed_database' => true, // Permitir popular BD com dados de teste
    'auto_login_admin' => false, // Login automático como admin
    'disable_csrf' => false, // Manter CSRF mesmo em dev
    'mock_external_apis' => true // Simular APIs externas
]);

// Função para carregar configurações do .env se existir
function loadEnvConfig() {
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, '"\' ');
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// Carregar variáveis de ambiente
loadEnvConfig();

// Sobrescrever configurações com variáveis de ambiente se existirem
if (isset($_ENV['DB_HOST'])) {
    $dbConfig = DB_CONFIG;
    $dbConfig['host'] = $_ENV['DB_HOST'];
    $dbConfig['port'] = $_ENV['DB_PORT'] ?? '3306';
    $dbConfig['dbname'] = $_ENV['DB_NAME'] ?? 'portal_noticias';
    $dbConfig['username'] = $_ENV['DB_USER'] ?? 'root';
    $dbConfig['password'] = $_ENV['DB_PASS'] ?? '';
    define('DB_CONFIG_OVERRIDE', $dbConfig);
}

if (isset($_ENV['SMTP_HOST'])) {
    $emailConfig = EMAIL_CONFIG;
    $emailConfig['smtp_host'] = $_ENV['SMTP_HOST'];
    $emailConfig['smtp_port'] = $_ENV['SMTP_PORT'] ?? 587;
    $emailConfig['smtp_username'] = $_ENV['SMTP_USER'] ?? '';
    $emailConfig['smtp_password'] = $_ENV['SMTP_PASS'] ?? '';
    $emailConfig['smtp_secure'] = $_ENV['SMTP_SECURE'] ?? 'tls';
    define('EMAIL_CONFIG_OVERRIDE', $emailConfig);
}

// Configurar timezone
date_default_timezone_set(APP_CONFIG['timezone']);

// Configurar locale
if (function_exists('setlocale')) {
    setlocale(LC_ALL, APP_CONFIG['locale']);
}

// Função helper para obter configuração
function getConfig($section, $key = null, $default = null) {
    $configName = strtoupper($section) . '_CONFIG';
    
    // Verificar se existe override
    $overrideName = $configName . '_OVERRIDE';
    if (defined($overrideName)) {
        $config = constant($overrideName);
    } elseif (defined($configName)) {
        $config = constant($configName);
    } else {
        return $default;
    }
    
    if ($key === null) {
        return $config;
    }
    
    return isset($config[$key]) ? $config[$key] : $default;
}

// Função helper para verificar se está em desenvolvimento
function isDevelopment() {
    return APP_CONFIG['environment'] === 'development';
}

// Função helper para debug
function debugLog($message, $data = null) {
    if (isDevelopment() && DEV_CONFIG['log_queries']) {
        $logMessage = '[DEBUG] ' . $message;
        if ($data !== null) {
            $logMessage .= ' - Data: ' . print_r($data, true);
        }
        error_log($logMessage);
    }
}

// Função para inicializar o ambiente de desenvolvimento
function initDevelopmentEnvironment() {
    // Criar diretórios necessários
    $dirs = [
        __DIR__ . '/backend/logs',
        __DIR__ . '/backend/cache',
        __DIR__ . '/backend/uploads',
        __DIR__ . '/backend/uploads/noticias',
        __DIR__ . '/backend/uploads/usuarios',
        __DIR__ . '/backend/uploads/anuncios',
        __DIR__ . '/backend/uploads/temp'
    ];
    
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    // Criar arquivo .htaccess para uploads se não existir
    $htaccessPath = __DIR__ . '/backend/uploads/.htaccess';
    if (!file_exists($htaccessPath)) {
        $htaccessContent = "# Proteger diretório de uploads\n";
        $htaccessContent .= "Options -Indexes\n";
        $htaccessContent .= "<Files *.php>\n";
        $htaccessContent .= "    Deny from all\n";
        $htaccessContent .= "</Files>\n";
        file_put_contents($htaccessPath, $htaccessContent);
    }
    
    // Log de inicialização
    if (LOG_CONFIG['enabled']) {
        $logFile = LOG_CONFIG['path'] . '/app-' . date('Y-m-d') . '.log';
        $logMessage = '[' . date('Y-m-d H:i:s') . '] Ambiente de desenvolvimento inicializado\n';
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// Inicializar ambiente se necessário
if (DEV_CONFIG['show_debug_bar'] || !file_exists(__DIR__ . '/backend/logs')) {
    initDevelopmentEnvironment();
}

// Exibir informações de debug se habilitado
if (DEV_CONFIG['show_debug_bar'] && php_sapi_name() !== 'cli') {
    register_shutdown_function(function() {
        if (isDevelopment()) {
            echo "\n<!-- DEBUG INFO -->\n";
            echo "<!-- Environment: " . APP_CONFIG['environment'] . " -->\n";
            echo "<!-- Memory Usage: " . memory_get_peak_usage(true) / 1024 / 1024 . " MB -->\n";
            echo "<!-- Execution Time: " . (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) . " seconds -->\n";
        }
    });
}

?>