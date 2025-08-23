-- Script para adicionar colunas de preferências faltantes na tabela usuarios
-- Portal de Notícias
-- Corrige incompatibilidade entre frontend e backend

USE portal_noticias;

-- =============================================
-- ADICIONAR COLUNAS DE PREFERÊNCIAS FALTANTES
-- =============================================

-- 1. Adicionar coluna email_newsletter (se não existir)
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'email_newsletter') = 0,
    'ALTER TABLE usuarios ADD COLUMN email_newsletter TINYINT(1) DEFAULT 1 COMMENT "Receber newsletter por email"',
    'SELECT "Coluna email_newsletter já existe" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Adicionar coluna profile_public (se não existir)
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'profile_public') = 0,
    'ALTER TABLE usuarios ADD COLUMN profile_public TINYINT(1) DEFAULT 1 COMMENT "Perfil público visível"',
    'SELECT "Coluna profile_public já existe" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Adicionar coluna show_activity (se não existir)
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'show_activity') = 0,
    'ALTER TABLE usuarios ADD COLUMN show_activity TINYINT(1) DEFAULT 1 COMMENT "Mostrar atividade do usuário"',
    'SELECT "Coluna show_activity já existe" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Adicionar coluna allow_messages (se não existir)
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'allow_messages') = 0,
    'ALTER TABLE usuarios ADD COLUMN allow_messages TINYINT(1) DEFAULT 1 COMMENT "Permitir mensagens de outros usuários"',
    'SELECT "Coluna allow_messages já existe" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Adicionar coluna favorite_categories (se não existir)
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'favorite_categories') = 0,
    'ALTER TABLE usuarios ADD COLUMN favorite_categories JSON DEFAULT NULL COMMENT "Categorias favoritas do usuário"',
    'SELECT "Coluna favorite_categories já existe" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. Adicionar coluna language_preference (se não existir)
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'language_preference') = 0,
    'ALTER TABLE usuarios ADD COLUMN language_preference VARCHAR(10) DEFAULT "pt-BR" COMMENT "Idioma preferido do usuário"',
    'SELECT "Coluna language_preference já existe" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================
-- VERIFICAR ESTRUTURA FINAL
-- =============================================

-- Listar todas as colunas de preferências
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'usuarios' 
    AND (COLUMN_NAME LIKE '%email%' 
         OR COLUMN_NAME LIKE '%push%' 
         OR COLUMN_NAME LIKE '%notification%'
         OR COLUMN_NAME LIKE '%profile%'
         OR COLUMN_NAME LIKE '%show%'
         OR COLUMN_NAME LIKE '%allow%'
         OR COLUMN_NAME LIKE '%favorite%'
         OR COLUMN_NAME LIKE '%language%'
         OR COLUMN_NAME LIKE '%dark%')
ORDER BY COLUMN_NAME;

SELECT 'Colunas de preferências adicionadas com sucesso!' as resultado;