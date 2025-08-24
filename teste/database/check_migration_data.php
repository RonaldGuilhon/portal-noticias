<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=portal_noticias', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== VERIFICAÇÃO DOS DADOS MIGRADOS ===\n\n";
    
    // Verificar dados na tabela usuarios (campo preferencias)
    echo "1. Dados na tabela usuarios (campo preferencias):\n";
    $stmt = $pdo->query("SELECT id, nome, preferencias FROM usuarios WHERE preferencias IS NOT NULL AND preferencias != ''");
    $usuarios_com_preferencias = $stmt->fetchAll();
    
    if (empty($usuarios_com_preferencias)) {
        echo "   - Nenhum usuário com preferências encontrado\n";
    } else {
        foreach ($usuarios_com_preferencias as $user) {
            echo "   - ID: {$user['id']}, Nome: {$user['nome']}, Preferencias: {$user['preferencias']}\n";
        }
    }
    
    echo "\n2. Dados na tabela user_preferences:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM user_preferences");
    $total_preferences = $stmt->fetch()['total'];
    
    echo "   - Total de registros: $total_preferences\n";
    
    if ($total_preferences > 0) {
        $stmt = $pdo->query("SELECT * FROM user_preferences LIMIT 10");
        $preferences = $stmt->fetchAll();
        
        foreach ($preferences as $pref) {
            echo "   - User ID: {$pref['user_id']}, Key: {$pref['preference_key']}, Value: {$pref['preference_value']}\n";
        }
        
        if ($total_preferences > 10) {
            echo "   - ... e mais " . ($total_preferences - 10) . " registros\n";
        }
    }
    
    echo "\n3. Verificação de integridade:\n";
    
    // Verificar se existem usuários com preferencias que não foram migradas
    $stmt = $pdo->query("
        SELECT u.id, u.nome 
        FROM usuarios u 
        WHERE u.preferencias IS NOT NULL 
        AND u.preferencias != '' 
        AND u.id NOT IN (SELECT DISTINCT user_id FROM user_preferences)
    ");
    $usuarios_nao_migrados = $stmt->fetchAll();
    
    if (empty($usuarios_nao_migrados)) {
        echo "   ✓ Todos os usuários com preferências foram migrados\n";
    } else {
        echo "   ⚠ Usuários com preferências não migradas:\n";
        foreach ($usuarios_nao_migrados as $user) {
            echo "     - ID: {$user['id']}, Nome: {$user['nome']}\n";
        }
    }
    
    echo "\n=== RESUMO ===\n";
    echo "- Usuários com preferências na tabela original: " . count($usuarios_com_preferencias) . "\n";
    echo "- Registros na tabela user_preferences: $total_preferences\n";
    echo "- Status da migração: " . (empty($usuarios_nao_migrados) ? "✓ COMPLETA" : "⚠ INCOMPLETA") . "\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}