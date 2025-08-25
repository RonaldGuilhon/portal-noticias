-- =====================================================
-- SCRIPT PARA REMOÇÃO DE TABELAS NÃO UTILIZADAS
-- Portal de Notícias - Database: portal_noticias
-- =====================================================
-- ATENÇÃO: SEMPRE FAÇA BACKUP ANTES DE EXECUTAR!
-- =====================================================

USE portal_noticias;

-- =====================================================
-- 1. BACKUP DAS TABELAS COM DADOS (Execute antes da remoção)
-- =====================================================

/*
Comandos para backup (execute no terminal):

mysqldump -u root -p portal_noticias user_share_summary > backup_user_share_summary_$(date +%Y%m%d).sql
mysqldump -u root -p portal_noticias vw_estatisticas_gerais > backup_vw_estatisticas_gerais_$(date +%Y%m%d).sql
mysqldump -u root -p portal_noticias vw_noticias_completas > backup_vw_noticias_completas_$(date +%Y%m%d).sql

*/

-- =====================================================
-- 2. VERIFICAÇÃO ANTES DA REMOÇÃO
-- =====================================================

-- Verificar se as tabelas realmente existem
SELECT 
    TABLE_NAME,
    TABLE_TYPE,
    TABLE_ROWS,
    DATA_LENGTH,
    INDEX_LENGTH
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'portal_noticias' 
AND TABLE_NAME IN (
    'curtidas_comentarios',
    'midias',
    'most_shared_content',
    'provider_share_stats',
    'social_share_stats',
    'user_share_summary',
    'vw_estatisticas_gerais',
    'vw_noticias_completas'
)
ORDER BY TABLE_NAME;

-- =====================================================
-- 3. REMOÇÃO FASE 1 - TABELAS VAZIAS (SEGURO)
-- =====================================================

-- Estas tabelas estão vazias e não são utilizadas no código
-- Remoção segura

SET FOREIGN_KEY_CHECKS = 0;

-- Remover tabela de curtidas em comentários (vazia)
DROP TABLE IF EXISTS `curtidas_comentarios`;
SELECT 'Tabela curtidas_comentarios removida' as status;

-- Remover tabela de mídias (vazia)
DROP TABLE IF EXISTS `midias`;
SELECT 'Tabela midias removida' as status;

-- Remover tabela de conteúdo mais compartilhado (vazia)
DROP TABLE IF EXISTS `most_shared_content`;
SELECT 'Tabela most_shared_content removida' as status;

-- Remover tabela de estatísticas de provedor (vazia)
DROP TABLE IF EXISTS `provider_share_stats`;
SELECT 'Tabela provider_share_stats removida' as status;

-- Remover tabela de estatísticas sociais (vazia)
DROP TABLE IF EXISTS `social_share_stats`;
SELECT 'Tabela social_share_stats removida' as status;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'FASE 1 CONCLUÍDA - Tabelas vazias removidas' as resultado;

-- =====================================================
-- 4. REMOÇÃO FASE 2 - TABELA COM DADOS (CUIDADO!)
-- =====================================================

-- ATENÇÃO: Esta tabela contém 13 registros!
-- Execute apenas após verificar se os dados não são importantes

/*
-- Verificar dados antes de remover
SELECT * FROM user_share_summary LIMIT 10;

-- Se confirmar que pode remover:
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `user_share_summary`;
SET FOREIGN_KEY_CHECKS = 1;
SELECT 'Tabela user_share_summary removida' as status;
*/

-- =====================================================
-- 5. REMOÇÃO FASE 3 - VIEWS (INVESTIGAR PRIMEIRO!)
-- =====================================================

-- ATENÇÃO: Views podem ser usadas por ferramentas externas!
-- Verifique relatórios, dashboards, ferramentas de BI antes de remover

/*
-- Verificar definição das views
SHOW CREATE VIEW vw_estatisticas_gerais;
SHOW CREATE VIEW vw_noticias_completas;

-- Se confirmar que pode remover:
DROP VIEW IF EXISTS `vw_estatisticas_gerais`;
SELECT 'View vw_estatisticas_gerais removida' as status;

DROP VIEW IF EXISTS `vw_noticias_completas`;
SELECT 'View vw_noticias_completas removida' as status;
*/

-- =====================================================
-- 6. VERIFICAÇÃO FINAL
-- =====================================================

-- Verificar se as tabelas foram realmente removidas
SELECT 
    TABLE_NAME,
    TABLE_TYPE
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'portal_noticias' 
AND TABLE_NAME IN (
    'curtidas_comentarios',
    'midias',
    'most_shared_content',
    'provider_share_stats',
    'social_share_stats',
    'user_share_summary',
    'vw_estatisticas_gerais',
    'vw_noticias_completas'
)
ORDER BY TABLE_NAME;

-- Se retornar vazio, todas foram removidas com sucesso

-- =====================================================
-- 7. OTIMIZAÇÃO PÓS-REMOÇÃO
-- =====================================================

-- Otimizar tabelas restantes
OPTIMIZE TABLE usuarios, noticias, categorias, comentarios;

-- Analisar tabelas para atualizar estatísticas
ANALYZE TABLE usuarios, noticias, categorias, comentarios;

-- =====================================================
-- 8. RELATÓRIO FINAL
-- =====================================================

SELECT 
    COUNT(*) as total_tabelas_restantes,
    SUM(CASE WHEN TABLE_TYPE = 'BASE TABLE' THEN 1 ELSE 0 END) as tabelas,
    SUM(CASE WHEN TABLE_TYPE = 'VIEW' THEN 1 ELSE 0 END) as views
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'portal_noticias';

SELECT 'LIMPEZA DE TABELAS CONCLUÍDA!' as resultado;

-- =====================================================
-- NOTAS IMPORTANTES:
-- =====================================================
/*
1. Execute este script em etapas, não tudo de uma vez
2. Sempre faça backup antes de qualquer remoção
3. Teste a aplicação após cada fase de remoção
4. As seções comentadas devem ser descomentadas apenas após confirmação
5. Monitore logs de erro da aplicação após as remoções
6. Mantenha os backups por pelo menos 30 dias
*/