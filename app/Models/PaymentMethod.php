<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class PaymentMethod extends Model
{
    protected string $table = 'payment_methods';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'user_id', 'nome', 'tipo', 'taxa', 'conta_id', 
        'dias_recebimento', 'adicionar_taxa_como_despesa', 
        'ativo', 'observacoes'
    ];
    
    protected array $casts = [
        'taxa' => 'float',
        'dias_recebimento' => 'integer',
        'adicionar_taxa_como_despesa' => 'boolean',
        'ativo' => 'boolean'
    ];
    
    /**
     * Relacionamento com conta bancária
     */
    public function account()
    {
        if (!$this->conta_id) {
            return null;
        }
        return \App\Models\BankAccount::find($this->conta_id);
    }
    
    /**
     * Calcula o valor líquido após a taxa
     */
    public function calcularValorLiquido(float $valorBruto): float
    {
        if ($this->taxa > 0) {
            $taxaDecimal = $this->taxa / 100;
            $valorTaxa = $valorBruto * $taxaDecimal;
            return $valorBruto - $valorTaxa;
        }
        return $valorBruto;
    }
    
    /**
     * Calcula o valor da taxa
     */
    public function calcularTaxa(float $valorBruto): float
    {
        if ($this->taxa > 0) {
            $taxaDecimal = $this->taxa / 100;
            return $valorBruto * $taxaDecimal;
        }
        return 0.00;
    }
    
    /**
     * Calcula a data de liberação baseada na data de vencimento
     */
    public function calcularDataLiberacao(string $dataVencimento): string
    {
        if ($this->dias_recebimento <= 0) {
            return $dataVencimento;
        }
        
        $data = new \DateTime($dataVencimento);
        $data->modify("+{$this->dias_recebimento} days");
        
        return $data->format('Y-m-d');
    }
}

