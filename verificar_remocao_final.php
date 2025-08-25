<?php
/**
 * Script para verificar e finalizar remoção das tabelas
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "=== VERIFICAÇÃO E FINALIZAÇÃO DA REMOÇÃO ===\n\n";
    
    // Verificar quais tabelas ainda existem
    $tabelasAlvo = [
        'curtidas_comentarios',
        'midias', 
        'most_shared_content',
        'provider_share_stats',
        'social_share_stats'
    ];
    
    echo "Verificando status das tabelas...\n\n";
    
    foreach($tabelasAlvo as $tabela) {
        $query = "SELECT COUNT(*) as existe FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'portal_noticias' AND TABLE_NAME = '$tabela'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $existe = $stmt->fetch(PDO::FETCH_ASSOC)['existe'];
        
        if($existe > 0) {
            echo "❌ $tabela - AINDA EXISTE\n";
            
            // Tentar remover novamente
            try {
                $dropQuery = "DROP TABLE IF EXISTS `$tabela`";
                $dropStmt = $conn->prepare($dropQuery);
                $dropStmt->execute();
                echo "   ✅ Removida com sucesso\n";
            } catch(Exception $e) {
                echo "   ❌ Erro ao remover: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✅ $tabela - REMOVIDA\n";
        }
    }
    
    echo "\n=== VERIFICAÇÃO FINAL ===\n\n";
    
    // Contar tabelas restantes no banco
    $query = "SELECT COUNT(*) as total FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'portal_noticias'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $totalTabelas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "📊 Total de tabelas no banco: $totalTabelas\n";
    
    // Listar todas as tabelas restantes
    $query = "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'portal_noticias' ORDER BY TABLE_NAME";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\n📋 Tabelas restantes no banco:\n";
    foreach($tabelas as $tabela) {
        echo "- $tabela\n";
    }
    
    // Verificar se ainda existem tabelas não utilizadas
    $tabelasNaoUtilizadas = array_intersect($tabelasAlvo, $tabelas);
    
    if(empty($tabelasNaoUtilizadas)) {
        echo "\n✅ SUCESSO: Todas as tabelas não utilizadas foram removidas!\n";
        echo "\n🎉 LIMPEZA DO BANCO CONCLUÍDA COM SUCESSO!\n";
    } else {
        echo "\n⚠️  ATENÇÃO: Ainda existem tabelas não utilizadas:\n";
        foreach($tabelasNaoUtilizadas as $tabela) {
            echo "- $tabela\n";
        }
    }
    
} catch(Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

echo "\n=== VERIFICAÇÃO CONCLUÍDA ===\n";
?>