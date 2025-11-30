<?php

declare(strict_types=1);

namespace App\Services\Automation\Triggers;

use App\Services\Automation\BaseTrigger;

class ContractTrigger extends BaseTrigger
{
    public function __construct()
    {
        parent::__construct(
            'contract',
            'Contrato',
            'Dispara quando um contrato é criado ou assinado'
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
                    ['value' => 'signed', 'label' => 'Assinado'],
                    ['value' => 'expired', 'label' => 'Expirado']
                ]
            ]
        ];
    }
    
    public function check($data = null): ?array
    {
        if (!$data || !isset($data['contract_id'])) {
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

