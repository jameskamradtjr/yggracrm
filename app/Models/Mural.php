<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Mural extends Model
{
    protected string $table = 'mural';
    
    protected array $fillable = [
        'user_id',
        'titulo',
        'descricao',
        'imagem_url',
        'link_url',
        'link_texto',
        'data_inicio',
        'data_fim',
        'is_ativo',
        'ordem'
    ];
    
    protected array $casts = [
        'is_ativo' => 'boolean',
        'ordem' => 'integer',
        'data_inicio' => 'date',
        'data_fim' => 'date'
    ];
    
    /**
     * Relacionamento com usuário
     */
    public function user()
    {
        return User::find($this->user_id);
    }
    
    /**
     * Verifica se o mural está ativo e dentro do período válido
     */
    public function isVisible(): bool
    {
        if (!$this->is_ativo) {
            return false;
        }
        
        $today = date('Y-m-d');
        
        if ($this->data_inicio && $this->data_inicio > $today) {
            return false;
        }
        
        if ($this->data_fim && $this->data_fim < $today) {
            return false;
        }
        
        return true;
    }
}

