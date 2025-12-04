<?php
$title = 'Histórico de Execuções';
ob_start();
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Histórico de Execuções</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/automations'); ?>">Automações</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Histórico</li>
                    </ol>
                </nav>
            </div>
            <div class="col-3 text-end">
                <a href="<?php echo url('/automations'); ?>" class="btn btn-light">
                    <i class="ti ti-arrow-left me-2"></i>
                    Voltar
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h5 class="mb-2"><?php echo e($automation->name); ?></h5>
                        <p class="text-muted mb-0">Histórico de execuções desta automação</p>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Status</th>
                                <th>Iniciado em</th>
                                <th>Concluído em</th>
                                <th>Erro</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($executions)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Nenhuma execução encontrada.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($executions as $execution): ?>
                                    <tr>
                                        <td><?php echo $execution->id; ?></td>
                                        <td>
                                            <?php if ($execution->status === 'completed'): ?>
                                                <span class="badge bg-success">Concluída</span>
                                            <?php elseif ($execution->status === 'failed'): ?>
                                                <span class="badge bg-danger">Falhou</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Em execução</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i:s', strtotime($execution->started_at)); ?></td>
                                        <td>
                                            <?php echo $execution->completed_at ? date('d/m/Y H:i:s', strtotime($execution->completed_at)) : '-'; ?>
                                        </td>
                                        <td>
                                            <?php if ($execution->error_message): ?>
                                                <span class="text-danger" title="<?php echo e($execution->error_message); ?>">
                                                    <i class="ti ti-alert-circle"></i>
                                                </span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" 
                                                    class="btn btn-sm btn-info" 
                                                    onclick="viewExecutionDetails(<?php echo $execution->id; ?>)"
                                                    title="Ver detalhes">
                                                <i class="ti ti-eye"></i>
                                            </button>
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

<!-- Modal para detalhes da execução -->
<div class="modal fade" id="executionDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes da Execução</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="executionDetailsContent">
                <!-- Conteúdo será preenchido via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewExecutionDetails(executionId) {
    const contentDiv = document.getElementById('executionDetailsContent');
    
    // Mostra loading
    contentDiv.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-2 text-muted">Carregando detalhes...</p>
        </div>
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('executionDetailsModal'));
    modal.show();
    
    // Busca detalhes via AJAX
    fetch('<?php echo url('/automations/executions'); ?>/' + executionId + '/details')
        .catch(error => {
            console.error('Erro na requisição:', error);
            contentDiv.innerHTML = `
                <div class="alert alert-danger">
                    <strong>Erro de conexão:</strong> Não foi possível conectar ao servidor.
                    <br><small>${error.message}</small>
                </div>
            `;
            throw error; // Re-throw para ser capturado pelo catch externo
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                contentDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Erro:</strong> ${data.message || 'Não foi possível carregar os detalhes'}
                    </div>
                `;
                return;
            }
            
            const execution = data.execution;
            // Garante que logs é sempre um array
            let logs = execution.execution_log || [];
            if (!Array.isArray(logs)) {
                logs = [];
            }
            
            let html = `
                <div class="mb-4">
                    <h6 class="fw-semibold mb-3">Informações Gerais</h6>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong>ID:</strong> ${execution.id}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Status:</strong> 
                            <span class="badge ${execution.status === 'completed' ? 'bg-success' : execution.status === 'failed' ? 'bg-danger' : 'bg-warning'}">
                                ${execution.status === 'completed' ? 'Concluída' : execution.status === 'failed' ? 'Falhou' : 'Em execução'}
                            </span>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Iniciado em:</strong> ${new Date(execution.started_at).toLocaleString('pt-BR')}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Concluído em:</strong> ${execution.completed_at ? new Date(execution.completed_at).toLocaleString('pt-BR') : '-'}
                        </div>
                    </div>
                    ${execution.error_message ? `
                        <div class="alert alert-danger mt-3">
                            <strong>Erro:</strong> ${execution.error_message}
                        </div>
                    ` : ''}
                </div>
            `;
            
            // Dados do trigger
            let triggerData = execution.trigger_data;
            if (triggerData) {
                // Se for string, tenta parsear
                if (typeof triggerData === 'string') {
                    try {
                        triggerData = JSON.parse(triggerData);
                    } catch (e) {
                        triggerData = null;
                    }
                }
                
                if (triggerData && typeof triggerData === 'object' && Object.keys(triggerData).length > 0) {
                    html += `
                        <div class="mb-4">
                            <h6 class="fw-semibold mb-3">Dados do Trigger</h6>
                            <pre class="bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto;"><code>${JSON.stringify(triggerData, null, 2)}</code></pre>
                        </div>
                    `;
                }
            }
            
            // Logs de execução
            if (logs.length > 0) {
                html += `
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-3">Logs de Execução (${logs.length})</h6>
                        <div class="timeline" style="max-height: 400px; overflow-y: auto;">
                `;
                
                logs.forEach((log, index) => {
                    const timestamp = log.timestamp || 'N/A';
                    const message = log.message || 'Sem mensagem';
                    const data = log.data || {};
                    
                    html += `
                        <div class="timeline-item mb-3 pb-3 ${index < logs.length - 1 ? 'border-bottom' : ''}">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="avatar-xs">
                                        <span class="avatar-title rounded-circle bg-primary-subtle text-primary">
                                            ${index + 1}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="d-flex align-items-center mb-1">
                                        <h6 class="mb-0 me-2">${message}</h6>
                                        <small class="text-muted">${timestamp}</small>
                                    </div>
                                    ${Object.keys(data).length > 0 ? `
                                        <div class="mt-2">
                                            <button class="btn btn-sm btn-link p-0" type="button" data-bs-toggle="collapse" data-bs-target="#logData${index}" aria-expanded="false">
                                                Ver detalhes <i class="ti ti-chevron-down"></i>
                                            </button>
                                            <div class="collapse mt-2" id="logData${index}">
                                                <pre class="bg-light p-2 rounded small" style="max-height: 200px; overflow-y: auto;"><code>${JSON.stringify(data, null, 2)}</code></pre>
                                            </div>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            } else {
                html += `
                    <div class="alert alert-info">
                        Nenhum log de execução disponível.
                    </div>
                `;
            }
            
            contentDiv.innerHTML = html;
        })
        .catch(error => {
            console.error('Erro ao buscar detalhes:', error);
            contentDiv.innerHTML = `
                <div class="alert alert-danger">
                    <strong>Erro:</strong> Não foi possível carregar os detalhes da execução.
                    <br><small>${error.message}</small>
                </div>
            `;
        });
}
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

