<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=portal_noticias', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== CATEGORIAS NO BANCO DE DADOS ===\n";
    $stmt = $pdo->query('SELECT id, nome, slug FROM categorias ORDER BY id');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} - Nome: {$row['nome']} - Slug: {$row['slug']}\n";
    }
    
    echo "\n=== PREFERÊNCIAS DO USUÁRIO ===\n";
    $stmt = $pdo->prepare('SELECT favorite_categories, language_preference FROM usuarios WHERE email = ?');
    $stmt->execute(['ronaldguilhon@gmail.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Categorias favoritas (raw): {$user['favorite_categories']}\n";
        echo "Idioma preferido: {$user['language_preference']}\n";
        
        if ($user['favorite_categories']) {
            $categories = json_decode($user['favorite_categories'], true);
            echo "Categorias favoritas (array): " . print_r($categories, true) . "\n";
            
            if (is_array($categories)) {
                echo "IDs das categorias favoritas:\n";
                foreach ($categories as $catId) {
                    $stmt2 = $pdo->prepare('SELECT nome, slug FROM categorias WHERE id = ?');
                    $stmt2->execute([$catId]);
                    $cat = $stmt2->fetch(PDO::FETCH_ASSOC);
                    if ($cat) {
                        echo "  - ID {$catId}: {$cat['nome']} (slug: {$cat['slug']})\n";
                    }
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>