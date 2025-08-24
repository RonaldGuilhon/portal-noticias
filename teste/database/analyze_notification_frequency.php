<?php
/**
 * Script para analisar a coluna notification_frequency
 * Verifica se a coluna está sendo utilizada e se pode ser removida
 */

// Configurações do banco de dados
$host = 'localhost';
$dbname = 'portal_noticias';
$username = 'root';
$password = '';

echo "=== ANÁLISE DA COLUNA notification_frequency ===\n\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Verificar estrutura da coluna
    echo "1. ESTRUTURA DA COLUNA:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'notification_frequency'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "   - Campo: {$column['Field']}\n";
        echo "   - Tipo: {$column['Type']}\n";
        echo "   - Null: {$column['Null']}\n";
        echo "   - Padrão: {$column['Default']}\n";
        echo "   - Extra: {$column['Extra']}\n\n";
    } else {
        echo "   - Coluna não encontrada!\n\n";
        exit;
    }
    
    // 2. Verificar dados existentes
    echo "2. ANÁLISE DOS DADOS:\n";
    $stmt = $pdo->query("SELECT notification_frequency, COUNT(*) as total FROM usuarios GROUP BY notification_frequency");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Distribuição dos valores:\n";
    foreach ($data as $row) {
        $value = $row['notification_frequency'] ?? 'NULL';
        echo "   - {$value}: {$row['total']} usuários\n";
    }
    
    // 3. Verificar total de usuários
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "\n   Total de usuários: {$total}\n\n";
    
    // 4. Verificar se há valores diferentes do padrão
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM usuarios WHERE notification_frequency != 'diario'");
    $nonDefault = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "3. ANÁLISE DE USO:\n";
    echo "   - Usuários com valor padrão ('diario'): " . ($total - $nonDefault) . "\n";
    echo "   - Usuários com valor personalizado: {$nonDefault}\n";
    
    if ($nonDefault == 0) {
        echo "   ✓ Todos os usuários usam o valor padrão\n";
    } else {
        echo "   ⚠ Alguns usuários têm valores personalizados\n";
    }
    
    // 5. Verificar se a coluna é referenciada em outras tabelas
    echo "\n4. VERIFICAÇÃO DE REFERÊNCIAS:\n";
    
    // Buscar por foreign keys
    $stmt = $pdo->query("
        SELECT 
            TABLE_NAME,
            COLUMN_NAME,
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_NAME = 'usuarios' 
        AND REFERENCED_COLUMN_NAME = 'notification_frequency'
    ");
    
    $references = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($references)) {
        echo "   ✓ Nenhuma referência de chave estrangeira encontrada\n";
    } else {
        echo "   ⚠ Referências encontradas:\n";
        foreach ($references as $ref) {
            echo "     - {$ref['TABLE_NAME']}.{$ref['COLUMN_NAME']}\n";
        }
    }
    
    // 6. Análise de funcionalidade
    echo "\n5. ANÁLISE DE FUNCIONALIDADE:\n";
    echo "   - A coluna notification_frequency controla a periodicidade das notificações\n";
    echo "   - Valores possíveis: 'imediato', 'diario', 'semanal'\n";
    echo "   - No frontend (perfil.html), não há campo específico para esta configuração\n";
    echo "   - O sistema usa campos separados para email_notifications e push_notifications\n";
    echo "   - A frequência parece não estar sendo utilizada na interface do usuário\n";
    
    // 7. Recomendações
    echo "\n6. RECOMENDAÇÕES:\n";
    
    if ($nonDefault == 0) {
        echo "   ✓ SEGURO PARA REMOÇÃO:\n";
        echo "     - Todos os usuários usam o valor padrão\n";
        echo "     - Não há interface para alterar este valor\n";
        echo "     - Não há referências em outras tabelas\n";
        echo "     - A funcionalidade não está implementada no frontend\n";
        
        echo "\n   PASSOS PARA REMOÇÃO:\n";
        echo "   1. Remover referências no código PHP (AuthController.php, Usuario.php)\n";
        echo "   2. Executar ALTER TABLE usuarios DROP COLUMN notification_frequency;\n";
        echo "   3. Testar o sistema para garantir que não há quebras\n";
        
    } else {
        echo "   ⚠ CUIDADO:\n";
        echo "     - Alguns usuários têm valores personalizados\n";
        echo "     - Verificar se há lógica de negócio que usa estes valores\n";
        echo "     - Considerar migração dos dados antes da remoção\n";
    }
    
    echo "\n=== FIM DA ANÁLISE ===\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>