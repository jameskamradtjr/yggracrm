<?php
$title = 'Editar Fornecedor';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="ti ti-truck me-2"></i>
                    Editar Fornecedor
                </h4>

                <form method="POST" action="<?php echo url('/financial/suppliers/' . $supplier->id); ?>">
                    <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome/Razão Social *</label>
                            <input type="text" name="name" class="form-control" value="<?php echo e($supplier->name); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome Fantasia</label>
                            <input type="text" name="fantasy_name" class="form-control" value="<?php echo e($supplier->fantasy_name ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">CNPJ</label>
                            <input type="text" name="cnpj" class="form-control" value="<?php echo e($supplier->cnpj ?? ''); ?>" placeholder="00.000.000/0000-00">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo e($supplier->email ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefone</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo e($supplier->phone ?? ''); ?>" placeholder="(00) 00000-0000">
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Endereço</label>
                            <textarea name="address" class="form-control" rows="2"><?php echo e($supplier->address ?? ''); ?></textarea>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Informações Adicionais</label>
                            <textarea name="additional_info" class="form-control" rows="3"><?php echo e($supplier->additional_info ?? ''); ?></textarea>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="form-check">
                                <input type="hidden" name="is_client" value="0">
                                <input type="checkbox" name="is_client" class="form-check-input" id="is_client" value="1" <?php echo $supplier->is_client ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_client">
                                    É também cliente
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="form-check">
                                <input type="hidden" name="receives_invoice" value="0">
                                <input type="checkbox" name="receives_invoice" class="form-check-input" id="receives_invoice" value="1" <?php echo $supplier->receives_invoice ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="receives_invoice">
                                    Recebe nota fiscal
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="form-check">
                                <input type="hidden" name="issues_invoice" value="0">
                                <input type="checkbox" name="issues_invoice" class="form-check-input" id="issues_invoice" value="1" <?php echo $supplier->issues_invoice ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="issues_invoice">
                                    Emite nota fiscal
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-2"></i>
                            Atualizar
                        </button>
                        <a href="<?php echo url('/financial/suppliers'); ?>" class="btn btn-secondary">
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

