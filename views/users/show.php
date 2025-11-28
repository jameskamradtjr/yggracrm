<?php
$title = 'Detalhes do Usuário';

// Inicia captura do conteúdo
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="card-title fw-semibold mb-2">Detalhes do Usuário</h4>
                        <p class="card-subtitle mb-0">Informações completas do usuário</p>
                    </div>
                    <div>
                        <a href="<?php echo url('/users'); ?>" class="btn btn-light me-2">
                            <i class="ti ti-arrow-left me-2"></i>
                            Voltar
                        </a>
                        <a href="<?php echo url('/users/' . $user->id . '/edit'); ?>" class="btn btn-primary">
                            <i class="ti ti-edit me-2"></i>
                            Editar
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <?php if (!empty($user->avatar)): ?>
                                    <img src="<?php echo e($user->avatar); ?>" alt="Avatar" class="rounded-circle mb-3" width="120" height="120">
                                <?php else: ?>
                                    <div class="rounded-circle bg-primary-subtle d-inline-flex align-items-center justify-content-center mb-3" style="width: 120px; height: 120px;">
                                        <span class="fs-4 fw-bold text-primary"><?php echo strtoupper(substr($user->name, 0, 2)); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <h5 class="mb-1"><?php echo e($user->name); ?></h5>
                                <p class="text-muted mb-3"><?php echo e($user->email); ?></p>
                                
                                <?php if ($user->status === 'active'): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php elseif ($user->status === 'inactive'): ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Suspenso</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Informações</h5>
                                <div class="table-responsive">
                                    <table class="table table-borderless">
                                        <tbody>
                                            <tr>
                                                <td class="fw-semibold" style="width: 200px;">ID:</td>
                                                <td><?php echo $user->id; ?></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Nome:</td>
                                                <td><?php echo e($user->name); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Email:</td>
                                                <td><?php echo e($user->email); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Telefone:</td>
                                                <td><?php echo e($user->phone ?? 'Não informado'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Status:</td>
                                                <td>
                                                    <?php if ($user->status === 'active'): ?>
                                                        <span class="badge bg-success">Ativo</span>
                                                    <?php elseif ($user->status === 'inactive'): ?>
                                                        <span class="badge bg-secondary">Inativo</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Suspenso</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Roles:</td>
                                                <td>
                                                    <?php 
                                                    $userRoles = $user->roles();
                                                    if (!empty($userRoles)): 
                                                    ?>
                                                        <?php foreach ($userRoles as $role): ?>
                                                            <span class="badge bg-info me-1"><?php echo e($role->name); ?></span>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Sem roles atribuídas</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Criado em:</td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($user->created_at)); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Última atualização:</td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($user->updated_at)); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Último login:</td>
                                                <td>
                                                    <?php if ($user->last_login_at): ?>
                                                        <?php echo date('d/m/Y H:i', strtotime($user->last_login_at)); ?>
                                                        <?php if ($user->last_login_ip): ?>
                                                            <small class="text-muted">(<?php echo e($user->last_login_ip); ?>)</small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Nunca</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
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

