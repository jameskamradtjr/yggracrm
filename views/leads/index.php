<?php
$title = 'CRM de Leads';

ob_start();
?>

<!-- Dashboard de Métricas -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
            <div class="card-body px-4 py-3">
                <div class="row align-items-center">
                    <div class="col-9">
                        <h4 class="fw-semibold mb-2">CRM de Leads</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo url('/dashboard'); ?>">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Leads</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-3 text-end">
                        <a href="<?php echo url('/leads/create'); ?>" class="btn btn-primary">
                            <i class="ti ti-plus me-2"></i>
                            Novo Lead
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Métricas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="round-40 bg-primary-subtle text-primary d-flex align-items-center justify-content-center">
                            <i class="ti ti-users fs-5"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-0 fw-semibold">Total de Leads</h6>
                        <h4 class="mb-0"><?php echo $metrics['total']; ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="round-40 bg-info-subtle text-info d-flex align-items-center justify-content-center">
                            <i class="ti ti-eye fs-5"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-0 fw-semibold">Interessados</h6>
                        <h4 class="mb-0"><?php echo $metrics['interessados']['count']; ?> <small class="text-muted">(<?php echo $metrics['interessados']['percentage']; ?>%)</small></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="round-40 bg-warning-subtle text-warning d-flex align-items-center justify-content-center">
                            <i class="ti ti-handshake fs-5"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-0 fw-semibold">Negociação</h6>
                        <h4 class="mb-0"><?php echo $metrics['negociacao_proposta']['count']; ?> <small class="text-muted">(<?php echo $metrics['negociacao_proposta']['percentage']; ?>%)</small></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="round-40 bg-success-subtle text-success d-flex align-items-center justify-content-center">
                            <i class="ti ti-check fs-5"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-0 fw-semibold">Fechamento</h6>
                        <h4 class="mb-0"><?php echo $metrics['fechamento']['count']; ?> <small class="text-muted">(<?php echo $metrics['fechamento']['percentage']; ?>%)</small></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Origem dos Leads -->
<?php if (!empty($origens)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Origem dos Leads</h5>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($origens as $origem): ?>
                        <span class="badge bg-primary-subtle text-primary p-2">
                            <?php echo e($origem['origem']); ?>: <?php echo $origem['total']; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Card Informativo -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-info">
            <div class="card-body bg-info-subtle">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-2">
                            <i class="ti ti-info-circle me-2"></i>
                            Formulário Público de Captura
                        </h5>
                        <p class="text-muted mb-0">
                            <strong>⚠️ IMPORTANTE:</strong> O quiz cria <strong>LEADS</strong>, não clientes! 
                            Os leads aparecem aqui em <strong>/leads</strong> (CRM de Leads). 
                            Para convertê-los em clientes, acesse o lead e clique em "Converter em Cliente". 
                            Só então o cliente aparecerá em <strong>/clients</strong>.
                        </p>
                    </div>
                    <div class="ms-3">
                        <button type="button" class="btn btn-primary" onclick="gerarLinkUnico()">
                            <i class="ti ti-link me-2"></i>
                            Gerar Link do Quiz
                        </button>
                        <div id="quiz-link-container" class="mt-3" style="display: none;">
                            <div class="input-group">
                                <input type="text" id="quiz-link-input" class="form-control" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copiarLinkUnico()">
                                    <i class="ti ti-copy me-2"></i>
                                    Copiar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Kanban Board -->
<div class="row">
    <div class="col-12">
        <div class="action-btn layout-top-spacing mb-4 d-flex align-items-center justify-content-between flex-wrap gap-6">
            <h5 class="mb-0 fs-5">Funil de Vendas</h5>
        </div>
        
        <div class="scrumboard" id="cancel-row">
            <div class="layout-spacing pb-3">
                <div data-simplebar>
                    <div class="task-list-section">
                        <!-- Coluna: Interessados -->
                        <div data-item="item-interessados" class="task-list-container" data-action="sorting">
                            <div class="connect-sorting connect-sorting-todo">
                                <div class="task-container-header">
                                    <h6 class="item-head mb-0 fs-4 fw-semibold" data-item-title="Interessados">Interessados</h6>
                                    <div class="hstack gap-2">
                                        <span class="badge bg-info rounded-pill"><?php echo count($leads['interessados']); ?></span>
                                        <div class="dropdown">
                                            <a class="dropdown-toggle" href="javascript:void(0)" role="button" data-bs-toggle="dropdown">
                                                <i class="ti ti-dots-vertical text-dark"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item" href="javascript:void(0);">Ver Todos</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="connect-sorting-content" data-sortable="true" data-etapa="interessados">
                                    <?php if (empty($leads['interessados'])): ?>
                                        <div class="text-center text-muted py-4">
                                            <i class="ti ti-inbox fs-1 d-block mb-2"></i>
                                            <small>Nenhum lead nesta etapa</small>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($leads['interessados'] as $lead): ?>
                                            <?php include base_path('views/leads/_kanban_card.php'); ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Coluna: Negociação e Proposta -->
                        <div data-item="item-negociacao" class="task-list-container" data-action="sorting">
                            <div class="connect-sorting connect-sorting-inprogress">
                                <div class="task-container-header">
                                    <h6 class="item-head mb-0 fs-4 fw-semibold" data-item-title="Negociação e Proposta">Negociação e Proposta</h6>
                                    <div class="hstack gap-2">
                                        <span class="badge bg-warning rounded-pill"><?php echo count($leads['negociacao_proposta']); ?></span>
                                        <div class="dropdown">
                                            <a class="dropdown-toggle" href="javascript:void(0)" role="button" data-bs-toggle="dropdown">
                                                <i class="ti ti-dots-vertical text-dark"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item" href="javascript:void(0);">Ver Todos</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="connect-sorting-content" data-sortable="true" data-etapa="negociacao_proposta">
                                    <?php if (empty($leads['negociacao_proposta'])): ?>
                                        <div class="text-center text-muted py-4">
                                            <i class="ti ti-inbox fs-1 d-block mb-2"></i>
                                            <small>Nenhum lead nesta etapa</small>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($leads['negociacao_proposta'] as $lead): ?>
                                            <?php include base_path('views/leads/_kanban_card.php'); ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Coluna: Fechamento -->
                        <div data-item="item-fechamento" class="task-list-container" data-action="sorting">
                            <div class="connect-sorting connect-sorting-done">
                                <div class="task-container-header">
                                    <h6 class="item-head mb-0 fs-4 fw-semibold" data-item-title="Fechamento">Fechamento</h6>
                                    <div class="hstack gap-2">
                                        <span class="badge bg-success rounded-pill"><?php echo count($leads['fechamento']); ?></span>
                                        <div class="dropdown">
                                            <a class="dropdown-toggle" href="javascript:void(0)" role="button" data-bs-toggle="dropdown">
                                                <i class="ti ti-dots-vertical text-dark"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item" href="javascript:void(0);">Ver Todos</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="connect-sorting-content" data-sortable="true" data-etapa="fechamento">
                                    <?php if (empty($leads['fechamento'])): ?>
                                        <div class="text-center text-muted py-4">
                                            <i class="ti ti-inbox fs-1 d-block mb-2"></i>
                                            <small>Nenhum lead nesta etapa</small>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($leads['fechamento'] as $lead): ?>
                                            <?php include base_path('views/leads/_kanban_card.php'); ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Editar Lead -->
<div class="modal fade" id="editLeadModal" tabindex="-1" aria-labelledby="editLeadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editLeadModalLabel">Editar Lead</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="editLeadModalBody">
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

.task-list-container[data-item="item-interessados"] .connect-sorting {
    border-left: 4px solid #0dcaf0;
}

.task-list-container[data-item="item-negociacao"] .connect-sorting {
    border-left: 4px solid #ffc107;
}

.task-list-container[data-item="item-fechamento"] .connect-sorting {
    border-left: 4px solid #198754;
}

.card[data-draggable="true"] {
    transition: transform 0.2s, box-shadow 0.2s;
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
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
// Função para gerar link único do quiz
function gerarLinkUnico() {
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader me-2"></i>Gerando...';
    
    fetch('<?php echo url('/leads/generate-link'); ?>', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('quiz-link-input').value = data.url;
            document.getElementById('quiz-link-container').style.display = 'block';
            btn.innerHTML = '<i class="ti ti-refresh me-2"></i>Gerar Novo Link';
        } else {
            alert('Erro: ' + data.message);
            btn.innerHTML = originalText;
        }
        btn.disabled = false;
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao gerar link.');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function copiarLinkUnico() {
    const input = document.getElementById('quiz-link-input');
    input.select();
    navigator.clipboard.writeText(input.value).then(function() {
        alert('Link copiado!');
    });
}

// Inicializa Kanban com SortableJS
document.addEventListener('DOMContentLoaded', function() {
    const columns = document.querySelectorAll('[data-etapa]');
    
    columns.forEach(column => {
        new Sortable(column, {
            group: 'kanban',
            animation: 150,
            ghostClass: 'opacity-50',
            onEnd: function(evt) {
                const leadId = evt.item.dataset.leadId;
                const newEtapa = evt.to.dataset.etapa;
                
                if (!leadId || !newEtapa) return;
                
                // Atualiza etapa via AJAX
                fetch('<?php echo url('/leads/update-etapa-funil'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        lead_id: leadId,
                        etapa_funil: newEtapa
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        evt.from.appendChild(evt.item);
                        alert('Erro ao atualizar etapa: ' + data.message);
                    } else {
                        // Atualiza contadores
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    evt.from.appendChild(evt.item);
                    alert('Erro ao atualizar etapa.');
                });
            }
        });
    });
    
    // Abre modal ao clicar no card
    document.querySelectorAll('[data-lead-id]').forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.closest('.dropdown') || e.target.closest('a')) return;
            
            const leadId = this.dataset.leadId;
            if (leadId) {
                abrirEdicaoLead(leadId);
            }
        });
    });
});

function abrirEdicaoLead(leadId) {
    fetch('<?php echo url('/leads'); ?>/' + leadId + '/edit-modal', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.text())
    .then(html => {
        document.getElementById('editLeadModalBody').innerHTML = html;
        const modal = new bootstrap.Modal(document.getElementById('editLeadModal'));
        modal.show();
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao carregar dados do lead.');
    });
}
</script>
<?php
$scripts = ob_get_clean();

include base_path('views/layouts/app.php');
?>
