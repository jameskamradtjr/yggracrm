<?php

declare(strict_types=1);

namespace App\Services\Automation\Triggers;

use App\Services\Automation\BaseTrigger;

class FinancialEntryTrigger extends BaseTrigger
{
    public function __construct()
    {
        parent::__construct(
            'financial_entry',
            'Lançamento Financeiro',
            'Dispara quando um lançamento financeiro é criado'
        );
    }
    
    public function getConfigSchema(): array
    {
        return [
            [
                'name' => 'type',
                'label' => 'Tipo de Lançamento',
                'type' => 'select',
                'required' => false,
                'options' => [
                    ['value' => 'income', 'label' => 'Receita'],
                    ['value' => 'expense', 'label' => 'Despesa']
                ]
            ],
            [
                'name' => 'min_value',
                'label' => 'Valor Mínimo',
                'type' => 'number',
                'required' => false
            ]
        ];
    }
    
    public function check($data = null): ?array
    {
        if (!$data || !isset($data['entry_id'])) {
            return null;
        }
        
        $config = $this->getConfig();
        
        // Verifica tipo (se configurado)
        if (isset($config['type']) && !empty($config['type']) && $config['type'] !== ($data['type'] ?? null)) {
            return null;
        }
        
        // Verifica valor mínimo (se configurado)
        if (isset($config['min_value']) && !empty($config['min_value']) && ($data['valor'] ?? 0) < $config['min_value']) {
            return null;
        }
        
        return $data;
    }
}

