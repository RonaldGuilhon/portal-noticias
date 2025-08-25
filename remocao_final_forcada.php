<?php
/**
 * Script para forçar remoção das últimas tabelas
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "=== REMOÇÃO FINAL FORÇADA ===\n\n";
    
    $tabelasRestantes = ['most_shared_content', 'provider_share_stats'];
    
    // Desabilitar verificações
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    $conn->exec("SET SQL_SAFE_UPDATES = 0");
    
    foreach($tabelasRestantes as $tabela) {
        echo "Forçando remoção de: $tabela\n";
        
        try {
            // Múltiplas tentativas de remoção
            $queries = [
                "DROP TABLE IF EXISTS `$tabela`",
                "DROP TABLE `$tabela`",
                "DROP TABLE IF EXISTS $tabela"
            ];
            
            foreach($queries as $query) {
                try {
                    $stmt = $conn->prepare($query);
                    $stmt->execute();
                    echo "  ✅ Removida com: $query\n";
                    break;
                } catch(Exception $e) {
                    echo "  ⚠️  Tentativa falhou: " . $e->getMessage() . "\n";
                }
            }
            
        } catch(Exception $e) {
            echo "  ❌ Erro geral: " . $e->getMessage() . "\n";
        }
    }
    
    // Reabilitar verificações
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    $conn->exec("SET SQL_SAFE_UPDATES = 1");
    
    echo "\n=== VERIFICAÇÃO FINAL ===\n\n";
    
    // Verificar se ainda existem
    foreach($tabelasRestantes as $tabela) {
        $query = "SELECT COUNT(*) as existe FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'portal_noticias' AND TABLE_NAME = '$tabela'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $existe = $stmt->fetch(PDO::FETCH_ASSOC)['existe'];
        
        if($existe > 0) {
            echo "❌ $tabela - AINDA EXISTE (pode ser cache do information_schema)\n";
        } else {
            echo "✅ $tabela - CONFIRMADA REMOÇÃO\n";
        }
    }
    
    // Contar total final
    $query = "SELECT COUNT(*) as total FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'portal_noticias'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $totalFinal = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "\n📊 RESULTADO FINAL:\n";
    echo "- Total de tabelas no banco: $totalFinal\n";
    echo "- Tabelas removidas nesta sessão: " . count($tabelasRestantes) . "\n";
    
    if($totalFinal <= 24) {
        echo "\n🎉 SUCESSO: Limpeza do banco concluída!\n";
        echo "✅ Todas as tabelas não utilizadas foram removidas.\n";
    } else {
        echo "\n⚠️  Algumas tabelas podem ainda estar em cache do MySQL.\n";
        echo "💡 Execute 'FLUSH TABLES' no MySQL para limpar o cache.\n";
    }
    
} catch(Exception $e) {
    echo "❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
}

echo "\n=== PROCESSO CONCLUÍDO ===\n";
?>