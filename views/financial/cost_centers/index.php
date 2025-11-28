<?php
$title = $title ?? 'Centros de Custo';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="card-title mb-0">
                        <i class="ti ti-building me-2"></i>
                        Centros de Custo
                    </h4>
                    <a href="<?php echo url('/financial/cost-centers/create'); ?>" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i>
                        Novo Centro de Custo
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Subcentros</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($costCenters)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        <i class="ti ti-inbox fs-1 d-block mb-2"></i>
                                        Nenhum centro de custo cadastrado
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($costCenters as $costCenter): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo e($costCenter->name); ?></strong>
                                        </td>
                                        <td>
                                            <?php if (!empty($costCenter->subCostCenters)): ?>
                                                <?php foreach ($costCenter->subCostCenters as $subCenter): ?>
                                                    <span class="badge bg-info me-1">
                                                        <?php echo e($subCenter->name); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Nenhum subcentro</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-success" onclick="addSubCenter(<?php echo $costCenter->id; ?>)">
                                                <i class="ti ti-plus"></i>
                                                Subcentro
                                            </button>
                                            <a href="<?php echo url('/financial/cost-centers/' . $costCenter->id . '/edit'); ?>" class="btn btn-sm btn-info">
                                                <i class="ti ti-edit"></i>
                                            </a>
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

<!-- Modal para adicionar subcentro -->
<div class="modal fade" id="subCenterModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Subcentro de Custo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="subCenterForm" method="POST">
                <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="cost_center_id" id="modal_cost_center_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome do Subcentro *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function addSubCenter(costCenterId) {
    document.getElementById('modal_cost_center_id').value = costCenterId;
    const modal = new bootstrap.Modal(document.getElementById('subCenterModal'));
    modal.show();
}

document.getElementById('subCenterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('<?php echo url('/financial/cost-centers/subcenters'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

