<?php
/**
 * Script para executar remoção segura das tabelas vazias
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "=== REMOÇÃO SEGURA DE TABELAS VAZIAS ===\n\n";
    
    // Desabilitar verificação de chaves estrangeiras temporariamente
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $tabelasParaRemover = [
        'curtidas_comentarios',
        'midias', 
        'most_shared_content',
        'provider_share_stats',
        'social_share_stats'
    ];
    
    $removidas = [];
    $erros = [];
    
    foreach($tabelasParaRemover as $tabela) {
        try {
            echo "Removendo tabela: $tabela...";
            
            $query = "DROP TABLE IF EXISTS `$tabela`";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            
            $removidas[] = $tabela;
            echo " ✅ REMOVIDA\n";
            
        } catch(Exception $e) {
            $erros[] = "$tabela: " . $e->getMessage();
            echo " ❌ ERRO: " . $e->getMessage() . "\n";
        }
    }
    
    // Reabilitar verificação de chaves estrangeiras
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n=== RESULTADO DA REMOÇÃO ===\n\n";
    
    if(!empty($removidas)) {
        echo "✅ TABELAS REMOVIDAS COM SUCESSO (" . count($removidas) . "):";
        foreach($removidas as $tabela) {
            echo "\n- $tabela";
        }
        echo "\n\n";
    }
    
    if(!empty($erros)) {
        echo "❌ ERROS ENCONTRADOS (" . count($erros) . "):";
        foreach($erros as $erro) {
            echo "\n- $erro";
        }
        echo "\n\n";
    }
    
    // Verificar se as tabelas foram realmente removidas
    echo "=== VERIFICAÇÃO FINAL ===\n\n";
    
    $query = "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'portal_noticias' AND TABLE_NAME IN ('curtidas_comentarios', 'midias', 'most_shared_content', 'provider_share_stats', 'social_share_stats')";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $tabelasRestantes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if(empty($tabelasRestantes)) {
        echo "✅ CONFIRMADO: Todas as tabelas foram removidas com sucesso!\n";
    } else {
        echo "⚠️  ATENÇÃO: Algumas tabelas ainda existem:\n";
        foreach($tabelasRestantes as $tabela) {
            echo "- $tabela\n";
        }
    }
    
    // Otimizar tabelas restantes
    echo "\n=== OTIMIZAÇÃO PÓS-REMOÇÃO ===\n\n";
    
    try {
        echo "Otimizando tabelas principais...\n";
        $conn->exec("OPTIMIZE TABLE usuarios, noticias, categorias, comentarios");
        echo "✅ Otimização concluída\n";
    } catch(Exception $e) {
        echo "⚠️  Aviso na otimização: " . $e->getMessage() . "\n";
    }
    
    // Contar tabelas restantes
    $query = "SELECT COUNT(*) as total FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'portal_noticias'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $totalTabelas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "\n📊 ESTATÍSTICAS FINAIS:\n";
    echo "- Tabelas removidas: " . count($removidas) . "\n";
    echo "- Tabelas restantes no banco: $totalTabelas\n";
    echo "- Erros encontrados: " . count($erros) . "\n";
    
} catch(Exception $e) {
    echo "❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
}

echo "\n=== REMOÇÃO CONCLUÍDA ===\n";
?>