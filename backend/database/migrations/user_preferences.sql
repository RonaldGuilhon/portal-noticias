-- Migração para Preferências de Usuário
-- Portal de Notícias
-- Adiciona colunas de preferências individuais

-- Adicionar colunas de notificação se não existirem
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS email_newsletter BOOLEAN DEFAULT 0,
ADD COLUMN IF NOT EXISTS email_breaking BOOLEAN DEFAULT 0,
ADD COLUMN IF NOT EXISTS email_comments BOOLEAN DEFAULT 0,
ADD COLUMN IF NOT EXISTS email_marketing BOOLEAN DEFAULT 0,
ADD COLUMN IF NOT EXISTS push_breaking BOOLEAN DEFAULT 0,
ADD COLUMN IF NOT EXISTS push_interests BOOLEAN DEFAULT 0,
ADD COLUMN IF NOT EXISTS push_comments BOOLEAN DEFAULT 0;

-- Adicionar colunas de privacidade se não existirem
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS perfil_publico BOOLEAN DEFAULT 1,
ADD COLUMN IF NOT EXISTS mostrar_atividade BOOLEAN DEFAULT 1,
ADD COLUMN IF NOT EXISTS permitir_mensagens BOOLEAN DEFAULT 1;

-- Adicionar coluna de idioma se não existir
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS idioma_preferido VARCHAR(10) DEFAULT 'pt-BR';

-- Índices serão criados após verificar se as colunas existem
-- CREATE INDEX IF NOT EXISTS idx_usuarios_email_newsletter ON usuarios(email_newsletter);
-- CREATE INDEX IF NOT EXISTS idx_usuarios_perfil_publico ON usuarios(perfil_publico);
-- CREATE INDEX IF NOT EXISTS idx_usuarios_idioma ON usuarios(idioma_preferido);