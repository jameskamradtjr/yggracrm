<?php
ob_start();
$title = $title ?? 'Propostas';
?>

<style>
/* Remove qualquer overflow que corte o dropdown */
.proposals-card .card-body {
    overflow: visible !important;
}

/* Table responsive com overflow visível para dropdown */
.proposals-card .table-responsive {
    overflow: visible !important;
}

/* Dropdown posicionamento */
.proposals-card .dropdown-menu {
    z-index: 1060 !important;
}
</style>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Propostas</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Propostas</li>
                    </ol>
                </nav>
            </div>
            <div class="col-3">
                <div class="text-end">
                    <a href="<?php echo url('/proposals/create'); ?>" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i>Nova Proposta
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card proposals-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="card-title fw-semibold mb-0">Lista de Propostas</h5>
            <form class="d-flex gap-2" method="GET" action="<?php echo url('/proposals'); ?>">
                <select name="status" class="form-select" style="width: auto;">
                    <option value="all" <?php echo ($status ?? 'all') === 'all' ? 'selected' : ''; ?>>Todos os Status</option>
                    <option value="rascunho" <?php echo ($status ?? '') === 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                    <option value="enviada" <?php echo ($status ?? '') === 'enviada' ? 'selected' : ''; ?>>Enviada</option>
                    <option value="aprovada" <?php echo ($status ?? '') === 'aprovada' ? 'selected' : ''; ?>>Aprovada</option>
                    <option value="rejeitada" <?php echo ($status ?? '') === 'rejeitada' ? 'selected' : ''; ?>>Rejeitada</option>
                    <option value="cancelada" <?php echo ($status ?? '') === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                </select>
                <input class="form-control" type="search" placeholder="Buscar proposta..." name="search" value="<?php echo e($search ?? ''); ?>">
                <input class="form-control" type="text" placeholder="Filtrar por tag..." name="tag_name" value="<?php echo e($tagName ?? ''); ?>" style="max-width: 150px;">
                <div class="form-check d-flex align-items-center ms-2">
                    <input class="form-check-input" type="checkbox" name="archived" value="1" id="showArchived" <?php echo ($showArchived ?? false) ? 'checked' : ''; ?>>
                    <label class="form-check-label ms-2" for="showArchived">Arquivadas</label>
                </div>
                <button class="btn btn-outline-primary" type="submit">Buscar</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Título</th>
                        <th>Cliente</th>
                        <th>Valor Total</th>
                        <th>Status</th>
                        <th>Data Criação</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($proposals)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">Nenhuma proposta encontrada.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($proposals as $proposal): ?>
                            <?php $client = $proposal->client(); ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($proposal->numero_proposta ?? 'N/A'); ?></strong>
                                </td>
                                <td>
                                    <strong><?php echo e($proposal->titulo); ?></strong>
                                    <?php if ($proposal->identificacao): ?>
                                        <br><small class="text-muted"><?php echo e($proposal->identificacao); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($client): ?>
                                        <?php echo e($client->nome_razao_social); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Não definido</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong>R$ <?php echo number_format($proposal->total ?? $proposal->valor ?? 0, 2, ',', '.'); ?></strong>
                                </td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'rascunho' => 'secondary',
                                        'enviada' => 'info',
                                        'aprovada' => 'success',
                                        'rejeitada' => 'danger',
                                        'cancelada' => 'dark'
                                    ];
                                    $color = $statusColors[$proposal->status] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $color; ?>">
                                        <?php echo ucfirst($proposal->status); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($proposal->created_at)); ?></td>
                                <td class="text-end">
                                    <div class="dropdown dropstart">
                                        <button class="btn btn-sm btn-secondary" type="button" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="<?php echo url('/proposals/' . $proposal->id); ?>">
                                                    <i class="ti ti-edit me-2"></i>Editar
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="<?php echo url('/proposals/' . $proposal->id . '/pdf'); ?>" target="_blank">
                                                    <i class="ti ti-file-text me-2"></i>Gerar PDF
                                                </a>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item" onclick="copiarLinkPublico(<?php echo $proposal->id; ?>, '<?php echo $proposal->token_publico; ?>')">
                                                    <i class="ti ti-link me-2"></i>Copiar Link Público
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li class="dropdown-header">Enviar Proposta</li>
                                            <li>
                                                <button type="button" class="dropdown-item" onclick="enviarPropostaEmail(<?php echo $proposal->id; ?>)">
                                                    <i class="ti ti-mail me-2"></i>Enviar por E-mail
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item" onclick="enviarPropostaWhatsApp(<?php echo $proposal->id; ?>, '<?php echo $proposal->token_publico; ?>')">
                                                    <i class="ti ti-brand-whatsapp me-2"></i>Enviar por WhatsApp
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li class="dropdown-header">Alterar Status</li>
                                            <li>
                                                <button type="button" class="dropdown-item" onclick="updateStatus(<?php echo $proposal->id; ?>, 'rascunho')">
                                                    <i class="ti ti-file-text me-2"></i>Rascunho
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item" onclick="updateStatus(<?php echo $proposal->id; ?>, 'enviada')">
                                                    <i class="ti ti-send me-2"></i>Enviada
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item" onclick="updateStatus(<?php echo $proposal->id; ?>, 'aprovada')">
                                                    <i class="ti ti-check me-2"></i>Aprovada
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item" onclick="updateStatus(<?php echo $proposal->id; ?>, 'rejeitada')">
                                                    <i class="ti ti-x me-2"></i>Rejeitada
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item" onclick="updateStatus(<?php echo $proposal->id; ?>, 'cancelada')">
                                                    <i class="ti ti-ban me-2"></i>Cancelada
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="<?php echo url('/proposals/' . $proposal->id . '/duplicate'); ?>" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="event.stopPropagation(); return true;">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="ti ti-copy me-2"></i>Duplicar
                                                    </button>
                                                </form>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item" onclick="toggleArchive(<?php echo $proposal->id; ?>, <?php echo $proposal->is_archived ? 'false' : 'true'; ?>)">
                                                    <i class="ti ti-<?php echo $proposal->is_archived ? 'archive-off' : 'archive'; ?> me-2"></i>
                                                    <?php echo $proposal->is_archived ? 'Desarquivar' : 'Arquivar'; ?>
                                                </button>
                                            </li>
                                            <li>
                                                <form action="<?php echo url('/proposals/' . $proposal->id . '/delete'); ?>" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="event.stopPropagation(); return confirm('Tem certeza que deseja deletar esta proposta?');">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="ti ti-trash me-2"></i>Deletar
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Move dropdown para o body quando aberto para evitar corte pelo overflow
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('show.bs.dropdown', function(e) {
        const dropdown = e.target.closest('.dropdown');
        if (!dropdown) return;
        
        const menu = dropdown.querySelector('.dropdown-menu');
        if (!menu || !dropdown.closest('.table-responsive')) return;
        
        const button = dropdown.querySelector('[data-bs-toggle="dropdown"]');
        if (!button) return;
        
        const rect = button.getBoundingClientRect();
        
        // Clona e move o menu para o body
        menu.setAttribute('data-original-parent', 'true');
        menu.style.position = 'fixed';
        menu.style.top = rect.bottom + 'px';
        menu.style.left = (rect.left - 200) + 'px'; // dropstart
        menu.style.right = 'auto';
        menu.style.zIndex = '1070';
        menu.style.minWidth = '220px';
        
        document.body.appendChild(menu);
    });
    
    document.addEventListener('hide.bs.dropdown', function(e) {
        const dropdown = e.target.closest('.dropdown');
        if (!dropdown) return;
        
        // Encontra o menu que foi movido para o body
        const menu = document.querySelector('.dropdown-menu[data-original-parent="true"]');
        if (menu) {
            menu.removeAttribute('data-original-parent');
            menu.style.position = '';
            menu.style.top = '';
            menu.style.left = '';
            menu.style.right = '';
            menu.style.zIndex = '';
            menu.style.minWidth = '';
            dropdown.appendChild(menu);
        }
    });
});

function copiarLinkPublico(proposalId, token) {
    // Gera link público completo com APP_URL
    const appUrl = '<?php echo rtrim(config('app.url', 'http://localhost'), '/'); ?>';
    const publicUrl = appUrl + '/proposals/' + proposalId + '/public/' + token;
    
    // Copia para clipboard
    navigator.clipboard.writeText(publicUrl).then(() => {
        alert('✅ Link público copiado com sucesso!\n\n' + publicUrl + '\n\n📧 Compartilhe este link com o cliente.');
    }).catch(err => {
        // Fallback para navegadores antigos
        const tempInput = document.createElement('input');
        tempInput.value = publicUrl;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        alert('✅ Link copiado!\n\n' + publicUrl);
    });
}

function updateStatus(proposalId, status) {
    if (!confirm(`Deseja alterar o status da proposta para "${status}"?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('_csrf_token', '<?php echo csrf_token(); ?>');
    formData.append('status', status);
    
    fetch('<?php echo url('/proposals'); ?>/' + proposalId + '/update-status', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Status atualizado com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao atualizar status.');
    });
}

function toggleArchive(proposalId, archive) {
    const formData = new FormData();
    formData.append('_csrf_token', '<?php echo csrf_token(); ?>');
    formData.append('archived', archive ? '1' : '0');
    
    fetch('<?php echo url('/proposals'); ?>/' + proposalId + '/archive', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao arquivar/desarquivar proposta.');
    });
}

function enviarPropostaEmail(proposalId) {
    // Abre modal para enviar por email ou redireciona para a página de envio
    const email = prompt('Digite o e-mail do destinatário:');
    if (!email || !email.trim()) return;
    
    const appUrl = '<?php echo rtrim(config('app.url', 'http://localhost'), '/'); ?>';
    const formData = new FormData();
    formData.append('_csrf_token', '<?php echo csrf_token(); ?>');
    formData.append('email', email.trim());
    
    fetch('<?php echo url('/proposals'); ?>/' + proposalId + '/send-email', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ E-mail enviado com sucesso!');
        } else {
            alert('Erro: ' + (data.message || 'Erro ao enviar e-mail'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao enviar e-mail.');
    });
}

function enviarPropostaWhatsApp(proposalId, token) {
    const appUrl = '<?php echo rtrim(config('app.url', 'http://localhost'), '/'); ?>';
    const publicUrl = appUrl + '/proposals/' + proposalId + '/public/' + token;
    
    const phone = prompt('Digite o número do WhatsApp (com DDD):');
    if (!phone || !phone.trim()) return;
    
    // Formata o número
    let formattedPhone = phone.replace(/\D/g, '');
    if (formattedPhone.length === 11 && formattedPhone.startsWith('0')) {
        formattedPhone = formattedPhone.substring(1);
    }
    if (!formattedPhone.startsWith('55')) {
        formattedPhone = '55' + formattedPhone;
    }
    
    const message = encodeURIComponent(`Olá! Segue o link da proposta para você:\n\n${publicUrl}`);
    const whatsappUrl = `https://wa.me/${formattedPhone}?text=${message}`;
    
    window.open(whatsappUrl, '_blank');
}
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

