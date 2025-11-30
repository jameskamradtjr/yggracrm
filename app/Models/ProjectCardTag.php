<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class ProjectCardTag extends Model
{
    protected string $table = 'project_card_tags';
    protected bool $multiTenant = false; // Não precisa, pois já filtra pelo card_id
    
    protected array $fillable = [
        'card_id', 'nome', 'cor'
    ];
    
    /**
     * Relacionamento com card
     */
    public function card()
    {
        if (!$this->card_id) {
            return null;
        }
        return ProjectCard::find($this->card_id);
    }
}

