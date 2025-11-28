<?php
$title = 'Detalhes da Role';

// Inicia captura do conteúdo
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="card-title fw-semibold mb-2">Detalhes da Role</h4>
                        <p class="card-subtitle mb-0">Informações completas da role</p>
                    </div>
                    <div>
                        <a href="<?php echo url('/roles'); ?>" class="btn btn-light me-2">
                            <i class="ti ti-arrow-left me-2"></i>
                            Voltar
                        </a>
                        <?php if (!$role->is_system): ?>
                            <a href="<?php echo url('/roles/' . $role->id . '/edit'); ?>" class="btn btn-primary">
                                <i class="ti ti-edit me-2"></i>
                                Editar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Informações Básicas</h5>
                                <p class="mb-2">
                                    <strong>Nome:</strong><br>
                                    <?php echo e($role->name); ?>
                                </p>
                                <p class="mb-2">
                                    <strong>Slug:</strong><br>
                                    <code><?php echo e($role->slug); ?></code>
                                </p>
                                <p class="mb-2">
                                    <strong>Descrição:</strong><br>
                                    <?php echo e($role->description ?? '-'); ?>
                                </p>
                                <p class="mb-0">
                                    <strong>Tipo:</strong><br>
                                    <?php if ($role->is_system): ?>
                                        <span class="badge bg-warning">Sistema</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Personalizada</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Permissões</h5>
                                
                                <?php 
                                $rolePerms = $role->permissions();
                                if (empty($rolePerms)): 
                                ?>
                                    <p class="text-muted">Esta role não possui permissões atribuídas.</p>
                                <?php else: ?>
                                    <?php 
                                    // Agrupa permissões por resource
                                    $permsByResource = [];
                                    foreach ($rolePerms as $perm) {
                                        $resource = $perm->resource ?? 'outros';
                                        if (!isset($permsByResource[$resource])) {
                                            $permsByResource[$resource] = [];
                                        }
                                        $permsByResource[$resource][] = $perm;
                                    }
                                    ?>
                                    
                                    <?php foreach ($permsByResource as $resource => $permissions): ?>
                                        <div class="mb-4">
                                            <h6 class="fw-bold mb-2">
                                                <i class="ti ti-folder me-2"></i>
                                                <?php echo ucfirst(str_replace('_', ' ', $resource)); ?>
                                            </h6>
                                            <div class="row">
                                                <?php foreach ($permissions as $permission): ?>
                                                    <div class="col-md-6 mb-2">
                                                        <span class="badge bg-info me-1">
                                                            <i class="ti ti-check me-1"></i>
                                                            <?php echo e($permission->name); ?>
                                                        </span>
                                                        <small class="text-muted">(<?php echo e($permission->action); ?>)</small>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Captura o conteúdo
$content = ob_get_clean();

// Inclui o layout
include base_path('views/layouts/app.php');
?>



