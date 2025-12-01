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
use App\Controllers\PaymentMethodController;
use App\Controllers\CalendarController;
use App\Controllers\ContractController;
use App\Controllers\ContractTemplateController;
use App\Controllers\ProposalController;
use App\Controllers\AutomationController;

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

// API pública para buscar origens (usa token)
$router->get('/api/leads/origens', [LeadController::class, 'getOrigens']);

// Páginas públicas de assinatura de contratos (fora do middleware de auth)
$router->get('/contracts/sign/{token}', [ContractController::class, 'signPage']);
$router->post('/contracts/sign/{token}', [ContractController::class, 'processSignature']);

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
    
    // Templates de WhatsApp
    $router->get('/settings/whatsapp-templates/create', [SettingsController::class, 'createWhatsAppTemplate']);
    $router->post('/settings/whatsapp-templates', [SettingsController::class, 'storeWhatsAppTemplate']);
    $router->get('/settings/whatsapp-templates/{id}/edit', [SettingsController::class, 'editWhatsAppTemplate']);
    $router->post('/settings/whatsapp-templates/{id}', [SettingsController::class, 'updateWhatsAppTemplate']);
    $router->post('/settings/whatsapp-templates/{id}/delete', [SettingsController::class, 'deleteWhatsAppTemplate']);
    
    // CRM de Leads
    $router->get('/leads', [LeadController::class, 'index']);
    $router->get('/leads/create', [LeadController::class, 'create']);
    $router->post('/leads', [LeadController::class, 'store']);
    $router->get('/leads/{id}', [LeadController::class, 'show']);
    $router->get('/leads/{id}/edit-modal', [LeadController::class, 'editModal']);
    $router->post('/leads/{id}/update', [LeadController::class, 'update']);
    $router->post('/leads/update-etapa-funil', [LeadController::class, 'updateEtapaFunil']);
    $router->post('/leads/update-responsible', [LeadController::class, 'updateResponsible']);
    $router->post('/leads/update-status', [LeadController::class, 'updateStatus']);
    $router->post('/leads/{id}/reanalyze', [LeadController::class, 'reanalyze']);
    $router->post('/leads/{id}/convert-to-client', [LeadController::class, 'convertToClient']);
    $router->post('/leads/generate-quiz-link', [LeadController::class, 'generateQuizLink']);
    
    // Clientes
    $router->get('/clients', [\App\Controllers\ClientController::class, 'index']);
    $router->get('/clients/create', [\App\Controllers\ClientController::class, 'create']);
    $router->post('/clients', [\App\Controllers\ClientController::class, 'store']);
    $router->get('/clients/{id}', [\App\Controllers\ClientController::class, 'show']);
    $router->get('/clients/{id}/details', [\App\Controllers\ClientController::class, 'details']);
    $router->get('/clients/{id}/edit', [\App\Controllers\ClientController::class, 'edit']);
    $router->post('/clients/{id}', [\App\Controllers\ClientController::class, 'update']);
    $router->post('/clients/{id}/delete', [\App\Controllers\ClientController::class, 'destroy']);
    
    // Propostas
    $router->get('/proposals', [\App\Controllers\ProposalController::class, 'index']);
    $router->get('/proposals/create', [\App\Controllers\ProposalController::class, 'create']);
    $router->post('/proposals', [\App\Controllers\ProposalController::class, 'store']);
    $router->get('/proposals/{id}', [\App\Controllers\ProposalController::class, 'show']);
    $router->get('/proposals/{id}/preview', [\App\Controllers\ProposalController::class, 'preview']);
    $router->post('/proposals/{id}', [\App\Controllers\ProposalController::class, 'update']);
    $router->post('/proposals/{id}/delete', [\App\Controllers\ProposalController::class, 'destroy']);
    $router->post('/proposals/{id}/add-service', [\App\Controllers\ProposalController::class, 'addService']);
    $router->post('/proposals/{id}/add-condition', [\App\Controllers\ProposalController::class, 'addCondition']);
    $router->post('/proposals/{id}/send', [\App\Controllers\ProposalController::class, 'send']);
    $router->post('/proposals/{id}/duplicate', [\App\Controllers\ProposalController::class, 'duplicate']);
    $router->get('/proposals/{id}/pdf', [\App\Controllers\ProposalController::class, 'generatePdf']);
    
    // Projetos
    $router->get('/projects', [\App\Controllers\ProjectController::class, 'index']);
    $router->get('/projects/create', [\App\Controllers\ProjectController::class, 'create']);
    $router->post('/projects', [\App\Controllers\ProjectController::class, 'store']);
    $router->get('/projects/{id}', [\App\Controllers\ProjectController::class, 'show']);
    $router->get('/projects/{id}/edit', [\App\Controllers\ProjectController::class, 'edit']);
    $router->post('/projects/{id}', [\App\Controllers\ProjectController::class, 'update']);
    $router->post('/projects/{id}/delete', [\App\Controllers\ProjectController::class, 'destroy']);
    
    // Kanban de Projetos
    $router->get('/projects/{id}/kanban', [\App\Controllers\ProjectKanbanController::class, 'show']);
    $router->post('/projects/kanban/store-card', [\App\Controllers\ProjectKanbanController::class, 'storeCard']);
    $router->post('/projects/kanban/update-card-column', [\App\Controllers\ProjectKanbanController::class, 'updateCardColumn']);
    $router->get('/projects/kanban/{id}/edit-modal', [\App\Controllers\ProjectKanbanController::class, 'editCardModal']);
    $router->post('/projects/kanban/{id}/update', [\App\Controllers\ProjectKanbanController::class, 'updateCard']);
    $router->post('/projects/kanban/{id}/delete', [\App\Controllers\ProjectKanbanController::class, 'deleteCard']);
    $router->post('/projects/kanban/add-checklist-item', [\App\Controllers\ProjectKanbanController::class, 'addChecklistItem']);
    $router->post('/projects/kanban/checklist/{id}/update', [\App\Controllers\ProjectKanbanController::class, 'updateChecklistItem']);
    $router->post('/projects/kanban/checklist/{id}/delete', [\App\Controllers\ProjectKanbanController::class, 'deleteChecklistItem']);
    $router->post('/projects/kanban/add-tag', [\App\Controllers\ProjectKanbanController::class, 'addTag']);
    $router->post('/projects/kanban/tag/{id}/delete', [\App\Controllers\ProjectKanbanController::class, 'deleteTag']);
    $router->post('/projects/kanban/timer/start', [\App\Controllers\ProjectKanbanController::class, 'startTimer']);
    $router->post('/projects/kanban/timer/stop', [\App\Controllers\ProjectKanbanController::class, 'stopTimer']);
    $router->post('/projects/kanban/timer/pause-all', [\App\Controllers\ProjectKanbanController::class, 'pauseAllTimers']);
    $router->get('/projects/kanban/timer/status', [\App\Controllers\ProjectKanbanController::class, 'getTimerStatus']);
    $router->get('/projects/kanban/timer/active', [\App\Controllers\ProjectKanbanController::class, 'getActiveTimers']);
    
    // Módulo Financeiro
    $router->get('/financial', [FinancialController::class, 'index']);
    $router->get('/financial/create', [FinancialController::class, 'create']);
    $router->post('/financial', [FinancialController::class, 'store']);
    $router->post('/financial/bulk-delete', [FinancialController::class, 'bulkDelete']); // Deve vir antes das rotas com {id}
    $router->post('/financial/bulk-mark-paid', [FinancialController::class, 'bulkMarkAsPaid']); // Marcar como pago/recebido em massa
    $router->post('/financial/bulk-unmark-paid', [FinancialController::class, 'bulkUnmarkAsPaid']); // Desmarcar como pago/recebido em massa
    
    // Contas Bancárias (rotas específicas antes das genéricas)
    $router->get('/financial/bank-accounts', [FinancialController::class, 'bankAccounts']);
    $router->get('/financial/bank-accounts/create', [FinancialController::class, 'createBankAccount']);
    $router->post('/financial/bank-accounts', [FinancialController::class, 'storeBankAccount']);
    
    // Cartões de Crédito (rotas específicas antes das genéricas)
    $router->get('/financial/credit-cards', [FinancialController::class, 'creditCards']);
    $router->get('/financial/credit-cards/create', [FinancialController::class, 'createCreditCard']);
    $router->post('/financial/credit-cards', [FinancialController::class, 'storeCreditCard']);
    
    // Fornecedores (rotas específicas antes das genéricas)
    $router->get('/financial/suppliers', [FinancialController::class, 'suppliers']);
    $router->get('/financial/suppliers/create', [FinancialController::class, 'createSupplier']);
    $router->post('/financial/suppliers', [FinancialController::class, 'storeSupplier']);
    $router->get('/financial/suppliers/{id}/edit', [FinancialController::class, 'editSupplier']);
    $router->post('/financial/suppliers/{id}', [FinancialController::class, 'updateSupplier']);
    $router->post('/financial/suppliers/{id}/delete', [FinancialController::class, 'deleteSupplier']);
    
    // Formas de Pagamento (rotas específicas antes das genéricas)
    $router->get('/financial/payment-methods', [PaymentMethodController::class, 'index']);
    $router->get('/financial/payment-methods/create', [PaymentMethodController::class, 'create']);
    $router->post('/financial/payment-methods', [PaymentMethodController::class, 'store']);
    $router->get('/financial/payment-methods/{id}/edit', [PaymentMethodController::class, 'edit']);
    $router->post('/financial/payment-methods/{id}', [PaymentMethodController::class, 'update']);
    $router->post('/financial/payment-methods/{id}/delete', [PaymentMethodController::class, 'destroy']);
    
    // Categorias - DEVEM VIR ANTES das rotas genéricas com {id}
    $router->get('/financial/categories', [FinancialController::class, 'categories']);
    $router->get('/financial/categories/create', [FinancialController::class, 'createCategory']);
    $router->post('/financial/categories', [FinancialController::class, 'storeCategory']);
    // Rotas de subcategorias DEVEM VIR ANTES das rotas com {id} para evitar conflito
    $router->post('/financial/categories/subcategories', [FinancialController::class, 'storeSubcategory']);
    $router->post('/financial/categories/subcategories/update', [FinancialController::class, 'updateSubcategory']);
    $router->post('/financial/categories/subcategories/delete', [FinancialController::class, 'deleteSubcategory']);
    $router->get('/financial/categories/subcategories/info', [FinancialController::class, 'getSubcategoryInfo']);
    // Rotas com {id} vêm DEPOIS das rotas específicas
    $router->get('/financial/categories/{id}/edit', [FinancialController::class, 'editCategory']);
    $router->post('/financial/categories/{id}', [FinancialController::class, 'updateCategory']);
    $router->post('/financial/categories/{id}/delete', [FinancialController::class, 'deleteCategory']);
    
    // Centros de Custo - DEVEM VIR ANTES das rotas genéricas com {id}
    $router->get('/financial/cost-centers', [FinancialController::class, 'costCenters']);
    $router->get('/financial/cost-centers/create', [FinancialController::class, 'createCostCenter']);
    $router->post('/financial/cost-centers', [FinancialController::class, 'storeCostCenter']);
    $router->post('/financial/cost-centers/subcenters', [FinancialController::class, 'storeSubCostCenter']);
    
    // Rotas genéricas com {id} devem vir DEPOIS das rotas específicas
    $router->get('/financial/{id}/edit', [FinancialController::class, 'edit']);
    $router->post('/financial/{id}', [FinancialController::class, 'update']);
    $router->post('/financial/{id}/delete', [FinancialController::class, 'delete']);
    $router->post('/financial/{id}/mark-paid', [FinancialController::class, 'markAsPaid']);
    $router->post('/financial/{id}/unmark-paid', [FinancialController::class, 'unmarkAsPaid']);
    
    // Notificações
    $router->get('/api/notificacoes', [NotificacaoController::class, 'index']);
    $router->post('/api/notificacoes/{id}/marcar-lida', [NotificacaoController::class, 'marcarLida']);
    $router->post('/api/notificacoes/marcar-todas-lidas', [NotificacaoController::class, 'marcarTodasLidas']);
    
    // Agenda/Calendário
    $router->get('/calendar', [CalendarController::class, 'index']);
    $router->get('/calendar/events', [CalendarController::class, 'getEvents']);
    $router->post('/calendar/store', [CalendarController::class, 'store']);
    $router->post('/calendar/{id}/update', [CalendarController::class, 'update']);
    $router->post('/calendar/{id}/delete', [CalendarController::class, 'destroy']);
    
    // Contratos
    $router->get('/contracts', [ContractController::class, 'index']);
    $router->get('/contracts/create', [ContractController::class, 'create']);
    $router->post('/contracts', [ContractController::class, 'store']);
    
    // Templates de Contratos (rotas específicas antes das genéricas com {id})
    $router->get('/contracts/templates', [ContractTemplateController::class, 'index']);
    $router->get('/contracts/templates/create', [ContractTemplateController::class, 'create']);
    $router->post('/contracts/templates', [ContractTemplateController::class, 'store']);
    $router->get('/contracts/templates/{id}/edit', [ContractTemplateController::class, 'edit']);
    $router->post('/contracts/templates/{id}', [ContractTemplateController::class, 'update']);
    $router->post('/contracts/templates/{id}/delete', [ContractTemplateController::class, 'destroy']);
    
    // Rotas de contratos com {id} (devem vir depois das rotas específicas)
    $router->get('/contracts/{id}', [ContractController::class, 'show']);
    $router->get('/contracts/{id}/edit', [ContractController::class, 'edit']);
    $router->post('/contracts/{id}', [ContractController::class, 'update']);
    $router->post('/contracts/{id}/delete', [ContractController::class, 'destroy']);
    $router->post('/contracts/{id}/add-service', [ContractController::class, 'addService']);
    $router->post('/contracts/{id}/add-condition', [ContractController::class, 'addCondition']);
    $router->post('/contracts/{id}/setup-signatures', [ContractController::class, 'setupSignatures']);
    $router->post('/contracts/{id}/send-for-signature', [ContractController::class, 'sendForSignature']);
    $router->get('/contracts/{id}/pdf', [ContractController::class, 'generatePdf']);
    
    // Automações
    $router->get('/automations', [AutomationController::class, 'index']);
    $router->get('/automations/builder', [AutomationController::class, 'builder']);
    $router->get('/automations/builder/{id}', [AutomationController::class, 'edit']);
    $router->post('/automations', [AutomationController::class, 'store']);
    $router->post('/automations/{id}', [AutomationController::class, 'update']);
    $router->post('/automations/{id}/delete', [AutomationController::class, 'destroy']);
    $router->get('/automations/{id}/executions', [AutomationController::class, 'executions']);
    $router->get('/api/automations/components', [AutomationController::class, 'getComponents']);
    $router->get('/api/tags', [AutomationController::class, 'getTags']);
    $router->get('/api/users', [AutomationController::class, 'getUsers']);
    $router->get('/api/lead-origins', [AutomationController::class, 'getLeadOrigins']);
    $router->get('/api/automations/automations', [AutomationController::class, 'getAutomations']);
});

