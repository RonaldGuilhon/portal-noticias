<?php
require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/../backend/config/database.php';

echo "=== ESTRUTURA DA TABELA USUARIOS ===\n\n";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->query('DESCRIBE usuarios');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Campos encontrados na tabela usuarios:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-30s %-20s %-15s %-10s\n", "CAMPO", "TIPO", "NULO", "PADRÃO");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($columns as $column) {
        printf("%-30s %-20s %-15s %-10s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'], 
            $column['Default'] ?? 'NULL'
        );
    }
    
    echo "\n" . str_repeat("-", 80) . "\n";
    echo "Total de campos: " . count($columns) . "\n";
    
    // Verificar campos específicos de notificação e privacidade
    $notification_fields = [
        'email_notifications', 'push_notifications', 'email_newsletter', 
        'email_breaking', 'email_comments', 'email_marketing',
        'push_breaking', 'push_interests', 'push_comments'
    ];
    
    $privacy_fields = [
        'profile_public', 'show_activity', 'allow_messages'
    ];
    
    $existing_fields = array_column($columns, 'Field');
    
    echo "\n=== ANÁLISE DOS CAMPOS ===\n";
    
    echo "\nCampos de Notificação:\n";
    foreach ($notification_fields as $field) {
        $status = in_array($field, $existing_fields) ? '✓ EXISTE' : '✗ AUSENTE';
        echo "  {$status} {$field}\n";
    }
    
    echo "\nCampos de Privacidade:\n";
    foreach ($privacy_fields as $field) {
        $status = in_array($field, $existing_fields) ? '✓ EXISTE' : '✗ AUSENTE';
        echo "  {$status} {$field}\n";
    }
    
    // Verificar se notification_frequency foi removida
    $deprecated = in_array('notification_frequency', $existing_fields) ? '✗ AINDA EXISTE' : '✓ REMOVIDO';
    echo "\nCampo Depreciado:\n";
    echo "  {$deprecated} notification_frequency\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DA VERIFICAÇÃO ===\n";
?>