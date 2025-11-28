<?php
$title = 'Editar Lançamento';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="ti ti-<?php echo $entry->type === 'entrada' ? 'arrow-down' : 'arrow-up'; ?> me-2"></i>
                    Editar <?php echo $entry->type === 'entrada' ? 'Entrada' : ($entry->type === 'saida' ? 'Saída' : 'Transferência'); ?>
                </h4>

                <form method="POST" action="<?php echo url('/financial/' . $entry->id); ?>">
                    <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="type" value="<?php echo $entry->type; ?>">
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Descrição *</label>
                            <input type="text" name="description" class="form-control" value="<?php echo e($entry->description); ?>" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Valor *</label>
                            <input type="number" name="value" class="form-control" step="0.01" min="0.01" value="<?php echo $entry->value; ?>" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Data de Competência *</label>
                            <input type="date" name="competence_date" class="form-control" value="<?php echo $entry->competence_date; ?>" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Data de Vencimento</label>
                            <input type="date" name="due_date" class="form-control" value="<?php echo $entry->due_date ?? ''; ?>">
                        </div>
                        
                        <?php if ($entry->type === 'entrada'): ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data de Liberação</label>
                                <input type="date" name="release_date" class="form-control" value="<?php echo $entry->release_date ?? ''; ?>">
                                <small class="text-muted">Para recebimentos via cartão que liberam após X dias</small>
                            </div>
                        <?php endif; ?>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo $entry->type === 'entrada' ? 'Cliente' : 'Fornecedor'; ?></label>
                            <select name="<?php echo $entry->type === 'entrada' ? 'client_id' : 'supplier_id'; ?>" class="form-select">
                                <option value="">Selecione</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier->id; ?>" <?php echo ($entry->type === 'entrada' ? $entry->client_id : $entry->supplier_id) == $supplier->id ? 'selected' : ''; ?>>
                                        <?php echo e($supplier->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Categoria</label>
                            <select name="category_id" class="form-select" id="category_id">
                                <option value="">Selecione</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category->id; ?>" <?php echo $entry->category_id == $category->id ? 'selected' : ''; ?>>
                                        <?php echo e($category->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <?php if (!empty($subcategories)): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Subcategoria</label>
                            <select name="subcategory_id" class="form-select" id="subcategory_id">
                                <option value="">Selecione</option>
                                <?php foreach ($subcategories as $subcategory): ?>
                                    <option value="<?php echo $subcategory->id; ?>" <?php echo $entry->subcategory_id == $subcategory->id ? 'selected' : ''; ?>>
                                        <?php echo e($subcategory->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Conta/Cartão</label>
                            <select class="form-select" id="account_select">
                                <option value="">Selecione</option>
                                <optgroup label="Contas Bancárias">
                                    <?php foreach ($bankAccounts as $account): ?>
                                        <option value="account_<?php echo $account->id; ?>" data-account-id="<?php echo $account->id; ?>" <?php echo $entry->bank_account_id == $account->id ? 'selected' : ''; ?>>
                                            <?php echo e($account->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php if (!empty($creditCards)): ?>
                                <optgroup label="Cartões de Crédito">
                                    <?php foreach ($creditCards as $card): ?>
                                        <option value="card_<?php echo $card->id; ?>" data-credit-card="<?php echo $card->id; ?>" <?php echo $entry->credit_card_id == $card->id ? 'selected' : ''; ?>>
                                            <?php echo e($card->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php endif; ?>
                            </select>
                            <input type="hidden" name="bank_account_id" id="bank_account_id" value="<?php echo $entry->bank_account_id ?? ''; ?>">
                            <input type="hidden" name="credit_card_id" id="credit_card_id" value="<?php echo $entry->credit_card_id ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Centro de Custo</label>
                            <select name="cost_center_id" class="form-select" id="cost_center_id">
                                <option value="">Selecione</option>
                                <?php foreach ($costCenters as $costCenter): ?>
                                    <option value="<?php echo $costCenter->id; ?>" <?php echo $entry->cost_center_id == $costCenter->id ? 'selected' : ''; ?>>
                                        <?php echo e($costCenter->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <?php if (!empty($subCostCenters)): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sub Centro de Custo</label>
                            <select name="sub_cost_center_id" class="form-select" id="sub_cost_center_id">
                                <option value="">Selecione</option>
                                <?php foreach ($subCostCenters as $subCostCenter): ?>
                                    <option value="<?php echo $subCostCenter->id; ?>" <?php echo $entry->sub_cost_center_id == $subCostCenter->id ? 'selected' : ''; ?>>
                                        <?php echo e($subCostCenter->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tags</label>
                            <select name="tags[]" class="form-select" multiple>
                                <?php foreach ($tags as $tag): ?>
                                    <option value="<?php echo $tag->id; ?>" <?php echo in_array($tag->id, $entryTagIds) ? 'selected' : ''; ?>>
                                        <?php echo e($tag->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Segure Ctrl (Windows) ou Cmd (Mac) para selecionar múltiplas tags</small>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Observações</label>
                            <textarea name="observations" class="form-control" rows="3"><?php echo e($entry->observations ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-2"></i>
                            Atualizar
                        </button>
                        <a href="<?php echo url('/financial'); ?>" class="btn btn-secondary">
                            <i class="ti ti-x me-2"></i>
                            Cancelar
                        </a>
                        <button type="button" class="btn btn-danger ms-auto" onclick="deleteEntry(<?php echo $entry->id; ?>, <?php echo ($entry->is_recurring || $entry->is_installment || $entry->parent_entry_id) ? 'true' : 'false'; ?>)">
                            <i class="ti ti-trash me-2"></i>
                            Excluir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
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
                window.location.href = '<?php echo url('/financial'); ?>';
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
                    window.location.href = '<?php echo url('/financial'); ?>';
                } else {
                    alert('Erro: ' + data.message);
                }
            });
        }
    }
}

document.getElementById('account_select').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const selectedValue = this.value;
    const creditCardId = selectedOption.getAttribute('data-credit-card');
    const accountId = selectedOption.getAttribute('data-account-id');
    
    if (selectedValue && selectedValue.startsWith('card_')) {
        document.getElementById('credit_card_id').value = creditCardId || '';
        document.getElementById('bank_account_id').value = '';
    } else if (selectedValue && selectedValue.startsWith('account_')) {
        document.getElementById('bank_account_id').value = accountId || '';
        document.getElementById('credit_card_id').value = '';
    } else {
        document.getElementById('credit_card_id').value = '';
        document.getElementById('bank_account_id').value = '';
    }
});

// Carrega subcategorias quando categoria mudar
document.getElementById('category_id').addEventListener('change', function() {
    const categoryId = this.value;
    if (categoryId) {
        fetch(`<?php echo url('/financial/categories'); ?>/${categoryId}/subcategories`)
            .then(response => response.json())
            .then(data => {
                const subcategorySelect = document.getElementById('subcategory_id');
                if (subcategorySelect) {
                    subcategorySelect.innerHTML = '<option value="">Selecione</option>';
                    if (data.subcategories) {
                        data.subcategories.forEach(sub => {
                            const option = document.createElement('option');
                            option.value = sub.id;
                            option.textContent = sub.name;
                            subcategorySelect.appendChild(option);
                        });
                    }
                }
            });
    }
});

// Carrega sub-centros de custo quando centro de custo mudar
document.getElementById('cost_center_id').addEventListener('change', function() {
    const costCenterId = this.value;
    if (costCenterId) {
        fetch(`<?php echo url('/financial/cost-centers'); ?>/${costCenterId}/sub-cost-centers`)
            .then(response => response.json())
            .then(data => {
                const subCostCenterSelect = document.getElementById('sub_cost_center_id');
                if (subCostCenterSelect) {
                    subCostCenterSelect.innerHTML = '<option value="">Selecione</option>';
                    if (data.subCostCenters) {
                        data.subCostCenters.forEach(sub => {
                            const option = document.createElement('option');
                            option.value = sub.id;
                            option.textContent = sub.name;
                            subCostCenterSelect.appendChild(option);
                        });
                    }
                }
            });
    }
});
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

