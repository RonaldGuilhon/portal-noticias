-- Script para remover colunas desnecessárias da tabela usuarios
-- Portal de Notícias - Limpeza do banco de dados
-- Data: 2025-01-15

USE portal_noticias;

-- ATENÇÃO: Execute este script apenas após confirmar que as colunas não são utilizadas
-- Recomenda-se fazer backup da tabela antes de executar

-- Backup da tabela (descomente se necessário)
-- CREATE TABLE usuarios_backup AS SELECT * FROM usuarios;

-- Análise de colunas potencialmente desnecessárias:

-- 1. Verificar se a coluna 'preferencias' é redundante
-- (pode ser substituída por categorias_favoritas após renomeação)
SELECT 
    COUNT(*) as total_registros,
    COUNT(CASE WHEN preferencias IS NOT NULL AND preferencias != '' AND preferencias != '[]' THEN 1 END) as com_preferencias,
    COUNT(CASE WHEN favorite_categories IS NOT NULL AND favorite_categories != '' AND favorite_categories != '[]' THEN 1 END) as com_categorias_favoritas
FROM usuarios;

-- Se preferencias e favorite_categories contêm dados similares, remover preferencias:
-- ALTER TABLE usuarios DROP COLUMN preferencias;

-- 2. Verificar colunas de autenticação social não utilizadas
SELECT 
    COUNT(*) as total_registros,
    COUNT(CASE WHEN provider IS NOT NULL AND provider != '' THEN 1 END) as com_provider,
    COUNT(CASE WHEN provider_id IS NOT NULL AND provider_id != '' THEN 1 END) as com_provider_id
FROM usuarios;

-- Se não há usuários com login social, remover colunas:
-- ALTER TABLE usuarios DROP COLUMN provider;
-- ALTER TABLE usuarios DROP COLUMN provider_id;

-- 3. Verificar tokens de recuperação e verificação antigos
SELECT 
    COUNT(*) as total_registros,
    COUNT(CASE WHEN token_verificacao IS NOT NULL THEN 1 END) as com_token_verificacao,
    COUNT(CASE WHEN token_recuperacao IS NOT NULL THEN 1 END) as com_token_recuperacao,
    COUNT(CASE WHEN email_verificado = 0 THEN 1 END) as emails_nao_verificados
FROM usuarios;

-- Limpar tokens antigos (mais de 24 horas):
-- UPDATE usuarios SET token_verificacao = NULL WHERE token_verificacao IS NOT NULL AND data_criacao < DATE_SUB(NOW(), INTERVAL 24 HOUR);
-- UPDATE usuarios SET token_recuperacao = NULL WHERE token_recuperacao IS NOT NULL AND data_atualizacao < DATE_SUB(NOW(), INTERVAL 1 HOUR);

-- 4. Verificar se há campos duplicados ou redundantes
-- Exemplo: se bio e biografia existirem após renomeação
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'usuarios' 
AND TABLE_SCHEMA = 'portal_noticias'
AND COLUMN_NAME LIKE '%bio%';

-- 5. Campos que podem ser removidos se não utilizados:

-- Remover coluna 'preferencias' se redundante com 'categorias_favoritas'
-- (Execute apenas após confirmar que os dados foram migrados)
-- ALTER TABLE usuarios DROP COLUMN preferencias;

-- Remover colunas de autenticação social se não utilizadas
-- (Execute apenas se não há planos de implementar login social)
-- ALTER TABLE usuarios DROP COLUMN provider;
-- ALTER TABLE usuarios DROP COLUMN provider_id;

-- 6. Otimização de índices após remoção de colunas
-- OPTIMIZE TABLE usuarios;

-- Verificar estrutura final
-- DESCRIBE usuarios;

-- IMPORTANTE: 
-- 1. Sempre faça backup antes de remover colunas
-- 2. Teste em ambiente de desenvolvimento primeiro
-- 3. Verifique se algum código ainda referencia as colunas removidas
-- 4. Atualize a documentação após as mudanças