<?php
$title = $title ?? 'Contas Bancárias';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="card-title mb-0">
                        <i class="ti ti-building-bank me-2"></i>
                        Contas Bancárias
                    </h4>
                    <a href="<?php echo url('/financial/bank-accounts/create'); ?>" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i>
                        Nova Conta
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>Banco</th>
                                <th>Agência</th>
                                <th>Conta</th>
                                <th>Saldo</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($accounts)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="ti ti-inbox fs-1 d-block mb-2"></i>
                                        Nenhuma conta cadastrada
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($accounts as $account): ?>
                                    <tr>
                                        <td><?php echo e($account->name); ?></td>
                                        <td>
                                            <?php
                                            $types = [
                                                'conta_corrente' => 'Conta Corrente',
                                                'conta_poupanca' => 'Conta Poupança',
                                                'conta_investimento' => 'Conta Investimento',
                                                'carteira_digital' => 'Carteira Digital',
                                                'outros' => 'Outros'
                                            ];
                                            echo $types[$account->type] ?? $account->type;
                                            ?>
                                        </td>
                                        <td><?php echo e($account->bank_name); ?></td>
                                        <td><?php echo e($account->agency ?? '-'); ?></td>
                                        <td><?php echo e($account->account_number ?? '-'); ?></td>
                                        <td>
                                            <strong class="<?php echo $account->current_balance >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                R$ <?php echo number_format($account->current_balance, 2, ',', '.'); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <a href="<?php echo url('/financial/bank-accounts/' . $account->id . '/edit'); ?>" class="btn btn-sm btn-info">
                                                <i class="ti ti-edit"></i>
                                            </a>
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

