<?php

declare(strict_types=1);

namespace App\Services\Automation;

use App\Models\Automation;
use App\Models\AutomationExecution;

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
            // Encontra o nó de trigger
            $triggerNode = $this->findTriggerNode($nodes);
            if (!$triggerNode) {
                throw new \Exception('Nó de trigger não encontrado');
            }
            
            // Verifica se o trigger foi acionado
            $trigger = AutomationRegistry::getTrigger($triggerNode['type']);
            if (!$trigger) {
                throw new \Exception("Trigger '{$triggerNode['type']}' não encontrado");
            }
            
            $trigger->setConfig($triggerNode['config'] ?? []);
            $triggerData = $trigger->check($triggerData);
            
            if (!$triggerData) {
                $execution->markAsCompleted();
                return;
            }
            
            // Executa o workflow
            $this->executeWorkflow($nodes, $connections, $triggerData, $execution);
            
            $execution->markAsCompleted();
        } catch (\Exception $e) {
            $execution->markAsFailed($e->getMessage());
            error_log("Erro ao executar automação {$automation->id}: " . $e->getMessage());
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
            $result = $action->execute($triggerData, $config);
            
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
}

