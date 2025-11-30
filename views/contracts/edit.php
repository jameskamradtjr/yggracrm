<?php
ob_start();
$title = $title ?? 'Editar Contrato';
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Editar Contrato <?php echo e($contract->numero_contrato); ?></h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/contracts'); ?>">Contratos</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Editar</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?php echo url('/contracts/' . $contract->id); ?>">
            <?php echo csrf_field(); ?>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="template_id" class="form-label">Template</label>
                    <select name="template_id" id="template_id" class="form-select" onchange="loadTemplate()">
                        <option value="">Selecione um template...</option>
                        <?php foreach ($templates as $template): ?>
                            <option value="<?php echo $template->id; ?>" <?php echo $contract->template_id == $template->id ? 'selected' : ''; ?>>
                                <?php echo e($template->nome); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="client_id" class="form-label">Cliente</label>
                    <select name="client_id" id="client_id" class="form-select">
                        <option value="">Selecione um cliente...</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client->id; ?>" <?php echo $contract->client_id == $client->id ? 'selected' : ''; ?>>
                                <?php echo e($client->nome_razao_social); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="titulo" class="form-label">Título do Contrato <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo e($contract->titulo); ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="data_inicio" class="form-label">Data de Início</label>
                    <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $contract->data_inicio ? date('Y-m-d', strtotime($contract->data_inicio)) : ''; ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="data_termino" class="form-label">Data de Término</label>
                    <input type="date" class="form-control" id="data_termino" name="data_termino" value="<?php echo $contract->data_termino ? date('Y-m-d', strtotime($contract->data_termino)) : ''; ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="valor_total" class="form-label">Valor Total</label>
                    <input type="number" step="0.01" class="form-control" id="valor_total" name="valor_total" value="<?php echo $contract->valor_total ?? ''; ?>" placeholder="0.00">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="conteudo_gerado" class="form-label">Conteúdo do Contrato</label>
                    <textarea class="form-control" id="conteudo_gerado" name="conteudo_gerado" rows="15"><?php echo e($contract->conteudo_gerado); ?></textarea>
                    <small class="text-muted">Use variáveis como {{nome_cliente}}, {{documento_cliente}}, etc.</small>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?php echo e($contract->observacoes ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo url('/contracts/' . $contract->id); ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<script src="<?php echo asset('tema/assets/libs/tinymce/tinymce.min.js'); ?>"></script>
<script>
tinymce.init({
    selector: '#conteudo_gerado',
    height: 500,
    menubar: false,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount'
    ],
    toolbar: 'undo redo | blocks | ' +
        'bold italic forecolor | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | ' +
        'removeformat | help',
    content_style: 'body { font-family: Arial, sans-serif; font-size: 14px }'
});

function loadTemplate() {
    const templateId = document.getElementById('template_id').value;
    if (!templateId) return;
    
    // O template será processado no backend ao salvar
}
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

