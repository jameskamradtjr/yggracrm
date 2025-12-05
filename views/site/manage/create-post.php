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
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label for="title" class="form-label mb-0">Título <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#aiGenerateModal">
                                <i class="ti ti-sparkles me-1"></i>
                                Criar com IA
                            </button>
                        </div>
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
                                <label for="featured_image_file" class="form-label">Imagem Destacada</label>
                                <input type="file" 
                                       class="form-control" 
                                       id="featured_image_file" 
                                       name="featured_image_file" 
                                       accept="image/*"
                                       onchange="handleImageUpload(this)">
                                <small class="text-muted">Faça upload da imagem destacada (PNG, JPG, GIF, WEBP)</small>
                                <div class="mt-2">
                                    <img id="featured_image_preview" 
                                         src="<?php echo old('featured_image'); ?>" 
                                         alt="Preview" 
                                         style="max-width: 100%; max-height: 200px; border-radius: 8px; <?php echo old('featured_image') ? '' : 'display: none;'; ?>">
                                </div>
                                <input type="hidden" id="featured_image_base64" name="featured_image_base64" value="">
                                <input type="hidden" id="featured_image" name="featured_image" value="<?php echo old('featured_image'); ?>">
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

<!-- Modal Criar com IA -->
<div class="modal fade" id="aiGenerateModal" tabindex="-1" aria-labelledby="aiGenerateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="aiGenerateModalLabel">
                    <i class="ti ti-sparkles me-2"></i>
                    Criar Post com IA
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="aiGenerateForm">
                    <div class="mb-3">
                        <label for="ai_keywords" class="form-label">
                            Palavras-chave <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="ai_keywords" 
                               name="keywords" 
                               required
                               placeholder="Ex: marketing digital, SEO, conteúdo, blog">
                        <small class="text-muted">Separe as palavras-chave por vírgula</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ai_tone" class="form-label">
                            Tom de Voz <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="ai_tone" name="tone" required>
                            <option value="profissional">Profissional</option>
                            <option value="descontraído">Descontraído</option>
                            <option value="técnico">Técnico</option>
                            <option value="conversacional">Conversacional</option>
                            <option value="formal">Formal</option>
                            <option value="amigável">Amigável</option>
                            <option value="persuasivo">Persuasivo</option>
                            <option value="educativo">Educativo</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ai_reference_links" class="form-label">
                            Links de Referência
                        </label>
                        <textarea class="form-control" 
                                  id="ai_reference_links" 
                                  name="reference_links" 
                                  rows="4"
                                  placeholder="Cole os links de referência, um por linha&#10;Ex:&#10;https://exemplo.com/artigo1&#10;https://exemplo.com/artigo2"></textarea>
                        <small class="text-muted">Um link por linha (opcional)</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="ti ti-info-circle me-2"></i>
                        <strong>Como funciona:</strong> A IA irá criar um post otimizado para SEO com base nas palavras-chave, tom de voz e links de referência fornecidos. O conteúdo será preenchido automaticamente no editor.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGenerateAI">
                    <i class="ti ti-sparkles me-2"></i>
                    Gerar Post
                </button>
            </div>
        </div>
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
    
    // Preview e conversão para base64 (igual ao /settings)
    function handleImageUpload(input) {
        const preview = document.getElementById('featured_image_preview');
        const base64Input = document.getElementById('featured_image_base64');
        const file = input.files[0];
        
        if (file) {
            // Validar tamanho (máx 5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('Arquivo muito grande! Tamanho máximo: 5MB');
                input.value = '';
                return;
            }
            
            // Validar tipo
            const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('Tipo de arquivo não permitido! Use PNG, JPG, GIF ou WEBP');
                input.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const base64 = e.target.result;
                preview.src = base64;
                preview.style.display = 'block';
                base64Input.value = base64;
            };
            reader.onerror = function(error) {
                console.error('Erro ao ler arquivo:', error);
                alert('Erro ao processar arquivo');
            };
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
            base64Input.value = '';
        }
    }
    
    // Gerar post com IA
    document.getElementById('btnGenerateAI').addEventListener('click', function() {
        const form = document.getElementById('aiGenerateForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        const keywords = document.getElementById('ai_keywords').value.trim();
        const tone = document.getElementById('ai_tone').value;
        const referenceLinksText = document.getElementById('ai_reference_links').value.trim();
        
        if (!keywords) {
            alert('Por favor, informe as palavras-chave.');
            return;
        }
        
        // Processa links de referência
        const referenceLinks = referenceLinksText
            .split('\n')
            .map(link => link.trim())
            .filter(link => link && (link.startsWith('http://') || link.startsWith('https://')));
        
        // Desabilita botão e mostra loading
        const btn = this;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Gerando...';
        
        // Chama API
        fetch('<?php echo url('/site/manage/posts/generate-ai'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '<?php echo csrf_token(); ?>'
            },
            body: JSON.stringify({
                keywords: keywords,
                tone: tone,
                reference_links: referenceLinks
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Preenche título
                document.getElementById('title').value = data.data.title || '';
                
                // Preenche resumo
                if (data.data.excerpt) {
                    document.getElementById('excerpt').value = data.data.excerpt;
                }
                
                // Preenche conteúdo no TinyMCE
                const editor = tinymce.get('content');
                if (editor) {
                    editor.setContent(data.data.content || '');
                } else {
                    document.getElementById('content').value = data.data.content || '';
                }
                
                // Fecha modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('aiGenerateModal'));
                if (modal) {
                    modal.hide();
                }
                
                alert('Post gerado com sucesso! Revise o conteúdo e salve quando estiver pronto.');
            } else {
                alert('Erro ao gerar post: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao gerar post. Verifique sua conexão e tente novamente.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

