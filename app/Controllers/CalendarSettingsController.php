<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\UserCalendarSettings;
use App\Models\UserWorkingHours;
use App\Models\SistemaLog;

class CalendarSettingsController extends Controller
{
    /**
     * Salva ou atualiza configurações de agenda
     */
    public function updateSettings(): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->back();
            return;
        }

        $userId = auth()->getDataUserId();
        
        $data = $this->validate([
            'public_calendar_enabled' => 'nullable|boolean',
            'calendar_title' => 'nullable|string|max:255',
            'calendar_description' => 'nullable|string',
            'appointment_duration' => 'nullable|integer|min:15|max:480',
            'buffer_time_before' => 'nullable|integer|min:0|max:120',
            'buffer_time_after' => 'nullable|integer|min:0|max:120',
            'advance_booking_days' => 'nullable|integer|min:1|max:365',
            'same_day_booking_hours' => 'nullable|integer|min:0|max:24'
        ]);

        try {
            $settings = UserCalendarSettings::where('user_id', $userId)->first();
            
            $settingsData = [
                'public_calendar_enabled' => !empty($data['public_calendar_enabled']),
                'calendar_title' => $data['calendar_title'] ?? null,
                'calendar_description' => $data['calendar_description'] ?? null,
                'appointment_duration' => $data['appointment_duration'] ?? 30,
                'buffer_time_before' => $data['buffer_time_before'] ?? 0,
                'buffer_time_after' => $data['buffer_time_after'] ?? 0,
                'advance_booking_days' => $data['advance_booking_days'] ?? 30,
                'same_day_booking_hours' => $data['same_day_booking_hours'] ?? 2
            ];
            
            // Gera slug se não existir e agenda estiver habilitada
            if ($settingsData['public_calendar_enabled'] && !$settings) {
                $slugName = $settingsData['calendar_title'] ?: auth()->user()->name;
                $settingsData['calendar_slug'] = UserCalendarSettings::generateSlug($slugName);
            }
            
            if ($settings) {
                $settings->update($settingsData);
            } else {
                $settingsData['user_id'] = $userId;
                if (empty($settingsData['calendar_slug'])) {
                    $slugName = $settingsData['calendar_title'] ?: auth()->user()->name;
                    $settingsData['calendar_slug'] = UserCalendarSettings::generateSlug($slugName);
                }
                $settings = UserCalendarSettings::create($settingsData);
            }
            
            SistemaLog::registrar('calendar_settings', 'UPDATE', $userId, 'Configurações de agenda atualizadas');
            
            session()->flash('success', 'Configurações de agenda salvas com sucesso!');
        } catch (\Exception $e) {
            error_log("Erro ao salvar configurações de agenda: " . $e->getMessage());
            session()->flash('error', 'Erro ao salvar configurações. Tente novamente.');
        }
        
        $this->back();
    }
    
    /**
     * Salva horários de trabalho
     */
    public function updateWorkingHours(): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->back();
            return;
        }

        $userId = auth()->getDataUserId();
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        try {
            foreach ($days as $day) {
                $isAvailable = !empty($this->request->input("{$day}_available"));
                $startMorning = $this->request->input("{$day}_start_morning");
                $endMorning = $this->request->input("{$day}_end_morning");
                $startAfternoon = $this->request->input("{$day}_start_afternoon");
                $endAfternoon = $this->request->input("{$day}_end_afternoon");
                
                $workingHour = UserWorkingHours::where('user_id', $userId)
                    ->where('day_of_week', $day)
                    ->first();
                
                $data = [
                    'day_of_week' => $day,
                    'is_available' => $isAvailable,
                    'start_time_morning' => $isAvailable && $startMorning ? $startMorning : null,
                    'end_time_morning' => $isAvailable && $endMorning ? $endMorning : null,
                    'start_time_afternoon' => $isAvailable && $startAfternoon ? $startAfternoon : null,
                    'end_time_afternoon' => $isAvailable && $endAfternoon ? $endAfternoon : null
                ];
                
                if ($workingHour) {
                    $workingHour->update($data);
                } else {
                    $data['user_id'] = $userId;
                    UserWorkingHours::create($data);
                }
            }
            
            SistemaLog::registrar('calendar_settings', 'UPDATE', $userId, 'Horários de trabalho atualizados');
            
            session()->flash('success', 'Horários de trabalho salvos com sucesso!');
        } catch (\Exception $e) {
            error_log("Erro ao salvar horários de trabalho: " . $e->getMessage());
            session()->flash('error', 'Erro ao salvar horários. Tente novamente.');
        }
        
        $this->back();
    }
}

