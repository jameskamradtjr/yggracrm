<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\Project;
use App\Models\SistemaLog;

/**
 * Controller de Projetos
 */
class ProjectController extends Controller
{
    /**
     * Lista todos os projetos
     */
    public function index(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        
        // Filtros
        $status = $this->request->query('status', 'all');
        $prioridade = $this->request->query('prioridade', 'all');
        $search = $this->request->query('search');
        
        $query = Project::where('user_id', $userId);
        
        if ($status !== 'all') {
            $query = $query->where('status', $status);
        }
        
        if ($prioridade !== 'all') {
            $query = $query->where('prioridade', $prioridade);
        }
        
        if ($search) {
            $query = $query->where(function($q) use ($search) {
                $q->where('titulo', 'LIKE', "%{$search}%")
                  ->orWhere('descricao', 'LIKE', "%{$search}%");
            });
        }
        
        $projects = $query->orderBy('created_at', 'DESC')->get();
        
        // Busca clientes e usuários para os filtros
        $clients = \App\Models\Client::where('user_id', $userId)->get();
        $users = \App\Models\User::where('status', 'active')->get();
        
        return $this->view('projects/index', [
            'title' => 'Gestão de Projetos',
            'projects' => $projects,
            'clients' => $clients,
            'users' => $users,
            'filters' => [
                'status' => $status,
                'prioridade' => $prioridade,
                'search' => $search
            ]
        ]);
    }
    
    /**
     * Exibe formulário de criação
     */
    public function create(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $clients = \App\Models\Client::where('user_id', $userId)->get();
        $leads = \App\Models\Lead::where('user_id', $userId)->get();
        $users = \App\Models\User::where('status', 'active')->get();
        
        return $this->view('projects/create', [
            'title' => 'Novo Projeto',
            'clients' => $clients,
            'leads' => $leads,
            'users' => $users
        ]);
    }
    
    /**
     * Salva novo projeto
     */
    public function store(): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/projects/create');
        }

        $data = $this->validate([
            'titulo' => 'required',
            'descricao' => 'nullable',
            'status' => 'required|in:planejamento,em_andamento,pausado,concluido,cancelado',
            'prioridade' => 'required|in:baixa,media,alta,urgente',
            'data_inicio' => 'nullable|date',
            'data_termino_prevista' => 'nullable|date',
            'data_termino_real' => 'nullable|date',
            'client_id' => 'nullable|numeric',
            'lead_id' => 'nullable|numeric',
            'responsible_user_id' => 'nullable|numeric',
            'orcamento' => 'nullable|numeric|min:0',
            'custo_real' => 'nullable|numeric|min:0',
            'progresso' => 'nullable|integer|min:0|max:100',
            'observacoes' => 'nullable'
        ]);

        try {
            $userId = auth()->getDataUserId();
            
            $project = Project::create([
                'user_id' => $userId,
                'titulo' => $data['titulo'],
                'descricao' => $data['descricao'] ?? null,
                'status' => $data['status'],
                'prioridade' => $data['prioridade'],
                'data_inicio' => !empty($data['data_inicio']) ? $data['data_inicio'] : null,
                'data_termino_prevista' => !empty($data['data_termino_prevista']) ? $data['data_termino_prevista'] : null,
                'data_termino_real' => !empty($data['data_termino_real']) ? $data['data_termino_real'] : null,
                'client_id' => !empty($data['client_id']) ? (int)$data['client_id'] : null,
                'lead_id' => !empty($data['lead_id']) ? (int)$data['lead_id'] : null,
                'responsible_user_id' => !empty($data['responsible_user_id']) ? (int)$data['responsible_user_id'] : null,
                'orcamento' => !empty($data['orcamento']) ? (float)$data['orcamento'] : null,
                'custo_real' => !empty($data['custo_real']) ? (float)$data['custo_real'] : null,
                'progresso' => $data['progresso'] ?? 0,
                'observacoes' => $data['observacoes'] ?? null
            ]);
            
            // Adiciona tags
            $tagsInput = $this->request->input('tags', '');
            $tags = array_filter(array_map('trim', explode(',', $tagsInput)));
            
            if (!empty($tags)) {
                $tagModel = \App\Models\Tag::class;
                foreach ($tags as $tagNameOrId) {
                    if (is_numeric($tagNameOrId)) {
                        $project->addTag((int) $tagNameOrId);
                    } else {
                        $tag = $tagModel::where('name', $tagNameOrId)
                            ->where('user_id', $userId)
                            ->first();
                        
                        if (!$tag) {
                            $tag = $tagModel::create([
                                'name' => $tagNameOrId,
                                'user_id' => $userId
                            ]);
                        }
                        $project->addTag($tag->id);
                    }
                }
            }
            
            // Registra log
            SistemaLog::registrar(
                'projects',
                'CREATE',
                $project->id,
                "Projeto criado: {$project->titulo}",
                null,
                $project->toArray()
            );
            
            session()->flash('success', 'Projeto cadastrado com sucesso!');
            $this->redirect('/projects');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao cadastrar projeto: ' . $e->getMessage());
            session()->flash('old', $this->request->all());
            $this->redirect('/projects/create');
        }
    }
    
    /**
     * Exibe detalhes do projeto
     */
    public function show(array $params): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $project = Project::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$project) {
            session()->flash('error', 'Projeto não encontrado.');
            $this->redirect('/projects');
        }
        
        // Busca relacionamentos
        $client = $project->client_id ? $project->client() : null;
        $lead = $project->lead_id ? $project->lead() : null;
        $responsible = $project->responsible_user_id ? $project->responsible() : null;
        
        return $this->view('projects/show', [
            'title' => 'Detalhes do Projeto',
            'project' => $project,
            'client' => $client,
            'lead' => $lead,
            'responsible' => $responsible
        ]);
    }
    
    /**
     * Exibe formulário de edição
     */
    public function edit(array $params): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $project = Project::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$project) {
            session()->flash('error', 'Projeto não encontrado.');
            $this->redirect('/projects');
        }
        
        $clients = \App\Models\Client::where('user_id', $userId)->get();
        $leads = \App\Models\Lead::where('user_id', $userId)->get();
        $users = \App\Models\User::where('status', 'active')->get();
        
        return $this->view('projects/edit', [
            'title' => 'Editar Projeto',
            'project' => $project,
            'clients' => $clients,
            'leads' => $leads,
            'users' => $users
        ]);
    }
    
    /**
     * Atualiza projeto
     */
    public function update(array $params): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/projects');
        }

        $userId = auth()->getDataUserId();
        $project = Project::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$project) {
            session()->flash('error', 'Projeto não encontrado.');
            $this->redirect('/projects');
        }

        $data = $this->validate([
            'titulo' => 'required',
            'descricao' => 'nullable',
            'status' => 'required|in:planejamento,em_andamento,pausado,concluido,cancelado',
            'prioridade' => 'required|in:baixa,media,alta,urgente',
            'data_inicio' => 'nullable|date',
            'data_termino_prevista' => 'nullable|date',
            'data_termino_real' => 'nullable|date',
            'client_id' => 'nullable|numeric',
            'lead_id' => 'nullable|numeric',
            'responsible_user_id' => 'nullable|numeric',
            'orcamento' => 'nullable|numeric|min:0',
            'custo_real' => 'nullable|numeric|min:0',
            'progresso' => 'nullable|integer|min:0|max:100',
            'observacoes' => 'nullable'
        ]);

        try {
            // Captura dados anteriores para o log
            $dadosAnteriores = $project->toArray();
            
            $project->update([
                'titulo' => $data['titulo'],
                'descricao' => $data['descricao'] ?? null,
                'status' => $data['status'],
                'prioridade' => $data['prioridade'],
                'data_inicio' => !empty($data['data_inicio']) ? $data['data_inicio'] : null,
                'data_termino_prevista' => !empty($data['data_termino_prevista']) ? $data['data_termino_prevista'] : null,
                'data_termino_real' => !empty($data['data_termino_real']) ? $data['data_termino_real'] : null,
                'client_id' => !empty($data['client_id']) ? (int)$data['client_id'] : null,
                'lead_id' => !empty($data['lead_id']) ? (int)$data['lead_id'] : null,
                'responsible_user_id' => !empty($data['responsible_user_id']) ? (int)$data['responsible_user_id'] : null,
                'orcamento' => !empty($data['orcamento']) ? (float)$data['orcamento'] : null,
                'custo_real' => !empty($data['custo_real']) ? (float)$data['custo_real'] : null,
                'progresso' => $data['progresso'] ?? 0,
                'observacoes' => $data['observacoes'] ?? null
            ]);
            
            // Remove todas as tags e adiciona as novas
            $project->removeAllTags();
            
            // Adiciona tags
            $tagsInput = $this->request->input('tags', '');
            $tags = array_filter(array_map('trim', explode(',', $tagsInput)));
            
            if (!empty($tags)) {
                $tagModel = \App\Models\Tag::class;
                foreach ($tags as $tagNameOrId) {
                    if (is_numeric($tagNameOrId)) {
                        $project->addTag((int) $tagNameOrId);
                    } else {
                        $tag = $tagModel::where('name', $tagNameOrId)
                            ->where('user_id', $userId)
                            ->first();
                        
                        if (!$tag) {
                            $tag = $tagModel::create([
                                'name' => $tagNameOrId,
                                'user_id' => $userId
                            ]);
                        }
                        $project->addTag($tag->id);
                    }
                }
            }
            
            // Registra log
            SistemaLog::registrar(
                'projects',
                'UPDATE',
                $project->id,
                "Projeto atualizado: {$project->titulo}",
                $dadosAnteriores,
                $project->toArray()
            );
            
            session()->flash('success', 'Projeto atualizado com sucesso!');
            $this->redirect('/projects');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao atualizar projeto: ' . $e->getMessage());
            $this->redirect('/projects/' . $params['id'] . '/edit');
        }
    }
    
    /**
     * Exclui projeto
     */
    public function destroy(array $params): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/projects');
        }

        $userId = auth()->getDataUserId();
        $project = Project::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$project) {
            session()->flash('error', 'Projeto não encontrado.');
            $this->redirect('/projects');
        }

        try {
            $titulo = $project->titulo;
            $dadosAnteriores = $project->toArray();
            
            $project->delete();
            
            // Registra log
            SistemaLog::registrar(
                'projects',
                'DELETE',
                $params['id'],
                "Projeto excluído: {$titulo}",
                $dadosAnteriores,
                null
            );
            
            session()->flash('success', 'Projeto excluído com sucesso!');
            $this->redirect('/projects');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao excluir projeto: ' . $e->getMessage());
            $this->redirect('/projects');
        }
    }
}

