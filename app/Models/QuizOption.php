<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class QuizOption extends Model
{
    protected string $table = 'quiz_options';
    protected bool $multiTenant = false;
    
    protected array $fillable = [
        'quiz_step_id',
        'label',
        'value',
        'points',
        'order'
    ];
    
    protected array $casts = [
        'points' => 'integer',
        'order' => 'integer'
    ];
    
    /**
     * Retorna o step pai
     */
    public function step(): ?QuizStep
    {
        return QuizStep::find($this->quiz_step_id);
    }
}

