<?php
$title = $title ?? 'Editar Quiz';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="card-title mb-0">
                        <i class="ti ti-clipboard-list me-2"></i>
                        Editar Quiz: <?php echo e($quiz->name); ?>
                    </h4>
                    <div>
                        <a href="<?php echo $quiz->getPublicUrl(); ?>" target="_blank" class="btn btn-success me-2">
                            <i class="ti ti-external-link me-2"></i>
                            Ver Quiz Público
                        </a>
                        <a href="<?php echo url('/quizzes'); ?>" class="btn btn-secondary">
                            <i class="ti ti-arrow-left me-2"></i>
                            Voltar
                        </a>
                    </div>
                </div>

                <!-- Formulário de Configuração do Quiz -->
                <form method="POST" action="<?php echo url('/quizzes/' . $quiz->id); ?>" class="mb-4">
                    <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Nome do Quiz *</label>
                            <input type="text" name="name" class="form-control" value="<?php echo e($quiz->name); ?>" required>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea name="description" class="form-control" rows="2"><?php echo e($quiz->description ?? ''); ?></textarea>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tag Padrão</label>
                            <?php 
                            $defaultTagName = '';
                            if ($quiz->default_tag_id) {
                                $defaultTag = \App\Models\Tag::find($quiz->default_tag_id);
                                $defaultTagName = $defaultTag ? $defaultTag->name : '';
                            }
                            ?>
                            <input type="text" name="default_tag_name" class="form-control" value="<?php echo e($defaultTagName); ?>" placeholder="Digite o nome da tag...">
                            <small class="text-muted">Tag que será aplicada automaticamente aos leads deste quiz (será criada se não existir)</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input type="hidden" name="active" value="0">
                                <input type="checkbox" name="active" class="form-check-input" id="active" value="1" <?php echo $quiz->active ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="active">
                                    Quiz Ativo
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mb-3">Personalização de Cores</h5>
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Cor Principal</label>
                            <input type="color" name="primary_color" class="form-control form-control-color" value="<?php echo e($quiz->primary_color); ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Cor Secundária</label>
                            <input type="color" name="secondary_color" class="form-control form-control-color" value="<?php echo e($quiz->secondary_color); ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Cor do Texto</label>
                            <input type="color" name="text_color" class="form-control form-control-color" value="<?php echo e($quiz->text_color); ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Cor de Fundo</label>
                            <input type="color" name="background_color" class="form-control form-control-color" value="<?php echo e($quiz->background_color); ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Cor do Botão</label>
                            <input type="color" name="button_color" class="form-control form-control-color" value="<?php echo e($quiz->button_color); ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Cor Texto Botão</label>
                            <input type="color" name="button_text_color" class="form-control form-control-color" value="<?php echo e($quiz->button_text_color); ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Cor Hover Botão</label>
                            <input type="color" name="button_hover_color" class="form-control form-control-color" value="<?php echo e(!empty($quiz->button_hover_color) ? $quiz->button_hover_color : '#0056b3'); ?>">
                            <small class="text-muted">Cor quando o mouse passa sobre o botão</small>
                        </div>
                    </div>
                    
                    <h5 class="mb-3 mt-4">Mensagens</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mensagem de Boas-vindas</label>
                            <textarea name="welcome_message" class="form-control" rows="3"><?php echo e($quiz->welcome_message ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mensagem de Conclusão</label>
                            <textarea name="completion_message" class="form-control" rows="3"><?php echo e($quiz->completion_message ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">URL do Logo</label>
                            <input type="url" name="logo_url" class="form-control" value="<?php echo e($quiz->logo_url ?? ''); ?>">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-2"></i>
                        Salvar Configurações
                    </button>
                </form>

                <hr class="my-4">

                <!-- Gerenciamento de Steps -->
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h5 class="mb-0">Etapas do Quiz</h5>
                    <button type="button" class="btn btn-primary" onclick="addStep()">
                        <i class="ti ti-plus me-2"></i>
                        Adicionar Etapa
                    </button>
                </div>

                <div id="steps-container">
                    <?php if (empty($steps)): ?>
                        <div class="alert alert-info">
                            <i class="ti ti-info-circle me-2"></i>
                            Nenhuma etapa adicionada ainda. Clique em "Adicionar Etapa" para começar.
                        </div>
                    <?php else: ?>
                        <?php foreach ($steps as $step): ?>
                            <?php include __DIR__ . '/_step_item.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Adicionar/Editar Step -->
<div class="modal fade" id="stepModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stepModalTitle">Adicionar Etapa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="stepForm">
                    <input type="hidden" id="step_id" name="step_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Título da Etapa *</label>
                        <input type="text" id="step_title" name="title" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição/Instruções</label>
                        <textarea id="step_description" name="description" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tipo de Campo *</label>
                            <select id="step_type" name="type" class="form-select" required onchange="toggleOptions()">
                                <option value="text">Texto</option>
                                <option value="textarea">Texto Longo</option>
                                <option value="email">E-mail</option>
                                <option value="phone">Telefone</option>
                                <option value="number">Número</option>
                                <option value="select">Seleção (Dropdown)</option>
                                <option value="radio">Opção Única (Radio)</option>
                                <option value="checkbox">Múltipla Escolha (Checkbox)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Pontos da Etapa</label>
                            <input type="number" id="step_points" name="points" class="form-control" value="0" min="0">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Ordem</label>
                            <input type="number" id="step_order" name="order" class="form-control" value="0" min="0">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nome do Campo (para mapear resposta)</label>
                        <input type="text" id="step_field_name" name="field_name" class="form-control" placeholder="Ex: nome, email, telefone">
                        <small class="text-muted">Use nomes como: nome, email, telefone, ramo, etc.</small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" id="step_required" name="required" class="form-check-input" checked>
                            <label class="form-check-label" for="step_required">
                                Campo obrigatório
                            </label>
                        </div>
                    </div>
                    
                    <div id="options-container" style="display: none;">
                        <hr>
                        <h6>Opções de Resposta</h6>
                        <div id="options-list"></div>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="addOption()">
                            <i class="ti ti-plus me-1"></i>
                            Adicionar Opção
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveStep()">Salvar Etapa</button>
            </div>
        </div>
    </div>
</div>

<script>
const quizId = <?php echo $quiz->id; ?>;
let currentStepId = null;
let stepCounter = 0;

function addStep() {
    currentStepId = null;
    document.getElementById('stepModalTitle').textContent = 'Adicionar Etapa';
    document.getElementById('stepForm').reset();
    document.getElementById('step_id').value = '';
    document.getElementById('step_order').value = stepCounter++;
    document.getElementById('options-container').style.display = 'none';
    document.getElementById('options-list').innerHTML = '';
    new bootstrap.Modal(document.getElementById('stepModal')).show();
}

function editStep(stepId) {
    // Busca dados do step via AJAX
    fetch(`<?php echo url('/quizzes/' . $quiz->id . '/steps'); ?>?step_id=${stepId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const step = data.step;
                currentStepId = step.id;
                document.getElementById('stepModalTitle').textContent = 'Editar Etapa';
                document.getElementById('step_id').value = step.id;
                document.getElementById('step_title').value = step.title;
                document.getElementById('step_description').value = step.description || '';
                document.getElementById('step_type').value = step.type;
                document.getElementById('step_points').value = step.points || 0;
                document.getElementById('step_order').value = step.order || 0;
                document.getElementById('step_field_name').value = step.field_name || '';
                document.getElementById('step_required').checked = step.required == 1;
                
                toggleOptions();
                
                // Carrega opções se houver
                if (data.options && data.options.length > 0) {
                    document.getElementById('options-list').innerHTML = '';
                    data.options.forEach((option, index) => {
                        addOption(option);
                    });
                }
                
                new bootstrap.Modal(document.getElementById('stepModal')).show();
            }
        });
}

function toggleOptions() {
    const type = document.getElementById('step_type').value;
    const container = document.getElementById('options-container');
    
    if (['select', 'radio', 'checkbox'].includes(type)) {
        container.style.display = 'block';
        if (container.querySelectorAll('.option-item').length === 0) {
            addOption();
        }
    } else {
        container.style.display = 'none';
    }
}

function addOption(optionData = null) {
    const container = document.getElementById('options-list');
    const optionId = optionData?.id || 'new_' + Date.now();
    const optionItem = document.createElement('div');
    optionItem.className = 'option-item mb-2 p-2 border rounded';
    optionItem.innerHTML = `
        <div class="row align-items-center">
            <div class="col-md-4">
                <input type="text" class="form-control form-control-sm option-label" placeholder="Texto da opção" value="${optionData?.label || ''}">
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control form-control-sm option-value" placeholder="Valor (opcional)" value="${optionData?.value || ''}">
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control form-control-sm option-points" placeholder="Pontos" value="${optionData?.points || 0}" min="0">
            </div>
            <div class="col-md-3 text-end">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeOption(this)">
                    <i class="ti ti-trash"></i>
                </button>
            </div>
        </div>
        <input type="hidden" class="option-id" value="${optionId}">
    `;
    container.appendChild(optionItem);
}

function removeOption(btn) {
    btn.closest('.option-item').remove();
}

function saveStep() {
    const formData = new FormData(document.getElementById('stepForm'));
    const stepData = {
        step_id: formData.get('step_id') || null,
        title: formData.get('title'),
        description: formData.get('description'),
        type: formData.get('type'),
        required: formData.get('required') ? 1 : 0,
        points: parseInt(formData.get('points')) || 0,
        order: parseInt(formData.get('order')) || 0,
        field_name: formData.get('field_name') || null
    };
    
    // Coleta opções
    const options = [];
    document.querySelectorAll('.option-item').forEach((item, index) => {
        const optionId = item.querySelector('.option-id').value;
        const label = item.querySelector('.option-label').value;
        const value = item.querySelector('.option-value').value;
        const points = parseInt(item.querySelector('.option-points').value) || 0;
        
        if (label.trim()) {
            options.push({
                id: optionId.startsWith('new_') ? null : optionId,
                label: label.trim(),
                value: value.trim() || null,
                points: points
            });
        }
    });
    
    stepData.options = options;
    
    fetch(`<?php echo url('/quizzes/' . $quiz->id . '/steps'); ?>`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(stepData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro ao salvar etapa: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar etapa');
    });
}

function deleteStep(stepId) {
    if (confirm('Deseja realmente excluir esta etapa?')) {
        fetch(`<?php echo url('/quizzes/' . $quiz->id . '/steps/delete'); ?>`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ step_id: stepId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro ao excluir etapa: ' + (data.message || 'Erro desconhecido'));
            }
        });
    }
}

function reorderSteps() {
    const steps = Array.from(document.querySelectorAll('.step-item'));
    const order = steps.map((step, index) => {
        return step.getAttribute('data-step-id');
    });
    
    fetch(`<?php echo url('/quizzes/' . $quiz->id . '/steps/reorder'); ?>`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ order: order })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Ordem atualizada');
        }
    });
}
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

