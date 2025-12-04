<?php

declare(strict_types=1);

namespace App\Services\Automation\Triggers;

use App\Services\Automation\BaseTrigger;

class CalendarEventTrigger extends BaseTrigger
{
    public function __construct()
    {
        parent::__construct(
            'calendar_event',
            'Agendamento',
            'Dispara quando um evento do calendário é criado ou atualizado'
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
                    ['value' => 'updated', 'label' => 'Atualizado'],
                    ['value' => 'deleted', 'label' => 'Deletado']
                ]
            ]
        ];
    }
    
    public function check($data = null): ?array
    {
        if (!$data || !isset($data['event_id']) || !isset($data['event_type'])) {
            error_log("CalendarEventTrigger::check() - Dados inválidos ou incompletos: " . json_encode($data));
            return null;
        }
        
        $config = $this->getConfig();
        
        error_log("CalendarEventTrigger::check() - Verificando trigger. Config: " . json_encode($config) . ", Data: " . json_encode($data));
        
        // Verifica se é o tipo de evento correto
        if (isset($config['event_type']) && $config['event_type'] !== $data['event_type']) {
            error_log("CalendarEventTrigger::check() - Tipo de evento não corresponde. Config espera: '{$config['event_type']}', recebido: '{$data['event_type']}'");
            return null;
        }
        
        error_log("CalendarEventTrigger::check() - Trigger acionado com sucesso!");
        return $data;
    }
}

