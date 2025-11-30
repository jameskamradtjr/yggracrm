<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class ProposalCondition extends Model
{
    protected string $table = 'proposal_conditions';
    protected bool $multiTenant = false; // Relacionado via proposal_id
    
    protected array $fillable = [
        'proposal_id', 'titulo', 'descricao', 'ordem'
    ];
    
    protected array $casts = [
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
}

