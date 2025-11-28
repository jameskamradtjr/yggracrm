# Guia de Uso - SistemaBase Framework

## Índice

1. [Estrutura do Projeto](#estrutura-do-projeto)
2. [Trabalhando com Models](#trabalhando-com-models)
3. [Criando Controllers](#criando-controllers)
4. [Sistema de Rotas](#sistema-de-rotas)
5. [Views e Templates](#views-e-templates)
6. [Migrations](#migrations)
7. [RBAC - Controle de Acesso](#rbac---controle-de-acesso)
8. [Multi-Tenancy](#multi-tenancy)
9. [Helpers Úteis](#helpers-úteis)

---

## Estrutura do Projeto

```
sistemabase26/
├── app/
│   ├── Controllers/      # Controllers da aplicação
│   ├── Models/          # Models (Active Record)
│   ├── Middleware/      # Middlewares
│   └── Services/        # Serviços de negócio
├── config/              # Configurações
├── database/
│   └── migrations/      # Migrations
├── public/              # Pasta pública (assets, index.php)
├── routes/              # Definição de rotas
├── src/
│   ├── Core/           # Core do framework
│   └── Helpers/        # Funções auxiliares
├── storage/            # Logs, cache, uploads
├── views/              # Templates
└── vendor/             # Dependências
```

---

## Trabalhando com Models

### Criando um Model

```php
<?php

namespace App\Models;

use Core\Model;

class Product extends Model
{
    protected string $table = 'products';
    
    protected array $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'user_id'
    ];
    
    protected array $hidden = ['deleted_at'];
    
    // Multi-tenancy automático
    protected bool $multiTenant = true;
    protected string $tenantColumn = 'user_id';
    
    // Timestamps automáticos
    protected bool $timestamps = true;
    
    // Soft deletes
    protected bool $softDeletes = true;
}
```

### Usando Models

```php
// Buscar todos
$products = Product::all();

// Buscar por ID
$product = Product::find(1);

// Criar
$product = Product::create([
    'name' => 'Produto Teste',
    'price' => 99.90
]);

// Atualizar
$product->update(['price' => 89.90]);

// Deletar
$product->delete();

// Query Builder
$products = Product::where('price', 100, '>')
    ->orderBy('name', 'ASC')
    ->limit(10)
    ->get();

// Primeiro resultado
$product = Product::where('name', 'Teste')->first();
```

---

## Criando Controllers

### Controller Básico

```php
<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function index(): string
    {
        // Verifica permissão
        $this->authorizeOrFail('products.read');
        
        $products = Product::all();
        
        return $this->view('products/index', [
            'products' => $products
        ]);
    }
    
    public function store(): void
    {
        $this->authorizeOrFail('products.create');
        
        // Valida CSRF
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token inválido.');
            $this->back();
        }
        
        // Valida dados
        $data = $this->validate([
            'name' => 'required|min:3',
            'price' => 'required|numeric'
        ]);
        
        Product::create($data);
        
        session()->flash('success', 'Produto criado!');
        $this->redirect('/products');
    }
}
```

---

## Sistema de Rotas

### Rotas Básicas

```php
// routes/web.php

use App\Controllers\ProductController;

$router = app()->router();

// GET
$router->get('/products', [ProductController::class, 'index']);

// POST
$router->post('/products', [ProductController::class, 'store']);

// PUT/PATCH
$router->put('/products/{id}', [ProductController::class, 'update']);

// DELETE
$router->delete('/products/{id}', [ProductController::class, 'destroy']);

// Qualquer método
$router->any('/webhook', [WebhookController::class, 'handle']);
```

### Rotas com Parâmetros

```php
$router->get('/products/{id}', function($params) {
    $id = $params['id'];
    // ...
});

$router->get('/posts/{slug}/comments/{id}', function($params) {
    $slug = $params['slug'];
    $commentId = $params['id'];
    // ...
});
```

### Grupos de Rotas

```php
// Com prefixo
$router->group(['prefix' => '/admin'], function($router) {
    $router->get('/dashboard', [AdminController::class, 'dashboard']);
    $router->get('/users', [AdminController::class, 'users']);
});
// URLs: /admin/dashboard, /admin/users

// Com middleware
$router->group(['middleware' => [AuthMiddleware::class]], function($router) {
    $router->get('/profile', [UserController::class, 'profile']);
});

// Combinando
$router->group([
    'prefix' => '/api',
    'middleware' => [ApiAuthMiddleware::class]
], function($router) {
    $router->get('/users', [Api\UserController::class, 'index']);
});
```

---

## Views e Templates

### Estrutura de View

```php
<!-- views/products/index.php -->

<?php $this->extend('app'); ?>

<?php $this->section('content'); ?>

<h1>Produtos</h1>

<table class="table">
    <?php foreach ($products as $product): ?>
        <tr>
            <td><?php echo e($product->name); ?></td>
            <td><?php echo e($product->price); ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
    console.log('Scripts específicos da página');
</script>
<?php $this->endSection(); ?>
```

### Renderizando Views

```php
// No Controller
return $this->view('products/index', [
    'products' => $products,
    'title' => 'Lista de Produtos'
]);

// Ou usando helper
return view('products/index', compact('products'));
```

---

## Migrations

### Criar Migration

```bash
php migrate create create_products_table
```

### Estrutura de Migration

```php
<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('products', function (Schema $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->string('sku', 50)->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->userIdColumn(); // Multi-tenancy
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('sku');
            $table->index('status');
        });
    }

    public function down(): void
    {
        $this->dropTable('products');
    }
};
```

### Comandos de Migration

```bash
# Executar migrations
php migrate apply

# Reverter última batch
php migrate rollback

# Reverter todas
php migrate rollback --all
```

---

## RBAC - Controle de Acesso

### Criar Permissions

```php
use App\Models\Permission;

// Criar permissões CRUD para um recurso
Permission::createCrudPermissions('products', 'Produtos');

// Ou manualmente
Permission::create([
    'name' => 'Criar Produtos',
    'slug' => 'products.create',
    'resource' => 'products',
    'action' => 'create'
]);
```

### Criar Roles

```php
use App\Models\Role;

$role = Role::create([
    'user_id' => auth()->id(),
    'name' => 'Gerente',
    'slug' => 'manager',
    'description' => 'Gerente da loja'
]);

// Atribuir permissões
$role->syncPermissions([1, 2, 3, 4]); // IDs das permissões
```

### Verificar Permissões

```php
// No Controller
if (auth()->can('create', 'products')) {
    // Usuário pode criar produtos
}

// Ou
$this->authorizeOrFail('products.create');

// No Model User
$user = auth()->user();
$user->hasPermission('products.create');
$user->hasRole('manager');
$user->can('create', 'products');
```

### No Blade/View

```php
<?php if (auth()->can('create', 'products')): ?>
    <button>Criar Produto</button>
<?php endif; ?>
```

---

## Multi-Tenancy

O sistema implementa multi-tenancy automático via `user_id`. Cada conta principal (owner) tem seus próprios dados isolados.

### Como Funciona

```php
// Model com multi-tenancy
class Product extends Model
{
    protected bool $multiTenant = true;
    protected string $tenantColumn = 'user_id';
}

// Automaticamente filtra por user_id do usuário logado
$products = Product::all(); // WHERE user_id = auth()->id()

// Criar automaticamente adiciona user_id
$product = Product::create([
    'name' => 'Produto'
    // user_id adicionado automaticamente
]);
```

### Sub-usuários

Sub-usuários herdam o `user_id` do owner:

```php
// Criar sub-usuário
$subUser = User::create([
    'name' => 'João',
    'email' => 'joao@email.com',
    'password' => bcrypt('senha'),
    'parent_user_id' => auth()->id() // Owner
]);

// Atribuir roles específicas
$subUser->assignRole($roleId);
```

---

## Helpers Úteis

```php
// Configuração
config('app.name');
env('DB_DATABASE');

// URL
url('/products');
url('/products/1');
asset('images/logo.png');

// Autenticação
auth()->check();
auth()->user();
auth()->id();
auth()->logout();

// Sessão
session()->set('key', 'value');
session()->get('key');
session()->flash('success', 'Mensagem');

// View
view('products.index', $data);

// Redirect
redirect('/dashboard');
abort(404);

// Validação e Segurança
e($string); // Escapa HTML
csrf_token();
csrf_field();
bcrypt('password');

// Utilitários
dd($var); // Dump and die
dump($var); // Dump
logger('Mensagem de log');
now(); // Data/hora atual
```

---

## Exemplos Práticos

### Sistema CRUD Completo

Veja os controllers `UserController` e `RoleController` para exemplos completos de CRUD com validação, permissões e multi-tenancy.

### API RESTful

```php
// routes/api.php
$router->group(['prefix' => '/api'], function($router) {
    $router->get('/products', function() {
        $products = Product::all();
        json_response(['data' => $products]);
    });
    
    $router->post('/products', function() {
        $data = request()->all();
        $product = Product::create($data);
        json_response(['data' => $product], 201);
    });
});
```

### Upload de Arquivos

```php
if (request()->hasFile('avatar')) {
    $file = request()->file('avatar');
    $filename = time() . '_' . $file['name'];
    $path = base_path('public/uploads/' . $filename);
    move_uploaded_file($file['tmp_name'], $path);
    
    $user->update(['avatar' => $filename]);
}
```

---

## Boas Práticas

1. **Sempre use prepared statements** - O framework já faz isso automaticamente
2. **Valide CSRF em formulários** - Use `csrf_field()` e `verify_csrf()`
3. **Escape output** - Use `e()` para escapar HTML
4. **Use namespaces PSR-4** - Mantenha a estrutura de pastas
5. **Documente suas classes** - Use PHPDoc
6. **Valide inputs** - Use o Validator do framework
7. **Verifique permissões** - Use `authorizeOrFail()` ou `auth()->can()`
8. **Log erros importantes** - Use `logger()`
9. **Use transactions** - Para operações que alteram múltiplas tabelas
10. **Mantenha controllers enxutos** - Use Services para lógica complexa

---

## Próximos Passos

- Integrar sistema de email (PHPMailer já está nas dependências)
- Implementar cache (Redis/Memcached)
- Adicionar queue system para tarefas assíncronas
- Integração com AWS (S3, SES, etc)
- API com autenticação JWT
- Testes automatizados (PHPUnit)
- CI/CD pipeline

