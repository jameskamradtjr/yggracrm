<?php
$title = 'Editar Template de Email';

// Inicia captura do conteúdo
ob_start();

$variables = $template->getVariables();
$variablesStr = !empty($variables) ? implode(', ', $variables) : '';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="card-title fw-semibold mb-2">Editar Template de Email</h4>
                        <p class="card-subtitle mb-0">Atualize as informações do template</p>
                    </div>
                    <a href="<?php echo url('/settings?tab=templates'); ?>" class="btn btn-light">
                        <i class="ti ti-arrow-left me-2"></i>
                        Voltar
                    </a>
                </div>

                <form action="<?php echo url('/settings/templates/' . $template->id); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nome do Template <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   value="<?php echo e(old('name', $template->name)); ?>" 
                                   required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="slug" class="form-label">Slug <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="slug" 
                                   name="slug" 
                                   value="<?php echo e(old('slug', $template->slug)); ?>" 
                                   required>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="subject" class="form-label">Assunto <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="subject" 
                                   name="subject" 
                                   value="<?php echo e(old('subject', $template->subject)); ?>" 
                                   required>
                            <small class="text-muted">Use {{variavel}} para variáveis dinâmicas</small>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="body" class="form-label">Corpo do Email (HTML) <span class="text-danger">*</span></label>
                            <textarea class="form-control" 
                                      id="body" 
                                      name="body" 
                                      rows="15" 
                                      required><?php echo e(old('body', $template->body)); ?></textarea>
                            <small class="text-muted">
                                Use o editor visual para formatar o email. Variáveis: {{variavel}}
                            </small>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="variables" class="form-label">Variáveis Disponíveis</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="variables" 
                                   name="variables" 
                                   value="<?php echo e(old('variables', $variablesStr)); ?>" 
                                   placeholder="nome, email, senha">
                            <small class="text-muted">Separe as variáveis por vírgula (sem espaços)</small>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_active" 
                                       name="is_active" 
                                       <?php echo $template->is_active ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    Template ativo
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="<?php echo url('/settings?tab=templates'); ?>" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Captura o conteúdo
$content = ob_get_clean();

// Scripts adicionais
$scripts = <<<'SCRIPTS'
<!-- TinyMCE -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<script>
tinymce.init({
    selector: '#body',
    height: 500,
    menubar: true,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount'
    ],
    toolbar: 'undo redo | blocks | ' +
        'bold italic forecolor backcolor | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | ' +
        'removeformat | link image | code | help',
    content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
    language: 'pt_BR',
    language_url: 'https://cdn.tiny.cloud/1/no-api-key/tinymce/6/langs/pt_BR.js',
    promotion: false,
    branding: false,
    setup: function(editor) {
        // Adiciona botão para inserir variáveis
        editor.ui.registry.addButton('variablebutton', {
            text: 'Variável',
            tooltip: 'Inserir variável',
            onAction: function() {
                const variable = prompt('Digite o nome da variável (ex: nome, email):');
                if (variable) {
                    editor.insertContent('{{' + variable + '}}');
                }
            }
        });
    },
    toolbar: 'undo redo | blocks | ' +
        'bold italic forecolor backcolor | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | ' +
        'removeformat | link image | variablebutton | code | help'
});
</script>
SCRIPTS;

// Inclui o layout
include base_path('views/layouts/app.php');
?>

