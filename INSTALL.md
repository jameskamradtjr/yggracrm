# Guia de Instalação - SistemaBase Framework

## Requisitos do Sistema

- PHP >= 8.1
- MySQL >= 8.0 ou MariaDB >= 10.6
- Composer
- Apache com mod_rewrite ou Nginx
- Extensões PHP necessárias:
  - PDO
  - pdo_mysql
  - mbstring
  - json
  - openssl

## Instalação Passo a Passo

### 1. Clone ou baixe o projeto

```bash
git clone [repository-url] sistemabase26
cd sistemabase26
```

### 2. Instale as dependências com Composer

```bash
composer install
```

### 3. Configure o ambiente

Copie o arquivo de exemplo de configuração:

```bash
# Windows
copy env.example .env

# Linux/Mac
cp env.example .env
```

Edite o arquivo `.env` e configure suas variáveis:

```env
APP_NAME="SistemaBase Framework"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/sistemabase26

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistemabase
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Crie o banco de dados

Acesse o MySQL e crie o banco:

```sql
CREATE DATABASE sistemabase CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Execute as migrations

```bash
php migrate apply
```

Isso criará todas as tabelas necessárias no banco de dados.

### 6. Configure o servidor web

#### Apache (XAMPP/WAMP)

O projeto já vem com arquivos `.htaccess` configurados. Certifique-se de que:

1. O `mod_rewrite` está ativado
2. O DocumentRoot aponta para a pasta `sistemabase26`
3. A diretiva `AllowOverride` está configurada como `All`

Exemplo de Virtual Host:

```apache
<VirtualHost *:80>
    ServerName sistemabase.local
    DocumentRoot "C:/xampp/htdocs/sistemabase26"
    
    <Directory "C:/xampp/htdocs/sistemabase26">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx

Configuração básica:

```nginx
server {
    listen 80;
    server_name sistemabase.local;
    root /var/www/sistemabase26/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 7. Acesse o sistema

Abra o navegador e acesse:

```
http://localhost/sistemabase26
```

ou se configurou virtual host:

```
http://sistemabase.local
```

### 8. Crie sua primeira conta

1. Clique em "Criar uma conta"
2. Preencha o formulário de registro
3. Faça login com suas credenciais

## Comandos Úteis

### Migrations

```bash
# Executar todas as migrations pendentes
php migrate apply

# Reverter a última batch de migrations
php migrate rollback

# Reverter todas as migrations
php migrate rollback --all

# Criar nova migration
php migrate create nome_da_migration
```

### Permissões de Pasta (Linux/Mac)

```bash
chmod -R 755 storage
chmod -R 755 public/uploads
```

## Solução de Problemas

### Erro: "Class not found"

Execute:
```bash
composer dump-autoload
```

### Erro de conexão com banco de dados

1. Verifique se o MySQL está rodando
2. Confira as credenciais no arquivo `.env`
3. Teste a conexão manualmente

### Página em branco

1. Ative o modo debug no `.env`: `APP_DEBUG=true`
2. Verifique os logs em `storage/logs/`
3. Verifique as permissões das pastas

### Erro 500

1. Verifique se o mod_rewrite está ativo (Apache)
2. Verifique as permissões dos arquivos
3. Confira os logs de erro do PHP

## Próximos Passos

Após a instalação:

1. Configure as permissões e roles conforme sua necessidade
2. Personalize o tema e layout
3. Adicione seus módulos e funcionalidades
4. Configure email para recuperação de senha
5. Configure backup automático do banco

## Suporte

Para dúvidas ou problemas:

- Verifique a documentação no README.md
- Consulte os logs em `storage/logs/`
- Entre em contato com a equipe de desenvolvimento

## Segurança

⚠️ **IMPORTANTE**: 

- Nunca commite o arquivo `.env`
- Altere `APP_DEBUG` para `false` em produção
- Use senhas fortes para banco de dados
- Mantenha o Composer e dependências atualizados
- Configure HTTPS em produção

