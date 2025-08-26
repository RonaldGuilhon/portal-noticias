<?php
/**
 * Script para configuração inicial do banco de dados
 * Portal de Notícias - Setup Database
 * 
 * Como as extensões MySQL do PHP não estão disponíveis,
 * este script irá gerar os comandos SQL necessários.
 */

echo "=== SETUP DO BANCO DE DADOS ===\n";
echo "Portal de Notícias\n";
echo "================================\n\n";

echo "⚠️  AVISO: Extensões MySQL do PHP não encontradas.\n";
echo "Gerando comandos SQL para execução manual...\n\n";

try {
    // Ler o arquivo SQL principal
    echo "Lendo arquivo SQL...\n";
    $sql = file_get_contents('database/portal_noticias.sql');
    
    if (!$sql) {
        throw new Exception('Não foi possível ler o arquivo SQL');
    }
    
    echo "✓ Arquivo SQL carregado com sucesso!\n\n";
    
    // Gerar hash da senha do administrador
    $senha_hash = password_hash('Rede@@123', PASSWORD_DEFAULT);
    
    // Criar arquivo SQL personalizado
    $sql_personalizado = $sql;
    
    // Adicionar comando para atualizar/criar usuário admin
    $sql_admin = "\n\n-- =============================================\n";
    $sql_admin .= "-- USUÁRIO ADMINISTRADOR PERSONALIZADO\n";
    $sql_admin .= "-- =============================================\n\n";
    
    $sql_admin .= "-- Remover admin padrão se existir\n";
    $sql_admin .= "DELETE FROM usuarios WHERE email = 'admin@portalnoticias.com' OR tipo_usuario = 'admin';\n\n";
    
    $sql_admin .= "-- Inserir novo usuário administrador\n";
    $sql_admin .= "INSERT INTO usuarios (nome, email, senha, tipo_usuario, ativo, email_verificado) VALUES \n";
    $sql_admin .= "('Administrador', 'admin@portalnoticias.com', '$senha_hash', 'admin', TRUE, TRUE);\n\n";
    
    $sql_personalizado .= $sql_admin;
    
    // Salvar arquivo SQL personalizado
    $arquivo_setup = 'database/setup_completo.sql';
    file_put_contents($arquivo_setup, $sql_personalizado);
    
    echo "✓ Arquivo SQL personalizado criado: $arquivo_setup\n\n";
    
    // Gerar instruções
    echo "=== INSTRUÇÕES PARA EXECUÇÃO ===\n\n";
    
    echo "1. OPÇÃO 1 - Via linha de comando (se MySQL estiver no PATH):\n";
    echo "   mysql -u root -p < database/setup_completo.sql\n\n";
    
    echo "2. OPÇÃO 2 - Via phpMyAdmin ou similar:\n";
    echo "   - Acesse phpMyAdmin (http://localhost/phpmyadmin)\n";
    echo "   - Vá em 'Importar'\n";
    echo "   - Selecione o arquivo: database/setup_completo.sql\n";
    echo "   - Clique em 'Executar'\n\n";
    
    echo "3. OPÇÃO 3 - Via MySQL Workbench:\n";
    echo "   - Abra o MySQL Workbench\n";
    echo "   - Conecte ao servidor MySQL\n";
    echo "   - Vá em File > Open SQL Script\n";
    echo "   - Selecione: database/setup_completo.sql\n";
    echo "   - Execute o script\n\n";
    
    echo "4. OPÇÃO 4 - Copiar e colar no terminal MySQL:\n";
    echo "   - Execute: mysql -u root -p\n";
    echo "   - Copie e cole o conteúdo do arquivo SQL\n\n";
    
    // Mostrar resumo do que será criado
    echo "=== O QUE SERÁ CRIADO ===\n\n";
    echo "📊 Banco de dados: portal_noticias\n";
    echo "📋 Tabelas principais:\n";
    echo "   - usuarios (sistema de usuários)\n";
    echo "   - categorias (categorias de notícias)\n";
    echo "   - tags (tags para notícias)\n";
    echo "   - noticias (notícias principais)\n";
    echo "   - comentarios (sistema de comentários)\n";
    echo "   - curtidas_noticias (curtidas em notícias)\n";
    echo "   - curtidas_comentarios (curtidas em comentários)\n";
    echo "   - estatisticas_acesso (estatísticas de acesso)\n";
    echo "   - newsletter (sistema de newsletter)\n";
    echo "   - anuncios (sistema de anúncios)\n";
    echo "   - configuracoes (configurações do sistema)\n";
    echo "   - midias (upload de arquivos)\n";
    echo "   - notificacoes (sistema de notificações)\n\n";
    
    echo "👤 Usuário Administrador:\n";
    echo "   Email: admin@portalnoticias.com\n";
    echo "   Senha: Rede@@123\n\n";
    
    echo "📝 Dados iniciais:\n";
    echo "   - 7 categorias padrão\n";
    echo "   - 10 tags padrão\n";
    echo "   - 8 configurações do sistema\n";
    echo "   - 1 notícia de exemplo\n\n";
    
    echo "=== PRÓXIMOS PASSOS ===\n\n";
    echo "1. Execute o arquivo SQL usando uma das opções acima\n";
    echo "2. Verifique se o banco foi criado corretamente\n";
    echo "3. Teste o login no sistema com as credenciais do admin\n";
    echo "4. Configure as permissões de upload de arquivos\n\n";
    
    echo "✅ Setup preparado com sucesso!\n";
    echo "📁 Arquivo gerado: $arquivo_setup\n\n";
    
    // Criar também um arquivo de verificação
    $sql_verificacao = "-- Comandos para verificar a instalação\n";
    $sql_verificacao .= "USE portal_noticias;\n\n";
    $sql_verificacao .= "-- Verificar tabelas criadas\n";
    $sql_verificacao .= "SHOW TABLES;\n\n";
    $sql_verificacao .= "-- Verificar usuário admin\n";
    $sql_verificacao .= "SELECT id, nome, email, tipo_usuario FROM usuarios WHERE tipo_usuario = 'admin';\n\n";
    $sql_verificacao .= "-- Verificar categorias\n";
    $sql_verificacao .= "SELECT COUNT(*) as total_categorias FROM categorias;\n\n";
    $sql_verificacao .= "-- Verificar tags\n";
    $sql_verificacao .= "SELECT COUNT(*) as total_tags FROM tags;\n\n";
    $sql_verificacao .= "-- Verificar configurações\n";
    $sql_verificacao .= "SELECT COUNT(*) as total_configuracoes FROM configuracoes;\n\n";
    
    file_put_contents('database/verificar_instalacao.sql', $sql_verificacao);
    echo "📋 Arquivo de verificação criado: database/verificar_instalacao.sql\n";
    echo "   Use este arquivo para verificar se tudo foi instalado corretamente.\n\n";
    
} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
?>