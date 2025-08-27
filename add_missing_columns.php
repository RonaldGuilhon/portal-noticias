<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "=== ADICIONANDO CAMPOS FALTANTES À TABELA USUARIOS ===\n";
    
    $alterations = [
        "ALTER TABLE usuarios ADD COLUMN telefone VARCHAR(100) DEFAULT ''",
        "ALTER TABLE usuarios ADD COLUMN show_images TINYINT(1) DEFAULT 1",
        "ALTER TABLE usuarios ADD COLUMN auto_play_videos TINYINT(1) DEFAULT 1",
        "ALTER TABLE usuarios ADD COLUMN dark_mode TINYINT(1) DEFAULT 1",
        "ALTER TABLE usuarios ADD COLUMN email_newsletter TINYINT(1) DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN email_breaking TINYINT(1) DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN email_comments TINYINT(1) DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN email_marketing TINYINT(1) DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN push_breaking TINYINT(1) DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN push_interests TINYINT(1) DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN push_comments TINYINT(1) DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN profile_public TINYINT(1) DEFAULT 1",
        "ALTER TABLE usuarios ADD COLUMN show_activity TINYINT(1) DEFAULT 1",
        "ALTER TABLE usuarios ADD COLUMN allow_messages TINYINT(1) DEFAULT 1",
        "ALTER TABLE usuarios ADD COLUMN favorite_categories JSON DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN language_preference VARCHAR(10) DEFAULT 'pt'"
    ];
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($alterations as $sql) {
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            echo "✅ Executado: $sql\n";
            $success_count++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "⚠️  Campo já existe: $sql\n";
            } else {
                echo "❌ Erro ao executar: $sql\n";
                echo "   Erro: " . $e->getMessage() . "\n";
                $error_count++;
            }
        }
    }
    
    echo "\n=== RESUMO ===\n";
    echo "Comandos executados com sucesso: $success_count\n";
    echo "Erros encontrados: $error_count\n";
    
    if ($error_count === 0) {
        echo "\n✅ Todos os campos foram adicionados com sucesso!\n";
        echo "Agora você pode testar novamente a atualização do perfil.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro geral: " . $e->getMessage() . "\n";
}
?>