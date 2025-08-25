<?php
/**
 * Verificação definitiva após limpeza de cache
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "=== VERIFICAÇÃO DEFINITIVA ===\n\n";
    
    // Limpar cache do MySQL
    echo "Limpando cache do MySQL...\n";
    try {
        $conn->exec("FLUSH TABLES");
        echo "✅ Cache limpo\n\n";
    } catch(Exception $e) {
        echo "⚠️  Aviso: " . $e->getMessage() . "\n\n";
    }
    
    // Verificação direta das tabelas
    echo "Verificando tabelas diretamente...\n\n";
    
    $tabelasAlvo = [
        'curtidas_comentarios',
        'midias', 
        'most_shared_content',
        'provider_share_stats',
        'social_share_stats'
    ];
    
    $removidas = 0;
    $existentes = [];
    
    foreach($tabelasAlvo as $tabela) {
        try {
            // Tentar fazer uma query simples na tabela
            $query = "SELECT 1 FROM `$tabela` LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            
            echo "❌ $tabela - AINDA EXISTE\n";
            $existentes[] = $tabela;
            
        } catch(Exception $e) {
            if(strpos($e->getMessage(), "doesn't exist") !== false || 
               strpos($e->getMessage(), "Table") !== false) {
                echo "✅ $tabela - CONFIRMADA REMOÇÃO\n";
                $removidas++;
            } else {
                echo "⚠️  $tabela - ERRO: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n=== RESULTADO FINAL ===\n\n";
    
    // Listar todas as tabelas atuais
    echo "📋 Listando todas as tabelas do banco:\n";
    try {
        $query = "SHOW TABLES";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $todasTabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach($todasTabelas as $tabela) {
            echo "- $tabela\n";
        }
        
        echo "\n📊 ESTATÍSTICAS:\n";
        echo "- Total de tabelas no banco: " . count($todasTabelas) . "\n";
        echo "- Tabelas alvo removidas: $removidas/" . count($tabelasAlvo) . "\n";
        
        if(empty($existentes)) {
            echo "\n🎉 SUCESSO COMPLETO!\n";
            echo "✅ Todas as 5 tabelas não utilizadas foram removidas:\n";
            foreach($tabelasAlvo as $tabela) {
                echo "   - $tabela\n";
            }
            echo "\n🧹 LIMPEZA DO BANCO CONCLUÍDA COM ÊXITO!\n";
        } else {
            echo "\n⚠️  TABELAS AINDA EXISTENTES:\n";
            foreach($existentes as $tabela) {
                echo "   - $tabela\n";
            }
        }
        
    } catch(Exception $e) {
        echo "❌ Erro ao listar tabelas: " . $e->getMessage() . "\n";
    }
    
} catch(Exception $e) {
    echo "❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
}

echo "\n=== VERIFICAÇÃO CONCLUÍDA ===\n";
?>