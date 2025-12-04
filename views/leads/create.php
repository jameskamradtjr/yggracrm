<?php
$title = 'Cadastrar Lead';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="card-title fw-semibold mb-2">Cadastrar Lead Manualmente</h4>
                        <p class="card-subtitle mb-0">Preencha os dados do lead para cadastrar no sistema</p>
                    </div>
                    <a href="<?php echo url('/leads'); ?>" class="btn btn-light">
                        <i class="ti ti-arrow-left me-2"></i>
                        Voltar
                    </a>
                </div>

                <form action="<?php echo url('/leads'); ?>" method="POST" id="formCreateLead">
                    <?php echo csrf_field(); ?>
                    
                    <div class="row">
                        <!-- Informações Básicas -->
                        <div class="col-md-12">
                            <h5 class="mb-3">Informações Básicas</h5>
                            
                            <div class="mb-3">
                                <label for="client_id" class="form-label">Cliente Existente (Opcional)</label>
                                <?php 
                                $id = 'client_id';
                                $name = 'client_id';
                                $placeholder = 'Digite para buscar cliente...';
                                $selected = old('client_id') ?? '';
                                include base_path('views/components/tom-select-client.php'); 
                                ?>
                                <small class="text-muted">Se selecionar um cliente, os campos abaixo serão preenchidos automaticamente. Se não selecionar, um novo cliente será criado automaticamente.</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nome" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="nome" 
                                           name="nome" 
                                           value="<?php echo old('nome'); ?>" 
                                           required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo old('email'); ?>" 
                                           required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="telefone" class="form-label">Telefone <span class="text-danger">*</span></label>
                                    <input type="tel" 
                                           class="form-control" 
                                           id="telefone" 
                                           name="telefone" 
                                           value="<?php echo old('telefone'); ?>" 
                                           placeholder="(00) 00000-0000"
                                           required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="instagram" class="form-label">Instagram</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="instagram" 
                                           name="instagram" 
                                           value="<?php echo old('instagram'); ?>" 
                                           placeholder="@seuinstagram">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="valor_oportunidade" class="form-label">Valor da Oportunidade (R$)</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="valor_oportunidade" 
                                           name="valor_oportunidade" 
                                           value="<?php echo old('valor_oportunidade'); ?>" 
                                           step="0.01"
                                           min="0"
                                           placeholder="0.00">
                                    <small class="text-muted">Valor estimado da oportunidade de negócio</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="tags" class="form-label">Tags</label>
                                    <div class="tags-input-container">
                                        <div id="tags-display" class="tags-display mb-2"></div>
                                        <input type="text" 
                                               class="form-control" 
                                               id="tags-input" 
                                               placeholder="Digite uma tag e pressione Enter ou vírgula">
                                        <input type="hidden" id="tags-hidden" name="tags" value="">
                                        <small class="text-muted">Digite tags separadas por Enter ou vírgula</small>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="objetivo" class="form-label">Objetivo Principal</label>
                                <textarea class="form-control" 
                                          id="objetivo" 
                                          name="objetivo" 
                                          rows="4" 
                                          placeholder="Descreva o objetivo principal..."><?php echo old('objetivo'); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?php echo url('/leads'); ?>" class="btn btn-light">
                                    Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-device-floppy me-2"></i>
                                    Cadastrar Lead
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.tags-display {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    min-height: 40px;
    padding: 8px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    background-color: #f8f9fa;
}

.tag-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background-color: #0dcaf0;
    color: white;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}

.tag-item .tag-remove {
    cursor: pointer;
    font-weight: bold;
    opacity: 0.8;
    transition: opacity 0.2s;
}

.tag-item .tag-remove:hover {
    opacity: 1;
}
</style>

<script>
let tags = [];

// Função para buscar dados do cliente e preencher campos
function buscarDadosCliente(clientId) {
    if (!clientId) {
        // Limpa e habilita os campos
        document.getElementById('nome').value = '';
        document.getElementById('email').value = '';
        document.getElementById('telefone').value = '';
        
        document.getElementById('nome').readOnly = false;
        document.getElementById('email').readOnly = false;
        document.getElementById('telefone').readOnly = false;
        return;
    }
    
    // Busca dados do cliente via AJAX
    fetch('<?php echo url('/api/clients'); ?>/' + clientId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.client) {
                const client = data.client;
                document.getElementById('nome').value = client.nome_razao_social || '';
                document.getElementById('email').value = client.email || '';
                document.getElementById('telefone').value = client.telefone || client.celular || '';
                
                // Desabilita os campos (mas mantém required para validação)
                document.getElementById('nome').readOnly = true;
                document.getElementById('email').readOnly = true;
                document.getElementById('telefone').readOnly = true;
            }
        })
        .catch(error => {
            console.error('Erro ao buscar dados do cliente:', error);
            // Tenta usar dados do Tom Select se disponíveis
            const clientSelect = document.getElementById('client_id');
            if (clientSelect && clientSelect.tomselect) {
                const selectedOption = clientSelect.tomselect.options[clientId];
                if (selectedOption) {
                    document.getElementById('nome').value = selectedOption.nome_razao_social || selectedOption.text || '';
                    document.getElementById('email').value = selectedOption.email || '';
                    document.getElementById('telefone').value = selectedOption.telefone || '';
                    
                    document.getElementById('nome').readOnly = true;
                    document.getElementById('email').readOnly = true;
                    document.getElementById('telefone').readOnly = true;
                }
            }
        });
}

function addTag(tagName) {
    tagName = tagName.trim();
    if (!tagName || tags.includes(tagName)) {
        return;
    }
    
    tags.push(tagName);
    updateTagsDisplay();
    updateHiddenInput();
    document.getElementById('tags-input').value = '';
}

function removeTag(tagName) {
    tags = tags.filter(t => t !== tagName);
    updateTagsDisplay();
    updateHiddenInput();
}

function removeTagByIndex(index) {
    tags.splice(index, 1);
    updateTagsDisplay();
    updateHiddenInput();
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
}

function updateHiddenInput() {
    document.getElementById('tags-hidden').value = tags.join(',');
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
    
    // Atualiza o campo hidden antes de submeter
    document.getElementById('formCreateLead').addEventListener('submit', function() {
        updateHiddenInput();
    });
    
    // Listener para quando o Tom Select for inicializado
    const clientSelect = document.getElementById('client_id');
    if (clientSelect) {
        clientSelect.addEventListener('tomselect:initialized', function(e) {
            const tomSelect = e.detail.tomSelect;
            
            // Quando um cliente é selecionado
            tomSelect.on('change', function(value) {
                if (!value) {
                    // Cliente desmarcado - limpa campos
                    document.getElementById('nome').value = '';
                    document.getElementById('email').value = '';
                    document.getElementById('telefone').value = '';
                    document.getElementById('nome').readOnly = false;
                    document.getElementById('email').readOnly = false;
                    document.getElementById('telefone').readOnly = false;
                    return;
                }
                
                // Tenta usar dados do Tom Select primeiro
                const selectedOption = tomSelect.options[value];
                if (selectedOption && selectedOption.email) {
                    // Usa dados já carregados pelo Tom Select
                    document.getElementById('nome').value = selectedOption.nome_razao_social || selectedOption.text || '';
                    document.getElementById('email').value = selectedOption.email || '';
                    document.getElementById('telefone').value = selectedOption.telefone || '';
                    
                    document.getElementById('nome').readOnly = true;
                    document.getElementById('email').readOnly = true;
                    document.getElementById('telefone').readOnly = true;
                } else {
                    // Se não tiver dados completos, busca via API
                    buscarDadosCliente(value);
                }
            });
        });
    }
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
