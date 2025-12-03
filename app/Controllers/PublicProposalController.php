<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\Proposal;
use App\Models\ProposalView;

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
        
        // Registra primeira visualização do cliente
        if (!$proposal->data_visualizacao_cliente) {
            $proposal->update([
                'data_visualizacao_cliente' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Registra visualização detalhada (IP, user agent, etc.)
        ProposalView::registrarVisualizacao((int)$proposalId);
        
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
    
    /**
     * Cliente aceita a proposta
     */
    public function accept(array $params): void
    {
        $proposalId = $params['id'] ?? null;
        $token = $params['token'] ?? null;
        
        if (!$proposalId || !$token) {
            json_response(['success' => false, 'message' => 'Proposta não encontrada'], 404);
            return;
        }
        
        $proposal = Proposal::where('id', $proposalId)
            ->where('token_publico', $token)
            ->first();
        
        if (!$proposal) {
            json_response(['success' => false, 'message' => 'Proposta não encontrada ou link inválido'], 404);
            return;
        }
        
        // Atualiza status para aprovada
        $proposal->update([
            'status' => 'aprovada'
        ]);
        
        // Registra log
        \App\Models\SistemaLog::registrar(
            'proposals',
            'ACCEPT_PUBLIC',
            $proposal->id,
            "Proposta {$proposal->numero_proposta} aceita pelo cliente via link público",
            null,
            null
        );
        
        json_response([
            'success' => true,
            'message' => 'Proposta aceita com sucesso! Em breve entraremos em contato.'
        ]);
    }
    
    /**
     * Cliente recusa a proposta
     */
    public function reject(array $params): void
    {
        $proposalId = $params['id'] ?? null;
        $token = $params['token'] ?? null;
        
        if (!$proposalId || !$token) {
            json_response(['success' => false, 'message' => 'Proposta não encontrada'], 404);
            return;
        }
        
        $proposal = Proposal::where('id', $proposalId)
            ->where('token_publico', $token)
            ->first();
        
        if (!$proposal) {
            json_response(['success' => false, 'message' => 'Proposta não encontrada ou link inválido'], 404);
            return;
        }
        
        // Recebe motivo (opcional)
        $data = json_decode(file_get_contents('php://input'), true);
        $motivo = $data['motivo'] ?? null;
        
        // Atualiza status para rejeitada
        $proposal->update([
            'status' => 'rejeitada'
        ]);
        
        // Registra log com motivo se fornecido
        \App\Models\SistemaLog::registrar(
            'proposals',
            'REJECT_PUBLIC',
            $proposal->id,
            "Proposta {$proposal->numero_proposta} recusada pelo cliente via link público" . ($motivo ? ". Motivo: {$motivo}" : ""),
            null,
            null
        );
        
        json_response([
            'success' => true,
            'message' => 'Sua resposta foi registrada. Agradecemos o retorno!'
        ]);
    }
}

