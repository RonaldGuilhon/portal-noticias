<?php
/**
 * Validação da estrutura real das tabelas do banco portal_noticias
 * Complementa o relatório de tabelas e utilidades
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "=== VALIDAÇÃO DA ESTRUTURA DAS TABELAS ===\n\n";
    
    // Obter todas as tabelas e views
    $query = "SELECT TABLE_NAME, TABLE_TYPE, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH 
             FROM information_schema.TABLES 
             WHERE TABLE_SCHEMA = DATABASE() 
             ORDER BY TABLE_TYPE, TABLE_NAME";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $objetos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 RESUMO GERAL:\n";
    echo "Total de objetos: " . count($objetos) . "\n\n";
    
    $tabelas = [];
    $views = [];
    
    foreach($objetos as $obj) {
        if($obj['TABLE_TYPE'] === 'BASE TABLE') {
            $tabelas[] = $obj;
        } else {
            $views[] = $obj;
        }
    }
    
    echo "📋 TABELAS (" . count($tabelas) . "):\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-25s %-10s %-15s %-15s\n", "NOME", "REGISTROS", "TAMANHO (KB)", "ÍNDICES (KB)");
    echo str_repeat("-", 80) . "\n";
    
    $totalRegistros = 0;
    $totalTamanho = 0;
    
    foreach($tabelas as $tabela) {
        $tamanhoKB = round($tabela['DATA_LENGTH'] / 1024, 2);
        $indicesKB = round($tabela['INDEX_LENGTH'] / 1024, 2);
        $registros = $tabela['TABLE_ROWS'] ?? 0;
        
        printf("%-25s %-10s %-15s %-15s\n", 
            $tabela['TABLE_NAME'], 
            number_format($registros), 
            number_format($tamanhoKB, 2), 
            number_format($indicesKB, 2)
        );
        
        $totalRegistros += $registros;
        $totalTamanho += $tamanhoKB + $indicesKB;
    }
    
    echo str_repeat("-", 80) . "\n";
    printf("%-25s %-10s %-15s\n", "TOTAL", number_format($totalRegistros), number_format($totalTamanho, 2) . " KB");
    
    echo "\n\n👁️  VIEWS (" . count($views) . "):\n";
    foreach($views as $view) {
        echo "- {$view['TABLE_NAME']}\n";
    }
    
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "🔍 ANÁLISE DETALHADA DAS PRINCIPAIS TABELAS\n";
    echo str_repeat("=", 80) . "\n\n";
    
    // Analisar estrutura das principais tabelas
    $tabelasPrincipais = [
        'noticias' => 'Conteúdo principal do portal',
        'usuarios' => 'Base de usuários do sistema',
        'comentarios' => 'Sistema de comentários',
        'categorias' => 'Organização do conteúdo',
        'estatisticas_acesso' => 'Analytics e métricas',
        'social_shares' => 'Compartilhamentos sociais',
        'configuracoes' => 'Configurações do sistema'
    ];
    
    foreach($tabelasPrincipais as $nomeTabela => $descricao) {
        echo "📋 TABELA: $nomeTabela\n";
        echo "📝 Descrição: $descricao\n";
        
        try {
            // Obter estrutura da tabela
            $query = "DESCRIBE `$nomeTabela`";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "🏗️  Estrutura (" . count($colunas) . " colunas):\n";
            foreach($colunas as $coluna) {
                $null = $coluna['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
                $key = $coluna['Key'] ? " [{$coluna['Key']}]" : '';
                $default = $coluna['Default'] ? " DEFAULT: {$coluna['Default']}" : '';
                echo "   - {$coluna['Field']}: {$coluna['Type']} $null$key$default\n";
            }
            
            // Contar registros
            $query = "SELECT COUNT(*) as total FROM `$nomeTabela`";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "📊 Total de registros: {$count['total']}\n";
            
            // Verificar chaves estrangeiras
            $query = "SELECT 
                        COLUMN_NAME,
                        REFERENCED_TABLE_NAME,
                        REFERENCED_COLUMN_NAME
                     FROM information_schema.KEY_COLUMN_USAGE 
                     WHERE TABLE_SCHEMA = DATABASE() 
                       AND TABLE_NAME = ? 
                       AND REFERENCED_TABLE_NAME IS NOT NULL";
            $stmt = $conn->prepare($query);
            $stmt->execute([$nomeTabela]);
            $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if(!empty($fks)) {
                echo "🔗 Relacionamentos (FKs):\n";
                foreach($fks as $fk) {
                    echo "   - {$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
                }
            }
            
        } catch(Exception $e) {
            echo "❌ Erro ao analisar $nomeTabela: " . $e->getMessage() . "\n";
        }
        
        echo "\n" . str_repeat("-", 60) . "\n\n";
    }
    
    // Análise de uso por categoria
    echo "📈 ANÁLISE DE USO POR CATEGORIA\n";
    echo str_repeat("=", 50) . "\n\n";
    
    $categorias = [
        'Conteúdo' => ['noticias', 'categorias', 'tags', 'noticia_tags'],
        'Usuários' => ['usuarios', 'user_preferences', 'user_social_settings'],
        'Interação' => ['comentarios', 'curtidas_noticias', 'social_shares'],
        'Notificações' => ['notificacoes', 'newsletter', 'push_subscriptions', 'push_preferences', 'push_logs'],
        'Sistema' => ['configuracoes', 'backups', 'estatisticas_acesso'],
        'Social' => ['social_connections', 'social_webhooks'],
        'Monetização' => ['anuncios']
    ];
    
    foreach($categorias as $categoria => $tabelasCategoria) {
        echo "🎯 $categoria:\n";
        $totalRegistrosCategoria = 0;
        
        foreach($tabelasCategoria as $tabela) {
            try {
                $query = "SELECT COUNT(*) as total FROM `$tabela`";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $count = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "   - $tabela: {$count['total']} registros\n";
                $totalRegistrosCategoria += $count['total'];
                
            } catch(Exception $e) {
                echo "   - $tabela: ERRO - " . $e->getMessage() . "\n";
            }
        }
        
        echo "   📊 Total da categoria: " . number_format($totalRegistrosCategoria) . " registros\n\n";
    }
    
    // Verificar integridade referencial
    echo "🔍 VERIFICAÇÃO DE INTEGRIDADE REFERENCIAL\n";
    echo str_repeat("=", 50) . "\n\n";
    
    $verificacoes = [
        'Notícias órfãs (sem autor)' => "SELECT COUNT(*) as total FROM noticias WHERE autor_id NOT IN (SELECT id FROM usuarios)",
        'Comentários órfãos (sem notícia)' => "SELECT COUNT(*) as total FROM comentarios WHERE noticia_id NOT IN (SELECT id FROM noticias)",
        'Curtidas órfãs (sem usuário)' => "SELECT COUNT(*) as total FROM curtidas_noticias WHERE usuario_id NOT IN (SELECT id FROM usuarios)",
        'Tags não utilizadas' => "SELECT COUNT(*) as total FROM tags WHERE id NOT IN (SELECT DISTINCT tag_id FROM noticia_tags)"
    ];
    
    foreach($verificacoes as $descricao => $query) {
        try {
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $status = $result['total'] == 0 ? '✅' : '⚠️ ';
            echo "$status $descricao: {$result['total']}\n";
            
        } catch(Exception $e) {
            echo "❌ $descricao: ERRO - " . $e->getMessage() . "\n";
        }
    }
    
} catch(Exception $e) {
    echo "❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
}

echo "\n=== VALIDAÇÃO CONCLUÍDA ===\n";
?>