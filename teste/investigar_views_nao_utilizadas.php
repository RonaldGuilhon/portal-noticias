<?php
/**
 * Script para investigar views não utilizadas no banco portal_noticias
 * Analisa estrutura e dados das views antes da remoção
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "=== INVESTIGAÇÃO DE VIEWS NÃO UTILIZADAS ===\n\n";
    
    $views = ['vw_estatisticas_gerais', 'vw_noticias_completas'];
    
    foreach($views as $view) {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "ANALISANDO VIEW: $view\n";
        echo str_repeat("=", 50) . "\n\n";
        
        // 1. Verificar se a view existe
        $query = "SELECT COUNT(*) as existe FROM information_schema.VIEWS WHERE TABLE_SCHEMA = 'portal_noticias' AND TABLE_NAME = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$view]);
        $existe = $stmt->fetch(PDO::FETCH_ASSOC)['existe'];
        
        if($existe == 0) {
            echo "❌ View $view não encontrada no banco\n";
            continue;
        }
        
        echo "✅ View encontrada\n\n";
        
        // 2. Obter definição da view
        try {
            $query = "SHOW CREATE VIEW `$view`";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "📋 DEFINIÇÃO DA VIEW:\n";
            echo "View: " . $result['View'] . "\n";
            echo "Create View: \n" . $result['Create View'] . "\n\n";
            
        } catch(Exception $e) {
            echo "❌ Erro ao obter definição: " . $e->getMessage() . "\n\n";
        }
        
        // 3. Obter estrutura (colunas)
        try {
            $query = "DESCRIBE `$view`";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "📊 ESTRUTURA DA VIEW:\n";
            foreach($colunas as $coluna) {
                echo "- {$coluna['Field']} ({$coluna['Type']})";
                if($coluna['Null'] == 'NO') echo " NOT NULL";
                if($coluna['Default'] !== null) echo " DEFAULT {$coluna['Default']}";
                echo "\n";
            }
            echo "\n";
            
        } catch(Exception $e) {
            echo "❌ Erro ao obter estrutura: " . $e->getMessage() . "\n\n";
        }
        
        // 4. Contar registros
        try {
            $query = "SELECT COUNT(*) as total FROM `$view`";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            echo "📈 TOTAL DE REGISTROS: $total\n\n";
            
        } catch(Exception $e) {
            echo "❌ Erro ao contar registros: " . $e->getMessage() . "\n\n";
        }
        
        // 5. Mostrar dados de exemplo (se houver)
        try {
            $query = "SELECT * FROM `$view` LIMIT 3";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if(!empty($dados)) {
                echo "📋 DADOS DE EXEMPLO (primeiros 3 registros):\n";
                foreach($dados as $index => $linha) {
                    echo "Registro " . ($index + 1) . ":\n";
                    foreach($linha as $campo => $valor) {
                        echo "  $campo: $valor\n";
                    }
                    echo "\n";
                }
            } else {
                echo "📋 DADOS: View vazia\n\n";
            }
            
        } catch(Exception $e) {
            echo "❌ Erro ao obter dados: " . $e->getMessage() . "\n\n";
        }
        
        // 6. Verificar dependências (tabelas base)
        try {
            $query = "SELECT DISTINCT TABLE_NAME FROM information_schema.VIEW_TABLE_USAGE WHERE VIEW_SCHEMA = 'portal_noticias' AND VIEW_NAME = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$view]);
            $dependencias = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if(!empty($dependencias)) {
                echo "🔗 DEPENDÊNCIAS (tabelas base):\n";
                foreach($dependencias as $tabela) {
                    echo "- $tabela\n";
                }
                echo "\n";
            } else {
                echo "🔗 DEPENDÊNCIAS: Nenhuma encontrada\n\n";
            }
            
        } catch(Exception $e) {
            echo "❌ Erro ao verificar dependências: " . $e->getMessage() . "\n\n";
        }
        
        // 7. Análise de utilidade
        echo "🔍 ANÁLISE DE UTILIDADE:\n";
        
        if(strpos($view, 'estatisticas') !== false) {
            echo "- Tipo: View de estatísticas/relatórios\n";
            echo "- Uso provável: Dashboards, relatórios gerenciais\n";
            echo "- Risco de remoção: MÉDIO - pode ser usada externamente\n";
        }
        
        if(strpos($view, 'noticias') !== false) {
            echo "- Tipo: View de notícias\n";
            echo "- Uso provável: Consultas complexas, relatórios\n";
            echo "- Risco de remoção: MÉDIO - pode ser usada externamente\n";
        }
        
        echo "\n";
        
        // 8. Recomendação
        echo "💡 RECOMENDAÇÃO:\n";
        if($total > 0) {
            echo "- ⚠️  CUIDADO: View contém dados ($total registros)\n";
            echo "- 🔍 INVESTIGAR: Verificar se é usada em relatórios externos\n";
            echo "- 💾 BACKUP: Fazer backup antes de remover\n";
            echo "- 🧪 TESTAR: Remover em ambiente de teste primeiro\n";
        } else {
            echo "- ✅ SEGURO: View vazia, pode ser removida\n";
            echo "- 🗑️  REMOVER: Baixo risco de impacto\n";
        }
        
        echo "\n";
    }
    
    // 9. Resumo final
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "RESUMO DA INVESTIGAÇÃO\n";
    echo str_repeat("=", 60) . "\n\n";
    
    echo "Views analisadas: " . count($views) . "\n";
    echo "\nPróximos passos recomendados:\n";
    echo "1. 🔍 Verificar logs de acesso do MySQL para uso das views\n";
    echo "2. 📊 Consultar equipe sobre uso em relatórios externos\n";
    echo "3. 🧪 Testar remoção em ambiente de desenvolvimento\n";
    echo "4. 💾 Fazer backup completo antes da remoção\n";
    echo "5. 📝 Documentar a remoção para referência futura\n";
    
    echo "\nComandos para backup das views:\n";
    foreach($views as $view) {
        echo "mysqldump -u root -p --no-data --routines --triggers portal_noticias $view > backup_$view.sql\n";
    }
    
    echo "\nComandos para remoção (após confirmação):\n";
    foreach($views as $view) {
        echo "DROP VIEW IF EXISTS `$view`;\n";
    }
    
} catch(Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

echo "\n=== INVESTIGAÇÃO CONCLUÍDA ===\n";
?>