<?php
/**
 * Script para atualizar as preferências do usuário de teste
 */

require_once __DIR__ . '/../backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== VERIFICANDO CATEGORIAS DISPONÍVEIS ===\n";
    $stmt = $db->query("SELECT id, nome FROM categorias ORDER BY nome");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($categorias)) {
        echo "❌ Nenhuma categoria encontrada no banco\n";
        exit;
    }
    
    echo "Categorias encontradas:\n";
    foreach ($categorias as $cat) {
        echo "- ID: {$cat['id']}, Nome: {$cat['nome']}\n";
    }
    
    // Selecionar algumas categorias para o usuário de teste
    $categoriasSelecionadas = [];
    $count = 0;
    foreach ($categorias as $cat) {
        if ($count < 3) { // Selecionar as 3 primeiras
            $categoriasSelecionadas[] = $cat['id'];
            $count++;
        }
    }
    
    echo "\n=== ATUALIZANDO PREFERÊNCIAS DO USUÁRIO ===\n";
    echo "Categorias selecionadas: " . implode(', ', $categoriasSelecionadas) . "\n";
    
    // Atualizar o usuário com as preferências
    $favoriteCategoriesJson = json_encode($categoriasSelecionadas);
    $languagePreference = 'pt-BR';
    
    $updateStmt = $db->prepare("
        UPDATE usuarios 
        SET 
            favorite_categories = ?,
            language_preference = ?,
            profile_public = 1,
            show_activity = 1,
            allow_messages = 1
        WHERE email = 'ronaldguilhon@gmail.com'
    ");
    
    $result = $updateStmt->execute([
        $favoriteCategoriesJson,
        $languagePreference
    ]);
    
    if ($result) {
        echo "✅ Preferências atualizadas com sucesso!\n";
        
        // Verificar os dados atualizados
        echo "\n=== VERIFICANDO DADOS ATUALIZADOS ===\n";
        $checkStmt = $db->prepare("SELECT favorite_categories, language_preference, profile_public, show_activity, allow_messages FROM usuarios WHERE email = ?");
        $checkStmt->execute(['ronaldguilhon@gmail.com']);
        $userData = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "favorite_categories: {$userData['favorite_categories']}\n";
        echo "language_preference: {$userData['language_preference']}\n";
        echo "profile_public: {$userData['profile_public']}\n";
        echo "show_activity: {$userData['show_activity']}\n";
        echo "allow_messages: {$userData['allow_messages']}\n";
        
    } else {
        echo "❌ Erro ao atualizar preferências\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}

echo "\n=== SCRIPT CONCLUÍDO ===\n";
?>