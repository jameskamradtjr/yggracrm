<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class AutomationExecution extends Model
{
    protected string $table = 'automation_executions';
    protected bool $multiTenant = false; // Relacionado via automation_id
    
    protected array $fillable = [
        'automation_id', 'status', 'trigger_data', 'execution_log', 
        'executed_nodes', 'error_message', 'started_at', 'completed_at'
    ];
    
    protected array $casts = [
        'trigger_data' => 'array',
        'execution_log' => 'array'
    ];
    
    /**
     * Converte trigger_data para JSON ao salvar
     */
    public function setTriggerDataAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['trigger_data'] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $this->attributes['trigger_data'] = $value;
        }
    }
    
    /**
     * Converte trigger_data de JSON ao ler
     */
    public function getTriggerDataAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        return is_array($value) ? $value : [];
    }
    
    /**
     * Converte execution_log para JSON ao salvar
     */
    public function setExecutionLogAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['execution_log'] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $this->attributes['execution_log'] = $value;
        }
    }
    
    /**
     * Converte execution_log de JSON ao ler
     */
    public function getExecutionLogAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        return is_array($value) ? $value : [];
    }
    
    /**
     * Relacionamento com automação
     */
    public function automation()
    {
        if (!$this->automation_id) {
            return null;
        }
        return Automation::find($this->automation_id);
    }
    
    /**
     * Marca como concluída
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Marca como falha
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'completed_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Adiciona log de execução
     */
    public function addLog(string $message, array $data = []): void
    {
        // Garante que execution_log é um array
        $logs = $this->execution_log ?? [];
        if (!is_array($logs)) {
            $logs = [];
        }
        
        $logs[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $message,
            'data' => $data
        ];
        
        // Atualiza diretamente o atributo (o setter vai converter para JSON)
        $this->attributes['execution_log'] = $logs;
        $this->update(['execution_log' => $logs]);
    }
    
    /**
     * Override do método save para garantir que arrays sejam convertidos para JSON
     */
    public function save(): bool
    {
        // Se trigger_data é array, converte para JSON
        if (isset($this->attributes['trigger_data']) && is_array($this->attributes['trigger_data'])) {
            $this->attributes['trigger_data'] = json_encode($this->attributes['trigger_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        
        // Se execution_log é array, converte para JSON
        if (isset($this->attributes['execution_log']) && is_array($this->attributes['execution_log'])) {
            $this->attributes['execution_log'] = json_encode($this->attributes['execution_log'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        
        return parent::save();
    }
}

