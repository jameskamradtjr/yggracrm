<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class UserCalendarSettings extends Model
{
    protected string $table = 'user_calendar_settings';
    protected bool $multiTenant = false; // Já tem user_id
    
    protected array $fillable = [
        'user_id',
        'public_calendar_enabled',
        'calendar_slug',
        'calendar_title',
        'calendar_description',
        'appointment_duration',
        'buffer_time_before',
        'buffer_time_after',
        'advance_booking_days',
        'same_day_booking_hours',
        'timezone'
    ];
    
    protected array $casts = [
        'public_calendar_enabled' => 'boolean',
        'appointment_duration' => 'integer',
        'buffer_time_before' => 'integer',
        'buffer_time_after' => 'integer',
        'advance_booking_days' => 'integer',
        'same_day_booking_hours' => 'integer',
        'timezone' => 'array'
    ];
    
    /**
     * Retorna o usuário
     */
    public function user(): ?User
    {
        return User::find($this->user_id);
    }
    
    /**
     * Retorna os horários de trabalho
     */
    public function workingHours(): array
    {
        return UserWorkingHours::where('user_id', $this->user_id)
            ->orderByRaw("FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')")
            ->get();
    }
    
    /**
     * Gera um slug único para a agenda
     */
    public static function generateSlug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $baseSlug = $slug;
        $counter = 1;
        
        while (self::where('calendar_slug', $slug)->first()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}

