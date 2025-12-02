<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\FileHelper;
use App\Models\Client;
use App\Models\SistemaLog;

class ClientController extends Controller
{
    /**
     * Lista todos os clientes
     */
    public function index(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $clients = Client::where('user_id', $userId)
            ->orderBy('nome_razao_social', 'ASC')
            ->get();

        return $this->view('clients/index', [
            'title' => 'Clientes',
            'clients' => $clients
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

        return $this->view('clients/create', [
            'title' => 'Novo Cliente'
        ]);
    }

    /**
     * Salva novo cliente
     */
    public function store(): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        try {
            $data = $this->validate([
                'tipo' => 'required|in:fisica,juridica',
                'nome_razao_social' => 'required',
                'nome_fantasia' => 'nullable',
                'cpf_cnpj' => 'nullable',
                'email' => 'nullable|email',
                'telefone' => 'nullable',
                'celular' => 'nullable',
                'instagram' => 'nullable',
                'endereco' => 'nullable',
                'numero' => 'nullable',
                'complemento' => 'nullable',
                'bairro' => 'nullable',
                'cidade' => 'nullable',
                'estado' => 'nullable',
                'cep' => 'nullable',
                'score' => 'nullable|integer|min:0|max:100',
                'observacoes' => 'nullable'
            ]);

            $userId = auth()->getDataUserId();

            $client = Client::create([
                'user_id' => $userId,
                'tipo' => $data['tipo'],
                'nome_razao_social' => $data['nome_razao_social'],
                'nome_fantasia' => $data['nome_fantasia'] ?? null,
                'cpf_cnpj' => $data['cpf_cnpj'] ?? null,
                'email' => $data['email'] ?? null,
                'telefone' => $data['telefone'] ?? null,
                'celular' => $data['celular'] ?? null,
                'instagram' => $data['instagram'] ?? null,
                'endereco' => $data['endereco'] ?? null,
                'numero' => $data['numero'] ?? null,
                'complemento' => $data['complemento'] ?? null,
                'bairro' => $data['bairro'] ?? null,
                'cidade' => $data['cidade'] ?? null,
                'estado' => $data['estado'] ?? null,
                'cep' => $data['cep'] ?? null,
                'score' => $data['score'] ?? 50,
                'observacoes' => $data['observacoes'] ?? null
            ]);
            
            // Processa foto se fornecida (após criar o cliente para ter o ID)
            if ($this->request->has('foto_base64') && !empty($this->request->input('foto_base64'))) {
                $fotoBase64 = trim($this->request->input('foto_base64', ''));
                if (!empty($fotoBase64) && strlen($fotoBase64) > 100) {
                    $filename = 'client_' . $client->id . '_' . time();
                    $fotoPath = FileHelper::saveBase64Image($fotoBase64, 'storage/clients', $filename);
                    
                    if ($fotoPath) {
                        // Atualiza o cliente com o caminho/URL da foto
                        $client->update(['foto' => $fotoPath]);
                    }
                }
            }
            
            // Adiciona tags
            $tagsInput = $this->request->input('tags', '');
            $tags = array_filter(array_map('trim', explode(',', $tagsInput)));
            
            if (!empty($tags)) {
                $tagModel = \App\Models\Tag::class;
                foreach ($tags as $tagNameOrId) {
                    if (is_numeric($tagNameOrId)) {
                        $client->addTag((int) $tagNameOrId);
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
                        $client->addTag($tag->id);
                    }
                }
            }

            SistemaLog::registrar(
                'clients',
                'create',
                $client->id,
                "Cliente criado: {$client->nome_razao_social}"
            );

            session()->flash('success', 'Cliente cadastrado com sucesso!');
            $this->redirect('/clients');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao cadastrar cliente: ' . $e->getMessage());
            $this->redirect('/clients/create');
        }
    }

    /**
     * Exibe detalhes do cliente
     */
    public function show(array $params): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $client = Client::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();

        if (!$client) {
            abort(404, 'Cliente não encontrado.');
        }

        // Busca relacionamentos
        $leads = $client->leads();
        $proposals = $client->proposals();
        $contacts = $client->contacts();

        // Busca histórico completo ordenado por data (timeline)
        $history = $this->getClientHistory($client->id, $userId);

        return $this->view('clients/show', [
            'title' => 'Detalhes do Cliente: ' . $client->nome_razao_social,
            'client' => $client,
            'leads' => $leads,
            'proposals' => $proposals,
            'contacts' => $contacts,
            'history' => $history
        ]);
    }

    /**
     * Retorna detalhes do cliente via AJAX (HTML parcial)
     */
    public function details(array $params): void
    {
        if (!auth()->check()) {
            http_response_code(401);
            echo 'Não autenticado';
            exit;
        }

        $userId = auth()->getDataUserId();
        $client = Client::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();

        if (!$client) {
            http_response_code(404);
            echo 'Cliente não encontrado';
            exit;
        }

        // Busca relacionamentos
        $leads = $client->leads();
        $proposals = $client->proposals();
        $contacts = $client->contacts();

        // Renderiza apenas o conteúdo parcial
        $viewFile = base_path('views/clients/_details.php');
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo '<p>Erro ao carregar detalhes do cliente.</p>';
        }
        exit;
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
        $client = Client::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();

        if (!$client) {
            abort(404, 'Cliente não encontrado.');
        }

        return $this->view('clients/edit', [
            'title' => 'Editar Cliente',
            'client' => $client
        ]);
    }

    /**
     * Atualiza cliente
     */
    public function update(array $params): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        try {
            $userId = auth()->getDataUserId();
            $client = Client::where('id', $params['id'])
                ->where('user_id', $userId)
                ->first();

            if (!$client) {
                abort(404, 'Cliente não encontrado.');
            }

            $data = $this->validate([
                'tipo' => 'required|in:fisica,juridica',
                'nome_razao_social' => 'required',
                'nome_fantasia' => 'nullable',
                'cpf_cnpj' => 'nullable',
                'email' => 'nullable|email',
                'telefone' => 'nullable',
                'celular' => 'nullable',
                'instagram' => 'nullable',
                'endereco' => 'nullable',
                'numero' => 'nullable',
                'complemento' => 'nullable',
                'bairro' => 'nullable',
                'cidade' => 'nullable',
                'estado' => 'nullable',
                'cep' => 'nullable',
                'score' => 'nullable|integer|min:0|max:100',
                'observacoes' => 'nullable'
            ]);

            // Processa foto se fornecida
            $updateData = [
                'tipo' => $data['tipo'],
                'nome_razao_social' => $data['nome_razao_social'],
                'nome_fantasia' => $data['nome_fantasia'] ?? null,
                'cpf_cnpj' => $data['cpf_cnpj'] ?? null,
                'email' => $data['email'] ?? null,
                'telefone' => $data['telefone'] ?? null,
                'celular' => $data['celular'] ?? null,
                'instagram' => $data['instagram'] ?? null,
                'endereco' => $data['endereco'] ?? null,
                'numero' => $data['numero'] ?? null,
                'complemento' => $data['complemento'] ?? null,
                'bairro' => $data['bairro'] ?? null,
                'cidade' => $data['cidade'] ?? null,
                'estado' => $data['estado'] ?? null,
                'cep' => $data['cep'] ?? null,
                'score' => $data['score'] ?? $client->score,
                'observacoes' => $data['observacoes'] ?? null
            ];
            
            // Processa foto se fornecida
            if ($this->request->has('foto_base64') && !empty($this->request->input('foto_base64'))) {
                $fotoBase64 = trim($this->request->input('foto_base64', ''));
                if (!empty($fotoBase64) && strlen($fotoBase64) > 100) {
                    // Remove foto antiga se existir
                    if (!empty($client->foto)) {
                        FileHelper::deleteFile($client->foto);
                    }
                    
                    $filename = 'client_' . $client->id . '_' . time();
                    $fotoPath = FileHelper::saveBase64Image($fotoBase64, 'storage/clients', $filename);
                    
                    if ($fotoPath) {
                        $updateData['foto'] = $fotoPath;
                    }
                }
            }
            
            $client->update($updateData);
            
            // Remove todas as tags e adiciona as novas
            $client->removeAllTags();
            
            // Adiciona tags
            $tagsInput = $this->request->input('tags', '');
            $tags = array_filter(array_map('trim', explode(',', $tagsInput)));
            
            if (!empty($tags)) {
                $tagModel = \App\Models\Tag::class;
                foreach ($tags as $tagNameOrId) {
                    if (is_numeric($tagNameOrId)) {
                        $client->addTag((int) $tagNameOrId);
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
                        $client->addTag($tag->id);
                    }
                }
            }

            SistemaLog::registrar(
                'clients',
                'update',
                $client->id,
                "Cliente atualizado: {$client->nome_razao_social}"
            );

            session()->flash('success', 'Cliente atualizado com sucesso!');
            $this->redirect('/clients/' . $client->id);
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao atualizar cliente: ' . $e->getMessage());
            $this->redirect('/clients/' . $params['id'] . '/edit');
        }
    }

    /**
     * Deleta cliente
     */
    public function destroy(array $params): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        try {
            $userId = auth()->getDataUserId();
            $client = Client::where('id', $params['id'])
                ->where('user_id', $userId)
                ->first();

            if (!$client) {
                abort(404, 'Cliente não encontrado.');
            }

            $nome = $client->nome_razao_social;
            $clientId = $client->id;

            $client->delete();

            SistemaLog::registrar(
                'clients',
                'delete',
                $clientId,
                "Cliente excluído: {$nome}"
            );

            session()->flash('success', 'Cliente excluído com sucesso!');
            $this->redirect('/clients');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao excluir cliente: ' . $e->getMessage());
            $this->redirect('/clients');
        }
    }

    /**
     * Busca histórico completo do cliente (timeline de negociações)
     */
    private function getClientHistory(int $clientId, int $userId): array
    {
        $history = [];
        $db = \Core\Database::getInstance();

        // Busca leads relacionados
        $leads = $db->query(
            "SELECT id, nome, email, telefone, etapa_funil, score_potencial, origem, 
                    origem_conheceu, created_at, 'lead' as tipo
             FROM leads 
             WHERE client_id = ? AND user_id = ?
             ORDER BY created_at DESC",
            [$clientId, $userId]
        );

        foreach ($leads as $lead) {
            $history[] = [
                'tipo' => 'lead',
                'id' => $lead['id'],
                'titulo' => "Lead: {$lead['nome']}",
                'descricao' => "Novo lead capturado via {$lead['origem']}" . 
                              ($lead['etapa_funil'] ? " - Etapa: " . ucfirst(str_replace('_', ' ', $lead['etapa_funil'])) : ''),
                'data' => $lead['created_at'],
                'dados' => $lead
            ];
        }

        // Busca propostas relacionadas
        $proposals = $db->query(
            "SELECT id, titulo, descricao, valor, status, data_envio, data_validade, created_at, 'proposal' as tipo
             FROM proposals 
             WHERE client_id = ? AND user_id = ?
             ORDER BY created_at DESC",
            [$clientId, $userId]
        );

        foreach ($proposals as $proposal) {
            $history[] = [
                'tipo' => 'proposal',
                'id' => $proposal['id'],
                'titulo' => "Proposta: {$proposal['titulo']}",
                'descricao' => "Valor: R$ " . number_format((float)$proposal['valor'], 2, ',', '.') . 
                              " - Status: " . ucfirst($proposal['status']),
                'data' => $proposal['created_at'],
                'dados' => $proposal
            ];
        }

        // Busca contatos relacionados
        $contacts = $db->query(
            "SELECT id, nome, cargo, tipo, assunto, descricao, data_contato, hora_contato, resultado, created_at, 'contact' as tipo
             FROM contacts 
             WHERE client_id = ? AND user_id = ?
             ORDER BY COALESCE(data_contato, created_at) DESC, COALESCE(hora_contato, '00:00') DESC",
            [$clientId, $userId]
        );

        foreach ($contacts as $contact) {
            $dataContato = $contact['data_contato'] ?? date('Y-m-d', strtotime($contact['created_at']));
            $horaContato = $contact['hora_contato'] ?? date('H:i', strtotime($contact['created_at']));
            
            $history[] = [
                'tipo' => 'contact',
                'id' => $contact['id'],
                'titulo' => "Contato: {$contact['nome']}" . ($contact['cargo'] ? " ({$contact['cargo']})" : ''),
                'descricao' => ucfirst($contact['tipo']) . 
                              ($contact['assunto'] ? " - {$contact['assunto']}" : '') .
                              ($contact['resultado'] ? " - Resultado: " . ucfirst($contact['resultado']) : ''),
                'data' => $dataContato . ' ' . $horaContato,
                'dados' => $contact
            ];
        }

        // Ordena histórico por data (mais recente primeiro)
        usort($history, function($a, $b) {
            return strtotime($b['data']) - strtotime($a['data']);
        });

        return $history;
    }
}

