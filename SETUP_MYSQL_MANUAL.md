# 🗄️ Setup Manual do MySQL - Portal de Notícias

## 📋 Situação Atual

**❌ Problema Identificado:** O PHP instalado não possui os drivers MySQL necessários (pdo_mysql)

**✅ Arquivos Disponíveis:**
- ✅ `database/portal_noticias.sql` - Estrutura do banco
- ✅ `dados_teste_completos.sql` - Dados de teste
- ✅ Sistema funcionando com dados mockados

## 🔧 Opções de Solução

### Opção 1: Usar XAMPP/WAMP (Recomendado)

1. **Baixar e instalar XAMPP:**
   - Download: https://www.apachefriends.org/
   - Instalar com MySQL e PHP

2. **Iniciar serviços:**
   - Abrir XAMPP Control Panel
   - Iniciar Apache e MySQL

3. **Acessar phpMyAdmin:**
   - Ir para: http://localhost/phpmyadmin/
   - Usuário: `root`, Senha: (vazia)

### Opção 2: MySQL Workbench

1. **Baixar MySQL Workbench:**
   - Download: https://dev.mysql.com/downloads/workbench/

2. **Conectar ao servidor MySQL local**

### Opção 3: Linha de Comando MySQL

1. **Instalar MySQL Server:**
   - Download: https://dev.mysql.com/downloads/mysql/
   - Adicionar ao PATH do Windows

## 📝 Comandos para Executar

### No phpMyAdmin (Opção 1):

1. **Criar banco de dados:**
   ```sql
   CREATE DATABASE portal_noticias CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Selecionar o banco:**
   ```sql
   USE portal_noticias;
   ```

3. **Importar estrutura:**
   - Clicar em "Importar"
   - Selecionar arquivo: `database/portal_noticias.sql`
   - Executar

4. **Importar dados:**
   - Clicar em "Importar"
   - Selecionar arquivo: `dados_teste_completos.sql`
   - Executar

### No MySQL CLI (Opção 3):

```bash
# 1. Conectar ao MySQL
mysql -u root -p

# 2. Criar banco de dados
CREATE DATABASE IF NOT EXISTS portal_noticias CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE portal_noticias;

# 3. Executar estrutura
SOURCE C:/Users/rsimplicio/Documents/GitHub/portal-noticias/database/portal_noticias.sql;

# 4. Executar dados de teste
SOURCE C:/Users/rsimplicio/Documents/GitHub/portal-noticias/dados_teste_completos.sql;

# 5. Verificar
SHOW TABLES;
SELECT COUNT(*) FROM usuarios;
SELECT COUNT(*) FROM noticias;
```

## ⚙️ Configurar o Backend

Após criar o banco de dados, edite o arquivo `backend/config/database.php`:

### Localizar estas linhas (aproximadamente linha 50-80):

```php
// Comentar ou remover a seção MockPDO
/*
class MockPDO {
    // ... todo o código da classe MockPDO
}
*/

// Descomentar a conexão real do MySQL
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=portal_noticias;charset=utf8mb4",
        'root',
        '', // senha vazia ou sua senha do MySQL
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
```

## 🔍 Verificar Instalação

Após configurar, acesse:
- **Frontend:** http://localhost:8000/
- **Admin:** http://localhost:8000/admin/

## 👥 Usuários de Teste Criados

| Tipo | Email | Senha | Descrição |
|------|-------|-------|-----------|
| Admin | admin@portal.com | password | Administrador completo |
| Editor | editor@portal.com | password | Editor de conteúdo |
| Autor | autor@portal.com | password | Autor de notícias |
| Leitor | leitor@portal.com | password | Usuário comum |

## 📊 Dados Inseridos

- **👥 Usuários:** 6 usuários de teste
- **📂 Categorias:** 9 categorias (Política, Economia, Esportes, etc.)
- **🏷️ Tags:** 15 tags variadas
- **📰 Notícias:** 12 notícias completas
- **💬 Comentários:** 18 comentários
- **👍 Curtidas:** Dados de curtidas em notícias e comentários
- **📊 Estatísticas:** Dados de acesso simulados
- **📧 Newsletter:** 6 inscrições de teste
- **📢 Anúncios:** 3 anúncios de exemplo
- **🖼️ Mídias:** Referências de imagens para notícias
- **🔔 Notificações:** 6 notificações de teste

## 🚨 Problemas Comuns

### "could not find driver"
- **Causa:** PHP sem extensão pdo_mysql
- **Solução:** Usar XAMPP ou instalar extensão PHP

### "Access denied for user 'root'"
- **Causa:** Senha do MySQL incorreta
- **Solução:** Verificar senha no MySQL ou usar senha vazia

### "Unknown database 'portal_noticias'"
- **Causa:** Banco não foi criado
- **Solução:** Executar comando CREATE DATABASE

## 📞 Suporte

Se encontrar problemas:
1. Verificar se o MySQL está rodando
2. Confirmar credenciais de acesso
3. Verificar se os arquivos SQL existem
4. Consultar logs de erro do MySQL

---

**✅ Após seguir estes passos, o portal estará funcionando com banco de dados MySQL real!**