<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class ProposalCondition extends Model
{
    protected string $table = 'proposal_conditions';
    protected bool $multiTenant = false; // Relacionado via proposal_id
    
    protected array $fillable = [
        'proposal_id', 'titulo', 'descricao', 'ordem', 
        'tipo', 'valor_original', 'valor_final', 'parcelas', 'valor_parcela', 'is_selected'
    ];
    
    protected array $casts = [
        'ordem' => 'integer',
        'valor_original' => 'float',
        'valor_final' => 'float',
        'valor_parcela' => 'float',
        'parcelas' => 'integer',
        'is_selected' => 'boolean'
    ];
    
    /**
     * Verifica se é uma forma de pagamento
     */
    public function isPaymentForm(): bool
    {
        return isset($this->tipo) && $this->tipo === 'pagamento';
    }
    
    /**
     * Relacionamento com proposta
     */
    public function proposal(): ?Proposal
    {
        if (!$this->proposal_id) {
            return null;
        }
        return Proposal::find($this->proposal_id);
    }
}

