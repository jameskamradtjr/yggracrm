<?php
$title = 'Mural';

ob_start();
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Mural</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Mural</li>
                    </ol>
                </nav>
            </div>
            <div class="col-3">
                <div class="text-end">
                    <a href="<?php echo url('/mural/create'); ?>" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i>Novo Item
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Cards do Mural -->
<div class="row" id="mural-cards">
    <?php if (empty($itens)): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="ti ti-news fs-10 text-muted mb-3"></i>
                    <h5 class="text-muted">Nenhum item no mural ainda</h5>
                    <p class="text-muted">Crie seu primeiro item do mural para começar!</p>
                    <a href="<?php echo url('/mural/create'); ?>" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i>Criar Primeiro Item
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($itens as $item): ?>
            <div class="col-lg-4 col-md-6 mb-4" data-item-id="<?php echo $item['id']; ?>">
                <div class="card h-100">
                    <?php if ($item['imagem_url']): ?>
                        <img class="card-img-top img-responsive" 
                             src="<?php echo e($item['imagem_url']); ?>" 
                             alt="<?php echo e($item['titulo']); ?>"
                             style="max-height: 250px; object-fit: cover;" />
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h4 class="card-title mb-0"><?php echo e($item['titulo']); ?></h4>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link text-muted p-0" type="button" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="<?php echo url('/mural/' . $item['id'] . '/edit'); ?>">
                                            <i class="ti ti-edit me-2"></i>Editar
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="excluirItem(<?php echo $item['id']; ?>, '<?php echo e($item['titulo']); ?>')">
                                            <i class="ti ti-trash me-2"></i>Excluir
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <?php if ($item['descricao']): ?>
                            <p class="card-text flex-grow-1"><?php echo nl2br(e($item['descricao'])); ?></p>
                        <?php endif; ?>
                        <?php if ($item['link_url'] && $item['link_texto']): ?>
                            <a href="<?php echo e($item['link_url']); ?>" 
                               class="btn btn-primary mt-auto" 
                               target="_blank" 
                               rel="noopener noreferrer">
                                <?php echo e($item['link_texto']); ?>
                            </a>
                        <?php endif; ?>
                        <div class="mt-2">
                            <small class="text-muted">
                                <?php if ($item['data_inicio'] || $item['data_fim']): ?>
                                    <?php if ($item['data_inicio']): ?>
                                        De: <?php echo date('d/m/Y', strtotime($item['data_inicio'])); ?>
                                    <?php endif; ?>
                                    <?php if ($item['data_fim']): ?>
                                        <?php echo $item['data_inicio'] ? ' até ' : 'Até '; ?>
                                        <?php echo date('d/m/Y', strtotime($item['data_fim'])); ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </small>
                            <?php if (!$item['is_ativo']): ?>
                                <span class="badge bg-warning ms-2">Inativo</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function excluirItem(id, titulo) {
    if (!confirm(`Tem certeza que deseja excluir o item "${titulo}"?\n\nEsta ação não pode ser desfeita.`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('_csrf_token', '<?php echo csrf_token(); ?>');
    
    fetch('<?php echo url('/mural'); ?>/' + id + '/delete', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove o card do DOM
            const cardElement = document.querySelector(`[data-item-id="${id}"]`);
            if (cardElement) {
                cardElement.remove();
            }
            
            // Se não houver mais cards, mostra mensagem
            const cardsContainer = document.getElementById('mural-cards');
            if (cardsContainer && cardsContainer.querySelectorAll('.col-lg-4').length === 0) {
                cardsContainer.innerHTML = `
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="ti ti-news fs-10 text-muted mb-3"></i>
                                <h5 class="text-muted">Nenhum item no mural ainda</h5>
                                <p class="text-muted">Crie seu primeiro item do mural para começar!</p>
                                <a href="<?php echo url('/mural/create'); ?>" class="btn btn-primary">
                                    <i class="ti ti-plus me-2"></i>Criar Primeiro Item
                                </a>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            alert('Item excluído com sucesso!');
        } else {
            alert('Erro ao excluir item: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao excluir item.');
    });
}
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

