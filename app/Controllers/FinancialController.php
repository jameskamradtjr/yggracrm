<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\FinancialEntry;
use App\Models\BankAccount;
use App\Models\CreditCard;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\CostCenter;
use App\Models\SubCostCenter;
use App\Models\Tag;
use App\Models\Supplier;

/**
 * Controller Financeiro
 * 
 * Gerencia lançamentos, contas bancárias, cartões de crédito
 */
class FinancialController extends Controller
{
    /**
     * Lista todos os lançamentos com filtros
     */
    public function index(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        
        // Filtros
        $type = $this->request->query('type', 'all'); // all, entrada, saida, transferencia
        $status = $this->request->query('status', 'all'); // all, paid, unpaid
        $accountId = $this->request->query('account_id');
        $categoryId = $this->request->query('category_id');
        $costCenterId = $this->request->query('cost_center_id');
        $tagId = $this->request->query('tag_id');
        $search = $this->request->query('search');
        
        // Período
        $period = $this->request->query('period', 'month'); // today, week, month, custom
        $startDate = $this->request->query('start_date');
        $endDate = $this->request->query('end_date');
        
        // Define datas do período
        if ($period === 'today') {
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d');
        } elseif ($period === 'week') {
            $startDate = date('Y-m-d', strtotime('monday this week'));
            $endDate = date('Y-m-d', strtotime('sunday this week'));
        } elseif ($period === 'month') {
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t');
        } elseif ($period === 'custom' && $startDate && $endDate) {
            // Usa as datas fornecidas
        } else {
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t');
        }
        
        // Query base usando Database diretamente para mais controle
        $db = \Core\Database::getInstance();
        $whereConditions = ["`user_id` = ?"];
        $params = [$userId];
        
        $whereConditions[] = "`competence_date` >= ?";
        $params[] = $startDate;
        
        $whereConditions[] = "`competence_date` <= ?";
        $params[] = $endDate;
        
        // Aplica filtros
        if ($type !== 'all') {
            $whereConditions[] = "`type` = ?";
            $params[] = $type;
        }
        
        if ($status === 'paid') {
            $whereConditions[] = "(`is_paid` = 1 OR `is_received` = 1)";
        } elseif ($status === 'unpaid') {
            $whereConditions[] = "(`is_paid` = 0 OR `is_received` = 0)";
        }
        
        if ($accountId) {
            $whereConditions[] = "`bank_account_id` = ?";
            $params[] = $accountId;
        }
        
        if ($categoryId) {
            $whereConditions[] = "`category_id` = ?";
            $params[] = $categoryId;
        }
        
        if ($costCenterId) {
            $whereConditions[] = "`cost_center_id` = ?";
            $params[] = $costCenterId;
        }
        
        if ($search) {
            $whereConditions[] = "`description` LIKE ?";
            $params[] = "%{$search}%";
        }
        
        $whereClause = "WHERE " . implode(' AND ', $whereConditions);
        
        // Executa query
        $sql = "SELECT * FROM `financial_entries` {$whereClause} ORDER BY `competence_date` DESC, `id` DESC";
        $results = $db->query($sql, $params);
        
        // Converte para objetos FinancialEntry
        $entries = array_map(function($row) {
            return FinancialEntry::newInstance($row, true);
        }, $results);
        
        // Filtra por tag se necessário
        if ($tagId) {
            $entries = array_filter($entries, function($entry) use ($tagId) {
                $tags = $entry->getTags();
                foreach ($tags as $tag) {
                    if ($tag['id'] == $tagId) {
                        return true;
                    }
                }
                return false;
            });
        }
        
        // Dados para os filtros
        $bankAccounts = BankAccount::where('user_id', $userId)->get();
        $creditCards = CreditCard::where('user_id', $userId)->get();
        $categories = Category::where('user_id', $userId)->get();
        $costCenters = CostCenter::where('user_id', $userId)->get();
        $tags = Tag::where('user_id', $userId)->get();
        
        return $this->view('financial/index', [
            'title' => 'Financeiro - Lançamentos',
            'entries' => $entries,
            'bankAccounts' => $bankAccounts,
            'creditCards' => $creditCards,
            'categories' => $categories,
            'costCenters' => $costCenters,
            'tags' => $tags,
            'filters' => [
                'type' => $type,
                'status' => $status,
                'account_id' => $accountId,
                'category_id' => $categoryId,
                'cost_center_id' => $costCenterId,
                'tag_id' => $tagId,
                'search' => $search,
                'period' => $period,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ]);
    }
    
    /**
     * Exibe formulário de criação de lançamento
     */
    public function create(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $type = $this->request->query('type', 'saida'); // entrada, saida, transferencia
        
        return $this->view('financial/create', [
            'title' => 'Novo Lançamento',
            'type' => $type,
            'bankAccounts' => BankAccount::where('user_id', $userId)->get(),
            'creditCards' => CreditCard::where('user_id', $userId)->get(),
            'suppliers' => Supplier::where('user_id', $userId)->get(),
            'categories' => Category::where('user_id', $userId)
                ->where('type', $type === 'entrada' ? 'entrada' : 'saida')
                ->get(),
            'costCenters' => CostCenter::where('user_id', $userId)->get(),
            'tags' => Tag::where('user_id', $userId)->get()
        ]);
    }
    
    /**
     * Exibe formulário de edição de lançamento
     */
    public function edit(array $params): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $entry = FinancialEntry::find($params['id']);
        
        if (!$entry || $entry->user_id != $userId) {
            session()->flash('error', 'Lançamento não encontrado.');
            $this->redirect('/financial');
        }
        
        // Busca tags do lançamento
        $entryTags = $entry->getTags();
        $entryTagIds = array_column($entryTags, 'id');
        
        return $this->view('financial/edit', [
            'title' => 'Editar Lançamento',
            'entry' => $entry,
            'type' => $entry->type,
            'bankAccounts' => BankAccount::where('user_id', $userId)->get(),
            'creditCards' => CreditCard::where('user_id', $userId)->get(),
            'suppliers' => Supplier::where('user_id', $userId)->get(),
            'categories' => Category::where('user_id', $userId)
                ->where('type', $entry->type === 'entrada' ? 'entrada' : 'saida')
                ->get(),
            'subcategories' => $entry->category_id 
                ? Subcategory::where('category_id', $entry->category_id)->where('user_id', $userId)->get()
                : [],
            'costCenters' => CostCenter::where('user_id', $userId)->get(),
            'subCostCenters' => $entry->cost_center_id
                ? SubCostCenter::where('cost_center_id', $entry->cost_center_id)->where('user_id', $userId)->get()
                : [],
            'tags' => Tag::where('user_id', $userId)->get(),
            'entryTagIds' => $entryTagIds
        ]);
    }
    
    /**
     * Atualiza lançamento
     */
    public function update(array $params): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/financial');
        }

        $userId = auth()->getDataUserId();
        $entry = FinancialEntry::find($params['id']);
        
        if (!$entry || $entry->user_id != $userId) {
            session()->flash('error', 'Lançamento não encontrado.');
            $this->redirect('/financial');
        }

        $data = $this->validate([
            'type' => 'required|in:entrada,saida,transferencia',
            'description' => 'required',
            'value' => 'required|numeric|min:0.01',
            'competence_date' => 'required|date',
            'due_date' => 'nullable|date',
            'bank_account_id' => 'nullable|integer',
            'credit_card_id' => 'nullable|integer',
            'supplier_id' => 'nullable|integer',
            'client_id' => 'nullable|integer',
            'category_id' => 'nullable|integer',
            'subcategory_id' => 'nullable|integer',
            'cost_center_id' => 'nullable|integer',
            'sub_cost_center_id' => 'nullable|integer',
            'observations' => 'nullable',
            'release_date' => 'nullable|date'
        ]);

        try {
            // Prepara dados do lançamento
            $entryData = [
                'type' => $data['type'],
                'description' => $data['description'],
                'value' => $data['value'],
                'competence_date' => $data['competence_date'],
                'due_date' => $data['due_date'] ?? $data['competence_date'],
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'credit_card_id' => $data['credit_card_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'client_id' => $data['client_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'subcategory_id' => $data['subcategory_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'sub_cost_center_id' => $data['sub_cost_center_id'] ?? null,
                'observations' => $data['observations'] ?? null,
                'release_date' => $data['release_date'] ?? null
            ];
            
            // Se for cartão de crédito, calcula a data de competência baseada no fechamento
            if ($data['credit_card_id']) {
                $card = CreditCard::find($data['credit_card_id']);
                if ($card) {
                    $closingDay = $card->closing_day;
                    $competenceDate = new \DateTime($data['competence_date']);
                    $day = (int) $competenceDate->format('d');
                    
                    // Se passou do dia de fechamento, vai para o próximo mês
                    if ($day >= $closingDay) {
                        $competenceDate->modify('first day of next month');
                    } else {
                        $competenceDate->modify('first day of this month');
                    }
                    
                    $entryData['competence_date'] = $competenceDate->format('Y-m-d');
                }
            }
            
            // Atualiza o lançamento
            $entry->update($entryData);
            
            // Remove todas as tags antigas
            $entry->removeAllTags();
            
            // Adiciona novas tags
            $tags = $this->request->input('tags', []);
            if (is_array($tags)) {
                foreach ($tags as $tagId) {
                    if (is_numeric($tagId)) {
                        $entry->addTag((int) $tagId);
                    } elseif (is_string($tagId) && !empty(trim($tagId))) {
                        // Cria nova tag se não existir
                        $tag = Tag::where('name', trim($tagId))
                            ->where('user_id', $userId)
                            ->first();
                        
                        if (!$tag) {
                            $tag = Tag::create([
                                'name' => trim($tagId),
                                'user_id' => $userId
                            ]);
                        }
                        
                        $entry->addTag($tag->id);
                    }
                }
            }
            
            session()->flash('success', 'Lançamento atualizado com sucesso!');
            $this->redirect('/financial');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao atualizar lançamento: ' . $e->getMessage());
            $this->redirect('/financial/' . $params['id'] . '/edit');
        }
    }
    
    /**
     * Salva novo lançamento
     */
    public function store(): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/financial');
        }

        $data = $this->validate([
            'type' => 'required|in:entrada,saida,transferencia',
            'description' => 'required',
            'value' => 'required|numeric|min:0.01',
            'competence_date' => 'required|date',
            'due_date' => 'nullable|date',
            'bank_account_id' => 'nullable|integer',
            'credit_card_id' => 'nullable|integer',
            'supplier_id' => 'nullable|integer',
            'category_id' => 'nullable|integer',
            'subcategory_id' => 'nullable|integer',
            'cost_center_id' => 'nullable|integer',
            'sub_cost_center_id' => 'nullable|integer',
            'observations' => 'nullable',
            'is_recurring' => 'nullable|boolean',
            'recurrence_type' => 'nullable|in:mensal,semanal,diario,anual',
            'is_installment' => 'nullable|boolean',
            'total_installments' => 'nullable|integer|min:1',
            'release_date' => 'nullable|date'
        ]);

        try {
            $userId = auth()->getDataUserId();
            
            // Prepara dados do lançamento
            $entryData = [
                'type' => $data['type'],
                'description' => $data['description'],
                'value' => $data['value'],
                'competence_date' => $data['competence_date'],
                'due_date' => $data['due_date'] ?? $data['competence_date'],
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'credit_card_id' => $data['credit_card_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'subcategory_id' => $data['subcategory_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'sub_cost_center_id' => $data['sub_cost_center_id'] ?? null,
                'observations' => $data['observations'] ?? null,
                'is_recurring' => $data['is_recurring'] ?? false,
                'recurrence_type' => $data['recurrence_type'] ?? null,
                'is_installment' => $data['is_installment'] ?? false,
                'total_installments' => $data['total_installments'] ?? null,
                'release_date' => $data['release_date'] ?? null,
                'responsible_user_id' => $userId,
                'user_id' => $userId
            ];
            
            // Se for cartão de crédito, calcula a data de competência baseada no fechamento
            if ($data['credit_card_id']) {
                $card = CreditCard::find($data['credit_card_id']);
                if ($card) {
                    $closingDay = $card->closing_day;
                    $competenceDate = new \DateTime($data['competence_date']);
                    $day = (int) $competenceDate->format('d');
                    
                    // Se passou do dia de fechamento, vai para o próximo mês
                    if ($day >= $closingDay) {
                        $competenceDate->modify('first day of next month');
                    } else {
                        $competenceDate->modify('first day of this month');
                    }
                    
                    $entryData['competence_date'] = $competenceDate->format('Y-m-d');
                }
            }
            
            // Se já está pago/recebido
            if ($this->request->input('is_paid') || $this->request->input('is_received')) {
                $entryData['is_paid'] = $data['type'] === 'saida';
                $entryData['is_received'] = $data['type'] === 'entrada';
                $entryData['payment_date'] = $this->request->input('payment_date') ?? date('Y-m-d');
                $entryData['paid_value'] = $data['value'];
            }
            
            // Cria o lançamento
            $entry = FinancialEntry::create($entryData);
            
            // Adiciona tags
            $tags = $this->request->input('tags', []);
            if (is_array($tags)) {
                foreach ($tags as $tagId) {
                    if (is_numeric($tagId)) {
                        $entry->addTag((int) $tagId);
                    } elseif (is_string($tagId) && !empty(trim($tagId))) {
                        // Cria nova tag se não existir
                        $tag = Tag::where('name', trim($tagId))
                            ->where('user_id', $userId)
                            ->first();
                        
                        if (!$tag) {
                            $tag = Tag::create([
                                'name' => trim($tagId),
                                'user_id' => $userId
                            ]);
                        }
                        
                        $entry->addTag($tag->id);
                    }
                }
            }
            
            // Se for recorrente, cria os próximos lançamentos
            if ($entry->is_recurring && $entry->recurrence_type) {
                $this->createRecurringEntries($entry);
            }
            
            // Se for parcelado, cria as parcelas
            if ($entry->is_installment && $entry->total_installments > 1) {
                $this->createInstallments($entry);
            }
            
            session()->flash('success', 'Lançamento cadastrado com sucesso!');
            $this->redirect('/financial');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao cadastrar lançamento: ' . $e->getMessage());
            $this->redirect('/financial/create?type=' . $data['type']);
        }
    }
    
    /**
     * Cria lançamentos recorrentes
     */
    private function createRecurringEntries(FinancialEntry $parent): void
    {
        $recurrenceType = $parent->recurrence_type;
        $endDate = $parent->recurrence_end_date ?? date('Y-m-d', strtotime('+1 year'));
        
        $currentDate = new \DateTime($parent->competence_date);
        $endDateTime = new \DateTime($endDate);
        
        $interval = match($recurrenceType) {
            'mensal' => new \DateInterval('P1M'),
            'semanal' => new \DateInterval('P1W'),
            'diario' => new \DateInterval('P1D'),
            'anual' => new \DateInterval('P1Y'),
            default => new \DateInterval('P1M')
        };
        
        $count = 0;
        $maxEntries = 120; // Limite de segurança
        
        while ($currentDate <= $endDateTime && $count < $maxEntries) {
            $currentDate->add($interval);
            
            if ($currentDate > $endDateTime) {
                break;
            }
            
            $entryData = $parent->toArray();
            unset($entryData['id'], $entryData['created_at'], $entryData['updated_at']);
            
            $entryData['competence_date'] = $currentDate->format('Y-m-d');
            $entryData['due_date'] = $currentDate->format('Y-m-d');
            $entryData['parent_entry_id'] = $parent->id;
            $entryData['is_paid'] = false;
            $entryData['is_received'] = false;
            $entryData['payment_date'] = null;
            $entryData['paid_value'] = null;
            
            FinancialEntry::create($entryData);
            $count++;
        }
    }
    
    /**
     * Cria parcelas de um lançamento
     */
    private function createInstallments(FinancialEntry $parent): void
    {
        $totalInstallments = $parent->total_installments;
        $valuePerInstallment = $parent->value / $totalInstallments;
        
        $currentDate = new \DateTime($parent->competence_date);
        
        for ($i = 2; $i <= $totalInstallments; $i++) {
            $currentDate->modify('+1 month');
            
            $entryData = $parent->toArray();
            unset($entryData['id'], $entryData['created_at'], $entryData['updated_at']);
            
            $entryData['competence_date'] = $currentDate->format('Y-m-d');
            $entryData['due_date'] = $currentDate->format('Y-m-d');
            $entryData['value'] = $valuePerInstallment;
            $entryData['parent_entry_id'] = $parent->id;
            $entryData['installment_number'] = $i;
            $entryData['is_paid'] = false;
            $entryData['is_received'] = false;
            $entryData['payment_date'] = null;
            $entryData['paid_value'] = null;
            
            FinancialEntry::create($entryData);
        }
        
        // Atualiza o lançamento pai
        $parent->update([
            'installment_number' => 1,
            'value' => $valuePerInstallment
        ]);
    }
    
    /**
     * Marca lançamento como pago/recebido
     */
    public function markAsPaid(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        $entry = FinancialEntry::find($params['id']);
        
        if (!$entry) {
            json_response(['success' => false, 'message' => 'Lançamento não encontrado'], 404);
        }

        $paymentDate = $this->request->input('payment_date') ?? date('Y-m-d');
        $paidValue = $this->request->input('paid_value') ?? $entry->value;
        $fees = $this->request->input('fees', 0);
        $interest = $this->request->input('interest', 0);
        
        $updateData = [
            'payment_date' => $paymentDate,
            'paid_value' => $paidValue,
            'fees' => $fees,
            'interest' => $interest
        ];
        
        if ($entry->type === 'saida') {
            $updateData['is_paid'] = true;
        } else {
            $updateData['is_received'] = true;
        }
        
        $entry->update($updateData);
        
        json_response([
            'success' => true,
            'message' => 'Lançamento atualizado com sucesso'
        ]);
    }
    
    /**
     * Desmarca lançamento como pago/recebido
     */
    public function unmarkAsPaid(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        $entry = FinancialEntry::find($params['id']);
        
        if (!$entry) {
            json_response(['success' => false, 'message' => 'Lançamento não encontrado'], 404);
        }

        $entry->unmarkAsPaid();
        
        json_response([
            'success' => true,
            'message' => 'Lançamento atualizado com sucesso'
        ]);
    }
    
    // ==================== CONTAS BANCÁRIAS ====================
    
    /**
     * Lista contas bancárias
     */
    public function bankAccounts(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $accounts = BankAccount::where('user_id', $userId)->get();
        
        return $this->view('financial/bank_accounts/index', [
            'title' => 'Contas Bancárias',
            'accounts' => $accounts
        ]);
    }
    
    /**
     * Exibe formulário de criação de conta
     */
    public function createBankAccount(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        return $this->view('financial/bank_accounts/create', [
            'title' => 'Nova Conta Bancária'
        ]);
    }
    
    /**
     * Salva nova conta bancária
     */
    public function storeBankAccount(): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/financial/bank-accounts');
        }

        $data = $this->validate([
            'name' => 'required',
            'type' => 'required|in:conta_corrente,conta_poupanca,conta_investimento,carteira_digital,outros',
            'bank_name' => 'required',
            'account_number' => 'nullable',
            'agency' => 'nullable',
            'digit' => 'nullable',
            'initial_balance' => 'nullable|numeric',
            'hide_balance' => 'nullable|boolean',
            'alert_email' => 'nullable|email',
            'alert_when_zero' => 'nullable|boolean'
        ]);

        try {
            $userId = auth()->getDataUserId();
            
            $account = BankAccount::create([
                'name' => $data['name'],
                'type' => $data['type'],
                'bank_name' => $data['bank_name'],
                'account_number' => $data['account_number'] ?? null,
                'agency' => $data['agency'] ?? null,
                'digit' => $data['digit'] ?? null,
                'initial_balance' => $data['initial_balance'] ?? 0,
                'current_balance' => $data['initial_balance'] ?? 0,
                'hide_balance' => $data['hide_balance'] ?? false,
                'alert_email' => $data['alert_email'] ?? null,
                'alert_when_zero' => $data['alert_when_zero'] ?? false,
                'user_id' => $userId
            ]);
            
            session()->flash('success', 'Conta bancária cadastrada com sucesso!');
            $this->redirect('/financial/bank-accounts');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao cadastrar conta: ' . $e->getMessage());
            $this->redirect('/financial/bank-accounts/create');
        }
    }
    
    // ==================== CARTÕES DE CRÉDITO ====================
    
    /**
     * Lista cartões de crédito
     */
    public function creditCards(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $cards = CreditCard::where('user_id', $userId)->get();
        
        // Calcula gastos de cada cartão
        foreach ($cards as $card) {
            $card->current_spent = $card->getCurrentSpent();
            $card->available_limit = $card->getAvailableLimit();
        }
        
        return $this->view('financial/credit_cards/index', [
            'title' => 'Cartões de Crédito',
            'cards' => $cards
        ]);
    }
    
    /**
     * Exibe formulário de criação de cartão
     */
    public function createCreditCard(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $bankAccounts = BankAccount::where('user_id', $userId)->get();
        
        return $this->view('financial/credit_cards/create', [
            'title' => 'Novo Cartão de Crédito',
            'bankAccounts' => $bankAccounts
        ]);
    }
    
    /**
     * Salva novo cartão de crédito
     */
    public function storeCreditCard(): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/financial/credit-cards');
        }

        $data = $this->validate([
            'name' => 'required',
            'brand' => 'required|in:visa,mastercard,elo,amex,hipercard,outros',
            'bank_account_id' => 'nullable|integer',
            'closing_day' => 'required|integer|min:1|max:31',
            'due_day' => 'required|integer|min:1|max:31',
            'limit' => 'required|numeric|min:0',
            'alert_limit' => 'nullable|boolean',
            'alert_percentage' => 'nullable|integer|min:1|max:100'
        ]);

        try {
            $userId = auth()->getDataUserId();
            
            $card = CreditCard::create([
                'name' => $data['name'],
                'brand' => $data['brand'],
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'closing_day' => $data['closing_day'],
                'due_day' => $data['due_day'],
                'limit' => $data['limit'],
                'alert_limit' => $data['alert_limit'] ?? false,
                'alert_percentage' => $data['alert_percentage'] ?? 90,
                'user_id' => $userId
            ]);
            
            session()->flash('success', 'Cartão de crédito cadastrado com sucesso!');
            $this->redirect('/financial/credit-cards');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao cadastrar cartão: ' . $e->getMessage());
            $this->redirect('/financial/credit-cards/create');
        }
    }
    
    // ==================== CATEGORIAS ====================
    
    /**
     * Lista categorias
     */
    public function categories(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $categories = Category::where('user_id', $userId)->orderBy('type')->orderBy('name')->get();
        
        // Busca todas as subcategorias de uma vez
        $db = \Core\Database::getInstance();
        $allSubcategories = $db->query(
            "SELECT * FROM subcategories WHERE user_id = ?",
            [$userId]
        );
        
        // Organiza subcategorias por category_id
        $subcategoriesByCategory = [];
        if ($allSubcategories) {
            foreach ($allSubcategories as $sub) {
                $catId = (int) $sub['category_id'];
                if (!isset($subcategoriesByCategory[$catId])) {
                    $subcategoriesByCategory[$catId] = [];
                }
                $subcategoriesByCategory[$catId][] = $sub;
            }
        }
        
        // Agrupa por tipo e adiciona subcategorias
        $categoriesByType = [
            'entrada' => [],
            'saida' => [],
            'outros' => []
        ];
        
        foreach ($categories as $category) {
            $type = $category->type ?? 'outros';
            
            // Cria um objeto stdClass com os dados da categoria + subcategorias
            $catData = new \stdClass();
            $catData->id = $category->id;
            $catData->name = $category->name;
            $catData->type = $category->type;
            $catData->color = $category->color;
            $catId = (int) $category->id;
            $catData->subcategories = $subcategoriesByCategory[$catId] ?? [];
            
            $categoriesByType[$type][] = $catData;
        }
        
        return $this->view('financial/categories/index', [
            'title' => 'Categorias Financeiras',
            'categoriesByType' => $categoriesByType
        ]);
    }
    
    /**
     * Exibe formulário de criação de categoria
     */
    public function createCategory(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        return $this->view('financial/categories/create', [
            'title' => 'Nova Categoria'
        ]);
    }
    
    /**
     * Salva nova categoria
     */
    public function storeCategory(): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/financial/categories');
        }

        $data = $this->validate([
            'name' => 'required',
            'type' => 'required|in:entrada,saida,outros',
            'color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/'
        ]);

        try {
            $userId = auth()->getDataUserId();
            
            Category::create([
                'name' => $data['name'],
                'type' => $data['type'],
                'color' => $data['color'] ?? null,
                'user_id' => $userId
            ]);
            
            session()->flash('success', 'Categoria cadastrada com sucesso!');
            $this->redirect('/financial/categories');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao cadastrar categoria: ' . $e->getMessage());
            $this->redirect('/financial/categories/create');
        }
    }
    
    /**
     * Exibe formulário de edição de categoria
     */
    public function editCategory(array $params): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $category = Category::find($params['id']);
        
        if (!$category || $category->user_id != $userId) {
            session()->flash('error', 'Categoria não encontrada.');
            $this->redirect('/financial/categories');
        }

        return $this->view('financial/categories/edit', [
            'title' => 'Editar Categoria',
            'category' => $category
        ]);
    }
    
    /**
     * Atualiza categoria
     */
    public function updateCategory(array $params): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/financial/categories');
        }

        $userId = auth()->getDataUserId();
        $category = Category::find($params['id']);
        
        if (!$category || $category->user_id != $userId) {
            session()->flash('error', 'Categoria não encontrada.');
            $this->redirect('/financial/categories');
        }

        $data = $this->validate([
            'name' => 'required',
            'type' => 'required|in:entrada,saida,outros',
            'color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/'
        ]);

        try {
            $category->update([
                'name' => $data['name'],
                'type' => $data['type'],
                'color' => $data['color'] ?? null
            ]);
            
            session()->flash('success', 'Categoria atualizada com sucesso!');
            $this->redirect('/financial/categories');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao atualizar categoria: ' . $e->getMessage());
            $this->redirect('/financial/categories/' . $params['id'] . '/edit');
        }
    }
    
    /**
     * Exclui categoria
     */
    public function deleteCategory(array $params): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/financial/categories');
        }

        $userId = auth()->getDataUserId();
        $category = Category::find($params['id']);
        
        if (!$category || $category->user_id != $userId) {
            session()->flash('error', 'Categoria não encontrada.');
            $this->redirect('/financial/categories');
        }

        try {
            // Exclui todas as subcategorias primeiro
            $subcategories = Subcategory::where('category_id', $category->id)->get();
            foreach ($subcategories as $subcategory) {
                $subcategory->delete();
            }
            
            // Exclui a categoria
            $category->delete();
            
            session()->flash('success', 'Categoria e suas subcategorias foram excluídas com sucesso!');
            $this->redirect('/financial/categories');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao excluir categoria: ' . $e->getMessage());
            $this->redirect('/financial/categories');
        }
    }
    
    /**
     * Salva nova subcategoria
     */
    public function storeSubcategory(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
        }

        $data = $this->validate([
            'name' => 'required',
            'category_id' => 'required|integer'
        ]);

        try {
            $userId = auth()->getDataUserId();
            
            $subcategory = Subcategory::create([
                'name' => $data['name'],
                'category_id' => (int) $data['category_id'],
                'user_id' => $userId
            ]);
            
            if (!$subcategory || !$subcategory->id) {
                throw new \Exception('Falha ao criar subcategoria');
            }
            
            json_response([
                'success' => true,
                'message' => 'Subcategoria cadastrada com sucesso!',
                'subcategory' => $subcategory->toArray()
            ]);
            
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao cadastrar subcategoria: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Atualiza subcategoria
     */
    public function updateSubcategory(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
        }

        $data = $this->validate([
            'id' => 'required|integer',
            'name' => 'required'
        ]);

        try {
            $userId = auth()->getDataUserId();
            $subcategory = Subcategory::find($data['id']);
            
            if (!$subcategory || $subcategory->user_id != $userId) {
                throw new \Exception('Subcategoria não encontrada');
            }
            
            $subcategory->update([
                'name' => $data['name']
            ]);
            
            json_response([
                'success' => true,
                'message' => 'Subcategoria atualizada com sucesso!'
            ]);
            
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao atualizar subcategoria: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Exclui subcategoria
     */
    public function deleteSubcategory(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        // Pega dados do POST (FormData)
        $csrfToken = $_POST['_csrf_token'] ?? $this->request->input('_csrf_token') ?? null;
        $id = $_POST['id'] ?? $this->request->input('id') ?? null;

        if (!$csrfToken || !verify_csrf($csrfToken)) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
            return;
        }

        // Validação manual para requisições AJAX
        if (empty($id) || !is_numeric($id)) {
            json_response(['success' => false, 'message' => 'ID inválido'], 400);
            return;
        }

        try {
            $userId = auth()->getDataUserId();
            $subcategoryId = (int)$id;
            
            // Busca a subcategoria usando where para ter mais controle
            $subcategory = Subcategory::where('id', $subcategoryId)
                ->where('user_id', $userId)
                ->first();
            
            if (!$subcategory) {
                json_response(['success' => false, 'message' => 'Subcategoria não encontrada ou você não tem permissão para excluí-la'], 404);
                return;
            }
            
            // Exclui a subcategoria
            $result = $subcategory->delete();
            
            if ($result) {
                json_response([
                    'success' => true,
                    'message' => 'Subcategoria excluída com sucesso!'
                ]);
            } else {
                json_response([
                    'success' => false,
                    'message' => 'Erro ao excluir subcategoria'
                ], 500);
            }
            
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao excluir subcategoria: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Retorna informações da subcategoria (para edição)
     */
    public function getSubcategoryInfo(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        $id = $this->request->query('id');
        if (!$id) {
            json_response(['success' => false, 'message' => 'ID não fornecido'], 400);
        }

        try {
            $userId = auth()->getDataUserId();
            $subcategory = Subcategory::find($id);
            
            if (!$subcategory || $subcategory->user_id != $userId) {
                throw new \Exception('Subcategoria não encontrada');
            }
            
            $category = Category::find($subcategory->category_id);
            
            json_response([
                'success' => true,
                'category_id' => $subcategory->category_id,
                'category_name' => $category ? $category->name : '',
                'name' => $subcategory->name
            ]);
            
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // ==================== CENTROS DE CUSTO ====================
    
    /**
     * Lista centros de custo
     */
    public function costCenters(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $costCenters = CostCenter::where('user_id', $userId)->orderBy('name')->get();
        
        // Carrega subcentros para cada centro
        foreach ($costCenters as $costCenter) {
            $costCenter->subCostCenters = SubCostCenter::where('cost_center_id', $costCenter->id)
                ->where('user_id', $userId)
                ->get();
        }
        
        return $this->view('financial/cost_centers/index', [
            'title' => 'Centros de Custo',
            'costCenters' => $costCenters
        ]);
    }
    
    /**
     * Exibe formulário de criação de centro de custo
     */
    public function createCostCenter(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        return $this->view('financial/cost_centers/create', [
            'title' => 'Novo Centro de Custo'
        ]);
    }
    
    /**
     * Salva novo centro de custo
     */
    public function storeCostCenter(): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/financial/cost-centers');
        }

        $data = $this->validate([
            'name' => 'required'
        ]);

        try {
            $userId = auth()->getDataUserId();
            
            CostCenter::create([
                'name' => $data['name'],
                'user_id' => $userId
            ]);
            
            session()->flash('success', 'Centro de custo cadastrado com sucesso!');
            $this->redirect('/financial/cost-centers');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao cadastrar centro de custo: ' . $e->getMessage());
            $this->redirect('/financial/cost-centers/create');
        }
    }
    
    /**
     * Salva novo subcentro de custo
     */
    public function storeSubCostCenter(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
        }

        $data = $this->validate([
            'name' => 'required',
            'cost_center_id' => 'required|integer'
        ]);

        try {
            $userId = auth()->getDataUserId();
            
            SubCostCenter::create([
                'name' => $data['name'],
                'cost_center_id' => $data['cost_center_id'],
                'user_id' => $userId
            ]);
            
            json_response([
                'success' => true,
                'message' => 'Subcentro de custo cadastrado com sucesso!'
            ]);
            
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao cadastrar subcentro: ' . $e->getMessage()
            ], 500);
        }
    }
}

