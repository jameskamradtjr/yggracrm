<?php
$title = 'CRM de Leads';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <!-- Card Informativo -->
        <div class="card border-info mb-4">
            <div class="card-body bg-info-subtle">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-2">
                            <i class="ti ti-info-circle me-2"></i>
                            Formulário Público de Captura
                        </h5>
                        <p class="text-muted mb-0">
                            Compartilhe o link do formulário público para capturar novos leads. 
                            Os leads serão automaticamente classificados pela IA e aparecerão aqui no Kanban.
                        </p>
                    </div>
                    <div class="ms-3">
                        <a href="<?php echo url('/leads/create'); ?>" class="btn btn-success">
                            <i class="ti ti-plus me-2"></i>
                            Cadastrar Lead Manualmente
                        </a>
                        <button type="button" class="btn btn-primary mt-2" onclick="gerarLinkUnico()">
                            <i class="ti ti-link me-2"></i>
                            Gerar Link do Quiz
                        </button>
                        <div id="quiz-link-container" class="mt-3" style="display: none;">
                            <div class="input-group">
                                <input type="text" id="quiz-link-input" class="form-control" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copiarLinkUnico()">
                                    <i class="ti ti-copy me-2"></i>
                                    Copiar
                                </button>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="ti ti-info-circle me-1"></i>
                                Compartilhe este link. Todos os leads preenchidos através dele serão associados à sua conta.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title fw-semibold mb-4">CRM de Leads - Kanban</h4>
                
                <div class="row g-3" id="kanban-board">
                    <!-- Coluna Cold (0-10k) -->
                    <div class="col-lg-3 col-md-6">
                        <div class="card border border-secondary">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0">Cold (0-10k)</h5>
                                <small class="text-white-50"><?php echo count($leads['cold']); ?> leads</small>
                            </div>
                            <div class="card-body p-3" data-status="cold" style="min-height: 500px; max-height: 800px; overflow-y: auto;">
                                <?php if (empty($leads['cold'])): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="ti ti-inbox fs-1 d-block mb-2"></i>
                                        <small>Nenhum lead nesta categoria</small>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($leads['cold'] as $lead): ?>
                                        <?php include base_path('views/leads/_card.php'); ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Coluna Morno (10-50k) -->
                    <div class="col-lg-3 col-md-6">
                        <div class="card border border-info">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">Morno (10-50k)</h5>
                                <small class="text-white-50"><?php echo count($leads['morno']); ?> leads</small>
                            </div>
                            <div class="card-body p-3" data-status="morno" style="min-height: 500px; max-height: 800px; overflow-y: auto;">
                                <?php if (empty($leads['morno'])): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="ti ti-inbox fs-1 d-block mb-2"></i>
                                        <small>Nenhum lead nesta categoria</small>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($leads['morno'] as $lead): ?>
                                        <?php include base_path('views/leads/_card.php'); ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Coluna Quente (50-200k) -->
                    <div class="col-lg-3 col-md-6">
                        <div class="card border border-warning">
                            <div class="card-header bg-warning text-white">
                                <h5 class="mb-0">Quente (50-200k)</h5>
                                <small class="text-white-50"><?php echo count($leads['quente']); ?> leads</small>
                            </div>
                            <div class="card-body p-3" data-status="quente" style="min-height: 500px; max-height: 800px; overflow-y: auto;">
                                <?php if (empty($leads['quente'])): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="ti ti-inbox fs-1 d-block mb-2"></i>
                                        <small>Nenhum lead nesta categoria</small>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($leads['quente'] as $lead): ?>
                                        <?php include base_path('views/leads/_card.php'); ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Coluna Ultra Quente (200k+) -->
                    <div class="col-lg-3 col-md-6">
                        <div class="card border border-danger">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">Ultra Quente (200k+)</h5>
                                <small class="text-white-50"><?php echo count($leads['ultra_quente']); ?> leads</small>
                            </div>
                            <div class="card-body p-3" data-status="ultra_quente" style="min-height: 500px; max-height: 800px; overflow-y: auto;">
                                <?php if (empty($leads['ultra_quente'])): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="ti ti-inbox fs-1 d-block mb-2"></i>
                                        <small>Nenhum lead nesta categoria</small>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($leads['ultra_quente'] as $lead): ?>
                                        <?php include base_path('views/leads/_card.php'); ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SortableJS via CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<script>
// Função para gerar link único do quiz
function gerarLinkUnico() {
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader me-2"></i>Gerando...';
    
    fetch('<?php echo url('/leads/generate-link'); ?>', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('quiz-link-input').value = data.url;
            document.getElementById('quiz-link-container').style.display = 'block';
            btn.innerHTML = '<i class="ti ti-refresh me-2"></i>Gerar Novo Link';
        } else {
            alert('Erro: ' + data.message);
            btn.innerHTML = originalText;
        }
        btn.disabled = false;
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao gerar link.');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// Função para copiar link único
function copiarLinkUnico() {
    const input = document.getElementById('quiz-link-input');
    input.select();
    input.setSelectionRange(0, 99999); // Para mobile
    
    navigator.clipboard.writeText(input.value).then(function() {
        alert('Link copiado para a área de transferência!');
    }, function() {
        // Fallback para navegadores antigos
        document.execCommand('copy');
        alert('Link copiado para a área de transferência!');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const columns = document.querySelectorAll('[data-status]');
    
    columns.forEach(column => {
        new Sortable(column, {
            group: 'kanban',
            animation: 150,
            ghostClass: 'opacity-50',
            onEnd: function(evt) {
                const leadId = evt.item.dataset.leadId;
                const newStatus = evt.to.dataset.status;
                
                // Atualiza status via AJAX
                fetch('<?php echo url('/leads/update-status'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        lead_id: leadId,
                        status: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        // Reverte se houver erro
                        evt.from.appendChild(evt.item);
                        alert('Erro ao atualizar status: ' + data.message);
                    } else {
                        // Atualiza contador
                        const header = evt.to.closest('.card').querySelector('.card-header small');
                        const count = evt.to.children.length;
                        header.textContent = count + ' leads';
                        
                        const oldHeader = evt.from.closest('.card').querySelector('.card-header small');
                        const oldCount = evt.from.children.length;
                        oldHeader.textContent = oldCount + ' leads';
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    evt.from.appendChild(evt.item);
                    alert('Erro ao atualizar status.');
                });
            }
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

