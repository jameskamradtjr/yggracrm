<?php

declare(strict_types=1);

namespace App\Services\Automation\Actions;

use App\Services\Automation\BaseAction;

class DelayAction extends BaseAction
{
    public function __construct()
    {
        parent::__construct(
            'delay',
            'Aguardar Tempo',
            'Aguarda um período de tempo antes de continuar o workflow'
        );
    }
    
    public function getConfigSchema(): array
    {
        return [
            [
                'name' => 'delay_days',
                'label' => 'Dias',
                'type' => 'number',
                'required' => false,
                'placeholder' => 'Ex: 3',
                'min' => 0,
                'description' => 'Número de dias para aguardar'
            ],
            [
                'name' => 'delay_hours',
                'label' => 'Horas',
                'type' => 'number',
                'required' => false,
                'placeholder' => 'Ex: 12',
                'min' => 0,
                'description' => 'Número de horas para aguardar (adicional aos dias)'
            ],
            [
                'name' => 'delay_minutes',
                'label' => 'Minutos',
                'type' => 'number',
                'required' => false,
                'placeholder' => 'Ex: 30',
                'min' => 0,
                'description' => 'Número de minutos para aguardar (adicional)'
            ]
        ];
    }
    
    public function execute(array $triggerData, array $config): bool
    {
        // Calcula o delay total em segundos
        $delaySeconds = 0;
        $delaySeconds += (int)($config['delay_days'] ?? 0) * 86400; // dias
        $delaySeconds += (int)($config['delay_hours'] ?? 0) * 3600; // horas
        $delaySeconds += (int)($config['delay_minutes'] ?? 0) * 60; // minutos
        
        if ($delaySeconds <= 0) {
            return false; // Delay inválido
        }
        
        // Calcula a data/hora de execução
        $executeAt = date('Y-m-d H:i:s', time() + $delaySeconds);
        
        // Cria registro de delay agendado
        try {
            $automationId = $triggerData['automation_id'] ?? null;
            $executionId = $triggerData['execution_id'] ?? null;
            $nodeId = $triggerData['node_id'] ?? null;
            
            if (!$automationId || !$executionId || !$nodeId) {
                error_log("DelayAction: Dados insuficientes para agendar delay. automation_id: {$automationId}, execution_id: {$executionId}, node_id: {$nodeId}");
                return false;
            }
            
            \App\Models\AutomationDelay::create([
                'automation_id' => $automationId,
                'execution_id' => $executionId,
                'node_id' => $nodeId,
                'trigger_data' => $triggerData,
                'execute_at' => $executeAt,
                'status' => 'pending'
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao criar delay agendado: " . $e->getMessage());
            return false;
        }
    }
}

