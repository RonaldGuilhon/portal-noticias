<?php
require_once 'config-local.php';

try {
    $pdo = new PDO("mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8mb4", 
        $config['database']['username'], $config['database']['password'], $config['database']['options']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== VERIFICAÇÃO DE COLUNAS DUPLICADAS ===\n\n";
    
    // Buscar todas as colunas da tabela usuarios
    $stmt = $pdo->query("
        SELECT 
            COLUMN_NAME,
            DATA_TYPE,
            IS_NULLABLE,
            COLUMN_DEFAULT,
            COLUMN_COMMENT
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'usuarios'
        ORDER BY COLUMN_NAME
    ");
    
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total de colunas na tabela usuarios: " . count($columns) . "\n\n";
    
    // Agrupar colunas por funcionalidade para identificar duplicatas
    $groups = [
        'email_notifications' => [],
        'push_notifications' => [],
        'profile_settings' => [],
        'user_data' => [],
        'preferences' => [],
        'other' => []
    ];
    
    foreach ($columns as $column) {
        $name = $column['COLUMN_NAME'];
        
        if (strpos($name, 'email') !== false || strpos($name, 'newsletter') !== false) {
            $groups['email_notifications'][] = $column;
        } elseif (strpos($name, 'push') !== false) {
            $groups['push_notifications'][] = $column;
        } elseif (strpos($name, 'profile') !== false || strpos($name, 'show') !== false || strpos($name, 'allow') !== false) {
            $groups['profile_settings'][] = $column;
        } elseif (in_array($name, ['nome', 'bio', 'foto_perfil', 'data_nascimento', 'genero', 'telefone', 'cidade', 'estado'])) {
            $groups['user_data'][] = $column;
        } elseif (strpos($name, 'preference') !== false || strpos($name, 'favorite') !== false || strpos($name, 'language') !== false || strpos($name, 'dark') !== false) {
            $groups['preferences'][] = $column;
        } else {
            $groups['other'][] = $column;
        }
    }
    
    // Analisar cada grupo
    foreach ($groups as $groupName => $groupColumns) {
        if (empty($groupColumns)) continue;
        
        echo "=== GRUPO: " . strtoupper($groupName) . " ===\n";
        echo str_repeat("-", 60) . "\n";
        
        foreach ($groupColumns as $column) {
            printf("%-25s %-15s %-10s %s\n", 
                $column['COLUMN_NAME'], 
                $column['DATA_TYPE'], 
                $column['IS_NULLABLE'], 
                $column['COLUMN_COMMENT'] ?? ''
            );
        }
        echo "\n";
    }
    
    // Verificar possíveis duplicatas específicas
    echo "=== ANÁLISE DE POSSÍVEIS DUPLICATAS ===\n";
    echo str_repeat("-", 60) . "\n";
    
    $potential_duplicates = [];
    
    // Verificar se existem colunas similares
    $column_names = array_column($columns, 'COLUMN_NAME');
    
    // Verificar duplicatas de notificação por email
    $email_cols = array_filter($column_names, function($name) {
        return strpos($name, 'email') !== false || strpos($name, 'newsletter') !== false;
    });
    
    if (count($email_cols) > 1) {
        $potential_duplicates['Email/Newsletter'] = $email_cols;
    }
    
    // Verificar duplicatas de notificação push
    $push_cols = array_filter($column_names, function($name) {
        return strpos($name, 'push') !== false;
    });
    
    if (count($push_cols) > 1) {
        $potential_duplicates['Push Notifications'] = $push_cols;
    }
    
    // Verificar duplicatas de configurações de perfil
    $profile_cols = array_filter($column_names, function($name) {
        return strpos($name, 'profile') !== false || strpos($name, 'show') !== false || strpos($name, 'allow') !== false;
    });
    
    if (count($profile_cols) > 1) {
        $potential_duplicates['Profile Settings'] = $profile_cols;
    }
    
    // Verificar se há colunas com nomes muito similares
    for ($i = 0; $i < count($column_names); $i++) {
        for ($j = $i + 1; $j < count($column_names); $j++) {
            $similarity = similar_text($column_names[$i], $column_names[$j], $percent);
            if ($percent > 70 && $percent < 100) { // Similar mas não idêntico
                $potential_duplicates['Similar Names'][] = [
                    'col1' => $column_names[$i],
                    'col2' => $column_names[$j],
                    'similarity' => round($percent, 2) . '%'
                ];
            }
        }
    }
    
    if (empty($potential_duplicates)) {
        echo "✅ Nenhuma duplicata óbvia encontrada!\n";
    } else {
        echo "⚠️  Possíveis duplicatas encontradas:\n\n";
        
        foreach ($potential_duplicates as $type => $duplicates) {
            echo "📋 {$type}:\n";
            
            if ($type === 'Similar Names') {
                foreach ($duplicates as $similar) {
                    echo "  - {$similar['col1']} ↔ {$similar['col2']} ({$similar['similarity']} similar)\n";
                }
            } else {
                foreach ($duplicates as $col) {
                    echo "  - {$col}\n";
                }
            }
            echo "\n";
        }
    }
    
    // Verificar colunas com valores padrão conflitantes
    echo "=== VERIFICAÇÃO DE VALORES PADRÃO ===\n";
    echo str_repeat("-", 60) . "\n";
    
    $default_conflicts = [];
    foreach ($groups as $groupName => $groupColumns) {
        if (count($groupColumns) > 1) {
            $defaults = [];
            foreach ($groupColumns as $col) {
                $default = $col['COLUMN_DEFAULT'] ?? 'NULL';
                if (!isset($defaults[$default])) {
                    $defaults[$default] = [];
                }
                $defaults[$default][] = $col['COLUMN_NAME'];
            }
            
            if (count($defaults) > 1) {
                $default_conflicts[$groupName] = $defaults;
            }
        }
    }
    
    if (empty($default_conflicts)) {
        echo "✅ Nenhum conflito de valores padrão encontrado!\n";
    } else {
        echo "⚠️  Conflitos de valores padrão encontrados:\n\n";
        foreach ($default_conflicts as $group => $defaults) {
            echo "📋 Grupo {$group}:\n";
            foreach ($defaults as $default => $cols) {
                echo "  Padrão '{$default}': " . implode(', ', $cols) . "\n";
            }
            echo "\n";
        }
    }
    
    echo "\n=== RECOMENDAÇÕES ===\n";
    echo str_repeat("-", 60) . "\n";
    
    if (!empty($potential_duplicates) || !empty($default_conflicts)) {
        echo "🔧 Ações recomendadas:\n";
        echo "1. Revisar colunas similares para consolidação\n";
        echo "2. Padronizar valores padrão em grupos relacionados\n";
        echo "3. Considerar renomear colunas com nomes confusos\n";
        echo "4. Documentar o propósito de cada coluna\n";
    } else {
        echo "✅ Estrutura da tabela parece estar bem organizada!\n";
    }
    
} catch(Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>