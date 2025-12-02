<?php
ob_start();
$title = $title ?? 'Novo Projeto';
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Novo Projeto</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/projects'); ?>">Projetos</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Novo Projeto</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?php echo url('/projects'); ?>">
            <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="titulo" class="form-label">Título do Projeto <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo old('titulo'); ?>" required>
                </div>
                
                <div class="col-md-12 mb-3">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="4" placeholder="Descreva o projeto..."><?php echo old('descricao'); ?></textarea>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="planejamento" <?php echo old('status') === 'planejamento' ? 'selected' : ''; ?>>Planejamento</option>
                        <option value="em_andamento" <?php echo old('status') === 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                        <option value="pausado" <?php echo old('status') === 'pausado' ? 'selected' : ''; ?>>Pausado</option>
                        <option value="concluido" <?php echo old('status') === 'concluido' ? 'selected' : ''; ?>>Concluído</option>
                        <option value="cancelado" <?php echo old('status') === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="prioridade" class="form-label">Prioridade <span class="text-danger">*</span></label>
                    <select class="form-select" id="prioridade" name="prioridade" required>
                        <option value="baixa" <?php echo old('prioridade') === 'baixa' ? 'selected' : ''; ?>>Baixa</option>
                        <option value="media" <?php echo old('prioridade') === 'media' || old('prioridade') === '' ? 'selected' : ''; ?>>Média</option>
                        <option value="alta" <?php echo old('prioridade') === 'alta' ? 'selected' : ''; ?>>Alta</option>
                        <option value="urgente" <?php echo old('prioridade') === 'urgente' ? 'selected' : ''; ?>>Urgente</option>
                    </select>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="data_inicio" class="form-label">Data de Início</label>
                    <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo old('data_inicio'); ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="data_termino_prevista" class="form-label">Data de Término Prevista</label>
                    <input type="date" class="form-control" id="data_termino_prevista" name="data_termino_prevista" value="<?php echo old('data_termino_prevista'); ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="data_termino_real" class="form-label">Data de Término Real</label>
                    <input type="date" class="form-control" id="data_termino_real" name="data_termino_real" value="<?php echo old('data_termino_real'); ?>">
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="client_id" class="form-label">Cliente</label>
                    <select class="form-select" id="client_id" name="client_id">
                        <option value="">Selecione um cliente (opcional)</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client->id; ?>" <?php echo old('client_id') == $client->id ? 'selected' : ''; ?>>
                                <?php echo e($client->nome_razao_social); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="lead_id" class="form-label">Lead</label>
                    <select class="form-select" id="lead_id" name="lead_id">
                        <option value="">Selecione um lead (opcional)</option>
                        <?php foreach ($leads as $lead): ?>
                            <option value="<?php echo $lead->id; ?>" <?php echo old('lead_id') == $lead->id ? 'selected' : ''; ?>>
                                <?php echo e($lead->nome); ?> - <?php echo e($lead->email); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="responsible_user_id" class="form-label">Responsável</label>
                    <select class="form-select" id="responsible_user_id" name="responsible_user_id">
                        <option value="">Selecione um responsável (opcional)</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user->id; ?>" <?php echo old('responsible_user_id') == $user->id ? 'selected' : ''; ?>>
                                <?php echo e($user->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="progresso" class="form-label">Progresso (%)</label>
                    <input type="number" class="form-control" id="progresso" name="progresso" min="0" max="100" value="<?php echo old('progresso', 0); ?>">
                    <small class="text-muted">0 a 100</small>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="orcamento" class="form-label">Orçamento (R$)</label>
                    <input type="number" step="0.01" class="form-control" id="orcamento" name="orcamento" min="0" value="<?php echo old('orcamento'); ?>" placeholder="0.00">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="custo_real" class="form-label">Custo Real (R$)</label>
                    <input type="number" step="0.01" class="form-control" id="custo_real" name="custo_real" min="0" value="<?php echo old('custo_real'); ?>" placeholder="0.00">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Tags</label>
                    <div id="tags-container" class="tags-input-container">
                        <div class="tags-list" id="tags-list"></div>
                        <input type="text" id="tags-input" class="form-control tags-input" placeholder="Digite e pressione Enter ou vírgula para adicionar">
                    </div>
                    <input type="hidden" name="tags" id="tags-hidden">
                    <small class="text-muted">Digite e pressione Enter ou vírgula para adicionar tags</small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea class="form-control" id="observacoes" name="observacoes" rows="4" placeholder="Observações adicionais..."><?php echo old('observacoes'); ?></textarea>
                </div>
            </div>
            
            <div class="d-flex gap-2 justify-content-end mt-4">
                <a href="<?php echo url('/projects'); ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-check me-2"></i>Salvar Projeto
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
</script>

<style>
.tags-input-container {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    padding: 0.375rem 0.75rem;
    min-height: 38px;
    background-color: #fff;
}

.tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    margin-bottom: 0.25rem;
}

.tags-input {
    border: none;
    outline: none;
    padding: 0;
    margin: 0;
    width: 100%;
    background: transparent;
}

.tags-input:focus {
    box-shadow: none;
}
</style>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

