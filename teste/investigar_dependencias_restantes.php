<?php
/**
 * Investigação detalhada das tabelas restantes
 * most_shared_content e provider_share_stats
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "=== INVESTIGAÇÃO DE DEPENDÊNCIAS ===\n\n";
    
    $tabelasRestantes = ['most_shared_content', 'provider_share_stats'];
    
    foreach($tabelasRestantes as $tabela) {
        echo "🔍 INVESTIGANDO: $tabela\n";
        echo str_repeat("-", 50) . "\n";
        
        // 1. Verificar se a tabela existe
        try {
            $query = "SHOW CREATE TABLE `$tabela`";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "✅ Tabela existe\n";
            echo "📋 Estrutura:\n";
            echo $createTable['Create Table'] . "\n\n";
            
        } catch(Exception $e) {
            echo "❌ Tabela não encontrada: " . $e->getMessage() . "\n\n";
            continue;
        }
        
        // 2. Verificar chaves estrangeiras que REFERENCIAM esta tabela
        echo "🔗 Verificando dependências (FKs que apontam para esta tabela):\n";
        try {
            $query = "SELECT 
                        TABLE_NAME,
                        COLUMN_NAME,
                        CONSTRAINT_NAME,
                        REFERENCED_TABLE_NAME,
                        REFERENCED_COLUMN_NAME
                     FROM information_schema.KEY_COLUMN_USAGE 
                     WHERE REFERENCED_TABLE_NAME = ? 
                       AND TABLE_SCHEMA = DATABASE()";
            $stmt = $conn->prepare($query);
            $stmt->execute([$tabela]);
            $dependencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if(empty($dependencias)) {
                echo "✅ Nenhuma FK aponta para esta tabela\n";
            } else {
                echo "⚠️  Encontradas " . count($dependencias) . " dependências:\n";
                foreach($dependencias as $dep) {
                    echo "   - {$dep['TABLE_NAME']}.{$dep['COLUMN_NAME']} -> {$dep['REFERENCED_TABLE_NAME']}.{$dep['REFERENCED_COLUMN_NAME']}\n";
                }
            }
        } catch(Exception $e) {
            echo "❌ Erro ao verificar FKs: " . $e->getMessage() . "\n";
        }
        
        // 3. Verificar triggers
        echo "\n🎯 Verificando triggers:\n";
        try {
            $query = "SHOW TRIGGERS LIKE ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$tabela]);
            $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if(empty($triggers)) {
                echo "✅ Nenhum trigger encontrado\n";
            } else {
                echo "⚠️  Encontrados " . count($triggers) . " triggers:\n";
                foreach($triggers as $trigger) {
                    echo "   - {$trigger['Trigger']} ({$trigger['Event']} {$trigger['Timing']})\n";
                }
            }
        } catch(Exception $e) {
            echo "❌ Erro ao verificar triggers: " . $e->getMessage() . "\n";
        }
        
        // 4. Verificar views que usam esta tabela
        echo "\n👁️  Verificando views dependentes:\n";
        try {
            $query = "SELECT TABLE_NAME, VIEW_DEFINITION 
                     FROM information_schema.VIEWS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                       AND VIEW_DEFINITION LIKE ?";
            $stmt = $conn->prepare($query);
            $stmt->execute(["%$tabela%"]);
            $views = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if(empty($views)) {
                echo "✅ Nenhuma view depende desta tabela\n";
            } else {
                echo "⚠️  Encontradas " . count($views) . " views dependentes:\n";
                foreach($views as $view) {
                    echo "   - {$view['TABLE_NAME']}\n";
                }
            }
        } catch(Exception $e) {
            echo "❌ Erro ao verificar views: " . $e->getMessage() . "\n";
        }
        
        // 5. Verificar dados na tabela
        echo "\n📊 Verificando dados:\n";
        try {
            $query = "SELECT COUNT(*) as total FROM `$tabela`";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "📈 Total de registros: {$count['total']}\n";
            
            if($count['total'] > 0) {
                echo "⚠️  ATENÇÃO: Tabela contém dados!\n";
            } else {
                echo "✅ Tabela vazia - segura para remoção\n";
            }
        } catch(Exception $e) {
            echo "❌ Erro ao contar registros: " . $e->getMessage() . "\n";
        }
        
        echo "\n" . str_repeat("=", 60) . "\n\n";
    }
    
    // Tentar remoção forçada com mais detalhes
    echo "🚀 TENTATIVA DE REMOÇÃO FORÇADA\n";
    echo str_repeat("=", 50) . "\n";
    
    foreach($tabelasRestantes as $tabela) {
        echo "\n🎯 Tentando remover: $tabela\n";
        
        try {
            // Desabilitar verificações
            $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
            $conn->exec("SET SQL_SAFE_UPDATES = 0");
            
            // Tentar DROP TABLE direto
            $query = "DROP TABLE `$tabela`";
            $conn->exec($query);
            
            echo "✅ SUCESSO: $tabela removida!\n";
            
        } catch(Exception $e) {
            echo "❌ FALHA: " . $e->getMessage() . "\n";
            
            // Tentar com IF EXISTS
            try {
                $query = "DROP TABLE IF EXISTS `$tabela`";
                $conn->exec($query);
                echo "✅ SUCESSO (com IF EXISTS): $tabela removida!\n";
            } catch(Exception $e2) {
                echo "❌ FALHA TOTAL: " . $e2->getMessage() . "\n";
            }
        }
        
        // Reabilitar verificações
        try {
            $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
            $conn->exec("SET SQL_SAFE_UPDATES = 1");
        } catch(Exception $e) {
            echo "⚠️  Aviso ao reabilitar verificações: " . $e->getMessage() . "\n";
        }
    }
    
    // Verificação final
    echo "\n🔍 VERIFICAÇÃO FINAL\n";
    echo str_repeat("-", 30) . "\n";
    
    foreach($tabelasRestantes as $tabela) {
        try {
            $query = "SELECT 1 FROM `$tabela` LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            echo "❌ $tabela - AINDA EXISTE\n";
        } catch(Exception $e) {
            if(strpos($e->getMessage(), "doesn't exist") !== false) {
                echo "✅ $tabela - REMOVIDA COM SUCESSO\n";
            } else {
                echo "⚠️  $tabela - STATUS INCERTO: " . $e->getMessage() . "\n";
            }
        }
    }
    
} catch(Exception $e) {
    echo "❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
}

echo "\n=== INVESTIGAÇÃO CONCLUÍDA ===\n";
?>