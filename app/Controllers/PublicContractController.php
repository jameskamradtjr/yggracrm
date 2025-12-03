<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\Contract;

class PublicContractController extends Controller
{
    /**
     * Exibe contrato público via token
     */
    public function show(array $params): string
    {
        $contractId = $params['id'] ?? null;
        $token = $params['token'] ?? null;
        
        if (!$contractId || !$token) {
            abort(404, 'Contrato não encontrado');
        }
        
        // Busca contrato pelo ID e token público
        $contract = Contract::where('id', $contractId)
            ->where('token_publico', $token)
            ->first();
        
        if (!$contract) {
            abort(404, 'Contrato não encontrado ou link inválido');
        }
        
        // Registra visualização (apenas primeira vez)
        if (!$contract->data_visualizacao_cliente) {
            $contract->update([
                'data_visualizacao_cliente' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Carrega dados relacionados
        $client = $contract->client();
        $proposal = $contract->proposal();
        $services = $contract->services();
        $conditions = $contract->conditions();
        
        // Busca dados da empresa
        $companyName = \App\Models\SystemSetting::get('company_name', config('app.name'));
        $companyLogo = \App\Models\SystemSetting::get('logo_dark');
        
        return $this->view('contracts/public', [
            'title' => $contract->numero_contrato ?? 'Contrato',
            'contract' => $contract,
            'client' => $client,
            'proposal' => $proposal,
            'services' => $services,
            'conditions' => $conditions,
            'companyName' => $companyName,
            'companyLogo' => $companyLogo,
            'isPublicView' => true
        ]);
    }
}

