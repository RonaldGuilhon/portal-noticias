-- Script para adicionar campos faltantes na tabela usuarios
-- Portal de Notícias

USE portal_noticias;

-- Adicionar campo data_nascimento
ALTER TABLE usuarios ADD COLUMN data_nascimento DATE DEFAULT NULL;

-- Adicionar campo genero
ALTER TABLE usuarios ADD COLUMN genero ENUM('masculino', 'feminino', 'outro', 'prefiro-nao-informar') DEFAULT NULL;

-- Adicionar campo newsletter
ALTER TABLE usuarios ADD COLUMN newsletter BOOLEAN DEFAULT FALSE;

-- Verificar a estrutura atualizada
DESCRIBE usuarios;