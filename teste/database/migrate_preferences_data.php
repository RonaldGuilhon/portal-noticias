<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=portal_noticias', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== MIGRAÇÃO DOS DADOS DE PREFERÊNCIAS ===\n\n";
    
    // Buscar usuários com preferências
    echo "1. Buscando usuários com preferências...\n";
    $stmt = $pdo->query("
        SELECT id, nome, preferencias 
        FROM usuarios 
        WHERE preferencias IS NOT NULL AND preferencias != ''
    ");
    $usuariosComPref = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   - Encontrados " . count($usuariosComPref) . " usuários com preferências\n\n";
    
    if (empty($usuariosComPref)) {
        echo "Nenhum usuário com preferências para migrar.\n";
        exit(0);
    }
    
    echo "2. Migrando dados...\n";
    $migratedCount = 0;
    $totalPreferences = 0;
    
    foreach ($usuariosComPref as $usuario) {
        echo "   - Processando usuário ID {$usuario['id']} ({$usuario['nome']})...\n";
        echo "     Preferências: {$usuario['preferencias']}\n";
        
        $jsonData = json_decode($usuario['preferencias'], true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($jsonData) && !empty($jsonData)) {
                foreach ($jsonData as $key => $value) {
                    $dataType = 'string';
                    $processedValue = $value;
                    
                    if (is_bool($value)) {
                        $dataType = 'boolean';
                        $processedValue = $value ? '1' : '0';
                    } elseif (is_int($value)) {
                        $dataType = 'integer';
                        $processedValue = (string)$value;
                    } elseif (is_array($value) || is_object($value)) {
                        $dataType = 'json';
                        $processedValue = json_encode($value);
                    } else {
                        $processedValue = (string)$value;
                    }
                    
                    $insertStmt = $pdo->prepare("
                        INSERT INTO user_preferences (user_id, preference_key, preference_value, data_type) 
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                        preference_value = VALUES(preference_value),
                        data_type = VALUES(data_type),
                        updated_at = CURRENT_TIMESTAMP
                    ");
                    
                    $insertStmt->execute([$usuario['id'], $key, $processedValue, $dataType]);
                    $totalPreferences++;
                    
                    echo "     ✓ Migrada preferência: $key = $processedValue ($dataType)\n";
                }
                $migratedCount++;
            } else {
                echo "     - Array JSON vazio, nada para migrar\n";
            }
        } else {
            echo "     ✗ Erro ao decodificar JSON: " . json_last_error_msg() . "\n";
            
            // Tentar como string simples
            if (!empty($usuario['preferencias'])) {
                $insertStmt = $pdo->prepare("
                    INSERT INTO user_preferences (user_id, preference_key, preference_value, data_type) 
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    preference_value = VALUES(preference_value),
                    data_type = VALUES(data_type),
                    updated_at = CURRENT_TIMESTAMP
                ");
                
                $insertStmt->execute([$usuario['id'], 'raw_preferences', $usuario['preferencias'], 'string']);
                $totalPreferences++;
                $migratedCount++;
                
                echo "     ✓ Migrada como string simples: raw_preferences\n";
            }
        }
        echo "\n";
    }
    
    echo "=== RESUMO DA MIGRAÇÃO ===\n";
    echo "- Usuários processados: " . count($usuariosComPref) . "\n";
    echo "- Usuários migrados com sucesso: $migratedCount\n";
    echo "- Total de preferências migradas: $totalPreferences\n";
    
    // Verificar resultado final
    echo "\n3. Verificando resultado...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM user_preferences");
    $totalFinal = $stmt->fetch()['total'];
    echo "   - Total de registros na tabela user_preferences: $totalFinal\n";
    
    if ($totalFinal > 0) {
        echo "\n   Exemplos de dados migrados:\n";
        $stmt = $pdo->query("SELECT * FROM user_preferences LIMIT 5");
        while ($row = $stmt->fetch()) {
            echo "   - User {$row['user_id']}: {$row['preference_key']} = {$row['preference_value']} ({$row['data_type']})\n";
        }
    }
    
    echo "\n✓ MIGRAÇÃO CONCLUÍDA COM SUCESSO!\n";
    
} catch (Exception $e) {
    echo "✗ Erro durante a migração: " . $e->getMessage() . "\n";
    exit(1);
}