<?php
/**
 * Teste específico para listBackups()
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'utils/BackupManager.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Teste do método listBackups()</h2>";

try {
    echo "<h3>1. Instanciando BackupManager...</h3>";
    $backup_manager = new BackupManager();
    echo "✓ BackupManager instanciado<br>";
    
    echo "<h3>2. Chamando listBackups()...</h3>";
    $backups = $backup_manager->listBackups();
    echo "✓ Método executado<br>";
    
    echo "<h3>3. Resultado:</h3>";
    echo "<p>Tipo: " . gettype($backups) . "</p>";
    echo "<p>Quantidade: " . count($backups) . "</p>";
    
    if (is_array($backups) && count($backups) > 0) {
        echo "<h4>Lista de Backups:</h4>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Nome do Arquivo</th><th>Tamanho</th><th>Tipo</th><th>Data Criação</th><th>Status</th><th>Existe</th></tr>";
        
        foreach ($backups as $backup) {
            echo "<tr>";
            echo "<td>{$backup['id']}</td>";
            echo "<td>{$backup['nome_arquivo']}</td>";
            echo "<td>" . number_format($backup['tamanho']) . " bytes</td>";
            echo "<td>{$backup['tipo']}</td>";
            echo "<td>{$backup['data_criacao']}</td>";
            echo "<td>{$backup['status']}</td>";
            echo "<td>" . ($backup['existe'] ? 'Sim' : 'Não') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ Nenhum backup encontrado ou erro na consulta</p>";
    }
    
    echo "<h3>4. JSON do resultado:</h3>";
    echo "<pre>" . json_encode($backups, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
}

echo "<p><em>Teste executado em " . date('Y-m-d H:i:s') . "</em></p>";
?>