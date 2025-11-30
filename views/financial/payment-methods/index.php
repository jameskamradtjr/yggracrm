<?php
ob_start();
$title = $title ?? 'Formas de Pagamento';
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Formas de Pagamento</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/financial'); ?>">Financeiro</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Formas de Pagamento</li>
                    </ol>
                </nav>
            </div>
            <div class="col-3">
                <div class="text-end">
                    <a href="<?php echo url('/financial/payment-methods/create'); ?>" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i>Nova Forma de Pagamento
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
                        <th>Tipo</th>
                        <th>Taxa</th>
                        <th>Conta</th>
                        <th>Dias Recebimento</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paymentMethods)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">Nenhuma forma de pagamento cadastrada.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($paymentMethods as $pm): ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($pm->nome); ?></strong>
                                </td>
                                <td>
                                    <?php
                                    $tipos = [
                                        'pix' => 'PIX',
                                        'boleto' => 'Boleto',
                                        'credito' => 'Cartão de Crédito',
                                        'debito' => 'Cartão de Débito',
                                        'transferencia' => 'Transferência',
                                        'dinheiro' => 'Dinheiro',
                                        'outro' => 'Outro'
                                    ];
                                    echo $tipos[$pm->tipo] ?? 'Outro';
                                    ?>
                                </td>
                                <td>
                                    <?php if ($pm->taxa > 0): ?>
                                        <span class="badge bg-warning"><?php echo number_format($pm->taxa, 2, ',', '.'); ?>%</span>
                                    <?php else: ?>
                                        <span class="text-muted">Sem taxa</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $account = $pm->account();
                                    echo $account ? e($account->name) : '<span class="text-muted">N/A</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php if ($pm->dias_recebimento > 0): ?>
                                        <span class="badge bg-info"><?php echo $pm->dias_recebimento; ?> dias</span>
                                    <?php else: ?>
                                        <span class="text-muted">À vista</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($pm->ativo): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="<?php echo url('/financial/payment-methods/' . $pm->id . '/edit'); ?>" 
                                           class="btn btn-sm btn-primary" 
                                           title="Editar">
                                            <i class="ti ti-pencil"></i>
                                        </a>
                                        <form action="<?php echo url('/financial/payment-methods/' . $pm->id . '/delete'); ?>" 
                                              method="POST" 
                                              class="d-inline" 
                                              onsubmit="return confirm('Tem certeza que deseja deletar esta forma de pagamento?');">
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

