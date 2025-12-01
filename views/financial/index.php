<?php
$title = $title ?? 'Financeiro - Lançamentos';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="card-title mb-0">
                        <i class="ti ti-wallet me-2"></i>
                        Lançamentos Financeiros
                    </h4>
                    <div>
                        <a href="<?php echo url('/financial/create?type=entrada'); ?>" class="btn btn-success">
                            <i class="ti ti-plus me-2"></i>
                            Nova Entrada
                        </a>
                        <a href="<?php echo url('/financial/create?type=saida'); ?>" class="btn btn-danger">
                            <i class="ti ti-plus me-2"></i>
                            Nova Saída
                        </a>
                    </div>
                </div>

                <!-- Filtros -->
                <form method="GET" action="<?php echo url('/financial'); ?>" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Tipo</label>
                            <select name="type" class="form-select">
                                <option value="all" <?php echo ($filters['type'] ?? 'all') === 'all' ? 'selected' : ''; ?>>Todos</option>
                                <option value="entrada" <?php echo ($filters['type'] ?? '') === 'entrada' ? 'selected' : ''; ?>>Entradas</option>
                                <option value="saida" <?php echo ($filters['type'] ?? '') === 'saida' ? 'selected' : ''; ?>>Saídas</option>
                                <option value="transferencia" <?php echo ($filters['type'] ?? '') === 'transferencia' ? 'selected' : ''; ?>>Transferências</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="all" <?php echo ($filters['status'] ?? 'all') === 'all' ? 'selected' : ''; ?>>Todos</option>
                                <option value="paid" <?php echo ($filters['status'] ?? '') === 'paid' ? 'selected' : ''; ?>>Pagos/Recebidos</option>
                                <option value="unpaid" <?php echo ($filters['status'] ?? '') === 'unpaid' ? 'selected' : ''; ?>>Pendentes</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Conta</label>
                            <select name="account_id" class="form-select">
                                <option value="">Todas</option>
                                <?php foreach ($bankAccounts as $account): ?>
                                    <option value="<?php echo $account->id; ?>" <?php echo ($filters['account_id'] ?? '') == $account->id ? 'selected' : ''; ?>>
                                        <?php echo e($account->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Categoria</label>
                            <select name="category_id" class="form-select">
                                <option value="">Todas</option>
                                <?php foreach ($categories as $category): ?>
                                    <optgroup label="<?php echo e($category->name); ?>">
                                        <option value="<?php echo $category->id; ?>" <?php echo ($filters['category_id'] ?? '') == $category->id ? 'selected' : ''; ?>>
                                            <?php echo e($category->name); ?>
                                        </option>
                                        <?php if (!empty($category->subcategories)): ?>
                                            <?php foreach ($category->subcategories as $subcategory): ?>
                                                <option value="subcategory_<?php echo $subcategory->id; ?>" <?php echo ($filters['category_id'] ?? '') == 'subcategory_' . $subcategory->id ? 'selected' : ''; ?>>
                                                    └ <?php echo e($subcategory->name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tag</label>
                            <select name="tag_id" class="form-select">
                                <option value="">Todas</option>
                                <?php foreach ($tags as $tag): ?>
                                    <option value="<?php echo $tag->id; ?>" <?php echo ($filters['tag_id'] ?? '') == $tag->id ? 'selected' : ''; ?>>
                                        <?php echo e($tag->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Período</label>
                            <select name="period" class="form-select" id="period_select">
                                <option value="today" <?php echo ($filters['period'] ?? '') === 'today' ? 'selected' : ''; ?>>Hoje</option>
                                <option value="week" <?php echo ($filters['period'] ?? '') === 'week' ? 'selected' : ''; ?>>Esta Semana</option>
                                <option value="month" <?php echo ($filters['period'] ?? 'month') === 'month' ? 'selected' : ''; ?>>Este Mês</option>
                                <option value="custom" <?php echo ($filters['period'] ?? '') === 'custom' ? 'selected' : ''; ?>>Personalizado</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ti ti-filter me-2"></i>
                                Filtrar
                            </button>
                        </div>
                    </div>
                    <!-- Campos de data personalizada (aparecem quando período é "Personalizado") -->
                    <div class="row g-3 mt-2" id="custom_date_fields" style="display: <?php echo ($filters['period'] ?? '') === 'custom' ? 'flex' : 'none'; ?>;">
                        <div class="col-md-3">
                            <label class="form-label">Data Inicial</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo $filters['start_date'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Data Final</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo $filters['end_date'] ?? ''; ?>">
                        </div>
                    </div>
                </form>

                <!-- Ações em massa -->
                <div class="mb-3" id="bulk-actions" style="display: none;">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-success" onclick="markSelectedAsPaid()">
                            <i class="ti ti-check me-2"></i>
                            Marcar como Pago/Recebido (<span id="selected-count">0</span>)
                        </button>
                        <button type="button" class="btn btn-warning" onclick="unmarkSelectedAsPaid()">
                            <i class="ti ti-x me-2"></i>
                            Marcar como Pendente (<span id="selected-count-2">0</span>)
                        </button>
                        <button type="button" class="btn btn-danger" onclick="deleteSelected()">
                            <i class="ti ti-trash me-2"></i>
                            Excluir Selecionados (<span id="selected-count-3">0</span>)
                        </button>
                    </div>
                </div>

                <!-- Lista de Lançamentos -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="select-all" onchange="toggleSelectAll()">
                                </th>
                                <th>Data</th>
                                <th>Descrição</th>
                                <th>Categoria</th>
                                <th>Valor</th>
                                <th>Conta</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($entries)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="ti ti-inbox fs-1 d-block mb-2"></i>
                                        Nenhum lançamento encontrado
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($entries as $entry): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="entry-checkbox" value="<?php echo $entry->id; ?>" onchange="updateSelectedCount()">
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($entry->competence_date)); ?></td>
                                        <td><?php echo e($entry->description); ?></td>
                                        <td>
                                            <?php 
                                            if ($entry->category_id) {
                                                $category = \App\Models\Category::find($entry->category_id);
                                                echo $category ? e($category->name) : '-';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="<?php echo $entry->type === 'entrada' ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $entry->type === 'entrada' ? '+' : '-'; ?>
                                                R$ <?php echo number_format($entry->value, 2, ',', '.'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($entry->bank_account_id) {
                                                $account = \App\Models\BankAccount::find($entry->bank_account_id);
                                                echo $account ? e($account->name) : '-';
                                            } elseif ($entry->credit_card_id) {
                                                $card = \App\Models\CreditCard::find($entry->credit_card_id);
                                                echo $card ? e($card->name) : '-';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($entry->type === 'saida'): ?>
                                                <?php if ($entry->is_paid): ?>
                                                    <span class="badge bg-success">Pago</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Pendente</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php if ($entry->is_received): ?>
                                                    <span class="badge bg-success">Recebido</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Pendente</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="<?php echo url('/financial/' . $entry->id . '/edit'); ?>" class="btn btn-sm btn-info" title="Editar">
                                                    <i class="ti ti-edit"></i>
                                                </a>
                                                <?php if (($entry->type === 'saida' && !$entry->is_paid) || ($entry->type === 'entrada' && !$entry->is_received)): ?>
                                                    <button class="btn btn-sm btn-success" onclick="markAsPaid(<?php echo $entry->id; ?>)" title="Marcar como pago/recebido">
                                                        <i class="ti ti-check"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-warning" onclick="unmarkAsPaid(<?php echo $entry->id; ?>)" title="Desmarcar como pago/recebido">
                                                        <i class="ti ti-x"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-danger" onclick="deleteEntry(<?php echo $entry->id; ?>, <?php echo ($entry->is_recurring || $entry->is_installment || $entry->parent_entry_id) ? 'true' : 'false'; ?>)" title="Excluir">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Mostra/oculta campos de data quando período muda
document.getElementById('period_select').addEventListener('change', function() {
    const period = this.value;
    const customDateFields = document.getElementById('custom_date_fields');
    
    if (period === 'custom') {
        customDateFields.style.display = 'flex';
    } else {
        customDateFields.style.display = 'none';
    }
});

// Seleção múltipla
function toggleSelectAll() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.entry-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateSelectedCount();
}

function updateSelectedCount() {
    const checked = document.querySelectorAll('.entry-checkbox:checked');
    const count = checked.length;
    document.getElementById('selected-count').textContent = count;
    document.getElementById('selected-count-2').textContent = count;
    document.getElementById('selected-count-3').textContent = count;
    document.getElementById('bulk-actions').style.display = count > 0 ? 'block' : 'none';
}

function deleteSelected() {
    const checked = document.querySelectorAll('.entry-checkbox:checked');
    const ids = Array.from(checked).map(cb => parseInt(cb.value));
    
    if (ids.length === 0) {
        alert('Selecione pelo menos um lançamento para excluir.');
        return;
    }
    
    if (confirm(`Deseja realmente excluir ${ids.length} lançamento(s)?`)) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        fetch('<?php echo url('/financial/bulk-delete'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ 
                ids: ids,
                _csrf_token: csrfToken
            })
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch {
                        throw new Error('Resposta inválida do servidor');
                    }
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao excluir lançamentos: ' + error.message);
        });
    }
}

function deleteEntry(id, hasDependencies) {
    if (hasDependencies) {
        const cancelDeps = confirm('Este lançamento possui parcelas ou recorrências.\n\nDeseja cancelar todas as parcelas/recorrências também?\n\nOK = Sim, cancelar tudo\nCancelar = Não, excluir apenas este');
        
        if (!confirm('Deseja realmente excluir este lançamento?')) {
            return;
        }
        
        fetch(`<?php echo url('/financial'); ?>/${id}/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ cancel_dependencies: cancelDeps })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro: ' + data.message);
            }
        });
    } else {
        if (confirm('Deseja realmente excluir este lançamento?')) {
            fetch(`<?php echo url('/financial'); ?>/${id}/delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ cancel_dependencies: false })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro: ' + data.message);
                }
            });
        }
    }
}

function markAsPaid(id) {
    if (confirm('Marcar como pago/recebido?')) {
        fetch(`<?php echo url('/financial'); ?>/${id}/mark-paid`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro: ' + data.message);
            }
        });
    }
}

function unmarkAsPaid(id) {
    if (confirm('Desmarcar como pago/recebido?')) {
        fetch(`<?php echo url('/financial'); ?>/${id}/unmark-paid`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro: ' + data.message);
            }
        });
    }
}

function markSelectedAsPaid() {
    const checked = document.querySelectorAll('.entry-checkbox:checked');
    const ids = Array.from(checked).map(cb => parseInt(cb.value));
    
    if (ids.length === 0) {
        alert('Selecione pelo menos um lançamento para marcar como pago/recebido.');
        return;
    }
    
    if (confirm(`Deseja marcar ${ids.length} lançamento(s) como pago/recebido?`)) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        fetch('<?php echo url('/financial/bulk-mark-paid'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ 
                ids: ids,
                _csrf_token: csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao marcar lançamentos: ' + error.message);
        });
    }
}

function unmarkSelectedAsPaid() {
    const checked = document.querySelectorAll('.entry-checkbox:checked');
    const ids = Array.from(checked).map(cb => parseInt(cb.value));
    
    if (ids.length === 0) {
        alert('Selecione pelo menos um lançamento para marcar como pendente.');
        return;
    }
    
    if (confirm(`Deseja marcar ${ids.length} lançamento(s) como pendente?`)) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        fetch('<?php echo url('/financial/bulk-unmark-paid'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ 
                ids: ids,
                _csrf_token: csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao desmarcar lançamentos: ' + error.message);
        });
    }
}
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

