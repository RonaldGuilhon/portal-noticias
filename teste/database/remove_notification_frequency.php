<?php
/**
 * Script para remover a coluna notification_frequency
 * Baseado na análise que confirmou que a coluna não está sendo utilizada
 */

// Configurações do banco de dados
$host = 'localhost';
$dbname = 'portal_noticias';
$username = 'root';
$password = '';

echo "=== REMOÇÃO DA COLUNA notification_frequency ===\n\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Verificar se a coluna existe
    echo "1. VERIFICANDO EXISTÊNCIA DA COLUNA...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'notification_frequency'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$column) {
        echo "   ✓ Coluna notification_frequency não existe. Nada a fazer.\n";
        exit;
    }
    
    echo "   ✓ Coluna encontrada: {$column['Type']}\n\n";
    
    // 2. Backup dos dados atuais (por segurança)
    echo "2. FAZENDO BACKUP DOS DADOS...\n";
    $stmt = $pdo->query("SELECT id, notification_frequency FROM usuarios WHERE notification_frequency IS NOT NULL");
    $backupData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $backupFile = 'backup_notification_frequency_' . date('Y-m-d_H-i-s') . '.json';
    file_put_contents($backupFile, json_encode($backupData, JSON_PRETTY_PRINT));
    echo "   ✓ Backup salvo em: {$backupFile}\n";
    echo "   ✓ Registros no backup: " . count($backupData) . "\n\n";
    
    // 3. Remover a coluna
    echo "3. REMOVENDO A COLUNA...\n";
    $pdo->exec("ALTER TABLE usuarios DROP COLUMN notification_frequency");
    echo "   ✓ Coluna notification_frequency removida com sucesso!\n\n";
    
    // 4. Verificar se a remoção foi bem-sucedida
    echo "4. VERIFICANDO REMOÇÃO...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'notification_frequency'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$column) {
        echo "   ✓ Confirmado: Coluna notification_frequency foi removida\n\n";
    } else {
        echo "   ❌ Erro: Coluna ainda existe!\n\n";
        exit(1);
    }
    
    // 5. Verificar estrutura atual da tabela
    echo "5. ESTRUTURA ATUAL DA TABELA USUARIOS:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Colunas restantes (" . count($columns) . " total):\n";
    foreach ($columns as $col) {
        echo "   - {$col['Field']} ({$col['Type']})\n";
    }
    
    echo "\n=== REMOÇÃO CONCLUÍDA COM SUCESSO ===\n";
    echo "\nPRÓXIMOS PASSOS:\n";
    echo "1. Remover referências no código PHP:\n";
    echo "   - AuthController.php (linhas 550, 602, 1021, 1033)\n";
    echo "   - Usuario.php (linhas 60, 228, 297, 369)\n";
    echo "2. Testar o sistema para garantir funcionamento\n";
    echo "3. Atualizar documentação se necessário\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
?>