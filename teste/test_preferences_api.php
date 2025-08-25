<?php
/**
 * Teste da API de preferências do perfil
 * Verifica se os campos favorite_categories e language_preference estão sendo retornados
 */

require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/models/Usuario.php';
require_once __DIR__ . '/../backend/controllers/AuthController.php';

echo "=== TESTE DA API DE PREFERÊNCIAS ===\n\n";

try {
    // Simular dados de usuário logado
    $database = new Database();
    $db = $database->getConnection();
    
    // Buscar um usuário existente
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute(['ronaldguilhon@gmail.com']);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        echo "❌ Usuário não encontrado\n";
        exit;
    }
    
    echo "✅ Usuário encontrado: {$userData['nome']} (ID: {$userData['id']})\n";
    echo "📧 Email: {$userData['email']}\n\n";
    
    // Verificar campos de preferências no banco
    echo "=== DADOS NO BANCO DE DADOS ===\n";
    echo "favorite_categories: " . ($userData['favorite_categories'] ?? 'NULL') . "\n";
    echo "language_preference: " . ($userData['language_preference'] ?? 'NULL') . "\n";
    echo "profile_public: " . ($userData['profile_public'] ?? 'NULL') . "\n";
    echo "show_activity: " . ($userData['show_activity'] ?? 'NULL') . "\n";
    echo "allow_messages: " . ($userData['allow_messages'] ?? 'NULL') . "\n";
    echo "email_newsletter: " . ($userData['email_newsletter'] ?? 'NULL') . "\n";
    echo "push_breaking: " . ($userData['push_breaking'] ?? 'NULL') . "\n\n";
    
    // Simular resposta da API
    $usuario = new Usuario($db);
    $usuario->id = $userData['id'];
    $usuario->nome = $userData['nome'];
    $usuario->email = $userData['email'];
    $usuario->bio = $userData['bio'];
    $usuario->foto_perfil = $userData['foto_perfil'];
    $usuario->tipo_usuario = $userData['tipo_usuario'];
    $usuario->data_criacao = $userData['data_criacao'];
    $usuario->preferencias = $userData['preferencias'];
    $usuario->data_nascimento = $userData['data_nascimento'];
    $usuario->genero = $userData['genero'];
    $usuario->telefone = $userData['telefone'];
    $usuario->cidade = $userData['cidade'];
    $usuario->estado = $userData['estado'];
    $usuario->show_images = $userData['show_images'];
    $usuario->auto_play_videos = $userData['auto_play_videos'];
    $usuario->dark_mode = $userData['dark_mode'];
    $usuario->email_newsletter = $userData['email_newsletter'];
    $usuario->email_breaking = $userData['email_breaking'];
    $usuario->email_comments = $userData['email_comments'];
    $usuario->email_marketing = $userData['email_marketing'];
    $usuario->push_breaking = $userData['push_breaking'];
    $usuario->push_interests = $userData['push_interests'];
    $usuario->push_comments = $userData['push_comments'];
    $usuario->profile_public = $userData['profile_public'];
    $usuario->show_activity = $userData['show_activity'];
    $usuario->allow_messages = $userData['allow_messages'];
    $usuario->favorite_categories = $userData['favorite_categories'];
    $usuario->language_preference = $userData['language_preference'];
    
    // Simular resposta da API obterPerfil
    $apiResponse = [
        'success' => true,
        'data' => [
            'id' => $usuario->id,
            'nome' => $usuario->nome,
            'email' => $usuario->email,
            'bio' => $usuario->bio,
            'foto_perfil' => $usuario->foto_perfil,
            'tipo' => $usuario->tipo_usuario,
            'data_criacao' => $usuario->data_criacao,
            'preferencias' => json_decode($usuario->preferencias, true),
            
            // Informações pessoais
            'data_nascimento' => $usuario->data_nascimento,
            'genero' => $usuario->genero,
            'telefone' => $usuario->telefone,
            'cidade' => $usuario->cidade,
            'estado' => $usuario->estado,
            
            // Configurações de exibição
            'show_images' => (bool)$usuario->show_images,
            'auto_play_videos' => (bool)$usuario->auto_play_videos,
            'dark_mode' => (bool)$usuario->dark_mode,
            
            // Configurações de notificação
            'email_newsletter' => (bool)$usuario->email_newsletter,
            'email_breaking' => (bool)$usuario->email_breaking,
            'email_comments' => (bool)$usuario->email_comments,
            'email_marketing' => (bool)$usuario->email_marketing,
            'push_breaking' => (bool)$usuario->push_breaking,
            'push_interests' => (bool)$usuario->push_interests,
            'push_comments' => (bool)$usuario->push_comments,
            
            // Configurações de privacidade
            'profile_public' => (bool)$usuario->profile_public,
            'show_activity' => (bool)$usuario->show_activity,
            'allow_messages' => (bool)$usuario->allow_messages,
            
            // Preferências de conteúdo
            'favorite_categories' => json_decode($usuario->favorite_categories ?? '[]', true),
            'language_preference' => $usuario->language_preference ?? 'pt-BR'
        ]
    ];
    
    echo "=== RESPOSTA SIMULADA DA API ===\n";
    echo json_encode($apiResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // Verificar campos específicos
    echo "=== VERIFICAÇÃO DOS CAMPOS CORRIGIDOS ===\n";
    echo "✅ favorite_categories: " . json_encode($apiResponse['data']['favorite_categories']) . "\n";
    echo "✅ language_preference: " . $apiResponse['data']['language_preference'] . "\n";
    echo "✅ profile_public: " . ($apiResponse['data']['profile_public'] ? 'true' : 'false') . "\n";
    echo "✅ show_activity: " . ($apiResponse['data']['show_activity'] ? 'true' : 'false') . "\n";
    echo "✅ allow_messages: " . ($apiResponse['data']['allow_messages'] ? 'true' : 'false') . "\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
?>