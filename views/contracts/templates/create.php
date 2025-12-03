<?php
ob_start();
$title = $title ?? 'Novo Template';
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Novo Template de Contrato</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/contracts/templates'); ?>">Templates</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Novo Template</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="<?php echo url('/contracts/templates'); ?>" id="templateForm">
                    <?php echo csrf_field(); ?>
                    
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome do Template <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="conteudo" class="form-label">Conteúdo do Template <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="conteudo" name="conteudo" rows="20"></textarea>
                        <small class="text-muted">Use variáveis como {{nome_cliente}}, {{documento_cliente}}, etc.</small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" checked>
                            <label class="form-check-label" for="ativo">
                                Template ativo
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?php echo url('/contracts/templates'); ?>" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Criar Template</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Variáveis Disponíveis</h5>
                <div class="list-group">
                    <?php foreach ($variaveis as $key => $label): ?>
                        <button type="button" class="list-group-item list-group-item-action" onclick="inserirVariavel('<?php echo $key; ?>')">
                            <strong>{{<?php echo $key; ?>}}</strong><br>
                            <small class="text-muted"><?php echo e($label); ?></small>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo asset('tema/assets/libs/tinymce/tinymce.min.js'); ?>"></script>
<script>
tinymce.init({
    selector: '#conteudo',
    height: 600,
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

function inserirVariavel(variavel) {
    const editor = tinymce.get('conteudo');
    if (editor) {
        editor.insertContent('{{' + variavel + '}}');
    } else {
        const textarea = document.getElementById('conteudo');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        const before = text.substring(0, start);
        const after = text.substring(end, text.length);
        textarea.value = before + '{{' + variavel + '}}' + after;
        textarea.selectionStart = textarea.selectionEnd = start + variavel.length + 4;
        textarea.focus();
    }
}

// Validação do formulário antes de submeter
document.getElementById('templateForm').addEventListener('submit', function(e) {
    // Sincroniza o conteúdo do TinyMCE com o textarea
    const editor = tinymce.get('conteudo');
    if (editor) {
        editor.save();
    }
    
    // Valida se o conteúdo não está vazio
    const conteudo = document.getElementById('conteudo').value.trim();
    if (!conteudo || conteudo === '') {
        e.preventDefault();
        alert('❌ Por favor, preencha o conteúdo do template.');
        
        // Foca no editor TinyMCE
        if (editor) {
            editor.focus();
        }
        
        return false;
    }
});
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

