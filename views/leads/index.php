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


<!-- Abas de Navegação -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs" id="leadsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="kanban-tab" data-bs-toggle="tab" data-bs-target="#kanban-pane" type="button" role="tab" aria-controls="kanban-pane" aria-selected="true">
                            <i class="ti ti-layout-kanban me-2"></i>
                            Funil de Vendas (Kanban)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="oportunidades-tab" data-bs-toggle="tab" data-bs-target="#oportunidades-pane" type="button" role="tab" aria-controls="oportunidades-pane" aria-selected="false">
                            <i class="ti ti-briefcase me-2"></i>
                            Oportunidades
                            <span class="badge bg-primary ms-2"><?php echo count($oportunidades); ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="leads-tab" data-bs-toggle="tab" data-bs-target="#leads-pane" type="button" role="tab" aria-controls="leads-pane" aria-selected="false">
                            <i class="ti ti-users me-2"></i>
                            Todos os Leads
                            <span class="badge bg-info ms-2"><?php echo count($allLeads); ?></span>
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="leadsTabsContent">
                    <!-- Aba: Kanban -->
                    <div class="tab-pane fade show active" id="kanban-pane" role="tabpanel" aria-labelledby="kanban-tab">
                        <div class="action-btn layout-top-spacing mb-4 d-flex align-items-center justify-content-between flex-wrap gap-6 mt-4">
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
                    
                    <!-- Aba: Oportunidades -->
                    <div class="tab-pane fade" id="oportunidades-pane" role="tabpanel" aria-labelledby="oportunidades-tab">
                        <div class="mt-4">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Email</th>
                                            <th>Telefone</th>
                                            <th>Valor Oportunidade</th>
                                            <th>Etapa</th>
                                            <th>Score</th>
                                            <th>Responsável</th>
                                            <th>Data</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($oportunidades)): ?>
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-4">
                                                    <i class="ti ti-briefcase fs-1 d-block mb-2"></i>
                                                    Nenhuma oportunidade encontrada
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($oportunidades as $lead): ?>
                                                <tr>
                                                    <td><?php echo e($lead->nome); ?></td>
                                                    <td><?php echo e($lead->email); ?></td>
                                                    <td><?php echo e($lead->telefone); ?></td>
                                                    <td>
                                                        <?php if ($lead->valor_oportunidade): ?>
                                                            <strong class="text-success">R$ <?php echo number_format($lead->valor_oportunidade, 2, ',', '.'); ?></strong>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $etapaLabels = [
                                                            'interessados' => '<span class="badge bg-info">Interessados</span>',
                                                            'negociacao_proposta' => '<span class="badge bg-warning">Negociação</span>',
                                                            'fechamento' => '<span class="badge bg-success">Fechamento</span>'
                                                        ];
                                                        echo $etapaLabels[$lead->etapa_funil] ?? '<span class="badge bg-secondary">' . e($lead->etapa_funil) . '</span>';
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary"><?php echo $lead->score_potencial ?? 0; ?></span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $responsible = $lead->responsible();
                                                        echo $responsible ? e($responsible->name) : '<span class="text-muted">-</span>';
                                                        ?>
                                                    </td>
                                                    <td><?php echo date('d/m/Y', strtotime($lead->created_at)); ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="<?php echo url('/leads/' . $lead->id); ?>" class="btn btn-sm btn-info" title="Ver">
                                                                <i class="ti ti-eye"></i>
                                                            </a>
                                                            <a href="<?php echo url('/leads/' . $lead->id . '/edit'); ?>" class="btn btn-sm btn-primary" title="Editar">
                                                                <i class="ti ti-edit"></i>
                                                            </a>
                                                            <button class="btn btn-sm btn-danger" onclick="excluirLead(<?php echo $lead->id; ?>, '<?php echo e($lead->nome); ?>')" title="Excluir">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Aba: Todos os Leads -->
                    <div class="tab-pane fade" id="leads-pane" role="tabpanel" aria-labelledby="leads-tab">
                        <div class="mt-4">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Email</th>
                                            <th>Telefone</th>
                                            <th>Etapa</th>
                                            <th>Score</th>
                                            <th>Origem</th>
                                            <th>Responsável</th>
                                            <th>Data</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($allLeads)): ?>
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-4">
                                                    <i class="ti ti-users fs-1 d-block mb-2"></i>
                                                    Nenhum lead encontrado
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($allLeads as $lead): ?>
                                                <tr>
                                                    <td><?php echo e($lead->nome); ?></td>
                                                    <td><?php echo e($lead->email); ?></td>
                                                    <td><?php echo e($lead->telefone); ?></td>
                                                    <td>
                                                        <?php
                                                        $etapaLabels = [
                                                            'interessados' => '<span class="badge bg-info">Interessados</span>',
                                                            'negociacao_proposta' => '<span class="badge bg-warning">Negociação</span>',
                                                            'fechamento' => '<span class="badge bg-success">Fechamento</span>'
                                                        ];
                                                        echo $etapaLabels[$lead->etapa_funil] ?? '<span class="badge bg-secondary">' . e($lead->etapa_funil) . '</span>';
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary"><?php echo $lead->score_potencial ?? 0; ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if ($lead->origem): ?>
                                                            <span class="badge bg-secondary"><?php echo e($lead->origem); ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $responsible = $lead->responsible();
                                                        echo $responsible ? e($responsible->name) : '<span class="text-muted">-</span>';
                                                        ?>
                                                    </td>
                                                    <td><?php echo date('d/m/Y', strtotime($lead->created_at)); ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="<?php echo url('/leads/' . $lead->id); ?>" class="btn btn-sm btn-info" title="Ver">
                                                                <i class="ti ti-eye"></i>
                                                            </a>
                                                            <a href="<?php echo url('/leads/' . $lead->id . '/edit'); ?>" class="btn btn-sm btn-primary" title="Editar">
                                                                <i class="ti ti-edit"></i>
                                                            </a>
                                                            <button class="btn btn-sm btn-danger" onclick="excluirLead(<?php echo $lead->id; ?>, '<?php echo e($lead->nome); ?>')" title="Excluir">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
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
    if (!leadId) {
        alert('ID do lead não informado.');
        return;
    }
    
    // Mostra loading
    document.getElementById('editLeadModalBody').innerHTML = '<div class="text-center p-4"><div class="spinner-border" role="status"><span class="visually-hidden">Carregando...</span></div></div>';
    
    const modal = new bootstrap.Modal(document.getElementById('editLeadModal'));
    modal.show();
    
    fetch('<?php echo url('/leads'); ?>/' + leadId + '/edit-modal', {
        method: 'GET',
        headers: {
            'Accept': 'text/html'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro ao carregar: ' + response.status);
        }
        return response.text();
    })
    .then(html => {
        if (!html || html.trim() === '') {
            throw new Error('Resposta vazia do servidor');
        }
        
        document.getElementById('editLeadModalBody').innerHTML = html;
        
        // Busca o nome do lead do atributo data ou do campo input
        const nomeContainer = document.querySelector('#editLeadModalBody [data-lead-nome]');
        const nomeInput = document.querySelector('#editLeadModalBody input[name="nome"]');
        
        let nomeLead = '';
        if (nomeContainer) {
            nomeLead = nomeContainer.getAttribute('data-lead-nome') || '';
        } else if (nomeInput) {
            nomeLead = nomeInput.value.trim();
        }
        
        // Atualiza o título do modal
        const modalTitle = document.getElementById('editLeadModalLabel');
        if (modalTitle) {
            modalTitle.textContent = nomeLead ? `Editar Lead: ${nomeLead}` : 'Editar Lead';
        }
        
        // Re-executa scripts dentro do HTML carregado (se houver)
        const scripts = document.getElementById('editLeadModalBody').querySelectorAll('script');
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => {
                newScript.setAttribute(attr.name, attr.value);
            });
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    })
    .catch(error => {
        console.error('Erro ao carregar lead:', error);
        document.getElementById('editLeadModalBody').innerHTML = `
            <div class="alert alert-danger">
                <h6>Erro ao carregar dados do lead</h6>
                <p class="mb-0">${error.message}</p>
                <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="bootstrap.Modal.getInstance(document.getElementById('editLeadModal')).hide()">Fechar</button>
            </div>
        `;
    });
}

// Função global para salvar lead (será chamada pelo formulário carregado via AJAX)
window.salvarLead = function(event, leadId) {
    event.preventDefault();
    
    if (!leadId) {
        alert('ID do lead não informado.');
        return;
    }
    
    const form = event.target;
    const formData = new FormData(form);
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';
    
    fetch('<?php echo url('/leads'); ?>/' + leadId + '/update', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro na requisição: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const modalInstance = bootstrap.Modal.getInstance(document.getElementById('editLeadModal'));
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro desconhecido'));
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Erro ao salvar lead:', error);
        alert('Erro ao salvar lead: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
};

function excluirLead(leadId, leadNome) {
    if (!leadId) {
        console.error('ID do lead não fornecido');
        return;
    }
    
    if (!confirm(`Tem certeza que deseja excluir o lead "${leadNome}"?\n\nEsta ação não pode ser desfeita.`)) {
        return;
    }
    
    // Cria formulário para enviar requisição DELETE
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?php echo url('/leads'); ?>/' + leadId + '/delete';
    
    // Adiciona CSRF token
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
$scripts = ob_get_clean();

include base_path('views/layouts/app.php');
?>
