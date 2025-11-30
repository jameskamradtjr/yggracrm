<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Client;

/**
 * Serviço para processamento de contratos
 */
class ContractService
{
    /**
     * Substitui variáveis no template do contrato
     */
    public static function substituirVariaveis(string $conteudo, Contract $contract, ?Client $client = null): string
    {
        $variaveis = self::extrairVariaveis($conteudo);
        $valores = self::obterValoresVariaveis($variaveis, $contract, $client);
        
        foreach ($valores as $variavel => $valor) {
            $conteudo = str_replace('{{' . $variavel . '}}', $valor, $conteudo);
            $conteudo = str_replace('{{ ' . $variavel . ' }}', $valor, $conteudo); // Com espaços
        }
        
        return $conteudo;
    }
    
    /**
     * Extrai variáveis do conteúdo (ex: {{nome_cliente}})
     */
    private static function extrairVariaveis(string $conteudo): array
    {
        preg_match_all('/\{\{([^}]+)\}\}/', $conteudo, $matches);
        return array_unique($matches[1] ?? []);
    }
    
    /**
     * Obtém valores das variáveis
     */
    private static function obterValoresVariaveis(array $variaveis, Contract $contract, ?Client $client = null): array
    {
        $valores = [];
        
        if (!$client && $contract->client_id) {
            $client = $contract->client();
        }
        
        foreach ($variaveis as $var) {
            $var = trim($var);
            
            switch ($var) {
                case 'nome_cliente':
                    $valores[$var] = $client ? e($client->nome_razao_social) : '';
                    break;
                case 'documento_cliente':
                    $valores[$var] = $client ? e($client->cpf_cnpj ?? '') : '';
                    break;
                case 'email_cliente':
                    $valores[$var] = $client ? e($client->email ?? '') : '';
                    break;
                case 'telefone_cliente':
                    $valores[$var] = $client ? e($client->telefone ?? $client->celular ?? '') : '';
                    break;
                case 'endereco_cliente':
                    $endereco = '';
                    if ($client) {
                        $parts = array_filter([
                            $client->endereco ?? '',
                            $client->numero ?? '',
                            $client->complemento ?? '',
                            $client->bairro ?? '',
                            $client->cidade ?? '',
                            $client->estado ?? '',
                            $client->cep ?? ''
                        ]);
                        $endereco = implode(', ', $parts);
                    }
                    $valores[$var] = $endereco;
                    break;
                case 'cidade_cliente':
                    $valores[$var] = $client ? e($client->cidade ?? '') : '';
                    break;
                case 'estado_cliente':
                    $valores[$var] = $client ? e($client->estado ?? '') : '';
                    break;
                case 'cep_cliente':
                    $valores[$var] = $client ? e($client->cep ?? '') : '';
                    break;
                case 'numero_contrato':
                    $valores[$var] = e($contract->numero_contrato);
                    break;
                case 'data_contrato':
                    $valores[$var] = date('d/m/Y');
                    break;
                case 'data_inicio':
                    $valores[$var] = $contract->data_inicio ? date('d/m/Y', strtotime($contract->data_inicio)) : '';
                    break;
                case 'data_termino':
                    $valores[$var] = $contract->data_termino ? date('d/m/Y', strtotime($contract->data_termino)) : '';
                    break;
                case 'valor_total':
                    $valores[$var] = $contract->valor_total ? 'R$ ' . number_format($contract->valor_total, 2, ',', '.') : '';
                    break;
                case 'nome_empresa':
                    // Pega do usuário logado ou configuração
                    $valores[$var] = auth()->user()->name ?? 'Empresa';
                    break;
                case 'documento_empresa':
                    // Pode vir de configurações do sistema
                    $valores[$var] = \App\Models\SystemSetting::get('empresa_cnpj', '');
                    break;
                default:
                    $valores[$var] = '';
            }
        }
        
        return $valores;
    }
    
    /**
     * Gera HTML do contrato para PDF
     */
    public static function gerarHtmlContrato(Contract $contract): string
    {
        $client = $contract->client();
        $services = $contract->services();
        $conditions = $contract->conditions();
        $signatures = $contract->signatures();
        
        ob_start();
        include base_path('views/contracts/pdf-template.php');
        return ob_get_clean();
    }
    
    /**
     * Gera link único de assinatura
     */
    public static function gerarLinkAssinatura(Contract $contract, string $tipoAssinante): string
    {
        $token = bin2hex(random_bytes(32));
        $contract->update(['token_assinatura' => $token]);
        
        return url('/contracts/sign/' . $token . '?tipo=' . $tipoAssinante);
    }
}

