-- =============================================
-- Sistema de Push Notifications
-- Portal de Notícias
-- =============================================

USE portal_noticias;

-- =============================================
-- Tabela de Subscriptions de Push Notifications
-- =============================================
CREATE TABLE push_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    endpoint TEXT NOT NULL,
    p256dh_key VARCHAR(255) NOT NULL,
    auth_key VARCHAR(255) NOT NULL,
    user_agent TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ultimo_uso TIMESTAMP NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_ativo (ativo),
    INDEX idx_data_criacao (data_criacao)
);

-- =============================================
-- Tabela de Configurações de Push Notifications
-- =============================================
CREATE TABLE push_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    push_breaking BOOLEAN DEFAULT FALSE,
    push_interests BOOLEAN DEFAULT FALSE,
    push_comments BOOLEAN DEFAULT FALSE,
    push_newsletter BOOLEAN DEFAULT FALSE,
    push_system BOOLEAN DEFAULT TRUE,
    categorias_interesse JSON DEFAULT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_usuario (usuario_id)
);

-- =============================================
-- Tabela de Log de Push Notifications Enviadas
-- =============================================
CREATE TABLE push_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT DEFAULT NULL,
    subscription_id INT DEFAULT NULL,
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    tipo ENUM('breaking', 'interests', 'comments', 'newsletter', 'system') NOT NULL,
    status ENUM('enviado', 'erro', 'pendente') DEFAULT 'pendente',
    erro_mensagem TEXT DEFAULT NULL,
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_entrega TIMESTAMP NULL,
    clicado BOOLEAN DEFAULT FALSE,
    data_clique TIMESTAMP NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (subscription_id) REFERENCES push_subscriptions(id) ON DELETE SET NULL,
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_subscription_id (subscription_id),
    INDEX idx_tipo (tipo),
    INDEX idx_status (status),
    INDEX idx_data_envio (data_envio)
);

-- =============================================
-- Inserir configurações VAPID
-- =============================================
INSERT INTO configuracoes (chave, valor, descricao, tipo) VALUES 
('vapid_public_key', '', 'Chave pública VAPID para push notifications', 'string'),
('vapid_private_key', '', 'Chave privada VAPID para push notifications', 'string'),
('vapid_subject', 'mailto:admin@portalnoticias.com', 'Subject VAPID para push notifications', 'string'),
('push_notifications_enabled', '1', 'Ativar sistema de push notifications', 'boolean');

-- =============================================
-- Atualizar tabela de usuários para incluir preferências de push
-- =============================================
-- Coluna notificacoes_push já existe na tabela usuarios

SELECT 'Sistema de Push Notifications criado com sucesso!' as status;