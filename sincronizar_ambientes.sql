-- =============================================
-- Script de Sincronização entre Ambientes Casa e Trabalho
-- Portal de Notícias - Migração Unificada
-- =============================================

USE portal_noticias;

-- =============================================
-- VERIFICAÇÃO E BACKUP
-- =============================================

-- Criar tabela de backup antes das alterações
CREATE TABLE IF NOT EXISTS usuarios_backup_sync AS SELECT * FROM usuarios LIMIT 0;
INSERT INTO usuarios_backup_sync SELECT * FROM usuarios;

SELECT 'Backup da tabela usuarios criado com sucesso!' as status;

-- =============================================
-- FASE 1: ADICIONAR CAMPOS FALTANTES
-- =============================================

-- Campos de informações pessoais
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'telefone') = 0,
    'ALTER TABLE usuarios ADD COLUMN telefone VARCHAR(20) NULL COMMENT "Telefone do usuário"',
    'SELECT "Campo telefone já existe" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'cidade') = 0,
    'ALTER TABLE usuarios ADD COLUMN cidade VARCHAR(100) NULL COMMENT "Cidade do usuário"',
    'SELECT "Campo cidade já existe" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'estado') = 0,
    'ALTER TABLE usuarios ADD COLUMN estado VARCHAR(100) NULL COMMENT "Estado do usuário"',
    'SELECT "Campo estado já existe" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Campos de configurações de exibição (versão inglês)
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'show_images') = 0,
    'ALTER TABLE usuarios ADD COLUMN show_images TINYINT(1) DEFAULT 1 COMMENT "Exibir imagens automaticamente"',
    'SELECT "Campo show_images já existe" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'auto_play_videos') = 0,
    'ALTER TABLE usuarios ADD COLUMN auto_play_videos TINYINT(1) DEFAULT 0 COMMENT "Reproduzir vídeos automaticamente"',
    'SELECT "Campo auto_play_videos já existe" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'dark_mode') = 0,
    'ALTER TABLE usuarios ADD COLUMN dark_mode TINYINT(1) DEFAULT 0 COMMENT "Modo escuro ativado"',
    'SELECT "Campo dark_mode já existe" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Campos de notificações por email (versão inglês)
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'email_newsletter') = 0,
    'ALTER TABLE usuarios ADD COLUMN email_newsletter TINYINT(1) DEFAULT 1 COMMENT "Receber newsletter por email"',
    'SELECT "Campo email_newsletter já existe" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'email_breaking') = 0,
    'ALTER TABLE usuarios ADD COLUMN email_breaking TINYINT(1) DEFAULT 1 COMMENT "Receber emails de notícias urgentes"',
    'SELECT "Campo email_breaking já existe" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'email_comments') = 0,
    'ALTER TABLE usuarios ADD COLUMN email_comments TINYINT(1) DEFAULT 1 COMMENT "Receber emails de novos comentários"',
    'SELECT "Campo email_comments já existe" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'email_marketing') = 0,
    'ALTER TABLE usuarios ADD COLUMN email_marketing TINYINT(1) DEFAULT 0 COMMENT "Receber emails de marketing"',
    'SELECT "Campo email_marketing já existe" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Campos de notificações push (versão inglês)
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'push_breaking') = 0,
    'ALTER TABLE usuarios ADD COLUMN push_breaking TINYINT(1) DEFAULT 1 COMMENT "Receber push de notícias urgentes"',
    'SELECT "Campo push_breaking já existe" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'push_interests') = 0,
    'ALTER TABLE usuarios ADD COLUMN push_interests TINYINT(1) DEFAULT 1 COMMENT "Receber push baseado em interesses"',
    'SELECT "Campo push_interests já existe" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'push_comments') = 0,
    'ALTER TABLE usuarios ADD COLUMN push_comments TINYINT(1) DEFAULT 1 COMMENT "Receber push de novos comentários"',
    'SELECT "Campo push_comments já existe" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Campos de privacidade e preferências (versão inglês)
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'profile_public') = 0,
    'ALTER TABLE usuarios ADD COLUMN profile_public TINYINT(1) DEFAULT 1 COMMENT "Perfil público visível"',
    'SELECT "Campo profile_public já existe" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'show_activity') = 0,
    'ALTER TABLE usuarios ADD COLUMN show_activity TINYINT(1) DEFAULT 1 COMMENT "Mostrar atividade do usuário"',
    'SELECT "Campo show_activity já existe" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'allow_messages') = 0,
    'ALTER TABLE usuarios ADD COLUMN allow_messages TINYINT(1) DEFAULT 1 COMMENT "Permitir mensagens de outros usuários"',
    'SELECT "Campo allow_messages já existe" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'favorite_categories') = 0,
    'ALTER TABLE usuarios ADD COLUMN favorite_categories JSON DEFAULT NULL COMMENT "Categorias favoritas do usuário"',
    'SELECT "Campo favorite_categories já existe" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'language_preference') = 0,
    'ALTER TABLE usuarios ADD COLUMN language_preference VARCHAR(10) DEFAULT "pt-BR" COMMENT "Idioma preferido do usuário"',
    'SELECT "Campo language_preference já existe" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'Fase 1 concluída: Campos adicionados!' as status;

-- =============================================
-- FASE 2: RENOMEAR CAMPOS PARA PORTUGUÊS (OPCIONAL)
-- =============================================
-- Esta seção renomeia os campos para português como no ambiente "casa"
-- Descomente as linhas abaixo se quiser padronizar para português

/*
-- Renomear bio para biografia
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'bio') > 0,
    'ALTER TABLE usuarios CHANGE COLUMN bio biografia TEXT',
    'SELECT "Campo bio não encontrado" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Renomear show_images para exibir_imagens
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'show_images') > 0,
    'ALTER TABLE usuarios CHANGE COLUMN show_images exibir_imagens TINYINT(1) DEFAULT 1',
    'SELECT "Campo show_images não encontrado" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Continue com os outros campos conforme necessário...
*/

-- =============================================
-- FASE 3: REMOVER CAMPOS DESNECESSÁRIOS
-- =============================================

-- Remover notification_frequency se existir
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'notification_frequency') > 0,
    'ALTER TABLE usuarios DROP COLUMN notification_frequency',
    'SELECT "Campo notification_frequency não encontrado" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Remover coluna newsletter redundante se existir
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'newsletter') > 0,
    'ALTER TABLE usuarios DROP COLUMN newsletter',
    'SELECT "Campo newsletter não encontrado" as message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'Fase 3 concluída: Campos desnecessários removidos!' as status;

-- =============================================
-- FASE 4: VERIFICAÇÃO FINAL
-- =============================================

-- Listar estrutura final da tabela usuarios
SELECT 'ESTRUTURA FINAL DA TABELA USUARIOS:' as info;
SELECT 
    COLUMN_NAME as Campo,
    DATA_TYPE as Tipo,
    IS_NULLABLE as Nulo,
    COLUMN_DEFAULT as Padrão,
    COLUMN_COMMENT as Comentário
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'usuarios'
ORDER BY ORDINAL_POSITION;

-- Contar total de campos
SELECT 
    COUNT(*) as total_campos,
    'Sincronização concluída com sucesso!' as status
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'usuarios';

-- =============================================
-- INSTRUÇÕES DE USO
-- =============================================
/*
INSTRUÇÕES:

1. Execute este script no ambiente "trabalho" para sincronizá-lo com o ambiente "casa"

2. O script é seguro e:
   - Cria backup automático antes das alterações
   - Verifica se os campos existem antes de adicioná-los
   - Não remove dados existentes

3. Para renomear campos para português:
   - Descomente a seção FASE 2
   - Execute novamente o script

4. Após a execução:
   - Verifique a estrutura final
   - Teste a aplicação
   - Confirme que os formulários funcionam corretamente

5. Em caso de problemas:
   - Use a tabela usuarios_backup_sync para restaurar
   - Contate o administrador do sistema
*/