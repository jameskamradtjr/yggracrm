<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class UserWorkingHours extends Model
{
    protected string $table = 'user_working_hours';
    protected bool $multiTenant = false; // Já tem user_id
    
    protected array $fillable = [
        'user_id',
        'day_of_week',
        'start_time_morning',
        'end_time_morning',
        'start_time_afternoon',
        'end_time_afternoon',
        'is_available'
    ];
    
    protected array $casts = [
        'is_available' => 'boolean'
    ];
    
    /**
     * Retorna o usuário
     */
    public function user(): ?User
    {
        return User::find($this->user_id);
    }
    
    /**
     * Retorna os períodos disponíveis do dia
     */
    public function getAvailablePeriods(): array
    {
        $periods = [];
        
        // Verifica período da manhã
        $startMorning = trim($this->start_time_morning ?? '');
        $endMorning = trim($this->end_time_morning ?? '');
        if (!empty($startMorning) && !empty($endMorning) && $startMorning !== '00:00:00' && $endMorning !== '00:00:00') {
            $periods[] = [
                'start' => $startMorning,
                'end' => $endMorning,
                'type' => 'morning'
            ];
        }
        
        // Verifica período da tarde
        $startAfternoon = trim($this->start_time_afternoon ?? '');
        $endAfternoon = trim($this->end_time_afternoon ?? '');
        if (!empty($startAfternoon) && !empty($endAfternoon) && $startAfternoon !== '00:00:00' && $endAfternoon !== '00:00:00') {
            $periods[] = [
                'start' => $startAfternoon,
                'end' => $endAfternoon,
                'type' => 'afternoon'
            ];
        }
        
        return $periods;
    }
}

