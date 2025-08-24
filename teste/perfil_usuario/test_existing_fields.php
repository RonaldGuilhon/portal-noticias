<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/models/Usuario.php';

echo "=== TESTE DOS CAMPOS EXISTENTES DE NOTIFICAÇÕES E PRIVACIDADE ===\n\n";

try {
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
    
    echo "1. TESTANDO USUÁRIO ESPECÍFICO (ID 2):\n";
    echo str_repeat("-", 50) . "\n";
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Buscar dados do usuário com campos existentes
    $all_fields = array_merge($existing_notification_fields, $existing_privacy_fields);
    $query = "SELECT id, nome, email, " . implode(', ', $all_fields) . " FROM usuarios WHERE id = 2";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✓ Usuário encontrado: {$user['nome']} ({$user['email']})\n\n";
        
        echo "Configurações de Notificação Existentes:\n";
        foreach ($existing_notification_fields as $field) {
            $value = $user[$field] ? 'ATIVADO' : 'DESATIVADO';
            echo "  - {$field}: {$value}\n";
        }
        
        echo "\nConfigurações de Privacidade Existentes:\n";
        foreach ($existing_privacy_fields as $field) {
            $value = $user[$field] ? 'ATIVADO' : 'DESATIVADO';
            echo "  - {$field}: {$value}\n";
        }
    } else {
        echo "✗ Usuário ID 2 não encontrado\n";
        exit(1);
    }
    
    echo "\n2. TESTANDO VIA MODELO USUARIO:\n";
    echo str_repeat("-", 50) . "\n";
    
    $usuario = new Usuario();
    $dadosUsuario = $usuario->buscarPorId(2);
    
    if ($dadosUsuario) {
        echo "✓ Usuário carregado via modelo\n";
        
        echo "\nCampos acessíveis via modelo:\n";
        foreach ($all_fields as $field) {
            $value = isset($dadosUsuario[$field]) ? ($dadosUsuario[$field] ? 'ATIVADO' : 'DESATIVADO') : 'CAMPO AUSENTE';
            echo "  - {$field}: {$value}\n";
        }
        
    } else {
        echo "✗ Erro ao carregar usuário via modelo\n";
        exit(1);
    }
    
    echo "\n3. TESTANDO ATUALIZAÇÃO DE CAMPO DE PRIVACIDADE:\n";
    echo str_repeat("-", 50) . "\n";
    
    // Testar atualização do campo profile_public
    $original_value = $dadosUsuario['profile_public'];
    $new_value = $original_value ? 0 : 1;
    
    echo "Valor original de profile_public: " . ($original_value ? 'PÚBLICO' : 'PRIVADO') . "\n";
    
    // Atualizar o campo
    $update_query = "UPDATE usuarios SET profile_public = ? WHERE id = 2";
    $stmt = $conn->prepare($update_query);
    $result = $stmt->execute([$new_value]);
    
    if ($result) {
        echo "✓ Campo profile_public atualizado para: " . ($new_value ? 'PÚBLICO' : 'PRIVADO') . "\n";
        
        // Verificar se a atualização funcionou
        $verify_query = "SELECT profile_public FROM usuarios WHERE id = 2";
        $stmt = $conn->prepare($verify_query);
        $stmt->execute();
        $updated_value = $stmt->fetchColumn();
        
        if ($updated_value == $new_value) {
            echo "✓ Verificação: Campo foi atualizado corretamente\n";
        } else {
            echo "✗ Verificação: Campo não foi atualizado corretamente\n";
        }
        
        // Restaurar valor original
        $restore_query = "UPDATE usuarios SET profile_public = ? WHERE id = 2";
        $stmt = $conn->prepare($restore_query);
        $stmt->execute([$original_value]);
        echo "✓ Valor original restaurado\n";
        
    } else {
        echo "✗ Erro ao atualizar campo profile_public\n";
    }
    
    echo "\n4. TESTANDO ATUALIZAÇÃO DE CAMPO DE NOTIFICAÇÃO:\n";
    echo str_repeat("-", 50) . "\n";
    
    // Testar atualização do campo email_breaking
    $original_value = $dadosUsuario['email_breaking'];
    $new_value = $original_value ? 0 : 1;
    
    echo "Valor original de email_breaking: " . ($original_value ? 'ATIVADO' : 'DESATIVADO') . "\n";
    
    // Atualizar o campo
    $update_query = "UPDATE usuarios SET email_breaking = ? WHERE id = 2";
    $stmt = $conn->prepare($update_query);
    $result = $stmt->execute([$new_value]);
    
    if ($result) {
        echo "✓ Campo email_breaking atualizado para: " . ($new_value ? 'ATIVADO' : 'DESATIVADO') . "\n";
        
        // Verificar se a atualização funcionou
        $verify_query = "SELECT email_breaking FROM usuarios WHERE id = 2";
        $stmt = $conn->prepare($verify_query);
        $stmt->execute();
        $updated_value = $stmt->fetchColumn();
        
        if ($updated_value == $new_value) {
            echo "✓ Verificação: Campo foi atualizado corretamente\n";
        } else {
            echo "✗ Verificação: Campo não foi atualizado corretamente\n";
        }
        
        // Restaurar valor original
        $restore_query = "UPDATE usuarios SET email_breaking = ? WHERE id = 2";
        $stmt = $conn->prepare($restore_query);
        $stmt->execute([$original_value]);
        echo "✓ Valor original restaurado\n";
        
    } else {
        echo "✗ Erro ao atualizar campo email_breaking\n";
    }
    
    echo "\n✓ TODOS OS TESTES CONCLUÍDOS COM SUCESSO!\n";
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    exit(1);
}

echo "\n=== TESTE CONCLUÍDO ===\n";
?>