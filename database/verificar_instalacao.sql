-- Comandos para verificar a instalação
USE portal_noticias;

-- Verificar tabelas criadas
SHOW TABLES;

-- Verificar usuário admin
SELECT id, nome, email, tipo_usuario FROM usuarios WHERE tipo_usuario = 'admin';

-- Verificar categorias
SELECT COUNT(*) as total_categorias FROM categorias;

-- Verificar tags
SELECT COUNT(*) as total_tags FROM tags;

-- Verificar configurações
SELECT COUNT(*) as total_configuracoes FROM configuracoes;

