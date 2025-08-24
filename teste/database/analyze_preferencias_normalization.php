<?php
require_once __DIR__ . '/../config-local.php';

echo "=== ANÁLISE DE NORMALIZAÇÃO DO CAMPO PREFERENCIAS ===\n\n";

try {
    // Conectar ao banco
    $db = $config['database'];
    $pdo = new PDO(
        "mysql:host={$db['host']};dbname={$db['dbname']};charset=utf8mb4",
        $db['username'],
        $db['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Conexão estabelecida\n\n";
    
    // Analisar estrutura da tabela usuarios
    echo "=== ESTRUTURA ATUAL DA TABELA USUARIOS ===\n";
    $stmt = $pdo->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasPreferencias = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'preferencias') {
            $hasPreferencias = true;
            $key = $column['Key'] ? " [{$column['Key']}]" : "";
            echo "Campo 'preferencias': {$column['Type']}{$key}\n";
            echo "Permite NULL: {$column['Null']}\n";
            echo "Valor padrão: {$column['Default']}\n";
            break;
        }
    }
    
    if (!$hasPreferencias) {
        echo "✗ Campo 'preferencias' não encontrado na tabela usuarios\n";
        echo "=== ANÁLISE CONCLUÍDA ===\n";
        exit;
    }
    
    // Verificar dados existentes
    echo "\n=== ANÁLISE DOS DADOS EXISTENTES ===\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $totalUsuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total de usuários: $totalUsuarios\n";
    
    // Verificar quantos usuários têm preferências definidas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE preferencias IS NOT NULL AND preferencias != ''");
    $usuariosComPreferencias = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Usuários com preferências definidas: $usuariosComPreferencias\n";
    
    if ($usuariosComPreferencias > 0) {
        // Analisar o conteúdo das preferências
        echo "\n--- ANÁLISE DO CONTEÚDO DAS PREFERÊNCIAS ---\n";
        $stmt = $pdo->query("
            SELECT id, nome, preferencias 
            FROM usuarios 
            WHERE preferencias IS NOT NULL AND preferencias != '' 
            LIMIT 10
        ");
        $usuariosComPref = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $jsonCount = 0;
        $serializedCount = 0;
        $plainTextCount = 0;
        $preferencesStructure = [];
        
        foreach ($usuariosComPref as $usuario) {
            $pref = $usuario['preferencias'];
            echo "\nUsuário ID {$usuario['id']} ({$usuario['nome']}): ";
            
            // Tentar decodificar como JSON
            $jsonData = json_decode($pref, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $jsonCount++;
                echo "JSON válido\n";
                echo "  Conteúdo: " . json_encode($jsonData, JSON_PRETTY_PRINT) . "\n";
                
                // Coletar estrutura das preferências
                foreach ($jsonData as $key => $value) {
                    if (!isset($preferencesStructure[$key])) {
                        $preferencesStructure[$key] = [];
                    }
                    $preferencesStructure[$key][] = $value;
                }
            } else {
                // Tentar decodificar como serializado
                $unserializedData = @unserialize($pref);
                if ($unserializedData !== false) {
                    $serializedCount++;
                    echo "Dados serializados\n";
                    echo "  Conteúdo: " . print_r($unserializedData, true) . "\n";
                } else {
                    $plainTextCount++;
                    echo "Texto simples\n";
                    echo "  Conteúdo: " . substr($pref, 0, 100) . "...\n";
                }
            }
        }
        
        echo "\n--- RESUMO DOS FORMATOS ---\n";
        echo "JSON: $jsonCount\n";
        echo "Serializado: $serializedCount\n";
        echo "Texto simples: $plainTextCount\n";
        
        if (!empty($preferencesStructure)) {
            echo "\n--- ESTRUTURA DAS PREFERÊNCIAS JSON ---\n";
            foreach ($preferencesStructure as $key => $values) {
                $uniqueValues = array_unique($values);
                echo "$key: " . implode(', ', array_slice($uniqueValues, 0, 5)) . "\n";
            }
        }
    }
    
    // Proposta de normalização
    echo "\n=== PROPOSTA DE NORMALIZAÇÃO ===\n\n";
    
    if ($usuariosComPreferencias > 0) {
        echo "ESTRUTURA PROPOSTA PARA TABELA 'user_preferences':\n";
        echo "CREATE TABLE user_preferences (\n";
        echo "    id INT AUTO_INCREMENT PRIMARY KEY,\n";
        echo "    user_id INT NOT NULL,\n";
        echo "    preference_key VARCHAR(100) NOT NULL,\n";
        echo "    preference_value TEXT,\n";
        echo "    data_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',\n";
        echo "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
        echo "    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";
        echo "    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,\n";
        echo "    UNIQUE KEY unique_user_preference (user_id, preference_key),\n";
        echo "    INDEX idx_user_preferences (user_id, preference_key)\n";
        echo ");\n\n";
        
        echo "BENEFÍCIOS DA NORMALIZAÇÃO:\n";
        echo "- Consultas mais eficientes por preferência específica\n";
        echo "- Facilita adição/remoção de preferências individuais\n";
        echo "- Permite indexação por tipo de preferência\n";
        echo "- Melhor integridade referencial\n";
        echo "- Facilita relatórios e análises\n\n";
        
        echo "SCRIPT DE MIGRAÇÃO NECESSÁRIO:\n";
        echo "1. Criar nova tabela user_preferences\n";
        echo "2. Migrar dados do campo preferencias para a nova tabela\n";
        echo "3. Atualizar código PHP para usar nova estrutura\n";
        echo "4. Remover campo preferencias da tabela usuarios\n\n";
        
        echo "ALTERNATIVA (MANTER CAMPO JSON):\n";
        echo "Se as preferências são sempre acessadas em conjunto,\n";
        echo "pode ser mais eficiente manter como JSON com validação:\n";
        echo "- Adicionar constraint JSON válido\n";
        echo "- Criar índices funcionais para chaves específicas\n";
        echo "- Padronizar estrutura JSON\n";
    } else {
        echo "✓ CAMPO PREFERENCIAS ESTÁ VAZIO\n";
        echo "Como não há dados no campo preferencias, a normalização\n";
        echo "pode ser feita diretamente removendo o campo ou\n";
        echo "definindo uma estrutura padrão para uso futuro.\n";
    }
    
    echo "\n=== ANÁLISE CONCLUÍDA ===\n";
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
}
?>