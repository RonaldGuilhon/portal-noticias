<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/models/Usuario.php';

echo "=== TESTE DE CAMPOS DE NOTIFICAÇÕES E PRIVACIDADE ===\n\n";

try {
    // Conectar ao banco
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "1. VERIFICANDO ESTRUTURA DA TABELA USUARIOS:\n";
    echo str_repeat("-", 50) . "\n";
    
    // Verificar se os campos de notificação e privacidade existem
    $stmt = $conn->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $notification_fields = [
        'email_notifications',
        'push_notifications',
        'email_newsletter',
        'email_breaking',
        'email_comments',
        'email_marketing',
        'push_breaking',
        'push_interests',
        'push_comments'
    ];
    
    $privacy_fields = [
        'profile_public',
        'show_activity',
        'allow_messages'
    ];
    
    echo "Campos de Notificação:\n";
    foreach ($notification_fields as $field) {
        $exists = in_array($field, $columns) ? '✓' : '✗';
        echo "  {$exists} {$field}\n";
    }
    
    echo "\nCampos de Privacidade:\n";
    foreach ($privacy_fields as $field) {
        $exists = in_array($field, $columns) ? '✓' : '✗';
        echo "  {$exists} {$field}\n";
    }
    
    // Verificar se notification_frequency foi removida
    $deprecated_fields = ['notification_frequency'];
    echo "\nCampos Depreciados (devem estar ausentes):\n";
    foreach ($deprecated_fields as $field) {
        $exists = in_array($field, $columns) ? '✗ AINDA EXISTE' : '✓ REMOVIDO';
        echo "  {$exists} {$field}\n";
    }
    
    echo "\n2. TESTANDO USUÁRIO ESPECÍFICO (ID 2):\n";
    echo str_repeat("-", 50) . "\n";
    
    // Buscar dados do usuário
    $query = "SELECT id, nome, email, " . implode(', ', array_merge($notification_fields, $privacy_fields)) . " FROM usuarios WHERE id = 2";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✓ Usuário encontrado: {$user['nome']} ({$user['email']})\n\n";
        
        echo "Configurações de Notificação:\n";
        foreach ($notification_fields as $field) {
            if (isset($user[$field])) {
                $value = $user[$field] ? 'ATIVADO' : 'DESATIVADO';
                echo "  - {$field}: {$value}\n";
            } else {
                echo "  - {$field}: CAMPO NÃO EXISTE\n";
            }
        }
        
        echo "\nConfigurações de Privacidade:\n";
        foreach ($privacy_fields as $field) {
            if (isset($user[$field])) {
                $value = $user[$field] ? 'ATIVADO' : 'DESATIVADO';
                echo "  - {$field}: {$value}\n";
            } else {
                echo "  - {$field}: CAMPO NÃO EXISTE\n";
            }
        }
    } else {
        echo "✗ Usuário ID 2 não encontrado\n";
    }
    
    echo "\n3. TESTANDO ATUALIZAÇÃO VIA MODELO USUARIO:\n";
    echo str_repeat("-", 50) . "\n";
    
    $usuario = new Usuario();
    $dadosUsuario = $usuario->buscarPorId(2);
    
    if ($dadosUsuario) {
        echo "✓ Usuário carregado via modelo\n";
        
        // Testar se os campos estão acessíveis
        $test_fields = ['email_notifications', 'push_notifications', 'profile_public', 'show_activity', 'allow_messages'];
        
        echo "\nCampos acessíveis via modelo:\n";
        foreach ($test_fields as $field) {
            $value = isset($dadosUsuario[$field]) ? ($dadosUsuario[$field] ? 'SIM' : 'NÃO') : 'CAMPO AUSENTE';
            echo "  - {$field}: {$value}\n";
        }
        
        // Tentar atualizar um campo de notificação
        echo "\n4. TESTANDO ATUALIZAÇÃO DE CAMPO:\n";
        echo str_repeat("-", 50) . "\n";
        
        $usuario->id = 2;
        $original_email_notifications = $dadosUsuario['email_notifications'] ?? 1;
        $new_value = $original_email_notifications ? 0 : 1;
        
        // Simular atualização (sem realmente alterar)
        echo "Valor original de email_notifications: " . ($original_email_notifications ? 'ATIVADO' : 'DESATIVADO') . "\n";
        echo "Novo valor seria: " . ($new_value ? 'ATIVADO' : 'DESATIVADO') . "\n";
        echo "✓ Teste de atualização simulado com sucesso\n";
        
    } else {
        echo "✗ Erro ao carregar usuário via modelo\n";
    }
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
?>