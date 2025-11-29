<?php
ob_start();
$title = $title ?? 'Detalhes do Projeto';
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Detalhes do Projeto</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/projects'); ?>">Projetos</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Detalhes</li>
                    </ol>
                </nav>
            </div>
            <div class="col-3">
                <div class="text-end">
                    <a href="<?php echo url('/projects/' . $project->id . '/edit'); ?>" class="btn btn-primary">
                        <i class="ti ti-pencil me-2"></i>Editar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-4"><?php echo e($project->titulo); ?></h5>
                
                <?php if ($project->descricao): ?>
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Descrição</h6>
                        <p><?php echo nl2br(e($project->descricao)); ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Progresso -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-muted mb-0">Progresso</h6>
                        <span class="fw-semibold"><?php echo $project->progresso; ?>%</span>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar" role="progressbar" style="width: <?php echo $project->progresso; ?>%" 
                             aria-valuenow="<?php echo $project->progresso; ?>" aria-valuemin="0" aria-valuemax="100">
                            <?php echo $project->progresso; ?>%
                        </div>
                    </div>
                </div>
                
                <?php if ($project->observacoes): ?>
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Observações</h6>
                        <p><?php echo nl2br(e($project->observacoes)); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="card-title mb-4">Informações do Projeto</h6>
                
                <div class="mb-3">
                    <small class="text-muted d-block">Status</small>
                    <span class="badge bg-<?php 
                        echo match($project->status) {
                            'planejamento' => 'info',
                            'em_andamento' => 'primary',
                            'pausado' => 'warning',
                            'concluido' => 'success',
                            'cancelado' => 'danger',
                            default => 'secondary'
                        };
                    ?> fs-2">
                        <?php echo ucfirst(str_replace('_', ' ', $project->status)); ?>
                    </span>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted d-block">Prioridade</small>
                    <span class="badge bg-<?php 
                        echo match($project->prioridade) {
                            'baixa' => 'secondary',
                            'media' => 'info',
                            'alta' => 'warning',
                            'urgente' => 'danger',
                            default => 'secondary'
                        };
                    ?> fs-2">
                        <?php echo ucfirst($project->prioridade); ?>
                    </span>
                </div>
                
                <?php if ($project->data_inicio): ?>
                    <div class="mb-3">
                        <small class="text-muted d-block">Data de Início</small>
                        <strong><?php echo date('d/m/Y', strtotime($project->data_inicio)); ?></strong>
                    </div>
                <?php endif; ?>
                
                <?php if ($project->data_termino_prevista): ?>
                    <div class="mb-3">
                        <small class="text-muted d-block">Término Previsto</small>
                        <strong><?php echo date('d/m/Y', strtotime($project->data_termino_prevista)); ?></strong>
                    </div>
                <?php endif; ?>
                
                <?php if ($project->data_termino_real): ?>
                    <div class="mb-3">
                        <small class="text-muted d-block">Término Real</small>
                        <strong><?php echo date('d/m/Y', strtotime($project->data_termino_real)); ?></strong>
                    </div>
                <?php endif; ?>
                
                <?php if ($project->orcamento): ?>
                    <div class="mb-3">
                        <small class="text-muted d-block">Orçamento</small>
                        <strong>R$ <?php echo number_format((float)$project->orcamento, 2, ',', '.'); ?></strong>
                    </div>
                <?php endif; ?>
                
                <?php if ($project->custo_real): ?>
                    <div class="mb-3">
                        <small class="text-muted d-block">Custo Real</small>
                        <strong>R$ <?php echo number_format((float)$project->custo_real, 2, ',', '.'); ?></strong>
                        <?php if ($project->orcamento): ?>
                            <?php 
                            $diferenca = $project->custo_real - $project->orcamento;
                            $percentual = ($diferenca / $project->orcamento) * 100;
                            ?>
                            <br>
                            <small class="text-<?php echo $diferenca > 0 ? 'danger' : 'success'; ?>">
                                <?php echo $diferenca > 0 ? '+' : ''; ?>R$ <?php echo number_format($diferenca, 2, ',', '.'); ?>
                                (<?php echo $diferenca > 0 ? '+' : ''; ?><?php echo number_format($percentual, 1, ',', '.'); ?>%)
                            </small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($client): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="card-title mb-3">Cliente</h6>
                    <p class="mb-1"><strong><?php echo e($client->nome_razao_social); ?></strong></p>
                    <?php if ($client->email): ?>
                        <p class="mb-1 small text-muted">
                            <i class="ti ti-mail me-1"></i><?php echo e($client->email); ?>
                        </p>
                    <?php endif; ?>
                    <a href="<?php echo url('/clients/' . $client->id); ?>" class="btn btn-sm btn-outline-primary mt-2">
                        <i class="ti ti-external-link me-1"></i>Ver Cliente
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($lead): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="card-title mb-3">Lead Associado</h6>
                    <p class="mb-1"><strong><?php echo e($lead->nome); ?></strong></p>
                    <?php if ($lead->email): ?>
                        <p class="mb-1 small text-muted">
                            <i class="ti ti-mail me-1"></i><?php echo e($lead->email); ?>
                        </p>
                    <?php endif; ?>
                    <a href="<?php echo url('/leads/' . $lead->id); ?>" class="btn btn-sm btn-outline-primary mt-2">
                        <i class="ti ti-external-link me-1"></i>Ver Lead
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($responsible): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="card-title mb-3">Responsável</h6>
                    <p class="mb-0">
                        <i class="ti ti-user-check me-2"></i>
                        <strong><?php echo e($responsible->name); ?></strong>
                    </p>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo url('/projects/' . $project->id . '/edit'); ?>" class="btn btn-primary">
                        <i class="ti ti-pencil me-2"></i>Editar Projeto
                    </a>
                    <form method="POST" action="<?php echo url('/projects/' . $project->id . '/delete'); ?>" onsubmit="return confirm('Tem certeza que deseja excluir este projeto? Esta ação não pode ser desfeita!');">
                        <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="ti ti-trash me-2"></i>Excluir Projeto
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

