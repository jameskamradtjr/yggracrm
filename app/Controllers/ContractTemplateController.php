<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\ContractTemplate;
use App\Models\SistemaLog;

class ContractTemplateController extends Controller
{
    /**
     * Lista templates
     */
    public function index(): string
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $templates = ContractTemplate::where('user_id', $userId)
            ->orderBy('nome', 'ASC')
            ->get();

        return $this->view('contracts/templates/index', [
            'title' => 'Templates de Contratos',
            'templates' => $templates
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

        $variaveis = ContractTemplate::getVariaveisPadrao();

        return $this->view('contracts/templates/create', [
            'title' => 'Novo Template',
            'variaveis' => $variaveis
        ]);
    }

    /**
     * Salva novo template
     */
    public function store(): void
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/contracts/templates/create');
        }

        $data = $this->validate([
            'nome' => 'required|min:3|max:255',
            'conteudo' => 'required|min:10',
            'ativo' => 'nullable|boolean',
            'observacoes' => 'nullable'
        ]);

        try {
            $userId = auth()->getDataUserId();
            
            // Extrai variáveis do conteúdo
            preg_match_all('/\{\{([^}]+)\}\}/', $data['conteudo'], $matches);
            $variaveisEncontradas = array_unique($matches[1] ?? []);
            $variaveisDisponiveis = [];
            foreach ($variaveisEncontradas as $var) {
                $var = trim($var);
                $variaveisDisponiveis[$var] = $var;
            }
            
            $template = ContractTemplate::create([
                'user_id' => $userId,
                'nome' => $data['nome'],
                'conteudo' => $data['conteudo'],
                'variaveis_disponiveis' => json_encode($variaveisDisponiveis),
                'ativo' => isset($data['ativo']) ? (bool)$data['ativo'] : true,
                'observacoes' => $data['observacoes'] ?? null
            ]);

            SistemaLog::registrar(
                'contract_templates',
                'CREATE',
                $template->id,
                "Template criado: {$template->nome}",
                null,
                $template->toArray()
            );

            session()->flash('success', 'Template criado com sucesso!');
            $this->redirect('/contracts/templates');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao criar template: ' . $e->getMessage());
            $this->redirect('/contracts/templates/create');
        }
    }

    /**
     * Exibe formulário de edição
     */
    public function edit(array $params): string
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        $template = ContractTemplate::find($params['id']);
        
        if (!$template || $template->user_id !== auth()->getDataUserId()) {
            session()->flash('error', 'Template não encontrado.');
            $this->redirect('/contracts/templates');
        }

        $variaveis = ContractTemplate::getVariaveisPadrao();

        return $this->view('contracts/templates/edit', [
            'title' => 'Editar Template',
            'template' => $template,
            'variaveis' => $variaveis
        ]);
    }

    /**
     * Atualiza template
     */
    public function update(array $params): void
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        $template = ContractTemplate::find($params['id']);
        
        if (!$template || $template->user_id !== auth()->getDataUserId()) {
            session()->flash('error', 'Template não encontrado.');
            $this->redirect('/contracts/templates');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/contracts/templates/' . $template->id . '/edit');
        }

        $data = $this->validate([
            'nome' => 'required|min:3|max:255',
            'conteudo' => 'required|min:10',
            'ativo' => 'nullable|boolean',
            'observacoes' => 'nullable'
        ]);

        try {
            $oldData = $template->toArray();
            
            // Extrai variáveis do conteúdo
            preg_match_all('/\{\{([^}]+)\}\}/', $data['conteudo'], $matches);
            $variaveisEncontradas = array_unique($matches[1] ?? []);
            $variaveisDisponiveis = [];
            foreach ($variaveisEncontradas as $var) {
                $var = trim($var);
                $variaveisDisponiveis[$var] = $var;
            }
            
            $template->update([
                'nome' => $data['nome'],
                'conteudo' => $data['conteudo'],
                'variaveis_disponiveis' => json_encode($variaveisDisponiveis),
                'ativo' => isset($data['ativo']) ? (bool)$data['ativo'] : true,
                'observacoes' => $data['observacoes'] ?? null
            ]);

            SistemaLog::registrar(
                'contract_templates',
                'UPDATE',
                $template->id,
                "Template atualizado: {$template->nome}",
                $oldData,
                $template->toArray()
            );

            session()->flash('success', 'Template atualizado com sucesso!');
            $this->redirect('/contracts/templates');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao atualizar template: ' . $e->getMessage());
            $this->redirect('/contracts/templates/' . $template->id . '/edit');
        }
    }

    /**
     * Deleta template
     */
    public function destroy(array $params): void
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        $template = ContractTemplate::find($params['id']);
        
        if (!$template || $template->user_id !== auth()->getDataUserId()) {
            session()->flash('error', 'Template não encontrado.');
            $this->redirect('/contracts/templates');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/contracts/templates');
        }

        try {
            $oldData = $template->toArray();
            $nome = $template->nome;
            
            $template->delete();

            SistemaLog::registrar(
                'contract_templates',
                'DELETE',
                $params['id'],
                "Template deletado: {$nome}",
                $oldData,
                null
            );

            session()->flash('success', 'Template deletado com sucesso!');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao deletar template: ' . $e->getMessage());
        }

        $this->redirect('/contracts/templates');
    }
}

