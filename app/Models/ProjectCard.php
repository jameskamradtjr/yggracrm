<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class ProjectCard extends Model
{
    protected string $table = 'project_cards';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'project_id', 'titulo', 'descricao', 'coluna', 'prioridade',
        'responsible_user_id', 'data_prazo', 'ordem', 'user_id'
    ];
    
    protected array $casts = [
        'ordem' => 'integer'
    ];
    
    /**
     * Relacionamento com projeto
     */
    public function project()
    {
        if (!$this->project_id) {
            return null;
        }
        return Project::find($this->project_id);
    }
    
    /**
     * Relacionamento com responsável
     */
    public function responsible()
    {
        if (!$this->responsible_user_id) {
            return null;
        }
        return \App\Models\User::find($this->responsible_user_id);
    }
    
    /**
     * Relacionamento com checklists
     */
    public function checklists(): array
    {
        return ProjectCardChecklist::where('card_id', $this->id)
            ->orderBy('ordem', 'ASC')
            ->get();
    }
    
    /**
     * Relacionamento com tags
     */
    public function tags(): array
    {
        return ProjectCardTag::where('card_id', $this->id)->get();
    }
    
    /**
     * Calcula progresso do checklist
     */
    public function getChecklistProgress(): array
    {
        $checklists = $this->checklists();
        $total = count($checklists);
        $concluidos = 0;
        
        foreach ($checklists as $item) {
            if ($item->concluido) {
                $concluidos++;
            }
        }
        
        return [
            'total' => $total,
            'concluidos' => $concluidos,
            'percentual' => $total > 0 ? round(($concluidos / $total) * 100) : 0
        ];
    }
}

