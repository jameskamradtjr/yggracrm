<?php
// Variáveis já estão disponíveis do controller
$checklistProgress = $card->getChecklistProgress();
?>
<div data-card-nome="<?php echo e($card->titulo); ?>">
    <form id="editCardForm" onsubmit="window.salvarCardCompleto(event, <?php echo $card->id; ?>)">
        <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
        
        <div class="row">
            <div class="col-md-12 mb-3">
                <label class="form-label">Título *</label>
                <input type="text" name="titulo" class="form-control" value="<?php echo e($card->titulo); ?>" required>
            </div>
            
            <div class="col-md-12 mb-3">
                <label class="form-label">Descrição</label>
                <textarea name="descricao" class="form-control" rows="3"><?php echo e($card->descricao ?? ''); ?></textarea>
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label">Prioridade</label>
                <select name="prioridade" class="form-select">
                    <option value="baixa" <?php echo $card->prioridade === 'baixa' ? 'selected' : ''; ?>>Baixa</option>
                    <option value="media" <?php echo $card->prioridade === 'media' ? 'selected' : ''; ?>>Média</option>
                    <option value="alta" <?php echo $card->prioridade === 'alta' ? 'selected' : ''; ?>>Alta</option>
                    <option value="urgente" <?php echo $card->prioridade === 'urgente' ? 'selected' : ''; ?>>Urgente</option>
                </select>
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label">Responsável</label>
                <select name="responsible_user_id" class="form-select">
                    <option value="">Sem responsável</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user->id; ?>" <?php echo $card->responsible_user_id == $user->id ? 'selected' : ''; ?>>
                            <?php echo e($user->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label">Prazo</label>
                <input type="date" name="data_prazo" class="form-control" value="<?php echo $card->data_prazo ? date('Y-m-d', strtotime($card->data_prazo)) : ''; ?>">
            </div>
        </div>
        
        <hr class="my-4">
        
        <!-- Checklist -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Checklist</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="adicionarItemChecklist(<?php echo $card->id; ?>)">
                    <i class="ti ti-plus me-1"></i>Adicionar Item
                </button>
            </div>
            
            <?php if ($checklistProgress['total'] > 0): ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted">Progresso</small>
                        <small class="fw-semibold"><?php echo $checklistProgress['percentual']; ?>%</small>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar" role="progressbar" style="width: <?php echo $checklistProgress['percentual']; ?>%"></div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div id="checklistItems">
                <?php if (empty($checklists)): ?>
                    <p class="text-muted small mb-0">Nenhum item no checklist</p>
                <?php else: ?>
                    <?php foreach ($checklists as $item): ?>
                        <div class="form-check mb-2" data-checklist-id="<?php echo $item->id; ?>">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="checklist_<?php echo $item->id; ?>" 
                                   <?php echo $item->concluido ? 'checked' : ''; ?>
                                   onchange="atualizarChecklistItem(<?php echo $item->id; ?>, this.checked)">
                            <label class="form-check-label" for="checklist_<?php echo $item->id; ?>">
                                <?php echo e($item->item); ?>
                            </label>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2" onclick="removerItemChecklist(<?php echo $item->id; ?>)">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <hr class="my-4">
        
        <!-- Tags -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Tags</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="adicionarTag(<?php echo $card->id; ?>)">
                    <i class="ti ti-plus me-1"></i>Adicionar Tag
                </button>
            </div>
            
            <div id="tagsContainer" class="d-flex flex-wrap gap-2">
                <?php if (empty($tags)): ?>
                    <p class="text-muted small mb-0">Nenhuma tag</p>
                <?php else: ?>
                    <?php foreach ($tags as $tag): ?>
                        <span class="badge fs-2 d-flex align-items-center gap-1" style="background-color: <?php echo e($tag->cor); ?>; color: white;">
                            <?php echo e($tag->nome); ?>
                            <button type="button" class="btn btn-sm p-0 text-white" onclick="removerTag(<?php echo $tag->id; ?>)">
                                <i class="ti ti-x"></i>
                            </button>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="d-flex gap-2 justify-content-end">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-check me-2"></i>Salvar
            </button>
        </div>
    </form>
</div>

<script>
// Torna a função globalmente acessível
window.salvarCardCompleto = function(event, cardId) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Salvando...';

    fetch('<?php echo url('/projects/kanban'); ?>/' + cardId + '/update', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('editCardModal')).hide();
            location.reload();
        } else {
            alert('Erro: ' + data.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar card.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
};

function adicionarItemChecklist(cardId) {
    const item = prompt('Digite o item do checklist:');
    if (!item || item.trim() === '') return;
    
    fetch('<?php echo url('/projects/kanban/add-checklist-item'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            card_id: cardId,
            item: item.trim()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao adicionar item.');
    });
}

function atualizarChecklistItem(itemId, concluido) {
    fetch('<?php echo url('/projects/kanban'); ?>/checklist/' + itemId + '/update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            concluido: concluido
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Erro: ' + data.message);
            location.reload();
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao atualizar item.');
        location.reload();
    });
}

function removerItemChecklist(itemId) {
    if (!confirm('Remover este item do checklist?')) return;
    
    fetch('<?php echo url('/projects/kanban'); ?>/checklist/' + itemId + '/delete', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao remover item.');
    });
}

function adicionarTag(cardId) {
    const nome = prompt('Nome da tag:');
    if (!nome || nome.trim() === '') return;
    
    const cores = ['#0dcaf0', '#198754', '#ffc107', '#dc3545', '#0d6efd', '#6f42c1', '#fd7e14'];
    const cor = cores[Math.floor(Math.random() * cores.length)];
    
    fetch('<?php echo url('/projects/kanban/add-tag'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            card_id: cardId,
            nome: nome.trim(),
            cor: cor
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao adicionar tag.');
    });
}

function removerTag(tagId) {
    if (!confirm('Remover esta tag?')) return;
    
    fetch('<?php echo url('/projects/kanban'); ?>/tag/' + tagId + '/delete', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao remover tag.');
    });
}
</script>

