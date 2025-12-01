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
        $logs = $this->execution_log ?? [];
        $logs[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $message,
            'data' => $data
        ];
        
        $this->update(['execution_log' => $logs]);
    }
}

