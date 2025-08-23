<?php
/**
 * Script para adicionar colunas de preferências faltantes
 * Portal de Notícias
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Conectado ao banco de dados com sucesso!\n";
    
    // Lista de colunas para adicionar
    $columns_to_add = [
        'email_newsletter' => 'TINYINT(1) DEFAULT 1 COMMENT "Receber newsletter por email"',
        'profile_public' => 'TINYINT(1) DEFAULT 1 COMMENT "Perfil público visível"',
        'show_activity' => 'TINYINT(1) DEFAULT 1 COMMENT "Mostrar atividade do usuário"',
        'allow_messages' => 'TINYINT(1) DEFAULT 1 COMMENT "Permitir mensagens de outros usuários"',
        'favorite_categories' => 'JSON DEFAULT NULL COMMENT "Categorias favoritas do usuário"',
        'language_preference' => 'VARCHAR(10) DEFAULT "pt-BR" COMMENT "Idioma preferido do usuário"'
    ];
    
    foreach ($columns_to_add as $column_name => $column_definition) {
        // Verificar se a coluna já existe
        $check_query = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS 
                       WHERE TABLE_SCHEMA = DATABASE() 
                       AND TABLE_NAME = 'usuarios' 
                       AND COLUMN_NAME = :column_name";
        
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':column_name', $column_name);
        $check_stmt->execute();
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] == 0) {
            // Coluna não existe, adicionar
            $add_query = "ALTER TABLE usuarios ADD COLUMN {$column_name} {$column_definition}";
            
            if ($db->exec($add_query) !== false) {
                echo "✓ Coluna '{$column_name}' adicionada com sucesso!\n";
            } else {
                echo "✗ Erro ao adicionar coluna '{$column_name}'\n";
                $errorInfo = $db->errorInfo();
                echo "Erro: " . $errorInfo[2] . "\n";
            }
        } else {
            echo "- Coluna '{$column_name}' já existe\n";
        }
    }
    
    // Verificar estrutura final
    echo "\n=== Verificando estrutura final ===\n";
    $verify_query = "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'usuarios' 
                    AND (COLUMN_NAME LIKE '%email%' 
                         OR COLUMN_NAME LIKE '%push%' 
                         OR COLUMN_NAME LIKE '%notification%'
                         OR COLUMN_NAME LIKE '%profile%'
                         OR COLUMN_NAME LIKE '%show%'
                         OR COLUMN_NAME LIKE '%allow%'
                         OR COLUMN_NAME LIKE '%favorite%'
                         OR COLUMN_NAME LIKE '%language%'
                         OR COLUMN_NAME LIKE '%dark%')
                    ORDER BY COLUMN_NAME";
    
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->execute();
    $columns = $verify_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- {$column['COLUMN_NAME']}: {$column['DATA_TYPE']} (Default: {$column['COLUMN_DEFAULT']})\n";
    }
    
    echo "\n✓ Script executado com sucesso!\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    exit(1);
}
?>