<?php
ob_start();
$title = $title ?? 'Novo Contrato';
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Novo Contrato</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/contracts'); ?>">Contratos</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Novo Contrato</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?php echo url('/contracts'); ?>">
            <?php echo csrf_field(); ?>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="template_id" class="form-label">Template (Opcional)</label>
                    <select name="template_id" id="template_id" class="form-select" onchange="loadTemplate()">
                        <option value="">Selecione um template...</option>
                        <?php foreach ($templates as $template): ?>
                            <option value="<?php echo $template->id; ?>"><?php echo e($template->nome); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Selecione um template para preencher automaticamente o conteúdo</small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="client_id" class="form-label">Cliente (Opcional)</label>
                    <?php 
                    $id = 'client_id';
                    $name = 'client_id';
                    $placeholder = 'Digite para buscar cliente...';
                    include base_path('views/components/tom-select-client.php'); 
                    ?>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="titulo" class="form-label">Título do Contrato <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="titulo" name="titulo" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="data_inicio" class="form-label">Data de Início</label>
                    <input type="date" class="form-control" id="data_inicio" name="data_inicio">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="data_termino" class="form-label">Data de Término</label>
                    <input type="date" class="form-control" id="data_termino" name="data_termino">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="valor_total" class="form-label">Valor Total</label>
                    <input type="number" step="0.01" class="form-control" id="valor_total" name="valor_total" placeholder="0.00">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
                </div>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo url('/contracts'); ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Criar Contrato</button>
            </div>
        </form>
    </div>
</div>

<script>
function loadTemplate() {
    const templateId = document.getElementById('template_id').value;
    if (!templateId) return;
    
    // Aqui você pode carregar o template via AJAX se necessário
    // Por enquanto, o template será processado no backend
}
</script>

<?php
$content = ob_get_clean();

// Tom Select Scripts
$scripts = '';
if (isset($GLOBALS['tom_select_inits'])) {
    ob_start();
    include base_path('views/components/tom-select-scripts.php');
    $scripts = ob_get_clean();
}

include base_path('views/layouts/app.php');
?>

