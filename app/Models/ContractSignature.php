<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class ContractSignature extends Model
{
    protected string $table = 'contract_signatures';
    protected bool $multiTenant = false; // Gerenciado pelo contract pai
    
    protected array $fillable = [
        'contract_id', 'tipo_assinante', 'nome_assinante', 'cpf_cnpj', 
        'email', 'telefone', 'codigo_verificacao', 'codigo_enviado_em',
        'codigo_validado_em', 'assinado', 'assinado_em', 'ip_assinatura',
        'geolocalizacao', 'dispositivo', 'hash_assinatura', 
        'certificado_digital', 'observacoes'
    ];
    
    protected array $casts = [
        'assinado' => 'boolean',
        'codigo_enviado_em' => 'datetime',
        'codigo_validado_em' => 'datetime',
        'assinado_em' => 'datetime',
        'geolocalizacao' => 'json'
    ];
    
    /**
     * Relacionamento com contrato
     */
    public function contract()
    {
        return Contract::find($this->contract_id);
    }
    
    /**
     * Gera código de verificação de 6 dígitos
     */
    public static function gerarCodigoVerificacao(): string
    {
        return str_pad((string)rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Gera hash de assinatura para validação jurídica
     */
    public function gerarHashAssinatura(): string
    {
        $contract = $this->contract();
        if (!$contract) {
            return '';
        }
        
        $dados = [
            'contract_id' => $contract->id,
            'numero_contrato' => $contract->numero_contrato,
            'assinante' => $this->nome_assinante,
            'cpf_cnpj' => $this->cpf_cnpj,
            'email' => $this->email,
            'tipo' => $this->tipo_assinante,
            'data_assinatura' => $this->assinado_em,
            'ip' => $this->ip_assinatura,
            'timestamp' => time()
        ];
        
        $string = json_encode($dados);
        return hash('sha256', $string . config('app.key', 'yggracrm-secret-key'));
    }
}

