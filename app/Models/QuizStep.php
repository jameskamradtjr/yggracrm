<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class QuizStep extends Model
{
    protected string $table = 'quiz_steps';
    protected bool $multiTenant = false; // Não precisa filtrar por user_id, já filtra por quiz_id
    
    protected array $fillable = [
        'quiz_id',
        'title',
        'description',
        'type',
        'required',
        'order',
        'points',
        'field_name'
    ];
    
    protected array $casts = [
        'required' => 'boolean',
        'order' => 'integer',
        'points' => 'integer'
    ];
    
    /**
     * Retorna as opções deste step
     */
    public function options(): array
    {
        try {
            $options = QuizOption::where('quiz_step_id', $this->id)
                ->orderBy('order', 'ASC')
                ->get();
            return is_array($options) ? $options : [];
        } catch (\Exception $e) {
            error_log("Erro ao buscar opções do step {$this->id}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Retorna o quiz pai
     */
    public function quiz(): ?Quiz
    {
        return Quiz::find($this->quiz_id);
    }
}

