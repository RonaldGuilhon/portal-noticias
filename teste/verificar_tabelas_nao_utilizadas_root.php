<?php
/**
 * Script para verificar tabelas não utilizadas no banco portal_noticias
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "=== ANÁLISE DE TABELAS NÃO UTILIZADAS ===\n\n";
    
    // 1. Obter todas as tabelas do banco
    $query = "SHOW TABLES";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tabelas encontradas no banco portal_noticias:\n";
    foreach($tabelas as $tabela) {
        echo "- $tabela\n";
    }
    echo "\nTotal: " . count($tabelas) . " tabelas\n\n";
    
    // 2. Definir diretórios para busca
    $diretorios = [
        'backend/models/',
        'backend/controllers/',
        'backend/services/',
        'backend/utils/',
        'teste/',
        'frontend/'
    ];
    
    // 3. Função para buscar referências de tabela no código
    function buscarReferenciasTabela($tabela, $diretorios) {
        $referencias = [];
        
        foreach($diretorios as $dir) {
            if(!is_dir($dir)) continue;
            
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir)
            );
            
            foreach($iterator as $arquivo) {
                if($arquivo->isFile()) {
                    $ext = pathinfo($arquivo, PATHINFO_EXTENSION);
                    if($ext === 'php' || $ext === 'html' || $ext === 'js') {
                        
                        $conteudo = file_get_contents($arquivo->getPathname());
                        
                        // Buscar referências simples da tabela
                        if(stripos($conteudo, $tabela) !== false) {
                            $referencias[] = $arquivo->getPathname();
                        }
                    }
                }
            }
        }
        
        return array_unique($referencias);
    }
    
    // 4. Analisar cada tabela
    $tabelasNaoUtilizadas = [];
    $tabelasUtilizadas = [];
    
    echo "Analisando uso das tabelas no código...\n\n";
    
    foreach($tabelas as $tabela) {
        echo "Verificando tabela: $tabela\n";
        
        $referencias = buscarReferenciasTabela($tabela, $diretorios);
        
        if(empty($referencias)) {
            $tabelasNaoUtilizadas[] = $tabela;
            echo "  ❌ NÃO UTILIZADA\n";
        } else {
            $tabelasUtilizadas[$tabela] = $referencias;
            echo "  ✅ UTILIZADA em " . count($referencias) . " arquivo(s)\n";
            foreach($referencias as $ref) {
                echo "     - $ref\n";
            }
        }
        echo "\n";
    }
    
    // 5. Relatório final
    echo "\n=== RELATÓRIO FINAL ===\n\n";
    
    echo "TABELAS NÃO UTILIZADAS (" . count($tabelasNaoUtilizadas) . "):";
    if(empty($tabelasNaoUtilizadas)) {
        echo " Nenhuma\n";
    } else {
        echo "\n";
        foreach($tabelasNaoUtilizadas as $tabela) {
            echo "- $tabela\n";
        }
    }
    
    echo "\nTABELAS UTILIZADAS (" . count($tabelasUtilizadas) . "):";
    if(empty($tabelasUtilizadas)) {
        echo " Nenhuma\n";
    } else {
        echo "\n";
        foreach($tabelasUtilizadas as $tabela => $refs) {
            echo "- $tabela (" . count($refs) . " referência(s))\n";
        }
    }
    
    // 6. Obter informações das tabelas não utilizadas
    if(!empty($tabelasNaoUtilizadas)) {
        echo "\n=== DETALHES DAS TABELAS NÃO UTILIZADAS ===\n\n";
        
        foreach($tabelasNaoUtilizadas as $tabela) {
            echo "Tabela: $tabela\n";
            
            // Contar registros
            try {
                $query = "SELECT COUNT(*) as total FROM `$tabela`";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                echo "Registros: $total\n";
            } catch(Exception $e) {
                echo "Registros: Erro ao contar - " . $e->getMessage() . "\n";
            }
            
            echo "\n";
        }
    }
    
    // 7. Recomendações
    echo "=== RECOMENDAÇÕES ===\n\n";
    
    if(!empty($tabelasNaoUtilizadas)) {
        echo "⚠️  ATENÇÃO: Encontradas " . count($tabelasNaoUtilizadas) . " tabela(s) não utilizadas.\n\n";
        echo "Recomendações:\n";
        echo "1. Verificar se são tabelas de backup ou temporárias\n";
        echo "2. Confirmar se não são usadas em scripts externos\n";
        echo "3. Fazer backup antes de remover\n";
        echo "4. Considerar remoção para otimizar o banco\n\n";
        
        echo "Script para remoção (CUIDADO!):\n";
        foreach($tabelasNaoUtilizadas as $tabela) {
            echo "-- DROP TABLE IF EXISTS `$tabela`;\n";
        }
    } else {
        echo "✅ Todas as tabelas estão sendo utilizadas no projeto.\n";
    }
    
} catch(Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

echo "\n=== ANÁLISE CONCLUÍDA ===\n";
?>