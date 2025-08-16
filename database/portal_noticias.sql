-- =============================================
-- Portal de Notícias - Estrutura do Banco de Dados
-- Sistema completo de gerenciamento de notícias
-- =============================================

CREATE DATABASE IF NOT EXISTS portal_noticias CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE portal_noticias;

-- =============================================
-- Tabela de Usuários
-- =============================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    foto_perfil VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    tipo_usuario ENUM('admin', 'editor', 'leitor') DEFAULT 'leitor',
    ativo BOOLEAN DEFAULT TRUE,
    email_verificado BOOLEAN DEFAULT FALSE,
    token_verificacao VARCHAR(100) DEFAULT NULL,
    token_recuperacao VARCHAR(100) DEFAULT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ultimo_login TIMESTAMP NULL,
    preferencias JSON DEFAULT NULL,
    provider VARCHAR(50) DEFAULT 'local', -- local, google, facebook
    provider_id VARCHAR(100) DEFAULT NULL,
    INDEX idx_email (email),
    INDEX idx_tipo_usuario (tipo_usuario),
    INDEX idx_ativo (ativo)
);

-- =============================================
-- Tabela de Categorias
-- =============================================
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    descricao TEXT DEFAULT NULL,
    cor VARCHAR(7) DEFAULT '#007bff', -- Cor hexadecimal
    icone VARCHAR(50) DEFAULT NULL,
    ativa BOOLEAN DEFAULT TRUE,
    ordem INT DEFAULT 0,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_ativa (ativa),
    INDEX idx_ordem (ordem)
);

-- =============================================
-- Tabela de Tags
-- =============================================
CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    cor VARCHAR(7) DEFAULT '#6c757d',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_nome (nome)
);

-- =============================================
-- Tabela de Notícias
-- =============================================
CREATE TABLE noticias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    subtitulo VARCHAR(500) DEFAULT NULL,
    conteudo LONGTEXT NOT NULL,
    resumo TEXT DEFAULT NULL,
    imagem_destaque VARCHAR(255) DEFAULT NULL,
    alt_imagem VARCHAR(255) DEFAULT NULL,
    autor_id INT NOT NULL,
    categoria_id INT NOT NULL,
    status ENUM('rascunho', 'publicado', 'arquivado') DEFAULT 'rascunho',
    destaque BOOLEAN DEFAULT FALSE,
    permitir_comentarios BOOLEAN DEFAULT TRUE,
    visualizacoes INT DEFAULT 0,
    curtidas INT DEFAULT 0,
    data_publicacao TIMESTAMP NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    meta_title VARCHAR(255) DEFAULT NULL,
    meta_description VARCHAR(500) DEFAULT NULL,
    meta_keywords VARCHAR(500) DEFAULT NULL,
    FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_destaque (destaque),
    INDEX idx_data_publicacao (data_publicacao),
    INDEX idx_visualizacoes (visualizacoes),
    INDEX idx_curtidas (curtidas),
    FULLTEXT idx_busca (titulo, subtitulo, conteudo, resumo)
);

-- =============================================
-- Tabela de Relacionamento Notícias-Tags
-- =============================================
CREATE TABLE noticia_tags (
    noticia_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (noticia_id, tag_id),
    FOREIGN KEY (noticia_id) REFERENCES noticias(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- =============================================
-- Tabela de Comentários
-- =============================================
CREATE TABLE comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    noticia_id INT NOT NULL,
    usuario_id INT NOT NULL,
    comentario_pai_id INT DEFAULT NULL,
    conteudo TEXT NOT NULL,
    aprovado BOOLEAN DEFAULT FALSE,
    curtidas INT DEFAULT 0,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (noticia_id) REFERENCES noticias(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (comentario_pai_id) REFERENCES comentarios(id) ON DELETE CASCADE,
    INDEX idx_noticia_id (noticia_id),
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_aprovado (aprovado),
    INDEX idx_data_criacao (data_criacao)
);

-- =============================================
-- Tabela de Curtidas em Notícias
-- =============================================
CREATE TABLE curtidas_noticias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    noticia_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo ENUM('curtida', 'descurtida') DEFAULT 'curtida',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_curtida (noticia_id, usuario_id),
    FOREIGN KEY (noticia_id) REFERENCES noticias(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_noticia_id (noticia_id),
    INDEX idx_usuario_id (usuario_id)
);

-- =============================================
-- Tabela de Curtidas em Comentários
-- =============================================
CREATE TABLE curtidas_comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comentario_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo ENUM('curtida', 'descurtida') DEFAULT 'curtida',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_curtida_comentario (comentario_id, usuario_id),
    FOREIGN KEY (comentario_id) REFERENCES comentarios(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_comentario_id (comentario_id),
    INDEX idx_usuario_id (usuario_id)
);

-- =============================================
-- Tabela de Estatísticas de Acesso
-- =============================================
CREATE TABLE estatisticas_acesso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    noticia_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT DEFAULT NULL,
    referer VARCHAR(500) DEFAULT NULL,
    data_acesso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (noticia_id) REFERENCES noticias(id) ON DELETE CASCADE,
    INDEX idx_noticia_id (noticia_id),
    INDEX idx_data_acesso (data_acesso),
    INDEX idx_ip_address (ip_address)
);

-- =============================================
-- Tabela de Newsletter
-- =============================================
CREATE TABLE newsletter (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) UNIQUE NOT NULL,
    nome VARCHAR(100) DEFAULT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    token_confirmacao VARCHAR(100) DEFAULT NULL,
    confirmado BOOLEAN DEFAULT FALSE,
    data_inscricao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_confirmacao TIMESTAMP NULL,
    categorias_interesse JSON DEFAULT NULL,
    INDEX idx_email (email),
    INDEX idx_ativo (ativo),
    INDEX idx_confirmado (confirmado)
);

-- =============================================
-- Tabela de Anúncios
-- =============================================
CREATE TABLE anuncios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    imagem VARCHAR(255) NOT NULL,
    link VARCHAR(500) NOT NULL,
    posicao ENUM('header', 'sidebar', 'footer', 'conteudo') NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    visualizacoes INT DEFAULT 0,
    cliques INT DEFAULT 0,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_posicao (posicao),
    INDEX idx_ativo (ativo),
    INDEX idx_data_inicio (data_inicio),
    INDEX idx_data_fim (data_fim)
);

-- =============================================
-- Tabela de Configurações do Sistema
-- =============================================
CREATE TABLE configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT DEFAULT NULL,
    descricao VARCHAR(255) DEFAULT NULL,
    tipo ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chave (chave)
);

-- =============================================
-- Tabela de Mídia (Imagens, Vídeos, Áudios)
-- =============================================
CREATE TABLE midias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_original VARCHAR(255) NOT NULL,
    nome_arquivo VARCHAR(255) NOT NULL,
    caminho VARCHAR(500) NOT NULL,
    tipo_mime VARCHAR(100) NOT NULL,
    tamanho INT NOT NULL,
    tipo ENUM('imagem', 'video', 'audio', 'documento') NOT NULL,
    alt_text VARCHAR(255) DEFAULT NULL,
    legenda TEXT DEFAULT NULL,
    usuario_id INT NOT NULL,
    data_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_tipo (tipo),
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_data_upload (data_upload)
);

-- =============================================
-- DADOS INICIAIS
-- =============================================

-- Inserir usuário administrador padrão
INSERT INTO usuarios (nome, email, senha, tipo_usuario, ativo, email_verificado) VALUES 
('Administrador', 'admin@portalnoticias.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE, TRUE);

-- Inserir categorias padrão
INSERT INTO categorias (nome, slug, descricao, cor, icone, ordem) VALUES 
('Política', 'politica', 'Notícias sobre política nacional e internacional', '#dc3545', 'fas fa-landmark', 1),
('Economia', 'economia', 'Notícias sobre economia, mercado financeiro e negócios', '#28a745', 'fas fa-chart-line', 2),
('Esportes', 'esportes', 'Notícias esportivas, futebol, olimpíadas e mais', '#007bff', 'fas fa-futbol', 3),
('Tecnologia', 'tecnologia', 'Inovações, gadgets e mundo digital', '#6f42c1', 'fas fa-laptop', 4),
('Saúde', 'saude', 'Notícias sobre saúde, medicina e bem-estar', '#20c997', 'fas fa-heartbeat', 5),
('Entretenimento', 'entretenimento', 'Cinema, música, celebridades e cultura pop', '#fd7e14', 'fas fa-film', 6),
('Mundo', 'mundo', 'Notícias internacionais e acontecimentos globais', '#6c757d', 'fas fa-globe', 7);

-- Inserir tags padrão
INSERT INTO tags (nome, slug) VALUES 
('Urgente', 'urgente'),
('Exclusivo', 'exclusivo'),
('Análise', 'analise'),
('Opinião', 'opiniao'),
('Entrevista', 'entrevista'),
('Investigação', 'investigacao'),
('Brasil', 'brasil'),
('São Paulo', 'sao-paulo'),
('Rio de Janeiro', 'rio-de-janeiro'),
('Copa do Mundo', 'copa-do-mundo');

-- Inserir configurações padrão
INSERT INTO configuracoes (chave, valor, descricao, tipo) VALUES 
('site_nome', 'Portal de Notícias', 'Nome do site', 'string'),
('site_descricao', 'Seu portal de notícias confiável', 'Descrição do site', 'string'),
('site_email', 'contato@portalnoticias.com', 'Email de contato', 'string'),
('comentarios_moderacao', '1', 'Ativar moderação de comentários', 'boolean'),
('newsletter_ativa', '1', 'Ativar sistema de newsletter', 'boolean'),
('anuncios_ativos', '1', 'Ativar sistema de anúncios', 'boolean'),
('noticias_por_pagina', '12', 'Número de notícias por página', 'number'),
('tema_escuro', '0', 'Ativar tema escuro por padrão', 'boolean');

-- Inserir notícia de exemplo
INSERT INTO noticias (titulo, slug, subtitulo, conteudo, resumo, autor_id, categoria_id, status, destaque, data_publicacao, meta_title, meta_description) VALUES 
('Bem-vindo ao Portal de Notícias', 'bem-vindo-portal-noticias', 'Sua fonte confiável de informação', '<p>Bem-vindo ao nosso portal de notícias! Aqui você encontrará as últimas notícias sobre política, economia, esportes, tecnologia e muito mais.</p><p>Nossa equipe de jornalistas trabalha 24 horas por dia para trazer as informações mais relevantes e atualizadas.</p><p>Fique sempre informado com nosso portal!</p>', 'Portal de notícias lança com cobertura completa de diversos temas', 1, 1, 'publicado', TRUE, NOW(), 'Bem-vindo ao Portal de Notícias - Sua fonte confiável', 'Conheça o novo portal de notícias com cobertura completa de política, economia, esportes e tecnologia');

-- =============================================
-- TRIGGERS PARA ATUALIZAR CONTADORES
-- =============================================

-- Trigger para atualizar contador de curtidas nas notícias
DELIMITER //
CREATE TRIGGER atualizar_curtidas_noticia
AFTER INSERT ON curtidas_noticias
FOR EACH ROW
BEGIN
    UPDATE noticias 
    SET curtidas = (
        SELECT COUNT(*) 
        FROM curtidas_noticias 
        WHERE noticia_id = NEW.noticia_id AND tipo = 'curtida'
    ) - (
        SELECT COUNT(*) 
        FROM curtidas_noticias 
        WHERE noticia_id = NEW.noticia_id AND tipo = 'descurtida'
    )
    WHERE id = NEW.noticia_id;
END//

-- Trigger para atualizar contador de curtidas nos comentários
CREATE TRIGGER atualizar_curtidas_comentario
AFTER INSERT ON curtidas_comentarios
FOR EACH ROW
BEGIN
    UPDATE comentarios 
    SET curtidas = (
        SELECT COUNT(*) 
        FROM curtidas_comentarios 
        WHERE comentario_id = NEW.comentario_id AND tipo = 'curtida'
    ) - (
        SELECT COUNT(*) 
        FROM curtidas_comentarios 
        WHERE comentario_id = NEW.comentario_id AND tipo = 'descurtida'
    )
    WHERE id = NEW.comentario_id;
END//
DELIMITER ;

-- =============================================
-- VIEWS ÚTEIS
-- =============================================

-- View para notícias com informações completas
CREATE VIEW vw_noticias_completas AS
SELECT 
    n.id,
    n.titulo,
    n.slug,
    n.subtitulo,
    n.conteudo,
    n.resumo,
    n.imagem_destaque,
    n.visualizacoes,
    n.curtidas,
    n.data_publicacao,
    n.data_criacao,
    n.destaque,
    u.nome as autor_nome,
    c.nome as categoria_nome,
    c.slug as categoria_slug,
    c.cor as categoria_cor,
    GROUP_CONCAT(t.nome SEPARATOR ', ') as tags
FROM noticias n
JOIN usuarios u ON n.autor_id = u.id
JOIN categorias c ON n.categoria_id = c.id
LEFT JOIN noticia_tags nt ON n.id = nt.noticia_id
LEFT JOIN tags t ON nt.tag_id = t.id
WHERE n.status = 'publicado'
GROUP BY n.id;

-- View para estatísticas gerais
CREATE VIEW vw_estatisticas_gerais AS
SELECT 
    (SELECT COUNT(*) FROM noticias WHERE status = 'publicado') as total_noticias,
    (SELECT COUNT(*) FROM usuarios WHERE ativo = TRUE) as total_usuarios,
    (SELECT COUNT(*) FROM comentarios WHERE aprovado = TRUE) as total_comentarios,
    (SELECT COUNT(*) FROM newsletter WHERE ativo = TRUE) as total_newsletter,
    (SELECT SUM(visualizacoes) FROM noticias) as total_visualizacoes;

-- =============================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- =============================================

CREATE INDEX idx_noticias_categoria_status ON noticias(categoria_id, status);
CREATE INDEX idx_noticias_destaque_status ON noticias(destaque, status);
CREATE INDEX idx_comentarios_noticia_aprovado ON comentarios(noticia_id, aprovado);
CREATE INDEX idx_estatisticas_data_noticia ON estatisticas_acesso(data_acesso, noticia_id);

-- =============================================
-- FIM DO SCRIPT
-- =============================================

SELECT 'Banco de dados Portal de Notícias criado com sucesso!' as status;