<?php
ob_start();
$title = $title ?? 'Nova Forma de Pagamento';
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Nova Forma de Pagamento</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/financial'); ?>">Financeiro</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/financial/payment-methods'); ?>">Formas de Pagamento</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Nova</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="<?php echo url('/financial/payment-methods'); ?>" method="POST">
            <?php echo csrf_field(); ?>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nome" class="form-label">Nome <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control" 
                           id="nome" 
                           name="nome" 
                           value="<?php echo old('nome'); ?>" 
                           required 
                           placeholder="Ex: PIX, Boleto, Stripe, etc.">
                    <small class="text-muted">Nome da forma de pagamento</small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="tipo" class="form-label">Tipo <span class="text-danger">*</span></label>
                    <select class="form-select" id="tipo" name="tipo" required>
                        <option value="">Selecione o tipo</option>
                        <option value="pix" <?php echo old('tipo') === 'pix' ? 'selected' : ''; ?>>PIX</option>
                        <option value="boleto" <?php echo old('tipo') === 'boleto' ? 'selected' : ''; ?>>Boleto</option>
                        <option value="credito" <?php echo old('tipo') === 'credito' ? 'selected' : ''; ?>>Cartão de Crédito</option>
                        <option value="debito" <?php echo old('tipo') === 'debito' ? 'selected' : ''; ?>>Cartão de Débito</option>
                        <option value="transferencia" <?php echo old('tipo') === 'transferencia' ? 'selected' : ''; ?>>Transferência</option>
                        <option value="dinheiro" <?php echo old('tipo') === 'dinheiro' ? 'selected' : ''; ?>>Dinheiro</option>
                        <option value="outro" <?php echo old('tipo') === 'outro' ? 'selected' : ''; ?>>Outro</option>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="taxa" class="form-label">Taxa (%)</label>
                    <input type="number" 
                           step="0.01" 
                           min="0" 
                           max="100" 
                           class="form-control" 
                           id="taxa" 
                           name="taxa" 
                           value="<?php echo old('taxa', '0.00'); ?>" 
                           placeholder="0.00">
                    <small class="text-muted">Taxa em percentual (ex: 2.99 para 2.99%)</small>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="conta_id" class="form-label">Conta Bancária</label>
                    <select class="form-select" id="conta_id" name="conta_id">
                        <option value="">Selecione uma conta (opcional)</option>
                        <?php foreach ($accounts as $account): ?>
                            <option value="<?php echo $account->id; ?>" <?php echo old('conta_id') == $account->id ? 'selected' : ''; ?>>
                                <?php echo e($account->name); ?> - <?php echo e($account->bank_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Conta onde o dinheiro será recebido</small>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="dias_recebimento" class="form-label">Dias para Recebimento</label>
                    <input type="number" 
                           min="0" 
                           class="form-control" 
                           id="dias_recebimento" 
                           name="dias_recebimento" 
                           value="<?php echo old('dias_recebimento', '0'); ?>" 
                           placeholder="0">
                    <small class="text-muted">Quantos dias demora para receber (0 = à vista)</small>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="adicionar_taxa_como_despesa" 
                               name="adicionar_taxa_como_despesa" 
                               value="1"
                               <?php echo old('adicionar_taxa_como_despesa') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="adicionar_taxa_como_despesa">
                            Adicionar taxa como despesa automaticamente
                        </label>
                        <small class="text-muted d-block">Se marcado, a taxa será adicionada como despesa ao criar o lançamento</small>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="ativo" 
                               name="ativo" 
                               value="1"
                               <?php echo old('ativo', true) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="ativo">
                            Ativo
                        </label>
                        <small class="text-muted d-block">Formas de pagamento inativas não aparecerão nos lançamentos</small>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="observacoes" class="form-label">Observações</label>
                <textarea class="form-control" 
                          id="observacoes" 
                          name="observacoes" 
                          rows="3" 
                          placeholder="Observações adicionais sobre esta forma de pagamento"><?php echo old('observacoes'); ?></textarea>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="<?php echo url('/financial/payment-methods'); ?>" class="btn btn-secondary">
                    <i class="ti ti-arrow-left me-2"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-2"></i>Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

