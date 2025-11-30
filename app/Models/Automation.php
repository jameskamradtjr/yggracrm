<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Automation extends Model
{
    protected string $table = 'automations';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'user_id', 'name', 'description', 'is_active', 'workflow_data'
    ];
    
    protected array $casts = [
        'is_active' => 'boolean'
    ];
    
    /**
     * Converte workflow_data para JSON ao salvar
     */
    public function setWorkflowDataAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['workflow_data'] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $this->attributes['workflow_data'] = $value;
        }
    }
    
    /**
     * Converte workflow_data de JSON ao ler
     */
    public function getWorkflowDataAttribute($value)
    {
        if (empty($value)) {
            return ['nodes' => [], 'connections' => []];
        }
        
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return $decoded ?? ['nodes' => [], 'connections' => []];
        }
        
        return $value;
    }
    
    /**
     * Relacionamento com usuário
     */
    public function user()
    {
        if (!$this->user_id) {
            return null;
        }
        return User::find($this->user_id);
    }
    
    /**
     * Relacionamento com execuções
     */
    public function executions()
    {
        return AutomationExecution::where('automation_id', $this->id)
            ->orderBy('started_at', 'DESC')
            ->get();
    }
    
    /**
     * Obtém workflow data decodificado
     */
    public function getWorkflowData(): array
    {
        if (empty($this->workflow_data)) {
            return ['nodes' => [], 'connections' => []];
        }
        
        if (is_string($this->workflow_data)) {
            return json_decode($this->workflow_data, true) ?? ['nodes' => [], 'connections' => []];
        }
        
        return $this->workflow_data;
    }
    
    /**
     * Define workflow data
     */
    public function setWorkflowData(array $data): void
    {
        $this->attributes['workflow_data'] = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Override do método save para garantir que workflow_data seja salvo como JSON
     */
    public function save(): bool
    {
        // Se workflow_data é array, converte para JSON
        if (isset($this->attributes['workflow_data']) && is_array($this->attributes['workflow_data'])) {
            $this->attributes['workflow_data'] = json_encode($this->attributes['workflow_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        
        return parent::save();
    }
}

