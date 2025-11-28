<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;
use App\Models\Lead;
use App\Models\Proposal;
use App\Models\Contact;

class Client extends Model
{
    protected string $table = 'clients';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'tipo', 'nome_razao_social', 'nome_fantasia', 'cpf_cnpj',
        'email', 'telefone', 'celular', 'instagram', 'endereco',
        'numero', 'complemento', 'bairro', 'cidade', 'estado', 'cep',
        'observacoes', 'score', 'user_id'
    ];
    
    protected array $casts = [
        'score' => 'integer'
    ];
    
    /**
     * Relacionamento com leads
     */
    public function leads(): array
    {
        return Lead::where('client_id', $this->id)->get();
    }
    
    /**
     * Relacionamento com propostas
     */
    public function proposals(): array
    {
        return Proposal::where('client_id', $this->id)->get();
    }
    
    /**
     * Relacionamento com contatos
     */
    public function contacts(): array
    {
        return Contact::where('client_id', $this->id)
            ->orderBy('data_contato', 'DESC')
            ->orderBy('hora_contato', 'DESC')
            ->get();
    }
}

