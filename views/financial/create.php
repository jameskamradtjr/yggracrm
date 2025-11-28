<?php
$title = 'Novo Lançamento';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="ti ti-<?php echo $type === 'entrada' ? 'arrow-down' : 'arrow-up'; ?> me-2"></i>
                    Nova <?php echo $type === 'entrada' ? 'Entrada' : ($type === 'saida' ? 'Saída' : 'Transferência'); ?>
                </h4>

                <form method="POST" action="<?php echo url('/financial'); ?>">
                    <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="type" value="<?php echo $type; ?>">
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Descrição *</label>
                            <input type="text" name="description" class="form-control" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Valor *</label>
                            <input type="number" name="value" class="form-control" step="0.01" min="0.01" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Data de Competência *</label>
                            <input type="date" name="competence_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Data de Vencimento</label>
                            <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <?php if ($type === 'entrada'): ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data de Liberação</label>
                                <input type="date" name="release_date" class="form-control">
                                <small class="text-muted">Para recebimentos via cartão que liberam após X dias</small>
                            </div>
                        <?php endif; ?>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo $type === 'entrada' ? 'Cliente' : 'Fornecedor'; ?></label>
                            <select name="<?php echo $type === 'entrada' ? 'client_id' : 'supplier_id'; ?>" class="form-select">
                                <option value="">Selecione</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier->id; ?>">
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
                                    <option value="<?php echo $category->id; ?>">
                                        <?php echo e($category->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Conta/Cartão</label>
                            <select class="form-select" id="account_select">
                                <option value="">Selecione</option>
                                <optgroup label="Contas Bancárias">
                                    <?php foreach ($bankAccounts as $account): ?>
                                        <option value="account_<?php echo $account->id; ?>" data-account-id="<?php echo $account->id; ?>">
                                            <?php echo e($account->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php if (!empty($creditCards)): ?>
                                <optgroup label="Cartões de Crédito">
                                    <?php foreach ($creditCards as $card): ?>
                                        <option value="card_<?php echo $card->id; ?>" data-credit-card="<?php echo $card->id; ?>">
                                            <?php echo e($card->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php endif; ?>
                            </select>
                            <input type="hidden" name="bank_account_id" id="bank_account_id">
                            <input type="hidden" name="credit_card_id" id="credit_card_id">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Centro de Custo</label>
                            <select name="cost_center_id" class="form-select">
                                <option value="">Selecione</option>
                                <?php foreach ($costCenters as $costCenter): ?>
                                    <option value="<?php echo $costCenter->id; ?>">
                                        <?php echo e($costCenter->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tags</label>
                            <input type="text" name="tags" class="form-control" placeholder="Digite as tags separadas por vírgula">
                            <small class="text-muted">Ex: reforma, escritório, sede</small>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Observações</label>
                            <textarea name="observations" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input type="hidden" name="is_recurring" value="0">
                                <input type="checkbox" name="is_recurring" class="form-check-input" id="is_recurring" value="1">
                                <label class="form-check-label" for="is_recurring">
                                    Lançamento Recorrente
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input type="hidden" name="is_installment" value="0">
                                <input type="checkbox" name="is_installment" class="form-check-input" id="is_installment" value="1">
                                <label class="form-check-label" for="is_installment">
                                    Lançamento Parcelado
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3" id="recurrence_fields" style="display: none;">
                            <label class="form-label">Tipo de Recorrência</label>
                            <select name="recurrence_type" class="form-select">
                                <option value="mensal">Mensal</option>
                                <option value="semanal">Semanal</option>
                                <option value="diario">Diário</option>
                                <option value="anual">Anual</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3" id="recurrence_end_date_field" style="display: none;">
                            <label class="form-label">Data Final da Recorrência</label>
                            <input type="date" name="recurrence_end_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                            <small class="text-muted">Até quando os lançamentos serão criados automaticamente</small>
                        </div>
                        
                        <div class="col-md-6 mb-3" id="installment_fields" style="display: none;">
                            <label class="form-label">Total de Parcelas</label>
                            <input type="number" name="total_installments" class="form-control" min="2">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_paid" class="form-check-input" id="is_paid" <?php echo $type === 'saida' ? '' : 'style="display:none"'; ?>>
                                <label class="form-check-label" for="is_paid" <?php echo $type === 'saida' ? '' : 'style="display:none"'; ?>>
                                    Já está pago
                                </label>
                                <input type="checkbox" name="is_received" class="form-check-input" id="is_received" <?php echo $type === 'entrada' ? '' : 'style="display:none"'; ?>>
                                <label class="form-check-label" for="is_received" <?php echo $type === 'entrada' ? '' : 'style="display:none"'; ?>>
                                    Já foi recebido
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-2"></i>
                            Salvar
                        </button>
                        <a href="<?php echo url('/financial'); ?>" class="btn btn-secondary">
                            <i class="ti ti-x me-2"></i>
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('is_recurring').addEventListener('change', function() {
    const recurrenceFields = document.getElementById('recurrence_fields');
    const recurrenceEndDateField = document.getElementById('recurrence_end_date_field');
    if (this.checked) {
        recurrenceFields.style.display = 'block';
        recurrenceEndDateField.style.display = 'block';
    } else {
        recurrenceFields.style.display = 'none';
        recurrenceEndDateField.style.display = 'none';
    }
});

document.getElementById('is_installment').addEventListener('change', function() {
    document.getElementById('installment_fields').style.display = this.checked ? 'block' : 'none';
});

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
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

