<?php

declare(strict_types=1);

namespace App\Services\Automation\Actions;

use App\Services\Automation\BaseAction;
use App\Models\Lead;

class MoveCardAction extends BaseAction
{
    public function __construct()
    {
        parent::__construct(
            'move_card',
            'Mover Card',
            'Move um card do Kanban para outro estágio'
        );
    }
    
    public function getConfigSchema(): array
    {
        return [
            [
                'name' => 'stage',
                'label' => 'Estágio de Destino',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => 'interessado', 'label' => 'Interessado'],
                    ['value' => 'proposta', 'label' => 'Negociação e Proposta'],
                    ['value' => 'fechamento', 'label' => 'Fechamento']
                ]
            ]
        ];
    }
    
    public function execute(array $triggerData, array $config): bool
    {
        if (!isset($config['stage']) || !isset($triggerData['lead_id'])) {
            return false;
        }
        
        $leadId = (int)$triggerData['lead_id'];
        $stage = $config['stage'];
        
        try {
            $lead = Lead::find($leadId);
            if (!$lead) {
                return false;
            }
            
            $lead->update(['status' => $stage]);
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao mover card na automação: " . $e->getMessage());
            return false;
        }
    }
}

