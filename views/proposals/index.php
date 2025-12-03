<?php
ob_start();
$title = $title ?? 'Propostas';
?>

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

<div class="card">
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
                                    <div class="btn-group">
                                        <a href="<?php echo url('/proposals/' . $proposal->id); ?>" class="btn btn-sm btn-primary" title="Editar">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-info" 
                                                title="Copiar Link Público para Cliente"
                                                onclick="copiarLinkPublico('<?php echo url('/proposals/' . $proposal->id . '/public/' . ($proposal->token_publico ?: 'sem-token')); ?>')">
                                            <i class="ti ti-link"></i>
                                        </button>
                                        <a href="<?php echo url('/proposals/' . $proposal->id . '/preview'); ?>" class="btn btn-sm btn-secondary" title="Preview Interno" target="_blank">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="<?php echo url('/proposals/' . $proposal->id . '/pdf'); ?>" class="btn btn-sm btn-warning" title="Gerar PDF" target="_blank">
                                            <i class="ti ti-file-pdf"></i>
                                        </a>
                                        <form action="<?php echo url('/proposals/' . $proposal->id . '/duplicate'); ?>" 
                                              method="POST" 
                                              class="d-inline">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="btn btn-sm btn-success" title="Duplicar">
                                                <i class="ti ti-copy"></i>
                                            </button>
                                        </form>
                                        <form action="<?php echo url('/proposals/' . $proposal->id . '/delete'); ?>" 
                                              method="POST" 
                                              class="d-inline" 
                                              onsubmit="return confirm('Tem certeza que deseja deletar esta proposta?');">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="btn btn-sm btn-danger" title="Deletar">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
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
function copiarLinkPublico(url) {
    // Cria um elemento temporário
    const tempInput = document.createElement('input');
    tempInput.value = url;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);
    
    // Mostra feedback visual
    alert('Link copiado!\n\n' + url + '\n\nCompartilhe este link com o cliente para que ele visualize a proposta.');
}
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

