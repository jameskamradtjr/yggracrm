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
        
        // Obtém componentes disponíveis
        $components = AutomationRegistry::getAllComponents();
        
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
        
        // Obtém componentes disponíveis
        $components = AutomationRegistry::getAllComponents();
        
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
            $automation->delete();
            
            SistemaLog::registrar(
                'automations',
                'DELETE',
                $params['id'],
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
            $components = AutomationRegistry::getAllComponents();
            
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
}

