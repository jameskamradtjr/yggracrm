<?php

declare(strict_types=1);

namespace App\Services\Automation\Actions;

use App\Services\Automation\BaseAction;

class AssignResponsibleAction extends BaseAction
{
    public function __construct()
    {
        parent::__construct(
            'assign_responsible',
            'Adicionar Responsável',
            'Atribui um responsável a um item'
        );
    }
    
    public function getConfigSchema(): array
    {
        return [
            [
                'name' => 'entity_type',
                'label' => 'Tipo de Entidade',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => 'lead', 'label' => 'Lead'],
                    ['value' => 'client', 'label' => 'Cliente'],
                    ['value' => 'project_card', 'label' => 'Card de Projeto'],
                    ['value' => 'calendar_event', 'label' => 'Evento do Calendário']
                ]
            ],
            [
                'name' => 'user_id',
                'label' => 'Usuário Responsável',
                'type' => 'select',
                'required' => true,
                'loadOptions' => 'users' // Carrega usuários dinamicamente
            ]
        ];
    }
    
    public function execute(array $triggerData, array $config): bool
    {
        if (!isset($config['entity_type']) || !isset($config['user_id'])) {
            return false;
        }
        
        $entityType = $config['entity_type'];
        $userId = (int)$config['user_id'];
        
        // Obtém ID da entidade do trigger
        $entityId = $this->getEntityId($entityType, $triggerData);
        if (!$entityId) {
            return false;
        }
        
        try {
            return $this->assignUser($entityType, $entityId, $userId);
        } catch (\Exception $e) {
            error_log("Erro ao atribuir responsável na automação: " . $e->getMessage());
            return false;
        }
    }
    
    private function getEntityId(string $entityType, array $triggerData): ?int
    {
        $fieldMap = [
            'lead' => 'lead_id',
            'client' => 'client_id',
            'project_card' => 'card_id',
            'calendar_event' => 'event_id'
        ];
        
        $field = $fieldMap[$entityType] ?? null;
        return $field ? ($triggerData[$field] ?? null) : null;
    }
    
    private function assignUser(string $entityType, int $entityId, int $userId): bool
    {
        $tableMap = [
            'lead' => 'leads',
            'client' => 'clients',
            'project_card' => 'project_cards',
            'calendar_event' => 'calendar_events'
        ];
        
        $table = $tableMap[$entityType] ?? null;
        if (!$table) {
            return false;
        }
        
        $db = \Core\Database::getInstance();
        $db->execute(
            "UPDATE `{$table}` SET `responsible_user_id` = ? WHERE `id` = ?",
            [$userId, $entityId]
        );
        
        return true;
    }
}

