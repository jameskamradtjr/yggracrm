<?php
ob_start();
$title = $title ?? 'Gestão de Projetos';
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Gestão de Projetos</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Projetos</li>
                    </ol>
                </nav>
            </div>
            <div class="col-3">
                <div class="text-end">
                    <a href="<?php echo url('/projects/create'); ?>" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i>Novo Projeto
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?php echo url('/projects'); ?>" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="all" <?php echo ($filters['status'] ?? 'all') === 'all' ? 'selected' : ''; ?>>Todos</option>
                    <option value="planejamento" <?php echo ($filters['status'] ?? '') === 'planejamento' ? 'selected' : ''; ?>>Planejamento</option>
                    <option value="em_andamento" <?php echo ($filters['status'] ?? '') === 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                    <option value="pausado" <?php echo ($filters['status'] ?? '') === 'pausado' ? 'selected' : ''; ?>>Pausado</option>
                    <option value="concluido" <?php echo ($filters['status'] ?? '') === 'concluido' ? 'selected' : ''; ?>>Concluído</option>
                    <option value="cancelado" <?php echo ($filters['status'] ?? '') === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Prioridade</label>
                <select name="prioridade" class="form-select">
                    <option value="all" <?php echo ($filters['prioridade'] ?? 'all') === 'all' ? 'selected' : ''; ?>>Todas</option>
                    <option value="baixa" <?php echo ($filters['prioridade'] ?? '') === 'baixa' ? 'selected' : ''; ?>>Baixa</option>
                    <option value="media" <?php echo ($filters['prioridade'] ?? '') === 'media' ? 'selected' : ''; ?>>Média</option>
                    <option value="alta" <?php echo ($filters['prioridade'] ?? '') === 'alta' ? 'selected' : ''; ?>>Alta</option>
                    <option value="urgente" <?php echo ($filters['prioridade'] ?? '') === 'urgente' ? 'selected' : ''; ?>>Urgente</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Buscar</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Título ou descrição..." value="<?php echo e($filters['search'] ?? ''); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-search"></i>
                    </button>
                    <?php if ($filters['status'] !== 'all' || $filters['prioridade'] !== 'all' || !empty($filters['search'])): ?>
                        <a href="<?php echo url('/projects'); ?>" class="btn btn-secondary">
                            <i class="ti ti-x"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Projetos -->
<div class="row">
    <?php if (empty($projects)): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="ti ti-briefcase fs-1 text-muted d-block mb-3"></i>
                    <h5 class="text-muted">Nenhum projeto encontrado</h5>
                    <p class="text-muted">Comece criando seu primeiro projeto.</p>
                    <a href="<?php echo url('/projects/create'); ?>" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i>Novo Projeto
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($projects as $project): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-2">
                                    <a href="<?php echo url('/projects/' . $project->id); ?>" class="text-dark text-decoration-none">
                                        <?php echo e($project->titulo); ?>
                                    </a>
                                </h5>
                                <div class="d-flex gap-2 mb-2">
                                    <span class="badge bg-<?php 
                                        echo match($project->status) {
                                            'planejamento' => 'info',
                                            'em_andamento' => 'primary',
                                            'pausado' => 'warning',
                                            'concluido' => 'success',
                                            'cancelado' => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $project->status)); ?>
                                    </span>
                                    <span class="badge bg-<?php 
                                        echo match($project->prioridade) {
                                            'baixa' => 'secondary',
                                            'media' => 'info',
                                            'alta' => 'warning',
                                            'urgente' => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?php echo ucfirst($project->prioridade); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="dropdown">
                                <a class="dropdown-toggle" href="javascript:void(0)" role="button" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical text-dark"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="<?php echo url('/projects/' . $project->id); ?>">
                                        <i class="ti ti-eye fs-5 me-2"></i>Ver Detalhes
                                    </a>
                                    <a class="dropdown-item" href="<?php echo url('/projects/' . $project->id . '/edit'); ?>">
                                        <i class="ti ti-pencil fs-5 me-2"></i>Editar
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteProject(<?php echo $project->id; ?>)">
                                        <i class="ti ti-trash fs-5 me-2"></i>Excluir
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($project->descricao): ?>
                            <p class="text-muted small mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo e($project->descricao); ?>
                            </p>
                        <?php endif; ?>
                        
                        <!-- Progresso -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">Progresso</small>
                                <small class="fw-semibold"><?php echo $project->progresso; ?>%</small>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $project->progresso; ?>%" 
                                     aria-valuenow="<?php echo $project->progresso; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        
                        <!-- Informações -->
                        <div class="row g-2 mb-3">
                            <?php if ($project->client_id): ?>
                                <?php $client = $project->client(); ?>
                                <div class="col-12">
                                    <small class="text-muted d-flex align-items-center">
                                        <i class="ti ti-user me-2"></i>
                                        Cliente: 
                                        <?php if ($client): ?>
                                            <a href="<?php echo url('/clients/' . $client->id); ?>" class="text-primary ms-1">
                                                <?php echo e($client->nome_razao_social); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="ms-1">N/A</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($project->responsible_user_id): ?>
                                <?php $responsible = $project->responsible(); ?>
                                <div class="col-12">
                                    <small class="text-muted d-flex align-items-center">
                                        <i class="ti ti-user-check me-2"></i>
                                        Responsável: 
                                        <?php if ($responsible): ?>
                                            <span class="ms-1"><?php echo e($responsible->name); ?></span>
                                        <?php else: ?>
                                            <span class="ms-1">N/A</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($project->data_inicio): ?>
                                <div class="col-12">
                                    <small class="text-muted d-flex align-items-center">
                                        <i class="ti ti-calendar me-2"></i>
                                        Início: <?php echo date('d/m/Y', strtotime($project->data_inicio)); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($project->data_termino_prevista): ?>
                                <div class="col-12">
                                    <small class="text-muted d-flex align-items-center">
                                        <i class="ti ti-calendar-event me-2"></i>
                                        Término Previsto: <?php echo date('d/m/Y', strtotime($project->data_termino_prevista)); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($project->orcamento): ?>
                                <div class="col-12">
                                    <small class="text-muted d-flex align-items-center">
                                        <i class="ti ti-currency-dollar me-2"></i>
                                        Orçamento: R$ <?php echo number_format((float)$project->orcamento, 2, ',', '.'); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <a href="<?php echo url('/projects/' . $project->id . '/kanban'); ?>" class="btn btn-sm btn-info flex-grow-1">
                                <i class="ti ti-layout-kanban me-1"></i>Kanban
                            </a>
                            <a href="<?php echo url('/projects/' . $project->id); ?>" class="btn btn-sm btn-primary">
                                <i class="ti ti-eye me-1"></i>Detalhes
                            </a>
                            <a href="<?php echo url('/projects/' . $project->id . '/edit'); ?>" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-pencil"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="deleteProjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir este projeto?</p>
                <p class="text-danger mb-0"><strong>Esta ação não pode ser desfeita!</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteProjectForm" method="POST" style="display: inline;">
                    <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteProject(projectId) {
    const form = document.getElementById('deleteProjectForm');
    form.action = '<?php echo url('/projects'); ?>/' + projectId + '/delete';
    const modal = new bootstrap.Modal(document.getElementById('deleteProjectModal'));
    modal.show();
}
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

