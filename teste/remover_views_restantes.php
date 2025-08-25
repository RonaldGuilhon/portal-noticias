<?php
/**
 * Remoção específica de VIEWS restantes
 * most_shared_content e provider_share_stats são VIEWS, não tabelas
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "=== REMOÇÃO DE VIEWS RESTANTES ===\n\n";
    
    $viewsRestantes = ['most_shared_content', 'provider_share_stats'];
    
    // Primeiro, vamos confirmar que são views
    echo "🔍 CONFIRMANDO TIPO DOS OBJETOS:\n";
    foreach($viewsRestantes as $objeto) {
        try {
            // Verificar se é uma view
            $query = "SELECT TABLE_TYPE FROM information_schema.TABLES 
                     WHERE TABLE_SCHEMA = DATABASE() 
                       AND TABLE_NAME = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$objeto]);
            $tipo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($tipo) {
                echo "📋 $objeto: {$tipo['TABLE_TYPE']}\n";
            } else {
                echo "❓ $objeto: NÃO ENCONTRADO\n";
            }
        } catch(Exception $e) {
            echo "❌ Erro ao verificar $objeto: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
    
    // Agora vamos remover as views
    echo "🗑️  REMOVENDO VIEWS:\n\n";
    
    $removidas = 0;
    
    foreach($viewsRestantes as $view) {
        echo "🎯 Removendo view: $view\n";
        
        try {
            // Tentar DROP VIEW
            $query = "DROP VIEW IF EXISTS `$view`";
            $conn->exec($query);
            
            echo "✅ SUCESSO: View $view removida!\n";
            $removidas++;
            
        } catch(Exception $e) {
            echo "❌ FALHA: " . $e->getMessage() . "\n";
            
            // Tentar como tabela (fallback)
            try {
                $query = "DROP TABLE IF EXISTS `$view`";
                $conn->exec($query);
                echo "✅ SUCESSO (como tabela): $view removida!\n";
                $removidas++;
            } catch(Exception $e2) {
                echo "❌ FALHA TOTAL: " . $e2->getMessage() . "\n";
            }
        }
        
        echo "\n";
    }
    
    // Verificação final
    echo str_repeat("-", 50) . "\n";
    echo "🔍 VERIFICAÇÃO FINAL:\n\n";
    
    $existentesAinda = [];
    
    foreach($viewsRestantes as $view) {
        try {
            // Verificar se ainda existe
            $query = "SELECT TABLE_TYPE FROM information_schema.TABLES 
                     WHERE TABLE_SCHEMA = DATABASE() 
                       AND TABLE_NAME = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$view]);
            $existe = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($existe) {
                echo "❌ $view - AINDA EXISTE ({$existe['TABLE_TYPE']})\n";
                $existentesAinda[] = $view;
            } else {
                echo "✅ $view - REMOVIDA COM SUCESSO\n";
            }
        } catch(Exception $e) {
            echo "⚠️  $view - ERRO NA VERIFICAÇÃO: " . $e->getMessage() . "\n";
        }
    }
    
    // Estatísticas finais
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "📊 RESULTADO FINAL:\n\n";
    
    // Contar todas as tabelas/views restantes
    try {
        $query = "SELECT COUNT(*) as total FROM information_schema.TABLES 
                 WHERE TABLE_SCHEMA = DATABASE()";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $totalObjetos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "📈 Total de objetos no banco: {$totalObjetos['total']}\n";
        echo "🗑️  Views removidas nesta operação: $removidas\n";
        
        if(empty($existentesAinda)) {
            echo "\n🎉 SUCESSO COMPLETO!\n";
            echo "✅ Todas as views não utilizadas foram removidas!\n";
            echo "\n🧹 LIMPEZA FINAL DO BANCO CONCLUÍDA!\n";
            
            // Listar objetos finais
            echo "\n📋 OBJETOS RESTANTES NO BANCO:\n";
            $query = "SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     ORDER BY TABLE_TYPE, TABLE_NAME";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $objetos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $tabelas = 0;
            $views = 0;
            
            foreach($objetos as $obj) {
                echo "   - {$obj['TABLE_NAME']} ({$obj['TABLE_TYPE']})\n";
                if($obj['TABLE_TYPE'] === 'BASE TABLE') {
                    $tabelas++;
                } else {
                    $views++;
                }
            }
            
            echo "\n📊 RESUMO FINAL:\n";
            echo "   - Tabelas: $tabelas\n";
            echo "   - Views: $views\n";
            echo "   - Total: {$totalObjetos['total']}\n";
            
        } else {
            echo "\n⚠️  OBJETOS AINDA EXISTENTES:\n";
            foreach($existentesAinda as $obj) {
                echo "   - $obj\n";
            }
        }
        
    } catch(Exception $e) {
        echo "❌ Erro ao obter estatísticas finais: " . $e->getMessage() . "\n";
    }
    
} catch(Exception $e) {
    echo "❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
}

echo "\n=== OPERAÇÃO CONCLUÍDA ===\n";
?>