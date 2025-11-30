<?php
ob_start();
$title = $title ?? 'Detalhes do Contrato';
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Contrato <?php echo e($contract->numero_contrato); ?></h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/dashboard'); ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="<?php echo url('/contracts'); ?>">Contratos</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page"><?php echo e($contract->numero_contrato); ?></li>
                    </ol>
                </nav>
            </div>
            <div class="col-3">
                <div class="text-end">
                    <a href="<?php echo url('/contracts/' . $contract->id . '/edit'); ?>" class="btn btn-primary me-2">
                        <i class="ti ti-pencil me-2"></i>Editar
                    </a>
                    <a href="<?php echo url('/contracts/' . $contract->id . '/pdf'); ?>" class="btn btn-secondary" target="_blank">
                        <i class="ti ti-download me-2"></i>Baixar PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status do Contrato -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1"><?php echo e($contract->titulo); ?></h5>
                <p class="text-muted mb-0">Número: <?php echo e($contract->numero_contrato); ?></p>
            </div>
            <div>
                <?php
                $status_map = [
                    'rascunho' => ['badge' => 'secondary', 'text' => 'Rascunho'],
                    'enviado' => ['badge' => 'info', 'text' => 'Enviado'],
                    'aguardando_assinaturas' => ['badge' => 'warning', 'text' => 'Aguardando Assinaturas'],
                    'assinado' => ['badge' => 'success', 'text' => 'Assinado'],
                    'cancelado' => ['badge' => 'danger', 'text' => 'Cancelado'],
                    'vencido' => ['badge' => 'dark', 'text' => 'Vencido']
                ];
                $status_info = $status_map[$contract->status] ?? ['badge' => 'secondary', 'text' => 'Desconhecido'];
                ?>
                <span class="badge bg-<?php echo $status_info['badge']; ?> fs-6"><?php echo $status_info['text']; ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Abas -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#dados" type="button" role="tab">
            <i class="ti ti-check me-2"></i>Dados
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#servicos" type="button" role="tab">
            <i class="ti ti-check me-2"></i>Serviços
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#condicoes" type="button" role="tab">
            <i class="ti ti-check me-2"></i>Condições
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#visualizacao" type="button" role="tab">
            <i class="ti ti-check me-2"></i>Visualização
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#assinaturas" type="button" role="tab">
            <i class="ti ti-alert-triangle me-2"></i>Assinaturas
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- Aba Dados -->
    <div class="tab-pane fade show active" id="dados" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong>Cliente:</strong><br>
                        <?php 
                        $client = $contract->client();
                        echo $client ? e($client->nome_razao_social) : '<span class="text-muted">N/A</span>';
                        ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Data de Início:</strong><br>
                        <?php echo $contract->data_inicio ? date('d/m/Y', strtotime($contract->data_inicio)) : '<span class="text-muted">N/A</span>'; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Data de Término:</strong><br>
                        <?php echo $contract->data_termino ? date('d/m/Y', strtotime($contract->data_termino)) : '<span class="text-muted">N/A</span>'; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Valor Total:</strong><br>
                        <?php 
                        if ($contract->valor_total) {
                            echo 'R$ ' . number_format($contract->valor_total, 2, ',', '.');
                        } else {
                            echo '<span class="text-muted">N/A</span>';
                        }
                        ?>
                    </div>
                    <?php if ($contract->observacoes): ?>
                    <div class="col-md-12 mb-3">
                        <strong>Observações:</strong><br>
                        <?php echo nl2br(e($contract->observacoes)); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Aba Serviços -->
    <div class="tab-pane fade" id="servicos" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Serviços</h5>
                    <button type="button" class="btn btn-sm btn-primary" onclick="adicionarServico()">
                        <i class="ti ti-plus me-2"></i>Adicionar Serviço
                    </button>
                </div>
                <div id="services-list">
                    <?php if (empty($services)): ?>
                        <p class="text-muted">Nenhum serviço adicionado.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Descrição</th>
                                        <th>Detalhes</th>
                                        <th>Valor</th>
                                        <th>Quantidade</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($services as $service): ?>
                                        <tr>
                                            <td><?php echo e($service->descricao); ?></td>
                                            <td><?php echo e($service->detalhes ?? '-'); ?></td>
                                            <td>R$ <?php echo number_format($service->valor ?? 0, 2, ',', '.'); ?></td>
                                            <td><?php echo $service->quantidade; ?></td>
                                            <td><strong>R$ <?php echo number_format($service->getValorTotal(), 2, ',', '.'); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Aba Condições -->
    <div class="tab-pane fade" id="condicoes" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Condições</h5>
                    <button type="button" class="btn btn-sm btn-primary" onclick="adicionarCondicao()">
                        <i class="ti ti-plus me-2"></i>Adicionar Condição
                    </button>
                </div>
                <div id="conditions-list">
                    <?php if (empty($conditions)): ?>
                        <p class="text-muted">Nenhuma condição adicionada.</p>
                    <?php else: ?>
                        <?php foreach ($conditions as $condition): ?>
                            <div class="card mb-2">
                                <div class="card-body">
                                    <h6><?php echo e($condition->titulo); ?></h6>
                                    <p class="mb-0"><?php echo nl2br(e($condition->descricao)); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Aba Visualização -->
    <div class="tab-pane fade" id="visualizacao" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <div class="border p-4" style="min-height: 400px;">
                    <?php if ($contract->conteudo_gerado): ?>
                        <?php echo $contract->conteudo_gerado; ?>
                    <?php else: ?>
                        <p class="text-muted">Nenhum conteúdo gerado ainda. Edite o contrato e selecione um template ou adicione conteúdo manualmente.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Aba Assinaturas -->
    <div class="tab-pane fade" id="assinaturas" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <?php if (empty($signatures)): ?>
                    <div class="alert alert-info">
                        <p class="mb-0">Configure as assinaturas antes de enviar o contrato.</p>
                        <button type="button" class="btn btn-primary mt-3" onclick="configurarAssinaturas()">
                            <i class="ti ti-settings me-2"></i>Configurar Assinaturas
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($signatures as $signature): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-2">
                                            Assinatura do <?php echo $signature->tipo_assinante === 'contratante' ? 'Cliente' : 'Freelancer'; ?>
                                        </h6>
                                        <p class="mb-1"><strong>Nome:</strong> <?php echo e($signature->nome_assinante); ?></p>
                                        <p class="mb-1"><strong>Email:</strong> <?php echo e($signature->email); ?></p>
                                        <?php if ($signature->cpf_cnpj): ?>
                                            <p class="mb-1"><strong>CPF/CNPJ:</strong> <?php echo e($signature->cpf_cnpj); ?></p>
                                        <?php endif; ?>
                                        <?php if ($signature->telefone): ?>
                                            <p class="mb-1"><strong>Telefone:</strong> <?php echo e($signature->telefone); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($signature->assinado): ?>
                                            <span class="badge bg-success">
                                                <i class="ti ti-check me-1"></i>Assinado
                                            </span>
                                            <?php if ($signature->assinado_em): ?>
                                                <p class="text-muted small mb-0 mt-2">
                                                    <?php echo date('d/m/Y H:i', strtotime($signature->assinado_em)); ?>
                                                </p>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning">
                                                <i class="ti ti-alert-triangle me-1"></i>Pendente
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ($signature->assinado && $signature->ip_assinatura): ?>
                                    <hr>
                                    <div class="small text-muted">
                                        <p class="mb-1"><strong>IP:</strong> <?php echo e($signature->ip_assinatura); ?></p>
                                        <?php if ($signature->geolocalizacao): ?>
                                            <?php $geo = json_decode($signature->geolocalizacao, true); ?>
                                            <?php if ($geo): ?>
                                                <p class="mb-1"><strong>Localização:</strong> <?php echo e($geo['ip'] ?? 'N/A'); ?></p>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($signature->dispositivo): ?>
                                            <p class="mb-0"><strong>Dispositivo:</strong> <?php echo e($signature->dispositivo); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!$signature->assinado): ?>
                                    <hr>
                                    <div class="mt-2">
                                        <button type="button" 
                                                class="btn btn-sm btn-success" 
                                                onclick="enviarParaAssinante('<?php echo $signature->tipo_assinante; ?>', '<?php echo e($signature->nome_assinante); ?>')"
                                                id="btn-enviar-<?php echo $signature->tipo_assinante; ?>">
                                            <i class="ti ti-send me-2"></i>Enviar Email para <?php echo $signature->tipo_assinante === 'contratante' ? 'Cliente' : 'Freelancer'; ?>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Adicionar Serviço -->
<div class="modal fade" id="modalServico" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Serviço</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formServico">
                    <div class="mb-3">
                        <label class="form-label">Descrição <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="descricao" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Detalhes</label>
                        <textarea class="form-control" name="detalhes" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Valor</label>
                            <input type="number" step="0.01" class="form-control" name="valor">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quantidade</label>
                            <input type="number" class="form-control" name="quantidade" value="1" min="1">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarServico()">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Adicionar Condição -->
<div class="modal fade" id="modalCondicao" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Condição</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formCondicao">
                    <div class="mb-3">
                        <label class="form-label">Título <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="titulo" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="descricao" rows="5" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarCondicao()">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Configurar Assinaturas -->
<div class="modal fade" id="modalAssinaturas" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Configurar Assinaturas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formAssinaturas">
                    <h6 class="mb-3">Dados do Contratante (Cliente)</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="contratante_nome" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="contratante_email" required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">CPF/CNPJ</label>
                            <input type="text" class="form-control" name="contratante_cpf_cnpj">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefone</label>
                            <input type="text" class="form-control" name="contratante_telefone">
                        </div>
                    </div>
                    
                    <h6 class="mb-3">Dados do Contratado (Freelancer/Empresa)</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="contratado_nome" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="contratado_email" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">CPF/CNPJ</label>
                            <input type="text" class="form-control" name="contratado_cpf_cnpj">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefone</label>
                            <input type="text" class="form-control" name="contratado_telefone">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarAssinaturas()">Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
const contractId = <?php echo $contract->id; ?>;

function adicionarServico() {
    document.getElementById('formServico').reset();
    new bootstrap.Modal(document.getElementById('modalServico')).show();
}

function salvarServico() {
    const form = document.getElementById('formServico');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    fetch(`<?php echo url('/contracts'); ?>/${contractId}/add-service`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            location.reload();
        } else {
            alert('Erro: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao adicionar serviço');
    });
}

function adicionarCondicao() {
    document.getElementById('formCondicao').reset();
    new bootstrap.Modal(document.getElementById('modalCondicao')).show();
}

function salvarCondicao() {
    const form = document.getElementById('formCondicao');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    fetch(`<?php echo url('/contracts'); ?>/${contractId}/add-condition`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            location.reload();
        } else {
            alert('Erro: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao adicionar condição');
    });
}

function configurarAssinaturas() {
    new bootstrap.Modal(document.getElementById('modalAssinaturas')).show();
}

function salvarAssinaturas() {
    const form = document.getElementById('formAssinaturas');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    fetch(`<?php echo url('/contracts'); ?>/${contractId}/setup-signatures`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            location.reload();
        } else {
            alert('Erro: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao configurar assinaturas');
    });
}

function enviarParaAssinante(tipoAssinante, nomeAssinante) {
    const tipoTexto = tipoAssinante === 'contratante' ? 'Cliente' : 'Freelancer';
    
    if (!confirm(`Deseja enviar o email de assinatura para ${nomeAssinante} (${tipoTexto})?`)) {
        return;
    }
    
    const btn = document.getElementById(`btn-enviar-${tipoAssinante}`);
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
    
    // Cria um AbortController para timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 60000); // 60 segundos de timeout
    
    fetch(`<?php echo url('/contracts'); ?>/${contractId}/send-for-signature`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            tipo_assinante: tipoAssinante
        }),
        signal: controller.signal
    })
    .then(response => {
        clearTimeout(timeoutId);
        if (!response.ok) {
            return response.text().then(text => {
                try {
                    const data = JSON.parse(text);
                    throw new Error(data.message || 'Erro ao enviar email');
                } catch (e) {
                    if (e instanceof SyntaxError) {
                        throw new Error('Resposta inválida do servidor: ' + text.substring(0, 100));
                    }
                    throw e;
                }
            });
        }
        return response.json();
    })
    .then(result => {
        if (result.success) {
            alert('Email enviado com sucesso para ' + nomeAssinante + '!');
            location.reload();
        } else {
            alert('Erro: ' + result.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        console.error('Erro:', error);
        let errorMessage = 'Erro ao enviar email';
        if (error.name === 'AbortError') {
            errorMessage = 'Timeout: O servidor demorou muito para responder. Verifique as configurações SMTP.';
        } else if (error.message) {
            errorMessage = error.message;
        }
        alert(errorMessage);
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

