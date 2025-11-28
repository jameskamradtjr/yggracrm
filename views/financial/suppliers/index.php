<?php
$title = $title ?? 'Fornecedores';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="card-title mb-0">
                        <i class="ti ti-truck me-2"></i>
                        Fornecedores
                    </h4>
                    <a href="<?php echo url('/financial/suppliers/create'); ?>" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i>
                        Novo Fornecedor
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Nome Fantasia</th>
                                <th>CNPJ</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>Tipo</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($suppliers)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="ti ti-inbox fs-1 d-block mb-2"></i>
                                        Nenhum fornecedor cadastrado
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <tr>
                                        <td><?php echo e($supplier->name); ?></td>
                                        <td><?php echo e($supplier->fantasy_name ?? '-'); ?></td>
                                        <td><?php echo e($supplier->cnpj ?? '-'); ?></td>
                                        <td><?php echo e($supplier->email ?? '-'); ?></td>
                                        <td><?php echo e($supplier->phone ?? '-'); ?></td>
                                        <td>
                                            <?php if ($supplier->is_client): ?>
                                                <span class="badge bg-info">Cliente</span>
                                            <?php endif; ?>
                                            <?php if ($supplier->receives_invoice): ?>
                                                <span class="badge bg-success">Recebe Nota</span>
                                            <?php endif; ?>
                                            <?php if ($supplier->issues_invoice): ?>
                                                <span class="badge bg-primary">Emite Nota</span>
                                            <?php endif; ?>
                                            <?php if (!$supplier->is_client && !$supplier->receives_invoice && !$supplier->issues_invoice): ?>
                                                <span class="badge bg-secondary">Fornecedor</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="<?php echo url('/financial/suppliers/' . $supplier->id . '/edit'); ?>" class="btn btn-sm btn-info" title="Editar">
                                                    <i class="ti ti-edit"></i>
                                                </a>
                                                <form method="POST" action="<?php echo url('/financial/suppliers/' . $supplier->id . '/delete'); ?>" style="display: inline;" onsubmit="return confirm('Deseja realmente excluir este fornecedor?');">
                                                    <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Excluir">
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

