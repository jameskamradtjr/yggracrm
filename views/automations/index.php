<?php
$title = 'Automações';
ob_start();
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Automações</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Automações</li>
                    </ol>
                </nav>
            </div>
            <div class="col-3 text-end">
                <a href="<?php echo url('/automations/builder'); ?>" class="btn btn-primary">
                    <i class="ti ti-plus me-2"></i>
                    Nova Automação
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Descrição</th>
                                <th>Status</th>
                                <th>Criado em</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($automations)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Nenhuma automação encontrada.
                                        <br>
                                        <a href="<?php echo url('/automations/builder'); ?>" class="btn btn-sm btn-primary mt-2">
                                            Criar Primeira Automação
                                        </a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($automations as $automation): ?>
                                    <tr>
                                        <td><?php echo $automation->id; ?></td>
                                        <td><?php echo e($automation->name); ?></td>
                                        <td><?php echo e($automation->description ?? '-'); ?></td>
                                        <td>
                                            <?php if ($automation->is_active): ?>
                                                <span class="badge bg-success">Ativa</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inativa</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($automation->created_at)); ?></td>
                                        <td class="text-end">
                                            <div class="btn-group">
                                                <a href="<?php echo url('/automations/builder/' . $automation->id); ?>" 
                                                   class="btn btn-sm btn-warning" 
                                                   title="Editar">
                                                    <i class="ti ti-edit"></i>
                                                </a>
                                                <a href="<?php echo url('/automations/' . $automation->id . '/executions'); ?>" 
                                                   class="btn btn-sm btn-info" 
                                                   title="Histórico">
                                                    <i class="ti ti-history"></i>
                                                </a>
                                                <form action="<?php echo url('/automations/' . $automation->id . '/delete'); ?>" 
                                                      method="POST" 
                                                      class="d-inline" 
                                                      onsubmit="return confirm('Tem certeza que deseja deletar esta automação?');">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Deletar">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </form>
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
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

