<?php
require_once 'config-local.php';

try {
    $pdo = new PDO("mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8mb4", 
        $config['database']['username'], $config['database']['password'], $config['database']['options']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== CORREÇÃO DE COLUNAS DUPLICADAS E PADRONIZAÇÃO ===\n\n";
    
    // 1. Padronizar valores padrão para notificações por email
    echo "1. Padronizando notificações por email...\n";
    echo str_repeat("-", 50) . "\n";
    
    $email_columns = [
        'email_newsletter' => 'Receber newsletter por email',
        'email_breaking' => 'Receber notificações de últimas notícias',
        'email_comments' => 'Receber notificações de comentários',
        'email_marketing' => 'Receber emails promocionais'
    ];
    
    foreach ($email_columns as $column => $comment) {
        try {
            // Verificar se a coluna existe
            $check_query = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS 
                           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = ?";
            $stmt = $pdo->prepare($check_query);
            $stmt->execute([$column]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                // Modificar coluna para ter valor padrão consistente
                $alter_query = "ALTER TABLE usuarios MODIFY COLUMN {$column} TINYINT(1) DEFAULT 1 COMMENT '{$comment}'";
                $pdo->exec($alter_query);
                echo "✓ Coluna '{$column}' padronizada (padrão: 1)\n";
            } else {
                echo "- Coluna '{$column}' não existe\n";
            }
        } catch (Exception $e) {
            echo "✗ Erro ao padronizar '{$column}': " . $e->getMessage() . "\n";
        }
    }
    
    // 2. Padronizar valores padrão para notificações push
    echo "\n2. Padronizando notificações push...\n";
    echo str_repeat("-", 50) . "\n";
    
    $push_columns = [
        'push_breaking' => 'Receber push de últimas notícias',
        'push_comments' => 'Receber push de comentários',
        'push_interests' => 'Receber push baseado em interesses'
    ];
    
    foreach ($push_columns as $column => $comment) {
        try {
            // Verificar se a coluna existe
            $check_query = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS 
                           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = ?";
            $stmt = $pdo->prepare($check_query);
            $stmt->execute([$column]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                // Modificar coluna para ter valor padrão consistente
                $alter_query = "ALTER TABLE usuarios MODIFY COLUMN {$column} TINYINT(1) DEFAULT 1 COMMENT '{$comment}'";
                $pdo->exec($alter_query);
                echo "✓ Coluna '{$column}' padronizada (padrão: 1)\n";
            } else {
                echo "- Coluna '{$column}' não existe\n";
            }
        } catch (Exception $e) {
            echo "✗ Erro ao padronizar '{$column}': " . $e->getMessage() . "\n";
        }
    }
    
    // 3. Padronizar configurações de perfil
    echo "\n3. Padronizando configurações de perfil...\n";
    echo str_repeat("-", 50) . "\n";
    
    $profile_columns = [
        'profile_public' => 'Perfil público visível para outros usuários',
        'show_activity' => 'Mostrar atividade do usuário',
        'allow_messages' => 'Permitir mensagens de outros usuários',
        'show_images' => 'Mostrar imagens automaticamente'
    ];
    
    foreach ($profile_columns as $column => $comment) {
        try {
            // Verificar se a coluna existe
            $check_query = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS 
                           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = ?";
            $stmt = $pdo->prepare($check_query);
            $stmt->execute([$column]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                // Modificar coluna para ter valor padrão consistente
                $alter_query = "ALTER TABLE usuarios MODIFY COLUMN {$column} TINYINT(1) DEFAULT 1 COMMENT '{$comment}'";
                $pdo->exec($alter_query);
                echo "✓ Coluna '{$column}' padronizada (padrão: 1)\n";
            } else {
                echo "- Coluna '{$column}' não existe\n";
            }
        } catch (Exception $e) {
            echo "✗ Erro ao padronizar '{$column}': " . $e->getMessage() . "\n";
        }
    }
    
    // 4. Verificar e corrigir colunas com propósitos similares
    echo "\n4. Analisando colunas com propósitos similares...\n";
    echo str_repeat("-", 50) . "\n";
    
    // Verificar se existe redundância entre email_newsletter e outras colunas de email
    $email_check_query = "SELECT 
        COLUMN_NAME,
        COLUMN_DEFAULT,
        COLUMN_COMMENT
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'usuarios' 
        AND COLUMN_NAME IN ('email_newsletter', 'newsletter')
    ORDER BY COLUMN_NAME";
    
    $stmt = $pdo->query($email_check_query);
    $email_cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($email_cols) > 1) {
        echo "⚠️  Encontradas colunas potencialmente redundantes:\n";
        foreach ($email_cols as $col) {
            echo "  - {$col['COLUMN_NAME']} (padrão: {$col['COLUMN_DEFAULT']})\n";
        }
        echo "\n📋 Recomendação: Manter apenas 'email_newsletter' e remover 'newsletter'\n";
        
        // Verificar se a coluna 'newsletter' ainda existe
        $newsletter_check = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS 
                            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'newsletter'";
        $stmt = $pdo->query($newsletter_check);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            echo "\n🔧 Removendo coluna redundante 'newsletter'...\n";
            try {
                $pdo->exec("ALTER TABLE usuarios DROP COLUMN newsletter");
                echo "✓ Coluna 'newsletter' removida com sucesso!\n";
            } catch (Exception $e) {
                echo "✗ Erro ao remover coluna 'newsletter': " . $e->getMessage() . "\n";
            }
        } else {
            echo "✓ Coluna 'newsletter' já foi removida anteriormente\n";
        }
    } else {
        echo "✓ Não há redundância entre colunas de newsletter\n";
    }
    
    // 5. Verificar estrutura final
    echo "\n5. Verificando estrutura final...\n";
    echo str_repeat("-", 50) . "\n";
    
    $final_check_query = "SELECT 
        COLUMN_NAME,
        DATA_TYPE,
        IS_NULLABLE,
        COLUMN_DEFAULT,
        COLUMN_COMMENT
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'usuarios' 
        AND (COLUMN_NAME LIKE '%email%' 
             OR COLUMN_NAME LIKE '%push%' 
             OR COLUMN_NAME LIKE '%profile%'
             OR COLUMN_NAME LIKE '%show%'
             OR COLUMN_NAME LIKE '%allow%'
             OR COLUMN_NAME LIKE '%newsletter%')
    ORDER BY COLUMN_NAME";
    
    $stmt = $pdo->query($final_check_query);
    $final_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Colunas de preferências após padronização:\n";
    printf("%-20s %-12s %-8s %-10s %s\n", "COLUNA", "TIPO", "NULL", "PADRÃO", "COMENTÁRIO");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($final_columns as $col) {
        printf("%-20s %-12s %-8s %-10s %s\n", 
            $col['COLUMN_NAME'], 
            $col['DATA_TYPE'], 
            $col['IS_NULLABLE'], 
            $col['COLUMN_DEFAULT'] ?? 'NULL',
            substr($col['COLUMN_COMMENT'] ?? '', 0, 30)
        );
    }
    
    echo "\n=== RESUMO DAS CORREÇÕES ===\n";
    echo str_repeat("-", 50) . "\n";
    echo "✅ Valores padrão padronizados para notificações\n";
    echo "✅ Comentários adicionados/atualizados\n";
    echo "✅ Colunas redundantes verificadas\n";
    echo "✅ Estrutura otimizada para consistência\n";
    
    echo "\n🎯 Próximos passos recomendados:\n";
    echo "1. Testar funcionalidades de preferências\n";
    echo "2. Atualizar documentação da API\n";
    echo "3. Verificar compatibilidade com frontend\n";
    
} catch(Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>