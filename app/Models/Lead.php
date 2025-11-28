<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Model Lead
 * 
 * Representa um lead capturado pelo quiz
 */
class Lead extends Model
{
    protected string $table = 'leads';
    
    // Habilita multi-tenancy para leads (filtra por user_id)
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'nome',
        'email',
        'telefone',
        'instagram',
        'ramo',
        'faturamento_raw',
        'faturamento_categoria',
        'invest_raw',
        'invest_categoria',
        'objetivo',
        'faz_trafego',
        'tags_ai',
        'score_potencial',
        'urgencia',
        'resumo',
        'status_kanban'
    ];

    protected array $casts = [
        'faz_trafego' => 'boolean',
        'tags_ai' => 'json',
        'score_potencial' => 'integer'
    ];

    /**
     * Retorna leads por status kanban
     */
    public static function byStatus(string $status): array
    {
        return static::where('status_kanban', $status)->orderBy('score_potencial', 'DESC')->get();
    }

    /**
     * Atualiza status kanban
     */
    public function updateStatus(string $status): bool
    {
        return $this->update(['status_kanban' => $status]);
    }

    /**
     * Retorna tags da IA como array
     */
    public function getTagsAi(): array
    {
        $value = $this->tags_ai ?? null;
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($value) ? $value : [];
    }
}

