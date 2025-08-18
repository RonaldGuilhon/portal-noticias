# 🚀 Guia de Instalação Local - Portal de Notícias

Este guia fornece instruções detalhadas para executar o Portal de Notícias localmente em seu ambiente de desenvolvimento.

## 📋 Pré-requisitos

### Software Necessário
- **PHP 7.4+** com extensões:
  - PDO
  - PDO_MySQL
  - GD
  - mbstring
  - openssl
  - curl
  - json
- **MySQL 5.7+** ou **MariaDB 10.2+**
- **Servidor Web** (Apache, Nginx ou servidor embutido do PHP)
- **Git** para clonar o repositório

### Verificação do Ambiente
```bash
# Verificar versão do PHP
php --version

# Verificar extensões instaladas
php -m | grep -E "pdo|mysql|gd|mbstring|openssl|curl|json"

# Verificar MySQL
mysql --version
```

## 🛠️ Instalação Passo a Passo

### 1. Clonar o Repositório
```bash
git clone https://github.com/seu-usuario/portal-noticias.git
cd portal-noticias
```

### 2. Configurar o Banco de Dados

#### Opção A: Via linha de comando
```bash
# Conectar ao MySQL
mysql -u root -p

# Criar banco de dados
CREATE DATABASE portal_noticias CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Sair do MySQL
exit

# Importar estrutura
mysql -u root -p portal_noticias < database/portal_noticias.sql
```

#### Opção B: Via phpMyAdmin
1. Acesse phpMyAdmin
2. Crie um novo banco: `portal_noticias`
3. Importe o arquivo `database/portal_noticias.sql`

### 3. Configurar Variáveis de Ambiente

```bash
# Copiar arquivo de exemplo
cp .env.example .env
```

Edite o arquivo `.env` com suas configurações:

```env
# Configurações do Banco de Dados
DB_HOST=localhost
DB_NAME=portal_noticias
DB_USERNAME=root
DB_PASSWORD=sua_senha_mysql
DB_PORT=3306

# Configurações da Aplicação
APP_URL=http://localhost:8080
APP_ENV=development
APP_DEBUG=true

# Configurações de Email (opcional para desenvolvimento)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=seu_email@gmail.com
SMTP_PASSWORD=sua_senha_app
SMTP_ENCRYPTION=tls

# Chave JWT (gere uma chave segura)
JWT_SECRET=sua_chave_jwt_muito_segura_aqui

# Configurações de Upload
UPLOAD_MAX_SIZE=10485760
UPLOAD_PATH=backend/uploads/
```

### 4. Configurar Permissões

#### Linux/macOS:
```bash
# Criar diretórios necessários
mkdir -p backend/uploads/{noticias,usuarios,anuncios,temp}
mkdir -p backend/logs

# Configurar permissões
chmod -R 755 backend/uploads/
chmod -R 755 backend/logs/
chown -R www-data:www-data backend/uploads/ # Se usando Apache
```

#### Windows:
```cmd
# Criar diretórios
mkdir backend\uploads\noticias
mkdir backend\uploads\usuarios
mkdir backend\uploads\anuncios
mkdir backend\uploads\temp
mkdir backend\logs
```

### 5. Executar o Servidor Local

#### Opção A: Servidor Embutido do PHP (Recomendado para desenvolvimento)
```bash
# Na pasta do projeto
cd frontend
php -S localhost:8080
```

#### Opção B: XAMPP/WAMP
1. Copie o projeto para `htdocs/portal-noticias`
2. Acesse `http://localhost/portal-noticias/frontend/`

#### Opção C: Apache Virtual Host
```apache
<VirtualHost *:80>
    ServerName portal-noticias.local
    DocumentRoot /caminho/para/portal-noticias/frontend
    
    <Directory /caminho/para/portal-noticias/frontend>
        AllowOverride All
        Require all granted
    </Directory>
    
    Alias /backend /caminho/para/portal-noticias/backend
    <Directory /caminho/para/portal-noticias/backend>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## 🔧 Configuração Inicial

### 1. Criar Usuário Administrador

Acesse: `http://localhost:8080/cadastro.html` e crie o primeiro usuário.

Ou via SQL:
```sql
INSERT INTO usuarios (nome, email, senha, tipo, ativo) 
VALUES ('Admin', 'admin@portal.com', '$2y$10$exemplo_hash_senha', 'admin', 1);
```

### 2. Configurar Categorias Iniciais

Acesse o painel admin: `http://localhost:8080/admin/` e crie as categorias básicas:
- Política
- Economia
- Esportes
- Tecnologia
- Entretenimento

### 3. Testar Upload de Arquivos

1. Acesse `http://localhost:8080/admin/uploads.html`
2. Teste o upload de uma imagem
3. Verifique se o arquivo foi salvo em `backend/uploads/`

## 🐛 Solução de Problemas

### Erro de Conexão com Banco
```
PDOException: SQLSTATE[HY000] [1045] Access denied
```
**Solução**: Verifique as credenciais no arquivo `.env`

### Erro de Permissão de Upload
```
Warning: move_uploaded_file(): failed to open stream
```
**Solução**: 
```bash
chmod -R 755 backend/uploads/
# ou
chown -R www-data:www-data backend/uploads/
```

### Erro 404 nas Rotas da API
**Solução**: Verifique se o mod_rewrite está ativado (Apache) ou configure as rotas no Nginx

### Erro de CORS
```
Access to fetch at 'http://localhost/backend/...' from origin 'http://localhost:8080' has been blocked by CORS policy
```
**Solução**: O arquivo `.htaccess` já inclui headers CORS. Verifique se está sendo carregado.

### Erro de JWT
```
JWT signature verification failed
```
**Solução**: Gere uma nova chave JWT no arquivo `.env`:
```bash
# Gerar chave aleatória
php -r "echo bin2hex(random_bytes(32));"
```

## 🧪 Testes

### Testar Funcionalidades Básicas

1. **Frontend**: `http://localhost:8080/`
2. **Login**: `http://localhost:8080/login.html`
3. **Admin**: `http://localhost:8080/admin/`
4. **API**: `http://localhost:8080/backend/noticias`

### Verificar Logs
```bash
# Ver logs de erro
tail -f backend/logs/error.log

# Ver logs da API
tail -f backend/logs/api.log
```

## 📱 Desenvolvimento

### Estrutura de Desenvolvimento
```
portal-noticias/
├── frontend/           # Desenvolvimento frontend
│   ├── assets/        # CSS, JS, imagens
│   ├── admin/         # Painel administrativo
│   └── *.html         # Páginas públicas
├── backend/           # API e lógica
│   ├── controllers/   # Controladores
│   ├── models/        # Modelos
│   └── services/      # Serviços
└── database/          # Scripts SQL
```

### Hot Reload (Opcional)
Para desenvolvimento com auto-reload:
```bash
# Instalar live-server (Node.js)
npm install -g live-server

# Executar com auto-reload
cd frontend
live-server --port=8080
```

## 🚀 Próximos Passos

1. ✅ Configurar ambiente local
2. ✅ Testar funcionalidades básicas
3. 📝 Criar conteúdo de teste
4. 🎨 Personalizar tema
5. 📧 Configurar email
6. 🔒 Configurar HTTPS (produção)

---

**Dica**: Mantenha este arquivo atualizado conforme você faz alterações no projeto!

## 📞 Suporte

Se encontrar problemas:
1. Verifique os logs em `backend/logs/`
2. Consulte a seção de solução de problemas
3. Abra uma issue no GitHub

**Boa sorte com seu Portal de Notícias! 🎉**