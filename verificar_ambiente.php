<?php
require_once 'config-unified.php';

echo "\n========================================\n";
echo "   VERIFICAÇÃO DE AMBIENTE\n";
echo "========================================\n";

echo "Ambiente: " . APP_ENV . "\n";
echo "Nome da App: " . APP_NAME . "\n";
echo "Debug: " . (APP_DEBUG ? 'Ativado' : 'Desativado') . "\n";
echo "\n--- URLs ---\n";
echo "Frontend: " . FRONTEND_URL . "\n";
echo "Backend: " . BACKEND_URL . "\n";
echo "\n--- Banco de Dados ---\n";
echo "Host: " . DB_HOST . "\n";
echo "Database: " . DB_NAME . "\n";
echo "User: " . DB_USER . "\n";
echo "\n--- CORS ---\n";
echo "Origins: " . CORS_ORIGINS . "\n";
echo "\n--- Upload ---\n";
echo "Path: " . UPLOAD_PATH . "\n";
echo "URL: " . UPLOAD_URL . "\n";
echo "Max Size: " . number_format(UPLOAD_MAX_SIZE / 1024 / 1024, 2) . " MB\n";

// Testar conexão com banco
echo "\n--- Teste de Conexão ---\n";
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✓ Conexão com banco de dados: OK\n";
} catch (Exception $e) {
    echo "✗ Erro na conexão: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
?>