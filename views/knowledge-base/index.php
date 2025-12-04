<?php
$title = 'Base de Conhecimento';

ob_start();
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Base de Conhecimento</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Base de Conhecimento</li>
                    </ol>
                </nav>
            </div>
            <div class="col-3">
                <div class="text-end">
                    <a href="<?php echo url('/knowledge-base/create'); ?>" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i>Novo Conhecimento
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?php echo url('/knowledge-base'); ?>" class="row g-3">
            <div class="col-md-3">
                <input type="text" 
                       class="form-control" 
                       name="search" 
                       placeholder="Buscar por título ou conteúdo..." 
                       value="<?php echo e($filters['search'] ?? ''); ?>">
            </div>
            <div class="col-md-2">
                <select name="categoria" class="form-select">
                    <option value="">Todas as Categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo e($cat['categoria']); ?>" <?php echo ($filters['categoria'] ?? '') === $cat['categoria'] ? 'selected' : ''; ?>>
                            <?php echo e($cat['categoria']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Todos os Status</option>
                    <option value="rascunho" <?php echo ($filters['status'] ?? '') === 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                    <option value="publicado" <?php echo ($filters['status'] ?? '') === 'publicado' ? 'selected' : ''; ?>>Publicado</option>
                    <option value="arquivado" <?php echo ($filters['status'] ?? '') === 'arquivado' ? 'selected' : ''; ?>>Arquivado</option>
                </select>
            </div>
            <div class="col-md-3">
                <?php 
                $id = 'filter_client_id';
                $name = 'client_id';
                $placeholder = 'Filtrar por cliente...';
                $selected = $filters['client_id'] ?? '';
                include base_path('views/components/tom-select-client.php'); 
                ?>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-search me-2"></i>Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Conhecimentos -->
<div class="row">
    <?php if (empty($knowledgeBase)): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="ti ti-book-off fs-1 text-muted mb-3"></i>
                    <h5 class="text-muted">Nenhum conhecimento encontrado</h5>
                    <p class="text-muted">Comece criando seu primeiro conhecimento!</p>
                    <a href="<?php echo url('/knowledge-base/create'); ?>" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i>Criar Conhecimento
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($knowledgeBase as $kb): ?>
            <?php 
            $client = $kb->client();
            $tags = $kb->tags();
            $author = $kb->author();
            ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100" style="transition: all 0.3s ease;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-2">
                                    <a href="<?php echo url('/knowledge-base/' . $kb->id); ?>" class="text-decoration-none">
                                        <?php echo e($kb->titulo); ?>
                                    </a>
                                </h5>
                                <?php if ($kb->categoria): ?>
                                    <span class="badge bg-info-subtle text-info mb-2"><?php echo e($kb->categoria); ?></span>
                                <?php endif; ?>
                                <span class="badge bg-<?php 
                                    echo match($kb->status) {
                                        'publicado' => 'success',
                                        'rascunho' => 'warning',
                                        'arquivado' => 'secondary',
                                        default => 'secondary'
                                    };
                                ?>-subtle text-<?php 
                                    echo match($kb->status) {
                                        'publicado' => 'success',
                                        'rascunho' => 'warning',
                                        'arquivado' => 'secondary',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst($kb->status); ?>
                                </span>
                            </div>
                            <div class="dropdown">
                                <a class="dropdown-toggle text-muted" href="javascript:void(0)" role="button" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="<?php echo url('/knowledge-base/' . $kb->id); ?>">
                                            <i class="ti ti-eye me-2"></i>Ver
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo url('/knowledge-base/' . $kb->id . '/edit'); ?>">
                                            <i class="ti ti-edit me-2"></i>Editar
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="excluirConhecimento(<?php echo $kb->id; ?>, '<?php echo e($kb->titulo); ?>')">
                                            <i class="ti ti-trash me-2"></i>Excluir
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <?php if ($kb->resumo): ?>
                            <p class="card-text text-muted small mb-3">
                                <?php echo e(substr($kb->resumo, 0, 120)); ?><?php echo strlen($kb->resumo) > 120 ? '...' : ''; ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($tags)): ?>
                            <div class="mb-3">
                                <?php foreach ($tags as $tag): ?>
                                    <span class="badge" style="background-color: <?php echo e($tag['color'] ?? '#0dcaf0'); ?>; color: white; font-size: 0.7rem; margin-right: 4px;">
                                        <?php echo e($tag['name']); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between align-items-center text-muted small">
                            <div>
                                <?php if ($client): ?>
                                    <i class="ti ti-user me-1"></i>
                                    <span><?php echo e($client->nome_razao_social); ?></span>
                                <?php else: ?>
                                    <i class="ti ti-building me-1"></i>
                                    <span>Geral</span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <i class="ti ti-eye me-1"></i>
                                <span><?php echo $kb->visualizacoes ?? 0; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="ti ti-calendar me-1"></i>
                                <?php echo date('d/m/Y', strtotime($kb->created_at)); ?>
                            </small>
                            <a href="<?php echo url('/knowledge-base/' . $kb->id); ?>" class="btn btn-sm btn-primary">
                                Ler mais <i class="ti ti-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
// Inclui scripts do Tom Select
include base_path('views/components/tom-select-scripts.php');
?>

<script>
function excluirConhecimento(id, titulo) {
    if (!confirm(`Tem certeza que deseja excluir o conhecimento "${titulo}"?\n\nEsta ação não pode ser desfeita.`)) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?php echo url('/knowledge-base'); ?>/' + id + '/delete';
    
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_csrf_token';
    csrfInput.value = document.querySelector('meta[name="csrf-token"]')?.content || '';
    form.appendChild(csrfInput);
    
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

