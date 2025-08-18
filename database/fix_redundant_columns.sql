-- Script para corrigir colunas redundantes no banco de dados
-- Portal de Notícias
-- Remove duplicações e mantém apenas as colunas necessárias

USE portal_noticias;

-- =============================================
-- VERIFICAR E REMOVER COLUNAS REDUNDANTES
-- =============================================

-- 1. Verificar se a coluna 'newsletter' existe e removê-la
-- (mantendo apenas 'email_newsletter' que é mais específica)
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'newsletter') > 0,
    'ALTER TABLE usuarios DROP COLUMN newsletter',
    'SELECT "Coluna newsletter não existe ou já foi removida" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Verificar se existe duplicação de colunas de notificação
-- Listar todas as colunas relacionadas a notificação para verificação
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'usuarios' 
    AND (COLUMN_NAME LIKE '%email%' OR COLUMN_NAME LIKE '%push%' OR COLUMN_NAME LIKE '%notification%')
ORDER BY COLUMN_NAME;

-- 3. Verificar estrutura final da tabela usuarios
DESCRIBE usuarios;

SELECT 'Verificação de colunas redundantes concluída!' as resultado;