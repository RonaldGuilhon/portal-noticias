<?php
require_once 'config-local.php';
require_once 'backend/config/config.php';

try {
    echo "=== TESTE SIMPLES DE SENHA SHA1 ===\n\n";
    
    // 1. Testar funções básicas
    echo "1. TESTANDO FUNÇÕES BÁSICAS:\n";
    echo str_repeat("-", 40) . "\n";
    
    $senha = 'teste123';
    $hash = hashPassword($senha);
    
    echo "Senha: {$senha}\n";
    echo "Hash SHA1: {$hash}\n";
    echo "Verificação: " . (verifyPassword($senha, $hash) ? 'OK' : 'ERRO') . "\n\n";
    
    // 2. Testar conexão com banco
    echo "2. TESTANDO CONEXÃO COM BANCO:\n";
    echo str_repeat("-", 40) . "\n";
    
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "✓ Conexão com banco OK\n";
        
        // Verificar se existe tabela usuarios
        $query = "SHOW TABLES LIKE 'usuarios'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo "✓ Tabela usuarios existe\n";
            
            // Contar usuários
            $count_query = "SELECT COUNT(*) as total FROM usuarios";
            $count_stmt = $conn->prepare($count_query);
            $count_stmt->execute();
            $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            echo "✓ Total de usuários: {$total}\n\n";
            
        } else {
            echo "✗ Tabela usuarios não existe\n";
        }
        
    } else {
        echo "✗ Erro na conexão com banco\n";
    }
    
    // 3. Testar se as funções estão definidas
    echo "3. VERIFICANDO FUNÇÕES:\n";
    echo str_repeat("-", 40) . "\n";
    
    echo "hashPassword definida: " . (function_exists('hashPassword') ? 'SIM' : 'NÃO') . "\n";
    echo "verifyPassword definida: " . (function_exists('verifyPassword') ? 'SIM' : 'NÃO') . "\n";
    echo "PASSWORD_MIN_LENGTH: " . (defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 'NÃO DEFINIDA') . "\n\n";
    
    echo "✓ Teste concluído com sucesso!\n";
    
} catch(Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>