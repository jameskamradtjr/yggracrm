<div data-draggable="true" class="card mb-3 <?php echo $lead->client_id ? 'border-success' : ''; ?>" data-lead-id="<?php echo $lead->id; ?>" style="cursor: pointer;">
    <div class="card-body">
        <div class="task-header d-flex justify-content-between align-items-start mb-2">
            <div class="flex-grow-1">
                <h6 class="mb-1 fw-semibold" data-item-title="<?php echo e($lead->nome); ?>">
                    <?php echo e($lead->nome); ?>
                    <?php if ($lead->client_id): ?>
                        <?php 
                        $client = $lead->client();
                        ?>
                        <span class="badge bg-success ms-2" title="Este lead foi convertido em cliente">
                            <i class="ti ti-user-check fs-5"></i> 
                            Cliente
                            <?php if ($client): ?>
                                <a href="<?php echo url('/clients/' . $client->id); ?>" class="text-white ms-1" onclick="event.stopPropagation();" title="Ver cliente">
                                    <i class="ti ti-external-link fs-6"></i>
                                </a>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </h6>
                <?php if ($lead->email): ?>
                    <small class="text-muted d-block">
                        <i class="ti ti-mail fs-5"></i> <?php echo e($lead->email); ?>
                    </small>
                <?php endif; ?>
            </div>
            <div class="dropdown">
                <a class="dropdown-toggle" href="javascript:void(0)" role="button" data-bs-toggle="dropdown">
                    <i class="ti ti-dots-vertical text-dark"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="javascript:void(0);" onclick="abrirEdicaoLead(<?php echo $lead->id; ?>)">
                        <i class="ti ti-pencil fs-5 me-2"></i>Editar
                    </a>
                    <a class="dropdown-item" href="<?php echo url('/leads/' . $lead->id); ?>">
                        <i class="ti ti-eye fs-5 me-2"></i>Ver Detalhes
                    </a>
                </div>
            </div>
        </div>
        
        <?php if ($lead->resumo): ?>
            <div class="task-content mb-2">
                <p class="mb-0 text-muted small" style="font-size: 0.85rem;">
                    <?php echo e(substr($lead->resumo, 0, 100)); ?><?php echo strlen($lead->resumo) > 100 ? '...' : ''; ?>
                </p>
            </div>
        <?php endif; ?>
        
        <div class="task-body">
            <div class="task-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="tb-section-1">
                    <span class="hstack gap-2 fs-2">
                        <i class="ti ti-star fs-5 text-warning"></i>
                        <span class="fw-semibold"><?php echo $lead->score_potencial ?? 0; ?></span>
                    </span>
                </div>
                <div class="tb-section-2">
                    <?php if ($lead->faturamento_categoria): ?>
                        <span class="badge text-bg-primary fs-1"><?php echo e($lead->faturamento_categoria); ?></span>
                    <?php endif; ?>
                    <?php if ($lead->valor_oportunidade && $lead->valor_oportunidade > 0): ?>
                        <span class="badge text-bg-success fs-1 ms-1">
                            <i class="ti ti-currency-dollar me-1"></i>
                            R$ <?php echo number_format((float)$lead->valor_oportunidade, 2, ',', '.'); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($lead->responsible_user_id): ?>
                <?php 
                $responsible = $lead->responsible();
                if ($responsible):
                ?>
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="ti ti-user me-1"></i>
                            <?php echo e($responsible->name); ?>
                        </small>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($lead->origem): ?>
                <div class="mt-1">
                    <small class="text-muted">
                        <i class="ti ti-source-code me-1"></i>
                        <?php echo e($lead->origem); ?>
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

