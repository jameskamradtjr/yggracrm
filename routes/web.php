<?php

/**
 * Rotas Web da Aplicação
 * 
 * @var \Core\Router $router
 */

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\UserController;
use App\Controllers\RoleController;
use App\Controllers\SistemaLogController;
use App\Controllers\NotificacaoController;
use App\Controllers\SettingsController;
use App\Controllers\LeadController;
use App\Controllers\FinancialController;

// O router é injetado automaticamente pela Application
// Não precisa chamar app()->router() aqui

// Rota inicial - redireciona para dashboard ou login
$router->get('/', function() {
    if (auth()->check()) {
        redirect('/dashboard');
    } else {
        redirect('/login');
    }
});

// Rota pública do Quiz (aceita parâmetros ?u=USER_ID ou ?token=TOKEN)
$router->get('/quiz', [LeadController::class, 'quiz']);

// Rota para gerar link único do quiz (protegida)
$router->group(['middleware' => [\App\Middleware\AuthMiddleware::class]], function($router) {
    $router->get('/leads/generate-link', [LeadController::class, 'generateQuizLink']);
});

// Rotas de Autenticação
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword']);
$router->get('/reset-password', [AuthController::class, 'showResetPassword']);
$router->post('/reset-password', [AuthController::class, 'resetPassword']);
$router->post('/logout', [AuthController::class, 'logout']);

// Rotas Protegidas (requerem autenticação)
$router->group(['middleware' => [\App\Middleware\AuthMiddleware::class]], function($router) {
    
    // Dashboard
    $router->get('/dashboard', [DashboardController::class, 'index']);
    
    // Perfil do Usuário
    $router->get('/profile', [UserController::class, 'profile']);
    $router->post('/profile', [UserController::class, 'updateProfile']);
    
    // CRUD de Usuários
    $router->get('/users', [UserController::class, 'index']);
    $router->get('/users/create', [UserController::class, 'create']);
    $router->post('/users', [UserController::class, 'store']);
    $router->get('/users/{id}', [UserController::class, 'show']);
    $router->get('/users/{id}/edit', [UserController::class, 'edit']);
    $router->post('/users/{id}', [UserController::class, 'update']);
    $router->post('/users/{id}/delete', [UserController::class, 'destroy']);
    
    // CRUD de Roles
    $router->get('/roles', [RoleController::class, 'index']);
    $router->get('/roles/create', [RoleController::class, 'create']);
    $router->post('/roles', [RoleController::class, 'store']);
    $router->get('/roles/{id}', [RoleController::class, 'show']);
    $router->get('/roles/{id}/edit', [RoleController::class, 'edit']);
    $router->post('/roles/{id}', [RoleController::class, 'update']);
    $router->post('/roles/{id}/delete', [RoleController::class, 'destroy']);
    
    // Logs do Sistema
    $router->get('/sistema/logs', [SistemaLogController::class, 'index']);
    $router->post('/sistema/logs/datatable', [SistemaLogController::class, 'datatable']);
    
    // Configurações do Sistema (apenas admin master)
    $router->get('/settings', [SettingsController::class, 'index']);
    $router->post('/settings/layout', [SettingsController::class, 'saveLayout']);
    $router->post('/settings/email', [SettingsController::class, 'saveEmail']);
    $router->post('/settings/integrations', [SettingsController::class, 'saveIntegrations']);
    $router->get('/settings/templates/create', [SettingsController::class, 'createTemplate']);
    $router->post('/settings/templates', [SettingsController::class, 'storeTemplate']);
    $router->get('/settings/templates/{id}/edit', [SettingsController::class, 'editTemplate']);
    $router->post('/settings/templates/{id}', [SettingsController::class, 'updateTemplate']);
    $router->post('/settings/templates/{id}/delete', [SettingsController::class, 'deleteTemplate']);
    
    // CRM de Leads
    $router->get('/leads', [LeadController::class, 'index']);
    $router->get('/leads/create', [LeadController::class, 'create']);
    $router->post('/leads', [LeadController::class, 'store']);
    $router->get('/leads/{id}', [LeadController::class, 'show']);
    $router->post('/leads/update-status', [LeadController::class, 'updateStatus']);
    $router->post('/leads/{id}/reanalyze', [LeadController::class, 'reanalyze']);
    $router->post('/leads/generate-quiz-link', [LeadController::class, 'generateQuizLink']);
    
    // Módulo Financeiro
    $router->get('/financial', [FinancialController::class, 'index']);
    $router->get('/financial/create', [FinancialController::class, 'create']);
    $router->post('/financial', [FinancialController::class, 'store']);
    $router->get('/financial/{id}/edit', [FinancialController::class, 'edit']);
    $router->post('/financial/{id}', [FinancialController::class, 'update']);
    $router->post('/financial/{id}/mark-paid', [FinancialController::class, 'markAsPaid']);
    $router->post('/financial/{id}/unmark-paid', [FinancialController::class, 'unmarkAsPaid']);
    
    // Contas Bancárias
    $router->get('/financial/bank-accounts', [FinancialController::class, 'bankAccounts']);
    $router->get('/financial/bank-accounts/create', [FinancialController::class, 'createBankAccount']);
    $router->post('/financial/bank-accounts', [FinancialController::class, 'storeBankAccount']);
    
    // Cartões de Crédito
    $router->get('/financial/credit-cards', [FinancialController::class, 'creditCards']);
    $router->get('/financial/credit-cards/create', [FinancialController::class, 'createCreditCard']);
    $router->post('/financial/credit-cards', [FinancialController::class, 'storeCreditCard']);
    
    // Categorias
    $router->get('/financial/categories', [FinancialController::class, 'categories']);
    $router->get('/financial/categories/create', [FinancialController::class, 'createCategory']);
    $router->post('/financial/categories', [FinancialController::class, 'storeCategory']);
    $router->get('/financial/categories/{id}/edit', [FinancialController::class, 'editCategory']);
    $router->post('/financial/categories/{id}', [FinancialController::class, 'updateCategory']);
    $router->post('/financial/categories/{id}/delete', [FinancialController::class, 'deleteCategory']);
    $router->post('/financial/categories/subcategories', [FinancialController::class, 'storeSubcategory']);
    $router->post('/financial/categories/subcategories/update', [FinancialController::class, 'updateSubcategory']);
    $router->post('/financial/categories/subcategories/delete', [FinancialController::class, 'deleteSubcategory']);
    $router->get('/financial/categories/subcategories/info', [FinancialController::class, 'getSubcategoryInfo']);
    
    // Centros de Custo
    $router->get('/financial/cost-centers', [FinancialController::class, 'costCenters']);
    $router->get('/financial/cost-centers/create', [FinancialController::class, 'createCostCenter']);
    $router->post('/financial/cost-centers', [FinancialController::class, 'storeCostCenter']);
    $router->post('/financial/cost-centers/subcenters', [FinancialController::class, 'storeSubCostCenter']);
    
    // Notificações
    $router->get('/api/notificacoes', [NotificacaoController::class, 'index']);
    $router->post('/api/notificacoes/{id}/marcar-lida', [NotificacaoController::class, 'marcarLida']);
    $router->post('/api/notificacoes/marcar-todas-lidas', [NotificacaoController::class, 'marcarTodasLidas']);
});

