<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Contract extends Model
{
    protected string $table = 'contracts';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'user_id', 'template_id', 'client_id', 'numero_contrato', 'titulo',
        'conteudo_gerado', 'status', 'data_inicio', 'data_termino', 
        'valor_total', 'observacoes', 'link_assinatura', 'token_assinatura',
        'data_envio', 'data_assinatura_completa', 'token_publico', 'data_visualizacao_cliente'
    ];
    
    protected array $casts = [
        'valor_total' => 'float',
        'data_inicio' => 'date',
        'data_termino' => 'date',
        'data_envio' => 'datetime',
        'data_assinatura_completa' => 'datetime'
    ];
    
    /**
     * Relacionamento com template
     */
    public function template()
    {
        if (!$this->template_id) {
            return null;
        }
        return ContractTemplate::find($this->template_id);
    }
    
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
     * Relacionamento com proposta (se o contrato foi gerado de uma proposta)
     */
    public function proposal()
    {
        // Busca proposta relacionada (assumindo que há uma relação)
        $db = \Core\Database::getInstance();
        $result = $db->queryOne(
            "SELECT * FROM proposals WHERE id = (SELECT proposal_id FROM contracts WHERE id = ? LIMIT 1)",
            [$this->id]
        );
        
        if ($result) {
            return Proposal::find($result['id']);
        }
        
        return null;
    }
    
    /**
     * Relacionamento com serviços
     */
    public function services(): array
    {
        return ContractService::where('contract_id', $this->id)
            ->orderBy('ordem', 'ASC')
            ->get();
    }
    
    /**
     * Relacionamento com condições
     */
    public function conditions(): array
    {
        return ContractCondition::where('contract_id', $this->id)
            ->orderBy('ordem', 'ASC')
            ->get();
    }
    
    /**
     * Relacionamento com assinaturas
     */
    public function signatures(): array
    {
        return ContractSignature::where('contract_id', $this->id)
            ->orderBy('tipo_assinante', 'ASC')
            ->get();
    }
    
    /**
     * Verifica se todas as assinaturas foram concluídas
     */
    public function todasAssinaturasConcluidas(): bool
    {
        $signatures = $this->signatures();
        if (empty($signatures)) {
            return false;
        }
        
        foreach ($signatures as $signature) {
            if (!$signature->assinado) {
                return false;
            }
        }
        
        return true;
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
     * Gera número de contrato único
     */
    public static function gerarNumeroContrato(): string
    {
        $ano = date('Y');
        $mes = date('m');
        
        // Busca último número do mês
        $db = \Core\Database::getInstance();
        $ultimo = $db->queryOne(
            "SELECT numero_contrato FROM contracts 
             WHERE numero_contrato LIKE ? 
             ORDER BY id DESC LIMIT 1",
            ["C{$ano}-{$mes}-%"]
        );
        
        $sequencial = 1;
        if ($ultimo && isset($ultimo['numero_contrato'])) {
            $parts = explode('-', $ultimo['numero_contrato']);
            if (count($parts) === 3) {
                $sequencial = (int)$parts[2] + 1;
            }
        }
        
        return "C{$ano}-{$mes}-{$sequencial}";
    }
    
    /**
     * Gera token único para assinatura
     */
    public function gerarTokenAssinatura(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->update(['token_assinatura' => $token]);
        return $token;
    }
    
    /**
     * Gera link de assinatura
     */
    public function gerarLinkAssinatura(string $tipoAssinante): string
    {
        if (!$this->token_assinatura) {
            $this->gerarTokenAssinatura();
        }
        return url('/contracts/sign/' . $this->token_assinatura . '?tipo=' . $tipoAssinante);
    }
}

