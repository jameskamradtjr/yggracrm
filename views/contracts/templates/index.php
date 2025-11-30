<?php
ob_start();
$title = $title ?? 'Templates de Contratos';
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Templates de Contratos</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/contracts'); ?>">Contratos</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Templates</li>
                    </ol>
                </nav>
            </div>
            <div class="col-3">
                <div class="text-end">
                    <a href="<?php echo url('/contracts/templates/create'); ?>" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i>Novo Template
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Variáveis</th>
                        <th>Status</th>
                        <th>Data Criação</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($templates)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">Nenhum template encontrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($templates as $template): ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($template->nome); ?></strong>
                                    <?php if ($template->observacoes): ?>
                                        <br><small class="text-muted"><?php echo e(substr($template->observacoes, 0, 50)); ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $variaveis = json_decode($template->variaveis_disponiveis ?? '[]', true);
                                    if (!empty($variaveis)) {
                                        $vars = array_slice($variaveis, 0, 3);
                                        foreach ($vars as $var) {
                                            echo '<span class="badge bg-info me-1">{{' . e($var) . '}}</span>';
                                        }
                                        if (count($variaveis) > 3) {
                                            echo '<span class="text-muted">+' . (count($variaveis) - 3) . ' mais</span>';
                                        }
                                    } else {
                                        echo '<span class="text-muted">Nenhuma</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($template->ativo): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($template->created_at)); ?></td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="<?php echo url('/contracts/templates/' . $template->id . '/edit'); ?>" class="btn btn-sm btn-primary" title="Editar">
                                            <i class="ti ti-pencil"></i>
                                        </a>
                                        <form action="<?php echo url('/contracts/templates/' . $template->id . '/delete'); ?>" 
                                              method="POST" 
                                              class="d-inline" 
                                              onsubmit="return confirm('Tem certeza que deseja deletar este template?');">
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

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

