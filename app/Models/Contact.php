<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;
use App\Models\Lead;
use App\Models\Client;

class Contact extends Model
{
    protected string $table = 'contacts';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'lead_id', 'client_id', 'tipo', 'assunto', 'descricao',
        'data_contato', 'hora_contato', 'resultado', 'observacoes', 'user_id'
    ];
    
    /**
     * Relacionamento com lead
     */
    public function lead(): ?Lead
    {
        if (!$this->lead_id) {
            return null;
        }
        return Lead::find($this->lead_id);
    }
    
    /**
     * Relacionamento com cliente
     */
    public function client(): ?Client
    {
        if (!$this->client_id) {
            return null;
        }
        return Client::find($this->client_id);
    }
}

