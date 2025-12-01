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
                            <input type="text" id="value_input" class="form-control" placeholder="R$ 0,00" required>
                            <input type="hidden" name="value" id="value_numeric">
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
                                <label class="form-label">Forma de Pagamento</label>
                                <select name="payment_method_id" class="form-select" id="payment_method_id">
                                    <option value="">Selecione (opcional)</option>
                                    <?php foreach ($paymentMethods as $pm): ?>
                                        <option value="<?php echo $pm->id; ?>" 
                                                data-taxa="<?php echo $pm->taxa; ?>"
                                                data-conta-id="<?php echo $pm->conta_id ?? ''; ?>"
                                                data-dias-recebimento="<?php echo $pm->dias_recebimento; ?>"
                                                data-adicionar-taxa="<?php echo $pm->adicionar_taxa_como_despesa ? '1' : '0'; ?>">
                                            <?php echo e($pm->nome); ?>
                                            <?php if ($pm->taxa > 0): ?>
                                                (Taxa: <?php echo number_format($pm->taxa, 2, ',', '.'); ?>%)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Ao selecionar, preenche automaticamente conta, taxa e data de liberação</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data de Liberação</label>
                                <input type="date" name="data_liberacao" id="data_liberacao" class="form-control">
                                <small class="text-muted">Calculada automaticamente baseada na forma de pagamento</small>
                            </div>
                            
                            <div class="col-md-6 mb-3" id="taxa_info" style="display: none;">
                                <div class="alert alert-info mb-0">
                                    <strong>Taxa:</strong> <span id="taxa_valor">R$ 0,00</span>
                                    <br>
                                    <small>Valor líquido após taxa: <span id="valor_liquido">R$ 0,00</span></small>
                                </div>
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
                                    <optgroup label="<?php echo e($category->name); ?>">
                                        <option value="<?php echo $category->id; ?>" data-subcategory-id="">
                                            <?php echo e($category->name); ?>
                                        </option>
                                        <?php if (!empty($category->subcategories)): ?>
                                            <?php foreach ($category->subcategories as $subcategory): ?>
                                                <option value="<?php echo $category->id; ?>" data-subcategory-id="<?php echo $subcategory->id; ?>">
                                                    └ <?php echo e($subcategory->name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="subcategory_id" id="subcategory_id">
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
                            <select name="cost_center_id" class="form-select" id="cost_center_id">
                                <option value="">Selecione</option>
                                <?php foreach ($costCenters as $costCenter): ?>
                                    <optgroup label="<?php echo e($costCenter->name); ?>">
                                        <option value="<?php echo $costCenter->id; ?>" data-sub-cost-center-id="">
                                            <?php echo e($costCenter->name); ?>
                                        </option>
                                        <?php if (!empty($costCenter->subCostCenters)): ?>
                                            <?php foreach ($costCenter->subCostCenters as $subCostCenter): ?>
                                                <option value="<?php echo $costCenter->id; ?>" data-sub-cost-center-id="<?php echo $subCostCenter->id; ?>">
                                                    └ <?php echo e($subCostCenter->name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="sub_cost_center_id" id="sub_cost_center_id">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tags</label>
                            <div id="tags-container" class="tags-input-container">
                                <div class="tags-list" id="tags-list"></div>
                                <input type="text" id="tags-input" class="form-control tags-input" placeholder="Digite e pressione Enter ou vírgula para adicionar">
                            </div>
                            <input type="hidden" name="tags" id="tags-hidden">
                            <small class="text-muted">Digite e pressione Enter ou vírgula para adicionar tags</small>
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

// Preenche automaticamente quando forma de pagamento é selecionada (apenas para entradas)
<?php if ($type === 'entrada'): ?>
document.getElementById('payment_method_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const paymentMethodId = this.value;
    
    if (!paymentMethodId) {
        // Limpa campos se nenhuma forma de pagamento for selecionada
        document.getElementById('taxa_info').style.display = 'none';
        document.getElementById('data_liberacao').value = '';
        return;
    }
    
    const taxa = parseFloat(selectedOption.getAttribute('data-taxa')) || 0;
    const contaId = selectedOption.getAttribute('data-conta-id') || '';
    const diasRecebimento = parseInt(selectedOption.getAttribute('data-dias-recebimento')) || 0;
    const adicionarTaxa = selectedOption.getAttribute('data-adicionar-taxa') === '1';
    
    // Preenche conta bancária se houver
    if (contaId) {
        const accountSelect = document.getElementById('account_select');
        // Procura a opção com data-account-id igual a contaId
        for (let i = 0; i < accountSelect.options.length; i++) {
            const opt = accountSelect.options[i];
            if (opt.getAttribute('data-account-id') === contaId) {
                accountSelect.value = opt.value;
                // Dispara o evento change para preencher os campos hidden
                accountSelect.dispatchEvent(new Event('change'));
                break;
            }
        }
    }
    
    // Calcula data de liberação baseada na data de vencimento
    const dueDateInput = document.querySelector('input[name="due_date"]');
    if (dueDateInput && dueDateInput.value) {
        const dueDate = new Date(dueDateInput.value);
        if (diasRecebimento > 0) {
            dueDate.setDate(dueDate.getDate() + diasRecebimento);
        }
        document.getElementById('data_liberacao').value = dueDate.toISOString().split('T')[0];
    } else {
        // Se não tiver data de vencimento, usa a data de competência
        const competenceDateInput = document.querySelector('input[name="competence_date"]');
        if (competenceDateInput && competenceDateInput.value) {
            const competenceDate = new Date(competenceDateInput.value);
            if (diasRecebimento > 0) {
                competenceDate.setDate(competenceDate.getDate() + diasRecebimento);
            }
            document.getElementById('data_liberacao').value = competenceDate.toISOString().split('T')[0];
        }
    }
    
    // Calcula e exibe taxa se houver
    const valueInput = document.getElementById('value_input');
    if (taxa > 0 && valueInput && valueInput.value) {
        // Remove formatação para calcular
        const unformattedValue = valueInput.value.replace('R$', '').replace(/\./g, '').replace(',', '.').trim();
        const valorBruto = parseFloat(unformattedValue) || 0;
        const valorTaxa = valorBruto * (taxa / 100);
        const valorLiquido = valorBruto - valorTaxa;
        
        document.getElementById('taxa_valor').textContent = 'R$ ' + valorTaxa.toFixed(2).replace('.', ',');
        document.getElementById('valor_liquido').textContent = 'R$ ' + valorLiquido.toFixed(2).replace('.', ',');
        document.getElementById('taxa_info').style.display = 'block';
    } else {
        document.getElementById('taxa_info').style.display = 'none';
    }
    
    // Recalcula taxa quando o valor mudar
    if (taxa > 0 && valueInput) {
        if (!valueInput.hasAttribute('data-taxa-listener')) {
            valueInput.setAttribute('data-taxa-listener', 'true');
            valueInput.addEventListener('input', function() {
                const paymentMethodSelect = document.getElementById('payment_method_id');
                if (paymentMethodSelect.value) {
                    paymentMethodSelect.dispatchEvent(new Event('change'));
                }
            });
        }
    }
    
    // Recalcula data de liberação quando a data de vencimento mudar
    const dueDateInput2 = document.querySelector('input[name="due_date"]');
    if (dueDateInput2 && !dueDateInput2.hasAttribute('data-liberacao-listener')) {
        dueDateInput2.setAttribute('data-liberacao-listener', 'true');
        dueDateInput2.addEventListener('change', function() {
            const paymentMethodSelect = document.getElementById('payment_method_id');
            if (paymentMethodSelect.value) {
                paymentMethodSelect.dispatchEvent(new Event('change'));
            }
        });
    }
});
<?php endif; ?>

// Atualiza subcategoria quando categoria é selecionada
document.getElementById('category_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const subcategoryId = selectedOption.getAttribute('data-subcategory-id');
    document.getElementById('subcategory_id').value = subcategoryId || '';
});

// Atualiza subcentro de custo quando centro de custo é selecionado
document.getElementById('cost_center_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const subCostCenterId = selectedOption.getAttribute('data-sub-cost-center-id');
    document.getElementById('sub_cost_center_id').value = subCostCenterId || '';
});

// Máscara de moeda brasileira
(function() {
    const valueInput = document.getElementById('value_input');
    const valueNumeric = document.getElementById('value_numeric');
    
    function formatCurrency(value) {
        // Remove tudo que não é número
        value = value.replace(/\D/g, '');
        
        // Se estiver vazio, retorna R$ 0,00
        if (!value || value === '0') {
            return 'R$ 0,00';
        }
        
        // Converte para número e divide por 100 para ter centavos
        value = (parseFloat(value) / 100).toFixed(2);
        
        // Formata como moeda brasileira
        value = value.replace('.', ',');
        value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        
        return 'R$ ' + value;
    }
    
    function unformatCurrency(value) {
        // Remove R$, espaços e pontos, substitui vírgula por ponto
        return value.replace('R$', '').replace(/\./g, '').replace(',', '.').trim();
    }
    
    if (valueInput) {
        // Aplica máscara ao digitar
        valueInput.addEventListener('input', function(e) {
            let value = e.target.value;
            const cursorPosition = e.target.selectionStart;
            
            // Remove formatação para calcular posição do cursor
            const unformattedBefore = unformatCurrency(value);
            const lengthBefore = value.length;
            
            // Aplica formatação
            value = formatCurrency(value);
            const lengthAfter = value.length;
            
            // Ajusta posição do cursor
            const cursorOffset = lengthAfter - lengthBefore;
            e.target.value = value;
            const newPosition = Math.max(0, Math.min(cursorPosition + cursorOffset, value.length));
            e.target.setSelectionRange(newPosition, newPosition);
            
            // Atualiza campo hidden com valor numérico
            if (valueNumeric) {
                const numericValue = unformatCurrency(value) || '0.00';
                valueNumeric.value = numericValue;
            }
        });
        
        // Aplica máscara ao perder foco
        valueInput.addEventListener('blur', function(e) {
            let value = e.target.value;
            if (!value || value.trim() === '' || value === 'R$') {
                e.target.value = 'R$ 0,00';
                if (valueNumeric) {
                    valueNumeric.value = '0.00';
                }
            } else {
                value = formatCurrency(value);
                e.target.value = value;
                if (valueNumeric) {
                    valueNumeric.value = unformatCurrency(value);
                }
            }
        });
        
        // Formata valor inicial
        valueInput.value = 'R$ 0,00';
    }
    
    // Antes de enviar o formulário, garante que o valor numérico está correto
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (valueInput && valueNumeric) {
                const numericValue = unformatCurrency(valueInput.value);
                // Garante que o valor seja pelo menos 0.01
                const finalValue = parseFloat(numericValue) > 0 ? numericValue : '0.01';
                valueNumeric.value = finalValue;
            }
        });
    }
})();

// Componente de Tags
(function() {
    const tagsList = document.getElementById('tags-list');
    const tagsInput = document.getElementById('tags-input');
    const tagsHidden = document.getElementById('tags-hidden');
    let tags = [];
    
    function updateHiddenInput() {
        tagsHidden.value = tags.join(',');
    }
    
    function addTag(tagText) {
        tagText = tagText.trim();
        if (tagText && !tags.includes(tagText)) {
            tags.push(tagText);
            const tagElement = document.createElement('span');
            tagElement.className = 'badge bg-primary me-1 mb-1';
            tagElement.innerHTML = tagText + ' <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.7em;" onclick="removeTag(\'' + tagText.replace(/'/g, "\\'") + '\')"></button>';
            tagsList.appendChild(tagElement);
            updateHiddenInput();
        }
    }
    
    function removeTag(tagText) {
        tags = tags.filter(t => t !== tagText);
        tagsList.innerHTML = '';
        tags.forEach(tag => {
            const tagElement = document.createElement('span');
            tagElement.className = 'badge bg-primary me-1 mb-1';
            tagElement.innerHTML = tag + ' <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.7em;" onclick="removeTag(\'' + tag.replace(/'/g, "\\'") + '\')"></button>';
            tagsList.appendChild(tagElement);
        });
        updateHiddenInput();
    }
    
    window.removeTag = removeTag;
    
    tagsInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            const value = this.value.trim();
            if (value) {
                addTag(value);
                this.value = '';
            }
        }
    });
    
    tagsInput.addEventListener('blur', function() {
        const value = this.value.trim();
        if (value) {
            addTag(value);
            this.value = '';
        }
    });
})();
</script>

<style>
.tags-input-container {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    padding: 0.375rem 0.75rem;
    min-height: 38px;
    background-color: #fff;
}

.tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    margin-bottom: 0.25rem;
}

.tags-input {
    border: none;
    outline: none;
    padding: 0;
    margin: 0;
    width: 100%;
    background: transparent;
}

.tags-input:focus {
    box-shadow: none;
}
</style>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

