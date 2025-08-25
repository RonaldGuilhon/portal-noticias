<?php
/**
 * Script para corrigir as categorias favoritas no banco de dados
 * Converte os dados para o formato correto esperado pelos checkboxes
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=portal_noticias', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== CORREÇÃO DAS CATEGORIAS FAVORITAS ===\n\n";
    
    // Verificar dados atuais
    $stmt = $pdo->prepare('SELECT id, nome, email, favorite_categories FROM usuarios WHERE email = ?');
    $stmt->execute(['ronaldguilhon@gmail.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ Usuário não encontrado\n";
        exit;
    }
    
    echo "✅ Usuário: {$user['nome']} ({$user['email']})\n";
    echo "📋 Categorias atuais: {$user['favorite_categories']}\n\n";
    
    // Mapear categorias do banco para valores dos checkboxes
    $stmt = $pdo->query('SELECT id, nome, slug FROM categorias ORDER BY id');
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== CATEGORIAS DISPONÍVEIS ===\n";
    foreach ($categorias as $cat) {
        echo "ID: {$cat['id']} - Nome: {$cat['nome']} - Slug: {$cat['slug']}\n";
    }
    
    // Valores dos checkboxes no HTML (conforme encontrado no perfil.html)
    $checkboxValues = [
        'politica' => 'politica',
        'economia' => 'economia', 
        'esportes' => 'esportes',
        'tecnologia' => 'tecnologia',
        'saude' => 'saude',
        'cultura' => 'cultura'
    ];
    
    echo "\n=== VALORES DOS CHECKBOXES NO HTML ===\n";
    foreach ($checkboxValues as $value) {
        echo "- {$value}\n";
    }
    
    // Definir categorias favoritas que queremos marcar
    // Baseado na imagem: Política, Economia, Esportes, Tecnologia, Saúde, Cultura
    $categoriasDesejadas = ['politica', 'economia', 'esportes', 'tecnologia', 'saude', 'cultura'];
    
    echo "\n=== ATUALIZANDO CATEGORIAS FAVORITAS ===\n";
    echo "Categorias a serem marcadas: " . implode(', ', $categoriasDesejadas) . "\n";
    
    // Atualizar no banco de dados
    $categoriasJson = json_encode($categoriasDesejadas);
    $stmt = $pdo->prepare('UPDATE usuarios SET favorite_categories = ? WHERE email = ?');
    $result = $stmt->execute([$categoriasJson, 'ronaldguilhon@gmail.com']);
    
    if ($result) {
        echo "✅ Categorias favoritas atualizadas com sucesso!\n";
        echo "📋 Novo valor: {$categoriasJson}\n";
        
        // Verificar a atualização
        $stmt = $pdo->prepare('SELECT favorite_categories FROM usuarios WHERE email = ?');
        $stmt->execute(['ronaldguilhon@gmail.com']);
        $updated = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "\n=== VERIFICAÇÃO FINAL ===\n";
        echo "Valor no banco: {$updated['favorite_categories']}\n";
        
        $decoded = json_decode($updated['favorite_categories'], true);
        echo "Array decodificado: " . print_r($decoded, true) . "\n";
        
        echo "\n=== TESTE DE COMPATIBILIDADE COM CHECKBOXES ===\n";
        foreach ($checkboxValues as $value) {
            $isSelected = in_array($value, $decoded);
            echo "Checkbox '{$value}': " . ($isSelected ? '✅ MARCADO' : '❌ DESMARCADO') . "\n";
        }
        
    } else {
        echo "❌ Erro ao atualizar categorias favoritas\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>