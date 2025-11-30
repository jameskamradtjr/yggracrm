<?php
ob_start();
$title = $title ?? 'Kanban - ' . $project->titulo;
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Kanban - <?php echo e($project->titulo); ?></h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/projects'); ?>">Projetos</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/projects/' . $project->id); ?>"><?php echo e($project->titulo); ?></a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Kanban</li>
                    </ol>
                </nav>
            </div>
            <div class="col-3">
                <div class="text-end">
                    <a href="<?php echo url('/projects/' . $project->id); ?>" class="btn btn-secondary">
                        <i class="ti ti-arrow-left me-2"></i>Voltar ao Projeto
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="action-btn layout-top-spacing mb-7 d-flex align-items-center justify-content-between flex-wrap gap-6">
    <h5 class="mb-0 fs-5"><?php echo e($project->titulo); ?></h5>
</div>

<div class="scrumboard" id="cancel-row">
    <div class="layout-spacing pb-3">
        <div data-simplebar>
            <div class="task-list-section">
                <!-- Coluna: Backlog -->
                <div data-item="item-backlog" class="task-list-container" data-action="sorting">
                    <div class="connect-sorting connect-sorting-backlog">
                        <div class="task-container-header">
                            <h6 class="item-head mb-0 fs-4 fw-semibold" data-item-title="Backlog">Backlog</h6>
                            <div class="hstack gap-2">
                                <div class="add-kanban-title">
                                    <a class="addTask d-flex align-items-center justify-content-center gap-1 lh-sm" 
                                       href="javascript:void(0)" 
                                       data-bs-toggle="modal" 
                                       data-bs-target="#addCardModal"
                                       data-coluna="backlog"
                                       data-bs-toggle="tooltip" 
                                       data-bs-placement="top" 
                                       data-bs-title="Adicionar Card">
                                        <i class="ti ti-plus text-dark"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="connect-sorting-content" data-sortable="true" data-coluna="backlog">
                            <?php if (empty($cards['backlog'])): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="ti ti-inbox fs-1 d-block mb-2"></i>
                                    <small>Nenhum card nesta coluna</small>
                                </div>
                            <?php else: ?>
                                <?php foreach ($cards['backlog'] as $card): ?>
                                    <?php 
                                    $cardData = [
                                        'card' => $card,
                                        'users' => $users,
                                        'project' => $project
                                    ];
                                    include base_path('views/projects/_kanban_card.php'); 
                                    ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Coluna: A Fazer -->
                <div data-item="item-a_fazer" class="task-list-container" data-action="sorting">
                    <div class="connect-sorting connect-sorting-a_fazer">
                        <div class="task-container-header">
                            <h6 class="item-head mb-0 fs-4 fw-semibold" data-item-title="A Fazer">A Fazer</h6>
                            <div class="hstack gap-2">
                                <div class="add-kanban-title">
                                    <a class="addTask d-flex align-items-center justify-content-center gap-1 lh-sm" 
                                       href="javascript:void(0)" 
                                       data-bs-toggle="modal" 
                                       data-bs-target="#addCardModal"
                                       data-coluna="a_fazer"
                                       data-bs-toggle="tooltip" 
                                       data-bs-placement="top" 
                                       data-bs-title="Adicionar Card">
                                        <i class="ti ti-plus text-dark"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="connect-sorting-content" data-sortable="true" data-coluna="a_fazer">
                            <?php if (empty($cards['a_fazer'])): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="ti ti-inbox fs-1 d-block mb-2"></i>
                                    <small>Nenhum card nesta coluna</small>
                                </div>
                            <?php else: ?>
                                <?php foreach ($cards['a_fazer'] as $card): ?>
                                    <?php 
                                    $cardData = [
                                        'card' => $card,
                                        'users' => $users,
                                        'project' => $project
                                    ];
                                    include base_path('views/projects/_kanban_card.php'); 
                                    ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Coluna: Fazendo -->
                <div data-item="item-fazendo" class="task-list-container" data-action="sorting">
                    <div class="connect-sorting connect-sorting-fazendo">
                        <div class="task-container-header">
                            <h6 class="item-head mb-0 fs-4 fw-semibold" data-item-title="Fazendo">Fazendo</h6>
                            <div class="hstack gap-2">
                                <div class="add-kanban-title">
                                    <a class="addTask d-flex align-items-center justify-content-center gap-1 lh-sm" 
                                       href="javascript:void(0)" 
                                       data-bs-toggle="modal" 
                                       data-bs-target="#addCardModal"
                                       data-coluna="fazendo"
                                       data-bs-toggle="tooltip" 
                                       data-bs-placement="top" 
                                       data-bs-title="Adicionar Card">
                                        <i class="ti ti-plus text-dark"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="connect-sorting-content" data-sortable="true" data-coluna="fazendo">
                            <?php if (empty($cards['fazendo'])): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="ti ti-inbox fs-1 d-block mb-2"></i>
                                    <small>Nenhum card nesta coluna</small>
                                </div>
                            <?php else: ?>
                                <?php foreach ($cards['fazendo'] as $card): ?>
                                    <?php 
                                    $cardData = [
                                        'card' => $card,
                                        'users' => $users,
                                        'project' => $project
                                    ];
                                    include base_path('views/projects/_kanban_card.php'); 
                                    ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Coluna: Testes -->
                <div data-item="item-testes" class="task-list-container" data-action="sorting">
                    <div class="connect-sorting connect-sorting-testes">
                        <div class="task-container-header">
                            <h6 class="item-head mb-0 fs-4 fw-semibold" data-item-title="Testes">Testes</h6>
                            <div class="hstack gap-2">
                                <div class="add-kanban-title">
                                    <a class="addTask d-flex align-items-center justify-content-center gap-1 lh-sm" 
                                       href="javascript:void(0)" 
                                       data-bs-toggle="modal" 
                                       data-bs-target="#addCardModal"
                                       data-coluna="testes"
                                       data-bs-toggle="tooltip" 
                                       data-bs-placement="top" 
                                       data-bs-title="Adicionar Card">
                                        <i class="ti ti-plus text-dark"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="connect-sorting-content" data-sortable="true" data-coluna="testes">
                            <?php if (empty($cards['testes'])): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="ti ti-inbox fs-1 d-block mb-2"></i>
                                    <small>Nenhum card nesta coluna</small>
                                </div>
                            <?php else: ?>
                                <?php foreach ($cards['testes'] as $card): ?>
                                    <?php 
                                    $cardData = [
                                        'card' => $card,
                                        'users' => $users,
                                        'project' => $project
                                    ];
                                    include base_path('views/projects/_kanban_card.php'); 
                                    ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Coluna: Publicado -->
                <div data-item="item-publicado" class="task-list-container" data-action="sorting">
                    <div class="connect-sorting connect-sorting-publicado">
                        <div class="task-container-header">
                            <h6 class="item-head mb-0 fs-4 fw-semibold" data-item-title="Publicado">Publicado</h6>
                            <div class="hstack gap-2">
                                <div class="add-kanban-title">
                                    <a class="addTask d-flex align-items-center justify-content-center gap-1 lh-sm" 
                                       href="javascript:void(0)" 
                                       data-bs-toggle="modal" 
                                       data-bs-target="#addCardModal"
                                       data-coluna="publicado"
                                       data-bs-toggle="tooltip" 
                                       data-bs-placement="top" 
                                       data-bs-title="Adicionar Card">
                                        <i class="ti ti-plus text-dark"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="connect-sorting-content" data-sortable="true" data-coluna="publicado">
                            <?php if (empty($cards['publicado'])): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="ti ti-inbox fs-1 d-block mb-2"></i>
                                    <small>Nenhum card nesta coluna</small>
                                </div>
                            <?php else: ?>
                                <?php foreach ($cards['publicado'] as $card): ?>
                                    <?php 
                                    $cardData = [
                                        'card' => $card,
                                        'users' => $users,
                                        'project' => $project
                                    ];
                                    include base_path('views/projects/_kanban_card.php'); 
                                    ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Adicionar/Editar Card -->
<div class="modal fade" id="addCardModal" tabindex="-1" role="dialog" aria-labelledby="addCardModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="add-card-title modal-title" id="addCardModalTitleLabel1">Adicionar Card</h5>
                <h5 class="edit-card-title modal-title" id="addCardModalTitleLabel2" style="display: none;">Editar Card</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="addCardModalBody">
                <form id="cardForm">
                    <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="project_id" value="<?php echo $project->id; ?>">
                    <input type="hidden" name="card_id" id="card_id">
                    <input type="hidden" name="coluna" id="card_coluna">
                    
                    <div class="mb-3">
                        <label for="card_titulo" class="form-label">Título <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="card_titulo" name="titulo" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="card_descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="card_descricao" name="descricao" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="card_prioridade" class="form-label">Prioridade</label>
                            <select class="form-select" id="card_prioridade" name="prioridade">
                                <option value="baixa">Baixa</option>
                                <option value="media" selected>Média</option>
                                <option value="alta">Alta</option>
                                <option value="urgente">Urgente</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="card_responsible" class="form-label">Responsável</label>
                            <select class="form-select" id="card_responsible" name="responsible_user_id">
                                <option value="">Sem responsável</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user->id; ?>"><?php echo e($user->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="card_prazo" class="form-label">Prazo</label>
                        <input type="date" class="form-control" id="card_prazo" name="data_prazo">
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-start">
                <div class="d-flex gap-6">
                    <button type="button" class="btn add-card-btn btn-primary" onclick="salvarCard()">Adicionar Card</button>
                    <button type="button" class="btn edit-card-btn btn-success" onclick="salvarCard()" style="display: none;">Salvar</button>
                    <button type="button" class="btn bg-danger-subtle text-danger d-flex align-items-center gap-1" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Editar Card Completo (com checklist e tags) -->
<div class="modal fade" id="editCardModal" tabindex="-1" aria-labelledby="editCardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCardModalLabel">Editar Card</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="editCardModalBody">
                <!-- Conteúdo carregado via AJAX -->
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos do Kanban */
.scrumboard {
    overflow-x: auto;
}

.task-list-section {
    display: flex;
    gap: 20px;
    min-width: 100%;
}

.task-list-container {
    min-width: 320px;
    max-width: 320px;
    flex-shrink: 0;
}

.connect-sorting {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    min-height: 500px;
}

.task-container-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #dee2e6;
}

.connect-sorting-content {
    min-height: 400px;
    max-height: 800px;
    overflow-y: auto;
}

.task-list-container[data-item="item-backlog"] .connect-sorting {
    border-left: 4px solid #6c757d;
}

.task-list-container[data-item="item-a_fazer"] .connect-sorting {
    border-left: 4px solid #0dcaf0;
}

.task-list-container[data-item="item-fazendo"] .connect-sorting {
    border-left: 4px solid #ffc107;
}

.task-list-container[data-item="item-testes"] .connect-sorting {
    border-left: 4px solid #0d6efd;
}

.task-list-container[data-item="item-publicado"] .connect-sorting {
    border-left: 4px solid #198754;
}

.card[data-draggable="true"] {
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
}

.card[data-draggable="true"]:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.opacity-50 {
    opacity: 0.5;
}
</style>

<?php
$content = ob_get_clean();

// Scripts
ob_start();
?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
let currentCardId = null;
let currentColuna = 'backlog';

// Inicializa Kanban com SortableJS
document.addEventListener('DOMContentLoaded', function() {
    const columns = document.querySelectorAll('[data-coluna]');
    
    columns.forEach(column => {
        new Sortable(column, {
            group: 'kanban',
            animation: 150,
            ghostClass: 'opacity-50',
            onEnd: function(evt) {
                const cardId = evt.item.dataset.cardId;
                const newColuna = evt.to.dataset.coluna;
                const newIndex = Array.from(evt.to.children).indexOf(evt.item);
                
                if (!cardId || !newColuna) return;
                
                // Atualiza coluna via AJAX
                fetch('<?php echo url('/projects/kanban/update-card-column'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        card_id: cardId,
                        coluna: newColuna,
                        ordem: newIndex
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        evt.from.appendChild(evt.item);
                        alert('Erro ao atualizar card: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    evt.from.appendChild(evt.item);
                    alert('Erro ao atualizar card.');
                });
            }
        });
    });
    
    // Configura modal de adicionar card
    const addCardModal = document.getElementById('addCardModal');
    if (addCardModal) {
        addCardModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const coluna = button.getAttribute('data-coluna') || 'backlog';
            currentColuna = coluna;
            document.getElementById('card_coluna').value = coluna;
            
            // Limpa formulário
            document.getElementById('cardForm').reset();
            document.getElementById('card_id').value = '';
            document.getElementById('card_coluna').value = coluna;
            document.querySelector('.add-card-title').style.display = 'block';
            document.querySelector('.edit-card-title').style.display = 'none';
            document.querySelector('.add-card-btn').style.display = 'block';
            document.querySelector('.edit-card-btn').style.display = 'none';
        });
    }
});

// Abre modal de edição completa do card
function abrirEdicaoCard(cardId) {
    currentCardId = cardId;
    fetch('<?php echo url('/projects/kanban'); ?>/' + cardId + '/edit-modal', {
        method: 'GET',
        headers: {
            'Accept': 'text/html'
        }
    })
    .then(response => response.text())
    .then(html => {
        document.getElementById('editCardModalBody').innerHTML = html;
        
        // Busca o nome do card do atributo data ou do campo input
        const nomeContainer = document.querySelector('#editCardModalBody [data-card-nome]');
        const nomeInput = document.querySelector('#editCardModalBody input[name="titulo"]');
        
        let nomeCard = '';
        if (nomeContainer) {
            nomeCard = nomeContainer.getAttribute('data-card-nome') || '';
        } else if (nomeInput) {
            nomeCard = nomeInput.value.trim();
        }
        
        // Atualiza o título do modal
        const modalTitle = document.getElementById('editCardModalLabel');
        if (modalTitle) {
            modalTitle.textContent = nomeCard ? `Editar Card: ${nomeCard}` : 'Editar Card';
        }
        
        const modal = new bootstrap.Modal(document.getElementById('editCardModal'));
        modal.show();
        
        // Re-executa scripts dentro do HTML carregado
        const scripts = document.getElementById('editCardModalBody').querySelectorAll('script');
        scripts.forEach(script => {
            const newScript = document.createElement('script');
            Array.from(script.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.appendChild(document.createTextNode(script.innerHTML));
            script.parentNode.replaceChild(newScript, script);
        });
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao carregar dados do card: ' + error.message);
    });
}

// Salva card (criar ou atualizar)
function salvarCard() {
    const form = document.getElementById('cardForm');
    const formData = new FormData(form);
    const cardId = document.getElementById('card_id').value;
    
    const url = cardId 
        ? '<?php echo url('/projects/kanban'); ?>/' + cardId + '/update'
        : '<?php echo url('/projects/kanban/store-card'); ?>';
    
    const submitBtn = cardId 
        ? document.querySelector('.edit-card-btn')
        : document.querySelector('.add-card-btn');
    
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Salvando...';

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'Erro na requisição');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('addCardModal')).hide();
            location.reload();
        } else {
            let errorMsg = data.message || 'Erro desconhecido';
            if (data.errors) {
                const errorList = Object.values(data.errors).flat().join('\n');
                errorMsg += '\n\n' + errorList;
            }
            alert('Erro: ' + errorMsg);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar card: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// Exclui card
function excluirCard(cardId) {
    if (!confirm('Tem certeza que deseja excluir este card?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('_csrf_token', document.querySelector('meta[name="csrf-token"]').content);
    
    fetch('<?php echo url('/projects/kanban'); ?>/' + cardId + '/delete', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
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
        alert('Erro ao excluir card.');
    });
}
</script>
<?php
$scripts = ob_get_clean();
include base_path('views/layouts/app.php');
?>

