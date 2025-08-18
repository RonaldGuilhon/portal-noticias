-- Script para remover coluna redundante 'newsletter' da tabela usuarios
-- Mantendo apenas 'email_newsletter' que é mais específica
-- Portal de Notícias

USE portal_noticias;

-- Remover coluna newsletter redundante
ALTER TABLE usuarios DROP COLUMN newsletter;

-- Verificar a estrutura atualizada
DESCRIBE usuarios;

SELECT 'Coluna newsletter removida com sucesso!' as resultado;