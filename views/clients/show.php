<?php
ob_start();
$title = $title ?? 'Detalhes do Cliente';
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Detalhes do Cliente</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/clients'); ?>">Clientes</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page"><?php echo e($client->nome_razao_social); ?></li>
                    </ol>
                </nav>
            </div>
            <div class="col-3">
                <div class="text-end">
                    <a href="<?php echo url('/clients/' . $client->id . '/edit'); ?>" class="btn btn-primary">
                        <i class="ti ti-edit me-2"></i>Editar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <!-- Informações do Cliente -->
        <?php include base_path('views/clients/_details.php'); ?>
    </div>
    
    <div class="col-md-8">
        <!-- Histórico de Negociações -->
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <i class="ti ti-history me-2"></i>
                    Histórico de Negociações
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($history)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="ti ti-inbox fs-1 d-block mb-2"></i>
                        <p>Nenhum histórico de negociações encontrado.</p>
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($history as $item): ?>
                            <div class="timeline-item mb-4 pb-4 border-bottom">
                                <div class="d-flex align-items-start">
                                    <div class="timeline-icon me-3">
                                        <?php if ($item['tipo'] === 'lead'): ?>
                                            <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="ti ti-user-plus text-primary"></i>
                                            </div>
                                        <?php elseif ($item['tipo'] === 'proposal'): ?>
                                            <div class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="ti ti-file-text text-success"></i>
                                            </div>
                                        <?php elseif ($item['tipo'] === 'contact'): ?>
                                            <div class="rounded-circle bg-info-subtle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="ti ti-phone text-info"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <div>
                                                <h6 class="mb-1 fw-semibold"><?php echo e($item['titulo']); ?></h6>
                                                <p class="text-muted mb-0 small"><?php echo e($item['descricao']); ?></p>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted">
                                                    <?php 
                                                    $dataFormatada = date('d/m/Y', strtotime($item['data']));
                                                    $horaFormatada = '';
                                                    if (strpos($item['data'], ' ') !== false) {
                                                        $horaFormatada = date('H:i', strtotime($item['data']));
                                                    }
                                                    echo $dataFormatada . ($horaFormatada ? ' às ' . $horaFormatada : '');
                                                    ?>
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <?php if ($item['tipo'] === 'lead'): ?>
                                            <div class="mt-2">
                                                <?php if (isset($item['dados']['etapa_funil'])): ?>
                                                    <span class="badge bg-primary me-1">
                                                        <?php echo ucfirst(str_replace('_', ' ', $item['dados']['etapa_funil'])); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (isset($item['dados']['score_potencial'])): ?>
                                                    <span class="badge bg-warning me-1">
                                                        Score: <?php echo $item['dados']['score_potencial']; ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (isset($item['dados']['origem'])): ?>
                                                    <span class="badge bg-info">
                                                        <?php echo e($item['dados']['origem']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <a href="<?php echo url('/leads/' . $item['id']); ?>" class="btn btn-sm btn-outline-primary ms-2">
                                                    <i class="ti ti-eye me-1"></i>Ver Lead
                                                </a>
                                            </div>
                                        <?php elseif ($item['tipo'] === 'proposal'): ?>
                                            <div class="mt-2">
                                                <span class="badge bg-<?php echo $item['dados']['status'] === 'aceita' ? 'success' : ($item['dados']['status'] === 'recusada' ? 'danger' : 'warning'); ?>">
                                                    <?php echo ucfirst($item['dados']['status']); ?>
                                                </span>
                                                <?php if (isset($item['dados']['valor']) && $item['dados']['valor'] > 0): ?>
                                                    <span class="badge bg-info ms-1">
                                                        R$ <?php echo number_format((float)$item['dados']['valor'], 2, ',', '.'); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($item['tipo'] === 'contact'): ?>
                                            <div class="mt-2">
                                                <span class="badge bg-info">
                                                    <?php echo ucfirst($item['dados']['tipo']); ?>
                                                </span>
                                                <?php if (isset($item['dados']['resultado'])): ?>
                                                    <span class="badge bg-<?php echo $item['dados']['resultado'] === 'sucesso' ? 'success' : ($item['dados']['resultado'] === 'falha' ? 'danger' : 'warning'); ?> ms-1">
                                                        <?php echo ucfirst($item['dados']['resultado']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (isset($item['dados']['assunto'])): ?>
                                                    <p class="text-muted small mb-0 mt-1">
                                                        <strong>Assunto:</strong> <?php echo e($item['dados']['assunto']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if (isset($item['dados']['descricao'])): ?>
                                                    <p class="text-muted small mb-0 mt-1">
                                                        <?php echo e(substr($item['dados']['descricao'], 0, 150)); ?><?php echo strlen($item['dados']['descricao']) > 150 ? '...' : ''; ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

