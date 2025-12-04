<?php
$title = 'Novo Conhecimento';

ob_start();
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Novo Conhecimento</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/knowledge-base'); ?>">Base de Conhecimento</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Novo Conhecimento</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?php echo url('/knowledge-base'); ?>" id="knowledgeForm">
            <?php echo csrf_field(); ?>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               id="titulo" 
                               name="titulo" 
                               value="<?php echo old('titulo'); ?>" 
                               required
                               placeholder="Digite o título do conhecimento...">
                    </div>
                    
                    <div class="mb-3">
                        <label for="resumo" class="form-label">Resumo</label>
                        <textarea class="form-control" 
                                  id="resumo" 
                                  name="resumo" 
                                  rows="3" 
                                  placeholder="Breve descrição do conhecimento..."><?php echo old('resumo'); ?></textarea>
                        <small class="text-muted">Resumo que aparecerá na listagem</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="conteudo" class="form-label">Conteúdo <span class="text-danger">*</span></label>
                        <textarea class="form-control" 
                                  id="conteudo" 
                                  name="conteudo" 
                                  rows="15"><?php echo old('conteudo'); ?></textarea>
                        <small class="text-muted">Conteúdo completo do conhecimento. Use o editor visual para formatar.</small>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card border">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Configurações</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="client_id" class="form-label">Cliente (Opcional)</label>
                                <?php 
                                $id = 'client_id';
                                $name = 'client_id';
                                $placeholder = 'Digite para buscar cliente...';
                                $selected = old('client_id') ?? '';
                                include base_path('views/components/tom-select-client.php'); 
                                ?>
                                <small class="text-muted">Selecione um cliente se este conhecimento for específico para ele</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="categoria" class="form-label">Categoria</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="categoria" 
                                       name="categoria" 
                                       value="<?php echo old('categoria'); ?>" 
                                       placeholder="Ex: Processo, Documentação, Tutorial...">
                                <small class="text-muted">Categoria para organização</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="rascunho" <?php echo (old('status') ?? 'rascunho') === 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                                    <option value="publicado" <?php echo old('status') === 'publicado' ? 'selected' : ''; ?>>Publicado</option>
                                    <option value="arquivado" <?php echo old('status') === 'arquivado' ? 'selected' : ''; ?>>Arquivado</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="tags_input" class="form-label">Tags</label>
                                <div class="tags-input-container">
                                    <div id="tags-display" class="tags-display mb-2"></div>
                                    <input type="text" 
                                           class="form-control" 
                                           id="tags-input" 
                                           name="tags_input"
                                           placeholder="Digite uma tag e pressione Enter ou vírgula">
                                    <small class="text-muted">Digite tags separadas por Enter ou vírgula</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?php echo url('/knowledge-base'); ?>" class="btn btn-light">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-2"></i>
                            Salvar Conhecimento
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- TinyMCE -->
<script src="https://cdn.tiny.cloud/1/o7tsgeqi6ge25a2owg2f57segvoz4ujqxpwajukett59f8af/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<style>
.tags-display {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    min-height: 30px;
}

.tag-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background-color: #e7f3ff;
    color: #0066cc;
    padding: 4px 10px;
    border-radius: 16px;
    font-size: 0.875rem;
    font-weight: 500;
}

.tag-remove {
    cursor: pointer;
    font-weight: bold;
    color: #0066cc;
    font-size: 1.1rem;
    line-height: 1;
}

.tag-remove:hover {
    color: #dc3545;
}
</style>

<script>
// Tags management
let tags = [];

function addTag(tagName) {
    tagName = tagName.trim();
    if (!tagName || tags.includes(tagName)) {
        return;
    }
    
    tags.push(tagName);
    updateTagsDisplay();
    document.getElementById('tags-input').value = '';
}

function removeTagByIndex(index) {
    tags.splice(index, 1);
    updateTagsDisplay();
}

function updateTagsDisplay() {
    const display = document.getElementById('tags-display');
    display.innerHTML = '';
    
    tags.forEach((tag, index) => {
        const tagElement = document.createElement('span');
        tagElement.className = 'tag-item';
        const escapedTag = tag.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        tagElement.innerHTML = `
            ${escapedTag}
            <span class="tag-remove" onclick="removeTagByIndex(${index})" title="Remover tag">×</span>
        `;
        display.appendChild(tagElement);
    });
    
    // Atualiza campo hidden para envio
    const hiddenInput = document.getElementById('tags-input');
    if (hiddenInput) {
        hiddenInput.value = tags.join(',');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const tagsInput = document.getElementById('tags-input');
    
    tagsInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const value = this.value.trim();
            if (value) {
                addTag(value);
            }
        }
    });
    
    tagsInput.addEventListener('keyup', function(e) {
        if (e.key === ',') {
            const value = this.value.trim();
            if (value && value.endsWith(',')) {
                const tagValue = value.slice(0, -1).trim();
                if (tagValue) {
                    addTag(tagValue);
                }
            }
        }
    });
    
    // TinyMCE
    tinymce.init({
        selector: '#conteudo',
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
    document.getElementById('knowledgeForm').addEventListener('submit', function(e) {
        const editor = tinymce.get('conteudo');
        if (editor) {
            editor.save();
        }
        updateTagsDisplay();
    });
});
</script>

<?php
// Inclui scripts do Tom Select
include base_path('views/components/tom-select-scripts.php');
?>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

