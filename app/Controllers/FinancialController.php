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
use App\Models\PaymentMethod;
use App\Models\SistemaLog;
use App\Services\Automation\AutomationEventDispatcher;

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
        $tagsFilter = $this->request->query('tags'); // Recebe tags separadas por vírgula
        $search = $this->request->query('search');
        
        // Período
        $period = $this->request->query('period', 'month'); // today, week, month, custom
        $startDate = $this->request->query('start_date');
        $endDate = $this->request->query('end_date');
        
        // Define datas do período
        $useDateFilter = true;
        if ($period === 'today') {
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d');
        } elseif ($period === 'week') {
            $startDate = date('Y-m-d', strtotime('monday this week'));
            $endDate = date('Y-m-d', strtotime('sunday this week'));
        } elseif ($period === 'month') {
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t');
        } elseif ($period === 'custom') {
            if ($startDate && $endDate) {
                // Usa as datas fornecidas
            } else {
                // Se for custom mas não tiver datas, não filtra por data
                $useDateFilter = false;
            }
        } else {
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t');
        }
        
        // Query base usando Database diretamente para mais controle
        $db = \Core\Database::getInstance();
        $whereConditions = ["`user_id` = ?"];
        $params = [$userId];
        
        // Aplica filtro de data apenas se necessário
        if ($useDateFilter) {
            $whereConditions[] = "`competence_date` >= ?";
            $params[] = $startDate;
            
            $whereConditions[] = "`competence_date` <= ?";
            $params[] = $endDate;
        }
        
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
            // Verifica se é uma subcategoria (formato: "subcategory_{id}") ou categoria normal
            if (strpos($categoryId, 'subcategory_') === 0) {
                $subcategoryId = str_replace('subcategory_', '', $categoryId);
                $whereConditions[] = "`subcategory_id` = ?";
                $params[] = $subcategoryId;
            } else {
                $whereConditions[] = "`category_id` = ?";
                $params[] = $categoryId;
            }
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
        
        // Filtra por tags se necessário
        if ($tagsFilter) {
            // Separa as tags por vírgula e remove espaços
            $tagNames = array_map('trim', explode(',', $tagsFilter));
            $tagNames = array_filter($tagNames); // Remove valores vazios
            
            if (!empty($tagNames)) {
                $entries = array_filter($entries, function($entry) use ($tagNames) {
                    $entryTags = $entry->getTags();
                    $entryTagNames = array_map(function($tag) {
                        return strtolower(trim($tag['name']));
                    }, $entryTags);
                    
                    // Verifica se pelo menos uma das tags do filtro está presente no lançamento
                    foreach ($tagNames as $filterTagName) {
                        if (in_array(strtolower(trim($filterTagName)), $entryTagNames)) {
                            return true;
                        }
                    }
                    return false;
                });
            }
        }
        
        // Dados para os filtros
        $bankAccounts = BankAccount::where('user_id', $userId)->get();
        $creditCards = CreditCard::where('user_id', $userId)->get();
        
        // Busca categorias com suas subcategorias
        $categories = Category::where('user_id', $userId)->orderBy('name')->get();
        $allSubcategories = Subcategory::where('user_id', $userId)->get();
        
        // Organiza subcategorias por category_id
        $subcategoriesByCategory = [];
        foreach ($allSubcategories as $subcategory) {
            $catId = $subcategory->category_id;
            if (!isset($subcategoriesByCategory[$catId])) {
                $subcategoriesByCategory[$catId] = [];
            }
            $subcategoriesByCategory[$catId][] = $subcategory;
        }
        
        // Adiciona subcategorias às categorias
        foreach ($categories as $category) {
            $category->subcategories = $subcategoriesByCategory[$category->id] ?? [];
        }
        
        $costCenters = CostCenter::where('user_id', $userId)->get();
        $tags = Tag::where('user_id', $userId)->orderBy('name')->get();
        
        // Calcula totais baseado nos lançamentos filtrados
        $totalEntradas = 0;
        $totalSaidas = 0;
        
        foreach ($entries as $entry) {
            $value = (float)$entry->value;
            if ($entry->type === 'entrada') {
                $totalEntradas += $value;
            } elseif ($entry->type === 'saida') {
                $totalSaidas += $value;
            }
            // Transferências não entram no cálculo de totais
        }
        
        $totalGeral = $totalEntradas - $totalSaidas;
        
        return $this->view('financial/index', [
            'title' => 'Financeiro - Lançamentos',
            'entries' => $entries,
            'bankAccounts' => $bankAccounts,
            'creditCards' => $creditCards,
            'categories' => $categories,
            'costCenters' => $costCenters,
            'tags' => $tags,
            'totals' => [
                'entradas' => $totalEntradas,
                'saidas' => $totalSaidas,
                'geral' => $totalGeral
            ],
            'filters' => [
                'type' => $type,
                'status' => $status,
                'account_id' => $accountId,
                'category_id' => $categoryId,
                'cost_center_id' => $costCenterId,
                'tags' => $tagsFilter,
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
        
        // Busca categorias com suas subcategorias
        $categories = Category::where('user_id', $userId)
            ->where('type', $type === 'entrada' ? 'entrada' : 'saida')
            ->orderBy('name')
            ->get();
        
        // Busca todas as subcategorias de uma vez
        $allSubcategories = Subcategory::where('user_id', $userId)->get();
        
        // Organiza subcategorias por category_id
        $subcategoriesByCategory = [];
        foreach ($allSubcategories as $subcategory) {
            $catId = $subcategory->category_id;
            if (!isset($subcategoriesByCategory[$catId])) {
                $subcategoriesByCategory[$catId] = [];
            }
            $subcategoriesByCategory[$catId][] = $subcategory;
        }
        
        // Adiciona subcategorias às categorias
        foreach ($categories as $category) {
            $category->subcategories = $subcategoriesByCategory[$category->id] ?? [];
        }
        
        // Busca centros de custo com seus subcentros
        $costCenters = CostCenter::where('user_id', $userId)
            ->orderBy('name')
            ->get();
        
        // Busca todos os subcentros de uma vez
        $allSubCostCenters = SubCostCenter::where('user_id', $userId)->get();
        
        // Organiza subcentros por cost_center_id
        $subCostCentersByCostCenter = [];
        foreach ($allSubCostCenters as $subCostCenter) {
            $ccId = $subCostCenter->cost_center_id;
            if (!isset($subCostCentersByCostCenter[$ccId])) {
                $subCostCentersByCostCenter[$ccId] = [];
            }
            $subCostCentersByCostCenter[$ccId][] = $subCostCenter;
        }
        
        // Adiciona subcentros aos centros de custo
        foreach ($costCenters as $costCenter) {
            $costCenter->subCostCenters = $subCostCentersByCostCenter[$costCenter->id] ?? [];
        }
        
        return $this->view('financial/create', [
            'title' => 'Novo Lançamento',
            'type' => $type,
            'bankAccounts' => BankAccount::where('user_id', $userId)->get(),
            'creditCards' => CreditCard::where('user_id', $userId)->get(),
            'suppliers' => Supplier::where('user_id', $userId)->get(),
            'categories' => $categories,
            'costCenters' => $costCenters,
            'tags' => Tag::where('user_id', $userId)->get(),
            'paymentMethods' => PaymentMethod::where('user_id', $userId)
                ->where('ativo', true)
                ->orderBy('nome', 'ASC')
                ->get()
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
            'entryTags' => $entryTags ?: [],
            'entryTagIds' => $entryTagIds,
            'paymentMethods' => PaymentMethod::where('user_id', $userId)
                ->where('ativo', true)
                ->orderBy('nome', 'ASC')
                ->get()
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

        // Pega o valor do campo hidden (já vem numérico do JavaScript)
        $valueInput = $this->request->input('value');
        // Remove formatação de moeda se houver (fallback de segurança)
        if (is_string($valueInput)) {
            $valueInput = str_replace(['R$', ' ', '.'], '', $valueInput);
            $valueInput = str_replace(',', '.', $valueInput);
            // Atualiza o POST diretamente para usar o valor numérico
            $_POST['value'] = $valueInput;
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
            $tagsInput = $this->request->input('tags', '');
            
            // Se for string (separada por vírgula), converte para array
            if (is_string($tagsInput) && !empty(trim($tagsInput))) {
                $tagsArray = array_map('trim', explode(',', $tagsInput));
                $tagsArray = array_filter($tagsArray); // Remove valores vazios
            } elseif (is_array($tagsInput)) {
                $tagsArray = $tagsInput;
            } else {
                $tagsArray = [];
            }
            
            if (!empty($tagsArray)) {
                foreach ($tagsArray as $tagValue) {
                    $tagValue = trim($tagValue);
                    if (empty($tagValue)) {
                        continue;
                    }
                    
                    // Se for numérico, assume que é ID de tag existente
                    if (is_numeric($tagValue)) {
                        $entry->addTag((int) $tagValue);
                    } else {
                        // Se for string, busca ou cria a tag pelo nome
                        $tag = Tag::where('name', $tagValue)
                            ->where('user_id', $userId)
                            ->first();
                        
                        if (!$tag) {
                            $tag = Tag::create([
                                'name' => $tagValue,
                                'user_id' => $userId
                            ]);
                        }
                        
                        $entry->addTag($tag->id);
                    }
                }
            }
            
            // Registra log
            $dadosAnteriores = $entry->original ?? [];
            SistemaLog::registrar(
                'financial_entries',
                'UPDATE',
                $entry->id,
                "Lançamento atualizado: {$entry->description} - R$ " . number_format((float)$entry->value, 2, ',', '.'),
                $dadosAnteriores,
                $entry->toArray()
            );
            
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

        // Debug: verifica o que está sendo recebido
        $allInput = $this->request->all();
        error_log("Dados recebidos no store: " . print_r($allInput, true));
        
        // Pega o valor numérico do campo hidden se existir, senão usa o campo value
        $valueInput = $this->request->input('value_numeric') ?: $this->request->input('value');
        // Remove formatação de moeda se houver
        if (is_string($valueInput)) {
            $valueInput = str_replace(['R$', ' ', '.'], '', $valueInput);
            $valueInput = str_replace(',', '.', $valueInput);
            // Atualiza o POST diretamente para usar o valor numérico
            $_POST['value'] = $valueInput;
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
            'is_recurring' => 'nullable',
            'recurrence_type' => 'nullable|in:mensal,semanal,diario,anual',
            'recurrence_end_date' => 'nullable|date',
            'is_installment' => 'nullable',
            'total_installments' => 'nullable|integer|min:1',
            'release_date' => 'nullable|date',
            'payment_method_id' => 'nullable|integer',
            'data_liberacao' => 'nullable|date'
        ]);
        
        // Normaliza checkbox (verifica tanto no $data quanto no input direto)
        $isRecurringInput = $this->request->input('is_recurring');
        $data['is_recurring'] = ($isRecurringInput === '1' || $isRecurringInput === 1 || $isRecurringInput === true || (isset($data['is_recurring']) && ($data['is_recurring'] === '1' || $data['is_recurring'] === 1 || $data['is_recurring'] === true)));
        
        $isInstallmentInput = $this->request->input('is_installment');
        $data['is_installment'] = ($isInstallmentInput === '1' || $isInstallmentInput === 1 || $isInstallmentInput === true || (isset($data['is_installment']) && ($data['is_installment'] === '1' || $data['is_installment'] === 1 || $data['is_installment'] === true)));
        
        // Se recurrence_type não veio no validated, pega direto do request (fallback)
        if (!isset($data['recurrence_type']) || $data['recurrence_type'] === null) {
            $data['recurrence_type'] = $this->request->input('recurrence_type');
        }
        
        // Se recurrence_end_date não veio no validated, pega direto do request (fallback)
        if (!isset($data['recurrence_end_date']) || $data['recurrence_end_date'] === null) {
            $data['recurrence_end_date'] = $this->request->input('recurrence_end_date');
        }
        
        error_log("is_recurring normalizado: " . ($data['is_recurring'] ? 'true' : 'false'));
        error_log("recurrence_type: " . ($data['recurrence_type'] ?? 'null'));
        error_log("recurrence_end_date: " . ($data['recurrence_end_date'] ?? 'null'));

        try {
            $userId = auth()->getDataUserId();
            
            // Processa forma de pagamento se for entrada
            $paymentMethodId = null;
            $dataLiberacao = null;
            $taxaDespesa = null;
            
            if ($data['type'] === 'entrada' && !empty($data['payment_method_id'])) {
                $paymentMethod = PaymentMethod::find($data['payment_method_id']);
                if ($paymentMethod && $paymentMethod->user_id === $userId) {
                    $paymentMethodId = $paymentMethod->id;
                    
                    // Calcula data de liberação - prioriza o campo do formulário
                    // Primeiro tenta pegar direto do request (pode não estar no validated)
                    $dataLiberacaoInput = $this->request->input('data_liberacao');
                    if (!empty($dataLiberacaoInput)) {
                        $dataLiberacao = $dataLiberacaoInput;
                    } elseif (!empty($data['data_liberacao'])) {
                        $dataLiberacao = $data['data_liberacao'];
                    } elseif (!empty($data['due_date'])) {
                        $dataLiberacao = $paymentMethod->calcularDataLiberacao($data['due_date']);
                    } elseif (!empty($data['competence_date'])) {
                        $dataLiberacao = $paymentMethod->calcularDataLiberacao($data['competence_date']);
                    }
                    
                    error_log("Data de liberação calculada: " . ($dataLiberacao ?? 'null') . " para payment_method_id: " . $paymentMethodId);
                    
                    // Se a forma de pagamento tem taxa e deve adicionar como despesa
                    if ($paymentMethod->taxa > 0 && $paymentMethod->adicionar_taxa_como_despesa) {
                        $valorTaxa = $paymentMethod->calcularTaxa((float)$data['value']);
                        // Cria uma despesa automática para a taxa
                        $taxaDespesa = [
                            'type' => 'saida',
                            'description' => "Taxa: {$paymentMethod->nome} - {$data['description']}",
                            'value' => $valorTaxa,
                            'competence_date' => $data['competence_date'],
                            'due_date' => $data['due_date'] ?? $data['competence_date'],
                            'category_id' => $data['category_id'] ?? null,
                            'bank_account_id' => $paymentMethod->conta_id ?? $data['bank_account_id'] ?? null,
                            'user_id' => $userId,
                            'responsible_user_id' => $userId,
                            'is_paid' => 0,
                            'is_received' => 0
                        ];
                    }
                }
            }
            
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
                'is_recurring' => $data['is_recurring'] ? 1 : 0, // Converte para inteiro para salvar no banco
                'recurrence_type' => $data['recurrence_type'] ?? null,
                'recurrence_end_date' => $data['recurrence_end_date'] ?? null,
                'is_installment' => $data['is_installment'] ? 1 : 0, // Converte para inteiro para salvar no banco
                'total_installments' => $data['total_installments'] ?? null,
                'release_date' => $data['release_date'] ?? null,
                'payment_method_id' => $paymentMethodId,
                'data_liberacao' => $dataLiberacao,
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
                $entryData['is_paid'] = $data['type'] === 'saida' ? 1 : 0;
                $entryData['is_received'] = $data['type'] === 'entrada' ? 1 : 0;
                $entryData['payment_date'] = $this->request->input('payment_date') ?? date('Y-m-d');
                $entryData['paid_value'] = $data['value'];
            } else {
                $entryData['is_paid'] = 0;
                $entryData['is_received'] = 0;
            }
            
            // Cria o lançamento
            $entry = FinancialEntry::create($entryData);
            
            // Dispara evento de automação
            AutomationEventDispatcher::onFinancialEntryCreated(
                $entry->id, 
                $entry->type, 
                (float)$entry->value, 
                auth()->getDataUserId()
            );
            
            // Adiciona tags
            $tagsInput = $this->request->input('tags', '');
            
            // Se for string (separada por vírgula), converte para array
            if (is_string($tagsInput) && !empty(trim($tagsInput))) {
                $tagsArray = array_map('trim', explode(',', $tagsInput));
                $tagsArray = array_filter($tagsArray); // Remove valores vazios
            } elseif (is_array($tagsInput)) {
                $tagsArray = $tagsInput;
            } else {
                $tagsArray = [];
            }
            
            if (!empty($tagsArray)) {
                foreach ($tagsArray as $tagValue) {
                    $tagValue = trim($tagValue);
                    if (empty($tagValue)) {
                        continue;
                    }
                    
                    // Se for numérico, assume que é ID de tag existente
                    if (is_numeric($tagValue)) {
                        $entry->addTag((int) $tagValue);
                    } else {
                        // Se for string, busca ou cria a tag pelo nome
                        $tag = Tag::where('name', $tagValue)
                            ->where('user_id', $userId)
                            ->first();
                        
                        if (!$tag) {
                            $tag = Tag::create([
                                'name' => $tagValue,
                                'user_id' => $userId
                            ]);
                        }
                        
                        $entry->addTag($tag->id);
                    }
                }
            }
            
            // Se for recorrente, cria os próximos lançamentos
            error_log("Verificando recorrência - is_recurring: " . ($entry->is_recurring ? 'true' : 'false') . ", recurrence_type: " . ($entry->recurrence_type ?? 'null'));
            
            if ($entry->is_recurring && $entry->recurrence_type) {
                error_log("Chamando createRecurringEntries para entry ID: " . $entry->id);
                $this->createRecurringEntries($entry);
            } else {
                error_log("NÃO chamou createRecurringEntries - is_recurring: " . ($entry->is_recurring ? 'true' : 'false') . ", recurrence_type: " . ($entry->recurrence_type ?? 'null'));
            }
            
            // Se for parcelado, cria as parcelas
            if ($entry->is_installment && $entry->total_installments > 1) {
                $this->createInstallments($entry);
            }
            
            // Cria despesa automática da taxa se necessário
            if ($taxaDespesa) {
                try {
                    FinancialEntry::create($taxaDespesa);
                    error_log("Despesa automática de taxa criada: " . print_r($taxaDespesa, true));
                } catch (\Exception $e) {
                    error_log("Erro ao criar despesa automática de taxa: " . $e->getMessage());
                }
            }
            
            // Registra log
            SistemaLog::registrar(
                'financial_entries',
                'CREATE',
                $entry->id,
                "Lançamento criado: {$entry->description} - R$ " . number_format((float)$entry->value, 2, ',', '.'),
                null,
                $entry->toArray()
            );
            
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
        
        // Prepara dados base do lançamento recorrente
        $baseData = [
            'type' => $parent->type,
            'description' => $parent->description,
            'value' => $parent->value,
            'bank_account_id' => $parent->bank_account_id,
            'credit_card_id' => $parent->credit_card_id,
            'supplier_id' => $parent->supplier_id,
            'client_id' => $parent->client_id ?? null,
            'category_id' => $parent->category_id,
            'subcategory_id' => $parent->subcategory_id,
            'cost_center_id' => $parent->cost_center_id,
            'sub_cost_center_id' => $parent->sub_cost_center_id,
            'observations' => $parent->observations,
            'release_date' => $parent->release_date,
            'is_recurring' => false, // Os lançamentos filhos não são recorrentes
            'recurrence_type' => null,
            'is_installment' => false,
            'parent_entry_id' => $parent->id,
            'is_paid' => false,
            'is_received' => false,
            'payment_date' => null,
            'paid_value' => null,
            'fees' => 0,
            'interest' => 0,
            'user_id' => $parent->user_id,
            'responsible_user_id' => $parent->responsible_user_id ?? $parent->user_id
        ];
        
        // Calcula a diferença em dias entre competence_date e due_date do lançamento original
        $parentCompetenceDate = new \DateTime($parent->competence_date);
        $parentDueDate = new \DateTime($parent->due_date);
        $daysDifference = (int)$parentCompetenceDate->diff($parentDueDate)->format('%r%a'); // %r para sinal, %a para dias absolutos
        
        // Debug: log para verificar se está entrando no método
        error_log("Criando lançamentos recorrentes. Tipo: {$recurrenceType}, Data inicial: {$parent->competence_date}, Data final: {$endDate}");
        error_log("Diferença entre competence_date e due_date: {$daysDifference} dias");
        
        while ($currentDate <= $endDateTime && $count < $maxEntries) {
            $currentDate->add($interval);
            
            if ($currentDate > $endDateTime) {
                break;
            }
            
            // Calcula a data de vencimento mantendo a diferença do lançamento original
            $dueDate = clone $currentDate;
            if ($daysDifference != 0) {
                $dueDate->modify("{$daysDifference} days");
            }
            
            $entryData = $baseData;
            $entryData['competence_date'] = $currentDate->format('Y-m-d');
            $entryData['due_date'] = $dueDate->format('Y-m-d');
            
            try {
                // Verifica se já existe um lançamento com a mesma data de competência e descrição
                $existing = FinancialEntry::where('competence_date', $entryData['competence_date'])
                    ->where('description', $entryData['description'])
                    ->where('value', $entryData['value'])
                    ->where('user_id', $parent->user_id)
                    ->where('parent_entry_id', $parent->id)
                    ->first();
                
                if (!$existing) {
                    $created = FinancialEntry::create($entryData);
                    error_log("Lançamento recorrente criado: ID {$created->id}, Competence: {$entryData['competence_date']}, Due: {$entryData['due_date']}");
                    $count++;
                } else {
                    error_log("Lançamento recorrente já existe (ID: {$existing->id}), pulando criação. Competence: {$entryData['competence_date']}");
                }
            } catch (\Exception $e) {
                // Log do erro mas continua criando os próximos
                error_log("Erro ao criar lançamento recorrente: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
            }
        }
        
        error_log("Total de lançamentos recorrentes criados: {$count}");
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
        
        $dadosAnteriores = $entry->toArray();
        $entry->update($updateData);
        
        // Registra log
        SistemaLog::registrar(
            'financial_entries',
            'UPDATE',
            $entry->id,
            "Lançamento marcado como pago/recebido: {$entry->description}",
            $dadosAnteriores,
            $entry->toArray()
        );
        
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

        $dadosAnteriores = $entry->toArray();
        $entry->unmarkAsPaid();
        
        // Registra log
        SistemaLog::registrar(
            'financial_entries',
            'UPDATE',
            $entry->id,
            "Lançamento desmarcado como pago/recebido: {$entry->description}",
            $dadosAnteriores,
            $entry->toArray()
        );
        
        json_response([
            'success' => true,
            'message' => 'Lançamento atualizado com sucesso'
        ]);
    }
    
    // ==================== FORNECEDORES ====================
    
    /**
     * Lista fornecedores
     */
    public function suppliers(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $suppliers = Supplier::where('user_id', $userId)->get();
        
        return $this->view('financial/suppliers/index', [
            'title' => 'Fornecedores',
            'suppliers' => $suppliers
        ]);
    }
    
    /**
     * Exibe formulário de criação de fornecedor
     */
    public function createSupplier(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        return $this->view('financial/suppliers/create', [
            'title' => 'Novo Fornecedor'
        ]);
    }
    
    /**
     * Salva novo fornecedor
     */
    public function storeSupplier(): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/financial/suppliers');
        }

        try {
            $data = $this->validate([
                'name' => 'required',
                'fantasy_name' => 'nullable',
                'cnpj' => 'nullable',
                'email' => 'nullable|email',
                'phone' => 'nullable',
                'address' => 'nullable',
                'additional_info' => 'nullable',
                'is_client' => 'nullable',
                'receives_invoice' => 'nullable',
                'issues_invoice' => 'nullable'
            ]);
        } catch (\Exception $e) {
            session()->flash('error', 'Erro na validação: ' . $e->getMessage());
            session()->flash('old', $this->request->all());
            $this->redirect('/financial/suppliers/create');
            return;
        }

        try {
            $userId = auth()->getDataUserId();
            
            // Converte checkboxes para boolean
            // Quando checkbox não está marcado, pode vir como "0" (string) ou não existir
            // Quando está marcado, vem como "1" (string)
            $isClient = isset($data['is_client']) && ($data['is_client'] === '1' || $data['is_client'] === 1 || $data['is_client'] === true);
            $receivesInvoice = isset($data['receives_invoice']) && ($data['receives_invoice'] === '1' || $data['receives_invoice'] === 1 || $data['receives_invoice'] === true);
            $issuesInvoice = isset($data['issues_invoice']) && ($data['issues_invoice'] === '1' || $data['issues_invoice'] === 1 || $data['issues_invoice'] === true);
            
            $supplier = Supplier::create([
                'name' => trim($data['name']),
                'fantasy_name' => !empty($data['fantasy_name']) ? trim($data['fantasy_name']) : null,
                'cnpj' => !empty($data['cnpj']) ? trim($data['cnpj']) : null,
                'email' => !empty($data['email']) ? trim($data['email']) : null,
                'phone' => !empty($data['phone']) ? trim($data['phone']) : null,
                'address' => !empty($data['address']) ? trim($data['address']) : null,
                'additional_info' => !empty($data['additional_info']) ? trim($data['additional_info']) : null,
                'is_client' => $isClient ? 1 : 0,
                'receives_invoice' => $receivesInvoice ? 1 : 0,
                'issues_invoice' => $issuesInvoice ? 1 : 0,
                'user_id' => $userId
            ]);
            
            // Registra log
            SistemaLog::registrar(
                'suppliers',
                'CREATE',
                $supplier->id,
                "Fornecedor criado: {$supplier->name}",
                null,
                $supplier->toArray()
            );
            
            session()->flash('success', 'Fornecedor cadastrado com sucesso!');
            $this->redirect('/financial/suppliers');
            
        } catch (\Exception $e) {
            error_log("Erro ao cadastrar fornecedor: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            session()->flash('error', 'Erro ao cadastrar fornecedor: ' . $e->getMessage());
            session()->flash('old', $this->request->all());
            $this->redirect('/financial/suppliers/create');
        }
    }
    
    /**
     * Exibe formulário de edição de fornecedor
     */
    public function editSupplier(array $params): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $userId = auth()->getDataUserId();
        $supplier = Supplier::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$supplier) {
            session()->flash('error', 'Fornecedor não encontrado.');
            $this->redirect('/financial/suppliers');
        }
        
        return $this->view('financial/suppliers/edit', [
            'title' => 'Editar Fornecedor',
            'supplier' => $supplier
        ]);
    }
    
    /**
     * Atualiza fornecedor
     */
    public function updateSupplier(array $params): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/financial/suppliers');
        }

        $userId = auth()->getDataUserId();
        $supplier = Supplier::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$supplier) {
            session()->flash('error', 'Fornecedor não encontrado.');
            $this->redirect('/financial/suppliers');
        }

        $data = $this->validate([
            'name' => 'required',
            'fantasy_name' => 'nullable',
            'cnpj' => 'nullable',
            'email' => 'nullable|email',
            'phone' => 'nullable',
            'address' => 'nullable',
            'additional_info' => 'nullable',
            'is_client' => 'nullable|boolean',
            'receives_invoice' => 'nullable|boolean',
            'issues_invoice' => 'nullable|boolean'
        ]);

        try {
            $dadosAnteriores = $supplier->toArray();
            
            $supplier->update([
                'name' => $data['name'],
                'fantasy_name' => $data['fantasy_name'] ?? null,
                'cnpj' => $data['cnpj'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'additional_info' => $data['additional_info'] ?? null,
                'is_client' => $data['is_client'] ?? false,
                'receives_invoice' => $data['receives_invoice'] ?? false,
                'issues_invoice' => $data['issues_invoice'] ?? false
            ]);
            
            // Registra log
            SistemaLog::registrar(
                'suppliers',
                'UPDATE',
                $supplier->id,
                "Fornecedor atualizado: {$supplier->name}",
                $dadosAnteriores,
                $supplier->toArray()
            );
            
            session()->flash('success', 'Fornecedor atualizado com sucesso!');
            $this->redirect('/financial/suppliers');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao atualizar fornecedor: ' . $e->getMessage());
            $this->redirect('/financial/suppliers/' . $params['id'] . '/edit');
        }
    }
    
    /**
     * Exclui fornecedor
     */
    public function deleteSupplier(array $params): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/financial/suppliers');
        }

        $userId = auth()->getDataUserId();
        $supplier = Supplier::where('id', $params['id'])
            ->where('user_id', $userId)
            ->first();
        
        if (!$supplier) {
            session()->flash('error', 'Fornecedor não encontrado.');
            $this->redirect('/financial/suppliers');
        }

        try {
            $nome = $supplier->name;
            
            // Registra log antes de excluir
            SistemaLog::registrar(
                'suppliers',
                'DELETE',
                $supplier->id,
                "Fornecedor excluído: {$nome}",
                $supplier->toArray(),
                null
            );
            
            $supplier->delete();
            
            session()->flash('success', 'Fornecedor excluído com sucesso!');
            $this->redirect('/financial/suppliers');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao excluir fornecedor: ' . $e->getMessage());
            $this->redirect('/financial/suppliers');
        }
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
        // Limpa qualquer output anterior
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Define headers JSON antes de qualquer output
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if (!auth()->check()) {
                json_response(['success' => false, 'message' => 'Não autenticado'], 401);
                return;
            }

            if (!verify_csrf($this->request->input('_csrf_token'))) {
                json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
                return;
            }

            // Validação manual para retornar JSON em caso de erro
            $validator = new \Core\Validator($this->request->all(), [
                'name' => 'required',
                'category_id' => 'required|integer'
            ]);

            if (!$validator->passes()) {
                $errors = $validator->errors();
                $firstError = !empty($errors) ? reset($errors)[0] : 'Dados inválidos';
                json_response([
                    'success' => false,
                    'message' => $firstError,
                    'errors' => $errors
                ], 422);
                return;
            }

            $data = $validator->validated();
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
            return;
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
            return;
        }

        // Validação manual para retornar JSON em caso de erro
        $validator = new \Core\Validator($this->request->all(), [
            'id' => 'required|integer',
            'name' => 'required'
        ]);

        if (!$validator->passes()) {
            $errors = $validator->errors();
            $firstError = !empty($errors) ? reset($errors)[0] : 'Dados inválidos';
            json_response([
                'success' => false,
                'message' => $firstError,
                'errors' => $errors
            ], 422);
            return;
        }

        $data = $validator->validated();

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

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
            return;
        }

        // Validação manual para retornar JSON em caso de erro
        $validator = new \Core\Validator($this->request->all(), [
            'id' => 'required|integer'
        ]);

        if (!$validator->passes()) {
            $errors = $validator->errors();
            $firstError = !empty($errors) ? reset($errors)[0] : 'Dados inválidos';
            json_response([
                'success' => false,
                'message' => $firstError,
                'errors' => $errors
            ], 422);
            return;
        }

        $data = $validator->validated();

        try {
            $userId = auth()->getDataUserId();
            $subcategoryId = (int)$data['id'];
            
            $subcategory = Subcategory::find($subcategoryId);
            
            if (!$subcategory || $subcategory->user_id != $userId) {
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
    
    /**
     * Retorna subcategorias por categoria
     */
    public function getSubcategoriesByCategory(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        try {
            $userId = auth()->getDataUserId();
            $categoryId = $params['id'];
            
            $subcategories = Subcategory::where('category_id', $categoryId)
                ->where('user_id', $userId)
                ->orderBy('name')
                ->get();
            
            json_response([
                'success' => true,
                'subcategories' => $subcategories->map(function($sub) {
                    return ['id' => $sub->id, 'name' => $sub->name];
                })->toArray()
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
     * Exibe formulário de edição de centro de custo
     */
    public function editCostCenter(array $params): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $id = $params['id'];
        $userId = auth()->getDataUserId();
        
        $costCenter = CostCenter::where('id', $id)
            ->where('user_id', $userId)
            ->first();
        
        if (!$costCenter) {
            session()->flash('error', 'Centro de custo não encontrado.');
            $this->redirect('/financial/cost-centers');
        }

        return $this->view('financial/cost_centers/edit', [
            'title' => 'Editar Centro de Custo',
            'costCenter' => $costCenter
        ]);
    }
    
    /**
     * Atualiza centro de custo
     */
    public function updateCostCenter(array $params): void
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/financial/cost-centers');
        }

        $id = $params['id'];
        $userId = auth()->getDataUserId();
        
        $costCenter = CostCenter::where('id', $id)
            ->where('user_id', $userId)
            ->first();
        
        if (!$costCenter) {
            session()->flash('error', 'Centro de custo não encontrado.');
            $this->redirect('/financial/cost-centers');
        }

        $data = $this->validate([
            'name' => 'required'
        ]);

        try {
            $costCenter->update([
                'name' => $data['name']
            ]);
            
            session()->flash('success', 'Centro de custo atualizado com sucesso!');
            $this->redirect('/financial/cost-centers');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao atualizar centro de custo: ' . $e->getMessage());
            $this->redirect('/financial/cost-centers/' . $id . '/edit');
        }
    }
    
    /**
     * Retorna subcentros de custo por centro de custo
     */
    public function getSubCostCentersByCostCenter(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
            return;
        }

        try {
            $userId = auth()->getDataUserId();
            $costCenterId = $params['id'];
            
            $subCostCenters = SubCostCenter::where('cost_center_id', $costCenterId)
                ->where('user_id', $userId)
                ->orderBy('name')
                ->get();
            
            json_response([
                'success' => true,
                'subCostCenters' => $subCostCenters->map(function($sub) {
                    return ['id' => $sub->id, 'name' => $sub->name];
                })->toArray()
            ]);
            
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage()
            ], 500);
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
    
    /**
     * Exclui um lançamento
     */
    public function delete(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!auth()->check()) {
            json_response(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        $requestData = json_decode(file_get_contents('php://input'), true);
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $requestData['_csrf_token'] ?? '';
        
        if (!verify_csrf($csrfToken)) {
            json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
        }

        try {
            $userId = auth()->getDataUserId();
            $entry = FinancialEntry::where('id', $params['id'])
                ->where('user_id', $userId)
                ->first();
            
            if (!$entry) {
                json_response(['success' => false, 'message' => 'Lançamento não encontrado'], 404);
            }
            
            $cancelDependencies = $requestData['cancel_dependencies'] ?? false;
            
            // Se deve cancelar dependências (parcelas/recorrências)
            if ($cancelDependencies) {
                // Exclui todas as parcelas/recorrências relacionadas
                if ($entry->is_recurring || $entry->is_installment) {
                    // Exclui lançamentos filhos (parcelas ou recorrências)
                    $db = \Core\Database::getInstance();
                    $db->execute(
                        "DELETE FROM financial_entries WHERE parent_entry_id = ? AND user_id = ?",
                        [$entry->id, $userId]
                    );
                }
                
                // Se este é um lançamento filho, exclui todos os irmãos também
                if ($entry->parent_entry_id) {
                    $parent = FinancialEntry::find($entry->parent_entry_id);
                    if ($parent && $parent->user_id == $userId) {
                        // Exclui todos os filhos do pai
                        $db = \Core\Database::getInstance();
                        $db->execute(
                            "DELETE FROM financial_entries WHERE parent_entry_id = ? AND user_id = ?",
                            [$parent->id, $userId]
                        );
                        // Exclui o pai também
                        $parent->delete();
                    }
                }
            } else {
                // Se não deve cancelar dependências, apenas exclui este lançamento
                // Mas se for um lançamento pai, não permite excluir sem cancelar dependências
                if ($entry->is_recurring || $entry->is_installment) {
                    // Verifica se tem filhos
                    $db = \Core\Database::getInstance();
                    $children = $db->query(
                        "SELECT COUNT(*) as count FROM financial_entries WHERE parent_entry_id = ? AND user_id = ?",
                        [$entry->id, $userId]
                    );
                    
                    if ($children && $children[0]['count'] > 0) {
                        json_response([
                            'success' => false,
                            'message' => 'Este lançamento possui parcelas/recorrências. Selecione a opção de cancelar dependências.'
                        ], 400);
                    }
                }
            }
            
            // Remove tags associadas
            $entry->removeAllTags();
            
            // Registra log antes de excluir
            SistemaLog::registrar(
                'financial_entries',
                'DELETE',
                $entry->id,
                "Lançamento excluído: {$entry->description} - R$ " . number_format((float)$entry->value, 2, ',', '.') . ($cancelDependencies ? ' (com dependências)' : ''),
                $entry->toArray(),
                null
            );
            
            // Exclui o lançamento
            $entry->delete();
            
            json_response([
                'success' => true,
                'message' => 'Lançamento excluído com sucesso!'
            ]);
            
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao excluir lançamento: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Exclui múltiplos lançamentos
     */
    public function bulkDelete(array $params = []): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if (!auth()->check()) {
                json_response(['success' => false, 'message' => 'Não autenticado'], 401);
                return;
            }

            $requestData = json_decode(file_get_contents('php://input'), true);
            
            if (!$requestData) {
                json_response(['success' => false, 'message' => 'Dados inválidos'], 400);
                return;
            }
            
            $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $requestData['_csrf_token'] ?? '';
            
            if (!verify_csrf($csrfToken)) {
                json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
                return;
            }

            $userId = auth()->getDataUserId();
            $ids = $requestData['ids'] ?? [];
            
            if (empty($ids) || !is_array($ids)) {
                json_response(['success' => false, 'message' => 'Nenhum lançamento selecionado'], 400);
                return;
            }
            
            // Converte IDs para inteiros
            $ids = array_map('intval', $ids);
            $ids = array_filter($ids, fn($id) => $id > 0);
            
            if (empty($ids)) {
                json_response(['success' => false, 'message' => 'IDs inválidos'], 400);
                return;
            }
            
            $deleted = 0;
            $errors = [];
            $db = \Core\Database::getInstance();
            
            // Busca todos os lançamentos de uma vez
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $entries = $db->query(
                "SELECT * FROM financial_entries WHERE id IN ({$placeholders}) AND user_id = ?",
                array_merge($ids, [$userId])
            );
            
            foreach ($entries as $entryData) {
                $entry = FinancialEntry::newInstance($entryData, true);
                
                try {
                    // Remove tags
                    $entry->removeAllTags();
                    
                    // Registra log antes de excluir
                    SistemaLog::registrar(
                        'financial_entries',
                        'DELETE',
                        $entry->id,
                        "Lançamento excluído (lote): {$entry->description} - R$ " . number_format((float)$entry->value, 2, ',', '.'),
                        $entry->toArray(),
                        null
                    );
                    
                    // Exclui o lançamento
                    if ($entry->delete()) {
                        $deleted++;
                    } else {
                        $errors[] = "Erro ao excluir lançamento ID {$entry->id}";
                    }
                } catch (\Exception $e) {
                    $errors[] = "Erro ao excluir lançamento ID {$entry->id}: " . $e->getMessage();
                }
            }
            
            if ($deleted > 0) {
                json_response([
                    'success' => true,
                    'message' => "{$deleted} lançamento(s) excluído(s) com sucesso!",
                    'deleted' => $deleted,
                    'errors' => $errors
                ]);
            } else {
                json_response([
                    'success' => false,
                    'message' => 'Nenhum lançamento foi excluído',
                    'errors' => $errors
                ], 400);
            }
            
        } catch (\Exception $e) {
            error_log("Erro em bulkDelete: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            json_response([
                'success' => false,
                'message' => 'Erro ao excluir lançamentos: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Marca múltiplos lançamentos como pago/recebido
     */
    public function bulkMarkAsPaid(array $params = []): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if (!auth()->check()) {
                json_response(['success' => false, 'message' => 'Não autenticado'], 401);
                return;
            }

            $requestData = json_decode(file_get_contents('php://input'), true);
            
            if (!$requestData) {
                json_response(['success' => false, 'message' => 'Dados inválidos'], 400);
                return;
            }
            
            $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $requestData['_csrf_token'] ?? '';
            
            if (!verify_csrf($csrfToken)) {
                json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
                return;
            }

            $userId = auth()->getDataUserId();
            $ids = $requestData['ids'] ?? [];
            
            if (empty($ids) || !is_array($ids)) {
                json_response(['success' => false, 'message' => 'Nenhum lançamento selecionado'], 400);
                return;
            }
            
            // Converte IDs para inteiros
            $ids = array_map('intval', $ids);
            $ids = array_filter($ids, fn($id) => $id > 0);
            
            if (empty($ids)) {
                json_response(['success' => false, 'message' => 'IDs inválidos'], 400);
                return;
            }
            
            $updated = 0;
            $errors = [];
            $db = \Core\Database::getInstance();
            
            // Busca todos os lançamentos de uma vez
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $entries = $db->query(
                "SELECT * FROM financial_entries WHERE id IN ({$placeholders}) AND user_id = ?",
                array_merge($ids, [$userId])
            );
            
            foreach ($entries as $entryData) {
                $entry = FinancialEntry::newInstance($entryData, true);
                
                try {
                    $oldData = $entry->toArray();
                    
                    // Marca como pago/recebido baseado no tipo
                    if ($entry->type === 'saida') {
                        $entry->update([
                            'is_paid' => 1,
                            'payment_date' => date('Y-m-d'),
                            'paid_value' => $entry->value
                        ]);
                    } else {
                        $entry->update([
                            'is_received' => 1,
                            'payment_date' => date('Y-m-d'),
                            'paid_value' => $entry->value
                        ]);
                    }
                    
                    // Registra log
                    SistemaLog::registrar(
                        'financial_entries',
                        'UPDATE',
                        $entry->id,
                        "Lançamento marcado como pago/recebido (lote): {$entry->description}",
                        $oldData,
                        $entry->toArray()
                    );
                    
                    $updated++;
                } catch (\Exception $e) {
                    $errors[] = "Erro ao marcar lançamento ID {$entry->id}: " . $e->getMessage();
                }
            }
            
            if ($updated > 0) {
                json_response([
                    'success' => true,
                    'message' => "{$updated} lançamento(s) marcado(s) como pago/recebido com sucesso!",
                    'updated' => $updated,
                    'errors' => $errors
                ]);
            } else {
                json_response([
                    'success' => false,
                    'message' => 'Nenhum lançamento foi atualizado',
                    'errors' => $errors
                ], 400);
            }
            
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao marcar lançamentos: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Desmarca múltiplos lançamentos como pago/recebido (marca como pendente)
     */
    public function bulkUnmarkAsPaid(array $params = []): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if (!auth()->check()) {
                json_response(['success' => false, 'message' => 'Não autenticado'], 401);
                return;
            }

            $requestData = json_decode(file_get_contents('php://input'), true);
            
            if (!$requestData) {
                json_response(['success' => false, 'message' => 'Dados inválidos'], 400);
                return;
            }
            
            $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $requestData['_csrf_token'] ?? '';
            
            if (!verify_csrf($csrfToken)) {
                json_response(['success' => false, 'message' => 'Token de segurança inválido'], 403);
                return;
            }

            $userId = auth()->getDataUserId();
            $ids = $requestData['ids'] ?? [];
            
            if (empty($ids) || !is_array($ids)) {
                json_response(['success' => false, 'message' => 'Nenhum lançamento selecionado'], 400);
                return;
            }
            
            // Converte IDs para inteiros
            $ids = array_map('intval', $ids);
            $ids = array_filter($ids, fn($id) => $id > 0);
            
            if (empty($ids)) {
                json_response(['success' => false, 'message' => 'IDs inválidos'], 400);
                return;
            }
            
            $updated = 0;
            $errors = [];
            $db = \Core\Database::getInstance();
            
            // Busca todos os lançamentos de uma vez
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $entries = $db->query(
                "SELECT * FROM financial_entries WHERE id IN ({$placeholders}) AND user_id = ?",
                array_merge($ids, [$userId])
            );
            
            foreach ($entries as $entryData) {
                $entry = FinancialEntry::newInstance($entryData, true);
                
                try {
                    $oldData = $entry->toArray();
                    
                    // Desmarca como pago/recebido baseado no tipo
                    if ($entry->type === 'saida') {
                        $entry->update([
                            'is_paid' => 0,
                            'payment_date' => null,
                            'paid_value' => null
                        ]);
                    } else {
                        $entry->update([
                            'is_received' => 0,
                            'payment_date' => null,
                            'paid_value' => null
                        ]);
                    }
                    
                    // Registra log
                    SistemaLog::registrar(
                        'financial_entries',
                        'UPDATE',
                        $entry->id,
                        "Lançamento marcado como pendente (lote): {$entry->description}",
                        $oldData,
                        $entry->toArray()
                    );
                    
                    $updated++;
                } catch (\Exception $e) {
                    $errors[] = "Erro ao desmarcar lançamento ID {$entry->id}: " . $e->getMessage();
                }
            }
            
            if ($updated > 0) {
                json_response([
                    'success' => true,
                    'message' => "{$updated} lançamento(s) marcado(s) como pendente com sucesso!",
                    'updated' => $updated,
                    'errors' => $errors
                ]);
            } else {
                json_response([
                    'success' => false,
                    'message' => 'Nenhum lançamento foi atualizado',
                    'errors' => $errors
                ], 400);
            }
            
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Erro ao desmarcar lançamentos: ' . $e->getMessage()
            ], 500);
        }
    }
}

