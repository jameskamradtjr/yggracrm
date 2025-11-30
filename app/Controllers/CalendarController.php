<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\CalendarEvent;
use App\Models\SistemaLog;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Project;

class CalendarController extends Controller
{
    /**
     * Exibe a página do calendário
     */
    public function index(): string
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        // Busca eventos do usuário
        $events = CalendarEvent::where('user_id', auth()->getDataUserId())
            ->orderBy('data_inicio', 'ASC')
            ->get();

        // Busca clientes, leads e projetos para associar
        $clients = Client::where('user_id', auth()->getDataUserId())->get();
        $leads = Lead::where('user_id', auth()->getDataUserId())->get();
        $projects = Project::where('user_id', auth()->getDataUserId())->get();

        return $this->view('calendar/index', [
            'title' => 'Agenda',
            'events' => $events,
            'clients' => $clients,
            'leads' => $leads,
            'projects' => $projects
        ]);
    }

    /**
     * Retorna eventos em formato JSON para FullCalendar
     */
    public function getEvents(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!auth()->check()) {
            json_response(['error' => 'Não autenticado'], 401);
            return;
        }

        $start = $this->request->input('start');
        $end = $this->request->input('end');

        $query = CalendarEvent::where('user_id', auth()->getDataUserId());

        // Filtra eventos que se sobrepõem ao período solicitado
        // Busca eventos que começam antes do fim do período E (não têm fim OU terminam depois do início)
        if ($start) {
            $endDate = $end ?? '9999-12-31';
            // Eventos que começam antes ou no fim do período
            $query = $query->where('data_inicio', '<=', $endDate);
            // E que não têm data_fim OU terminam depois do início do período
            // Como o QueryBuilder não suporta closures, vamos buscar todos e filtrar depois
        }

        $events = $query->orderBy('data_inicio', 'ASC')->get();
        
        // Filtra eventos que realmente se sobrepõem ao período
        if ($start) {
            $events = array_filter($events, function($event) use ($start, $end) {
                $eventStart = $event->data_inicio;
                $eventEnd = $event->data_fim ?? $eventStart;
                $periodEnd = $end ?? '9999-12-31';
                
                // Evento se sobrepõe se: começa antes do fim do período E termina depois do início
                return $eventStart <= $periodEnd && $eventEnd >= $start;
            });
        }
        
        $fcEvents = [];
        foreach ($events as $event) {
            $fcEvents[] = $event->toFullCalendar();
        }

        json_response($fcEvents);
    }

    /**
     * Cria novo evento
     */
    public function store(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
            return;
        }

        $input = $this->request->all();

        $validator = new \Core\Validator($input, [
            'titulo' => 'required|min:3|max:255',
            'data_inicio' => 'required|date',
            'data_fim' => 'nullable|date',
            'cor' => 'nullable|in:danger,success,primary,warning',
            'descricao' => 'nullable',
            'localizacao' => 'nullable|max:255',
            'observacoes' => 'nullable',
            'dia_inteiro' => 'nullable|boolean',
            'client_id' => 'nullable|numeric',
            'lead_id' => 'nullable|numeric',
            'project_id' => 'nullable|numeric'
        ]);

        if (!$validator->passes()) {
            error_log("Erro de validação ao criar evento: " . print_r($validator->errors(), true));
            error_log("Dados recebidos: " . print_r($input, true));
            json_response([
                'success' => false,
                'message' => 'Dados inválidos. Verifique os campos obrigatórios.',
                'errors' => $validator->errors()
            ], 400);
            return;
        }

        try {
            $validatedData = $validator->validated();
            
            // Se não tem data_fim, usa data_inicio
            if (empty($validatedData['data_fim'])) {
                $validatedData['data_fim'] = $validatedData['data_inicio'];
            }
            
            // Converte datetime-local para formato MySQL (Y-m-d H:i:s)
            if (strpos($validatedData['data_inicio'], 'T') !== false) {
                $parts = explode('T', $validatedData['data_inicio']);
                if (count($parts) === 2) {
                    $time = $parts[1];
                    // Se não tem segundos, adiciona :00
                    if (strlen($time) === 5) {
                        $time .= ':00';
                    }
                    $validatedData['data_inicio'] = $parts[0] . ' ' . $time;
                }
            }
            if (!empty($validatedData['data_fim']) && strpos($validatedData['data_fim'], 'T') !== false) {
                $parts = explode('T', $validatedData['data_fim']);
                if (count($parts) === 2) {
                    $time = $parts[1];
                    // Se não tem segundos, adiciona :00
                    if (strlen($time) === 5) {
                        $time .= ':00';
                    }
                    $validatedData['data_fim'] = $parts[0] . ' ' . $time;
                }
            }

            // Trata dia_inteiro (checkbox não envia valor se não marcado)
            $diaInteiro = false;
            if (isset($input['dia_inteiro']) && ($input['dia_inteiro'] === '1' || $input['dia_inteiro'] === true || $input['dia_inteiro'] === 'on')) {
                $diaInteiro = true;
            }
            
            $event = CalendarEvent::create([
                'user_id' => auth()->getDataUserId(),
                'titulo' => $validatedData['titulo'],
                'descricao' => $validatedData['descricao'] ?? null,
                'data_inicio' => $validatedData['data_inicio'],
                'data_fim' => $validatedData['data_fim'],
                'cor' => $validatedData['cor'] ?? 'primary',
                'dia_inteiro' => $diaInteiro,
                'localizacao' => $validatedData['localizacao'] ?? null,
                'observacoes' => $validatedData['observacoes'] ?? null,
                'client_id' => !empty($validatedData['client_id']) ? (int)$validatedData['client_id'] : null,
                'lead_id' => !empty($validatedData['lead_id']) ? (int)$validatedData['lead_id'] : null,
                'project_id' => !empty($validatedData['project_id']) ? (int)$validatedData['project_id'] : null
            ]);

            SistemaLog::registrar(
                'calendar_events',
                'CREATE',
                $event->id,
                "Evento criado: {$event->titulo}",
                null,
                $event->toArray()
            );

            json_response([
                'success' => true,
                'message' => 'Evento criado com sucesso!',
                'event' => $event->toFullCalendar()
            ]);

        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao criar evento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza evento
     */
    public function update(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
            return;
        }

        $event = CalendarEvent::find($params['id']);

        if (!$event || $event->user_id !== auth()->getDataUserId()) {
            json_response(['success' => false, 'message' => 'Evento não encontrado'], 404);
            return;
        }

        $input = $this->request->all();

        $validator = new \Core\Validator($input, [
            'titulo' => 'required|min:3|max:255',
            'data_inicio' => 'required|date',
            'data_fim' => 'nullable|date',
            'cor' => 'nullable|in:danger,success,primary,warning',
            'descricao' => 'nullable',
            'localizacao' => 'nullable|max:255',
            'observacoes' => 'nullable',
            'dia_inteiro' => 'nullable|boolean',
            'client_id' => 'nullable|numeric',
            'lead_id' => 'nullable|numeric',
            'project_id' => 'nullable|numeric'
        ]);

        if (!$validator->passes()) {
            error_log("Erro de validação ao atualizar evento: " . print_r($validator->errors(), true));
            error_log("Dados recebidos: " . print_r($input, true));
            json_response([
                'success' => false,
                'message' => 'Dados inválidos. Verifique os campos obrigatórios.',
                'errors' => $validator->errors()
            ], 400);
            return;
        }

        try {
            $validatedData = $validator->validated();
            $oldData = $event->toArray();

            // Se não tem data_fim, usa data_inicio
            if (empty($validatedData['data_fim'])) {
                $validatedData['data_fim'] = $validatedData['data_inicio'];
            }
            
            // Converte datetime-local para formato MySQL (Y-m-d H:i:s)
            if (strpos($validatedData['data_inicio'], 'T') !== false) {
                $parts = explode('T', $validatedData['data_inicio']);
                if (count($parts) === 2) {
                    $time = $parts[1];
                    // Se não tem segundos, adiciona :00
                    if (strlen($time) === 5) {
                        $time .= ':00';
                    }
                    $validatedData['data_inicio'] = $parts[0] . ' ' . $time;
                }
            }
            if (!empty($validatedData['data_fim']) && strpos($validatedData['data_fim'], 'T') !== false) {
                $parts = explode('T', $validatedData['data_fim']);
                if (count($parts) === 2) {
                    $time = $parts[1];
                    // Se não tem segundos, adiciona :00
                    if (strlen($time) === 5) {
                        $time .= ':00';
                    }
                    $validatedData['data_fim'] = $parts[0] . ' ' . $time;
                }
            }

            // Trata dia_inteiro (checkbox não envia valor se não marcado)
            $diaInteiro = false;
            if (isset($input['dia_inteiro']) && ($input['dia_inteiro'] === '1' || $input['dia_inteiro'] === true || $input['dia_inteiro'] === 'on')) {
                $diaInteiro = true;
            }

            $event->update([
                'titulo' => $validatedData['titulo'],
                'descricao' => $validatedData['descricao'] ?? null,
                'data_inicio' => $validatedData['data_inicio'],
                'data_fim' => $validatedData['data_fim'],
                'cor' => $validatedData['cor'] ?? 'primary',
                'dia_inteiro' => $diaInteiro,
                'localizacao' => $validatedData['localizacao'] ?? null,
                'observacoes' => $validatedData['observacoes'] ?? null,
                'client_id' => !empty($validatedData['client_id']) ? (int)$validatedData['client_id'] : null,
                'lead_id' => !empty($validatedData['lead_id']) ? (int)$validatedData['lead_id'] : null,
                'project_id' => !empty($validatedData['project_id']) ? (int)$validatedData['project_id'] : null
            ]);

            SistemaLog::registrar(
                'calendar_events',
                'UPDATE',
                $event->id,
                "Evento atualizado: {$event->titulo}",
                $oldData,
                $event->toArray()
            );

            json_response([
                'success' => true,
                'message' => 'Evento atualizado com sucesso!',
                'event' => $event->toFullCalendar()
            ]);

        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao atualizar evento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deleta evento
     */
    public function destroy(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
            return;
        }

        $event = CalendarEvent::find($params['id']);

        if (!$event || $event->user_id !== auth()->getDataUserId()) {
            json_response(['success' => false, 'message' => 'Evento não encontrado'], 404);
            return;
        }

        try {
            $oldData = $event->toArray();
            $eventTitle = $event->titulo;
            $eventId = $event->id;

            $event->delete();

            SistemaLog::registrar(
                'calendar_events',
                'DELETE',
                $eventId,
                "Evento deletado: {$eventTitle}",
                $oldData,
                null
            );

            json_response([
                'success' => true,
                'message' => 'Evento deletado com sucesso!'
            ]);

        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao deletar evento: ' . $e->getMessage()
            ], 500);
        }
    }
}

