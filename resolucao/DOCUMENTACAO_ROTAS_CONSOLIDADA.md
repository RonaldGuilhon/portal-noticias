# Documentação Consolidada - Rotas e Caminhos

## Portal de Notícias - Rotas Atualizadas (Janeiro 2025)

### 🔧 Status das Correções
Todas as correções de rotas e caminhos foram verificadas e estão funcionais.

---

## 📍 Rotas da API (Backend)

### Autenticação
- `POST /auth/login` - Login de usuário
- `POST /auth/register` - Registro de usuário
- `POST /auth/logout` - Logout de usuário
- `GET /auth/check` - Verificar autenticação
- `POST /auth/check-email` - Verificar email
- `POST /auth/forgot-password` - Recuperar senha

### Usuários
- `GET /user/profile` - Obter perfil do usuário
- `PUT /user/profile` - Atualizar perfil
- `POST /user/avatar` - Upload de avatar
- `PUT /user/password` - Alterar senha
- `GET /user/preferences` - Obter preferências
- `PUT /user/preferences` - Atualizar preferências

### Notícias
- `GET /noticias` - Listar todas as notícias
- `GET /noticias/{id}` - Obter notícia por ID
- `GET /noticias/slug/{slug}` - Obter notícia por slug
- `GET /noticias?action=categoria&id={id}` - Notícias por categoria
- `GET /noticias?action=featured` - Notícias em destaque
- `GET /noticias?action=popular` - Notícias populares
- `GET /noticias/sugestoes` - Sugestões de notícias

### Categorias
- `GET /categorias` - Listar todas as categorias
- `GET /categorias/{id}` - Obter categoria por ID
- `GET /categorias/slug/{slug}` - Obter categoria por slug

### Tags
- `GET /tags` - Listar todas as tags
- `GET /tags/{id}` - Obter tag por ID
- `GET /tags/slug/{slug}` - Obter tag por slug

### Comentários
- `GET /comentarios` - Listar comentários
- `POST /comentarios` - Criar comentário
- `PUT /comentarios/{id}` - Atualizar comentário
- `DELETE /comentarios/{id}` - Deletar comentário

### Cache
- `GET /cache/stats` - Estatísticas do cache
- `POST /cache/clear` - Limpar cache
- `POST /cache/warm` - Aquecer cache

### Arquivos
- `POST /files/upload` - Upload de arquivos
- `GET /files/{filename}` - Obter arquivo

### Notificações
- `GET /notificacoes` - Listar notificações
- `POST /notificacoes/mark-read` - Marcar como lida

### Push Notifications
- `POST /push/subscribe` - Inscrever-se
- `POST /push/unsubscribe` - Desinscrever-se
- `POST /push/send` - Enviar notificação

---

## 🌐 Páginas Frontend

### Páginas Principais
- `/` - Página inicial
- `/categoria/{slug}` - Página de categoria
- `/noticia/{slug}` - Página de notícia
- `/busca` - Página de busca
- `/contato` - Página de contato
- `/sobre` - Página sobre

### Área do Usuário
- `/login` - Página de login
- `/registro` - Página de registro
- `/perfil` - Página de perfil
- `/configuracoes` - Configurações do usuário

### Painel Administrativo
- `/admin/` - Dashboard administrativo
- `/admin/noticias` - Gerenciar notícias
- `/admin/categorias` - Gerenciar categorias
- `/admin/usuarios` - Gerenciar usuários
- `/admin/comentarios` - Gerenciar comentários
- `/admin/cache` - Gerenciar cache
- `/admin/configuracoes` - Configurações do sistema

---

## 🔧 Correções Implementadas

### 1. URLs e APIs Incorretas (Arquivo 02)
✅ **Status**: Corrigido
- Atualizadas URLs de desenvolvimento para produção
- Corrigidos endpoints de API no frontend
- Ajustados caminhos de assets e recursos

### 2. Links Absolutos vs Relativos (Arquivo 27)
✅ **Status**: Corrigido
- Padronizados caminhos relativos no frontend
- Corrigidos links de navegação
- Ajustados imports de CSS e JavaScript

### 3. Rota de Logout (Arquivo 31)
✅ **Status**: Corrigido
- Corrigido redirecionamento após logout
- Ajustada limpeza de sessão
- Implementada validação de autenticação

### 4. Problemas de Perfil e Upload (Arquivos 07, 08, 09)
✅ **Status**: Corrigido
- Corrigidas rotas de upload de avatar
- Ajustados caminhos de arquivos de perfil
- Implementada validação de tipos de arquivo

### 5. APIs e CORS (Arquivos 16, 21, 22)
✅ **Status**: Corrigido
- Configurados cabeçalhos CORS adequados
- Padronizadas respostas JSON das APIs
- Implementado tratamento de requisições OPTIONS

### 6. APIs JSON Final (Arquivo 23)
✅ **Status**: Corrigido
- Corrigidas chamadas diretas a controllers
- Implementada verificação isApiRequest()
- Ajustado roteamento do CacheController

---

## 🛠️ Configurações Técnicas

### Servidor de Desenvolvimento
- **URL Base**: `http://localhost:8000`
- **Comando**: `php -S localhost:8000 -t . backend/router.php`
- **Router**: `backend/router.php`

### CORS
- **Origin Permitida**: `http://localhost:8000`
- **Métodos**: GET, POST, PUT, DELETE, OPTIONS
- **Headers**: Content-Type, Authorization, X-Requested-With
- **Credentials**: true

### Estrutura de Arquivos
```
portal-noticias/
├── frontend/
│   ├── assets/js/main.js (rotas corretas implementadas)
│   ├── admin/cache.html (rotas corretas implementadas)
│   └── ...
├── backend/
│   ├── router.php (roteamento principal)
│   ├── controllers/ (todos com CORS configurado)
│   └── config/config.php
├── config-dev.php (com isApiRequest())
└── resolucao/ (documentação de correções)
```

---

## ✅ Verificações Realizadas

1. **Rotas de API**: Todas funcionais e retornando JSON
2. **CORS**: Configurado corretamente em todos os controllers
3. **Frontend**: Usando rotas corretas (não chamadas diretas)
4. **Cache**: Sistema funcionando sem interferir nas APIs
5. **Autenticação**: Fluxo completo de login/logout
6. **Upload**: Sistema de arquivos funcionando
7. **Notificações**: Push notifications configuradas

---

## 📝 Notas Importantes

- Todas as correções foram testadas e estão funcionais
- O sistema usa roteamento centralizado via `router.php`
- APIs retornam JSON consistentemente
- CORS configurado para desenvolvimento local
- Sistema de cache não interfere nas respostas JSON
- Debugging habilitado apenas para páginas HTML (não APIs)

---

**Última Atualização**: Janeiro 2025  
**Status Geral**: ✅ Todas as rotas funcionais e corrigidas