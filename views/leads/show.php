<?php
$title = 'Detalhes do Lead';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title fw-semibold mb-0">Detalhes do Lead</h4>
                    <a href="<?php echo url('/leads'); ?>" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-2"></i>
                        Voltar
                    </a>
                </div>

                <div class="row">
                    <!-- Informações Básicas -->
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="ti ti-user me-2"></i>Informações Básicas</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-semibold" style="width: 40%;">Nome:</td>
                                        <td><?php echo e($lead->nome); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Email:</td>
                                        <td><?php echo e($lead->email); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Telefone:</td>
                                        <td><?php echo e($lead->telefone); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Instagram:</td>
                                        <td>
                                            <?php if ($lead->instagram): ?>
                                                <a href="https://instagram.com/<?php echo e(ltrim($lead->instagram, '@')); ?>" target="_blank">
                                                    <?php echo e($lead->instagram); ?>
                                                    <i class="ti ti-external-link ms-1"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Não informado</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Ramo:</td>
                                        <td><?php echo e($lead->ramo ?? 'Não informado'); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Análise da IA -->
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="ti ti-brain me-2"></i>Análise da IA</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-semibold" style="width: 40%;">Score Potencial:</td>
                                        <td>
                                            <div class="progress" style="height: 25px;">
                                                <div class="progress-bar <?php 
                                                    echo $lead->score_potencial >= 70 ? 'bg-success' : 
                                                        ($lead->score_potencial >= 40 ? 'bg-warning' : 'bg-danger'); 
                                                ?>" 
                                                role="progressbar" 
                                                style="width: <?php echo $lead->score_potencial; ?>%">
                                                    <?php echo $lead->score_potencial; ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Urgência:</td>
                                        <td>
                                            <?php
                                            $urgenciaClass = match($lead->urgencia) {
                                                'alta' => 'danger',
                                                'media' => 'warning',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?php echo $urgenciaClass; ?>">
                                                <?php echo ucfirst($lead->urgencia); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Status Kanban:</td>
                                        <td>
                                            <?php
                                            $statusLabels = [
                                                'cold' => 'Cold (0-10k)',
                                                'morno' => 'Morno (10-50k)',
                                                'quente' => 'Quente (50-200k)',
                                                'ultra_quente' => 'Ultra Quente (200k+)'
                                            ];
                                            $statusClass = match($lead->status_kanban) {
                                                'ultra_quente' => 'danger',
                                                'quente' => 'warning',
                                                'morno' => 'info',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                <?php echo $statusLabels[$lead->status_kanban] ?? $lead->status_kanban; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Faturamento:</td>
                                        <td>
                                            <strong><?php echo e($lead->faturamento_raw); ?></strong>
                                            <small class="text-muted">(<?php echo e($lead->faturamento_categoria); ?>)</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Investimento:</td>
                                        <td>
                                            <strong><?php echo e($lead->invest_raw); ?></strong>
                                            <small class="text-muted">(<?php echo e($lead->invest_categoria); ?>)</small>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resumo da IA -->
                <?php if ($lead->resumo): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card border">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="ti ti-file-text me-2"></i>Resumo da Análise</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-0"><?php echo nl2br(e($lead->resumo)); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tags da IA -->
                <?php 
                $tags = $lead->getTagsAi();
                if (!empty($tags)):
                ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card border">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0"><i class="ti ti-tags me-2"></i>Tags da IA</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($tags as $tag): ?>
                                    <span class="badge bg-primary-subtle text-primary me-2 mb-2" style="font-size: 0.9rem;">
                                        <?php echo e($tag); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Informações Adicionais -->
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0"><i class="ti ti-info-circle me-2"></i>Informações Adicionais</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-semibold" style="width: 40%;">Objetivo:</td>
                                        <td><?php echo e($lead->objetivo ?? 'Não informado'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Faz Tráfego:</td>
                                        <td>
                                            <?php if ($lead->faz_trafego): ?>
                                                <span class="badge bg-success">Sim</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Não</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Data de Cadastro:</td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($lead->created_at)); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botão Reanalisar -->
                <div class="row mt-4">
                    <div class="col-12">
                        <button type="button" id="btn-reanalyze" class="btn btn-primary">
                            <i class="ti ti-refresh me-2"></i>
                            Reanalisar com IA
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('btn-reanalyze').addEventListener('click', function() {
    const btn = this;
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader me-2"></i>Reanalisando...';
    
    fetch('<?php echo url('/leads/' . $lead->id . '/reanalyze'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Lead reanalisado com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao reanalisar lead.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

