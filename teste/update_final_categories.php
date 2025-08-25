<?php
/**
 * Atualização final das categorias favoritas
 * Remove 'cultura' e adiciona 'entretenimento' que existe no banco
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=portal_noticias', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== ATUALIZAÇÃO FINAL DAS CATEGORIAS FAVORITAS ===\n\n";
    
    // Categorias corretas que existem no banco e correspondem aos checkboxes
    $categoriasCorretas = ['politica', 'economia', 'esportes', 'tecnologia', 'saude', 'entretenimento'];
    
    echo "Categorias a serem definidas:\n";
    foreach ($categoriasCorretas as $cat) {
        echo "- {$cat}\n";
    }
    
    // Verificar se todas as categorias existem no banco
    echo "\n=== VERIFICAÇÃO DAS CATEGORIAS NO BANCO ===\n";
    foreach ($categoriasCorretas as $categoria) {
        $stmt = $pdo->prepare('SELECT id, nome FROM categorias WHERE slug = ?');
        $stmt->execute([$categoria]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "✅ {$categoria}: ID {$result['id']} - {$result['nome']}\n";
        } else {
            echo "❌ {$categoria}: NÃO ENCONTRADA\n";
        }
    }
    
    // Atualizar no banco de dados
    echo "\n=== ATUALIZANDO NO BANCO ===\n";
    $categoriasJson = json_encode($categoriasCorretas);
    $stmt = $pdo->prepare('UPDATE usuarios SET favorite_categories = ? WHERE email = ?');
    $result = $stmt->execute([$categoriasJson, 'ronaldguilhon@gmail.com']);
    
    if ($result) {
        echo "✅ Categorias favoritas atualizadas com sucesso!\n";
        echo "📋 Novo valor: {$categoriasJson}\n";
        
        // Verificar a atualização
        $stmt = $pdo->prepare('SELECT favorite_categories, language_preference FROM usuarios WHERE email = ?');
        $stmt->execute(['ronaldguilhon@gmail.com']);
        $updated = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "\n=== VERIFICAÇÃO FINAL ===\n";
        echo "Categorias no banco: {$updated['favorite_categories']}\n";
        echo "Idioma no banco: {$updated['language_preference']}\n";
        
        $decoded = json_decode($updated['favorite_categories'], true);
        echo "\n=== TESTE DE COMPATIBILIDADE ===\n";
        foreach ($categoriasCorretas as $categoria) {
            $isSelected = in_array($categoria, $decoded);
            echo "Checkbox '{$categoria}': " . ($isSelected ? '✅ SERÁ MARCADO' : '❌ NÃO SERÁ MARCADO') . "\n";
        }
        
        echo "\n🎉 ATUALIZAÇÃO CONCLUÍDA COM SUCESSO!\n";
        echo "Agora todas as categorias favoritas devem aparecer marcadas na página de perfil.\n";
        
    } else {
        echo "❌ Erro ao atualizar categorias favoritas\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>