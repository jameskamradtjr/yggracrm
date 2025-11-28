<?php
$title = 'Gerenciar Usuários';

// Inicia captura do conteúdo
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="card-title fw-semibold mb-2">Usuários</h4>
                        <p class="card-subtitle mb-0">Gerencie os usuários da sua conta</p>
                    </div>
                    <?php if (auth()->check()): ?>
                        <a href="<?php echo url('/users/create'); ?>" class="btn btn-primary">
                            <i class="ti ti-plus me-2"></i>
                            Novo Usuário
                        </a>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>Status</th>
                                <th>Roles</th>
                                <th>Último Login</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        Nenhum usuário encontrado.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user->id; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($user->avatar)): ?>
                                                    <img src="<?php echo e($user->avatar); ?>" alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-primary-subtle d-inline-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                        <span class="fs-6 fw-bold text-primary"><?php echo strtoupper(substr($user->name, 0, 1)); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <span><?php echo e($user->name); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo e($user->email); ?></td>
                                        <td><?php echo e($user->phone ?? '-'); ?></td>
                                        <td>
                                            <?php if ($user->status === 'active'): ?>
                                                <span class="badge bg-success">Ativo</span>
                                            <?php elseif ($user->status === 'inactive'): ?>
                                                <span class="badge bg-secondary">Inativo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Suspenso</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $userRoles = $user->roles();
                                            if (!empty($userRoles)): 
                                            ?>
                                                <?php foreach ($userRoles as $role): ?>
                                                    <span class="badge bg-info me-1"><?php echo e($role->name); ?></span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Sem roles</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user->last_login_at): ?>
                                                <?php echo date('d/m/Y H:i', strtotime($user->last_login_at)); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Nunca</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group">
                                                <a href="<?php echo url('/users/' . $user->id); ?>" class="btn btn-sm btn-info" title="Ver">
                                                    <i class="ti ti-eye"></i>
                                                </a>
                                                <a href="<?php echo url('/users/' . $user->id . '/edit'); ?>" class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="ti ti-edit"></i>
                                                </a>
                                                <?php if ($user->id !== auth()->id()): ?>
                                                    <form action="<?php echo url('/users/' . $user->id . '/delete'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja deletar este usuário?');">
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

