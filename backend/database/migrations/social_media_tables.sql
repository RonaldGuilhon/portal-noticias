-- Migração para Tabelas de Redes Sociais
-- Portal de Notícias
-- Data: 2024

-- Tabela para armazenar conexões sociais dos usuários
CREATE TABLE IF NOT EXISTS `social_connections` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `provider` varchar(50) NOT NULL COMMENT 'facebook, google, twitter, linkedin, etc.',
    `provider_id` varchar(255) NOT NULL COMMENT 'ID do usuário no provider',
    `access_token` text COMMENT 'Token de acesso para API',
    `refresh_token` text COMMENT 'Token de refresh (quando disponível)',
    `token_expires_at` datetime COMMENT 'Data de expiração do token',
    `profile_data` json COMMENT 'Dados do perfil social em JSON',
    `is_active` tinyint(1) DEFAULT 1 COMMENT 'Se a conexão está ativa',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_provider` (`user_id`, `provider`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_provider` (`provider`),
    KEY `idx_provider_id` (`provider_id`),
    CONSTRAINT `fk_social_connections_user` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para armazenar histórico de compartilhamentos
CREATE TABLE IF NOT EXISTS `social_shares` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `provider` varchar(50) NOT NULL COMMENT 'Rede social onde foi compartilhado',
    `content_type` varchar(50) NOT NULL COMMENT 'Tipo de conteúdo: noticia, comentario, etc.',
    `content_id` int(11) COMMENT 'ID do conteúdo compartilhado',
    `share_url` varchar(500) COMMENT 'URL do conteúdo compartilhado',
    `share_text` text COMMENT 'Texto usado no compartilhamento',
    `provider_post_id` varchar(255) COMMENT 'ID do post na rede social',
    `response` json COMMENT 'Resposta completa da API em JSON',
    `status` enum('success', 'failed', 'pending') DEFAULT 'pending',
    `error_message` text COMMENT 'Mensagem de erro se falhou',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_provider` (`provider`),
    KEY `idx_content` (`content_type`, `content_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_social_shares_user` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para armazenar estatísticas de compartilhamento por URL
CREATE TABLE IF NOT EXISTS `social_share_stats` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `url` varchar(500) NOT NULL,
    `url_hash` varchar(64) NOT NULL COMMENT 'Hash MD5 da URL para indexação',
    `provider` varchar(50) NOT NULL,
    `share_count` int(11) DEFAULT 0,
    `like_count` int(11) DEFAULT 0,
    `comment_count` int(11) DEFAULT 0,
    `last_updated` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_url_provider` (`url_hash`, `provider`),
    KEY `idx_url_hash` (`url_hash`),
    KEY `idx_provider` (`provider`),
    KEY `idx_last_updated` (`last_updated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para configurações de redes sociais por usuário
CREATE TABLE IF NOT EXISTS `user_social_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `auto_share_news` tinyint(1) DEFAULT 0 COMMENT 'Compartilhar automaticamente notícias',
    `auto_share_comments` tinyint(1) DEFAULT 0 COMMENT 'Compartilhar automaticamente comentários',
    `preferred_providers` json COMMENT 'Providers preferidos para compartilhamento',
    `share_template` text COMMENT 'Template personalizado para compartilhamento',
    `privacy_level` enum('public', 'friends', 'private') DEFAULT 'public',
    `notifications_enabled` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_settings` (`user_id`),
    CONSTRAINT `fk_user_social_settings_user` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para webhooks de redes sociais
CREATE TABLE IF NOT EXISTS `social_webhooks` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `provider` varchar(50) NOT NULL,
    `event_type` varchar(100) NOT NULL COMMENT 'Tipo de evento do webhook',
    `payload` json NOT NULL COMMENT 'Dados do webhook em JSON',
    `signature` varchar(255) COMMENT 'Assinatura para verificação',
    `processed` tinyint(1) DEFAULT 0,
    `processed_at` timestamp NULL,
    `error_message` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_provider` (`provider`),
    KEY `idx_event_type` (`event_type`),
    KEY `idx_processed` (`processed`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar colunas na tabela usuarios se não existirem
ALTER TABLE `usuarios` 
ADD COLUMN IF NOT EXISTS `provider` varchar(50) DEFAULT 'local' COMMENT 'Provider de autenticação',
ADD COLUMN IF NOT EXISTS `provider_id` varchar(255) COMMENT 'ID no provider externo',
ADD COLUMN IF NOT EXISTS `social_avatar` varchar(500) COMMENT 'URL do avatar das redes sociais';

-- Índices adicionais na tabela usuarios
ALTER TABLE `usuarios` 
ADD INDEX IF NOT EXISTS `idx_provider` (`provider`),
ADD INDEX IF NOT EXISTS `idx_provider_id` (`provider_id`);

-- Inserir configurações padrão para usuários existentes
INSERT IGNORE INTO `user_social_settings` (`user_id`, `auto_share_news`, `auto_share_comments`, `privacy_level`, `notifications_enabled`)
SELECT `id`, 0, 0, 'public', 1 FROM `usuarios` WHERE `id` NOT IN (SELECT `user_id` FROM `user_social_settings`);

-- Criar views úteis

-- View para estatísticas de compartilhamento por usuário
CREATE OR REPLACE VIEW `user_share_stats` AS
SELECT 
    u.id as user_id,
    u.nome as user_name,
    u.email as user_email,
    COUNT(ss.id) as total_shares,
    COUNT(CASE WHEN ss.status = 'success' THEN 1 END) as successful_shares,
    COUNT(CASE WHEN ss.status = 'failed' THEN 1 END) as failed_shares,
    COUNT(DISTINCT ss.provider) as providers_used,
    MAX(ss.created_at) as last_share_date
FROM usuarios u
LEFT JOIN social_shares ss ON u.id = ss.user_id
GROUP BY u.id, u.nome, u.email;

-- View para conexões sociais ativas
CREATE OR REPLACE VIEW `active_social_connections` AS
SELECT 
    sc.user_id,
    u.nome as user_name,
    u.email as user_email,
    sc.provider,
    sc.provider_id,
    sc.is_active,
    sc.created_at as connected_at,
    sc.updated_at as last_updated,
    CASE 
        WHEN sc.token_expires_at IS NULL THEN 'never'
        WHEN sc.token_expires_at > NOW() THEN 'valid'
        ELSE 'expired'
    END as token_status
FROM social_connections sc
JOIN usuarios u ON sc.user_id = u.id
WHERE sc.is_active = 1;

-- View para estatísticas gerais de redes sociais
CREATE OR REPLACE VIEW `social_platform_stats` AS
SELECT 
    provider,
    COUNT(DISTINCT user_id) as connected_users,
    COUNT(*) as total_connections,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_connections,
    (
        SELECT COUNT(*) 
        FROM social_shares ss 
        WHERE ss.provider = sc.provider AND ss.status = 'success'
    ) as successful_shares,
    (
        SELECT COUNT(*) 
        FROM social_shares ss 
        WHERE ss.provider = sc.provider
    ) as total_shares
FROM social_connections sc
GROUP BY provider;

-- Inserir dados de exemplo (opcional - remover em produção)
-- INSERT INTO `social_connections` (`user_id`, `provider`, `provider_id`, `is_active`) VALUES
-- (1, 'facebook', 'fb_123456789', 1),
-- (1, 'google', 'google_987654321', 1),
-- (2, 'twitter', 'twitter_555666777', 1);

-- Comentários finais
-- Esta migração cria toda a estrutura necessária para:
-- 1. Gerenciar conexões OAuth com redes sociais
-- 2. Armazenar histórico de compartilhamentos
-- 3. Coletar estatísticas de engajamento
-- 4. Configurações personalizadas por usuário
-- 5. Processar webhooks das redes sociais
-- 6. Views para relatórios e análises

-- Para executar esta migração:
-- mysql -u root -p portal_noticias < social_media_tables.sql