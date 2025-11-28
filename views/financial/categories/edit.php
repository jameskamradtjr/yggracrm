<?php
$title = 'Editar Categoria';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="ti ti-tags me-2"></i>
                    Editar Categoria
                </h4>

                <form method="POST" action="<?php echo url('/financial/categories/' . $category->id); ?>">
                    <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome da Categoria *</label>
                            <input type="text" name="name" class="form-control" value="<?php echo e($category->name); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo *</label>
                            <select name="type" class="form-select" required>
                                <option value="entrada" <?php echo $category->type === 'entrada' ? 'selected' : ''; ?>>Entrada</option>
                                <option value="saida" <?php echo $category->type === 'saida' ? 'selected' : ''; ?>>Saída</option>
                                <option value="outros" <?php echo $category->type === 'outros' ? 'selected' : ''; ?>>Outros</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cor (opcional)</label>
                            <input type="color" name="color" class="form-control form-control-color" value="<?php echo e($category->color ?? '#3b82f6'); ?>">
                            <small class="text-muted">Escolha uma cor para identificar esta categoria</small>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-2"></i>
                            Atualizar
                        </button>
                        <a href="<?php echo url('/financial/categories'); ?>" class="btn btn-secondary">
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

