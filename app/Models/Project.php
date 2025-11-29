<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Project extends Model
{
    protected string $table = 'projects';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'titulo', 'descricao', 'status', 'prioridade',
        'data_inicio', 'data_termino_prevista', 'data_termino_real',
        'client_id', 'lead_id', 'responsible_user_id',
        'orcamento', 'custo_real', 'progresso', 'observacoes', 'user_id'
    ];
    
    protected array $casts = [
        'orcamento' => 'float',
        'custo_real' => 'float',
        'progresso' => 'integer'
    ];
    
    /**
     * Busca cliente associado
     */
    public function client()
    {
        if (!$this->client_id) {
            return null;
        }
        return \App\Models\Client::find($this->client_id);
    }
    
    /**
     * Busca lead associado
     */
    public function lead()
    {
        if (!$this->lead_id) {
            return null;
        }
        return \App\Models\Lead::find($this->lead_id);
    }
    
    /**
     * Busca usuário responsável
     */
    public function responsible()
    {
        if (!$this->responsible_user_id) {
            return null;
        }
        return \App\Models\User::find($this->responsible_user_id);
    }
}

