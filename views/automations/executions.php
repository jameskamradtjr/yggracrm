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
    // Por enquanto, apenas mostra ID
    // Pode ser expandido para buscar detalhes via AJAX
    document.getElementById('executionDetailsContent').innerHTML = `
        <p><strong>ID da Execução:</strong> ${executionId}</p>
        <p class="text-muted">Detalhes completos serão implementados em breve.</p>
    `;
    const modal = new bootstrap.Modal(document.getElementById('executionDetailsModal'));
    modal.show();
}
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

