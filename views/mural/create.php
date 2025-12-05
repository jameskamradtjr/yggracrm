<?php
$title = 'Novo Item do Mural';

ob_start();
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Novo Item do Mural</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/mural'); ?>">Mural</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Novo Item</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form id="mural-form" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               id="titulo" 
                               name="titulo" 
                               required 
                               placeholder="Ex: Festa de Fim de Ano, Aniversário do João...">
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" 
                                  id="descricao" 
                                  name="descricao" 
                                  rows="5" 
                                  placeholder="Descrição do evento ou aviso..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="data_inicio" class="form-label">Data de Início</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="data_inicio" 
                                       name="data_inicio">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="data_fim" class="form-label">Data de Término</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="data_fim" 
                                       name="data_fim">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="link_url" class="form-label">Link (URL)</label>
                                <input type="url" 
                                       class="form-control" 
                                       id="link_url" 
                                       name="link_url" 
                                       placeholder="https://...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="link_texto" class="form-label">Texto do Botão</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="link_texto" 
                                       name="link_texto" 
                                       placeholder="Ex: Saiba Mais, Ver Mais...">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="ordem" class="form-label">Ordem de Exibição</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="ordem" 
                                       name="ordem" 
                                       value="0" 
                                       min="0">
                                <small class="text-muted">Itens com menor número aparecem primeiro</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check form-switch mt-4">
                                    <input type="hidden" name="is_ativo" value="0">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="is_ativo" 
                                           name="is_ativo" 
                                           value="1"
                                           checked>
                                    <label class="form-check-label" for="is_ativo">
                                        Item Ativo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="imagem" class="form-label">Imagem</label>
                        <input type="file" 
                               class="form-control" 
                               id="imagem" 
                               name="imagem" 
                               accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                        <small class="text-muted">Formatos: JPG, PNG, GIF, WEBP</small>
                        <div id="imagem-preview" class="mt-3" style="display: none;">
                            <img id="preview-img" src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px;">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="<?php echo url('/mural'); ?>" class="btn btn-secondary">
                    <i class="ti ti-arrow-left me-2"></i>Voltar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-check me-2"></i>Salvar
                </button>
            </div>
            
            <?php echo csrf_field(); ?>
        </form>
    </div>
</div>

<script>
document.getElementById('imagem').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('imagem-preview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('imagem-preview').style.display = 'none';
    }
});

document.getElementById('mural-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando...';
    
    fetch('<?php echo url('/mural'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Item do mural criado com sucesso!');
            window.location.href = data.redirect || '<?php echo url('/mural'); ?>';
        } else {
            alert('Erro: ' + (data.message || 'Erro desconhecido'));
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar item do mural.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

