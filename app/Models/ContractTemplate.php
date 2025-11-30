<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class ContractTemplate extends Model
{
    protected string $table = 'contract_templates';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'user_id', 'nome', 'conteudo', 'variaveis_disponiveis', 
        'ativo', 'observacoes'
    ];
    
    protected array $casts = [
        'ativo' => 'boolean',
        'variaveis_disponiveis' => 'json'
    ];
    
    /**
     * Retorna lista padrão de variáveis disponíveis
     */
    public static function getVariaveisPadrao(): array
    {
        return [
            'nome_cliente' => 'Nome do Cliente',
            'documento_cliente' => 'CPF/CNPJ do Cliente',
            'email_cliente' => 'Email do Cliente',
            'telefone_cliente' => 'Telefone do Cliente',
            'endereco_cliente' => 'Endereço Completo do Cliente',
            'nome_empresa' => 'Nome da Empresa (Contratado)',
            'documento_empresa' => 'CPF/CNPJ da Empresa',
            'data_contrato' => 'Data do Contrato',
            'data_inicio' => 'Data de Início',
            'data_termino' => 'Data de Término',
            'valor_total' => 'Valor Total do Contrato',
            'numero_contrato' => 'Número do Contrato',
            'cidade_cliente' => 'Cidade do Cliente',
            'estado_cliente' => 'Estado do Cliente',
            'cep_cliente' => 'CEP do Cliente'
        ];
    }
}

