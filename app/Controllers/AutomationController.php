<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\Automation;
use App\Models\AutomationExecution;
use App\Models\SistemaLog;
use App\Services\Automation\AutomationRegistry;
use App\Services\Automation\AutomationBootstrap;

class AutomationController extends Controller
{
    /**
     * Lista todas as automações
     */
    public function index(): string
    {
        if (!auth()->check()) {
            redirect('/login');
        }
        
        $automations = Automation::where('user_id', auth()->getDataUserId())
            ->orderBy('created_at', 'DESC')
            ->get();
        
        return $this->view('automations/index', [
            'title' => 'Automações',
            'automations' => $automations
        ]);
    }
    
    /**
     * Exibe o builder de automações
     */
    public function builder(): string
    {
        if (!auth()->check()) {
            redirect('/login');
        }
        
        // Registra componentes
        AutomationBootstrap::registerAll();
        
        // Obtém componentes disponíveis e converte para arrays
        $componentsRaw = AutomationRegistry::getAllComponents();
        $components = [
            'triggers' => array_values($componentsRaw['triggers'] ?? []),
            'conditions' => array_values($componentsRaw['conditions'] ?? []),
            'actions' => array_values($componentsRaw['actions'] ?? [])
        ];
        
        return $this->view('automations/builder', [
            'title' => 'Criar Automação',
            'components' => $components
        ]);
    }
    
    /**
     * Edita uma automação
     */
    public function edit(array $params): string
    {
        if (!auth()->check()) {
            redirect('/login');
        }
        
        $automation = Automation::where('id', $params['id'])
            ->where('user_id', auth()->getDataUserId())
            ->first();
        
        if (!$automation) {
            abort(404, 'Automação não encontrada');
        }
        
        // Registra componentes
        AutomationBootstrap::registerAll();
        
        // Obtém componentes disponíveis e converte para arrays
        $componentsRaw = AutomationRegistry::getAllComponents();
        $components = [
            'triggers' => array_values($componentsRaw['triggers'] ?? []),
            'conditions' => array_values($componentsRaw['conditions'] ?? []),
            'actions' => array_values($componentsRaw['actions'] ?? [])
        ];
        
        return $this->view('automations/builder', [
            'title' => 'Editar Automação',
            'automation' => $automation,
            'components' => $components
        ]);
    }
    
    /**
     * Salva uma automação
     */
    public function store(): void
    {
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) {
            $input = $this->request->all();
        }
        
        $validator = new \Core\Validator($input, [
            'name' => 'required|max:255'
        ]);
        
        if (!$validator->passes()) {
            json_response([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
            return;
        }
        
        try {
            // Prepara workflow_data
            $workflowData = $input['workflow_data'] ?? ['nodes' => [], 'connections' => []];
            if (is_string($workflowData)) {
                $workflowData = json_decode($workflowData, true) ?? ['nodes' => [], 'connections' => []];
            }
            
            $automation = new Automation();
            $automation->user_id = auth()->getDataUserId();
            $automation->name = $input['name'];
            $automation->description = $input['description'] ?? null;
            $automation->is_active = isset($input['is_active']) && $input['is_active'];
            $automation->setWorkflowData($workflowData);
            $automation->save();
            
            SistemaLog::registrar(
                'automations',
                'CREATE',
                $automation->id,
                "Automação '{$automation->name}' criada",
                null,
                ['name' => $automation->name]
            );
            
            json_response([
                'success' => true,
                'message' => 'Automação criada com sucesso!',
                'automation' => $automation->toArray()
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao criar automação: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao criar automação: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Atualiza uma automação
     */
    public function update(array $params): void
    {
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) {
            $input = $this->request->all();
        }
        
        $automation = Automation::where('id', $params['id'])
            ->where('user_id', auth()->getDataUserId())
            ->first();
        
        if (!$automation) {
            json_response(['success' => false, 'message' => 'Automação não encontrada'], 404);
            return;
        }
        
        $validator = new \Core\Validator($input, [
            'name' => 'required|max:255'
        ]);
        
        if (!$validator->passes()) {
            json_response([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
            return;
        }
        
        try {
            // Prepara workflow_data
            $workflowData = $input['workflow_data'] ?? ['nodes' => [], 'connections' => []];
            if (is_string($workflowData)) {
                $workflowData = json_decode($workflowData, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $workflowData = ['nodes' => [], 'connections' => []];
                }
            }
            
            if (!is_array($workflowData)) {
                $workflowData = ['nodes' => [], 'connections' => []];
            }
            
            $oldData = $automation->toArray();
            
            $automation->name = $input['name'];
            $automation->description = $input['description'] ?? null;
            $automation->is_active = isset($input['is_active']) && $input['is_active'];
            $automation->setWorkflowData($workflowData);
            $automation->save();
            
            SistemaLog::registrar(
                'automations',
                'UPDATE',
                $automation->id,
                "Automação '{$automation->name}' atualizada",
                $oldData,
                $automation->toArray()
            );
            
            json_response([
                'success' => true,
                'message' => 'Automação atualizada com sucesso!',
                'automation' => $automation->toArray()
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao atualizar automação: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao atualizar automação: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Deleta uma automação
     */
    public function destroy(array $params): void
    {
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        $automation = Automation::where('id', $params['id'])
            ->where('user_id', auth()->getDataUserId())
            ->first();
        
        if (!$automation) {
            json_response(['success' => false, 'message' => 'Automação não encontrada'], 404);
            return;
        }
        
        try {
            $name = $automation->name;
            $automationId = (int)$automation->id; // Salva o ID antes de deletar
            $automation->delete();
            
            SistemaLog::registrar(
                'automations',
                'DELETE',
                $automationId,
                "Automação '{$name}' deletada",
                ['name' => $name],
                null
            );
            
            json_response(['success' => true, 'message' => 'Automação deletada com sucesso!']);
        } catch (\Exception $e) {
            error_log("Erro ao deletar automação: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao deletar automação: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Obtém componentes disponíveis
     */
    public function getComponents(): void
    {
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        try {
            AutomationBootstrap::registerAll();
            $componentsRaw = AutomationRegistry::getAllComponents();
            
            // Converte objetos para arrays
            $components = [
                'triggers' => array_values($componentsRaw['triggers'] ?? []),
                'conditions' => array_values($componentsRaw['conditions'] ?? []),
                'actions' => array_values($componentsRaw['actions'] ?? [])
            ];
            
            json_response([
                'success' => true,
                'components' => $components
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao obter componentes: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            json_response([
                'success' => false,
                'message' => 'Erro ao carregar componentes: ' . $e->getMessage(),
                'components' => ['triggers' => [], 'conditions' => [], 'actions' => []]
            ], 500);
        }
    }
    
    /**
     * API: Obtém tags para selects dinâmicos
     */
    public function getTags(): void
    {
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        try {
            $userId = auth()->getDataUserId();
            $tags = \App\Models\Tag::where('user_id', $userId)
                ->orderBy('name', 'ASC')
                ->get();
            
            $tagsArray = array_map(function($tag) {
                return ['id' => $tag->id, 'name' => $tag->name];
            }, $tags);
            
            json_response([
                'success' => true,
                'tags' => $tagsArray
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao obter tags: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao carregar tags'], 500);
        }
    }
    
    /**
     * API: Obtém usuários para selects dinâmicos
     */
    public function getUsers(): void
    {
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        try {
            $users = \App\Models\User::where('status', 'active')
                ->orderBy('name', 'ASC')
                ->get();
            
            $usersArray = array_map(function($user) {
                return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email];
            }, $users);
            
            json_response([
                'success' => true,
                'users' => $usersArray
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao obter usuários: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao carregar usuários'], 500);
        }
    }
    
    /**
     * API: Obtém origens de leads para selects dinâmicos
     */
    public function getLeadOrigins(): void
    {
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        try {
            $userId = auth()->getDataUserId();
            $origins = \App\Models\LeadOrigin::where('user_id', $userId)
                ->orderBy('nome', 'ASC')
                ->get();
            
            $originsArray = array_map(function($origin) {
                return ['id' => $origin->id, 'nome' => $origin->nome];
            }, $origins);
            
            json_response([
                'success' => true,
                'origins' => $originsArray
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao obter origens: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao carregar origens'], 500);
        }
    }
    
    /**
     * API: Obtém templates de email para selects dinâmicos
     */
    public function getEmailTemplates(): void
    {
        // Garante que sempre retorna JSON, mesmo em caso de erro
        try {
            if (!auth()->check()) {
                json_response(['success' => false, 'message' => 'Não autenticado'], 401);
                return;
            }
            
            // Templates de email são globais (não multi-tenant)
            // Busca todos os templates usando query SQL direta para evitar problemas com QueryBuilder
            $db = \Core\Database::getInstance();
            $sql = "SELECT * FROM `email_templates` ORDER BY `name` ASC";
            $results = $db->query($sql);
            
            error_log("AutomationController::getEmailTemplates() - Templates encontrados: " . count($results));
            
            // Verifica se $results é array e não está vazio
            if (!is_array($results)) {
                $results = [];
            }
            
            $templatesArray = array_map(function($row) {
                return [
                    'slug' => $row['slug'] ?? '',
                    'name' => $row['name'] ?? ''
                ];
            }, $results);
            
            error_log("AutomationController::getEmailTemplates() - Templates processados: " . count($templatesArray));
            
            json_response([
                'success' => true,
                'email_templates' => $templatesArray
            ]);
        } catch (\Throwable $e) {
            // Captura qualquer erro (Exception, Error, etc)
            error_log("Erro ao obter templates de email: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Garante que retorna JSON mesmo em caso de erro
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false, 
                'message' => 'Erro ao carregar templates de email: ' . $e->getMessage(),
                'email_templates' => []
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
    
    /**
     * API: Obtém templates de WhatsApp para selects dinâmicos
     */
    public function getWhatsAppTemplates(): void
    {
        // Garante que sempre retorna JSON, mesmo em caso de erro
        try {
            if (!auth()->check()) {
                json_response(['success' => false, 'message' => 'Não autenticado'], 401);
                return;
            }
            
            // Templates de WhatsApp são globais (não multi-tenant)
            $templates = \App\Models\WhatsAppTemplate::where('is_active', 1)
                ->orderBy('name', 'ASC')
                ->get();
            
            // Verifica se $templates é array e não está vazio
            if (!is_array($templates)) {
                $templates = [];
            }
            
            $templatesArray = array_map(function($template) {
                return [
                    'slug' => $template->slug ?? '',
                    'name' => $template->name ?? ''
                ];
            }, $templates);
            
            json_response([
                'success' => true,
                'whatsapp_templates' => $templatesArray
            ]);
        } catch (\Throwable $e) {
            // Captura qualquer erro (Exception, Error, etc)
            error_log("Erro ao obter templates de WhatsApp: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Garante que retorna JSON mesmo em caso de erro
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false, 
                'message' => 'Erro ao carregar templates de WhatsApp: ' . $e->getMessage(),
                'whatsapp_templates' => []
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
    
    /**
     * Obtém histórico de execuções
     */
    public function executions(array $params): string
    {
        if (!auth()->check()) {
            redirect('/login');
        }
        
        $automation = Automation::where('id', $params['id'])
            ->where('user_id', auth()->getDataUserId())
            ->first();
        
        if (!$automation) {
            abort(404, 'Automação não encontrada');
        }
        
        $executions = AutomationExecution::where('automation_id', $automation->id)
            ->orderBy('started_at', 'DESC')
            ->limit(50)
            ->get();
        
        return $this->view('automations/executions', [
            'title' => 'Histórico de Execuções',
            'automation' => $automation,
            'executions' => $executions
        ]);
    }
    
    /**
     * API: Obtém detalhes de uma execução
     */
    public function getExecutionDetails(array $params): void
    {
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        $execution = AutomationExecution::find($params['execution_id']);
        
        if (!$execution) {
            json_response(['success' => false, 'message' => 'Execução não encontrada'], 404);
            return;
        }
        
        // Verifica se a automação pertence ao usuário
        $automation = $execution->automation();
        if (!$automation || $automation->user_id !== auth()->getDataUserId()) {
            json_response(['success' => false, 'message' => 'Acesso negado'], 403);
            return;
        }
        
        // Garante que execution_log é sempre um array
        $executionLog = $execution->execution_log ?? [];
        if (!is_array($executionLog)) {
            // Se for string JSON, tenta decodificar
            if (is_string($executionLog)) {
                $decoded = json_decode($executionLog, true);
                $executionLog = is_array($decoded) ? $decoded : [];
            } else {
                $executionLog = [];
            }
        }
        
        json_response([
            'success' => true,
            'execution' => [
                'id' => $execution->id,
                'status' => $execution->status,
                'started_at' => $execution->started_at,
                'completed_at' => $execution->completed_at,
                'error_message' => $execution->error_message,
                'trigger_data' => $execution->trigger_data ?? [],
                'execution_log' => $executionLog
            ]
        ]);
    }
}

