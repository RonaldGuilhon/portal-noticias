<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "=== ESTRUTURA DA TABELA USUARIOS ===\n";
    
    // Verificar estrutura da tabela
    $query = "DESCRIBE usuarios";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Colunas existentes na tabela:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) - {$column['Null']} - Default: {$column['Default']}\n";
    }
    
    echo "\n=== CAMPOS ESPERADOS PELO SISTEMA ===\n";
    $expected_fields = [
        'id', 'nome', 'email', 'senha', 'tipo_usuario', 'ativo', 'email_verificado',
        'token_verificacao', 'token_recuperacao', 'data_criacao', 'ultimo_login',
        'bio', 'foto_perfil', 'preferencias', 'data_nascimento', 'genero',
        'telefone', 'cidade', 'estado',
        'show_images', 'auto_play_videos', 'dark_mode',
        'email_newsletter', 'email_breaking', 'email_comments', 'email_marketing',
        'push_breaking', 'push_interests', 'push_comments',
        'profile_public', 'show_activity', 'allow_messages',
        'favorite_categories', 'language_preference'
    ];
    
    $existing_fields = array_column($columns, 'Field');
    $missing_fields = array_diff($expected_fields, $existing_fields);
    
    if (!empty($missing_fields)) {
        echo "Campos FALTANDO na tabela:\n";
        foreach ($missing_fields as $field) {
            echo "- $field\n";
        }
        
        echo "\n=== SCRIPT SQL PARA ADICIONAR CAMPOS FALTANDO ===\n";
        foreach ($missing_fields as $field) {
            switch ($field) {
                case 'telefone':
                case 'cidade':
                case 'estado':
                    echo "ALTER TABLE usuarios ADD COLUMN $field VARCHAR(100) DEFAULT '';\n";
                    break;
                case 'show_images':
                case 'auto_play_videos':
                case 'dark_mode':
                case 'profile_public':
                case 'show_activity':
                case 'allow_messages':
                    echo "ALTER TABLE usuarios ADD COLUMN $field TINYINT(1) DEFAULT 1;\n";
                    break;
                case 'email_newsletter':
                case 'email_breaking':
                case 'email_comments':
                case 'email_marketing':
                case 'push_breaking':
                case 'push_interests':
                case 'push_comments':
                    echo "ALTER TABLE usuarios ADD COLUMN $field TINYINT(1) DEFAULT 0;\n";
                    break;
                case 'favorite_categories':
                    echo "ALTER TABLE usuarios ADD COLUMN $field JSON DEFAULT NULL;\n";
                    break;
                case 'language_preference':
                    echo "ALTER TABLE usuarios ADD COLUMN $field VARCHAR(10) DEFAULT 'pt';\n";
                    break;
                default:
                    echo "ALTER TABLE usuarios ADD COLUMN $field TEXT DEFAULT NULL;\n";
                    break;
            }
        }
    } else {
        echo "✅ Todos os campos esperados estão presentes na tabela.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>