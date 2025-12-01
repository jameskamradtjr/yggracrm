<?php

declare(strict_types=1);

namespace App\Services\Automation\Actions;

use App\Services\Automation\BaseAction;
use App\Models\Automation;
use App\Services\Automation\AutomationEngine;

class TriggerAutomationAction extends BaseAction
{
    public function __construct()
    {
        parent::__construct(
            'trigger_automation',
            'Iniciar Outra Automação',
            'Inicia a execução de outra automação'
        );
    }
    
    public function getConfigSchema(): array
    {
        return [
            [
                'name' => 'automation_id',
                'label' => 'Automação',
                'type' => 'select',
                'required' => true,
                'options' => [], // Será preenchido dinamicamente
                'loadOptions' => 'automations' // Indica que as opções devem ser carregadas da API de automações
            ],
            [
                'name' => 'pass_trigger_data',
                'label' => 'Passar Dados do Trigger',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => 'yes', 'label' => 'Sim'],
                    ['value' => 'no', 'label' => 'Não']
                ],
                'description' => 'Se sim, os dados do trigger atual serão passados para a automação iniciada'
            ]
        ];
    }
    
    public function execute(array $triggerData, array $config): bool
    {
        if (!isset($config['automation_id'])) {
            return false;
        }
        
        $automationId = (int)$config['automation_id'];
        $passTriggerData = ($config['pass_trigger_data'] ?? 'yes') === 'yes';
        
        try {
            // Busca a automação
            $automation = Automation::find($automationId);
            
            if (!$automation) {
                error_log("Automação {$automationId} não encontrada");
                return false;
            }
            
            // Verifica se a automação está ativa
            if (!$automation->is_active) {
                error_log("Automação {$automationId} não está ativa");
                return false;
            }
            
            // Prepara dados do trigger para a nova automação
            $newTriggerData = $passTriggerData ? $triggerData : [];
            $newTriggerData['triggered_by_automation'] = true;
            $newTriggerData['parent_automation_id'] = $triggerData['automation_id'] ?? null;
            $newTriggerData['parent_execution_id'] = $triggerData['execution_id'] ?? null;
            
            // Inicia a execução da automação
            $engine = new AutomationEngine();
            $engine->execute($automation, $newTriggerData);
            
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao iniciar automação {$automationId}: " . $e->getMessage());
            return false;
        }
    }
}

