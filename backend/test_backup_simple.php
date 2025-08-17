<?php
/**
 * Teste Simples do Sistema de Backup
 * Portal de Notícias
 */

// Definir cabeçalho para evitar problemas de encoding
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Teste Simples do Sistema de Backup</h2>";

try {
    echo "<h3>1. Testando includes...</h3>";
    
    // Testar includes um por vez
    echo "Carregando config/database.php...<br>";
    require_once __DIR__ . '/config/database.php';
    echo "✓ Database carregado<br>";
    
    echo "Carregando config/config.php...<br>";
    require_once __DIR__ . '/config/config.php';
    echo "✓ Config carregado<br>";
    
    echo "Carregando utils/BackupManager.php...<br>";
    require_once __DIR__ . '/utils/BackupManager.php';
    echo "✓ BackupManager carregado<br>";
    
    echo "<h3>2. Testando conexão com banco...</h3>";
    $database = new Database();
    $db = $database->getConnection();
    echo "✓ Conexão com banco estabelecida<br>";
    
    echo "<h3>3. Testando BackupManager...</h3>";
    $backup_manager = new BackupManager();
    echo "✓ BackupManager instanciado<br>";
    
    echo "<h3>4. Testando mysqldump...</h3>";
    $mysqldump_available = BackupManager::checkMysqldumpAvailable();
    echo "mysqldump disponível: " . ($mysqldump_available ? 'SIM' : 'NÃO') . "<br>";
    
    echo "<h3>5. Testando diretórios...</h3>";
    $backup_dir = __DIR__ . '/backups/';
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0755, true);
        echo "✓ Diretório de backup criado<br>";
    } else {
        echo "✓ Diretório de backup já existe<br>";
    }
    
    echo "<h3>6. Testando estatísticas...</h3>";
    $stats = $backup_manager->getBackupStats();
    echo "<pre>" . print_r($stats, true) . "</pre>";
    
    echo "<h3>✅ Todos os testes básicos passaram!</h3>";
    
} catch (Exception $e) {
    echo "<h3>❌ Erro encontrado:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
    echo "<h4>Stack Trace:</h4>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "<h3>❌ Erro fatal encontrado:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
    echo "<h4>Stack Trace:</h4>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<p><em>Teste concluído em " . date('Y-m-d H:i:s') . "</em></p>";
?>