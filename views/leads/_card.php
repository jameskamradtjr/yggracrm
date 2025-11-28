<div class="card mb-3 shadow-sm" data-lead-id="<?php echo $lead->id; ?>" style="cursor: move;">
    <div class="card-body p-3">
        <h6 class="card-title mb-2 fw-semibold"><?php echo e($lead->nome); ?></h6>
        
        <div class="mb-2">
            <small class="text-muted d-block">
                <i class="ti ti-currency-dollar me-1"></i>
                Faturamento: <?php echo e($lead->faturamento_categoria ?? 'N/A'); ?>
            </small>
            <small class="text-muted d-block">
                <i class="ti ti-trending-up me-1"></i>
                Investimento: <?php echo e($lead->invest_categoria ?? 'N/A'); ?>
            </small>
        </div>

        <?php 
        $tags = $lead->getTagsAi();
        if (!empty($tags)):
        ?>
            <div class="mb-2">
                <?php foreach (array_slice($tags, 0, 3) as $tag): ?>
                    <span class="badge bg-primary-subtle text-primary me-1 mb-1" style="font-size: 0.7rem;">
                        <?php echo e($tag); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mt-2">
            <small class="text-muted">
                <i class="ti ti-star me-1"></i>
                Score: <?php echo $lead->score_potencial ?? 0; ?>
            </small>
            <a href="<?php echo url('/leads/' . $lead->id); ?>" class="btn btn-sm btn-outline-primary">
                <i class="ti ti-eye"></i>
            </a>
        </div>
    </div>
</div>

