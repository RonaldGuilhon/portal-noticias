<?php
/**
 * Configuração para Desenvolvimento Local
 * Portal de Notícias
 * 
 * Este arquivo facilita a configuração inicial do projeto
 * para desenvolvimento local.
 */

// Verificar se é ambiente de desenvolvimento
if (!defined('DEVELOPMENT_MODE')) {
    define('DEVELOPMENT_MODE', true);
}

// Configurações de desenvolvimento
if (DEVELOPMENT_MODE) {
    // Mostrar todos os erros
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    
    // Configurações de sessão para desenvolvimento
    ini_set('session.cookie_secure', 0);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
}

// Configurações do banco de dados local
$config = [
    'database' => [
        'host' => 'localhost',
        'dbname' => 'portal_noticias',
        'username' => 'root',
        'password' => '', // Deixe vazio se não tiver senha
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],
    
    'app' => [
        'name' => 'Portal de Notícias - Dev',
        'url' => 'http://localhost:8080',
        'env' => 'development',
        'debug' => true,
        'timezone' => 'America/Sao_Paulo'
    ],
    
    'upload' => [
        'path' => __DIR__ . '/backend/uploads',
        'url' => 'http://localhost:8080/backend/uploads',
        'max_size' => 10 * 1024 * 1024, // 10MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'mp3', 'pdf']
    ],
    
    'email' => [
        'enabled' => false, // Desabilitado para desenvolvimento
        'host' => 'localhost',
        'port' => 1025, // MailHog ou similar
        'username' => '',
        'password' => '',
        'encryption' => null,
        'from_email' => 'noreply@localhost',
        'from_name' => 'Portal de Notícias Dev'
    ],
    
    'jwt' => [
        'secret' => 'dev_jwt_secret_key_change_in_production',
        'algorithm' => 'HS256',
        'expiration' => 3600 // 1 hora
    ],
    
    'security' => [
        'password_min_length' => 6,
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutos
        'csrf_protection' => true
    ],
    
    'cache' => [
        'enabled' => false, // Desabilitado para desenvolvimento
        'ttl' => 300 // 5 minutos
    ],
    
    'logs' => [
        'enabled' => true,
        'path' => __DIR__ . '/backend/logs',
        'level' => 'debug'
    ]
];

// Função para criar diretórios necessários
function createDirectories() {
    $directories = [
        __DIR__ . '/backend/uploads',
        __DIR__ . '/backend/uploads/noticias',
        __DIR__ . '/backend/uploads/usuarios',
        __DIR__ . '/backend/uploads/anuncios',
        __DIR__ . '/backend/uploads/temp',
        __DIR__ . '/backend/logs'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "Diretório criado: {$dir}\n";
        }
    }
}

// Função para verificar requisitos
function checkRequirements() {
    $requirements = [
        'PHP >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
        'GD Extension' => extension_loaded('gd'),
        'mbstring Extension' => extension_loaded('mbstring'),
        'OpenSSL Extension' => extension_loaded('openssl'),
        'cURL Extension' => extension_loaded('curl'),
        'JSON Extension' => extension_loaded('json')
    ];
    
    $allMet = true;
    echo "\n=== Verificação de Requisitos ===\n";
    
    foreach ($requirements as $requirement => $met) {
        $status = $met ? '✅ OK' : '❌ FALTANDO';
        echo "{$requirement}: {$status}\n";
        if (!$met) $allMet = false;
    }
    
    if (!$allMet) {
        echo "\n⚠️  Alguns requisitos não foram atendidos. Instale as extensões necessárias.\n";
        return false;
    }
    
    echo "\n✅ Todos os requisitos foram atendidos!\n";
    return true;
}

// Função para testar conexão com banco
function testDatabaseConnection($config) {
    try {
        $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}";
        $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password'], $config['database']['options']);
        
        // Testar uma query simples
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM usuarios');
        $result = $stmt->fetch();
        
        echo "✅ Conexão com banco de dados OK! ({$result['count']} usuários encontrados)\n";
        return true;
    } catch (PDOException $e) {
        echo "❌ Erro na conexão com banco: {$e->getMessage()}\n";
        echo "\nVerifique se:\n";
        echo "- O MySQL está rodando\n";
        echo "- O banco 'portal_noticias' existe\n";
        echo "- As credenciais estão corretas\n";
        echo "- O arquivo database/portal_noticias.sql foi importado\n";
        return false;
    }
}

// Função para configuração inicial
function setupDevelopment() {
    global $config;
    
    echo "\n🚀 Configurando Portal de Notícias para Desenvolvimento\n";
    echo "================================================\n";
    
    // Verificar requisitos
    if (!checkRequirements()) {
        return false;
    }
    
    // Criar diretórios
    echo "\n=== Criando Diretórios ===\n";
    createDirectories();
    
    // Testar banco
    echo "\n=== Testando Banco de Dados ===\n";
    if (!testDatabaseConnection($config)) {
        return false;
    }
    
    echo "\n✅ Configuração concluída com sucesso!\n";
    echo "\n🌐 Acesse: http://localhost:8080\n";
    echo "👤 Admin: http://localhost:8080/admin/\n";
    echo "\n📝 Para executar o servidor:\n";
    echo "cd frontend && php -S localhost:8080\n";
    
    return true;
}

// Executar configuração se chamado diretamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    setupDevelopment();
}

return $config;