<?php
$title = $knowledge->titulo;

ob_start();
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8"><?php echo e($knowledge->titulo); ?></h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/knowledge-base'); ?>">Base de Conhecimento</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page"><?php echo e($knowledge->titulo); ?></li>
                    </ol>
                </nav>
            </div>
            <div class="col-3">
                <div class="text-end">
                    <a href="<?php echo url('/knowledge-base/' . $knowledge->id . '/edit'); ?>" class="btn btn-primary">
                        <i class="ti ti-edit me-2"></i>Editar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <?php if ($knowledge->resumo): ?>
                    <div class="alert alert-info mb-4">
                        <strong>Resumo:</strong> <?php echo e($knowledge->resumo); ?>
                    </div>
                <?php endif; ?>
                
                <div class="knowledge-content">
                    <?php echo $knowledge->conteudo; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0">Informações</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="fw-semibold" style="width: 40%;">Status:</td>
                        <td>
                            <span class="badge bg-<?php 
                                echo match($knowledge->status) {
                                    'publicado' => 'success',
                                    'rascunho' => 'warning',
                                    'arquivado' => 'secondary',
                                    default => 'secondary'
                                };
                            ?>">
                                <?php echo ucfirst($knowledge->status); ?>
                            </span>
                        </td>
                    </tr>
                    <?php if ($knowledge->categoria): ?>
                    <tr>
                        <td class="fw-semibold">Categoria:</td>
                        <td>
                            <span class="badge bg-info-subtle text-info"><?php echo e($knowledge->categoria); ?></span>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($client): ?>
                    <tr>
                        <td class="fw-semibold">Cliente:</td>
                        <td>
                            <a href="<?php echo url('/clients/' . $client->id); ?>" class="text-decoration-none">
                                <?php echo e($client->nome_razao_social); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="fw-semibold">Visualizações:</td>
                        <td>
                            <i class="ti ti-eye me-1"></i>
                            <?php echo $knowledge->visualizacoes ?? 0; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Criado em:</td>
                        <td><?php echo date('d/m/Y H:i', strtotime($knowledge->created_at)); ?></td>
                    </tr>
                    <?php if ($author): ?>
                    <tr>
                        <td class="fw-semibold">Autor:</td>
                        <td><?php echo e($author->name); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <?php if (!empty($tags)): ?>
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0">Tags</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($tags as $tag): ?>
                        <span class="badge" style="background-color: <?php echo e($tag['color'] ?? '#0dcaf0'); ?>; color: white;">
                            <?php echo e($tag['name']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo url('/knowledge-base/' . $knowledge->id . '/edit'); ?>" class="btn btn-primary">
                        <i class="ti ti-edit me-2"></i>Editar
                    </a>
                    <button class="btn btn-danger" onclick="excluirConhecimento(<?php echo $knowledge->id; ?>, '<?php echo e($knowledge->titulo); ?>')">
                        <i class="ti ti-trash me-2"></i>Excluir
                    </button>
                    <a href="<?php echo url('/knowledge-base'); ?>" class="btn btn-light">
                        <i class="ti ti-arrow-left me-2"></i>Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.knowledge-content {
    line-height: 1.8;
    color: #333;
}

.knowledge-content h1,
.knowledge-content h2,
.knowledge-content h3,
.knowledge-content h4,
.knowledge-content h5,
.knowledge-content h6 {
    margin-top: 1.5em;
    margin-bottom: 0.5em;
    font-weight: 600;
}

.knowledge-content p {
    margin-bottom: 1em;
}

.knowledge-content ul,
.knowledge-content ol {
    margin-bottom: 1em;
    padding-left: 2em;
}

.knowledge-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 1em 0;
}

.knowledge-content blockquote {
    border-left: 4px solid #007bff;
    padding-left: 1em;
    margin: 1em 0;
    color: #666;
    font-style: italic;
}

.knowledge-content code {
    background-color: #f4f4f4;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 0.9em;
}

.knowledge-content pre {
    background-color: #f4f4f4;
    padding: 1em;
    border-radius: 8px;
    overflow-x: auto;
    margin: 1em 0;
}

.knowledge-content pre code {
    background-color: transparent;
    padding: 0;
}
</style>

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

