<?php
$title = $title ?? 'Cartões de Crédito';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="card-title mb-0">
                        <i class="ti ti-credit-card me-2"></i>
                        Cartões de Crédito
                    </h4>
                    <a href="<?php echo url('/financial/credit-cards/create'); ?>" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i>
                        Novo Cartão
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Bandeira</th>
                                <th>Fechamento</th>
                                <th>Vencimento</th>
                                <th>Limite</th>
                                <th>Gasto Atual</th>
                                <th>Disponível</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cards)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="ti ti-inbox fs-1 d-block mb-2"></i>
                                        Nenhum cartão cadastrado
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cards as $card): ?>
                                    <tr>
                                        <td><?php echo e($card->name); ?></td>
                                        <td>
                                            <?php
                                            $brands = [
                                                'visa' => 'Visa',
                                                'mastercard' => 'Mastercard',
                                                'elo' => 'Elo',
                                                'amex' => 'American Express',
                                                'hipercard' => 'Hipercard',
                                                'outros' => 'Outros'
                                            ];
                                            echo $brands[$card->brand] ?? $card->brand;
                                            ?>
                                        </td>
                                        <td>Dia <?php echo $card->closing_day; ?></td>
                                        <td>Dia <?php echo $card->due_day; ?></td>
                                        <td>R$ <?php echo number_format($card->limit, 2, ',', '.'); ?></td>
                                        <td>
                                            <span class="text-danger">
                                                R$ <?php echo number_format($card->current_spent ?? 0, 2, ',', '.'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong class="<?php echo ($card->available_limit ?? $card->limit) > 0 ? 'text-success' : 'text-danger'; ?>">
                                                R$ <?php echo number_format($card->available_limit ?? $card->limit, 2, ',', '.'); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <a href="<?php echo url('/financial/credit-cards/' . $card->id . '/edit'); ?>" class="btn btn-sm btn-info">
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

