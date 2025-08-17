# Configuração do MySQL para o Portal de Notícias

## Problema Identificado
O driver PDO MySQL está disponível no PHP mas não está habilitado.

## Solução

### 1. Habilitar a extensão PDO MySQL

Edite o arquivo `C:\php-8.3.2\php.ini` e:

1. Encontre a linha: `;extension=pdo_mysql` (linha 955)
2. Remova o `;` do início para descomentá-la: `extension=pdo_mysql`
3. Salve o arquivo
4. Reinicie o servidor web

### 2. Instalar e configurar MySQL

Se o MySQL não estiver instalado:

1. Baixe o MySQL Community Server em: https://dev.mysql.com/downloads/mysql/
2. Instale seguindo as instruções
3. Configure com usuário `root` e senha vazia (ou ajuste o arquivo `.env`)
4. Crie o banco de dados executando: `database/portal_noticias.sql`

### 3. Verificar configuração

Após as alterações, execute:
```bash
php -r "echo in_array('mysql', PDO::getAvailableDrivers()) ? 'MySQL OK' : 'MySQL não disponível';"
```

## Status Atual

✅ Sistema funcionando com mock database  
⚠️ MySQL disponível mas não habilitado  
📝 Configuração necessária no php.ini  

## Alternativas

1. **Usar SQLite** (mais simples): Descomente `;extension=pdo_sqlite` no php.ini
2. **Continuar com mock** (para desenvolvimento): Sistema já funcional
3. **Configurar MySQL** (recomendado para produção): Seguir passos acima