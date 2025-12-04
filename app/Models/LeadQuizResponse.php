<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class LeadQuizResponse extends Model
{
    protected string $table = 'lead_quiz_responses';
    protected bool $multiTenant = false; // Não precisa de user_id pois já tem lead_id
    
    protected array $fillable = [
        'lead_id',
        'quiz_id',
        'quiz_step_id',
        'field_name',
        'response',
        'points'
    ];
    
    protected array $casts = [
        'points' => 'integer'
    ];
    
    /**
     * Retorna o lead relacionado
     */
    public function lead(): ?Lead
    {
        return Lead::find($this->lead_id);
    }
    
    /**
     * Retorna o quiz relacionado
     */
    public function quiz(): ?Quiz
    {
        return Quiz::find($this->quiz_id);
    }
    
    /**
     * Retorna o step relacionado
     */
    public function step(): ?QuizStep
    {
        if (!$this->quiz_step_id) {
            return null;
        }
        return QuizStep::find($this->quiz_step_id);
    }
}

