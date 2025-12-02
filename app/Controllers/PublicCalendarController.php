<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\UserCalendarSettings;
use App\Models\UserWorkingHours;
use App\Models\PublicAppointment;
use App\Models\Client;
use App\Models\Lead;
use App\Models\CalendarEvent;
use App\Models\SistemaLog;

class PublicCalendarController extends Controller
{
    /**
     * Exibe a agenda pública para agendamento
     */
    public function show(array $params): string
    {
        $slug = $params['slug'] ?? '';
        $settings = UserCalendarSettings::where('calendar_slug', $slug)
            ->where('public_calendar_enabled', true)
            ->first();
        
        if (!$settings) {
            return $this->view('errors/404', [
                'title' => 'Agenda não encontrada'
            ]);
        }
        
        $user = $settings->user();
        if (!$user) {
            return $this->view('errors/404', [
                'title' => 'Agenda não encontrada'
            ]);
        }
        
        // Busca horários de trabalho
        $workingHours = UserWorkingHours::where('user_id', $user->id)
            ->where('is_available', true)
            ->orderByRaw("FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')")
            ->get();
        
        // Busca agendamentos existentes para os próximos dias
        $existingAppointments = PublicAppointment::where('user_id', $user->id)
            ->whereNot('status', 'cancelled')
            ->where('appointment_date', '>=', date('Y-m-d H:i:s'))
            ->orderBy('appointment_date', 'ASC')
            ->get();
        
        return $this->view('calendar/public', [
            'title' => $settings->calendar_title ?: 'Agendar Reunião',
            'settings' => $settings,
            'user' => $user,
            'workingHours' => $workingHours,
            'existingAppointments' => $existingAppointments
        ]);
    }
    
    /**
     * Processa agendamento público
     */
    public function book(array $params = []): void
    {
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Token de segurança inválido.']);
            return;
        }
        
        // Tenta pegar o slug da rota primeiro, depois do input
        $slug = $params['slug'] ?? $this->request->input('calendar_slug');
        
        if (!$slug) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
            return;
        }
        
        $settings = UserCalendarSettings::where('calendar_slug', $slug)
            ->where('public_calendar_enabled', true)
            ->first();
        
        if (!$settings) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Agenda não encontrada.']);
            return;
        }
        
        // Validação manual para retornar JSON em vez de redirect
        $input = $this->request->all();
        $errors = [];
        
        if (empty($input['name'])) {
            $errors['name'] = ['O campo nome é obrigatório.'];
        }
        
        if (empty($input['email'])) {
            $errors['email'] = ['O campo email é obrigatório.'];
        } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = ['O campo email deve ser um email válido.'];
        }
        
        if (empty($input['appointment_date'])) {
            $errors['appointment_date'] = ['O campo data é obrigatório.'];
        } elseif (!strtotime($input['appointment_date'])) {
            $errors['appointment_date'] = ['O campo data deve ser uma data válida.'];
        }
        
        if (empty($input['appointment_time'])) {
            $errors['appointment_time'] = ['O campo horário é obrigatório.'];
        }
        
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Erros de validação.',
                'errors' => $errors
            ]);
            return;
        }
        
        $data = [
            'name' => $input['name'],
            'email' => $input['email'],
            'phone' => $input['phone'] ?? null,
            'notes' => $input['notes'] ?? null,
            'appointment_date' => $input['appointment_date'],
            'appointment_time' => $input['appointment_time']
        ];
        
        try {
            // Combina data e hora
            $appointmentDateTime = $data['appointment_date'] . ' ' . $data['appointment_time'] . ':00';
            $appointmentTimestamp = strtotime($appointmentDateTime);
            
            if ($appointmentTimestamp === false) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Data/hora inválida.']);
                return;
            }
            
            // Validações de data/hora
            $now = time();
            $minTime = $now + ($settings->same_day_booking_hours * 3600);
            $maxTime = $now + ($settings->advance_booking_days * 86400);
            
            if ($appointmentTimestamp < $minTime) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Agendamento deve ser com pelo menos ' . $settings->same_day_booking_hours . ' horas de antecedência.']);
                return;
            }
            
            if ($appointmentTimestamp > $maxTime) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Agendamento não pode ser mais de ' . $settings->advance_booking_days . ' dias no futuro.']);
                return;
            }
            
            // Verifica se está dentro dos horários de trabalho
            $dayOfWeek = strtolower(date('l', $appointmentTimestamp));
            $dayMap = [
                'monday' => 'monday',
                'tuesday' => 'tuesday',
                'wednesday' => 'wednesday',
                'thursday' => 'thursday',
                'friday' => 'friday',
                'saturday' => 'saturday',
                'sunday' => 'sunday'
            ];
            
            $dayKey = $dayMap[$dayOfWeek] ?? null;
            if ($dayKey) {
                $workingHour = UserWorkingHours::where('user_id', $settings->user_id)
                    ->where('day_of_week', $dayKey)
                    ->where('is_available', true)
                    ->first();
                
                if (!$workingHour) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Horário não disponível neste dia.']);
                    return;
                }
                
                $appointmentTime = date('H:i:s', $appointmentTimestamp);
                $isValidTime = false;
                
                if ($workingHour->start_time_morning && $workingHour->end_time_morning) {
                    if ($appointmentTime >= $workingHour->start_time_morning && $appointmentTime <= $workingHour->end_time_morning) {
                        $isValidTime = true;
                    }
                }
                
                if (!$isValidTime && $workingHour->start_time_afternoon && $workingHour->end_time_afternoon) {
                    if ($appointmentTime >= $workingHour->start_time_afternoon && $appointmentTime <= $workingHour->end_time_afternoon) {
                        $isValidTime = true;
                    }
                }
                
                if (!$isValidTime) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Horário fora do período de trabalho.']);
                    return;
                }
            }
            
            // Verifica conflitos com outros agendamentos
            $endTime = date('Y-m-d H:i:s', $appointmentTimestamp + (($settings->appointment_duration ?? 30) * 60));
            
            // Query SQL direta para verificar conflitos
            // Conflito ocorre se:
            // 1. O novo agendamento começa durante um agendamento existente
            // 2. O novo agendamento termina durante um agendamento existente
            // 3. O novo agendamento engloba completamente um agendamento existente
            $db = \Core\Database::getInstance();
            $conflictSql = "
                SELECT * FROM `public_appointments` 
                WHERE `user_id` = ? 
                AND `status` != 'cancelled'
                AND (
                    (`appointment_date` >= ? AND `appointment_date` < ?)
                    OR (DATE_ADD(`appointment_date`, INTERVAL `duration` MINUTE) > ? AND DATE_ADD(`appointment_date`, INTERVAL `duration` MINUTE) <= ?)
                    OR (`appointment_date` <= ? AND DATE_ADD(`appointment_date`, INTERVAL `duration` MINUTE) >= ?)
                )
                LIMIT 1
            ";
            
            $conflictParams = [
                $settings->user_id,
                $appointmentDateTime, // início do novo agendamento
                $endTime, // fim do novo agendamento
                $appointmentDateTime, // início do novo agendamento
                $endTime, // fim do novo agendamento
                $appointmentDateTime, // início do novo agendamento
                $endTime // fim do novo agendamento
            ];
            
            $conflict = $db->queryOne($conflictSql, $conflictParams);
            
            if ($conflict) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Este horário já está ocupado. Por favor, escolha outro.']);
                return;
            }
            
            // Verifica se já é cliente
            $client = Client::where('email', $data['email'])
                ->where('user_id', $settings->user_id)
                ->first();
            
            // Cria agendamento
            $appointmentData = [
                'user_id' => $settings->user_id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'notes' => $data['notes'] ?? null,
                'appointment_date' => $appointmentDateTime,
                'duration' => $settings->appointment_duration ?? 30,
                'status' => 'pending',
                'confirmation_token' => PublicAppointment::generateConfirmationToken(),
                'client_id' => $client ? $client->id : null
            ];
            
            error_log("Tentando criar agendamento com dados: " . json_encode($appointmentData));
            
            try {
                $appointment = PublicAppointment::create($appointmentData);
                
                if (!$appointment || !isset($appointment->id)) {
                    throw new \Exception('Falha ao criar agendamento: objeto não retornado corretamente.');
                }
                
                error_log("Agendamento criado com sucesso. ID: " . $appointment->id);
            } catch (\Exception $createError) {
                error_log("Erro ao criar PublicAppointment: " . $createError->getMessage());
                error_log("Stack trace: " . $createError->getTraceAsString());
                throw $createError;
            }
            
            // Cria lead se não for cliente (antes de criar o evento de calendário)
            $leadId = null;
            if (!$client) {
                try {
                    // Cria instância do Lead e preenche manualmente para evitar problemas com multiTenant
                    $lead = new Lead();
                    $lead->fill([
                        'user_id' => $settings->user_id,
                        'nome' => $data['name'],
                        'email' => $data['email'],
                        'telefone' => $data['phone'] ?? null,
                        'origem' => 'agenda_publica',
                        'observacoes' => 'Agendamento criado via agenda pública: ' . ($data['notes'] ?? ''),
                        'etapa_funil' => 'interessados'
                    ]);
                    
                    error_log("Tentando criar lead com dados: " . json_encode($lead->toArray()));
                    
                    $saved = $lead->save();
                    
                    if ($saved && isset($lead->id)) {
                        $appointment->update(['lead_id' => $lead->id]);
                        $leadId = $lead->id;
                        error_log("Lead criado com sucesso. ID: " . $lead->id);
                    } else {
                        error_log("Aviso: Lead não foi criado corretamente. Saved: " . ($saved ? 'true' : 'false'));
                    }
                } catch (\Exception $leadError) {
                    error_log("Erro ao criar Lead (não crítico): " . $leadError->getMessage());
                    error_log("Stack trace: " . $leadError->getTraceAsString());
                    // Não interrompe o fluxo se falhar ao criar lead
                }
            }
            
            // Cria evento na agenda (calendar_events) para aparecer no calendário
            try {
                $eventEnd = date('Y-m-d H:i:s', $appointmentTimestamp + (($settings->appointment_duration ?? 30) * 60));
                
                $calendarEventData = [
                    'user_id' => $settings->user_id,
                    'titulo' => 'Agendamento: ' . $data['name'],
                    'descricao' => 'Agendamento público via ' . ($settings->calendar_title ?: 'Agenda Pública') . "\n" .
                                   'Email: ' . $data['email'] . "\n" .
                                   ($data['phone'] ? 'Telefone: ' . $data['phone'] . "\n" : '') .
                                   ($data['notes'] ? 'Observações: ' . $data['notes'] : ''),
                    'data_inicio' => $appointmentDateTime,
                    'data_fim' => $eventEnd,
                    'cor' => 'info',
                    'dia_inteiro' => 0,
                    'localizacao' => null,
                    'observacoes' => 'Agendamento público - ID: ' . $appointment->id,
                    'client_id' => $client ? $client->id : null,
                    'lead_id' => $leadId,
                    'project_id' => null
                ];
                
                // Cria instância do CalendarEvent e preenche manualmente
                $calendarEvent = new CalendarEvent();
                $calendarEvent->fill($calendarEventData);
                $calendarEventSaved = $calendarEvent->save();
                
                if ($calendarEventSaved && isset($calendarEvent->id)) {
                    error_log("Evento de calendário criado com sucesso. ID: " . $calendarEvent->id);
                } else {
                    error_log("Aviso: CalendarEvent não foi criado corretamente.");
                }
            } catch (\Exception $eventError) {
                error_log("Erro ao criar CalendarEvent (não crítico): " . $eventError->getMessage());
                error_log("Stack trace: " . $eventError->getTraceAsString());
                // Não interrompe o fluxo se falhar ao criar evento
            }
            
            try {
                SistemaLog::registrar('public_appointments', 'CREATE', $appointment->id, "Agendamento público criado: {$data['name']}");
            } catch (\Exception $logError) {
                // Ignora erros de log, não é crítico
                error_log("Erro ao registrar log: " . $logError->getMessage());
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Agendamento realizado com sucesso!',
                'appointment_id' => $appointment->id
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao criar agendamento público: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            error_log("File: " . $e->getFile() . " Line: " . $e->getLine());
            
            // Em desenvolvimento, retorna o erro completo
            $errorMessage = 'Erro ao processar agendamento. Tente novamente.';
            if (config('app.debug', false)) {
                $errorMessage = $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine();
            }
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $errorMessage,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        } catch (\Error $e) {
            error_log("Erro fatal ao criar agendamento público: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            error_log("File: " . $e->getFile() . " Line: " . $e->getLine());
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro fatal ao processar agendamento.',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }
    
    /**
     * Retorna horários disponíveis para uma data
     */
    public function getAvailableTimes(array $params = []): void
    {
        // Tenta pegar o slug da rota primeiro, depois da query string
        $slug = $params['slug'] ?? $this->request->query('calendar_slug');
        $date = $this->request->query('date');
        
        if (!$slug || !$date) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
            return;
        }
        
        $settings = UserCalendarSettings::where('calendar_slug', $slug)
            ->where('public_calendar_enabled', true)
            ->first();
        
        if (!$settings) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Agenda não encontrada.']);
            return;
        }
        
        $dayOfWeek = strtolower(date('l', strtotime($date)));
        $dayMap = [
            'monday' => 'monday',
            'tuesday' => 'tuesday',
            'wednesday' => 'wednesday',
            'thursday' => 'thursday',
            'friday' => 'friday',
            'saturday' => 'saturday',
            'sunday' => 'sunday'
        ];
        
        $dayKey = $dayMap[$dayOfWeek] ?? null;
        if (!$dayKey) {
            echo json_encode(['success' => true, 'times' => []]);
            return;
        }
        
        $workingHour = UserWorkingHours::where('user_id', $settings->user_id)
            ->where('day_of_week', $dayKey)
            ->where('is_available', true)
            ->first();
        
        if (!$workingHour) {
            echo json_encode(['success' => true, 'times' => []]);
            return;
        }
        
        // Busca agendamentos existentes
        $existingAppointments = PublicAppointment::where('user_id', $settings->user_id)
            ->whereNot('status', 'cancelled')
            ->whereDate('appointment_date', $date)
            ->get();
        
        // Gera slots disponíveis
        $availableTimes = [];
        $periods = $workingHour->getAvailablePeriods();
        
        // Debug: log dos períodos encontrados
        error_log("Períodos encontrados para {$dayKey}: " . json_encode($periods));
        error_log("Horários configurados - Manhã: {$workingHour->start_time_morning} - {$workingHour->end_time_morning}, Tarde: {$workingHour->start_time_afternoon} - {$workingHour->end_time_afternoon}");
        
        if (empty($periods)) {
            error_log("Nenhum período disponível encontrado para {$dayKey}");
            echo json_encode([
                'success' => true,
                'times' => [],
                'debug' => 'Nenhum período configurado para este dia'
            ]);
            return;
        }
        
        $duration = ($settings->appointment_duration ?? 30) * 60; // em segundos, padrão 30 minutos
        $bufferBefore = ($settings->buffer_time_before ?? 0) * 60; // em segundos
        $bufferAfter = ($settings->buffer_time_after ?? 0) * 60; // em segundos
        
        foreach ($periods as $period) {
            $periodStart = strtotime($date . ' ' . $period['start']);
            $periodEnd = strtotime($date . ' ' . $period['end']);
            
            // Verifica se os horários são válidos
            if ($periodStart === false || $periodEnd === false || $periodStart >= $periodEnd) {
                error_log("Horário inválido: {$period['start']} - {$period['end']}");
                continue;
            }
            
            // Ajusta o início do período considerando o buffer antes
            $start = $periodStart + $bufferBefore;
            $end = $periodEnd;
            
            // Verifica se ainda há tempo suficiente após o buffer
            if ($start + $duration > $end) {
                continue;
            }
            
            $current = $start;
            while ($current + $duration <= $end) {
                $timeSlot = date('H:i', $current);
                $slotStart = $current;
                $slotEnd = $current + $duration;
                
                // Verifica conflitos com agendamentos existentes
                $hasConflict = false;
                foreach ($existingAppointments as $apt) {
                    $aptStart = strtotime($apt->appointment_date);
                    $aptEnd = $aptStart + (($apt->duration ?? $settings->appointment_duration) * 60);
                    
                    // Verifica sobreposição considerando buffers
                    // Conflito se: (slotStart < aptEnd + bufferAfter) && (slotEnd + bufferAfter > aptStart - bufferBefore)
                    $aptStartWithBuffer = $aptStart - $bufferBefore;
                    $aptEndWithBuffer = $aptEnd + $bufferAfter;
                    
                    if (($slotStart < $aptEndWithBuffer) && ($slotEnd > $aptStartWithBuffer)) {
                        $hasConflict = true;
                        break;
                    }
                }
                
                // Verifica buffer de agendamento no mesmo dia (apenas se for hoje)
                $selectedDateStr = date('Y-m-d', strtotime($date));
                $todayStr = date('Y-m-d');
                if ($selectedDateStr === $todayStr) {
                    $now = time();
                    $minTime = $now + (($settings->same_day_booking_hours ?? 0) * 3600);
                    if ($slotStart < $minTime) {
                        $hasConflict = true;
                    }
                }
                
                if (!$hasConflict) {
                    $availableTimes[] = $timeSlot;
                }
                
                // Avança para o próximo slot: duração do agendamento + buffer após
                $current += $duration + $bufferAfter;
            }
        }
        
        error_log("Horários disponíveis gerados: " . count($availableTimes) . " slots");
        
        echo json_encode([
            'success' => true,
            'times' => $availableTimes
        ]);
    }
    
    /**
     * Retorna datas disponíveis para o calendário
     */
    public function getAvailableDates(array $params = []): void
    {
        // Tenta pegar o slug da rota primeiro, depois da query string
        $slug = $params['slug'] ?? $this->request->query('calendar_slug');
        
        if (!$slug) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
            return;
        }
        
        $settings = UserCalendarSettings::where('calendar_slug', $slug)
            ->where('public_calendar_enabled', true)
            ->first();
        
        if (!$settings) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Agenda não encontrada.']);
            return;
        }
        
        // Busca horários de trabalho
        $workingHours = UserWorkingHours::where('user_id', $settings->user_id)
            ->where('is_available', true)
            ->get();
        
        if (empty($workingHours)) {
            echo json_encode(['success' => true, 'dates' => []]);
            return;
        }
        
        // Gera lista de datas disponíveis
        $availableDates = [];
        $today = date('Y-m-d');
        $maxDate = date('Y-m-d', strtotime('+' . $settings->advance_booking_days . ' days'));
        
        // Mapeia dias da semana: date('w') retorna 0=domingo, 1=segunda, ..., 6=sábado
        $dayMap = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 0
        ];
        
        $availableDays = [];
        foreach ($workingHours as $wh) {
            $dayNum = $dayMap[$wh->day_of_week] ?? null;
            if ($dayNum !== null) {
                $availableDays[] = $dayNum;
            }
        }
        
        if (empty($availableDays)) {
            echo json_encode(['success' => true, 'dates' => []]);
            return;
        }
        
        $current = strtotime($today);
        $end = strtotime($maxDate);
        
        while ($current <= $end) {
            $dayOfWeek = (int)date('w', $current); // 0 = domingo, 1 = segunda, ..., 6 = sábado
            if (in_array($dayOfWeek, $availableDays)) {
                $availableDates[] = date('Y-m-d', $current);
            }
            $current = strtotime('+1 day', $current);
        }
        
        echo json_encode([
            'success' => true,
            'dates' => $availableDates
        ]);
    }
}

