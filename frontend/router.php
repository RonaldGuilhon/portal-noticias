<?php
// Router para o frontend - servidor PHP built-in

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = ltrim($uri, '/');

// Permitir arquivos estáticos (CSS, JS, imagens, etc.)
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/i', $uri)) {
    return false; // Deixa o servidor built-in lidar com arquivos estáticos
}

// Roteamento para categoria
if (preg_match('/^categoria\/([^\/?]+)\/?$/', $uri, $matches)) {
    $categoria_slug = $matches[1];
    
    // Servir o arquivo categoria.html
    if (file_exists(__DIR__ . '/categoria.html')) {
        include __DIR__ . '/categoria.html';
        return;
    }
}

// Roteamento para notícia
if (preg_match('/^noticia\/([^\/?]+)\/?$/', $uri, $matches)) {
    $noticia_slug = $matches[1];
    
    // Servir o arquivo noticia.html
    if (file_exists(__DIR__ . '/noticia.html')) {
        include __DIR__ . '/noticia.html';
        return;
    }
}

// Roteamento para notícia por slug direto (formato: slug.html)
if (preg_match('/^([^\/?]+)\.html$/', $uri, $matches)) {
    $noticia_slug = $matches[1];
    
    // Verificar se não é um arquivo que já existe
    if (!file_exists(__DIR__ . '/' . $uri)) {
        // Servir o arquivo noticia.html para slugs de notícias
        if (file_exists(__DIR__ . '/noticia.html')) {
            include __DIR__ . '/noticia.html';
            return;
        }
    }
}

// Se não é uma rota especial, deixar o servidor built-in lidar com arquivos estáticos
return false;
?>