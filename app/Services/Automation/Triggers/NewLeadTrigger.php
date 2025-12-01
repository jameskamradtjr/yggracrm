<?php

declare(strict_types=1);

namespace App\Services\Automation\Triggers;

use App\Services\Automation\BaseTrigger;

class NewLeadTrigger extends BaseTrigger
{
    public function __construct()
    {
        parent::__construct(
            'new_lead',
            'Novo Lead',
            'Dispara quando um novo lead é criado'
        );
    }
    
    public function getConfigSchema(): array
    {
        return [
            [
                'name' => 'origin_id',
                'label' => 'Origem Específica',
                'type' => 'select',
                'required' => false,
                'loadOptions' => 'origins' // Carrega origens dinamicamente
            ]
        ];
    }
    
    public function check($data = null): ?array
    {
        if (!$data || !isset($data['lead_id'])) {
            return null;
        }
        
        $config = $this->getConfig();
        
        // Verifica se é a origem específica (se configurado)
        if (isset($config['origin_id']) && !empty($config['origin_id']) && $config['origin_id'] != ($data['origin_id'] ?? null)) {
            return null;
        }
        
        return $data;
    }
}

