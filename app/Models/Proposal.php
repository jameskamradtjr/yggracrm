<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;
use App\Models\Lead;
use App\Models\Client;
use App\Models\Project;

class Proposal extends Model
{
    protected string $table = 'proposals';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'user_id', 'lead_id', 'client_id', 'project_id', 'numero_proposta',
        'titulo', 'identificacao', 'imagem_capa', 'video_youtube', 'objetivo', 'apresentacao',
        'descricao', 'subtotal', 'desconto_valor', 'desconto_percentual', 'total',
        'duracao_dias', 'data_estimada_conclusao', 'disponibilidade_inicio_imediato',
        'forma_pagamento', 'formas_pagamento_aceitas', 'valor',
        'status', 'data_envio', 'data_validade', 'data_visualizacao_cliente',
        'token_publico', 'observacoes'
    ];
    
    protected array $casts = [
        'valor' => 'float',
        'subtotal' => 'float',
        'desconto_valor' => 'float',
        'desconto_percentual' => 'float',
        'total' => 'float',
        'duracao_dias' => 'integer',
        'disponibilidade_inicio_imediato' => 'boolean',
        'formas_pagamento_aceitas' => 'json'
    ];
    
    /**
     * Gera número único da proposta (ex: P2025-02-1)
     */
    public function gerarNumeroProposta(): string
    {
        if ($this->numero_proposta) {
            return $this->numero_proposta;
        }
        
        $ano = date('Y');
        $mes = date('m');
        $userId = $this->user_id ?? auth()->getDataUserId();
        
        // Busca última proposta do mês para este usuário usando query direta
        $db = \Core\Database::getInstance();
        $pattern = "P{$ano}-{$mes}-%";
        $sql = "SELECT * FROM `proposals` WHERE `user_id` = ? AND `numero_proposta` LIKE ? ORDER BY `id` DESC LIMIT 1";
        $result = $db->queryOne($sql, [$userId, $pattern]);
        
        $sequencial = 1;
        if ($result && !empty($result['numero_proposta'])) {
            $parts = explode('-', $result['numero_proposta']);
            if (count($parts) === 3) {
                $sequencial = (int)$parts[2] + 1;
            }
        }
        
        $numero = "P{$ano}-{$mes}-{$sequencial}";
        $this->numero_proposta = $numero;
        return $numero;
    }
    
    /**
     * Gera token público para visualização
     */
    public function gerarTokenPublico(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->token_publico = $token;
        return $token;
    }
    
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
    
    /**
     * Relacionamento com projeto
     */
    public function project(): ?Project
    {
        if (!$this->project_id) {
            return null;
        }
        return Project::find($this->project_id);
    }
    
    /**
     * Relacionamento com serviços
     */
    public function services(): array
    {
        return ProposalService::where('proposal_id', $this->id)
            ->orderBy('ordem', 'ASC')
            ->get();
    }
    
    /**
     * Relacionamento com condições
     */
    public function conditions(): array
    {
        return ProposalCondition::where('proposal_id', $this->id)
            ->orderBy('ordem', 'ASC')
            ->get();
    }
    
    /**
     * Calcula totais baseado nos serviços
     */
    public function calcularTotais(): void
    {
        $services = $this->services();
        $subtotal = 0;
        
        foreach ($services as $service) {
            $subtotal += $service->valor_total;
        }
        
        $this->subtotal = $subtotal;
        
        // Aplica desconto
        if ($this->desconto_percentual > 0) {
            $this->desconto_valor = ($subtotal * $this->desconto_percentual) / 100;
        }
        
        $this->total = $subtotal - $this->desconto_valor;
        $this->valor = $this->total; // Mantém compatibilidade
    }
}
