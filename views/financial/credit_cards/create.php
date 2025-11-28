<?php
$title = 'Novo Cartão de Crédito';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="ti ti-credit-card me-2"></i>
                    Novo Cartão de Crédito
                </h4>

                <form method="POST" action="<?php echo url('/financial/credit-cards'); ?>">
                    <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome do Cartão *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bandeira *</label>
                            <select name="brand" class="form-select" required>
                                <option value="visa">Visa</option>
                                <option value="mastercard">Mastercard</option>
                                <option value="elo">Elo</option>
                                <option value="amex">American Express</option>
                                <option value="hipercard">Hipercard</option>
                                <option value="outros">Outros</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Conta Vinculada</label>
                            <select name="bank_account_id" class="form-select">
                                <option value="">Nenhuma</option>
                                <?php foreach ($bankAccounts as $account): ?>
                                    <option value="<?php echo $account->id; ?>">
                                        <?php echo e($account->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Dia de Fechamento *</label>
                            <input type="number" name="closing_day" class="form-control" min="1" max="31" value="1" required>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Dia de Vencimento *</label>
                            <input type="number" name="due_day" class="form-control" min="1" max="31" value="10" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Limite *</label>
                            <input type="number" name="limit" class="form-control" step="0.01" min="0" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="alert_limit" class="form-check-input" id="alert_limit">
                                <label class="form-check-label" for="alert_limit">
                                    Receber alerta de limite
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Percentual para Alerta</label>
                            <input type="number" name="alert_percentage" class="form-control" min="1" max="100" value="90">
                            <small class="text-muted">Alerta quando atingir X% do limite</small>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-2"></i>
                            Salvar
                        </button>
                        <a href="<?php echo url('/financial/credit-cards'); ?>" class="btn btn-secondary">
                            <i class="ti ti-x me-2"></i>
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

