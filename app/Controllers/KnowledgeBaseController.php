<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\KnowledgeBase;
use App\Models\Client;
use App\Models\Tag;
use App\Models\User;
use App\Models\SistemaLog;

class KnowledgeBaseController extends Controller
{
    /**
     * Lista todos os conhecimentos
     */
    public function index(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $search = $this->request->query('search', '');
        $categoria = $this->request->query('categoria', '');
        $status = $this->request->query('status', '');
        $clientId = $this->request->query('client_id', '');
        
        $db = \Core\Database::getInstance();
        $conditions = ['user_id = ?'];
        $params = [$userId];
        
        if (!empty($search)) {
            $conditions[] = '(titulo LIKE ? OR conteudo LIKE ? OR resumo LIKE ?)';
            $searchLike = "%{$search}%";
            $params[] = $searchLike;
            $params[] = $searchLike;
            $params[] = $searchLike;
        }
        
        if (!empty($categoria)) {
            $conditions[] = 'categoria = ?';
            $params[] = $categoria;
        }
        
        if (!empty($status)) {
            $conditions[] = 'status = ?';
            $params[] = $status;
        }
        
        if (!empty($clientId)) {
            $conditions[] = 'client_id = ?';
            $params[] = $clientId;
        }
        
        $whereClause = implode(' AND ', $conditions);
        $sql = "SELECT * FROM knowledge_base WHERE {$whereClause} ORDER BY created_at DESC";
        $results = $db->query($sql, $params);
        
        $knowledgeBase = array_map(function($row) {
            return KnowledgeBase::newInstance($row, true);
        }, $results);
        
        // Busca categorias únicas para filtro
        $categorias = $db->query(
            "SELECT DISTINCT categoria FROM knowledge_base WHERE user_id = ? AND categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC",
            [$userId]
        );
        
        // Busca clientes para filtro
        $clientsResults = $db->query(
            "SELECT * FROM clients WHERE user_id = ? ORDER BY nome_razao_social ASC",
            [$userId]
        );
        $clients = array_map(function($row) {
            return Client::newInstance($row, true);
        }, $clientsResults);

        return $this->view('knowledge-base/index', [
            'title' => 'Base de Conhecimento',
            'knowledgeBase' => $knowledgeBase,
            'categorias' => $categorias,
            'clients' => $clients,
            'filters' => [
                'search' => $search,
                'categoria' => $categoria,
                'status' => $status,
                'client_id' => $clientId
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
        $tags = Tag::where('user_id', $userId)
            ->orderBy('name', 'ASC')
            ->get();

        return $this->view('knowledge-base/create', [
            'title' => 'Novo Conhecimento',
            'tags' => $tags
        ]);
    }

    /**
     * Salva novo conhecimento
     */
    public function store(): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido');
            $this->redirect('/knowledge-base/create');
        }

        try {
            $userId = auth()->getDataUserId();
            
            $data = $this->validate([
                'titulo' => 'required|string|max:255',
                'conteudo' => 'required|string',
                'resumo' => 'nullable|string',
                'client_id' => 'nullable|integer',
                'categoria' => 'nullable|string|max:100',
                'status' => 'nullable|in:rascunho,publicado,arquivado',
                'tags_input' => 'nullable|string'
            ]);

            $knowledge = KnowledgeBase::create([
                'titulo' => $data['titulo'],
                'conteudo' => $data['conteudo'],
                'resumo' => $data['resumo'] ?? null,
                'client_id' => !empty($data['client_id']) ? (int)$data['client_id'] : null,
                'categoria' => $data['categoria'] ?? null,
                'status' => $data['status'] ?? 'rascunho',
                'visualizacoes' => 0,
                'user_id' => $userId
            ]);

            // Processa tags
            if (!empty($data['tags_input'])) {
                $tagNames = array_filter(array_map('trim', explode(',', $data['tags_input'])));
                foreach ($tagNames as $tagName) {
                    if (empty($tagName)) {
                        continue;
                    }
                    
                    $tag = Tag::where('name', $tagName)
                        ->where('user_id', $userId)
                        ->first();
                    
                    if (!$tag) {
                        $tag = Tag::create([
                            'name' => $tagName,
                            'color' => '#0dcaf0',
                            'user_id' => $userId
                        ]);
                    }
                    
                    $knowledge->addTag($tag->id);
                }
            }

            SistemaLog::registrar(
                'knowledge_base',
                'CREATE',
                $knowledge->id,
                "Conhecimento criado: {$knowledge->titulo}",
                null,
                $knowledge->toArray()
            );

            session()->flash('success', 'Conhecimento criado com sucesso!');
            $this->redirect('/knowledge-base/' . $knowledge->id);
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao criar conhecimento: ' . $e->getMessage());
            $this->redirect('/knowledge-base/create');
        }
    }

    /**
     * Exibe detalhes do conhecimento
     */
    public function show(array $params): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $knowledge = KnowledgeBase::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();

        if (!$knowledge) {
            abort(404, 'Conhecimento não encontrado.');
        }

        // Incrementa visualizações
        $knowledge->incrementViews();

        $tags = $knowledge->tags();
        $client = $knowledge->client();
        $author = $knowledge->author();

        return $this->view('knowledge-base/show', [
            'title' => $knowledge->titulo,
            'knowledge' => $knowledge,
            'tags' => $tags,
            'client' => $client,
            'author' => $author
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
        $knowledge = KnowledgeBase::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();

        if (!$knowledge) {
            abort(404, 'Conhecimento não encontrado.');
        }

        $tags = Tag::where('user_id', $userId)
            ->orderBy('name', 'ASC')
            ->get();
        
        $knowledgeTags = $knowledge->tags();
        $tagsInput = implode(', ', array_map(function($tag) {
            return $tag['name'];
        }, $knowledgeTags));

        return $this->view('knowledge-base/edit', [
            'title' => 'Editar Conhecimento',
            'knowledge' => $knowledge,
            'tags' => $tags,
            'tagsInput' => $tagsInput
        ]);
    }

    /**
     * Atualiza conhecimento
     */
    public function update(array $params): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido');
            $this->redirect('/knowledge-base/' . $params['id'] . '/edit');
        }

        try {
            $userId = auth()->getDataUserId();
            $knowledge = KnowledgeBase::where('id', $params['id'])
                ->where('user_id', $userId)
                ->first();

            if (!$knowledge) {
                abort(404, 'Conhecimento não encontrado.');
            }

            $data = $this->validate([
                'titulo' => 'required|string|max:255',
                'conteudo' => 'required|string',
                'resumo' => 'nullable|string',
                'client_id' => 'nullable|integer',
                'categoria' => 'nullable|string|max:100',
                'status' => 'nullable|in:rascunho,publicado,arquivado',
                'tags_input' => 'nullable|string'
            ]);

            $knowledge->update([
                'titulo' => $data['titulo'],
                'conteudo' => $data['conteudo'],
                'resumo' => $data['resumo'] ?? null,
                'client_id' => !empty($data['client_id']) ? (int)$data['client_id'] : null,
                'categoria' => $data['categoria'] ?? null,
                'status' => $data['status'] ?? 'rascunho'
            ]);

            // Remove todas as tags e adiciona as novas
            $knowledge->removeAllTags();
            if (!empty($data['tags_input'])) {
                $tagNames = array_filter(array_map('trim', explode(',', $data['tags_input'])));
                foreach ($tagNames as $tagName) {
                    if (empty($tagName)) {
                        continue;
                    }
                    
                    $tag = Tag::where('name', $tagName)
                        ->where('user_id', $userId)
                        ->first();
                    
                    if (!$tag) {
                        $tag = Tag::create([
                            'name' => $tagName,
                            'color' => '#0dcaf0',
                            'user_id' => $userId
                        ]);
                    }
                    
                    $knowledge->addTag($tag->id);
                }
            }

            SistemaLog::registrar(
                'knowledge_base',
                'UPDATE',
                $knowledge->id,
                "Conhecimento atualizado: {$knowledge->titulo}",
                null,
                $knowledge->toArray()
            );

            session()->flash('success', 'Conhecimento atualizado com sucesso!');
            $this->redirect('/knowledge-base/' . $knowledge->id);
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao atualizar conhecimento: ' . $e->getMessage());
            $this->redirect('/knowledge-base/' . $params['id'] . '/edit');
        }
    }

    /**
     * Exclui conhecimento
     */
    public function destroy(array $params): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        try {
            $userId = auth()->getDataUserId();
            $knowledge = KnowledgeBase::where('id', $params['id'])
                ->where('user_id', $userId)
                ->first();

            if (!$knowledge) {
                abort(404, 'Conhecimento não encontrado.');
            }

            $knowledgeId = $knowledge->id;
            $knowledgeTitulo = $knowledge->titulo;
            
            // Remove tags relacionadas
            $knowledge->removeAllTags();
            
            // Exclui o conhecimento
            $knowledge->delete();

            SistemaLog::registrar(
                'knowledge_base',
                'DELETE',
                $knowledgeId,
                "Conhecimento excluído: {$knowledgeTitulo}",
                null,
                null
            );

            session()->flash('success', 'Conhecimento excluído com sucesso!');
            $this->redirect('/knowledge-base');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao excluir conhecimento: ' . $e->getMessage());
            $this->redirect('/knowledge-base');
        }
    }
}

