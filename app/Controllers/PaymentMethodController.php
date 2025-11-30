<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\PaymentMethod;
use App\Models\SistemaLog;

class PaymentMethodController extends Controller
{
    /**
     * Lista formas de pagamento
     */
    public function index(): string
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $paymentMethods = PaymentMethod::where('user_id', $userId)
            ->orderBy('nome', 'ASC')
            ->get();

        return $this->view('financial/payment-methods/index', [
            'title' => 'Formas de Pagamento',
            'paymentMethods' => $paymentMethods
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
        
        // Busca contas bancárias para o select
        $accounts = \App\Models\BankAccount::where('user_id', $userId)
            ->orderBy('name', 'ASC')
            ->get();

        return $this->view('financial/payment-methods/create', [
            'title' => 'Nova Forma de Pagamento',
            'accounts' => $accounts
        ]);
    }

    /**
     * Salva nova forma de pagamento
     */
    public function store(): void
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/financial/payment-methods/create');
        }

        $data = $this->validate([
            'nome' => 'required|min:2|max:255',
            'tipo' => 'required|in:pix,boleto,credito,debito,transferencia,dinheiro,outro',
            'taxa' => 'nullable|numeric|min:0|max:100',
            'conta_id' => 'nullable|numeric',
            'dias_recebimento' => 'nullable|integer|min:0',
            'adicionar_taxa_como_despesa' => 'nullable|boolean',
            'ativo' => 'nullable|boolean',
            'observacoes' => 'nullable'
        ]);

        try {
            $paymentMethod = PaymentMethod::create([
                'user_id' => auth()->getDataUserId(),
                'nome' => $data['nome'],
                'tipo' => $data['tipo'],
                'taxa' => $data['taxa'] ?? 0.00,
                'conta_id' => !empty($data['conta_id']) ? (int)$data['conta_id'] : null,
                'dias_recebimento' => $data['dias_recebimento'] ?? 0,
                'adicionar_taxa_como_despesa' => isset($data['adicionar_taxa_como_despesa']) ? (bool)$data['adicionar_taxa_como_despesa'] : false,
                'ativo' => isset($data['ativo']) ? (bool)$data['ativo'] : true,
                'observacoes' => $data['observacoes'] ?? null
            ]);

            SistemaLog::registrar(
                'payment_methods',
                'CREATE',
                $paymentMethod->id,
                "Forma de pagamento criada: {$paymentMethod->nome}",
                null,
                $paymentMethod->toArray()
            );

            session()->flash('success', 'Forma de pagamento criada com sucesso!');
            $this->redirect('/financial/payment-methods');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao criar forma de pagamento: ' . $e->getMessage());
            $this->redirect('/financial/payment-methods/create');
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

        $paymentMethod = PaymentMethod::find($params['id']);

        if (!$paymentMethod || $paymentMethod->user_id !== auth()->getDataUserId()) {
            session()->flash('error', 'Forma de pagamento não encontrada.');
            $this->redirect('/financial/payment-methods');
        }

        $userId = auth()->getDataUserId();
        $accounts = \App\Models\BankAccount::where('user_id', $userId)
            ->orderBy('name', 'ASC')
            ->get();

        return $this->view('financial/payment-methods/edit', [
            'title' => 'Editar Forma de Pagamento',
            'paymentMethod' => $paymentMethod,
            'accounts' => $accounts
        ]);
    }

    /**
     * Atualiza forma de pagamento
     */
    public function update(array $params): void
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        $paymentMethod = PaymentMethod::find($params['id']);

        if (!$paymentMethod || $paymentMethod->user_id !== auth()->getDataUserId()) {
            session()->flash('error', 'Forma de pagamento não encontrada.');
            $this->redirect('/financial/payment-methods');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/financial/payment-methods/' . $paymentMethod->id . '/edit');
        }

        $data = $this->validate([
            'nome' => 'required|min:2|max:255',
            'tipo' => 'required|in:pix,boleto,credito,debito,transferencia,dinheiro,outro',
            'taxa' => 'nullable|numeric|min:0|max:100',
            'conta_id' => 'nullable|numeric',
            'dias_recebimento' => 'nullable|integer|min:0',
            'adicionar_taxa_como_despesa' => 'nullable|boolean',
            'ativo' => 'nullable|boolean',
            'observacoes' => 'nullable'
        ]);

        try {
            $oldData = $paymentMethod->toArray();
            
            $paymentMethod->update([
                'nome' => $data['nome'],
                'tipo' => $data['tipo'],
                'taxa' => $data['taxa'] ?? 0.00,
                'conta_id' => !empty($data['conta_id']) ? (int)$data['conta_id'] : null,
                'dias_recebimento' => $data['dias_recebimento'] ?? 0,
                'adicionar_taxa_como_despesa' => isset($data['adicionar_taxa_como_despesa']) ? (bool)$data['adicionar_taxa_como_despesa'] : false,
                'ativo' => isset($data['ativo']) ? (bool)$data['ativo'] : true,
                'observacoes' => $data['observacoes'] ?? null
            ]);

            SistemaLog::registrar(
                'payment_methods',
                'UPDATE',
                $paymentMethod->id,
                "Forma de pagamento atualizada: {$paymentMethod->nome}",
                $oldData,
                $paymentMethod->toArray()
            );

            session()->flash('success', 'Forma de pagamento atualizada com sucesso!');
            $this->redirect('/financial/payment-methods');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao atualizar forma de pagamento: ' . $e->getMessage());
            $this->redirect('/financial/payment-methods/' . $paymentMethod->id . '/edit');
        }
    }

    /**
     * Deleta forma de pagamento
     */
    public function destroy(array $params): void
    {
        if (!auth()->check()) {
            redirect('/login');
        }

        $paymentMethod = PaymentMethod::find($params['id']);

        if (!$paymentMethod || $paymentMethod->user_id !== auth()->getDataUserId()) {
            session()->flash('error', 'Forma de pagamento não encontrada.');
            $this->redirect('/financial/payment-methods');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/financial/payment-methods');
        }

        try {
            $oldData = $paymentMethod->toArray();
            $nome = $paymentMethod->nome;
            
            $paymentMethod->delete();

            SistemaLog::registrar(
                'payment_methods',
                'DELETE',
                $params['id'],
                "Forma de pagamento deletada: {$nome}",
                $oldData,
                null
            );

            session()->flash('success', 'Forma de pagamento deletada com sucesso!');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao deletar forma de pagamento: ' . $e->getMessage());
        }

        $this->redirect('/financial/payment-methods');
    }
}

