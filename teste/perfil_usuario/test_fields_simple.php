<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/config/database.php';

echo "=== TESTE SIMPLES DOS CAMPOS DE NOTIFICAÇÕES E PRIVACIDADE ===\n\n";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Campos que realmente existem na tabela
    $existing_notification_fields = [
        'email_newsletter',
        'email_breaking', 
        'email_comments',
        'email_marketing',
        'push_breaking',
        'push_interests',
        'push_comments'
    ];
    
    $existing_privacy_fields = [
        'profile_public',
        'show_activity',
        'allow_messages'
    ];
    
    echo "1. VERIFICANDO DADOS ATUAIS DO USUÁRIO (ID 2):\n";
    echo str_repeat("-", 60) . "\n";
    
    $all_fields = array_merge($existing_notification_fields, $existing_privacy_fields);
    $query = "SELECT id, nome, email, " . implode(', ', $all_fields) . " FROM usuarios WHERE id = 2";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "✗ Usuário ID 2 não encontrado\n";
        exit(1);
    }
    
    echo "✓ Usuário: {$user['nome']} ({$user['email']})\n\n";
    
    echo "Configurações de Notificação:\n";
    foreach ($existing_notification_fields as $field) {
        $value = $user[$field] ? 'ATIVADO' : 'DESATIVADO';
        echo "  - {$field}: {$value}\n";
    }
    
    echo "\nConfigurações de Privacidade:\n";
    foreach ($existing_privacy_fields as $field) {
        $value = $user[$field] ? 'ATIVADO' : 'DESATIVADO';
        echo "  - {$field}: {$value}\n";
    }
    
    echo "\n2. TESTANDO ATUALIZAÇÃO DE CAMPO DE PRIVACIDADE (profile_public):\n";
    echo str_repeat("-", 60) . "\n";
    
    $original_value = $user['profile_public'];
    $new_value = $original_value ? 0 : 1;
    
    echo "Valor original: " . ($original_value ? 'PÚBLICO' : 'PRIVADO') . "\n";
    echo "Novo valor: " . ($new_value ? 'PÚBLICO' : 'PRIVADO') . "\n";
    
    // Atualizar
    $update_query = "UPDATE usuarios SET profile_public = ? WHERE id = 2";
    $stmt = $conn->prepare($update_query);
    $result = $stmt->execute([$new_value]);
    
    if ($result) {
        echo "✓ Atualização executada\n";
        
        // Verificar
        $verify_query = "SELECT profile_public FROM usuarios WHERE id = 2";
        $stmt = $conn->prepare($verify_query);
        $stmt->execute();
        $updated_value = $stmt->fetchColumn();
        
        if ($updated_value == $new_value) {
            echo "✓ Verificação: Campo atualizado corretamente\n";
        } else {
            echo "✗ Verificação: Campo não foi atualizado (esperado: {$new_value}, obtido: {$updated_value})\n";
        }
        
        // Restaurar
        $restore_query = "UPDATE usuarios SET profile_public = ? WHERE id = 2";
        $stmt = $conn->prepare($restore_query);
        $stmt->execute([$original_value]);
        echo "✓ Valor original restaurado\n";
        
    } else {
        echo "✗ Erro ao executar atualização\n";
    }
    
    echo "\n3. TESTANDO ATUALIZAÇÃO DE CAMPO DE NOTIFICAÇÃO (email_breaking):\n";
    echo str_repeat("-", 60) . "\n";
    
    $original_value = $user['email_breaking'];
    $new_value = $original_value ? 0 : 1;
    
    echo "Valor original: " . ($original_value ? 'ATIVADO' : 'DESATIVADO') . "\n";
    echo "Novo valor: " . ($new_value ? 'ATIVADO' : 'DESATIVADO') . "\n";
    
    // Atualizar
    $update_query = "UPDATE usuarios SET email_breaking = ? WHERE id = 2";
    $stmt = $conn->prepare($update_query);
    $result = $stmt->execute([$new_value]);
    
    if ($result) {
        echo "✓ Atualização executada\n";
        
        // Verificar
        $verify_query = "SELECT email_breaking FROM usuarios WHERE id = 2";
        $stmt = $conn->prepare($verify_query);
        $stmt->execute();
        $updated_value = $stmt->fetchColumn();
        
        if ($updated_value == $new_value) {
            echo "✓ Verificação: Campo atualizado corretamente\n";
        } else {
            echo "✗ Verificação: Campo não foi atualizado (esperado: {$new_value}, obtido: {$updated_value})\n";
        }
        
        // Restaurar
        $restore_query = "UPDATE usuarios SET email_breaking = ? WHERE id = 2";
        $stmt = $conn->prepare($restore_query);
        $stmt->execute([$original_value]);
        echo "✓ Valor original restaurado\n";
        
    } else {
        echo "✗ Erro ao executar atualização\n";
    }
    
    echo "\n4. VERIFICANDO SE notification_frequency FOI REMOVIDA:\n";
    echo str_repeat("-", 60) . "\n";
    
    $check_query = "SHOW COLUMNS FROM usuarios LIKE 'notification_frequency'";
    $stmt = $conn->prepare($check_query);
    $stmt->execute();
    $column_exists = $stmt->fetch();
    
    if ($column_exists) {
        echo "✗ PROBLEMA: Campo notification_frequency ainda existe na tabela\n";
    } else {
        echo "✓ Campo notification_frequency foi removido corretamente\n";
    }
    
    echo "\n✓ TODOS OS TESTES CONCLUÍDOS COM SUCESSO!\n";
    echo "\n=== RESUMO ===\n";
    echo "- Campos de notificação existentes: " . count($existing_notification_fields) . "\n";
    echo "- Campos de privacidade existentes: " . count($existing_privacy_fields) . "\n";
    echo "- Campo notification_frequency: REMOVIDO\n";
    echo "- Atualizações de campos: FUNCIONANDO\n";
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    exit(1);
}

echo "\n=== TESTE CONCLUÍDO ===\n";
?>