<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class ProposalTestimonial extends Model
{
    protected string $table = 'proposal_testimonials';
    protected bool $multiTenant = false; // Relacionado via proposal_id
    
    protected array $fillable = [
        'proposal_id', 'client_name', 'company', 'testimonial', 'photo_url', 'order'
    ];
    
    protected array $casts = [
        'order' => 'integer'
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

