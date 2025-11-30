<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class ContractService extends Model
{
    protected string $table = 'contract_services';
    protected bool $multiTenant = false; // Gerenciado pelo contract pai
    
    protected array $fillable = [
        'contract_id', 'descricao', 'detalhes', 'valor', 'quantidade', 'ordem'
    ];
    
    protected array $casts = [
        'valor' => 'float',
        'quantidade' => 'integer',
        'ordem' => 'integer'
    ];
    
    /**
     * Relacionamento com contrato
     */
    public function contract()
    {
        return Contract::find($this->contract_id);
    }
    
    /**
     * Calcula valor total do serviço
     */
    public function getValorTotal(): float
    {
        return (float)$this->valor * (int)$this->quantidade;
    }
}

