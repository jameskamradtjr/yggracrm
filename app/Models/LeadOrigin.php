<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class LeadOrigin extends Model
{
    protected string $table = 'lead_origins';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'user_id',
        'nome',
        'descricao',
        'ativo',
        'ordem'
    ];
    
    protected array $casts = [
        'ativo' => 'boolean',
        'ordem' => 'integer'
    ];
    
    /**
     * Obtém todas as origens ativas do usuário
     */
    public static function getActiveOrigins(int $userId): array
    {
        return static::where('user_id', $userId)
            ->where('ativo', true)
            ->orderBy('ordem', 'ASC')
            ->orderBy('nome', 'ASC')
            ->get();
    }
}

