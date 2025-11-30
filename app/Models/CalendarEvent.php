<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class CalendarEvent extends Model
{
    protected string $table = 'calendar_events';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'user_id', 'titulo', 'descricao', 'data_inicio', 'data_fim',
        'cor', 'dia_inteiro', 'localizacao', 'observacoes',
        'client_id', 'lead_id', 'project_id'
    ];
    
    protected array $casts = [
        'dia_inteiro' => 'boolean'
    ];
    
    /**
     * Relacionamento com cliente
     */
    public function client()
    {
        if (!$this->client_id) {
            return null;
        }
        return Client::find($this->client_id);
    }
    
    /**
     * Relacionamento com lead
     */
    public function lead()
    {
        if (!$this->lead_id) {
            return null;
        }
        return Lead::find($this->lead_id);
    }
    
    /**
     * Relacionamento com projeto
     */
    public function project()
    {
        if (!$this->project_id) {
            return null;
        }
        return Project::find($this->project_id);
    }
    
    /**
     * Converte para formato FullCalendar
     */
    public function toFullCalendar(): array
    {
        // Converte data_inicio para formato ISO (Y-m-d\TH:i:s)
        $start = $this->data_inicio;
        if (strpos($start, ' ') !== false) {
            // Se está no formato MySQL (Y-m-d H:i:s), converte para ISO
            $start = str_replace(' ', 'T', $start);
        } elseif (strlen($start) === 10) {
            // Se for apenas data, adiciona hora
            $start = $start . 'T00:00:00';
        }
        
        $event = [
            'id' => (string)$this->id,
            'title' => $this->titulo,
            'start' => $start,
            'extendedProps' => [
                'calendar' => ucfirst($this->cor),
                'descricao' => $this->descricao ?? '',
                'localizacao' => $this->localizacao ?? '',
                'observacoes' => $this->observacoes ?? '',
                'client_id' => $this->client_id,
                'lead_id' => $this->lead_id,
                'project_id' => $this->project_id,
                'data_inicio_original' => $this->data_inicio, // Mantém formato original para referência
                'data_fim_original' => $this->data_fim ?? null
            ]
        ];
        
        if ($this->data_fim) {
            $end = $this->data_fim;
            if (strpos($end, ' ') !== false) {
                // Se está no formato MySQL (Y-m-d H:i:s), converte para ISO
                $end = str_replace(' ', 'T', $end);
            } elseif (strlen($end) === 10) {
                // Se for apenas data, adiciona hora
                $end = $end . 'T23:59:59';
            }
            $event['end'] = $end;
        }
        
        if ($this->dia_inteiro) {
            $event['allDay'] = true;
            // Para eventos de dia inteiro, remove hora
            if (strpos($event['start'], 'T') !== false) {
                $event['start'] = substr($event['start'], 0, 10);
            }
            if (isset($event['end']) && strpos($event['end'], 'T') !== false) {
                $event['end'] = substr($event['end'], 0, 10);
            }
        }
        
        return $event;
    }
}

