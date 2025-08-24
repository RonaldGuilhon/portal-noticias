<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/models/Usuario.php';

echo "=== TESTE SIMPLES DO PERFIL ===\n\n";

try {
    // Testar busca de usuário por ID
    $usuario = new Usuario();
    $dadosUsuario = $usuario->buscarPorId(2);
    
    if ($dadosUsuario) {
        echo "✓ Usuário encontrado com sucesso\n";
        echo "ID: " . $dadosUsuario['id'] . "\n";
        echo "Nome: " . $dadosUsuario['nome'] . "\n";
        echo "Email: " . $dadosUsuario['email'] . "\n";
        echo "Bio: " . ($dadosUsuario['bio'] ?? 'NULL') . "\n";
        
        // Verificar se notification_frequency ainda existe (não deveria)
        if (isset($dadosUsuario['notification_frequency'])) {
            echo "✗ ERRO: notification_frequency ainda existe nos dados!\n";
        } else {
            echo "✓ notification_frequency removida com sucesso\n";
        }
        
        // Testar atualização de dados básicos
        echo "\n=== TESTE DE ATUALIZAÇÃO ===\n";
        $usuario->id = 2;
        $usuario->nome = $dadosUsuario['nome'];
        $usuario->email = $dadosUsuario['email'];
        $usuario->bio = $dadosUsuario['bio'] ?? '';
        $usuario->foto_perfil = $dadosUsuario['foto_perfil'] ?? '';
        $usuario->estado = $dadosUsuario['estado'] ?? '';
        $usuario->profile_public = $dadosUsuario['profile_public'] ?? 1;
        $usuario->show_activity = $dadosUsuario['show_activity'] ?? 1;
        $usuario->allow_messages = $dadosUsuario['allow_messages'] ?? 1;
        $usuario->email_notifications = $dadosUsuario['email_notifications'] ?? 1;
        $usuario->push_notifications = $dadosUsuario['push_notifications'] ?? 1;
        $usuario->newsletter = $dadosUsuario['newsletter'] ?? 0;
        $usuario->favorite_categories = $dadosUsuario['favorite_categories'] ?? '';
        $usuario->language_preference = $dadosUsuario['language_preference'] ?? 'pt';
        
        $resultado = $usuario->atualizar();
        
        if ($resultado) {
            echo "✓ Atualização realizada com sucesso\n";
        } else {
            echo "✗ Erro na atualização\n";
        }
        
    } else {
        echo "✗ Usuário não encontrado\n";
    }
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
?>