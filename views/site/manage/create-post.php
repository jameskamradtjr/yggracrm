<?php
$title = 'Novo Post';

ob_start();
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Novo Post</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/site/manage'); ?>">Meu Site</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Novo Post</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?php echo url('/site/manage/posts'); ?>" id="postForm">
            <?php echo csrf_field(); ?>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="title" class="form-label">Título <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               id="title" 
                               name="title" 
                               value="<?php echo old('title'); ?>" 
                               required
                               placeholder="Digite o título do post...">
                    </div>
                    
                    <div class="mb-3">
                        <label for="excerpt" class="form-label">Resumo</label>
                        <textarea class="form-control" 
                                  id="excerpt" 
                                  name="excerpt" 
                                  rows="3" 
                                  placeholder="Breve descrição do post..."><?php echo old('excerpt'); ?></textarea>
                        <small class="text-muted">Resumo que aparecerá no feed</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">Conteúdo <span class="text-danger">*</span></label>
                        <textarea class="form-control" 
                                  id="content" 
                                  name="content" 
                                  rows="15"><?php echo old('content'); ?></textarea>
                        <small class="text-muted">Conteúdo completo do post. Use o editor visual para formatar.</small>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card border">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Configurações</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="type" class="form-label">Tipo de Post</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="text" <?php echo (old('type') ?? 'text') === 'text' ? 'selected' : ''; ?>>Texto</option>
                                    <option value="youtube" <?php echo old('type') === 'youtube' ? 'selected' : ''; ?>>Vídeo YouTube</option>
                                    <option value="twitter" <?php echo old('type') === 'twitter' ? 'selected' : ''; ?>>Tweet Twitter</option>
                                </select>
                            </div>
                            
                            <div class="mb-3" id="externalUrlContainer" style="display: none;">
                                <label for="external_url" class="form-label">URL Externa</label>
                                <input type="url" 
                                       class="form-control" 
                                       id="external_url" 
                                       name="external_url" 
                                       value="<?php echo old('external_url'); ?>" 
                                       placeholder="https://...">
                                <small class="text-muted" id="externalUrlHelp"></small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="featured_image" class="form-label">Imagem Destacada (URL)</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="featured_image" 
                                       name="featured_image" 
                                       value="<?php echo old('featured_image'); ?>" 
                                       placeholder="https://...">
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="published" 
                                           name="published" 
                                           value="1"
                                           <?php echo old('published') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="published">
                                        Publicar imediatamente
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?php echo url('/site/manage'); ?>" class="btn btn-light">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-2"></i>
                            Salvar Post
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- TinyMCE -->
<script src="https://cdn.tiny.cloud/1/o7tsgeqi6ge25a2owg2f57segvoz4ujqxpwajukett59f8af/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const externalUrlContainer = document.getElementById('externalUrlContainer');
    const externalUrlInput = document.getElementById('external_url');
    const externalUrlHelp = document.getElementById('externalUrlHelp');
    
    function toggleExternalUrl() {
        const type = typeSelect.value;
        if (type === 'youtube' || type === 'twitter') {
            externalUrlContainer.style.display = 'block';
            externalUrlInput.required = true;
            if (type === 'youtube') {
                externalUrlHelp.textContent = 'Cole a URL completa do vídeo do YouTube';
            } else {
                externalUrlHelp.textContent = 'Cole a URL completa do tweet do Twitter/X';
            }
        } else {
            externalUrlContainer.style.display = 'none';
            externalUrlInput.required = false;
        }
    }
    
    typeSelect.addEventListener('change', toggleExternalUrl);
    toggleExternalUrl();
    
    // TinyMCE
    tinymce.init({
        selector: '#content',
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
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }',
        language: 'pt_BR'
    });
    
    // Form submit handler
    document.getElementById('postForm').addEventListener('submit', function(e) {
        const editor = tinymce.get('content');
        if (editor) {
            editor.save();
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

