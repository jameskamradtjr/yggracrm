<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class ProjectCardChecklist extends Model
{
    protected string $table = 'project_card_checklists';
    protected bool $multiTenant = false; // Não precisa, pois já filtra pelo card_id
    
    protected array $fillable = [
        'card_id', 'item', 'concluido', 'ordem'
    ];
    
    protected array $casts = [
        'concluido' => 'boolean',
        'ordem' => 'integer'
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

