# Relatório de Compatibilidade dos Formulários

## Análise de Compatibilidade entre cadastro.html, perfil.html e Banco de Dados

### 1. CAMPOS DO FORMULÁRIO CADASTRO.HTML

#### Campos Presentes:
- ✅ `nome` → Compatível com `usuarios.nome` (VARCHAR(100))
- ✅ `email` → Compatível com `usuarios.email` (VARCHAR(150))
- ✅ `password` → Compatível com `usuarios.senha` (VARCHAR(255))
- ✅ `password_confirm` → Campo de validação (não salvo no BD)
- ✅ `data_nascimento` → Compatível com `usuarios.data_nascimento` (DATE)
- ✅ `genero` → Compatível com `usuarios.genero` (ENUM)
- ✅ `preferencias[]` → Compatível com `usuarios.favorite_categories` (LONGTEXT)
- ✅ `terms` → Campo de validação (não salvo no BD)
- ✅ `newsletter` → Compatível com `usuarios.email_newsletter` (TINYINT)
- ✅ `marketing` → Compatível com `usuarios.email_marketing` (TINYINT)

#### Valores do Campo Gênero:
- ✅ `masculino` → Compatível
- ✅ `feminino` → Compatível
- ✅ `outro` → Compatível
- ✅ `prefiro-nao-informar` → Compatível

#### Categorias de Preferências:
- ✅ `politica` → Compatível
- ✅ `economia` → Compatível
- ✅ `esportes` → Compatível
- ✅ `tecnologia` → Compatível
- ✅ `saude` → Compatível
- ✅ `cultura` → Compatível

### 2. CAMPOS DO FORMULÁRIO PERFIL.HTML

#### Campos de Informações Pessoais:
- ❌ `edit-name` → **SEM ATRIBUTO NAME** (deveria ser `name="nome"`)
- ❌ `edit-email` → **SEM ATRIBUTO NAME** (deveria ser `name="email"`)
- ❌ `edit-phone` → **SEM ATRIBUTO NAME** (deveria ser `name="telefone"`)
- ❌ `edit-birthdate` → **SEM ATRIBUTO NAME** (deveria ser `name="data_nascimento"`)
- ❌ `edit-bio` → **SEM ATRIBUTO NAME** (deveria ser `name="bio"`)
- ❌ `edit-city` → **SEM ATRIBUTO NAME** (deveria ser `name="cidade"`)
- ❌ `edit-state` → **SEM ATRIBUTO NAME** (deveria ser `name="estado"`)

#### Campos de Preferências:
- ❌ `email-notifications` → **SEM ATRIBUTO NAME** (deveria mapear para múltiplos campos)
- ❌ `push-notifications` → **SEM ATRIBUTO NAME** (deveria mapear para múltiplos campos)
- ❌ `newsletter` → **SEM ATRIBUTO NAME** (deveria ser `name="email_newsletter"`)
- ❌ `profile-public` → **SEM ATRIBUTO NAME** (deveria ser `name="profile_public"`)
- ❌ `show-activity` → **SEM ATRIBUTO NAME** (deveria ser `name="show_activity"`)
- ❌ `allow-messages` → **SEM ATRIBUTO NAME** (deveria ser `name="allow_messages"`)
- ❌ `favorite-categories` → **SEM ATRIBUTO NAME** (deveria ser `name="favorite_categories"`)
- ❌ `language-preference` → **SEM ATRIBUTO NAME** (deveria ser `name="language_preference"`)

### 3. INCOMPATIBILIDADES CRÍTICAS IDENTIFICADAS

#### 3.1 Problema Principal: Ausência de Atributos NAME
**CRÍTICO**: Todos os campos do formulário perfil.html estão sem o atributo `name`, o que impede o envio correto dos dados.

#### 3.2 Mapeamento de Campos de Notificação
O campo `email-notifications` deveria mapear para:
- `usuarios.email_breaking`
- `usuarios.email_comments`
- `usuarios.email_newsletter`

O campo `push-notifications` deveria mapear para:
- `usuarios.push_breaking`
- `usuarios.push_interests`
- `usuarios.push_comments`

#### 3.3 Diferenças nas Categorias
Cadastro.html tem 6 categorias, perfil.html tem 7:
- Cadastro: politica, economia, esportes, tecnologia, saude, cultura
- Perfil: politica, economia, esportes, tecnologia, saude, cultura, **mundo**

### 4. CAMPOS AUSENTES NO FORMULÁRIO DE CADASTRO

Campos presentes no BD mas ausentes no cadastro:
- `telefone` (presente apenas no perfil)
- `cidade` (presente apenas no perfil)
- `estado` (presente apenas no perfil)
- `bio` (presente apenas no perfil)
- Configurações detalhadas de notificação
- Configurações de privacidade

### 5. CAMPOS AUSENTES NO FORMULÁRIO DE PERFIL

Campos que deveriam estar no perfil mas estão ausentes:
- `genero` (presente apenas no cadastro)
- Upload de `foto_perfil`
- Configurações granulares de notificação por tipo

### 6. PROBLEMAS DE ESTRUTURA DE DADOS

#### 6.1 Campo preferencias vs favorite_categories
- Cadastro usa `preferencias[]` (array)
- BD tem tanto `preferencias` (JSON) quanto `favorite_categories` (LONGTEXT)
- Perfil usa `favorite-categories` (select multiple)

#### 6.2 Inconsistência nos Nomes dos Campos
- Cadastro: `data_nascimento`
- Perfil: `edit-birthdate` (sem name)
- BD: `data_nascimento`

### 7. CORREÇÕES NECESSÁRIAS

#### 7.1 Correções Críticas no perfil.html:
1. Adicionar atributo `name` em todos os campos do formulário
2. Corrigir mapeamento dos campos de notificação
3. Padronizar nomes dos campos com o banco de dados
4. Adicionar campo de gênero
5. Adicionar upload de foto de perfil

#### 7.2 Correções no cadastro.html:
1. Considerar adicionar campos básicos de perfil (telefone, cidade)
2. Padronizar categorias com o perfil

#### 7.3 Correções no Backend:
1. Verificar se a API está preparada para receber todos os campos
2. Implementar validação adequada para os novos campos
3. Garantir que o mapeamento JSON das preferências funcione corretamente

### 8. PRIORIDADE DAS CORREÇÕES

#### Alta Prioridade:
- ✅ Adicionar atributos `name` nos campos do perfil.html
- ✅ Corrigir mapeamento de notificações
- ✅ Padronizar nomes dos campos

#### Média Prioridade:
- ⚠️ Adicionar campo gênero no perfil
- ⚠️ Adicionar upload de foto no perfil
- ⚠️ Padronizar categorias entre formulários

#### Baixa Prioridade:
- 📝 Adicionar campos opcionais no cadastro
- 📝 Melhorar validações frontend

### 9. CONCLUSÃO

Os formulários apresentam **incompatibilidades críticas** que impedem o funcionamento correto:

1. **perfil.html**: Todos os campos sem atributo `name`
2. **Mapeamento inconsistente**: Campos de notificação mal estruturados
3. **Categorias divergentes**: Diferenças entre cadastro e perfil
4. **Campos ausentes**: Alguns campos importantes não estão em ambos os formulários

A correção desses problemas é **essencial** para o funcionamento adequado do sistema.