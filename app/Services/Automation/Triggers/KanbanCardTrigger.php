<?php

declare(strict_types=1);

namespace App\Services\Automation\Triggers;

use App\Services\Automation\BaseTrigger;

class KanbanCardTrigger extends BaseTrigger
{
    public function __construct()
    {
        parent::__construct(
            'kanban_card',
            'Kanban CRM',
            'Dispara quando um card do Kanban CRM é movido ou criado'
        );
    }
    
    public function getConfigSchema(): array
    {
        return [
            [
                'name' => 'event_type',
                'label' => 'Tipo de Evento',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => 'created', 'label' => 'Criado'],
                    ['value' => 'moved', 'label' => 'Movido'],
                    ['value' => 'updated', 'label' => 'Atualizado']
                ]
            ],
            [
                'name' => 'stage',
                'label' => 'Estágio Específico',
                'type' => 'select',
                'required' => false,
                'options' => [
                    ['value' => 'interessado', 'label' => 'Interessado'],
                    ['value' => 'proposta', 'label' => 'Negociação e Proposta'],
                    ['value' => 'fechamento', 'label' => 'Fechamento']
                ]
            ]
        ];
    }
    
    public function check($data = null): ?array
    {
        if (!$data || !isset($data['lead_id'])) {
            return null;
        }
        
        $config = $this->getConfig();
        
        // Verifica tipo de evento
        if (isset($config['event_type']) && $config['event_type'] !== ($data['event_type'] ?? 'created')) {
            return null;
        }
        
        // Verifica estágio (se configurado)
        if (isset($config['stage']) && !empty($config['stage']) && $config['stage'] !== ($data['stage'] ?? null)) {
            return null;
        }
        
        return $data;
    }
}

