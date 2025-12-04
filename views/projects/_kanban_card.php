<?php
$card = $cardData['card'];
$users = $cardData['users'];
$project = $cardData['project'];

$checklistProgress = $card->getChecklistProgress();
$tags = $card->tags();
$responsible = $card->responsible();
?>

<div data-draggable="true" class="card mb-3 kanban-card" data-card-id="<?php echo $card->id; ?>" style="cursor: pointer; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.2s ease;" onclick="abrirEdicaoCard(<?php echo $card->id; ?>)" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.1)'; this.style.transform='translateY(0)'">
    <div class="card-body p-3">
        <!-- Header com tags e menu -->
        <div class="d-flex justify-content-between align-items-start mb-2">
            <!-- Tags no topo -->
            <?php if (!empty($tags)): ?>
                <div class="d-flex flex-wrap gap-1" style="flex: 1;">
                    <?php foreach ($tags as $tag): ?>
                        <span class="badge" style="background-color: <?php echo e($tag->cor); ?>; color: white; font-size: 0.7rem; padding: 4px 8px; border-radius: 6px; font-weight: 500;">
                            <?php echo e($tag->nome); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="flex: 1;"></div>
            <?php endif; ?>
            
            <!-- Menu de opções -->
            <div class="dropdown" onclick="event.stopPropagation();">
                <a class="dropdown-toggle text-muted" href="javascript:void(0)" role="button" data-bs-toggle="dropdown" style="text-decoration: none; padding: 4px;">
                    <i class="ti ti-dots-vertical" style="font-size: 18px;"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="javascript:void(0);" onclick="abrirEdicaoCard(<?php echo $card->id; ?>)">
                        <i class="ti ti-pencil me-2"></i>Editar
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="event.stopPropagation(); excluirCard(<?php echo $card->id; ?>)">
                        <i class="ti ti-trash me-2"></i>Excluir
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Título -->
        <h6 class="mb-2 fw-semibold" style="font-size: 0.95rem; line-height: 1.4; color: #1f2937;" data-item-title="<?php echo e($card->titulo); ?>">
            <?php echo e($card->titulo); ?>
        </h6>
        
        <!-- Descrição -->
        <?php if ($card->descricao): ?>
            <p class="mb-3 text-muted" style="font-size: 0.8rem; line-height: 1.5; color: #6b7280;">
                <?php echo e(substr($card->descricao, 0, 120)); ?><?php echo strlen($card->descricao) > 120 ? '...' : ''; ?>
            </p>
        <?php endif; ?>
        
        <!-- Barra de progresso do checklist -->
        <?php if ($checklistProgress['total'] > 0): ?>
            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <small class="text-muted" style="font-size: 0.75rem;">
                        <i class="ti ti-checklist me-1"></i>
                        <?php echo $checklistProgress['concluidos']; ?>/<?php echo $checklistProgress['total']; ?>
                    </small>
                    <small class="text-muted fw-semibold" style="font-size: 0.75rem;">
                        <?php echo $checklistProgress['percentual']; ?>%
                    </small>
                </div>
                <div class="progress" style="height: 6px; border-radius: 10px; background-color: #e5e7eb;">
                    <div class="progress-bar" role="progressbar" 
                         style="width: <?php echo $checklistProgress['percentual']; ?>%; background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%); border-radius: 10px;"
                         aria-valuenow="<?php echo $checklistProgress['percentual']; ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Footer: Responsável, Prazo, Prioridade, Timer -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pt-2" style="border-top: 1px solid #e5e7eb;">
            <!-- Esquerda: Responsável e Prazo -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Responsável com avatar -->
                <?php if ($responsible): ?>
                    <div class="d-flex align-items-center" style="position: relative;">
                        <?php 
                        $avatar = $responsible->avatar ?? null;
                        $initials = strtoupper(substr($responsible->name ?? 'U', 0, 2));
                        
                        if (!empty($avatar)) {
                            $avatarUrl = (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) 
                                ? $avatar 
                                : asset($avatar);
                            ?>
                            <img src="<?php echo htmlspecialchars($avatarUrl); ?>" 
                                 alt="<?php echo htmlspecialchars($responsible->name); ?>" 
                                 class="rounded-circle" 
                                 width="28" 
                                 height="28" 
                                 style="object-fit: cover; border: 2px solid #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"
                                 onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div style="display: none; width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: 2px solid #fff; align-items: center; justify-content: center; color: white; font-size: 10px; font-weight: 600; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                <?php echo htmlspecialchars($initials); ?>
                            </div>
                        <?php } else {
                            $colors = ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#3b82f6'];
                            $colorIndex = crc32($responsible->name ?? '') % count($colors);
                            $bgColor = $colors[$colorIndex];
                            ?>
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center" 
                                 style="width: 28px; height: 28px; background-color: <?php echo $bgColor; ?>; border: 2px solid #fff; color: white; font-size: 11px; font-weight: 600; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                <?php echo htmlspecialchars($initials); ?>
                            </div>
                        <?php } ?>
                    </div>
                <?php endif; ?>
                
                <!-- Prazo -->
                <?php if ($card->data_prazo): ?>
                    <?php 
                    $prazo = new \DateTime($card->data_prazo);
                    $hoje = new \DateTime();
                    $diferenca = $hoje->diff($prazo);
                    $dias = (int)$diferenca->format('%r%a');
                    
                    if ($dias < 0) {
                        $cor = '#ef4444';
                        $texto = abs($dias) . 'd atrasado';
                        $icon = 'ti-alert-circle';
                    } elseif ($dias == 0) {
                        $cor = '#f59e0b';
                        $texto = 'Hoje';
                        $icon = 'ti-calendar-event';
                    } elseif ($dias <= 3) {
                        $cor = '#f59e0b';
                        $texto = $dias . 'd';
                        $icon = 'ti-calendar';
                    } else {
                        $cor = '#6b7280';
                        $texto = date('d/m', strtotime($card->data_prazo));
                        $icon = 'ti-calendar';
                    }
                    ?>
                    <small class="d-flex align-items-center" style="color: <?php echo $cor; ?>; font-size: 0.75rem; font-weight: 500;">
                        <i class="ti <?php echo $icon; ?> me-1" style="font-size: 0.85rem;"></i>
                        <?php echo $texto; ?>
                    </small>
                <?php endif; ?>
            </div>
            
            <!-- Direita: Prioridade e Timer -->
            <div class="d-flex align-items-center gap-2">
                <!-- Prioridade -->
                <?php 
                $prioridadeConfig = match($card->prioridade) {
                    'baixa' => ['color' => '#6b7280', 'bg' => '#f3f4f6', 'icon' => 'ti-arrow-down'],
                    'media' => ['color' => '#3b82f6', 'bg' => '#dbeafe', 'icon' => 'ti-minus'],
                    'alta' => ['color' => '#f59e0b', 'bg' => '#fef3c7', 'icon' => 'ti-arrow-up'],
                    'urgente' => ['color' => '#ef4444', 'bg' => '#fee2e2', 'icon' => 'ti-alert-triangle'],
                    default => ['color' => '#6b7280', 'bg' => '#f3f4f6', 'icon' => 'ti-minus']
                };
                ?>
                <span class="badge" style="background-color: <?php echo $prioridadeConfig['bg']; ?>; color: <?php echo $prioridadeConfig['color']; ?>; font-size: 0.7rem; padding: 4px 8px; border-radius: 6px; font-weight: 500;">
                    <i class="ti <?php echo $prioridadeConfig['icon']; ?> me-1" style="font-size: 0.7rem;"></i>
                    <?php echo ucfirst($card->prioridade); ?>
                </span>
                
                <!-- Timer -->
                <div onclick="event.stopPropagation();">
                    <button type="button" 
                            class="btn btn-sm btn-success timer-start-btn" 
                            data-card-id="<?php echo $card->id; ?>"
                            onclick="iniciarTimer(<?php echo $card->id; ?>)"
                            style="display: none; padding: 2px 8px; font-size: 0.7rem;">
                        <i class="ti ti-player-play me-1"></i>
                    </button>
                    <button type="button" 
                            class="btn btn-sm btn-danger timer-stop-btn" 
                            data-card-id="<?php echo $card->id; ?>"
                            onclick="pararTimer(<?php echo $card->id; ?>)"
                            style="display: none; padding: 2px 8px; font-size: 0.7rem;">
                        <i class="ti ti-player-stop me-1"></i>
                    </button>
                    <small class="text-muted timer-display d-flex align-items-center" data-card-id="<?php echo $card->id; ?>" style="font-size: 0.75rem;">
                        <i class="ti ti-clock me-1" style="font-size: 0.85rem;"></i>
                        <span class="timer-total">Carregando...</span>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

