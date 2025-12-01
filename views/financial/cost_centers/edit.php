<?php
$title = 'Editar Centro de Custo';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="ti ti-building me-2"></i>
                    Editar Centro de Custo
                </h4>

                <form method="POST" action="<?php echo url('/financial/cost-centers/' . $costCenter->id); ?>">
                    <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Nome do Centro de Custo *</label>
                            <input type="text" name="name" class="form-control" required 
                                   value="<?php echo e($costCenter->name); ?>" 
                                   placeholder="Ex: Marketing, Vendas, Administrativo">
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-2"></i>
                            Atualizar
                        </button>
                        <a href="<?php echo url('/financial/cost-centers'); ?>" class="btn btn-secondary">
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

