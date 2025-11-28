<?php
$title = 'Criar Role';

// Inicia captura do conteúdo
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="card-title fw-semibold mb-2">Criar Nova Role</h4>
                        <p class="card-subtitle mb-0">Defina uma nova função com permissões específicas</p>
                    </div>
                    <a href="<?php echo url('/roles'); ?>" class="btn btn-light">
                        <i class="ti ti-arrow-left me-2"></i>
                        Voltar
                    </a>
                </div>

                <form action="<?php echo url('/roles'); ?>" method="POST" id="roleForm">
                    <?php echo csrf_field(); ?>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nome da Role <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo e(old('name')); ?>" required>
                            <small class="text-muted">Ex: Gerente, Vendedor, Analista</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="slug" class="form-label">Slug <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="slug" name="slug" value="<?php echo e(old('slug')); ?>" required>
                            <small class="text-muted">Ex: gerente, vendedor, analista (sem espaços, minúsculas)</small>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="form-label">Descrição <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="3" required><?php echo e(old('description')); ?></textarea>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5 class="mb-4">Permissões por Módulo</h5>
                    
                    <?php if (empty($permissionsByResource)): ?>
                        <div class="alert alert-info">
                            <i class="ti ti-info-circle me-2"></i>
                            Nenhuma permissão disponível. Execute o seeder para criar permissões:
                            <code class="ms-2">php -f database/seeds/InitialDataSeeder.php</code>
                        </div>
                    <?php else: ?>
                        <?php foreach ($permissionsByResource as $resource => $permissions): ?>
                            <div class="card mb-3 border">
                                <div class="card-header bg-primary-subtle">
                                    <div class="form-check">
                                        <input class="form-check-input module-all" type="checkbox" 
                                               id="module_all_<?php echo e($resource); ?>" 
                                               data-module="<?php echo e($resource); ?>">
                                        <label class="form-check-label fw-bold fs-5" for="module_all_<?php echo e($resource); ?>">
                                            <i class="ti ti-folder me-2"></i>
                                            <strong><?php echo ucfirst(str_replace('_', ' ', $resource)); ?></strong>
                                            <span class="text-muted ms-2 fs-6">(Selecionar Todos)</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <?php foreach ($permissions as $permission): ?>
                                            <div class="col-md-6 col-lg-3">
                                                <div class="form-check p-2 border rounded">
                                                    <input class="form-check-input permission-checkbox" 
                                                           type="checkbox" 
                                                           name="permissions[]" 
                                                           value="<?php echo $permission->id; ?>" 
                                                           id="perm_<?php echo $permission->id; ?>"
                                                           data-module="<?php echo e($resource); ?>"
                                                           data-action="<?php echo e($permission->action); ?>">
                                                    <label class="form-check-label w-100" for="perm_<?php echo $permission->id; ?>">
                                                        <div class="fw-semibold"><?php echo e($permission->name); ?></div>
                                                        <small class="text-muted">
                                                            <i class="ti ti-<?php 
                                                                $icon = 'check';
                                                                if ($permission->action === 'create') $icon = 'plus';
                                                                elseif ($permission->action === 'read') $icon = 'eye';
                                                                elseif ($permission->action === 'update') $icon = 'edit';
                                                                elseif ($permission->action === 'delete') $icon = 'trash';
                                                                echo $icon;
                                                            ?> me-1"></i>
                                                            <?php echo e($permission->action); ?>
                                                        </small>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="<?php echo url('/roles'); ?>" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Criar Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Controla checkbox "Todos" de cada módulo
    document.querySelectorAll('.module-all').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const module = this.dataset.module;
            const moduleCheckboxes = document.querySelectorAll('.permission-checkbox[data-module="' + module + '"]');
            
            moduleCheckboxes.forEach(function(cb) {
                cb.checked = checkbox.checked;
            });
        });
    });
    
    // Controla checkboxes individuais - desmarca "Todos" se algum for desmarcado
    document.querySelectorAll('.permission-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const module = this.dataset.module;
            const moduleAll = document.getElementById('module_all_' + module);
            const moduleCheckboxes = document.querySelectorAll('.permission-checkbox[data-module="' + module + '"]');
            const checkedCount = Array.from(moduleCheckboxes).filter(cb => cb.checked).length;
            
            // Se todos estão marcados, marca "Todos", senão desmarca
            moduleAll.checked = (checkedCount === moduleCheckboxes.length);
        });
    });
    
    // Marca "Todos" inicialmente se todas as permissões do módulo estiverem marcadas
    document.querySelectorAll('.module-all').forEach(function(checkbox) {
        const module = checkbox.dataset.module;
        const moduleCheckboxes = document.querySelectorAll('.permission-checkbox[data-module="' + module + '"]');
        const checkedCount = Array.from(moduleCheckboxes).filter(cb => cb.checked).length;
        
        if (checkedCount === moduleCheckboxes.length && moduleCheckboxes.length > 0) {
            checkbox.checked = true;
        }
    });
});
</script>

<?php
// Captura o conteúdo
$content = ob_get_clean();

// Inclui o layout
include base_path('views/layouts/app.php');
?>

