<?php
// Verificação completa dos dados do usuário admin@portal.com
require_once __DIR__ . '/../backend/config/database.php';

try {
    echo "=== VERIFICAÇÃO COMPLETA DOS DADOS DO ADMIN ===\n";
    echo "Data: " . date('Y-m-d H:i:s') . "\n\n";
    
    $pdo = getConnection();
    
    // Buscar todos os dados do usuário admin
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute(['admin@portal.com']);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        echo "✅ USUÁRIO ENCONTRADO\n";
        echo "=== DADOS BÁSICOS ===\n";
        echo "ID: " . $usuario['id'] . "\n";
        echo "Nome: " . ($usuario['nome'] ?: '❌ VAZIO') . "\n";
        echo "Email: " . $usuario['email'] . "\n";
        echo "Tipo: " . $usuario['tipo_usuario'] . "\n";
        echo "Ativo: " . ($usuario['ativo'] ? 'Sim' : 'Não') . "\n";
        echo "Email verificado: " . ($usuario['email_verificado'] ? 'Sim' : 'Não') . "\n";
        echo "Data criação: " . $usuario['data_criacao'] . "\n";
        
        echo "\n=== INFORMAÇÕES PESSOAIS ===\n";
        echo "Bio: " . ($usuario['bio'] ?: '❌ VAZIO') . "\n";
        echo "Foto perfil: " . ($usuario['foto_perfil'] ?: '❌ VAZIO') . "\n";
        echo "Data nascimento: " . ($usuario['data_nascimento'] ?: '❌ VAZIO') . "\n";
        echo "Gênero: " . ($usuario['genero'] ?: '❌ VAZIO') . "\n";
        echo "Telefone: " . ($usuario['telefone'] ?: '❌ VAZIO') . "\n";
        echo "Cidade: " . ($usuario['cidade'] ?: '❌ VAZIO') . "\n";
        echo "Estado: " . ($usuario['estado'] ?: '❌ VAZIO') . "\n";
        
        echo "\n=== CONFIGURAÇÕES DE EXIBIÇÃO ===\n";
        echo "Show images: " . (isset($usuario['show_images']) ? ($usuario['show_images'] ? 'Sim' : 'Não') : '❌ CAMPO NÃO EXISTE') . "\n";
        echo "Auto play videos: " . (isset($usuario['auto_play_videos']) ? ($usuario['auto_play_videos'] ? 'Sim' : 'Não') : '❌ CAMPO NÃO EXISTE') . "\n";
        echo "Dark mode: " . (isset($usuario['dark_mode']) ? ($usuario['dark_mode'] ? 'Sim' : 'Não') : '❌ CAMPO NÃO EXISTE') . "\n";
        
        echo "\n=== NOTIFICAÇÕES ===\n";
        echo "Email notifications: " . (isset($usuario['email_notifications']) ? ($usuario['email_notifications'] ? 'Sim' : 'Não') : '❌ CAMPO NÃO EXISTE') . "\n";
        echo "Push notifications: " . (isset($usuario['push_notifications']) ? ($usuario['push_notifications'] ? 'Sim' : 'Não') : '❌ CAMPO NÃO EXISTE') . "\n";
        echo "Newsletter: " . (isset($usuario['newsletter']) ? ($usuario['newsletter'] ? 'Sim' : 'Não') : '❌ CAMPO NÃO EXISTE') . "\n";
        
        echo "\n=== PRIVACIDADE ===\n";
        echo "Profile public: " . (isset($usuario['profile_public']) ? ($usuario['profile_public'] ? 'Sim' : 'Não') : '❌ CAMPO NÃO EXISTE') . "\n";
        echo "Show activity: " . (isset($usuario['show_activity']) ? ($usuario['show_activity'] ? 'Sim' : 'Não') : '❌ CAMPO NÃO EXISTE') . "\n";
        echo "Allow messages: " . (isset($usuario['allow_messages']) ? ($usuario['allow_messages'] ? 'Sim' : 'Não') : '❌ CAMPO NÃO EXISTE') . "\n";
        
        echo "\n=== PREFERÊNCIAS ===\n";
        echo "Favorite categories: " . (isset($usuario['favorite_categories']) ? ($usuario['favorite_categories'] ?: '❌ VAZIO') : '❌ CAMPO NÃO EXISTE') . "\n";
        echo "Language preference: " . (isset($usuario['language_preference']) ? ($usuario['language_preference'] ?: '❌ VAZIO') : '❌ CAMPO NÃO EXISTE') . "\n";
        echo "Preferências (JSON): " . ($usuario['preferencias'] ?: '❌ VAZIO') . "\n";
        
        // Verificar se há campos NULL que deveriam ter valores padrão
        echo "\n=== ANÁLISE DE PROBLEMAS ===\n";
        $problemasEncontrados = [];
        
        if (empty($usuario['nome'])) {
            $problemasEncontrados[] = "Nome está vazio";
        }
        
        if (empty($usuario['bio'])) {
            $problemasEncontrados[] = "Bio está vazia";
        }
        
        if (empty($usuario['foto_perfil'])) {
            $problemasEncontrados[] = "Foto de perfil está vazia";
        }
        
        if (!isset($usuario['show_images'])) {
            $problemasEncontrados[] = "Campo show_images não existe";
        }
        
        if (!isset($usuario['profile_public'])) {
            $problemasEncontrados[] = "Campo profile_public não existe";
        }
        
        if (empty($problemasEncontrados)) {
            echo "✅ NENHUM PROBLEMA ENCONTRADO - Todos os dados estão OK!\n";
        } else {
            echo "❌ PROBLEMAS ENCONTRADOS:\n";
            foreach ($problemasEncontrados as $problema) {
                echo "- $problema\n";
            }
        }
        
        // Verificar estrutura da tabela
        echo "\n=== ESTRUTURA DA TABELA USUARIOS ===\n";
        $stmt = $pdo->query("DESCRIBE usuarios");
        $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $colunasEssenciais = [
            'id', 'nome', 'email', 'bio', 'foto_perfil', 'telefone', 'cidade', 'estado',
            'show_images', 'auto_play_videos', 'dark_mode', 'email_notifications',
            'push_notifications', 'newsletter', 'profile_public', 'show_activity',
            'allow_messages', 'favorite_categories', 'language_preference'
        ];
        
        $colunasExistentes = array_column($colunas, 'Field');
        $colunasFaltando = array_diff($colunasEssenciais, $colunasExistentes);
        
        if (empty($colunasFaltando)) {
            echo "✅ Todas as colunas essenciais existem\n";
        } else {
            echo "❌ COLUNAS FALTANDO:\n";
            foreach ($colunasFaltando as $coluna) {
                echo "- $coluna\n";
            }
        }
        
    } else {
        echo "❌ USUÁRIO NÃO ENCONTRADO\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
?>