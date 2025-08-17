<?php
/**
 * Script para Inicializar Tabela de Backups
 * Portal de Notícias
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/config/database.php';

echo "<h2>Inicializando Tabela de Backups</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p>✓ Conexão com banco estabelecida</p>";
    
    // Criar tabela de backups
    $create_table_sql = "
        CREATE TABLE IF NOT EXISTS backups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome_arquivo VARCHAR(255) NOT NULL,
            tamanho BIGINT NOT NULL,
            tipo ENUM('full', 'incremental') NOT NULL,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('success', 'erro') DEFAULT 'success',
            INDEX idx_data_criacao (data_criacao),
            INDEX idx_tipo (tipo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $result = $db->exec($create_table_sql);
    echo "<p>✓ Tabela 'backups' criada/verificada com sucesso</p>";
    
    // Verificar se a tabela foi criada
    $check_table = $db->query("SHOW TABLES LIKE 'backups'");
    $table_exists = $check_table->rowCount() > 0;
    
    if ($table_exists) {
        echo "<p>✅ Tabela 'backups' confirmada no banco de dados</p>";
        
        // Mostrar estrutura da tabela
        echo "<h3>Estrutura da Tabela:</h3>";
        $describe = $db->query("DESCRIBE backups");
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
        
        while ($row = $describe->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "<td>{$row['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Inserir alguns dados de exemplo para teste
        echo "<h3>Inserindo Dados de Exemplo:</h3>";
        
        $sample_data = [
            ['backup_exemplo_1.sql', 1024000, 'full'],
            ['backup_exemplo_2.sql', 512000, 'incremental'],
            ['backup_exemplo_3.sql', 2048000, 'full']
        ];
        
        $insert_sql = "INSERT INTO backups (nome_arquivo, tamanho, tipo) VALUES (?, ?, ?)";
        $stmt = $db->prepare($insert_sql);
        
        foreach ($sample_data as $data) {
            try {
                $stmt->execute($data);
                echo "<p>✓ Inserido: {$data[0]} ({$data[1]} bytes, {$data[2]})</p>";
            } catch (Exception $e) {
                echo "<p>⚠️ Erro ao inserir {$data[0]}: " . $e->getMessage() . "</p>";
            }
        }
        
        // Mostrar dados inseridos
        echo "<h3>Dados na Tabela:</h3>";
        $select = $db->query("SELECT * FROM backups ORDER BY data_criacao DESC");
        $backups = $select->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($backups) > 0) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>ID</th><th>Nome do Arquivo</th><th>Tamanho</th><th>Tipo</th><th>Data Criação</th><th>Status</th></tr>";
            
            foreach ($backups as $backup) {
                echo "<tr>";
                echo "<td>{$backup['id']}</td>";
                echo "<td>{$backup['nome_arquivo']}</td>";
                echo "<td>" . number_format($backup['tamanho']) . " bytes</td>";
                echo "<td>{$backup['tipo']}</td>";
                echo "<td>{$backup['data_criacao']}</td>";
                echo "<td>{$backup['status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Nenhum backup encontrado na tabela.</p>";
        }
        
    } else {
        echo "<p>❌ Erro: Tabela 'backups' não foi criada</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
}

echo "<p><em>Script executado em " . date('Y-m-d H:i:s') . "</em></p>";
?>