<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class ProposalService extends Model
{
    protected string $table = 'proposal_services';
    protected bool $multiTenant = false; // Relacionado via proposal_id
    
    protected array $fillable = [
        'proposal_id', 'titulo', 'descricao', 'quantidade',
        'valor_unitario', 'valor_total', 'ordem'
    ];
    
    protected array $casts = [
        'quantidade' => 'integer',
        'valor_unitario' => 'float',
        'valor_total' => 'float',
        'ordem' => 'integer'
    ];
    
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
    
    /**
     * Calcula valor total
     */
    public function calcularTotal(): void
    {
        $this->valor_total = $this->quantidade * $this->valor_unitario;
    }
}

