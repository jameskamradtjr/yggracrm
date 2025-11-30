<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class ProjectCardTimeTracking extends Model
{
    protected string $table = 'project_card_time_tracking';
    protected bool $multiTenant = false; // Relacionado via card_id
    
    protected array $fillable = [
        'card_id', 'user_id', 'inicio', 'fim', 'tempo_segundos', 'observacoes'
    ];
    
    protected array $casts = [
        'tempo_segundos' => 'integer'
    ];
    
    /**
     * Relacionamento com card
     */
    public function card(): ?ProjectCard
    {
        if (!$this->card_id) {
            return null;
        }
        return ProjectCard::find($this->card_id);
    }
    
    /**
     * Relacionamento com usuário
     */
    public function user(): ?\App\Models\User
    {
        if (!$this->user_id) {
            return null;
        }
        return \App\Models\User::find($this->user_id);
    }
    
    /**
     * Formata tempo em formato legível (HH:MM:SS)
     */
    public function formatarTempo(): string
    {
        $horas = floor($this->tempo_segundos / 3600);
        $minutos = floor(($this->tempo_segundos % 3600) / 60);
        $segundos = $this->tempo_segundos % 60;
        
        return sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos);
    }
    
    /**
     * Calcula tempo total trabalhado em um card
     */
    public static function tempoTotalCard(int $cardId): int
    {
        $db = \Core\Database::getInstance();
        $result = $db->queryOne(
            "SELECT COALESCE(SUM(tempo_segundos), 0) as total FROM project_card_time_tracking WHERE card_id = ?",
            [$cardId]
        );
        
        return (int)($result['total'] ?? 0);
    }
    
    /**
     * Formata segundos em formato legível
     */
    public static function formatarSegundos(int $segundos): string
    {
        $horas = floor($segundos / 3600);
        $minutos = floor(($segundos % 3600) / 60);
        $seg = $segundos % 60;
        
        if ($horas > 0) {
            return sprintf('%d:%02d:%02d', $horas, $minutos, $seg);
        }
        return sprintf('%d:%02d', $minutos, $seg);
    }
}

