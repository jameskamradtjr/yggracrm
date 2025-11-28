<?php
$title = 'Dashboard';

// Inicia captura do conteúdo
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="card-title fw-semibold mb-2">Dashboard</h4>
                        <p class="card-subtitle mb-0">Bem-vindo, <?php echo e($user->name); ?>!</p>
                    </div>
                </div>

                <div class="row">
                    <!-- Card 1 - Usuários -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card border-bottom border-primary">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h4 class="card-title fw-semibold"><?php echo $total_users ?? 0; ?></h4>
                                        <p class="card-subtitle">Usuários</p>
                                    </div>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(93, 135, 255, 0.1);">
                                        <i class="ti ti-users fs-6 text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2 - Roles -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card border-bottom border-success">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h4 class="card-title fw-semibold"><?php echo count($user_roles ?? []); ?></h4>
                                        <p class="card-subtitle">Minhas Roles</p>
                                    </div>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(19, 194, 150, 0.1);">
                                        <i class="ti ti-shield fs-6 text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3 - Status -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card border-bottom border-warning">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h4 class="card-title fw-semibold text-capitalize"><?php echo $user->status ?? 'Ativo'; ?></h4>
                                        <p class="card-subtitle">Status da Conta</p>
                                    </div>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(255, 180, 0, 0.1);">
                                        <i class="ti ti-check fs-6 text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4 - Atividade -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card border-bottom border-danger">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h4 class="card-title fw-semibold">0</h4>
                                        <p class="card-subtitle">Atividades Hoje</p>
                                    </div>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(255, 102, 146, 0.1);">
                                        <i class="ti ti-activity fs-6 text-danger"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informações do Usuário -->
                <div class="row mt-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title fw-semibold mb-4">Informações da Conta</h5>
                                <div class="table-responsive">
                                    <table class="table table-borderless">
                                        <tbody>
                                            <tr>
                                                <td class="fw-semibold" style="width: 200px;">Nome:</td>
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
                                                <td class="fw-semibold">Último Login:</td>
                                                <td><?php echo $user->last_login_at ? date('d/m/Y H:i', strtotime($user->last_login_at)) : 'Primeiro acesso'; ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-4">
                                    <a href="<?php echo url('/profile'); ?>" class="btn btn-primary">Editar Perfil</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title fw-semibold mb-4">Minhas Roles</h5>
                                <?php if (!empty($user_roles)): ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($user_roles as $role): ?>
                                            <li class="list-group-item d-flex align-items-center">
                                                <i class="ti ti-shield me-2 text-primary"></i>
                                                <div>
                                                    <h6 class="mb-0"><?php echo e($role->name); ?></h6>
                                                    <small class="text-muted"><?php echo e($role->description ?? ''); ?></small>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-muted">Nenhuma role atribuída.</p>
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
