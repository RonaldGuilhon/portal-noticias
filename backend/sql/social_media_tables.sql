-- Tabelas para funcionalidades de redes sociais
-- Portal de Notícias

-- Tabela para armazenar conexões de usuários com redes sociais
CREATE TABLE IF NOT EXISTS social_connections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    provider VARCHAR(50) NOT NULL,
    provider_id VARCHAR(255) NOT NULL,
    access_token TEXT,
    refresh_token TEXT,
    token_expires_at DATETIME,
    profile_data JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_provider (user_id, provider),
    INDEX idx_provider (provider),
    INDEX idx_user_id (user_id)
);

-- Tabela para histórico de compartilhamentos
CREATE TABLE IF NOT EXISTS social_shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    provider VARCHAR(50) NOT NULL,
    content_type ENUM('noticia', 'categoria', 'custom') NOT NULL,
    content_id INT,
    message TEXT,
    url VARCHAR(500),
    share_id VARCHAR(255),
    response_data JSON,
    status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
    error_message TEXT,
    shared_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_user_provider (user_id, provider),
    INDEX idx_content (content_type, content_id),
    INDEX idx_status (status),
    INDEX idx_shared_at (shared_at)
);

-- Tabela para estatísticas de compartilhamento por URL
CREATE TABLE IF NOT EXISTS social_share_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(500) NOT NULL,
    provider VARCHAR(50) NOT NULL,
    share_count INT DEFAULT 0,
    like_count INT DEFAULT 0,
    comment_count INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_url_provider (url, provider),
    INDEX idx_url (url),
    INDEX idx_provider_stats (provider)
);

-- Tabela para configurações de usuário para redes sociais
CREATE TABLE IF NOT EXISTS user_social_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    auto_share BOOLEAN DEFAULT FALSE,
    default_message TEXT,
    enabled_providers JSON,
    share_frequency ENUM('immediate', 'hourly', 'daily', 'weekly') DEFAULT 'immediate',
    privacy_settings JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_settings (user_id)
);

-- Tabela para webhooks de redes sociais
CREATE TABLE IF NOT EXISTS social_webhooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(50) NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    webhook_id VARCHAR(255),
    payload JSON,
    processed BOOLEAN DEFAULT FALSE,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_provider_event (provider, event_type),
    INDEX idx_processed (processed),
    INDEX idx_created_at (created_at)
);

-- Adicionar colunas à tabela usuarios se não existirem
-- Verificar se as colunas já existem antes de adicionar
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'provider') = 0,
    'ALTER TABLE usuarios ADD COLUMN provider VARCHAR(50) DEFAULT NULL',
    'SELECT "Column provider already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'provider_id') = 0,
    'ALTER TABLE usuarios ADD COLUMN provider_id VARCHAR(255) DEFAULT NULL',
    'SELECT "Column provider_id already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'social_login_enabled') = 0,
    'ALTER TABLE usuarios ADD COLUMN social_login_enabled BOOLEAN DEFAULT TRUE',
    'SELECT "Column social_login_enabled already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Criar índices adicionais na tabela usuarios
CREATE INDEX IF NOT EXISTS idx_provider_login ON usuarios(provider, provider_id);

-- View para relatório de compartilhamentos por usuário
CREATE OR REPLACE VIEW user_share_summary AS
SELECT 
    u.id as user_id,
    u.nome as user_name,
    u.email,
    COUNT(ss.id) as total_shares,
    COUNT(CASE WHEN ss.status = 'success' THEN 1 END) as successful_shares,
    COUNT(CASE WHEN ss.status = 'failed' THEN 1 END) as failed_shares,
    COUNT(DISTINCT ss.provider) as active_providers,
    MAX(ss.shared_at) as last_share_date
FROM usuarios u
LEFT JOIN social_shares ss ON u.id = ss.user_id
GROUP BY u.id, u.nome, u.email;

-- View para estatísticas de compartilhamento por provider
CREATE OR REPLACE VIEW provider_share_stats AS
SELECT 
    provider,
    COUNT(*) as total_shares,
    COUNT(CASE WHEN status = 'success' THEN 1 END) as successful_shares,
    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_shares,
    ROUND((COUNT(CASE WHEN status = 'success' THEN 1 END) / COUNT(*)) * 100, 2) as success_rate,
    COUNT(DISTINCT user_id) as active_users,
    DATE(MIN(shared_at)) as first_share_date,
    DATE(MAX(shared_at)) as last_share_date
FROM social_shares
GROUP BY provider;

-- View para conteúdo mais compartilhado
CREATE OR REPLACE VIEW most_shared_content AS
SELECT 
    ss.content_type,
    ss.content_id,
    CASE 
        WHEN ss.content_type = 'noticia' THEN n.titulo
        WHEN ss.content_type = 'categoria' THEN c.nome
        ELSE 'Conteúdo Personalizado'
    END as content_title,
    COUNT(*) as share_count,
    COUNT(CASE WHEN ss.status = 'success' THEN 1 END) as successful_shares,
    COUNT(DISTINCT ss.provider) as providers_used,
    MAX(ss.shared_at) as last_shared
FROM social_shares ss
LEFT JOIN noticias n ON ss.content_type = 'noticia' AND ss.content_id = n.id
LEFT JOIN categorias c ON ss.content_type = 'categoria' AND ss.content_id = c.id
WHERE ss.status = 'success'
GROUP BY ss.content_type, ss.content_id
ORDER BY share_count DESC;

-- Inserir configurações padrão do sistema
INSERT IGNORE INTO configuracoes (chave, valor, descricao) VALUES
('social_login_enabled', '1', 'Habilitar login via redes sociais'),
('social_auto_share', '0', 'Compartilhamento automático de notícias'),
('social_default_message', 'Confira esta notícia em nosso portal!', 'Mensagem padrão para compartilhamentos'),
('social_share_frequency', 'immediate', 'Frequência de compartilhamento automático'),
('facebook_app_configured', '0', 'Facebook App configurado'),
('twitter_api_configured', '0', 'Twitter API configurado'),
('linkedin_api_configured', '0', 'LinkedIn API configurado'),
('google_oauth_configured', '0', 'Google OAuth configurado');

-- Comentários para documentação
ALTER TABLE social_connections COMMENT = 'Armazena conexões de usuários com redes sociais';
ALTER TABLE social_shares COMMENT = 'Histórico de compartilhamentos em redes sociais';
ALTER TABLE social_share_stats COMMENT = 'Estatísticas de compartilhamento por URL';
ALTER TABLE user_social_settings COMMENT = 'Configurações de usuário para redes sociais';
ALTER TABLE social_webhooks COMMENT = 'Webhooks recebidos de redes sociais';

SELECT 'Tabelas de redes sociais criadas com sucesso!' as status;