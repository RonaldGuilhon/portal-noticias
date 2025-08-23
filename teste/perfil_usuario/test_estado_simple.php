<?php
require_once 'config-local.php';

try {
    $pdo = new PDO("mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8mb4", 
        $config['database']['username'], $config['database']['password'], $config['database']['options']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== TESTE SIMPLES DO CAMPO ESTADO ===\n\n";
    
    // 1. Verificar estado atual
    echo "1. Estado atual do usuário ID 2:\n";
    $stmt = $pdo->prepare("SELECT cidade, estado FROM usuarios WHERE id = 2");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Cidade: " . ($user['cidade'] ?? 'NULL') . "\n";
    echo "Estado: " . ($user['estado'] ?? 'NULL') . "\n\n";
    
    // 2. Atualizar diretamente no banco
    echo "2. Atualizando campo estado para 'AM'...\n";
    $stmt = $pdo->prepare("UPDATE usuarios SET estado = 'AM' WHERE id = 2");
    $result = $stmt->execute();
    
    if ($result) {
        echo "✓ Atualização executada com sucesso!\n";
        echo "Linhas afetadas: " . $stmt->rowCount() . "\n\n";
    } else {
        echo "✗ Erro na atualização!\n";
        exit(1);
    }
    
    // 3. Verificar se foi salvo
    echo "3. Verificando dados após atualização:\n";
    $stmt = $pdo->prepare("SELECT cidade, estado FROM usuarios WHERE id = 2");
    $stmt->execute();
    $user_updated = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Cidade: " . ($user_updated['cidade'] ?? 'NULL') . "\n";
    echo "Estado: " . ($user_updated['estado'] ?? 'NULL') . "\n\n";
    
    if ($user_updated['estado'] === 'AM') {
        echo "✓ SUCESSO: Campo estado foi salvo corretamente!\n";
    } else {
        echo "✗ ERRO: Campo estado não foi salvo!\n";
    }
    
} catch(Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>