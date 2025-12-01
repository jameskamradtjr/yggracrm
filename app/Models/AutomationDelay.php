<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class AutomationDelay extends Model
{
    protected string $table = 'automation_delays';
    protected bool $multiTenant = false; // Não precisa de multi-tenancy pois já tem automation_id
    
    protected array $fillable = [
        'automation_id', 'execution_id', 'node_id', 'trigger_data', 'execute_at', 'status'
    ];
    
    protected array $casts = [
        'trigger_data' => 'json'
    ];
    
    /**
     * Relacionamento com automação
     */
    public function automation()
    {
        return $this->belongsTo(Automation::class, 'automation_id');
    }
    
    /**
     * Relacionamento com execução
     */
    public function execution()
    {
        return $this->belongsTo(AutomationExecution::class, 'execution_id');
    }
    
    /**
     * Marca o delay como processado
     */
    public function markAsProcessed(): void
    {
        $this->update(['status' => 'processed']);
    }
    
    /**
     * Marca o delay como cancelado
     */
    public function markAsCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}

