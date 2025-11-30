<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class ContractCondition extends Model
{
    protected string $table = 'contract_conditions';
    protected bool $multiTenant = false; // Gerenciado pelo contract pai
    
    protected array $fillable = [
        'contract_id', 'titulo', 'descricao', 'ordem'
    ];
    
    protected array $casts = [
        'ordem' => 'integer'
    ];
    
    /**
     * Relacionamento com contrato
     */
    public function contract()
    {
        return Contract::find($this->contract_id);
    }
}

