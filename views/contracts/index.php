<?php
ob_start();
$title = $title ?? 'Contratos';
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Contratos</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Contratos</li>
                    </ol>
                </nav>
            </div>
            <div class="col-3">
                <div class="text-end">
                    <a href="<?php echo url('/contracts/create'); ?>" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i>Novo Contrato
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="card-title fw-semibold mb-0">Lista de Contratos</h5>
            <form class="d-flex gap-2" method="GET" action="<?php echo url('/contracts'); ?>">
                <select name="status" class="form-select" style="width: auto;">
                    <option value="all" <?php echo ($status ?? 'all') === 'all' ? 'selected' : ''; ?>>Todos os Status</option>
                    <option value="rascunho" <?php echo ($status ?? '') === 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                    <option value="enviado" <?php echo ($status ?? '') === 'enviado' ? 'selected' : ''; ?>>Enviado</option>
                    <option value="aguardando_assinaturas" <?php echo ($status ?? '') === 'aguardando_assinaturas' ? 'selected' : ''; ?>>Aguardando Assinaturas</option>
                    <option value="assinado" <?php echo ($status ?? '') === 'assinado' ? 'selected' : ''; ?>>Assinado</option>
                    <option value="cancelado" <?php echo ($status ?? '') === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
                <input class="form-control" type="search" placeholder="Buscar contrato..." name="search" value="<?php echo e($search ?? ''); ?>">
                <button class="btn btn-outline-primary" type="submit">Buscar</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Título</th>
                        <th>Cliente</th>
                        <th>Status</th>
                        <th>Data Criação</th>
                        <th>Valor</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contracts)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">Nenhum contrato encontrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($contracts as $contract): ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($contract->numero_contrato); ?></strong>
                                </td>
                                <td><?php echo e($contract->titulo); ?></td>
                                <td>
                                    <?php 
                                    $client = $contract->client();
                                    echo $client ? e($client->nome_razao_social) : '<span class="text-muted">N/A</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $status_map = [
                                        'rascunho' => ['badge' => 'secondary', 'text' => 'Rascunho'],
                                        'enviado' => ['badge' => 'info', 'text' => 'Enviado'],
                                        'aguardando_assinaturas' => ['badge' => 'warning', 'text' => 'Aguardando Assinaturas'],
                                        'assinado' => ['badge' => 'success', 'text' => 'Assinado'],
                                        'cancelado' => ['badge' => 'danger', 'text' => 'Cancelado'],
                                        'vencido' => ['badge' => 'dark', 'text' => 'Vencido']
                                    ];
                                    $status_info = $status_map[$contract->status] ?? ['badge' => 'secondary', 'text' => 'Desconhecido'];
                                    ?>
                                    <span class="badge bg-<?php echo $status_info['badge']; ?>"><?php echo $status_info['text']; ?></span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($contract->created_at)); ?></td>
                                <td>
                                    <?php if ($contract->valor_total): ?>
                                        <strong>R$ <?php echo number_format($contract->valor_total, 2, ',', '.'); ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="<?php echo url('/contracts/' . $contract->id); ?>" class="btn btn-sm btn-info" title="Ver Detalhes">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="<?php echo url('/contracts/' . $contract->id . '/edit'); ?>" class="btn btn-sm btn-primary" title="Editar">
                                            <i class="ti ti-pencil"></i>
                                        </a>
                                        <a href="<?php echo url('/contracts/' . $contract->id . '/pdf'); ?>" class="btn btn-sm btn-secondary" title="Baixar PDF" target="_blank">
                                            <i class="ti ti-download"></i>
                                        </a>
                                        <form action="<?php echo url('/contracts/' . $contract->id . '/delete'); ?>" 
                                              method="POST" 
                                              class="d-inline" 
                                              onsubmit="return confirm('Tem certeza que deseja deletar este contrato?');">
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

