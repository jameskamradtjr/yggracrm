<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\Proposal;
use App\Models\ProposalService;
use App\Models\ProposalCondition;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Project;
use App\Models\SistemaLog;
use App\Services\ResendService;
use App\Services\SmtpService;
use App\Services\Automation\AutomationEventDispatcher;

class ProposalController extends Controller
{
    /**
     * Lista propostas
     */
    public function index(): string
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $status = $this->request->query('status', 'all');
        $search = $this->request->query('search');
        
        $query = Proposal::where('user_id', $userId);
        
        if ($status !== 'all') {
            $query = $query->where('status', $status);
        }
        
        if ($search) {
            $query = $query->where('titulo', 'LIKE', "%{$search}%")
                ->orWhere('numero_proposta', 'LIKE', "%{$search}%")
                ->orWhere('identificacao', 'LIKE', "%{$search}%");
        }
        
        $proposals = $query->orderBy('created_at', 'DESC')->get();

        return $this->view('proposals/index', [
            'title' => 'Propostas',
            'proposals' => $proposals,
            'status' => $status,
            'search' => $search
        ]);
    }

    /**
     * Exibe formulário de criação
     */
    public function create(): string
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        $userId = auth()->getDataUserId();
        
        $clients = Client::where('user_id', $userId)
            ->orderBy('nome_razao_social', 'ASC')
            ->get();
        
        $leads = Lead::where('user_id', $userId)
            ->whereNotNull('client_id')
            ->orderBy('nome', 'ASC')
            ->get();
        
        $projects = Project::where('user_id', $userId)
            ->orderBy('titulo', 'ASC')
            ->get();

        return $this->view('proposals/create', [
            'title' => 'Nova Proposta',
            'clients' => $clients,
            'leads' => $leads,
            'projects' => $projects
        ]);
    }

    /**
     * Salva nova proposta
     */
    public function store(): void
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/proposals/create');
        }

        $userId = auth()->getDataUserId();
        
        $data = $this->validate([
            'client_id' => 'nullable|integer',
            'lead_id' => 'nullable|integer',
            'project_id' => 'nullable|integer',
            'titulo' => 'required|min:3|max:255',
            'identificacao' => 'nullable|max:255',
            'objetivo' => 'nullable',
            'apresentacao' => 'nullable',
            'duracao_dias' => 'nullable|integer|min:1',
            'disponibilidade_inicio_imediato' => 'nullable|boolean',
            'forma_pagamento' => 'nullable|max:50',
            'desconto_percentual' => 'nullable|numeric|min:0|max:100',
            'data_validade' => 'nullable|date'
        ]);

        try {
            $proposalData = [
                'user_id' => $userId,
                'client_id' => $data['client_id'] ?? null,
                'lead_id' => $data['lead_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'titulo' => $data['titulo'],
                'identificacao' => $data['identificacao'] ?? null,
                'objetivo' => $data['objetivo'] ?? null,
                'apresentacao' => $data['apresentacao'] ?? null,
                'duracao_dias' => $data['duracao_dias'] ?? null,
                'disponibilidade_inicio_imediato' => isset($data['disponibilidade_inicio_imediato']) && $data['disponibilidade_inicio_imediato'],
                'forma_pagamento' => $data['forma_pagamento'] ?? null,
                'desconto_percentual' => $data['desconto_percentual'] ?? 0,
                'data_validade' => $data['data_validade'] ?? null,
                'status' => 'rascunho',
                'subtotal' => 0,
                'desconto_valor' => 0,
                'total' => 0,
                'valor' => 0
            ];
            
            $proposal = Proposal::create($proposalData);
            
            // Gera número da proposta após criar
            if (!$proposal->numero_proposta) {
                $proposal->gerarNumeroProposta();
            }
            
            // Gera token público para compartilhamento
            if (!$proposal->token_publico) {
                $proposal->gerarTokenPublico();
            }
            
            $proposal->save();
            
            // Dispara evento de automação
            AutomationEventDispatcher::onProposal('created', $proposal->id, auth()->getDataUserId());
            
            // Processa upload de imagem de capa após criar a proposta (para ter o ID)
            if ($this->request->hasFile('imagem_capa')) {
                $file = $this->request->file('imagem_capa');
                
                if ($file && isset($file['error']) && $file['error'] === UPLOAD_ERR_OK) {
                    $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'];
                    
                    if (in_array($file['type'], $allowedTypes)) {
                        // Cria diretório se não existir
                        $uploadDir = base_path('public/uploads/proposals');
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        // Gera nome único
                        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = 'proposal_' . $proposal->id . '_' . time() . '.' . $extension;
                        $filepath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

                        // Move arquivo
                        if (move_uploaded_file($file['tmp_name'], $filepath)) {
                            // Salva caminho relativo
                            $proposal->imagem_capa = '/uploads/proposals/' . $filename;
                            $proposal->save();
                        }
                    }
                }
            }
            
            // Log
            SistemaLog::registrar(
                'proposals',
                'CREATE',
                $proposal->id,
                "Proposta {$proposal->numero_proposta} criada",
                null,
                $proposal->toArray()
            );
            
            session()->flash('success', 'Proposta criada com sucesso!');
            $this->redirect('/proposals/' . $proposal->id);
        } catch (\Exception $e) {
            error_log("Erro ao criar proposta: " . $e->getMessage());
            session()->flash('error', 'Erro ao criar proposta: ' . $e->getMessage());
            $this->redirect('/proposals/create');
        }
    }

    /**
     * Exibe detalhes da proposta
     */
    public function show(array $params): string
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $proposal = Proposal::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$proposal) {
            session()->flash('error', 'Proposta não encontrada.');
            $this->redirect('/proposals');
        }
        
        $services = $proposal->services();
        $conditions = $proposal->conditions();
        $client = $proposal->client();
        $lead = $proposal->lead();
        $project = $proposal->project();

        return $this->view('proposals/show', [
            'title' => $proposal->numero_proposta ?? 'Proposta',
            'proposal' => $proposal,
            'services' => $services,
            'conditions' => $conditions,
            'client' => $client,
            'lead' => $lead,
            'project' => $project
        ]);
    }

    /**
     * Atualiza proposta
     */
    public function update(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        // Verifica CSRF token (pode vir no body ou no header)
        $csrfToken = $this->request->input('_csrf_token') 
            ?? $this->request->header('X-CSRF-Token') 
            ?? $this->request->header('X-Csrf-Token')
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? null;
        
        if (!$csrfToken || !verify_csrf($csrfToken)) {
            error_log("CSRF Token inválido - Token recebido: " . ($csrfToken ?: 'vazio'));
            json_response(['success' => false, 'message' => 'Token de segurança inválido. Recarregue a página e tente novamente.'], 403);
            return;
        }

        $userId = auth()->getDataUserId();
        $proposal = Proposal::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$proposal) {
            json_response(['success' => false, 'message' => 'Proposta não encontrada.'], 404);
            return;
        }

        // Aceita tanto FormData quanto JSON
        $input = $this->request->all();
        if (empty($input)) {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
        }
        
        // Debug: log dos dados recebidos
        error_log("ProposalController::update - Dados recebidos: " . print_r($input, true));

        $validator = new \Core\Validator($input, [
            'client_id' => 'nullable|integer',
            'lead_id' => 'nullable|integer',
            'project_id' => 'nullable|integer',
            'titulo' => 'required|min:3|max:255',
            'identificacao' => 'nullable|max:255',
            'video_youtube' => 'nullable|max:500',
            'objetivo' => 'nullable',
            'apresentacao' => 'nullable',
            'duracao_dias' => 'nullable|integer|min:1',
            'disponibilidade_inicio_imediato' => 'nullable|boolean',
            'forma_pagamento' => 'nullable|max:50',
            'desconto_percentual' => 'nullable|numeric|min:0|max:100',
            'data_validade' => 'nullable|date',
            'data_estimada_conclusao' => 'nullable|date',
            'observacoes' => 'nullable'
        ]);

        if ($validator->fails()) {
            json_response([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
            return;
        }

        try {
            $validatedData = $validator->validated();
            $oldData = $proposal->toArray();
            
            // Processa upload de imagem de capa
            if ($this->request->hasFile('imagem_capa')) {
                $file = $this->request->file('imagem_capa');
                
                if ($file && isset($file['error']) && $file['error'] === UPLOAD_ERR_OK) {
                    $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'];
                    
                    if (!in_array($file['type'], $allowedTypes)) {
                        json_response([
                            'success' => false,
                            'message' => 'Tipo de arquivo não permitido. Use PNG, JPG, GIF ou WEBP.'
                        ], 400);
                        return;
                    }

                    // Cria diretório se não existir
                    $uploadDir = base_path('public/uploads/proposals');
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    // Gera nome único
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'proposal_' . $proposal->id . '_' . time() . '.' . $extension;
                    $filepath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

                    // Move arquivo
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        // Remove imagem antiga se existir
                        if ($proposal->imagem_capa && strpos($proposal->imagem_capa, '/uploads/') !== false) {
                            $oldPath = base_path('public' . $proposal->imagem_capa);
                            if (file_exists($oldPath)) {
                                @unlink($oldPath);
                            }
                        }

                        // Salva caminho relativo
                        $proposal->imagem_capa = '/uploads/proposals/' . $filename;
                    } else {
                        json_response([
                            'success' => false,
                            'message' => 'Erro ao fazer upload da imagem.'
                        ], 500);
                        return;
                    }
                }
            }
            
            $proposal->client_id = $validatedData['client_id'] ?? null;
            $proposal->lead_id = $validatedData['lead_id'] ?? null;
            $proposal->project_id = $validatedData['project_id'] ?? null;
            $proposal->titulo = $validatedData['titulo'];
            $proposal->identificacao = isset($validatedData['identificacao']) && !empty(trim($validatedData['identificacao'])) ? trim($validatedData['identificacao']) : null;
            $proposal->video_youtube = isset($validatedData['video_youtube']) && !empty(trim($validatedData['video_youtube'])) ? trim($validatedData['video_youtube']) : null;
            $proposal->objetivo = isset($validatedData['objetivo']) && !empty(trim($validatedData['objetivo'])) ? trim($validatedData['objetivo']) : null;
            $proposal->apresentacao = $validatedData['apresentacao'] ?? null;
            $proposal->duracao_dias = $validatedData['duracao_dias'] ?? null;
            $proposal->disponibilidade_inicio_imediato = isset($validatedData['disponibilidade_inicio_imediato']) && $validatedData['disponibilidade_inicio_imediato'];
            $proposal->forma_pagamento = $validatedData['forma_pagamento'] ?? null;
            $proposal->desconto_percentual = $validatedData['desconto_percentual'] ?? 0;
            $proposal->data_validade = $validatedData['data_validade'] ?? null;
            $proposal->data_estimada_conclusao = $validatedData['data_estimada_conclusao'] ?? null;
            $proposal->observacoes = $validatedData['observacoes'] ?? null;
            
            // Recalcula totais
            $proposal->calcularTotais();
            
            $proposal->save();
            
            // Log
            SistemaLog::registrar(
                'proposals',
                'UPDATE',
                $proposal->id,
                "Proposta {$proposal->numero_proposta} atualizada",
                $oldData,
                $proposal->toArray()
            );
            
            json_response([
                'success' => true,
                'message' => 'Proposta atualizada com sucesso!'
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao atualizar proposta: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao atualizar proposta: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Deleta proposta
     */
    public function destroy(array $params): void
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        // Verifica CSRF token (pode vir no body ou no header)
        $csrfToken = $this->request->input('_csrf_token') 
            ?? $this->request->header('X-CSRF-Token') 
            ?? $this->request->header('X-Csrf-Token')
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? null;
        
        if (!$csrfToken || !verify_csrf($csrfToken)) {
            error_log("CSRF Token inválido - Token recebido: " . ($csrfToken ?: 'vazio'));
            json_response(['success' => false, 'message' => 'Token de segurança inválido. Recarregue a página e tente novamente.'], 403);
            return;
        }

        $userId = auth()->getDataUserId();
        $proposal = Proposal::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$proposal) {
            json_response(['success' => false, 'message' => 'Proposta não encontrada.'], 404);
            return;
        }

        try {
            $numeroProposta = $proposal->numero_proposta;
            
            // Deleta serviços e condições relacionados
            ProposalService::where('proposal_id', $proposal->id)->delete();
            ProposalCondition::where('proposal_id', $proposal->id)->delete();
            
            $proposal->delete();
            
            // Log
            SistemaLog::registrar(
                'proposals',
                'DELETE',
                (int)$params['id'],
                "Proposta {$numeroProposta} deletada",
                $proposal->toArray(),
                null
            );
            
            json_response(['success' => true, 'message' => 'Proposta deletada com sucesso!']);
        } catch (\Exception $e) {
            error_log("Erro ao deletar proposta: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao deletar proposta: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Adiciona serviço à proposta
     */
    public function addService(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        // Verifica CSRF token (pode vir no body ou no header)
        $csrfToken = $this->request->input('_csrf_token') 
            ?? $this->request->header('X-CSRF-Token') 
            ?? $this->request->header('X-Csrf-Token')
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? null;
        
        if (!$csrfToken || !verify_csrf($csrfToken)) {
            error_log("CSRF Token inválido - Token recebido: " . ($csrfToken ?: 'vazio'));
            json_response(['success' => false, 'message' => 'Token de segurança inválido. Recarregue a página e tente novamente.'], 403);
            return;
        }

        $userId = auth()->getDataUserId();
        $proposal = Proposal::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$proposal) {
            json_response(['success' => false, 'message' => 'Proposta não encontrada.'], 404);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) {
            $input = $this->request->all();
        }

        $validator = new \Core\Validator($input, [
            'titulo' => 'required|min:3|max:255',
            'descricao' => 'nullable',
            'quantidade' => 'required|integer|min:1',
            'valor_unitario' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            json_response([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
            return;
        }

        try {
            $validatedData = $validator->validated();
            
            // Busca última ordem usando query direta
            $db = \Core\Database::getInstance();
            $result = $db->queryOne(
                "SELECT MAX(`ordem`) as max_ordem FROM `proposal_services` WHERE `proposal_id` = ?",
                [$proposal->id]
            );
            $ultimaOrdem = $result['max_ordem'] ?? 0;
            
            $service = new ProposalService();
            $service->proposal_id = $proposal->id;
            $service->titulo = $validatedData['titulo'];
            $service->descricao = $validatedData['descricao'] ?? null;
            $service->quantidade = (int)$validatedData['quantidade'];
            $service->valor_unitario = (float)$validatedData['valor_unitario'];
            $service->ordem = $ultimaOrdem + 1;
            $service->calcularTotal();
            $service->save();
            
            // Recalcula totais da proposta
            $proposal->calcularTotais();
            $proposal->save();
            
            json_response([
                'success' => true,
                'message' => 'Serviço adicionado com sucesso!',
                'service' => $service->toArray()
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao adicionar serviço: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao adicionar serviço: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Adiciona condição à proposta
     */
    public function addCondition(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        // Verifica CSRF token (pode vir no body ou no header)
        $csrfToken = $this->request->input('_csrf_token') 
            ?? $this->request->header('X-CSRF-Token') 
            ?? $this->request->header('X-Csrf-Token')
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? null;
        
        if (!$csrfToken || !verify_csrf($csrfToken)) {
            error_log("CSRF Token inválido - Token recebido: " . ($csrfToken ?: 'vazio'));
            json_response(['success' => false, 'message' => 'Token de segurança inválido. Recarregue a página e tente novamente.'], 403);
            return;
        }

        $userId = auth()->getDataUserId();
        $proposal = Proposal::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$proposal) {
            json_response(['success' => false, 'message' => 'Proposta não encontrada.'], 404);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) {
            $input = $this->request->all();
        }

        $validator = new \Core\Validator($input, [
            'titulo' => 'required|min:3|max:255',
            'descricao' => 'nullable'
        ]);

        if ($validator->fails()) {
            json_response([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
            return;
        }

        try {
            $validatedData = $validator->validated();
            
            // Busca última ordem usando query direta
            $db = \Core\Database::getInstance();
            $result = $db->queryOne(
                "SELECT MAX(`ordem`) as max_ordem FROM `proposal_conditions` WHERE `proposal_id` = ?",
                [$proposal->id]
            );
            $ultimaOrdem = $result['max_ordem'] ?? 0;
            
            $condition = new ProposalCondition();
            $condition->proposal_id = $proposal->id;
            $condition->titulo = $validatedData['titulo'];
            $condition->descricao = $validatedData['descricao'] ?? null;
            $condition->ordem = $ultimaOrdem + 1;
            $condition->save();
            
            json_response([
                'success' => true,
                'message' => 'Condição adicionada com sucesso!',
                'condition' => $condition->toArray()
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao adicionar condição: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao adicionar condição: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Preview da proposta (como o cliente verá)
     */
    public function preview(array $params): string
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $proposal = Proposal::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$proposal) {
            session()->flash('error', 'Proposta não encontrada.');
            $this->redirect('/proposals');
        }
        
        $services = $proposal->services();
        $conditions = $proposal->conditions();
        $client = $proposal->client();

        return $this->view('proposals/preview', [
            'title' => 'Preview - ' . ($proposal->numero_proposta ?? 'Proposta'),
            'proposal' => $proposal,
            'services' => $services,
            'conditions' => $conditions,
            'client' => $client
        ]);
    }

    /**
     * Envia proposta para o cliente
     */
    public function send(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        // Verifica CSRF token (pode vir no body ou no header)
        $csrfToken = $this->request->input('_csrf_token') 
            ?? $this->request->header('X-CSRF-Token') 
            ?? $this->request->header('X-Csrf-Token')
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? null;
        
        if (!$csrfToken || !verify_csrf($csrfToken)) {
            error_log("CSRF Token inválido - Token recebido: " . ($csrfToken ?: 'vazio'));
            json_response(['success' => false, 'message' => 'Token de segurança inválido. Recarregue a página e tente novamente.'], 403);
            return;
        }

        $userId = auth()->getDataUserId();
        $proposal = Proposal::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$proposal) {
            json_response(['success' => false, 'message' => 'Proposta não encontrada.'], 404);
            return;
        }

        $client = $proposal->client();
        if (!$client || !$client->email) {
            json_response(['success' => false, 'message' => 'Cliente não possui email cadastrado.'], 400);
            return;
        }

        try {
            // Gera token público se não existir
            if (!$proposal->token_publico) {
                $proposal->gerarTokenPublico();
            }
            
            // Calcula data estimada de conclusão se não existir
            if (!$proposal->data_estimada_conclusao && $proposal->duracao_dias) {
                $proposal->data_estimada_conclusao = date('Y-m-d', strtotime("+{$proposal->duracao_dias} days"));
            }
            
            $proposal->status = 'enviada';
            
            // Dispara evento de automação
            AutomationEventDispatcher::onProposal('sent', $proposal->id, auth()->getDataUserId());
            $proposal->data_envio = date('Y-m-d H:i:s');
            $proposal->save();
            
            // Envia email (implementar depois)
            // TODO: Implementar envio de email
            
            // Log
            SistemaLog::registrar(
                'proposals',
                'UPDATE',
                $proposal->id,
                "Proposta {$proposal->numero_proposta} enviada para cliente",
                null,
                ['email' => $client->email, 'token' => $proposal->token_publico]
            );
            
            json_response([
                'success' => true,
                'message' => 'Proposta enviada com sucesso!',
                'token' => $proposal->token_publico
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao enviar proposta: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao enviar proposta: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Duplica uma proposta
     */
    public function duplicate(array $params): void
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/proposals');
        }

        $userId = auth()->getDataUserId();
        $originalProposal = Proposal::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$originalProposal) {
            session()->flash('error', 'Proposta não encontrada.');
            $this->redirect('/proposals');
        }

        try {
            // Cria nova proposta com dados da original (exceto ID, número, status, datas)
            $newProposalData = $originalProposal->toArray();
            unset($newProposalData['id']);
            unset($newProposalData['numero_proposta']);
            unset($newProposalData['status']);
            unset($newProposalData['data_envio']);
            unset($newProposalData['data_validade']);
            unset($newProposalData['data_visualizacao_cliente']);
            unset($newProposalData['token_publico']);
            unset($newProposalData['created_at']);
            unset($newProposalData['updated_at']);
            
            // Define como rascunho
            $newProposalData['status'] = 'rascunho';
            
            // Remove client_id para que o usuário escolha um novo cliente
            $newProposalData['client_id'] = null;
            $newProposalData['lead_id'] = null;
            
            $newProposal = Proposal::create($newProposalData);
            
            // Gera novo número de proposta
            $newProposal->gerarNumeroProposta();
            $newProposal->save();
            
            // Duplica serviços
            $originalServices = ProposalService::where('proposal_id', $originalProposal->id)->get();
            foreach ($originalServices as $service) {
                $serviceData = $service->toArray();
                unset($serviceData['id']);
                unset($serviceData['created_at']);
                unset($serviceData['updated_at']);
                $serviceData['proposal_id'] = $newProposal->id;
                ProposalService::create($serviceData);
            }
            
            // Duplica condições
            $originalConditions = ProposalCondition::where('proposal_id', $originalProposal->id)->get();
            foreach ($originalConditions as $condition) {
                $conditionData = $condition->toArray();
                unset($conditionData['id']);
                unset($conditionData['created_at']);
                unset($conditionData['updated_at']);
                $conditionData['proposal_id'] = $newProposal->id;
                ProposalCondition::create($conditionData);
            }
            
            // Recalcula totais
            $newProposal->calcularTotais();
            $newProposal->save();
            
            // Log
            SistemaLog::registrar(
                'proposals',
                'CREATE',
                $newProposal->id,
                "Proposta {$newProposal->numero_proposta} duplicada da proposta {$originalProposal->numero_proposta}",
                null,
                ['original_id' => $originalProposal->id]
            );
            
            session()->flash('success', 'Proposta duplicada com sucesso!');
            $this->redirect('/proposals/' . $newProposal->id);
        } catch (\Exception $e) {
            error_log("Erro ao duplicar proposta: " . $e->getMessage());
            session()->flash('error', 'Erro ao duplicar proposta: ' . $e->getMessage());
            $this->redirect('/proposals');
        }
    }

    /**
     * Gera PDF da proposta
     */
    public function generatePdf(array $params): void
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $proposal = Proposal::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$proposal) {
            session()->flash('error', 'Proposta não encontrada.');
            $this->redirect('/proposals');
        }
        
        $services = $proposal->services();
        $conditions = $proposal->conditions();
        $client = $proposal->client();
        
        // Gera HTML da proposta
        ob_start();
        include base_path('views/proposals/pdf-template.php');
        $html = ob_get_clean();
        
        // Gera PDF
        $filename = 'proposta_' . ($proposal->numero_proposta ?? $proposal->id) . '.pdf';
        $filePath = \App\Services\PdfService::generateFromHtml($html, $filename);
        
        // Força download
        \App\Services\PdfService::download($filePath, $filename);
    }
}

