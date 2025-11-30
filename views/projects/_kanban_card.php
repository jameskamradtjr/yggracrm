<?php
$card = $cardData['card'];
$users = $cardData['users'];
$project = $cardData['project'];

$checklistProgress = $card->getChecklistProgress();
$tags = $card->tags();
$responsible = $card->responsible();
?>

<div data-draggable="true" class="card mb-3" data-card-id="<?php echo $card->id; ?>" style="cursor: pointer;" onclick="abrirEdicaoCard(<?php echo $card->id; ?>)">
    <div class="card-body">
        <div class="task-header d-flex justify-content-between align-items-start mb-2">
            <div class="flex-grow-1">
                <h6 class="mb-1 fw-semibold" data-item-title="<?php echo e($card->titulo); ?>">
                    <?php echo e($card->titulo); ?>
                </h6>
            </div>
            <div class="dropdown" onclick="event.stopPropagation();">
                <a class="dropdown-toggle" href="javascript:void(0)" role="button" data-bs-toggle="dropdown">
                    <i class="ti ti-dots-vertical text-dark"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="javascript:void(0);" onclick="abrirEdicaoCard(<?php echo $card->id; ?>)">
                        <i class="ti ti-pencil fs-5 me-2"></i>Editar
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="event.stopPropagation(); excluirCard(<?php echo $card->id; ?>)">
                        <i class="ti ti-trash fs-5 me-2"></i>Excluir
                    </a>
                </div>
            </div>
        </div>
        
        <?php if ($card->descricao): ?>
            <div class="task-content mb-2">
                <p class="mb-0 text-muted small" style="font-size: 0.85rem;">
                    <?php echo e(substr($card->descricao, 0, 100)); ?><?php echo strlen($card->descricao) > 100 ? '...' : ''; ?>
                </p>
            </div>
        <?php endif; ?>
        
        <!-- Tags -->
        <?php if (!empty($tags)): ?>
            <div class="mb-2">
                <?php foreach ($tags as $tag): ?>
                    <span class="badge fs-1" style="background-color: <?php echo e($tag->cor); ?>; color: white;">
                        <?php echo e($tag->nome); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="task-body">
            <div class="task-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <!-- Checklist Progress -->
                <?php if ($checklistProgress['total'] > 0): ?>
                    <div class="tb-section-1">
                        <div class="d-flex align-items-center gap-1">
                            <i class="ti ti-checklist fs-5"></i>
                            <small class="text-muted">
                                <?php echo $checklistProgress['concluidos']; ?>/<?php echo $checklistProgress['total']; ?>
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Prioridade -->
                <div class="tb-section-2">
                    <span class="badge bg-<?php 
                        echo match($card->prioridade) {
                            'baixa' => 'secondary',
                            'media' => 'info',
                            'alta' => 'warning',
                            'urgente' => 'danger',
                            default => 'secondary'
                        };
                    ?> fs-1">
                        <?php echo ucfirst($card->prioridade); ?>
                    </span>
                </div>
            </div>
            
            <!-- Responsável -->
            <?php if ($responsible): ?>
                <div class="mt-2">
                    <small class="text-muted d-flex align-items-center">
                        <i class="ti ti-user me-1"></i>
                        <?php echo e($responsible->name); ?>
                    </small>
                </div>
            <?php endif; ?>
            
            <!-- Prazo -->
            <?php if ($card->data_prazo): ?>
                <div class="mt-1">
                    <small class="text-muted d-flex align-items-center">
                        <i class="ti ti-calendar me-1"></i>
                        <?php 
                        $prazo = new \DateTime($card->data_prazo);
                        $hoje = new \DateTime();
                        $diferenca = $hoje->diff($prazo);
                        $dias = (int)$diferenca->format('%r%a');
                        
                        if ($dias < 0) {
                            $cor = 'danger';
                            $texto = 'Atrasado (' . abs($dias) . ' dias)';
                        } elseif ($dias == 0) {
                            $cor = 'warning';
                            $texto = 'Hoje';
                        } elseif ($dias <= 3) {
                            $cor = 'warning';
                            $texto = $dias . ' dias';
                        } else {
                            $cor = 'muted';
                            $texto = date('d/m/Y', strtotime($card->data_prazo));
                        }
                        ?>
                        <span class="text-<?php echo $cor; ?>">
                            <?php echo $texto; ?>
                        </span>
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

