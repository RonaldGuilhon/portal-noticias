<?php
// Script para verificar foto de perfil do usuário no banco
// Portal de Notícias

require_once 'config-unified.php';
require_once 'backend/models/Usuario.php';

header('Content-Type: text/html; charset=UTF-8');

try {
    // Conectar ao banco
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
    
    echo "<h1>🔍 Verificação de Fotos de Perfil</h1>";
    
    // Buscar todos os usuários e suas fotos
    $stmt = $pdo->query("SELECT id, nome, email, foto_perfil FROM usuarios ORDER BY id");
    $usuarios = $stmt->fetchAll();
    
    echo "<h2>📊 Usuários no Sistema</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th style='padding: 10px;'>ID</th>";
    echo "<th style='padding: 10px;'>Nome</th>";
    echo "<th style='padding: 10px;'>Email</th>";
    echo "<th style='padding: 10px;'>Foto Perfil</th>";
    echo "<th style='padding: 10px;'>Status</th>";
    echo "</tr>";
    
    foreach ($usuarios as $usuario) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($usuario['id']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($usuario['nome']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($usuario['email']) . "</td>";
        echo "<td style='padding: 8px;'>" . (empty($usuario['foto_perfil']) ? '<em>NULL</em>' : htmlspecialchars($usuario['foto_perfil'])) . "</td>";
        
        if (empty($usuario['foto_perfil'])) {
            echo "<td style='padding: 8px; color: orange;'>⚠️ Sem foto (usará padrão)</td>";
        } else {
            // Verificar se o arquivo existe
            $fotoPath = 'C:/portal-noticias-uploads/profile-photos/' . $usuario['foto_perfil'];
            if (file_exists($fotoPath)) {
                echo "<td style='padding: 8px; color: green;'>✅ Arquivo existe</td>";
            } else {
                echo "<td style='padding: 8px; color: red;'>❌ Arquivo não encontrado</td>";
            }
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar diretório de uploads
    echo "<h2>📁 Verificação do Diretório de Uploads</h2>";
    $uploadDir = 'C:/portal-noticias-uploads/profile-photos';
    
    echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Diretório:</strong> {$uploadDir}<br>";
    
    if (is_dir($uploadDir)) {
        echo "<strong>Status:</strong> <span style='color: green;'>✅ Diretório existe</span><br>";
        echo "<strong>Permissões:</strong> " . (is_writable($uploadDir) ? '<span style="color: green;">✅ Gravável</span>' : '<span style="color: red;">❌ Não gravável</span>') . "<br>";
        
        // Listar arquivos no diretório
        $files = scandir($uploadDir);
        $imageFiles = array_filter($files, function($file) {
            return !in_array($file, ['.', '..']) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file);
        });
        
        echo "<strong>Arquivos de imagem:</strong> " . count($imageFiles) . "<br>";
        if (count($imageFiles) > 0) {
            echo "<ul>";
            foreach ($imageFiles as $file) {
                echo "<li>" . htmlspecialchars($file) . "</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<strong>Status:</strong> <span style='color: red;'>❌ Diretório não existe</span><br>";
    }
    echo "</div>";
    
    // Testar URL de acesso às fotos
    echo "<h2>🌐 Teste de URLs</h2>";
    echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>URL base configurada:</strong> http://localhost:8001/files/profile/<br>";
    echo "<strong>Rota no backend:</strong> /files/profile/{filename}<br><br>";
    
    // Testar se a rota funciona
    echo "<strong>Teste da rota:</strong><br>";
    $testUrl = 'http://localhost:8001/files/profile/test.jpg';
    echo "<a href='{$testUrl}' target='_blank'>{$testUrl}</a> (deve retornar erro 404 se não houver arquivo)<br>";
    echo "</div>";
    
    // Verificar configuração do avatar padrão
    echo "<h2>🖼️ Avatar Padrão</h2>";
    $defaultAvatarPath = __DIR__ . '/frontend/assets/images/default-avatar.png';
    echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Caminho:</strong> {$defaultAvatarPath}<br>";
    echo "<strong>Existe:</strong> " . (file_exists($defaultAvatarPath) ? '<span style="color: green;">✅ Sim</span>' : '<span style="color: red;">❌ Não</span>') . "<br>";
    
    if (file_exists($defaultAvatarPath)) {
        echo "<strong>Tamanho:</strong> " . number_format(filesize($defaultAvatarPath)) . " bytes<br>";
        echo "<strong>URL de acesso:</strong> <a href='http://localhost:8000/assets/images/default-avatar.png' target='_blank'>http://localhost:8000/assets/images/default-avatar.png</a><br>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background-color: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<strong>❌ Erro:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f5f5f5;
}
h1, h2 {
    color: #333;
}
table {
    background-color: white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
</style>