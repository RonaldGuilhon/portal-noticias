# Comparação das Estruturas de Banco de Dados - Ambientes Casa e Trabalho

## Resumo da Análise

Baseado na análise dos arquivos SQL encontrados no repositório, identifiquei várias diferenças entre as estruturas de banco de dados dos ambientes "casa" e "trabalho".

## Estrutura Base Comum

Ambos os ambientes compartilham a mesma estrutura base definida em:
- `database/setup_completo.sql`
- `database/portal_noticias.sql`

### Tabelas Principais:
1. **usuarios** - Informações dos usuários
2. **categorias** - Categorias de notícias
3. **tags** - Tags para classificação
4. **noticias** - Conteúdo das notícias
5. **noticia_tags** - Relacionamento notícias-tags
6. **comentarios** - Comentários nas notícias
7. **curtidas_noticias** - Sistema de curtidas
8. **curtidas_comentarios** - Curtidas em comentários
9. **estatisticas_acesso** - Estatísticas de visualização
10. **newsletter** - Inscrições na newsletter
11. **anuncios** - Sistema de anúncios
12. **configuracoes** - Configurações do sistema
13. **midias** - Arquivos de mídia
14. **notificacoes** - Sistema de notificações

## Principais Diferenças Identificadas

### 1. Tabela `usuarios` - Campos de Preferências

#### Ambiente "Casa" (Mais Atualizado)
Possui campos renomeados para português conforme `resolucao/renomear_colunas_usuarios.sql`:

```sql
-- Campos em português (ambiente casa)
biografia TEXT
exibir_imagens TINYINT(1) DEFAULT 1
reproduzir_videos_automaticamente TINYINT(1) DEFAULT 0
modo_escuro TINYINT(1) DEFAULT 0
receber_newsletter TINYINT(1) DEFAULT 0
notificacoes_email_urgentes TINYINT(1) DEFAULT 1
notificacoes_email_comentarios TINYINT(1) DEFAULT 1
receber_promocoes TINYINT(1) DEFAULT 0
notificacoes_push_urgentes TINYINT(1) DEFAULT 1
notificacoes_push_interesses TINYINT(1) DEFAULT 1
notificacoes_push_comentarios TINYINT(1) DEFAULT 1
perfil_publico TINYINT(1) DEFAULT 1
mostrar_atividade TINYINT(1) DEFAULT 1
permitir_mensagens TINYINT(1) DEFAULT 1
categorias_favoritas JSON
idioma_preferido VARCHAR(10) DEFAULT 'pt-BR'
```

#### Ambiente "Trabalho" (Versão Anterior)
Possui campos em inglês conforme `database/add_profile_fields.sql`:

```sql
-- Campos em inglês (ambiente trabalho)
bio TEXT
show_images TINYINT(1) DEFAULT 1
auto_play_videos TINYINT(1) DEFAULT 0
dark_mode TINYINT(1) DEFAULT 0
email_newsletter TINYINT(1) DEFAULT 1
email_breaking TINYINT(1) DEFAULT 1
email_comments TINYINT(1) DEFAULT 1
email_marketing TINYINT(1) DEFAULT 0
push_breaking TINYINT(1) DEFAULT 1
push_interests TINYINT(1) DEFAULT 1
push_comments TINYINT(1) DEFAULT 1
profile_public TINYINT(1) DEFAULT 1
show_activity TINYINT(1) DEFAULT 1
allow_messages TINYINT(1) DEFAULT 1
favorite_categories JSON
language_preference VARCHAR(10) DEFAULT 'pt-BR'
```

### 2. Campos Adicionais Identificados

#### Campos de Informações Pessoais:
- `telefone VARCHAR(20)` - Telefone do usuário
- `cidade VARCHAR(100)` - Cidade do usuário
- `estado VARCHAR(100)` - Estado do usuário (adicionado posteriormente)

#### Campo Removido:
- `notification_frequency ENUM('imediato','diario','semanal')` - Foi removido conforme `teste/database/remove_notification_frequency.php`

### 3. Tabelas de Redes Sociais

O ambiente pode ter tabelas adicionais de redes sociais:
- `social_connections`
- `social_shares`
- `social_share_stats`
- `user_social_settings`
- `social_webhooks`

### 4. Tabelas de Push Notifications

Tabelas para notificações push:
- `push_subscriptions`
- `push_preferences`
- `push_logs`

### 5. Tabela de Backups

- `backups` - Para gerenciamento de backups do sistema

## Problemas de Compatibilidade

### 1. Nomenclatura de Campos
- **Casa**: Campos em português (mais recente)
- **Trabalho**: Campos em inglês (versão anterior)

### 2. Campos Faltantes
O ambiente "trabalho" pode não ter:
- Campos de informações pessoais (telefone, cidade, estado)
- Algumas colunas de preferências
- Tabelas de redes sociais
- Tabelas de push notifications

### 3. Valores Padrão Diferentes
Alguns campos têm valores padrão diferentes entre os ambientes.

## Recomendações para Sincronização

### 1. Padronizar Nomenclatura
Executar o script `resolucao/renomear_colunas_usuarios.sql` no ambiente "trabalho" para padronizar os nomes dos campos.

### 2. Adicionar Campos Faltantes
Executar os scripts:
- `database/add_profile_fields.sql`
- `database/add_missing_preference_columns.sql`

### 3. Remover Campos Desnecessários
Executar:
- `database/remove_redundant_newsletter.sql`
- `teste/database/remove_notification_frequency.php`

### 4. Criar Tabelas Adicionais
Se necessário, executar:
- `backend/sql/social_media_tables.sql`
- `database/push_notifications.sql`

### 5. Script de Migração Unificado

Criar um script que:
1. Verifique a estrutura atual
2. Aplique as alterações necessárias
3. Mantenha compatibilidade com ambos os ambientes

## Conclusão

As principais diferenças estão na tabela `usuarios`, especificamente:
- **Nomenclatura**: Casa (português) vs Trabalho (inglês)
- **Campos adicionais**: Informações pessoais e preferências
- **Tabelas extras**: Redes sociais e push notifications

Para garantir compatibilidade total, é necessário executar os scripts de migração no ambiente "trabalho" para alinhá-lo com o ambiente "casa".