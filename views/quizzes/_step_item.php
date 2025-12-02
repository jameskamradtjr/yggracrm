<div class="card mb-3 step-item" data-step-id="<?php echo $step->id; ?>">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center mb-2">
                    <span class="badge bg-primary me-2">Etapa <?php echo $step->order + 1; ?></span>
                    <h6 class="mb-0"><?php echo e($step->title); ?></h6>
                </div>
                <?php if ($step->description): ?>
                    <p class="text-muted mb-2"><?php echo e($step->description); ?></p>
                <?php endif; ?>
                <div class="d-flex gap-3">
                    <small class="text-muted">
                        <i class="ti ti-tag me-1"></i>
                        Tipo: <?php echo e($step->type); ?>
                    </small>
                    <small class="text-muted">
                        <i class="ti ti-star me-1"></i>
                        Pontos: <?php echo $step->points; ?>
                    </small>
                    <?php if ($step->field_name): ?>
                        <small class="text-muted">
                            <i class="ti ti-code me-1"></i>
                            Campo: <?php echo e($step->field_name); ?>
                        </small>
                    <?php endif; ?>
                    <?php if ($step->required): ?>
                        <small class="text-danger">
                            <i class="ti ti-asterisk me-1"></i>
                            Obrigatório
                        </small>
                    <?php endif; ?>
                </div>
                <?php 
                $options = $step->options();
                if (!empty($options)): 
                ?>
                    <div class="mt-2">
                        <small class="text-muted">Opções:</small>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            <?php foreach ($options as $option): ?>
                                <span class="badge bg-secondary">
                                    <?php echo e($option->label); ?>
                                    <?php if ($option->points > 0): ?>
                                        (+<?php echo $option->points; ?> pts)
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="btn-group">
                <button class="btn btn-sm btn-info" onclick="editStep(<?php echo $step->id; ?>)" title="Editar">
                    <i class="ti ti-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteStep(<?php echo $step->id; ?>)" title="Excluir">
                    <i class="ti ti-trash"></i>
                </button>
            </div>
        </div>
    </div>
</div>

