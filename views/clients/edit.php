<?php
ob_start();
$title = $title ?? 'Editar Cliente';
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Editar Cliente</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/clients'); ?>">Clientes</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Editar Cliente</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?php echo url('/clients/' . $client->id); ?>" id="clientForm">
            <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
            
            <div class="row">
                <div class="col-md-12 mb-4">
                    <h5 class="mb-3">Tipo de Cliente</h5>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo" id="tipo_fisica" value="fisica" <?php echo $client->tipo === 'fisica' ? 'checked' : ''; ?> onchange="toggleTipoCliente()">
                            <label class="form-check-label" for="tipo_fisica">
                                Pessoa Física
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo" id="tipo_juridica" value="juridica" <?php echo $client->tipo === 'juridica' ? 'checked' : ''; ?> onchange="toggleTipoCliente()">
                            <label class="form-check-label" for="tipo_juridica">
                                Pessoa Jurídica
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="nome_razao_social" class="form-label">
                        <span id="label_nome">Nome Completo</span> <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="nome_razao_social" name="nome_razao_social" value="<?php echo e($client->nome_razao_social); ?>" required>
                </div>
                
                <div class="col-md-6 mb-3" id="nome_fantasia_container" style="<?php echo $client->tipo === 'fisica' ? 'display: none;' : ''; ?>">
                    <label for="nome_fantasia" class="form-label">Nome Fantasia</label>
                    <input type="text" class="form-control" id="nome_fantasia" name="nome_fantasia" value="<?php echo e($client->nome_fantasia ?? ''); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="cpf_cnpj" class="form-label">
                        <span id="label_cpf_cnpj">CPF</span>
                    </label>
                    <input type="text" class="form-control" id="cpf_cnpj" name="cpf_cnpj" value="<?php echo e($client->cpf_cnpj ?? ''); ?>" placeholder="000.000.000-00">
                </div>
            </div>

            <hr class="my-4">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo e($client->email ?? ''); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="telefone" class="form-label">Telefone</label>
                    <input type="text" class="form-control phone-mask" id="telefone" name="telefone" value="<?php echo e($client->telefone ?? ''); ?>" placeholder="(00) 0000-0000">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="celular" class="form-label">Celular</label>
                    <input type="text" class="form-control mobile-mask" id="celular" name="celular" value="<?php echo e($client->celular ?? ''); ?>" placeholder="(00) 00000-0000">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="instagram" class="form-label">Instagram</label>
                    <input type="text" class="form-control" id="instagram" name="instagram" value="<?php echo e($client->instagram ?? ''); ?>" placeholder="@usuario">
                </div>
            </div>

            <hr class="my-4">

            <h5 class="mb-3">Foto do Cliente</h5>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="text-center">
                        <div class="mb-3">
                            <?php if (!empty($client->foto)): ?>
                                <img id="foto-preview" src="<?php echo asset($client->foto); ?>" class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                                <div id="foto-placeholder" class="rounded-circle bg-primary-subtle d-inline-flex align-items-center justify-content-center" style="width: 150px; height: 150px; display: none;">
                                    <i class="ti ti-camera fs-1 text-primary"></i>
                                </div>
                            <?php else: ?>
                                <img id="foto-preview" src="/tema/assets/images/profile/user-1.jpg" class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover; display: none;">
                                <div id="foto-placeholder" class="rounded-circle bg-primary-subtle d-inline-flex align-items-center justify-content-center" style="width: 150px; height: 150px;">
                                    <i class="ti ti-camera fs-1 text-primary"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('foto-input').click()">
                                <i class="ti ti-camera me-1"></i> <?php echo !empty($client->foto) ? 'Alterar Foto' : 'Selecionar Foto'; ?>
                            </button>
                            <button type="button" class="btn btn-outline-danger ms-2" id="remove-foto-btn" <?php echo empty($client->foto) ? 'style="display: none;"' : ''; ?> onclick="removeFoto()">
                                <i class="ti ti-trash me-1"></i> Remover
                            </button>
                        </div>
                        <input type="file" id="foto-input" accept="image/*" style="display: none;" onchange="handleFotoChange(this)">
                        <input type="hidden" name="foto_base64" id="foto_base64">
                        <small class="text-muted d-block mt-2">Formatos aceitos: JPG, PNG, GIF, WEBP (máx. 5MB)</small>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <h5 class="mb-3">Endereço</h5>
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label for="endereco" class="form-label">Logradouro</label>
                    <input type="text" class="form-control" id="endereco" name="endereco" value="<?php echo e($client->endereco ?? ''); ?>" placeholder="Rua, Avenida, etc.">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="numero" class="form-label">Número</label>
                    <input type="text" class="form-control" id="numero" name="numero" value="<?php echo e($client->numero ?? ''); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="complemento" class="form-label">Complemento</label>
                    <input type="text" class="form-control" id="complemento" name="complemento" value="<?php echo e($client->complemento ?? ''); ?>" placeholder="Apto, Bloco, etc.">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="bairro" class="form-label">Bairro</label>
                    <input type="text" class="form-control" id="bairro" name="bairro" value="<?php echo e($client->bairro ?? ''); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="cidade" class="form-label">Cidade</label>
                    <input type="text" class="form-control" id="cidade" name="cidade" value="<?php echo e($client->cidade ?? ''); ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="estado" class="form-label">Estado</label>
                    <input type="text" class="form-control" id="estado" name="estado" value="<?php echo e($client->estado ?? ''); ?>" maxlength="2" placeholder="SP">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="cep" class="form-label">CEP</label>
                    <input type="text" class="form-control cep-mask" id="cep" name="cep" value="<?php echo e($client->cep ?? ''); ?>" placeholder="00000-000">
                </div>
            </div>

            <hr class="my-4">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="score" class="form-label">Score (0-100)</label>
                    <input type="number" class="form-control" id="score" name="score" min="0" max="100" value="<?php echo $client->score ?? 50; ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Tags</label>
                    <div id="tags-container" class="tags-input-container">
                        <div class="tags-list" id="tags-list"></div>
                        <input type="text" id="tags-input" class="form-control tags-input" placeholder="Digite e pressione Enter ou vírgula para adicionar">
                    </div>
                    <input type="hidden" name="tags" id="tags-hidden" value="<?php 
                        $clientTags = $client->getTags();
                        echo htmlspecialchars(implode(',', array_column($clientTags, 'name')));
                    ?>">
                    <small class="text-muted">Digite e pressione Enter ou vírgula para adicionar tags</small>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea class="form-control" id="observacoes" name="observacoes" rows="4"><?php echo e($client->observacoes ?? ''); ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end mt-4">
                <a href="<?php echo url('/clients'); ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-check me-2"></i>Atualizar Cliente
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Componente de Tags
(function() {
    const tagsList = document.getElementById('tags-list');
    const tagsInput = document.getElementById('tags-input');
    const tagsHidden = document.getElementById('tags-hidden');
    let tags = [];
    
    // Inicializa tags existentes
    const existingTags = tagsHidden.value;
    if (existingTags) {
        tags = existingTags.split(',').map(t => t.trim()).filter(t => t);
        tags.forEach(tag => {
            const tagElement = document.createElement('span');
            tagElement.className = 'badge bg-primary me-1 mb-1';
            tagElement.innerHTML = tag + ' <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.7em;" onclick="removeTag(\'' + tag.replace(/'/g, "\\'") + '\')"></button>';
            tagsList.appendChild(tagElement);
        });
    }
    
    function updateHiddenInput() {
        tagsHidden.value = tags.join(',');
    }
    
    function addTag(tagText) {
        tagText = tagText.trim();
        if (tagText && !tags.includes(tagText)) {
            tags.push(tagText);
            const tagElement = document.createElement('span');
            tagElement.className = 'badge bg-primary me-1 mb-1';
            tagElement.innerHTML = tagText + ' <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.7em;" onclick="removeTag(\'' + tagText.replace(/'/g, "\\'") + '\')"></button>';
            tagsList.appendChild(tagElement);
            updateHiddenInput();
        }
    }
    
    function removeTag(tagText) {
        tags = tags.filter(t => t !== tagText);
        tagsList.innerHTML = '';
        tags.forEach(tag => {
            const tagElement = document.createElement('span');
            tagElement.className = 'badge bg-primary me-1 mb-1';
            tagElement.innerHTML = tag + ' <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.7em;" onclick="removeTag(\'' + tag.replace(/'/g, "\\'") + '\')"></button>';
            tagsList.appendChild(tagElement);
        });
        updateHiddenInput();
    }
    
    window.removeTag = removeTag;
    
    tagsInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            const value = this.value.trim();
            if (value) {
                addTag(value);
                this.value = '';
            }
        }
    });
    
    tagsInput.addEventListener('blur', function() {
        const value = this.value.trim();
        if (value) {
            addTag(value);
            this.value = '';
        }
    });
})();

function toggleTipoCliente() {
    const tipo = document.querySelector('input[name="tipo"]:checked').value;
    const nomeLabel = document.getElementById('label_nome');
    const cpfCnpjLabel = document.getElementById('label_cpf_cnpj');
    const nomeFantasiaContainer = document.getElementById('nome_fantasia_container');
    const cpfCnpjInput = document.getElementById('cpf_cnpj');
    
    if (tipo === 'juridica') {
        nomeLabel.textContent = 'Razão Social';
        cpfCnpjLabel.textContent = 'CNPJ';
        cpfCnpjInput.placeholder = '00.000.000/0000-00';
        nomeFantasiaContainer.style.display = 'block';
    } else {
        nomeLabel.textContent = 'Nome Completo';
        cpfCnpjLabel.textContent = 'CPF';
        cpfCnpjInput.placeholder = '000.000.000-00';
        nomeFantasiaContainer.style.display = 'none';
    }
}

// Inicializa ao carregar
toggleTipoCliente();

// Máscaras
document.getElementById('cpf_cnpj')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    const tipo = document.querySelector('input[name="tipo"]:checked').value;
    
    if (tipo === 'juridica') {
        if (value.length <= 14) {
            value = value.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2}).*/, '$1.$2.$3/$4-$5');
        }
    } else {
        if (value.length <= 11) {
            value = value.replace(/^(\d{3})(\d{3})(\d{3})(\d{2}).*/, '$1.$2.$3-$4');
        }
    }
    e.target.value = value;
});

document.getElementById('telefone')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 10) {
        value = value.replace(/^(\d{2})(\d{4})(\d{4}).*/, '($1) $2-$3');
    }
    e.target.value = value;
});

document.getElementById('celular')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 11) {
        value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
    }
    e.target.value = value;
});

document.getElementById('cep')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 8) {
        value = value.replace(/^(\d{5})(\d{3}).*/, '$1-$2');
    }
    e.target.value = value;
});

// Manipulação de foto
function handleFotoChange(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validação de tamanho (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('Arquivo muito grande. Tamanho máximo: 5MB');
            input.value = '';
            return;
        }
        
        // Validação de tipo
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            alert('Formato inválido. Use JPG, PNG, GIF ou WEBP');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const img = document.getElementById('foto-preview');
            const placeholder = document.getElementById('foto-placeholder');
            const removeBtn = document.getElementById('remove-foto-btn');
            const base64Input = document.getElementById('foto_base64');
            
            img.src = e.target.result;
            img.style.display = 'block';
            placeholder.style.display = 'none';
            removeBtn.style.display = 'inline-block';
            base64Input.value = e.target.result;
        };
        
        reader.readAsDataURL(file);
    }
}

function removeFoto() {
    const img = document.getElementById('foto-preview');
    const placeholder = document.getElementById('foto-placeholder');
    const removeBtn = document.getElementById('remove-foto-btn');
    const base64Input = document.getElementById('foto_base64');
    const fileInput = document.getElementById('foto-input');
    
    img.style.display = 'none';
    placeholder.style.display = 'inline-flex';
    removeBtn.style.display = 'none';
    base64Input.value = '';
    fileInput.value = '';
}
</script>

<?php
$content = ob_get_clean();

// Scripts específicos da página
ob_start();
?>
<script src="<?php echo asset('tema/assets/libs/inputmask/dist/jquery.inputmask.min.js'); ?>"></script>
<script>
// Máscaras de Input
$(document).ready(function() {
    // Máscara de telefone fixo
    $('.phone-mask').inputmask('(99) 9999-9999', {
        clearMaskOnLostFocus: false,
        showMaskOnHover: false,
        removeMaskOnSubmit: true
    });
    
    // Máscara de celular
    $('.mobile-mask').inputmask('(99) 99999-9999', {
        clearMaskOnLostFocus: false,
        showMaskOnHover: false,
        removeMaskOnSubmit: true
    });
    
    // Máscara de CEP
    $('.cep-mask').inputmask('99999-999', {
        clearMaskOnLostFocus: false,
        showMaskOnHover: false,
        removeMaskOnSubmit: true
    });
    
    // Máscara dinâmica para CPF/CNPJ
    function updateCpfCnpjMask() {
        const tipo = $('input[name="tipo"]:checked').val();
        const input = $('#cpf_cnpj');
        
        // Remove máscara anterior
        input.inputmask('remove');
        
        if (tipo === 'fisica') {
            // CPF: 000.000.000-00
            input.inputmask('999.999.999-99', {
                clearMaskOnLostFocus: false,
                showMaskOnHover: false,
                removeMaskOnSubmit: true
            });
            input.attr('placeholder', '000.000.000-00');
        } else {
            // CNPJ: 00.000.000/0000-00
            input.inputmask('99.999.999/9999-99', {
                clearMaskOnLostFocus: false,
                showMaskOnHover: false,
                removeMaskOnSubmit: true
            });
            input.attr('placeholder', '00.000.000/0000-00');
        }
    }
    
    // Atualiza máscara ao mudar tipo
    $('input[name="tipo"]').on('change', updateCpfCnpjMask);
    
    // Inicializa com a máscara correta baseada no tipo atual
    updateCpfCnpjMask();
    
    // Remove máscaras antes de enviar o formulário
    $('#clientForm').on('submit', function(e) {
        // Remove máscaras dos campos para enviar apenas números
        $('.phone-mask, .mobile-mask, .cep-mask').each(function() {
            const unmaskedValue = $(this).inputmask('unmaskedvalue');
            if (unmaskedValue) {
                $(this).val(unmaskedValue);
            }
        });
        
        // Remove máscara do CPF/CNPJ
        const cpfCnpj = $('#cpf_cnpj');
        const unmaskedCpfCnpj = cpfCnpj.inputmask('unmaskedvalue');
        if (unmaskedCpfCnpj) {
            cpfCnpj.val(unmaskedCpfCnpj);
        }
        
        console.log('Formulário enviando - dados sem máscaras');
    });
});
</script>
<?php
$scripts = ob_get_clean();

include base_path('views/layouts/app.php');
?>

