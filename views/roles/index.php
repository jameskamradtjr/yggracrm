<?php
$title = 'Gerenciar Roles';

// Inicia captura do conteúdo
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="card-title fw-semibold mb-2">Roles</h4>
                        <p class="card-subtitle mb-0">Gerencie as funções e permissões do sistema</p>
                    </div>
                    <a href="<?php echo url('/roles/create'); ?>" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i>
                        Nova Role
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Slug</th>
                                <th>Descrição</th>
                                <th>Permissões</th>
                                <th>Tipo</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Filtra apenas roles da conta do owner
                            $ownerId = auth()->id();
                            $filteredRoles = array_filter($roles ?? [], function($role) use ($ownerId) {
                                return $role->user_id == $ownerId;
                            });
                            ?>
                            
                            <?php if (empty($filteredRoles)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Nenhuma role encontrada.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($filteredRoles as $role): ?>
                                    <tr>
                                        <td><?php echo $role->id; ?></td>
                                        <td>
                                            <strong><?php echo e($role->name); ?></strong>
                                        </td>
                                        <td>
                                            <code><?php echo e($role->slug); ?></code>
                                        </td>
                                        <td><?php echo e($role->description ?? '-'); ?></td>
                                        <td>
                                            <?php 
                                            $rolePerms = $role->permissions();
                                            if (!empty($rolePerms)): 
                                            ?>
                                                <span class="badge bg-info"><?php echo count($rolePerms); ?> permissões</span>
                                            <?php else: ?>
                                                <span class="text-muted">Sem permissões</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($role->is_system): ?>
                                                <span class="badge bg-warning">Sistema</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Personalizada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group">
                                                <a href="<?php echo url('/roles/' . $role->id); ?>" class="btn btn-sm btn-info" title="Ver">
                                                    <i class="ti ti-eye"></i>
                                                </a>
                                                <?php if (!$role->is_system): ?>
                                                    <a href="<?php echo url('/roles/' . $role->id . '/edit'); ?>" class="btn btn-sm btn-warning" title="Editar">
                                                        <i class="ti ti-edit"></i>
                                                    </a>
                                                    <form action="<?php echo url('/roles/' . $role->id . '/delete'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja deletar esta role?');">
                                                        <?php echo csrf_field(); ?>
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Deletar">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
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



