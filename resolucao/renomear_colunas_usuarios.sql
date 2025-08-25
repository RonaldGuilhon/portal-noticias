-- Script para renomear colunas da tabela usuarios
-- Portal de Notícias - Padronização de campos
-- Data: 2025-01-15

USE portal_noticias;

-- Renomear colunas para melhor associação com formulários
-- Baseado na análise em analise_campos_formularios.md

-- 1. Renomear bio para biografia (mais descritivo)
ALTER TABLE usuarios CHANGE COLUMN bio biografia TEXT;

-- 2. Renomear show_images para exibir_imagens (português)
ALTER TABLE usuarios CHANGE COLUMN show_images exibir_imagens TINYINT(1) DEFAULT 1;

-- 3. Renomear auto_play_videos para reproduzir_videos_automaticamente (mais descritivo)
ALTER TABLE usuarios CHANGE COLUMN auto_play_videos reproduzir_videos_automaticamente TINYINT(1) DEFAULT 0;

-- 4. Renomear dark_mode para modo_escuro (português)
ALTER TABLE usuarios CHANGE COLUMN dark_mode modo_escuro TINYINT(1) DEFAULT 0;

-- 5. Renomear email_newsletter para receber_newsletter (mais descritivo)
ALTER TABLE usuarios CHANGE COLUMN email_newsletter receber_newsletter TINYINT(1) DEFAULT 0;

-- 6. Renomear email_breaking para notificacoes_email_urgentes (mais descritivo)
ALTER TABLE usuarios CHANGE COLUMN email_breaking notificacoes_email_urgentes TINYINT(1) DEFAULT 1;

-- 7. Renomear email_comments para notificacoes_email_comentarios (mais descritivo)
ALTER TABLE usuarios CHANGE COLUMN email_comments notificacoes_email_comentarios TINYINT(1) DEFAULT 1;

-- 8. Renomear email_marketing para receber_promocoes (mais descritivo)
ALTER TABLE usuarios CHANGE COLUMN email_marketing receber_promocoes TINYINT(1) DEFAULT 0;

-- 9. Renomear push_breaking para notificacoes_push_urgentes (mais descritivo)
ALTER TABLE usuarios CHANGE COLUMN push_breaking notificacoes_push_urgentes TINYINT(1) DEFAULT 1;

-- 10. Renomear push_interests para notificacoes_push_interesses (mais descritivo)
ALTER TABLE usuarios CHANGE COLUMN push_interests notificacoes_push_interesses TINYINT(1) DEFAULT 1;

-- 11. Renomear push_comments para notificacoes_push_comentarios (mais descritivo)
ALTER TABLE usuarios CHANGE COLUMN push_comments notificacoes_push_comentarios TINYINT(1) DEFAULT 1;

-- 12. Renomear profile_public para perfil_publico (português)
ALTER TABLE usuarios CHANGE COLUMN profile_public perfil_publico TINYINT(1) DEFAULT 1;

-- 13. Renomear show_activity para mostrar_atividade (português)
ALTER TABLE usuarios CHANGE COLUMN show_activity mostrar_atividade TINYINT(1) DEFAULT 1;

-- 14. Renomear allow_messages para permitir_mensagens (português)
ALTER TABLE usuarios CHANGE COLUMN allow_messages permitir_mensagens TINYINT(1) DEFAULT 1;

-- 15. Renomear favorite_categories para categorias_favoritas (português)
ALTER TABLE usuarios CHANGE COLUMN favorite_categories categorias_favoritas JSON;

-- 16. Renomear language_preference para idioma_preferido (português)
ALTER TABLE usuarios CHANGE COLUMN language_preference idioma_preferido VARCHAR(10) DEFAULT 'pt-BR';

-- Verificar estrutura atualizada
DESCRIBE usuarios;

-- Comentários sobre as mudanças:
-- - Todas as colunas agora têm nomes em português para consistência
-- - Nomes mais descritivos facilitam o entendimento
-- - Melhor associação com os campos dos formulários
-- - Mantida a funcionalidade existente