<?php
// Formulário de edição do lead (usado no modal)
?>

<form id="editLeadForm" onsubmit="salvarLead(event, <?php echo $lead->id; ?>)">
    <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Nome *</label>
            <input type="text" name="nome" class="form-control" value="<?php echo e($lead->nome); ?>" required>
        </div>
        
        <div class="col-md-6 mb-3">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" value="<?php echo e($lead->email); ?>" required>
        </div>
        
        <div class="col-md-6 mb-3">
            <label class="form-label">Telefone *</label>
            <input type="text" name="telefone" class="form-control" value="<?php echo e($lead->telefone); ?>" required>
        </div>
        
        <div class="col-md-6 mb-3">
            <label class="form-label">Etapa do Funil</label>
            <select name="etapa_funil" class="form-select">
                <option value="interessados" <?php echo $lead->etapa_funil === 'interessados' ? 'selected' : ''; ?>>Interessados</option>
                <option value="negociacao_proposta" <?php echo $lead->etapa_funil === 'negociacao_proposta' ? 'selected' : ''; ?>>Negociação e Proposta</option>
                <option value="fechamento" <?php echo $lead->etapa_funil === 'fechamento' ? 'selected' : ''; ?>>Fechamento</option>
            </select>
        </div>
        
        <div class="col-md-6 mb-3">
            <label class="form-label">Responsável</label>
            <select name="responsible_user_id" class="form-select">
                <option value="">Sem responsável</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user->id; ?>" <?php echo $lead->responsible_user_id == $user->id ? 'selected' : ''; ?>>
                        <?php echo e($user->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-6 mb-3">
            <label class="form-label">Origem</label>
            <input type="text" name="origem" class="form-control" value="<?php echo e($lead->origem ?? ''); ?>" placeholder="Ex: Google Ads, Facebook, Indicação...">
        </div>
        
        <div class="col-md-6 mb-3">
            <label class="form-label">Valor da Oportunidade (R$)</label>
            <div class="input-group">
                <span class="input-group-text">R$</span>
                <input type="number" name="valor_oportunidade" class="form-control" value="<?php echo $lead->valor_oportunidade ?? ''; ?>" step="0.01" min="0" placeholder="0.00">
            </div>
            <small class="text-muted">Deixe vazio para remover o valor</small>
        </div>
        
        <div class="col-md-6 mb-3">
            <label class="form-label">Score</label>
            <div class="input-group">
                <input type="number" class="form-control" value="<?php echo $lead->score_potencial ?? 0; ?>" readonly>
                <span class="input-group-text">
                    <i class="ti ti-star text-warning"></i>
                </span>
            </div>
        </div>
    </div>
    
    <div class="d-flex gap-2 justify-content-end">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-check me-2"></i>
            Salvar
        </button>
    </div>
</form>

<script>
// A função salvarLead está definida globalmente em views/leads/index.php
// Se não estiver disponível, define localmente
if (typeof window.salvarLead !== 'function') {
    window.salvarLead = function(event, leadId) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';
        
        fetch('<?php echo url('/leads'); ?>/' + leadId + '/update', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na requisição: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const modalInstance = bootstrap.Modal.getInstance(document.getElementById('editLeadModal'));
                if (modalInstance) {
                    modalInstance.hide();
                }
                location.reload();
            } else {
                alert('Erro: ' + (data.message || 'Erro desconhecido'));
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Erro ao salvar lead:', error);
            alert('Erro ao salvar lead: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    };
}
</script>

