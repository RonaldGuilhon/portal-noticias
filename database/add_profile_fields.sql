-- Adicionar campos faltantes na tabela usuarios para suporte completo aos formulários de perfil

-- Campos de informações pessoais
ALTER TABLE usuarios ADD COLUMN telefone VARCHAR(20) NULL COMMENT 'Telefone do usuário';
ALTER TABLE usuarios ADD COLUMN cidade VARCHAR(100) NULL COMMENT 'Cidade do usuário';

-- Campos de configurações de exibição
ALTER TABLE usuarios ADD COLUMN show_images TINYINT(1) DEFAULT 1 COMMENT 'Exibir imagens automaticamente';
ALTER TABLE usuarios ADD COLUMN auto_play_videos TINYINT(1) DEFAULT 0 COMMENT 'Reproduzir vídeos automaticamente';
ALTER TABLE usuarios ADD COLUMN dark_mode TINYINT(1) DEFAULT 0 COMMENT 'Modo escuro ativado';

-- Campos de notificações por email
ALTER TABLE usuarios ADD COLUMN email_breaking TINYINT(1) DEFAULT 1 COMMENT 'Receber emails de notícias urgentes';
ALTER TABLE usuarios ADD COLUMN email_comments TINYINT(1) DEFAULT 1 COMMENT 'Receber emails de novos comentários';
ALTER TABLE usuarios ADD COLUMN email_marketing TINYINT(1) DEFAULT 0 COMMENT 'Receber emails de marketing';

-- Campos de notificações push
ALTER TABLE usuarios ADD COLUMN push_breaking TINYINT(1) DEFAULT 1 COMMENT 'Receber push de notícias urgentes';
ALTER TABLE usuarios ADD COLUMN push_interests TINYINT(1) DEFAULT 1 COMMENT 'Receber push baseado em interesses';
ALTER TABLE usuarios ADD COLUMN push_comments TINYINT(1) DEFAULT 1 COMMENT 'Receber push de novos comentários';

-- Campo de frequência de notificações
ALTER TABLE usuarios ADD COLUMN notification_frequency ENUM('imediato','diario','semanal') DEFAULT 'diario' COMMENT 'Frequência de notificações';

-- Verificar se os campos foram adicionados
SELECT 'Campos adicionados com sucesso!' as resultado;
DESCRIBE usuarios;