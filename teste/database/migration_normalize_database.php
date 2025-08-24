<?php
require_once __DIR__ . '/../config-local.php';

echo "=== SCRIPT DE MIGRAÇÃO - NORMALIZAÇÃO DO BANCO DE DADOS ===\n\n";

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
    
    // Desabilitar verificação de chaves estrangeiras temporariamente
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $migrations = [];
    $errors = [];
    
    echo "=== FASE 1: ADICIONANDO CHAVES ESTRANGEIRAS ===\n\n";
    
    // 1. Adicionar FK na tabela comentarios
    echo "1. Adicionando FKs na tabela comentarios...\n";
    try {
        // Verificar se as FKs já existem
        $stmt = $pdo->query("
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = '{$db['dbname']}' 
            AND TABLE_NAME = 'comentarios' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $existingFKs = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('noticia_id', $existingFKs)) {
            $pdo->exec("ALTER TABLE comentarios ADD FOREIGN KEY (noticia_id) REFERENCES noticias(id) ON DELETE CASCADE");
            $migrations[] = "✓ FK comentarios.noticia_id -> noticias.id";
            echo "  ✓ FK comentarios.noticia_id -> noticias.id adicionada\n";
        } else {
            echo "  - FK comentarios.noticia_id já existe\n";
        }
        
        if (!in_array('usuario_id', $existingFKs)) {
            $pdo->exec("ALTER TABLE comentarios ADD FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE");
            $migrations[] = "✓ FK comentarios.usuario_id -> usuarios.id";
            echo "  ✓ FK comentarios.usuario_id -> usuarios.id adicionada\n";
        } else {
            echo "  - FK comentarios.usuario_id já existe\n";
        }
        
        if (!in_array('comentario_pai_id', $existingFKs)) {
            $pdo->exec("ALTER TABLE comentarios ADD FOREIGN KEY (comentario_pai_id) REFERENCES comentarios(id) ON DELETE CASCADE");
            $migrations[] = "✓ FK comentarios.comentario_pai_id -> comentarios.id";
            echo "  ✓ FK comentarios.comentario_pai_id -> comentarios.id adicionada\n";
        } else {
            echo "  - FK comentarios.comentario_pai_id já existe\n";
        }
        
    } catch (Exception $e) {
        $errors[] = "Erro ao adicionar FKs em comentarios: " . $e->getMessage();
        echo "  ✗ Erro: " . $e->getMessage() . "\n";
    }
    
    // 2. Adicionar FKs nas tabelas de social media
    echo "\n2. Adicionando FKs nas tabelas de social media...\n";
    
    $socialTables = [
        'social_connections' => ['provider_id' => 'providers'], // Assumindo que existe uma tabela providers
        'social_shares' => ['content_id' => 'noticias'],
        'social_webhooks' => [] // webhook_id pode não ter tabela de referência
    ];
    
    foreach ($socialTables as $tableName => $fks) {
        try {
            // Verificar se a tabela existe
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            if (!$stmt->fetch()) {
                echo "  - Tabela $tableName não existe\n";
                continue;
            }
            
            // Verificar FKs existentes
            $stmt = $pdo->query("
                SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = '{$db['dbname']}' 
                AND TABLE_NAME = '$tableName' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            $existingFKs = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($fks as $column => $refTable) {
                if (!in_array($column, $existingFKs)) {
                    // Verificar se a tabela de referência existe
                    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
                    $stmt->execute([$refTable]);
                    if ($stmt->fetch()) {
                        $pdo->exec("ALTER TABLE $tableName ADD FOREIGN KEY ($column) REFERENCES $refTable(id) ON DELETE CASCADE");
                        $migrations[] = "✓ FK $tableName.$column -> $refTable.id";
                        echo "  ✓ FK $tableName.$column -> $refTable.id adicionada\n";
                    } else {
                        echo "  - Tabela de referência $refTable não existe para $tableName.$column\n";
                    }
                } else {
                    echo "  - FK $tableName.$column já existe\n";
                }
            }
            
        } catch (Exception $e) {
            $errors[] = "Erro ao adicionar FKs em $tableName: " . $e->getMessage();
            echo "  ✗ Erro em $tableName: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== FASE 2: CRIANDO TABELA DE PREFERÊNCIAS (OPCIONAL) ===\n\n";
    
    // 3. Criar tabela user_preferences (opcional)
    echo "3. Criando tabela user_preferences...\n";
    try {
        // Verificar se a tabela já existe
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'user_preferences'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            $createTableSQL = "
                CREATE TABLE user_preferences (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    preference_key VARCHAR(100) NOT NULL,
                    preference_value TEXT,
                    data_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_user_preference (user_id, preference_key),
                    INDEX idx_user_preferences (user_id, preference_key)
                )
            ";
            $pdo->exec($createTableSQL);
            $migrations[] = "✓ Tabela user_preferences criada";
            echo "  ✓ Tabela user_preferences criada com sucesso\n";
            
            // Migrar dados existentes do campo preferencias
            echo "  Migrando dados do campo preferencias...\n";
            $stmt = $pdo->query("
                SELECT id, preferencias 
                FROM usuarios 
                WHERE preferencias IS NOT NULL AND preferencias != ''
            ");
            $usuariosComPref = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $migratedCount = 0;
            foreach ($usuariosComPref as $usuario) {
                $jsonData = json_decode($usuario['preferencias'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                    foreach ($jsonData as $key => $value) {
                        $dataType = 'string';
                        if (is_bool($value)) {
                            $dataType = 'boolean';
                            $value = $value ? '1' : '0';
                        } elseif (is_int($value)) {
                            $dataType = 'integer';
                        } elseif (is_array($value) || is_object($value)) {
                            $dataType = 'json';
                            $value = json_encode($value);
                        }
                        
                        $insertStmt = $pdo->prepare("
                            INSERT INTO user_preferences (user_id, preference_key, preference_value, data_type) 
                            VALUES (?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE 
                            preference_value = VALUES(preference_value),
                            data_type = VALUES(data_type),
                            updated_at = CURRENT_TIMESTAMP
                        ");
                        $insertStmt->execute([$usuario['id'], $key, $value, $dataType]);
                    }
                    $migratedCount++;
                }
            }
            echo "  ✓ Migrados dados de $migratedCount usuários\n";
            $migrations[] = "✓ Migrados dados de preferências de $migratedCount usuários";
            
        } else {
            echo "  - Tabela user_preferences já existe\n";
        }
        
    } catch (Exception $e) {
        $errors[] = "Erro ao criar tabela user_preferences: " . $e->getMessage();
        echo "  ✗ Erro: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== FASE 3: CRIANDO ÍNDICES ADICIONAIS ===\n\n";
    
    // 4. Criar índices para melhor performance
    echo "4. Criando índices adicionais...\n";
    $indexes = [
        "CREATE INDEX idx_comentarios_noticia_status ON comentarios(noticia_id, aprovado)",
        "CREATE INDEX idx_comentarios_usuario_data ON comentarios(usuario_id, data_criacao)",
        "CREATE INDEX idx_noticias_autor_status ON noticias(autor_id, status)",
        "CREATE INDEX idx_noticias_categoria_destaque ON noticias(categoria_id, destaque)"
    ];
    
    foreach ($indexes as $indexSQL) {
        try {
            $pdo->exec($indexSQL);
            $indexName = preg_match('/CREATE INDEX (\w+)/', $indexSQL, $matches) ? $matches[1] : 'índice';
            echo "  ✓ Índice $indexName criado\n";
            $migrations[] = "✓ Índice $indexName criado";
        } catch (Exception $e) {
            // Índice pode já existir
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                $indexName = preg_match('/CREATE INDEX (\w+)/', $indexSQL, $matches) ? $matches[1] : 'índice';
                echo "  - Índice $indexName já existe\n";
            } else {
                $errors[] = "Erro ao criar índice: " . $e->getMessage();
                echo "  ✗ Erro ao criar índice: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Reabilitar verificação de chaves estrangeiras
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n=== RESUMO DA MIGRAÇÃO ===\n\n";
    
    if (!empty($migrations)) {
        echo "MUDANÇAS APLICADAS COM SUCESSO:\n";
        foreach ($migrations as $migration) {
            echo "$migration\n";
        }
    }
    
    if (!empty($errors)) {
        echo "\nERROS ENCONTRADOS:\n";
        foreach ($errors as $error) {
            echo "✗ $error\n";
        }
    }
    
    echo "\n=== PRÓXIMOS PASSOS RECOMENDADOS ===\n\n";
    echo "1. Testar todas as funcionalidades do sistema\n";
    echo "2. Verificar se as consultas estão funcionando corretamente\n";
    echo "3. Atualizar código PHP se necessário para usar nova estrutura\n";
    echo "4. Considerar remover campo 'preferencias' da tabela usuarios se migração foi bem-sucedida\n";
    echo "5. Fazer backup completo do banco após validação\n";
    
    echo "\n=== MIGRAÇÃO CONCLUÍDA ===\n";
    
} catch (Exception $e) {
    echo "✗ ERRO CRÍTICO: " . $e->getMessage() . "\n";
    // Tentar reabilitar verificação de FKs em caso de erro
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $e2) {
        // Ignorar erro secundário
    }
}
?>