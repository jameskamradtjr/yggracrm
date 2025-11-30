<?php

declare(strict_types=1);

namespace App\Services\Automation\Triggers;

use App\Services\Automation\BaseTrigger;

class ProposalTrigger extends BaseTrigger
{
    public function __construct()
    {
        parent::__construct(
            'proposal',
            'Proposta',
            'Dispara quando uma proposta é criada ou atualizada'
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
                    ['value' => 'created', 'label' => 'Criada'],
                    ['value' => 'sent', 'label' => 'Enviada'],
                    ['value' => 'accepted', 'label' => 'Aceita'],
                    ['value' => 'rejected', 'label' => 'Recusada']
                ]
            ]
        ];
    }
    
    public function check($data = null): ?array
    {
        if (!$data || !isset($data['proposal_id'])) {
            return null;
        }
        
        $config = $this->getConfig();
        
        // Verifica tipo de evento
        if (isset($config['event_type']) && $config['event_type'] !== ($data['event_type'] ?? 'created')) {
            return null;
        }
        
        return $data;
    }
}

