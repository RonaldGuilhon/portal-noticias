<?php
/**
 * Script para executar migração de preferências de usuário
 */

require_once __DIR__ . '/../../config/database.php';

try {
    // Usar a conexão do sistema
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Ler o arquivo SQL
    $sql = file_get_contents(__DIR__ . '/user_preferences.sql');
    
    // Executar as queries
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "Migração de preferências de usuário executada com sucesso!\n";
    
} catch (Exception $e) {
    echo "Erro ao executar migração: " . $e->getMessage() . "\n";
    exit(1);
}
?>