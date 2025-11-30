<?php
ob_start();
$title = $title ?? 'Proposta';
$proposalId = $proposal->id;
?>

<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <a href="<?php echo url('/proposals'); ?>" class="text-decoration-none mb-2 d-inline-block">
                    <i class="ti ti-arrow-left me-2"></i>Voltar para Propostas
                </a>
                <h4 class="fw-semibold mb-2"><?php echo e($proposal->numero_proposta ?? 'Proposta'); ?></h4>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-<?php echo $proposal->status === 'rascunho' ? 'secondary' : ($proposal->status === 'enviada' ? 'info' : ($proposal->status === 'aprovada' ? 'success' : 'danger')); ?>">
                        <?php echo ucfirst($proposal->status); ?>
                    </span>
                    <?php if ($proposal->status === 'rascunho'): ?>
                        <small class="text-muted">Preencha todos os dados da proposta e envie para o cliente; ele ainda não tem acesso a ela.</small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-end">
                    <a href="<?php echo url('/proposals/' . $proposal->id . '/pdf'); ?>" class="btn btn-warning" target="_blank" title="Gerar PDF">
                        <i class="ti ti-file-pdf me-2"></i>PDF
                    </a>
                    <button type="button" class="btn btn-secondary" onclick="salvarProposta()">
                        <i class="ti ti-device-floppy me-2"></i>Salvar alterações
                    </button>
                    <?php if ($proposal->status === 'rascunho'): ?>
                        <button type="button" class="btn btn-primary" onclick="salvarEEnviar()">
                            <i class="ti ti-send me-2"></i>Salvar e enviar
                        </button>
                    <?php endif; ?>
                </div>
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
            <i class="ti ti-eye me-2"></i>Visualização
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- Aba Dados -->
    <div class="tab-pane fade show active" id="dados" role="tabpanel">
        <form id="proposalForm" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Dados Gerais</h5>
                    <p class="text-muted mb-4">Informações gerais da proposta. Capriche na apresentação! :)</p>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="client_id" class="form-label">Cliente <span class="text-danger">*</span></label>
                            <select name="client_id" id="client_id" class="form-select" required>
                                <option value="">Selecione um cliente...</option>
                                <?php 
                                $allClients = \App\Models\Client::where('user_id', auth()->getDataUserId())->get();
                                foreach ($allClients as $c): 
                                ?>
                                    <option value="<?php echo $c->id; ?>" <?php echo ($proposal->client_id == $c->id) ? 'selected' : ''; ?>>
                                        <?php echo e($c->nome_razao_social); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Destinatário da proposta</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="project_id" class="form-label">Projeto</label>
                            <select name="project_id" id="project_id" class="form-select">
                                <option value="">Selecione um projeto...</option>
                                <?php 
                                $allProjects = \App\Models\Project::where('user_id', auth()->getDataUserId())->get();
                                foreach ($allProjects as $p): 
                                ?>
                                    <option value="<?php echo $p->id; ?>" <?php echo ($proposal->project_id == $p->id) ? 'selected' : ''; ?>>
                                        <?php echo e($p->titulo); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="titulo" class="form-label">Título da Proposta <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo e($proposal->titulo); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="identificacao" class="form-label">Identificação da Proposta</label>
                            <input type="text" class="form-control" id="identificacao" name="identificacao" value="<?php echo e($proposal->identificacao ?? ''); ?>" placeholder="Exemplo: 2023-02, Desconto de 10%, Plano Gold, Versão 1, etc...">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="imagem_capa" class="form-label">Imagem de Capa (opcional)</label>
                            <input type="file" class="form-control" id="imagem_capa" name="imagem_capa" accept="image/*">
                            <small class="text-muted">Escolha uma imagem que tenha relação com o projeto para aumentar o apelo visual da sua proposta. Tamanho ideal: 936 x 312 px (proporção de 3:1).</small>
                            <?php if ($proposal->imagem_capa): ?>
                                <div class="mt-2">
                                    <img src="<?php echo asset($proposal->imagem_capa); ?>" alt="Capa" class="img-thumbnail" style="max-width: 300px;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="video_youtube" class="form-label">Vídeo do YouTube (opcional)</label>
                            <input type="text" class="form-control" id="video_youtube" name="video_youtube" value="<?php echo e($proposal->video_youtube ?? ''); ?>" placeholder="Cole aqui a URL do vídeo do YouTube (ex: https://www.youtube.com/watch?v=...)">
                            <small class="text-muted">Cole a URL completa do vídeo do YouTube. O vídeo será exibido na proposta para o cliente.</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="objetivo" class="form-label">Objetivo</label>
                            <textarea class="form-control" id="objetivo" name="objetivo" rows="3" placeholder="Qual o objetivo do cliente com este projeto? Ex.: &quot;Aumentar as vendas da Petshop Aumigo com um E-commerce de alta qualidade&quot;"><?php echo e($proposal->objetivo ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="apresentacao" class="form-label">Apresentação</label>
                            <textarea class="form-control" id="apresentacao" name="apresentacao" rows="5" placeholder="Apresente o seu negócio ou você como..."><?php echo e($proposal->apresentacao ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="duracao_dias" class="form-label">Duração (dias)</label>
                            <input type="number" class="form-control" id="duracao_dias" name="duracao_dias" value="<?php echo $proposal->duracao_dias ?? ''; ?>" min="1">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="data_estimada_conclusao" class="form-label">Data Estimada de Conclusão</label>
                            <input type="date" class="form-control" id="data_estimada_conclusao" name="data_estimada_conclusao" value="<?php echo $proposal->data_estimada_conclusao ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Disponibilidade</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="disponibilidade_inicio_imediato" name="disponibilidade_inicio_imediato" value="1" <?php echo ($proposal->disponibilidade_inicio_imediato ?? false) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="disponibilidade_inicio_imediato">
                                    Disponibilidade para início imediato
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="forma_pagamento" class="form-label">Forma de Pagamento</label>
                            <select name="forma_pagamento" id="forma_pagamento" class="form-select">
                                <option value="">Selecione...</option>
                                <option value="a_vista" <?php echo ($proposal->forma_pagamento === 'a_vista') ? 'selected' : ''; ?>>À vista</option>
                                <option value="parcelado" <?php echo ($proposal->forma_pagamento === 'parcelado') ? 'selected' : ''; ?>>Parcelado</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="desconto_percentual" class="form-label">Desconto (%)</label>
                            <input type="number" class="form-control" id="desconto_percentual" name="desconto_percentual" value="<?php echo $proposal->desconto_percentual ?? 0; ?>" min="0" max="100" step="0.01" onchange="calcularTotais()">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?php echo e($proposal->observacoes ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Aba Serviços -->
    <div class="tab-pane fade" id="servicos" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Serviços</h5>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
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
                                        <th>Título</th>
                                        <th>Descrição</th>
                                        <th>Quantidade</th>
                                        <th>Valor Unitário</th>
                                        <th>Total</th>
                                        <th class="text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($services as $service): ?>
                                        <tr>
                                            <td><?php echo e($service->titulo); ?></td>
                                            <td><?php echo e($service->descricao ?? '-'); ?></td>
                                            <td><?php echo $service->quantidade; ?></td>
                                            <td>R$ <?php echo number_format($service->valor_unitario, 2, ',', '.'); ?></td>
                                            <td><strong>R$ <?php echo number_format($service->valor_total, 2, ',', '.'); ?></strong></td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-danger" onclick="deletarServico(<?php echo $service->id; ?>)">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                        <td><strong>R$ <?php echo number_format($proposal->subtotal ?? 0, 2, ',', '.'); ?></strong></td>
                                        <td></td>
                                    </tr>
                                    <?php if ($proposal->desconto_valor > 0): ?>
                                    <tr>
                                        <td colspan="4" class="text-end text-success"><strong>Desconto (<?php echo number_format($proposal->desconto_percentual ?? 0, 2, ',', '.'); ?>%):</strong></td>
                                        <td class="text-success"><strong>- R$ <?php echo number_format($proposal->desconto_valor, 2, ',', '.'); ?></strong></td>
                                        <td></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                        <td><strong class="text-primary">R$ <?php echo number_format($proposal->total ?? 0, 2, ',', '.'); ?></strong></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
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
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addConditionModal">
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
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6><?php echo e($condition->titulo); ?></h6>
                                            <?php if ($condition->descricao): ?>
                                                <p class="mb-0"><?php echo nl2br(e($condition->descricao)); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deletarCondicao(<?php echo $condition->id; ?>)">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
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
                <div class="alert alert-info mb-3">
                    <i class="ti ti-info-circle me-2"></i>
                    Veja como o seu cliente vai visualizar a sua proposta e prepare-se para enviá-la.
                </div>
                <iframe src="<?php echo url('/proposals/' . $proposal->id . '/preview'); ?>" style="width: 100%; height: 800px; border: 1px solid #ddd; border-radius: 5px;"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Modal Adicionar Serviço -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Serviço</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addServiceForm">
                    <div class="mb-3">
                        <label for="service_titulo" class="form-label">Título <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="service_titulo" name="titulo" required>
                    </div>
                    <div class="mb-3">
                        <label for="service_descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="service_descricao" name="descricao" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="service_quantidade" class="form-label">Quantidade <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="service_quantidade" name="quantidade" value="1" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="service_valor_unitario" class="form-label">Valor Unitário <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="service_valor_unitario" name="valor_unitario" step="0.01" min="0" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="adicionarServico()">Adicionar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Adicionar Condição -->
<div class="modal fade" id="addConditionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Condição</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addConditionForm">
                    <div class="mb-3">
                        <label for="condition_titulo" class="form-label">Título <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="condition_titulo" name="titulo" required>
                    </div>
                    <div class="mb-3">
                        <label for="condition_descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="condition_descricao" name="descricao" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="adicionarCondicao()">Adicionar</button>
            </div>
        </div>
    </div>
</div>

<script>
const proposalId = <?php echo $proposalId; ?>;

function salvarProposta() {
    const form = document.getElementById('proposalForm');
    const formData = new FormData(form);
    
    fetch(`<?php echo url('/proposals'); ?>/${proposalId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Proposta salva com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro ao salvar proposta'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar proposta');
    });
}

function salvarEEnviar() {
    if (!confirm('Tem certeza que deseja enviar esta proposta para o cliente?')) {
        return;
    }
    
    salvarProposta();
    
    // Após salvar, envia
    setTimeout(() => {
        fetch(`<?php echo url('/proposals'); ?>/${proposalId}/send`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Proposta enviada com sucesso!');
                location.reload();
            } else {
                alert('Erro: ' + (data.message || 'Erro ao enviar proposta'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao enviar proposta');
        });
    }, 500);
}

function adicionarServico() {
    const form = document.getElementById('addServiceForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    fetch(`<?php echo url('/proposals'); ?>/${proposalId}/add-service`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Serviço adicionado com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro ao adicionar serviço'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao adicionar serviço');
    });
}

function adicionarCondicao() {
    const form = document.getElementById('addConditionForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    fetch(`<?php echo url('/proposals'); ?>/${proposalId}/add-condition`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Condição adicionada com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro ao adicionar condição'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao adicionar condição');
    });
}

function deletarServico(serviceId) {
    if (!confirm('Tem certeza que deseja deletar este serviço?')) {
        return;
    }
    
    // TODO: Implementar endpoint de deletar serviço
    alert('Funcionalidade de deletar serviço será implementada');
}

function deletarCondicao(conditionId) {
    if (!confirm('Tem certeza que deseja deletar esta condição?')) {
        return;
    }
    
    // TODO: Implementar endpoint de deletar condição
    alert('Funcionalidade de deletar condição será implementada');
}

function calcularTotais() {
    // Recalcula totais quando desconto muda
    // Será feito no backend ao salvar
}
</script>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

