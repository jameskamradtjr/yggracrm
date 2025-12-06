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
        $tagName = trim($this->request->query('tag_name', ''));
        $archivedParam = $this->request->query('archived');
        $showArchived = $archivedParam === '1' || $archivedParam === 1;
        
        // Usa query direta para ter controle total sobre os filtros
        $db = \Core\Database::getInstance();
        
        // Se houver filtro de tag por nome, precisa fazer JOIN
        if ($tagName) {
            $whereConditions = ["p.`user_id` = ?"];
            $params = [$userId];
            
            // Filtro de arquivadas
            if ($showArchived) {
                $whereConditions[] = "p.`is_archived` = 1";
            } else {
                $whereConditions[] = "(p.`is_archived` = 0 OR p.`is_archived` IS NULL)";
            }
            
            // Filtro de status
            if ($status !== 'all') {
                $whereConditions[] = "p.`status` = ?";
                $params[] = $status;
            }
            
            // Filtro de tag por nome
            $whereConditions[] = "t.`name` LIKE ? AND tg.`taggable_type` = 'Proposal'";
            $params[] = "%{$tagName}%";
            
            // Busca
            if ($search) {
                $searchTerm = "%{$search}%";
                $whereConditions[] = "(p.`titulo` LIKE ? OR p.`numero_proposta` LIKE ? OR p.`identificacao` LIKE ?)";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $whereClause = "WHERE " . implode(' AND ', $whereConditions);
            $sql = "SELECT DISTINCT p.* FROM `proposals` p 
                    INNER JOIN `taggables` tg ON p.`id` = tg.`taggable_id` 
                    INNER JOIN `tags` t ON tg.`tag_id` = t.`id`
                    {$whereClause} 
                    ORDER BY p.`created_at` DESC";
            $results = $db->query($sql, $params);
        } else {
            // Sem filtro de tag, query normal
            $whereConditions = ["`user_id` = ?"];
            $params = [$userId];
            
            // Filtro de arquivadas
            if ($showArchived) {
                $whereConditions[] = "`is_archived` = 1";
            } else {
                $whereConditions[] = "(`is_archived` = 0 OR `is_archived` IS NULL)";
            }
            
            // Filtro de status
            if ($status !== 'all') {
                $whereConditions[] = "`status` = ?";
                $params[] = $status;
            }
            
            // Busca
            if ($search) {
                $searchTerm = "%{$search}%";
                $whereConditions[] = "(`titulo` LIKE ? OR `numero_proposta` LIKE ? OR `identificacao` LIKE ?)";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $whereClause = "WHERE " . implode(' AND ', $whereConditions);
            $sql = "SELECT * FROM `proposals` {$whereClause} ORDER BY `created_at` DESC";
            $results = $db->query($sql, $params);
        }
        
        $proposals = array_map(function($row) {
            return Proposal::newInstance($row, true);
        }, $results ?: []);

        return $this->view('proposals/index', [
            'title' => 'Propostas',
            'proposals' => $proposals,
            'status' => $status,
            'search' => $search,
            'showArchived' => $showArchived,
            'tagName' => $tagName
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
            
            // Processa tecnologias se fornecidas
            if (isset($data['technologies']) && is_array($data['technologies'])) {
                $proposal->setTechnologies($data['technologies']);
            }
            
            // Processa roadmap steps se fornecidos
            if (isset($data['roadmap_steps']) && is_array($data['roadmap_steps'])) {
                foreach ($data['roadmap_steps'] as $index => $step) {
                    if (!empty($step['title'])) {
                        $proposal->addRoadmapStep(
                            $step['title'],
                            $step['description'] ?? null,
                            $step['estimated_date'] ?? null,
                            $step['order'] ?? $index
                        );
                    }
                }
            }
            
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
        $paymentForms = $proposal->getPaymentForms();
        $testimonials = $proposal->getTestimonials();
        $technologies = $proposal->getTechnologies();
        $roadmapSteps = $proposal->getRoadmapSteps();
        $tags = $proposal->getTags();
        $client = $proposal->client();
        $lead = $proposal->lead();
        $project = $proposal->project();

        return $this->view('proposals/show', [
            'title' => $proposal->numero_proposta ?? 'Proposta',
            'proposal' => $proposal,
            'services' => $services,
            'conditions' => $conditions,
            'paymentForms' => $paymentForms,
            'testimonials' => $testimonials,
            'technologies' => $technologies,
            'roadmapSteps' => $roadmapSteps,
            'tags' => $tags,
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
            
            // Processa tecnologias se fornecidas
            if (isset($input['technologies']) && is_array($input['technologies'])) {
                $proposal->setTechnologies($input['technologies']);
            }
            
            // Processa tags se fornecidas
            if (isset($input['tags']) && !empty($input['tags'])) {
                $tagIds = is_array($input['tags']) 
                    ? $input['tags'] 
                    : explode(',', $input['tags']);
                $tagIds = array_filter(array_map('intval', $tagIds));
                $proposal->setTags($tagIds);
            } elseif (isset($input['tags']) && empty($input['tags'])) {
                // Se tags vazio, remove todas
                $proposal->removeAllTags();
            }
            
            // Processa roadmap steps se fornecidos
            if (isset($input['roadmap_steps']) && is_array($input['roadmap_steps'])) {
                // Remove todas as etapas existentes
                $db = \Core\Database::getInstance();
                $db->execute("DELETE FROM proposal_roadmap_steps WHERE proposal_id = ?", [$proposal->id]);
                
                // Adiciona as novas etapas
                foreach ($input['roadmap_steps'] as $index => $step) {
                    if (!empty($step['title'])) {
                        $proposal->addRoadmapStep(
                            $step['title'],
                            $step['description'] ?? null,
                            $step['estimated_date'] ?? null,
                            $step['order'] ?? $index
                        );
                    }
                }
            }
            
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
     * Remove condição (ou forma de pagamento)
     */
    public function deleteCondition(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
            return;
        }
        
        $userId = auth()->getDataUserId();
        $proposal = Proposal::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$proposal) {
            json_response(['success' => false, 'message' => 'Proposta não encontrada'], 404);
            return;
        }
        
        $conditionId = (int)$this->request->input('condition_id');
        
        if (!$conditionId) {
            json_response(['success' => false, 'message' => 'ID da condição não fornecido'], 400);
            return;
        }
        
        try {
            $condition = ProposalCondition::find($conditionId);
            
            if (!$condition || $condition->proposal_id != $proposal->id) {
                json_response(['success' => false, 'message' => 'Condição não encontrada'], 404);
                return;
            }
            
            $condition->delete();
            
            json_response(['success' => true, 'message' => 'Condição removida com sucesso!']);
        } catch (\Exception $e) {
            error_log("Erro ao remover condição: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao remover condição'], 500);
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
        $paymentForms = $proposal->getPaymentForms();
        $testimonials = $proposal->getTestimonials();
        $client = $proposal->client();
        $lead = $proposal->lead();

        return $this->view('proposals/preview', [
            'title' => 'Preview - ' . ($proposal->numero_proposta ?? 'Proposta'),
            'proposal' => $proposal,
            'services' => $services,
            'conditions' => $conditions,
            'paymentForms' => $paymentForms,
            'testimonials' => $testimonials,
            'client' => $client,
            'lead' => $lead
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
    
    /**
     * Adiciona forma de pagamento
     */
    public function addPaymentForm(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
            return;
        }
        
        $userId = auth()->getDataUserId();
        $proposal = Proposal::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$proposal) {
            json_response(['success' => false, 'message' => 'Proposta não encontrada'], 404);
            return;
        }
        
        $titulo = trim($this->request->input('titulo', ''));
        $descricao = trim($this->request->input('descricao', ''));
        $valorOriginal = $this->request->input('valor_original') ? (float)$this->request->input('valor_original') : null;
        $valorFinal = $this->request->input('valor_final') ? (float)$this->request->input('valor_final') : null;
        $parcelas = $this->request->input('parcelas') ? (int)$this->request->input('parcelas') : null;
        $valorParcela = $this->request->input('valor_parcela') ? (float)$this->request->input('valor_parcela') : null;
        $ordem = (int)$this->request->input('ordem', 0);
        
        if (empty($titulo)) {
            json_response(['success' => false, 'message' => 'Título é obrigatório'], 400);
            return;
        }
        
        try {
            $condition = ProposalCondition::create([
                'proposal_id' => $proposal->id,
                'titulo' => $titulo,
                'descricao' => $descricao ?: null,
                'tipo' => 'pagamento',
                'valor_original' => $valorOriginal,
                'valor_final' => $valorFinal,
                'parcelas' => $parcelas,
                'valor_parcela' => $valorParcela,
                'ordem' => $ordem,
                'is_selected' => false
            ]);
            
            json_response([
                'success' => true,
                'message' => 'Forma de pagamento adicionada com sucesso!',
                'data' => $condition->toArray()
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao adicionar forma de pagamento: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao adicionar forma de pagamento'], 500);
        }
    }
    
    /**
     * Adiciona prova social
     */
    public function addTestimonial(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
            return;
        }
        
        $userId = auth()->getDataUserId();
        $proposal = Proposal::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$proposal) {
            json_response(['success' => false, 'message' => 'Proposta não encontrada'], 404);
            return;
        }
        
        $clientName = trim($this->request->input('client_name', ''));
        $testimonial = trim($this->request->input('testimonial', ''));
        $company = trim($this->request->input('company', ''));
        $order = (int)$this->request->input('order', 0);
        
        if (empty($clientName) || empty($testimonial)) {
            json_response(['success' => false, 'message' => 'Nome do cliente e depoimento são obrigatórios'], 400);
            return;
        }
        
        // Processa upload de foto se houver
        $photoUrl = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['photo'];
            $tmpFile = $file['tmp_name'];
            $originalName = $file['name'];
            
            // Upload para S3 público
            $url = s3_upload_public($tmpFile, $userId, 'proposals/testimonials', ['jpg', 'jpeg', 'png', 'gif', 'webp'], $originalName);
            
            if ($url) {
                $photoUrl = $url;
            }
        }
        
        try {
            $testimonialId = $proposal->addTestimonial($clientName, $testimonial, $company ?: null, $photoUrl, $order);
            
            if ($testimonialId) {
                $testimonialObj = \App\Models\ProposalTestimonial::find($testimonialId);
                json_response([
                    'success' => true,
                    'message' => 'Prova social adicionada com sucesso!',
                    'data' => $testimonialObj ? $testimonialObj->toArray() : null
                ]);
            } else {
                json_response(['success' => false, 'message' => 'Limite de 3 provas sociais atingido'], 400);
            }
        } catch (\Exception $e) {
            error_log("Erro ao adicionar prova social: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao adicionar prova social'], 500);
        }
    }
    
    /**
     * Remove prova social
     */
    public function removeTestimonial(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
            return;
        }
        
        $userId = auth()->getDataUserId();
        $proposal = Proposal::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$proposal) {
            json_response(['success' => false, 'message' => 'Proposta não encontrada'], 404);
            return;
        }
        
        $testimonialId = (int)$this->request->input('testimonial_id');
        
        if ($proposal->removeTestimonial($testimonialId)) {
            json_response(['success' => true, 'message' => 'Prova social removida com sucesso!']);
        } else {
            json_response(['success' => false, 'message' => 'Erro ao remover prova social'], 500);
        }
    }
    
    /**
     * Seleciona forma de pagamento (pelo cliente na proposta pública)
     */
    public function selectPaymentForm(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $proposal = Proposal::find($params['id']);
        
        if (!$proposal) {
            json_response(['success' => false, 'message' => 'Proposta não encontrada'], 404);
            return;
        }
        
        // Verifica token público
        $token = $params['token'] ?? null;
        if (!$token || $proposal->token_publico !== $token) {
            json_response(['success' => false, 'message' => 'Token inválido'], 403);
            return;
        }
        
        $conditionId = (int)$this->request->input('condition_id');
        
        // Desmarca todas as formas de pagamento desta proposta
        $db = \Core\Database::getInstance();
        $db->execute(
            "UPDATE proposal_conditions SET is_selected = 0 WHERE proposal_id = ? AND tipo = 'pagamento'",
            [$proposal->id]
        );
        
        // Marca a selecionada
        $db->execute(
            "UPDATE proposal_conditions SET is_selected = 1 WHERE id = ? AND proposal_id = ? AND tipo = 'pagamento'",
            [$conditionId, $proposal->id]
        );
        
        json_response(['success' => true, 'message' => 'Forma de pagamento selecionada com sucesso!']);
    }
    
    /**
     * Arquivar/Desarquivar proposta
     */
    public function archive(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
            return;
        }
        
        $userId = auth()->getDataUserId();
        $proposal = Proposal::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$proposal) {
            json_response(['success' => false, 'message' => 'Proposta não encontrada'], 404);
            return;
        }
        
        $isArchived = (bool)$this->request->input('archived', false);
        $proposal->is_archived = $isArchived;
        $proposal->save();
        
        SistemaLog::registrar(
            'proposals',
            'UPDATE',
            $proposal->id,
            $isArchived ? "Proposta arquivada" : "Proposta desarquivada",
            null,
            ['is_archived' => $isArchived]
        );
        
        json_response([
            'success' => true,
            'message' => $isArchived ? 'Proposta arquivada com sucesso!' : 'Proposta desarquivada com sucesso!'
        ]);
    }
    
    /**
     * Atualizar status da proposta
     */
    public function updateStatus(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
            return;
        }
        
        $userId = auth()->getDataUserId();
        $proposal = Proposal::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$proposal) {
            json_response(['success' => false, 'message' => 'Proposta não encontrada'], 404);
            return;
        }
        
        $status = $this->request->input('status');
        $allowedStatuses = ['rascunho', 'enviada', 'aprovada', 'rejeitada', 'cancelada'];
        
        if (!in_array($status, $allowedStatuses)) {
            json_response(['success' => false, 'message' => 'Status inválido'], 400);
            return;
        }
        
        $oldStatus = $proposal->status;
        $proposal->status = $status;
        
        if ($status === 'enviada' && !$proposal->data_envio) {
            $proposal->data_envio = date('Y-m-d');
        }
        
        $proposal->save();
        
        SistemaLog::registrar(
            'proposals',
            'UPDATE',
            $proposal->id,
            "Status alterado de {$oldStatus} para {$status}",
            ['status' => $oldStatus],
            ['status' => $status]
        );
        
        // Dispara evento de automação
        AutomationEventDispatcher::onProposal('status_changed', $proposal->id, $userId);
        
        json_response([
            'success' => true,
            'message' => 'Status atualizado com sucesso!'
        ]);
    }
    
    /**
     * Adicionar etapa ao roadmap
     */
    public function addRoadmapStepAction(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
            return;
        }
        
        $userId = auth()->getDataUserId();
        $proposal = Proposal::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$proposal) {
            json_response(['success' => false, 'message' => 'Proposta não encontrada'], 404);
            return;
        }
        
        $title = trim($this->request->input('title', ''));
        if (empty($title)) {
            json_response(['success' => false, 'message' => 'O título é obrigatório'], 400);
            return;
        }
        
        $description = trim($this->request->input('description', ''));
        $estimatedDate = $this->request->input('estimated_date') ?: null;
        
        // Pega a próxima ordem
        $db = \Core\Database::getInstance();
        $lastStep = $db->queryOne(
            "SELECT MAX(`order`) as max_order FROM proposal_roadmap_steps WHERE proposal_id = ?",
            [$proposal->id]
        );
        $order = ($lastStep['max_order'] ?? 0) + 1;
        
        $stepId = $proposal->addRoadmapStep($title, $description, $estimatedDate, $order);
        
        if ($stepId) {
            json_response([
                'success' => true,
                'message' => 'Etapa adicionada com sucesso!',
                'step_id' => $stepId
            ]);
        } else {
            json_response(['success' => false, 'message' => 'Erro ao adicionar etapa'], 500);
        }
    }
    
    /**
     * Remover etapa do roadmap
     */
    public function removeRoadmapStep(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
            return;
        }
        
        $userId = auth()->getDataUserId();
        $proposal = Proposal::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$proposal) {
            json_response(['success' => false, 'message' => 'Proposta não encontrada'], 404);
            return;
        }
        
        $stepId = (int)$this->request->input('step_id');
        if (!$stepId) {
            json_response(['success' => false, 'message' => 'ID da etapa é obrigatório'], 400);
            return;
        }
        
        if ($proposal->removeRoadmapStep($stepId)) {
            json_response([
                'success' => true,
                'message' => 'Etapa removida com sucesso!'
            ]);
        } else {
            json_response(['success' => false, 'message' => 'Erro ao remover etapa'], 500);
        }
    }
    
    /**
     * Enviar proposta por email
     */
    public function sendEmail(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }
        
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
            return;
        }
        
        $userId = auth()->getDataUserId();
        $proposal = Proposal::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$proposal) {
            json_response(['success' => false, 'message' => 'Proposta não encontrada'], 404);
            return;
        }
        
        $email = trim($this->request->input('email', ''));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['success' => false, 'message' => 'E-mail inválido'], 400);
            return;
        }
        
        try {
            // Gera link público
            $appUrl = rtrim(config('app.url', 'http://localhost'), '/');
            $publicUrl = $appUrl . '/proposals/' . $proposal->id . '/public/' . $proposal->token_publico;
            
            // Envia email
            $smtpService = new \App\Services\SMTPService();
            
            $subject = "Proposta: " . $proposal->titulo;
            $body = "
                <h1>Proposta Comercial</h1>
                <p>Olá!</p>
                <p>Segue o link para visualizar a proposta <strong>{$proposal->titulo}</strong>:</p>
                <p><a href='{$publicUrl}' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Visualizar Proposta</a></p>
                <p>Ou acesse diretamente: <a href='{$publicUrl}'>{$publicUrl}</a></p>
                <br>
                <p>Atenciosamente.</p>
            ";
            
            $result = $smtpService->sendEmail($email, $subject, $body);
            
            if ($result['success']) {
                // Atualiza status se for rascunho
                if ($proposal->status === 'rascunho') {
                    $proposal->status = 'enviada';
                    $proposal->data_envio = date('Y-m-d');
                    $proposal->save();
                }
                
                SistemaLog::registrar(
                    'proposals',
                    'EMAIL',
                    $proposal->id,
                    "Proposta enviada por e-mail para: {$email}",
                    null,
                    ['email' => $email]
                );
                
                json_response([
                    'success' => true,
                    'message' => 'E-mail enviado com sucesso!'
                ]);
            } else {
                json_response(['success' => false, 'message' => $result['message'] ?? 'Erro ao enviar e-mail'], 500);
            }
        } catch (\Exception $e) {
            error_log("Erro ao enviar proposta por email: " . $e->getMessage());
            json_response(['success' => false, 'message' => 'Erro ao enviar e-mail: ' . $e->getMessage()], 500);
        }
    }
}

