<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\Proposal;

class PublicProposalController extends Controller
{
    /**
     * Exibe proposta pública via token
     */
    public function show(array $params): string
    {
        $proposalId = $params['id'] ?? null;
        $token = $params['token'] ?? null;
        
        if (!$proposalId || !$token) {
            abort(404, 'Proposta não encontrada');
        }
        
        // Busca proposta pelo ID e token público
        $proposal = Proposal::where('id', $proposalId)
            ->where('token_publico', $token)
            ->first();
        
        if (!$proposal) {
            abort(404, 'Proposta não encontrada ou link inválido');
        }
        
        // Registra visualização (apenas primeira vez)
        if (!$proposal->data_visualizacao_cliente) {
            $proposal->update([
                'data_visualizacao_cliente' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Carrega dados relacionados
        $services = $proposal->services();
        $conditions = $proposal->conditions();
        $client = $proposal->client();
        $lead = $proposal->lead();
        
        // Busca dados da empresa
        $companyName = \App\Models\SystemSetting::get('company_name', config('app.name'));
        $companyLogo = \App\Models\SystemSetting::get('logo_dark');
        
        return $this->view('proposals/public', [
            'title' => $proposal->numero_proposta ?? 'Proposta Comercial',
            'proposal' => $proposal,
            'services' => $services,
            'conditions' => $conditions,
            'client' => $client,
            'lead' => $lead,
            'companyName' => $companyName,
            'companyLogo' => $companyLogo,
            'isPublicView' => true
        ]);
    }
}

