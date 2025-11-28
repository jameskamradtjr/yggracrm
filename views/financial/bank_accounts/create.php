<?php
$title = 'Nova Conta Bancária';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="ti ti-building-bank me-2"></i>
                    Nova Conta Bancária
                </h4>

                <form method="POST" action="<?php echo url('/financial/bank-accounts'); ?>">
                    <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome da Conta *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo de Conta *</label>
                            <select name="type" class="form-select" required>
                                <option value="conta_corrente">Conta Corrente</option>
                                <option value="conta_poupanca">Conta Poupança</option>
                                <option value="conta_investimento">Conta Investimento</option>
                                <option value="carteira_digital">Carteira Digital</option>
                                <option value="outros">Outros</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome do Banco *</label>
                            <input type="text" name="bank_name" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Agência</label>
                            <input type="text" name="agency" class="form-control">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Número da Conta</label>
                            <input type="text" name="account_number" class="form-control">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Dígito</label>
                            <input type="text" name="digit" class="form-control" maxlength="2">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Saldo Inicial</label>
                            <input type="number" name="initial_balance" class="form-control" step="0.01" value="0">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="hide_balance" class="form-check-input" id="hide_balance">
                                <label class="form-check-label" for="hide_balance">
                                    Ocultar saldo na tela inicial
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">E-mail para Alertas</label>
                            <input type="email" name="alert_email" class="form-control">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="alert_when_zero" class="form-check-input" id="alert_when_zero">
                                <label class="form-check-label" for="alert_when_zero">
                                    Receber alerta quando saldo for zero
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-2"></i>
                            Salvar
                        </button>
                        <a href="<?php echo url('/financial/bank-accounts'); ?>" class="btn btn-secondary">
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

