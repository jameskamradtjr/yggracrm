<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class PublicAppointment extends Model
{
    protected string $table = 'public_appointments';
    protected bool $multiTenant = false; // Já tem user_id
    
    protected array $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'notes',
        'appointment_date',
        'duration',
        'status',
        'confirmation_token',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
        'client_id',
        'lead_id'
    ];
    
    protected array $casts = [
        'appointment_date' => 'datetime',
        'duration' => 'integer',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];
    
    /**
     * Retorna o usuário responsável
     */
    public function user(): ?User
    {
        return User::find($this->user_id);
    }
    
    /**
     * Retorna o cliente (se existir)
     */
    public function client(): ?Client
    {
        return $this->client_id ? Client::find($this->client_id) : null;
    }
    
    /**
     * Retorna o lead (se existir)
     */
    public function lead(): ?Lead
    {
        return $this->lead_id ? Lead::find($this->lead_id) : null;
    }
    
    /**
     * Gera token de confirmação
     */
    public static function generateConfirmationToken(): string
    {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Confirma o agendamento
     */
    public function confirm(): bool
    {
        return $this->update([
            'status' => 'confirmed',
            'confirmed_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Cancela o agendamento
     */
    public function cancel(?string $reason = null): bool
    {
        return $this->update([
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s'),
            'cancellation_reason' => $reason
        ]);
    }
}

