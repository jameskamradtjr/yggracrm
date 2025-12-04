<?php

declare(strict_types=1);

namespace App\Services\Automation;

use App\Models\Automation;
use App\Services\Automation\AutomationEngine;

/**
 * Dispatcher de eventos para automações
 * 
 * Este serviço é responsável por verificar e executar automações
 * quando eventos ocorrem no sistema
 */
class AutomationEventDispatcher
{
    private AutomationEngine $engine;
    
    public function __construct()
    {
        $this->engine = new AutomationEngine();
    }
    
    /**
     * Dispara evento e executa automações relacionadas
     */
    public function dispatch(string $eventType, array $eventData, ?int $userId = null): void
    {
        if (!$userId) {
            $userId = auth()->getDataUserId() ?? null;
        }
        
        if (!$userId) {
            return;
        }
        
        // Busca automações ativas do usuário
        $automations = Automation::where('user_id', $userId)
            ->where('is_active', true)
            ->get();
        
        foreach ($automations as $automation) {
            $workflow = $automation->getWorkflowData();
            $nodes = $workflow['nodes'] ?? [];
            
            // Verifica se alguma automação tem trigger para este evento
            $triggerNode = $this->findTriggerForEvent($nodes, $eventType);
            
            if ($triggerNode) {
                // Extrai o tipo de evento simples (ex: "created" de "calendar.event.created")
                $simpleEventType = $eventData['event_type'] ?? null;
                if (!$simpleEventType && preg_match('/\.([^.]+)$/', $eventType, $matches)) {
                    $simpleEventType = $matches[1]; // Pega a última parte após o último ponto
                }
                
                // Prepara dados do trigger
                $triggerData = array_merge($eventData, [
                    'event_type' => $simpleEventType, // Usa o tipo simples (created, updated, etc)
                    'automation_id' => $automation->id
                ]);
                
                // Executa automação
                try {
                    error_log("AutomationEventDispatcher::dispatch() - Executando automação ID: {$automation->id} para evento: {$eventType} (tipo simples: {$simpleEventType})");
                    error_log("AutomationEventDispatcher::dispatch() - triggerData: " . json_encode($triggerData));
                    $this->engine->execute($automation, $triggerData);
                    error_log("AutomationEventDispatcher::dispatch() - Automação ID: {$automation->id} executada com sucesso");
                } catch (\Exception $e) {
                    error_log("AutomationEventDispatcher::dispatch() - ERRO ao executar automação {$automation->id} no evento {$eventType}: " . $e->getMessage());
                    error_log("AutomationEventDispatcher::dispatch() - Stack trace: " . $e->getTraceAsString());
                }
            }
        }
    }
    
    /**
     * Encontra nó de trigger que corresponde ao evento
     */
    private function findTriggerForEvent(array $nodes, string $eventType): ?array
    {
        foreach ($nodes as $node) {
            if (!isset($node['type']) || strpos($node['type'], 'trigger_') !== 0) {
                continue;
            }
            
            $triggerId = str_replace('trigger_', '', $node['type']);
            
            // Mapeia tipos de evento para triggers
            $eventTriggerMap = [
                'lead.created' => 'new_lead',
                'lead.updated' => 'new_lead',
                'tag.added' => 'tag_added',
                'calendar.event.created' => 'calendar_event',
                'calendar.event.updated' => 'calendar_event',
                'financial.entry.created' => 'financial_entry',
                'kanban.card.created' => 'kanban_card',
                'kanban.card.moved' => 'kanban_card',
                'proposal.created' => 'proposal',
                'proposal.sent' => 'proposal',
                'proposal.accepted' => 'proposal',
                'proposal.rejected' => 'proposal',
                'contract.created' => 'contract',
                'contract.signed' => 'contract'
            ];
            
            if (isset($eventTriggerMap[$eventType]) && $eventTriggerMap[$eventType] === $triggerId) {
                return $node;
            }
        }
        
        return null;
    }
    
    /**
     * Helper para disparar evento de novo lead
     */
    public static function onLeadCreated(int $leadId, ?int $userId = null): void
    {
        $dispatcher = new self();
        $dispatcher->dispatch('lead.created', [
            'lead_id' => $leadId,
            'event_type' => 'created'
        ], $userId);
    }
    
    /**
     * Helper para disparar evento de tag adicionada
     */
    public static function onTagAdded(int $tagId, string $entityType, int $entityId, ?int $userId = null): void
    {
        $dispatcher = new self();
        $dispatcher->dispatch('tag.added', [
            'tag_id' => $tagId,
            'entity_type' => $entityType,
            'entity_id' => $entityId
        ], $userId);
    }
    
    /**
     * Helper para disparar evento de calendário
     */
    public static function onCalendarEvent(string $eventType, int $eventId, ?int $userId = null): void
    {
        $dispatcher = new self();
        $dispatcher->dispatch("calendar.event.{$eventType}", [
            'event_id' => $eventId,
            'event_type' => $eventType
        ], $userId);
    }
    
    /**
     * Helper para disparar evento financeiro
     */
    public static function onFinancialEntryCreated(int $entryId, string $type, float $value, ?int $userId = null): void
    {
        $dispatcher = new self();
        $dispatcher->dispatch('financial.entry.created', [
            'entry_id' => $entryId,
            'type' => $type,
            'valor' => $value
        ], $userId);
    }
    
    /**
     * Helper para disparar evento de Kanban
     */
    public static function onKanbanCard(string $action, int $leadId, ?string $stage = null, ?int $userId = null): void
    {
        $dispatcher = new self();
        $dispatcher->dispatch("kanban.card.{$action}", [
            'lead_id' => $leadId,
            'stage' => $stage,
            'event_type' => $action
        ], $userId);
    }
    
    /**
     * Helper para disparar evento de proposta
     */
    public static function onProposal(string $eventType, int $proposalId, ?int $userId = null): void
    {
        $dispatcher = new self();
        $dispatcher->dispatch("proposal.{$eventType}", [
            'proposal_id' => $proposalId,
            'event_type' => $eventType
        ], $userId);
    }
    
    /**
     * Helper para disparar evento de contrato
     */
    public static function onContract(string $eventType, int $contractId, ?int $userId = null): void
    {
        $dispatcher = new self();
        $dispatcher->dispatch("contract.{$eventType}", [
            'contract_id' => $contractId,
            'event_type' => $eventType
        ], $userId);
    }
}

