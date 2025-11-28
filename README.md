# SistemaBase Framework

Um framework PHP moderno e robusto para desenvolvimento de aplicações empresariais, construído com as melhores práticas do mercado.

## 🚀 Características

- **PHP 8.1+** com tipagem forte e recursos modernos
- **PSR-4 Autoloading** via Composer
- **Arquitetura MVC** limpa e organizada
- **Sistema de Migrations** com apply e rollback
- **RBAC** (Role-Based Access Control) completo
- **Multi-tenant** com isolamento por user_id
- **Sistema de autenticação** robusto com JWT
- **Sub-usuários** com permissões granulares
- **Preparado para SaaS** e multi-empresa
- **Bootstrap 5** e jQuery integrados
- **API RESTful** pronta para uso
- **Preparado para AWS** e cloud services

## 📋 Requisitos

- PHP >= 8.1
- MySQL >= 8.0 ou MariaDB >= 10.6
- Composer
- Apache/Nginx com mod_rewrite
- Extensões PHP: PDO, mbstring, json, openssl

## 🔧 Instalação

1. Clone o repositório
```bash
git clone [repository-url]
cd sistemabase26
```

2. Instale as dependências
```bash
composer install
```

3. Configure o ambiente
```bash
cp .env.example .env
# Edite o arquivo .env com suas configurações
```

4. Execute as migrations
```bash
# 3. Criar banco de dados
mysql -u root -e "CREATE DATABASE sistemabase"

# 4. Executar migrations
php migrate apply

# 5. Popular dados iniciais
php -f database/seeds/InitialDataSeeder.php
```

## 📁 Estrutura de Diretórios

```
sistemabase26/
├── app/
│   ├── Controllers/      # Controllers da aplicação
│   ├── Models/          # Models (Eloquent-like)
│   ├── Middleware/      # Middlewares de autenticação e autorização
│   └── Services/        # Serviços de negócio
├── config/              # Arquivos de configuração
├── database/
│   └── migrations/      # Migrations do banco de dados
├── public/              # Pasta pública (DocumentRoot)
│   ├── index.php       # Front controller
│   └── assets/         # Assets estáticos
├── routes/              # Definição de rotas
├── src/
│   ├── Core/           # Core do framework
│   └── Helpers/        # Funções auxiliares
├── storage/
│   ├── logs/           # Logs da aplicação
│   └── cache/          # Cache da aplicação
├── views/              # Views (templates)
└── vendor/             # Dependências do Composer
```

## 🔐 Segurança

- Proteção contra SQL Injection via PDO Prepared Statements
- Proteção CSRF em formulários
- Bcrypt para hash de senhas
- JWT para autenticação de API
- Validação e sanitização de inputs
- Rate limiting em APIs

## 📚 Documentação

### Migrations

```bash
# Executar migrations
php migrate apply

# Reverter última migration
php migrate rollback

# Reverter todas as migrations
php migrate rollback --all

# Criar nova migration
php migrate create nome_da_migration
```

### Rotas

As rotas são definidas em `routes/web.php` e `routes/api.php`

### Controllers

Controllers seguem o padrão PSR-4 em `app/Controllers/`

### Models

Models seguem o padrão Active Record em `app/Models/`

## 🤝 Contribuindo

Este é um framework empresarial desenvolvido para uso interno da equipe.

## 📄 Licença

MIT License

