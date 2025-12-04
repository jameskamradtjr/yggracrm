<?php

declare(strict_types=1);

namespace App\Services\Automation;

use App\Models\Automation;
use App\Models\AutomationExecution;
use App\Models\AutomationDelay;
use App\Services\Automation\AutomationBootstrap;

/**
 * Engine de execução de automações
 */
class AutomationEngine
{
    /**
     * Executa uma automação quando um trigger é acionado
     */
    public function execute(Automation $automation, array $triggerData): void
    {
        // Garante que os componentes estão registrados antes de executar
        AutomationBootstrap::registerAll();
        
        if (!$automation->is_active) {
            return;
        }
        
        $workflow = $automation->getWorkflowData();
        $nodes = $workflow['nodes'] ?? [];
        $connections = $workflow['connections'] ?? [];
        
        if (empty($nodes)) {
            return;
        }
        
        // Cria registro de execução
        $execution = AutomationExecution::create([
            'automation_id' => $automation->id,
            'status' => 'running',
            'trigger_data' => $triggerData,
            'started_at' => date('Y-m-d H:i:s')
        ]);
        
        try {
            $execution->addLog("Iniciando execução da automação", [
                'automation_id' => $automation->id,
                'automation_name' => $automation->name,
                'trigger_data_keys' => array_keys($triggerData)
            ]);
            
            // Encontra o nó de trigger
            $triggerNode = $this->findTriggerNode($nodes);
            if (!$triggerNode) {
                $execution->addLog("Erro: Nó de trigger não encontrado no workflow", [
                    'nodes_count' => count($nodes),
                    'nodes_types' => array_map(function($n) { return $n['type'] ?? 'unknown'; }, $nodes)
                ]);
                throw new \Exception('Nó de trigger não encontrado');
            }
            
            $execution->addLog("Nó de trigger encontrado", [
                'trigger_type' => $triggerNode['type'] ?? 'unknown',
                'trigger_config' => $triggerNode['config'] ?? []
            ]);
            
            // Remove o prefixo 'trigger_' do type para obter o ID do trigger
            $triggerId = str_replace('trigger_', '', $triggerNode['type']);
            
            // Verifica se o trigger foi acionado
            $trigger = AutomationRegistry::getTrigger($triggerId);
            if (!$trigger) {
                // Lista triggers disponíveis para debug
                $allTriggers = AutomationRegistry::getAllTriggers();
                $availableTriggerIds = array_map(function($t) { return $t['id']; }, $allTriggers);
                $execution->addLog("Erro: Trigger não encontrado no registry", [
                    'trigger_id' => $triggerId,
                    'trigger_type_original' => $triggerNode['type'],
                    'available_triggers' => $availableTriggerIds
                ]);
                error_log("Trigger '{$triggerId}' não encontrado. Triggers disponíveis: " . implode(', ', $availableTriggerIds));
                throw new \Exception("Trigger '{$triggerId}' não encontrado (type original: '{$triggerNode['type']}')");
            }
            
            $execution->addLog("Trigger encontrado no registry", [
                'trigger_id' => $triggerId,
                'trigger_name' => $trigger->getName()
            ]);
            
            $trigger->setConfig($triggerNode['config'] ?? []);
            $execution->addLog("Verificando se trigger foi acionado", [
                'trigger_config' => $triggerNode['config'] ?? [],
                'trigger_data_received' => $triggerData
            ]);
            
            $triggerData = $trigger->check($triggerData);
            
            if (!$triggerData) {
                $execution->addLog("Trigger não foi acionado (check retornou null/false)", [
                    'trigger_id' => $triggerId,
                    'trigger_config' => $triggerNode['config'] ?? [],
                    'trigger_data_sent' => $triggerData
                ]);
                $execution->markAsCompleted();
                return;
            }
            
            $execution->addLog("Trigger acionado com sucesso", [
                'trigger_id' => $triggerId,
                'trigger_data_keys' => array_keys($triggerData)
            ]);
            
            // Executa o workflow
            $this->executeWorkflow($nodes, $connections, $triggerData, $execution);
            
            $execution->markAsCompleted();
            error_log("AutomationEngine::execute() - Automação {$automation->id} executada com sucesso");
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            error_log("AutomationEngine::execute() - ERRO ao executar automação {$automation->id}: " . $errorMsg);
            error_log("AutomationEngine::execute() - Stack trace: " . $e->getTraceAsString());
            
            // Adiciona log de erro na execução se possível
            try {
                if (isset($execution)) {
                    $execution->addLog("ERRO na execução da automação", [
                        'error' => $errorMsg,
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            } catch (\Exception $logError) {
                error_log("Erro ao adicionar log de erro na execução: " . $logError->getMessage());
            }
            
            $execution->markAsFailed($errorMsg);
        }
    }
    
    /**
     * Executa o workflow a partir do trigger
     */
    private function executeWorkflow(array $nodes, array $connections, array $triggerData, AutomationExecution $execution): void
    {
        $executedNodes = [];
        $queue = [$this->findTriggerNode($nodes)];
        
        while (!empty($queue)) {
            $currentNode = array_shift($queue);
            $nodeId = $currentNode['id'] ?? null;
            
            if (!$nodeId || in_array($nodeId, $executedNodes)) {
                continue;
            }
            
            $executedNodes[] = $nodeId;
            $execution->addLog("Executando nó: {$currentNode['type']}", ['node_id' => $nodeId]);
            
            // Processa o nó
            $result = $this->processNode($currentNode, $triggerData, $execution);
            
            if ($result === false) {
                continue; // Condição não satisfeita, não continua
            }
            
            // Se o resultado for 'delayed', pausa o workflow
            if ($result === 'delayed') {
                $execution->addLog("Workflow pausado devido a delay no nó '{$nodeId}'", ['node_id' => $nodeId]);
                break; // Para a execução do workflow
            }
            
            // Encontra nós conectados
            $nextNodes = $this->findConnectedNodes($nodeId, $connections, $nodes);
            $queue = array_merge($queue, $nextNodes);
        }
    }
    
    /**
     * Processa um nó do workflow
     */
    private function processNode(array $node, array $triggerData, AutomationExecution $execution)
    {
        $type = $node['type'] ?? '';
        $config = $node['config'] ?? [];
        
        // Processa condições
        if (strpos($type, 'condition_') === 0) {
            $conditionId = str_replace('condition_', '', $type);
            $condition = AutomationRegistry::getCondition($conditionId);
            
            if (!$condition) {
                $execution->addLog("Condição '{$conditionId}' não encontrada", ['node' => $node]);
                return false;
            }
            
            $condition->setConfig($config);
            $result = $condition->evaluate($triggerData, $config);
            
            $execution->addLog("Condição '{$conditionId}' avaliada: " . ($result ? 'verdadeira' : 'falsa'), [
                'node' => $node,
                'result' => $result
            ]);
            
            return $result;
        }
        
        // Processa ações
        if (strpos($type, 'action_') === 0) {
            $actionId = str_replace('action_', '', $type);
            $action = AutomationRegistry::getAction($actionId);
            
            if (!$action) {
                $execution->addLog("Ação '{$actionId}' não encontrada", ['node' => $node]);
                return false;
            }
            
            $action->setConfig($config);
            
            // Adiciona informações do contexto para ações que precisam (como DelayAction)
            $actionTriggerData = array_merge($triggerData, [
                'automation_id' => $execution->automation_id,
                'execution_id' => $execution->id,
                'node_id' => $node['id'] ?? null,
                '_execution' => $execution // Passa o objeto execution para ações adicionarem logs
            ]);
            
            $execution->addLog("Executando ação '{$actionId}'", [
                'node_id' => $node['id'] ?? null,
                'config' => $config
            ]);
            
            $result = $action->execute($actionTriggerData, $config);
            
            // Se for uma ação de delay, não continua o workflow imediatamente
            if ($actionId === 'delay' && $result === true) {
                $execution->addLog("Delay agendado para o nó '{$node['id']}'", [
                    'node' => $node,
                    'result' => 'agendado'
                ]);
                return 'delayed'; // Retorna 'delayed' para indicar que o workflow foi pausado
            }
            
            $execution->addLog("Ação '{$actionId}' executada: " . ($result ? 'sucesso' : 'falha'), [
                'node' => $node,
                'result' => $result
            ]);
            
            return $result;
        }
        
        return true;
    }
    
    /**
     * Encontra o nó de trigger
     */
    private function findTriggerNode(array $nodes): ?array
    {
        foreach ($nodes as $node) {
            if (isset($node['type']) && strpos($node['type'], 'trigger_') === 0) {
                return $node;
            }
        }
        return null;
    }
    
    /**
     * Encontra nós conectados a um nó
     */
    private function findConnectedNodes(string $nodeId, array $connections, array $nodes): array
    {
        $connected = [];
        
        foreach ($connections as $connection) {
            if (($connection['source'] ?? null) === $nodeId) {
                $targetId = $connection['target'] ?? null;
                if ($targetId) {
                    foreach ($nodes as $node) {
                        if (($node['id'] ?? null) === $targetId) {
                            $connected[] = $node;
                            break;
                        }
                    }
                }
            }
        }
        
        return $connected;
    }
    
    /**
     * Continua a execução de um workflow após um delay
     */
    public function continueAfterDelay(AutomationDelay $delay): void
    {
        // Garante que os componentes estão registrados antes de continuar
        AutomationBootstrap::registerAll();
        
        $automation = $delay->automation();
        if (!$automation || !$automation->is_active) {
            $delay->markAsCancelled();
            return;
        }
        
        $execution = $delay->execution();
        if (!$execution) {
            $delay->markAsCancelled();
            return;
        }
        
        $workflow = $automation->getWorkflowData();
        $nodes = $workflow['nodes'] ?? [];
        $connections = $workflow['connections'] ?? [];
        $triggerData = $delay->trigger_data ?? [];
        
        // Encontra o nó de delay
        $delayNode = null;
        foreach ($nodes as $node) {
            if (($node['id'] ?? null) === $delay->node_id) {
                $delayNode = $node;
                break;
            }
        }
        
        if (!$delayNode) {
            $delay->markAsCancelled();
            $execution->addLog("Nó de delay '{$delay->node_id}' não encontrado", ['delay_id' => $delay->id]);
            return;
        }
        
        // Marca o delay como processado
        $delay->markAsProcessed();
        
        // Continua o workflow a partir dos nós conectados ao delay
        $nextNodes = $this->findConnectedNodes($delay->node_id, $connections, $nodes);
        
        if (empty($nextNodes)) {
            $execution->addLog("Nenhum nó conectado após o delay '{$delay->node_id}'", ['delay_id' => $delay->id]);
            $execution->markAsCompleted();
            return;
        }
        
        // Continua a execução
        $executedNodes = json_decode($execution->executed_nodes ?? '[]', true) ?: [];
        $queue = $nextNodes;
        
        while (!empty($queue)) {
            $currentNode = array_shift($queue);
            $nodeId = $currentNode['id'] ?? null;
            
            if (!$nodeId || in_array($nodeId, $executedNodes)) {
                continue;
            }
            
            $executedNodes[] = $nodeId;
            $execution->addLog("Continuando workflow após delay: {$currentNode['type']}", ['node_id' => $nodeId]);
            
            // Processa o nó
            $result = $this->processNode($currentNode, $triggerData, $execution);
            
            if ($result === false) {
                continue; // Condição não satisfeita, não continua
            }
            
            // Se o resultado for 'delayed', pausa novamente
            if ($result === 'delayed') {
                $execution->addLog("Workflow pausado novamente devido a delay no nó '{$nodeId}'", ['node_id' => $nodeId]);
                break;
            }
            
            // Encontra nós conectados
            $nextNodes = $this->findConnectedNodes($nodeId, $connections, $nodes);
            $queue = array_merge($queue, $nextNodes);
        }
        
        // Atualiza nós executados
        $execution->update(['executed_nodes' => json_encode($executedNodes)]);
        
        // Se não há mais nós na fila e não foi pausado, marca como concluído
        if (empty($queue)) {
            $execution->markAsCompleted();
        }
    }
    
    /**
     * Processa delays agendados que já devem ser executados
     */
    public static function processScheduledDelays(): void
    {
        $now = date('Y-m-d H:i:s');
        
        $delays = AutomationDelay::where('status', 'pending')
            ->where('execute_at', '<=', $now)
            ->get();
        
        foreach ($delays as $delay) {
            try {
                $engine = new self();
                $engine->continueAfterDelay($delay);
            } catch (\Exception $e) {
                error_log("Erro ao processar delay {$delay->id}: " . $e->getMessage());
                $delay->markAsCancelled();
            }
        }
    }
}

