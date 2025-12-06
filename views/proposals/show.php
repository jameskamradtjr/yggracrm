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
                    <button type="button" 
                            class="btn btn-info" 
                            onclick="copyPublicLink(<?php echo $proposal->id; ?>, '<?php echo $proposal->token_publico; ?>')"
                            title="Copiar Link Público para Cliente">
                        <i class="ti ti-link me-2"></i>Link Cliente
                    </button>
                    <a href="<?php echo url('/proposals/' . $proposal->id . '/pdf'); ?>" class="btn btn-warning" target="_blank" title="Gerar PDF">
                        <i class="ti ti-file-text me-2"></i>PDF
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
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pagamento" type="button" role="tab">
            <i class="ti ti-credit-card me-2"></i>Formas de Pagamento
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#provas-sociais" type="button" role="tab">
            <i class="ti ti-star me-2"></i>Provas Sociais
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tecnologias" type="button" role="tab">
            <i class="ti ti-code me-2"></i>Tecnologias
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#roadmap" type="button" role="tab">
            <i class="ti ti-route me-2"></i>Roadmap
        </button>
    </li>
</ul>

<!-- Estatísticas de Visualização -->
<?php 
$viewStats = \App\Models\ProposalView::getStats($proposal->id);
?>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary-subtle">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="ti ti-eye fs-8 text-primary"></i>
                    </div>
                    <div>
                        <h3 class="mb-0"><?php echo number_format($viewStats['total']); ?></h3>
                        <p class="mb-0 text-muted">Visualizações</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success-subtle">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="ti ti-users fs-8 text-success"></i>
                    </div>
                    <div>
                        <h3 class="mb-0"><?php echo number_format($viewStats['unique']); ?></h3>
                        <p class="mb-0 text-muted">Visitantes Únicos</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info-subtle">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="ti ti-calendar fs-8 text-info"></i>
                    </div>
                    <div>
                        <h3 class="mb-0"><?php echo number_format($viewStats['today']); ?></h3>
                        <p class="mb-0 text-muted">Hoje</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning-subtle">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="ti ti-clock fs-8 text-warning"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">
                            <?php if ($viewStats['last_view']): ?>
                                <?php echo date('d/m/Y H:i', strtotime($viewStats['last_view'])); ?>
                            <?php else: ?>
                                Nunca
                            <?php endif; ?>
                        </h6>
                        <p class="mb-0 text-muted">Última Visualização</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
                            <label for="lead_id" class="form-label">Lead</label>
                            <select class="form-control tom-select-ajax" id="lead_id" name="lead_id" 
                                    data-type="lead"
                                    data-placeholder="Digite para buscar lead..."
                                    data-selected-id="<?php echo $proposal->lead_id ?? ''; ?>"
                                    data-selected-text="<?php echo e($proposal->lead() ? $proposal->lead()->nome : ''); ?>">
                                <option value="">Selecione um lead...</option>
                                <?php if ($proposal->lead_id && $proposal->lead()): ?>
                                    <option value="<?php echo $proposal->lead_id; ?>" selected>
                                        <?php echo e($proposal->lead()->nome); ?>
                                    </option>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">Relacionar proposta com um lead</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="client_id" class="form-label">Cliente</label>
                            <select class="form-control tom-select-ajax" id="client_id" name="client_id"
                                    data-type="client"
                                    data-placeholder="Digite para buscar cliente..."
                                    data-selected-id="<?php echo $proposal->client_id ?? ''; ?>"
                                    data-selected-text="<?php echo e($proposal->client() ? $proposal->client()->nome_razao_social : ''); ?>">
                                <option value="">Selecione um cliente...</option>
                                <?php if ($proposal->client_id && $proposal->client()): ?>
                                    <option value="<?php echo $proposal->client_id; ?>" selected>
                                        <?php echo e($proposal->client()->nome_razao_social); ?>
                                    </option>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">Destinatário da proposta (opcional se tiver lead)</small>
                        </div>
                    </div>
                    
                    <div class="row">
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
                            <label for="tags-input" class="form-label">Tags</label>
                            <div class="tags-input-container" style="border: 1px solid #ced4da; border-radius: 0.375rem; padding: 0.375rem 0.75rem; min-height: 38px; background-color: #fff;">
                                <div id="tags-list" class="tags-list" style="display: flex; flex-wrap: wrap; gap: 0.25rem; margin-bottom: 0.25rem;">
                                    <?php 
                                    $proposalTags = $proposal->getTags();
                                    foreach ($proposalTags as $tag):
                                    ?>
                                        <span class="badge bg-primary me-1 mb-1">
                                            <?php echo e($tag['name']); ?>
                                            <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.7em;" onclick="removeProposalTag(<?php echo $tag['id']; ?>)"></button>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                                <input type="text" class="tags-input" id="tags-input" style="border: none; outline: none; padding: 0; margin: 0; width: 100%; background: transparent;" placeholder="Digite uma tag e pressione Enter ou vírgula">
                                <input type="hidden" id="tags-hidden" name="tags" value="<?php echo implode(',', array_column($proposalTags, 'id')); ?>">
                            </div>
                            <small class="text-muted">Digite tags e pressione Enter ou vírgula para adicionar</small>
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
    
    <!-- Aba Formas de Pagamento -->
    <div class="tab-pane fade" id="pagamento" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Formas de Pagamento</h5>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentFormModal">
                        <i class="ti ti-plus me-2"></i>Adicionar Forma de Pagamento
                    </button>
                </div>
                
                <div id="payment-forms-list">
                    <?php 
                    $normalConditions = [];
                    if (!empty($conditions)) {
                        foreach ($conditions as $c) {
                            if (!$c->isPaymentForm()) {
                                $normalConditions[] = $c;
                            }
                        }
                    }
                    $paymentForms = $paymentForms ?? [];
                    
                    // Busca forma de pagamento selecionada
                    $selectedPaymentForm = null;
                    foreach ($paymentForms as $form) {
                        if ($form->is_selected) {
                            $selectedPaymentForm = $form;
                            break;
                        }
                    }
                    ?>
                    
                    <?php if ($selectedPaymentForm): ?>
                        <div class="alert alert-info mb-3">
                            <h6 class="mb-2"><i class="ti ti-check-circle me-2"></i>Forma de Pagamento Selecionada pelo Cliente:</h6>
                            <div class="card border-primary">
                                <div class="card-body">
                                    <h6 class="text-primary"><?php echo e($selectedPaymentForm->titulo); ?></h6>
                                    <?php if ($selectedPaymentForm->descricao): ?>
                                        <p class="mb-2 text-muted"><?php echo nl2br(e($selectedPaymentForm->descricao)); ?></p>
                                    <?php endif; ?>
                                    <div>
                                        <?php if ($selectedPaymentForm->valor_original && $selectedPaymentForm->valor_final): ?>
                                            <span class="text-decoration-line-through text-muted me-2">
                                                R$ <?php echo number_format($selectedPaymentForm->valor_original, 2, ',', '.'); ?>
                                            </span>
                                            <strong class="text-primary fs-5">
                                                R$ <?php echo number_format($selectedPaymentForm->valor_final, 2, ',', '.'); ?>
                                            </strong>
                                        <?php elseif ($selectedPaymentForm->parcelas && $selectedPaymentForm->valor_parcela): ?>
                                            <strong class="fs-5">
                                                <?php echo $selectedPaymentForm->parcelas; ?>x de R$ <?php echo number_format($selectedPaymentForm->valor_parcela, 2, ',', '.'); ?>
                                            </strong>
                                            <?php if ($selectedPaymentForm->valor_final): ?>
                                                <span class="text-muted ms-2">
                                                    (Total: R$ <?php echo number_format($selectedPaymentForm->valor_final, 2, ',', '.'); ?>)
                                                </span>
                                            <?php endif; ?>
                                        <?php elseif ($selectedPaymentForm->valor_final): ?>
                                            <strong class="text-primary fs-5">
                                                R$ <?php echo number_format($selectedPaymentForm->valor_final, 2, ',', '.'); ?>
                                            </strong>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($paymentForms)): ?>
                        <p class="text-muted">Nenhuma forma de pagamento adicionada.</p>
                    <?php else: ?>
                        <h6 class="mb-3">Todas as Formas de Pagamento:</h6>
                        <?php foreach ($paymentForms as $form): ?>
                            <div class="card mb-2 <?php echo $form->is_selected ? 'border-primary' : ''; ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6>
                                                <?php echo e($form->titulo); ?>
                                                <?php if ($form->is_selected): ?>
                                                    <span class="badge bg-primary ms-2">Selecionada</span>
                                                <?php endif; ?>
                                            </h6>
                                            <?php if ($form->descricao): ?>
                                                <p class="mb-2 text-muted"><?php echo nl2br(e($form->descricao)); ?></p>
                                            <?php endif; ?>
                                            <div>
                                                <?php if ($form->valor_original && $form->valor_final): ?>
                                                    <span class="text-decoration-line-through text-muted me-2">
                                                        R$ <?php echo number_format($form->valor_original, 2, ',', '.'); ?>
                                                    </span>
                                                    <strong class="text-primary">
                                                        R$ <?php echo number_format($form->valor_final, 2, ',', '.'); ?>
                                                    </strong>
                                                <?php elseif ($form->parcelas && $form->valor_parcela): ?>
                                                    <strong>
                                                        <?php echo $form->parcelas; ?>x de R$ <?php echo number_format($form->valor_parcela, 2, ',', '.'); ?>
                                                    </strong>
                                                    <?php if ($form->valor_final): ?>
                                                        <span class="text-muted ms-2">
                                                            (Total: R$ <?php echo number_format($form->valor_final, 2, ',', '.'); ?>)
                                                        </span>
                                                    <?php endif; ?>
                                                <?php elseif ($form->valor_final): ?>
                                                    <strong class="text-primary">
                                                        R$ <?php echo number_format($form->valor_final, 2, ',', '.'); ?>
                                                    </strong>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deletarFormaPagamento(<?php echo $form->id; ?>)">
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
    
    <!-- Aba Provas Sociais -->
    <div class="tab-pane fade" id="provas-sociais" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Provas Sociais</h5>
                    <?php 
                    $testimonials = $testimonials ?? [];
                    $testimonialsCount = count($testimonials);
                    ?>
                    <?php if ($testimonialsCount < 3): ?>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTestimonialModal">
                            <i class="ti ti-plus me-2"></i>Adicionar Prova Social
                        </button>
                    <?php else: ?>
                        <span class="text-muted">Limite de 3 provas sociais atingido</span>
                    <?php endif; ?>
                </div>
                
                <div id="testimonials-list">
                    <?php if (empty($testimonials)): ?>
                        <p class="text-muted">Nenhuma prova social adicionada.</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($testimonials as $testimonial): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <?php if (!empty($testimonial['photo_url'])): ?>
                                                    <img src="<?php echo e($testimonial['photo_url']); ?>" 
                                                         alt="<?php echo e($testimonial['client_name']); ?>"
                                                         class="rounded-circle me-2"
                                                         style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" 
                                                         style="width: 50px; height: 50px; font-size: 20px; font-weight: bold;">
                                                        <?php echo strtoupper(substr($testimonial['client_name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-semibold"><?php echo e($testimonial['client_name']); ?></div>
                                                    <?php if (!empty($testimonial['company'])): ?>
                                                        <small class="text-muted"><?php echo e($testimonial['company']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <p class="mb-0" style="font-style: italic; color: #666;">
                                                "<?php echo nl2br(e($testimonial['testimonial'])); ?>"
                                            </p>
                                            <button type="button" class="btn btn-sm btn-danger mt-2" onclick="deletarProvaSocial(<?php echo $testimonial['id']; ?>)">
                                                <i class="ti ti-trash"></i> Remover
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Aba Tecnologias -->
    <div class="tab-pane fade" id="tecnologias" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Tecnologias Utilizadas</h5>
                </div>
                
                <?php 
                $availableTechnologies = [
                    'PHP' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/php/php-original.svg',
                    'MySQL' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/mysql/mysql-original.svg',
                    'Figma' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/figma/figma-original.svg',
                    'AWS' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/amazonwebservices/amazonwebservices-original-wordmark.svg',
                    'Python' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/python/python-original.svg',
                    'N8N' => 'https://n8n.io/favicon.ico',
                    'Hostinger' => 'https://www.hostinger.com.br/favicon.ico',
                    'VPS' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/linux/linux-original.svg',
                    'JavaScript' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/javascript/javascript-original.svg',
                    'React' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/react/react-original.svg',
                    'Node.js' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/nodejs/nodejs-original.svg',
                    'Laravel' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/laravel/laravel-original.svg',
                    'Docker' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/docker/docker-original.svg',
                    'PostgreSQL' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/postgresql/postgresql-original.svg',
                    'MongoDB' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/mongodb/mongodb-original.svg',
                    'Redis' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/redis/redis-original.svg',
                ];
                $currentTechnologies = $technologies ?? [];
                ?>
                
                <div class="mb-4">
                    <label class="form-label">Adicionar Tecnologia</label>
                    <div class="d-flex gap-2">
                        <select class="form-select" id="technology-select" style="max-width: 300px;">
                            <option value="">Selecione uma tecnologia...</option>
                            <?php foreach ($availableTechnologies as $tech => $logo): ?>
                                <?php if (!in_array($tech, $currentTechnologies)): ?>
                                    <option value="<?php echo e($tech); ?>"><?php echo e($tech); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-primary" onclick="adicionarTecnologia()">
                            <i class="ti ti-plus me-2"></i>Adicionar
                        </button>
                    </div>
                </div>
                
                <div id="technologies-list" class="d-flex flex-wrap gap-3">
                    <?php if (empty($currentTechnologies)): ?>
                        <p class="text-muted">Nenhuma tecnologia adicionada.</p>
                    <?php else: ?>
                        <?php foreach ($currentTechnologies as $tech): ?>
                            <div class="d-flex align-items-center gap-2 p-2 border rounded technology-badge" data-tech="<?php echo e($tech); ?>">
                                <?php if (isset($availableTechnologies[$tech])): ?>
                                    <img src="<?php echo $availableTechnologies[$tech]; ?>" alt="<?php echo e($tech); ?>" style="width: 24px; height: 24px;">
                                <?php else: ?>
                                    <i class="ti ti-code fs-5"></i>
                                <?php endif; ?>
                                <span><?php echo e($tech); ?></span>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removerTecnologia('<?php echo e($tech); ?>')">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Aba Roadmap -->
    <div class="tab-pane fade" id="roadmap" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Roadmap / Cronograma</h5>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addRoadmapModal">
                        <i class="ti ti-plus me-2"></i>Adicionar Etapa
                    </button>
                </div>
                
                <div id="roadmap-list">
                    <?php $roadmapSteps = $roadmapSteps ?? []; ?>
                    <?php if (empty($roadmapSteps)): ?>
                        <p class="text-muted">Nenhuma etapa do roadmap adicionada.</p>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($roadmapSteps as $index => $step): ?>
                                <div class="timeline-item mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <span class="badge bg-primary me-2"><?php echo $index + 1; ?></span>
                                                    <strong><?php echo e($step['title']); ?></strong>
                                                    <?php if (!empty($step['estimated_date'])): ?>
                                                        <span class="text-muted ms-2">
                                                            <i class="ti ti-calendar"></i>
                                                            <?php echo date('d/m/Y', strtotime($step['estimated_date'])); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="removerEtapaRoadmap(<?php echo $step['id']; ?>)">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </div>
                                            <?php if (!empty($step['description'])): ?>
                                                <p class="mt-2 mb-0 text-muted"><?php echo nl2br(e($step['description'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
</div>

<!-- Modal Adicionar Etapa Roadmap -->
<div class="modal fade" id="addRoadmapModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Etapa do Roadmap</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addRoadmapForm">
                    <div class="mb-3">
                        <label for="roadmap_title" class="form-label">Título <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="roadmap_title" name="title" required placeholder="Ex: Reunião Inicial, Design, Programação">
                    </div>
                    <div class="mb-3">
                        <label for="roadmap_description" class="form-label">Descrição</label>
                        <textarea class="form-control" id="roadmap_description" name="description" rows="3" placeholder="Detalhes da etapa"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="roadmap_estimated_date" class="form-label">Data Prevista</label>
                        <input type="date" class="form-control" id="roadmap_estimated_date" name="estimated_date">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="adicionarEtapaRoadmap()">Adicionar</button>
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

<!-- Modal Adicionar Forma de Pagamento -->
<div class="modal fade" id="addPaymentFormModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Forma de Pagamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addPaymentFormForm">
                    <div class="mb-3">
                        <label for="payment_titulo" class="form-label">Título <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="payment_titulo" name="titulo" required placeholder="Ex: À vista, 12x no Cartão, 3 parcelas no PIX">
                    </div>
                    <div class="mb-3">
                        <label for="payment_descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="payment_descricao" name="descricao" rows="3" placeholder="Ex: O pagamento pode ser feito à vista. Com desconto de R$ 500"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="payment_valor_original" class="form-label">Valor Original (opcional)</label>
                            <input type="number" step="0.01" class="form-control" id="payment_valor_original" name="valor_original" placeholder="8000.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="payment_valor_final" class="form-label">Valor Final</label>
                            <input type="number" step="0.01" class="form-control" id="payment_valor_final" name="valor_final" placeholder="5000.00">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="payment_parcelas" class="form-label">Número de Parcelas (opcional)</label>
                            <input type="number" class="form-control" id="payment_parcelas" name="parcelas" placeholder="12" min="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="payment_valor_parcela" class="form-label">Valor da Parcela (opcional)</label>
                            <input type="number" step="0.01" class="form-control" id="payment_valor_parcela" name="valor_parcela" placeholder="1000.00">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="adicionarFormaPagamento()">Adicionar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Adicionar Prova Social -->
<div class="modal fade" id="addTestimonialModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Prova Social</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addTestimonialForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="testimonial_client_name" class="form-label">Nome do Cliente <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="testimonial_client_name" name="client_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="testimonial_company" class="form-label">Empresa</label>
                        <input type="text" class="form-control" id="testimonial_company" name="company">
                    </div>
                    <div class="mb-3">
                        <label for="testimonial_testimonial" class="form-label">Depoimento <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="testimonial_testimonial" name="testimonial" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="testimonial_photo" class="form-label">Foto do Cliente</label>
                        <input type="file" class="form-control" id="testimonial_photo" name="photo" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                        <small class="text-muted">Formatos: JPG, PNG, GIF, WEBP</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="adicionarProvaSocial()">Adicionar</button>
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

function adicionarFormaPagamento() {
    const form = document.getElementById('addPaymentFormForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    // Adiciona CSRF token
    data._csrf_token = document.querySelector('meta[name="csrf-token"]').content;
    
    fetch(`<?php echo url('/proposals'); ?>/${proposalId}/add-payment-form`, {
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
            alert('Forma de pagamento adicionada com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro ao adicionar forma de pagamento'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao adicionar forma de pagamento');
    });
}

function deletarFormaPagamento(conditionId) {
    if (!confirm('Tem certeza que deseja deletar esta forma de pagamento?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('_csrf_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('condition_id', conditionId);
    
    fetch(`<?php echo url('/proposals'); ?>/${proposalId}/delete-condition`, {
        method: 'POST',
        headers: {
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Forma de pagamento removida com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro ao remover forma de pagamento'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao remover forma de pagamento');
    });
}

function adicionarProvaSocial() {
    const form = document.getElementById('addTestimonialForm');
    const formData = new FormData(form);
    
    // Adiciona CSRF token
    formData.append('_csrf_token', document.querySelector('meta[name="csrf-token"]').content);
    
    fetch(`<?php echo url('/proposals'); ?>/${proposalId}/add-testimonial`, {
        method: 'POST',
        headers: {
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Prova social adicionada com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro ao adicionar prova social'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao adicionar prova social');
    });
}

function deletarProvaSocial(testimonialId) {
    if (!confirm('Tem certeza que deseja remover esta prova social?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('_csrf_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('testimonial_id', testimonialId);
    
    fetch(`<?php echo url('/proposals'); ?>/${proposalId}/remove-testimonial`, {
        method: 'POST',
        headers: {
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Prova social removida com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro ao remover prova social'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao remover prova social');
    });
}

function adicionarTecnologia() {
    const select = document.getElementById('technology-select');
    const technology = select.value;
    
    if (!technology) {
        alert('Selecione uma tecnologia');
        return;
    }
    
    // Pega as tecnologias atuais e adiciona a nova
    const currentTechnologies = <?php echo json_encode($proposal->getTechnologies()); ?>;
    if (currentTechnologies.includes(technology)) {
        alert('Esta tecnologia já foi adicionada');
        return;
    }
    
    const formData = new FormData(document.getElementById('proposalForm'));
    
    // Adiciona todas as tecnologias existentes + a nova
    currentTechnologies.forEach(t => formData.append('technologies[]', t));
    formData.append('technologies[]', technology);
    
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
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro ao adicionar tecnologia'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao adicionar tecnologia');
    });
}

function removerTecnologia(technology) {
    if (!confirm(`Tem certeza que deseja remover a tecnologia "${technology}"?`)) {
        return;
    }
    
    const technologies = <?php echo json_encode($proposal->getTechnologies()); ?>;
    const updated = technologies.filter(t => t !== technology);
    
    const formData = new FormData(document.getElementById('proposalForm'));
    updated.forEach(t => formData.append('technologies[]', t));
    
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
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro ao remover tecnologia'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao remover tecnologia');
    });
}

function adicionarEtapaRoadmap() {
    const form = document.getElementById('addRoadmapForm');
    const title = document.getElementById('roadmap_title').value.trim();
    const description = document.getElementById('roadmap_description').value.trim();
    const estimatedDate = document.getElementById('roadmap_estimated_date').value;
    
    if (!title) {
        alert('O título é obrigatório');
        return;
    }
    
    const formData = new FormData();
    formData.append('_csrf_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('title', title);
    formData.append('description', description);
    formData.append('estimated_date', estimatedDate);
    
    fetch(`<?php echo url('/proposals'); ?>/${proposalId}/add-roadmap-step`, {
        method: 'POST',
        headers: {
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('addRoadmapModal')).hide();
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro ao adicionar etapa'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao adicionar etapa');
    });
}

function removerEtapaRoadmap(stepId) {
    if (!confirm('Tem certeza que deseja remover esta etapa?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('_csrf_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('step_id', stepId);
    
    fetch(`<?php echo url('/proposals'); ?>/${proposalId}/remove-roadmap-step`, {
        method: 'POST',
        headers: {
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro ao remover etapa'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao remover etapa');
    });
}

// Sistema de Tags
(function() {
    const tagsList = document.getElementById('tags-list');
    const tagsInput = document.getElementById('tags-input');
    const tagsHidden = document.getElementById('tags-hidden');
    let tagIds = [];
    
    // Inicializa tags existentes
    const existingTags = tagsHidden.value;
    if (existingTags) {
        tagIds = existingTags.split(',').map(t => t.trim()).filter(t => t);
    }
    
    function updateHiddenInput() {
        tagsHidden.value = tagIds.join(',');
    }
    
    function addTag(tagName) {
        tagName = tagName.trim();
        if (!tagName) return;
        
        // Busca ou cria tag via API
        fetch('<?php echo url('/api/tags/create'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                name: tagName,
                _csrf_token: document.querySelector('meta[name="csrf-token"]').content
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.tag) {
                const tagId = data.tag.id;
                if (!tagIds.includes(tagId.toString())) {
                    tagIds.push(tagId.toString());
                    const tagElement = document.createElement('span');
                    tagElement.className = 'badge bg-primary me-1 mb-1';
                    tagElement.innerHTML = data.tag.name + ' <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.7em;" onclick="removeProposalTag(' + tagId + ')"></button>';
                    tagsList.appendChild(tagElement);
                    updateHiddenInput();
                }
            }
        })
        .catch(error => {
            console.error('Erro ao adicionar tag:', error);
        });
    }
    
    window.removeProposalTag = function(tagId) {
        tagIds = tagIds.filter(id => id !== tagId.toString());
        // Remove visualmente
        const badges = tagsList.querySelectorAll('.badge');
        badges.forEach(badge => {
            const btn = badge.querySelector('button');
            if (btn && btn.getAttribute('onclick') && btn.getAttribute('onclick').includes(tagId)) {
                badge.remove();
            }
        });
        updateHiddenInput();
    };
    
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

// Sistema de Tags para Propostas
(function() {
    const tagsList = document.getElementById('tags-list');
    const tagsInput = document.getElementById('tags-input');
    const tagsHidden = document.getElementById('tags-hidden');
    let tagIds = [];
    
    // Inicializa tags existentes
    const existingTags = tagsHidden.value;
    if (existingTags) {
        tagIds = existingTags.split(',').map(t => t.trim()).filter(t => t);
    }
    
    function updateHiddenInput() {
        tagsHidden.value = tagIds.join(',');
    }
    
    function addTag(tagName) {
        tagName = tagName.trim();
        if (!tagName) return;
        
        // Busca ou cria tag via API
        fetch('<?php echo url('/api/tags/create'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                name: tagName,
                _csrf_token: document.querySelector('meta[name="csrf-token"]').content
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.tag) {
                const tagId = data.tag.id;
                if (!tagIds.includes(tagId.toString())) {
                    tagIds.push(tagId.toString());
                    const tagElement = document.createElement('span');
                    tagElement.className = 'badge bg-primary me-1 mb-1';
                    tagElement.innerHTML = data.tag.name + ' <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.7em;" onclick="removeProposalTag(' + tagId + ')"></button>';
                    tagsList.appendChild(tagElement);
                    updateHiddenInput();
                }
            }
        })
        .catch(error => {
            console.error('Erro ao adicionar tag:', error);
        });
    }
    
    window.removeProposalTag = function(tagId) {
        tagIds = tagIds.filter(id => id !== tagId.toString());
        // Remove visualmente
        const badges = tagsList.querySelectorAll('.badge');
        badges.forEach(badge => {
            const btn = badge.querySelector('button');
            if (btn && btn.getAttribute('onclick') && btn.getAttribute('onclick').includes(tagId)) {
                badge.remove();
            }
        });
        updateHiddenInput();
    };
    
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

function calcularTotais() {
    // Recalcula totais quando desconto muda
    // Será feito no backend ao salvar
}

// Função para copiar link público
function copyPublicLink(proposalId, token) {
    // Gera link público completo com APP_URL
    const appUrl = '<?php echo rtrim(config('app.url', 'http://localhost'), '/'); ?>';
    const publicUrl = appUrl + '/proposals/' + proposalId + '/public/' + token;
    
    // Copia para clipboard
    navigator.clipboard.writeText(publicUrl).then(() => {
        alert('✅ Link público copiado com sucesso!\n\n' + publicUrl + '\n\n📧 Compartilhe este link com o cliente para que ele visualize a proposta.');
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
</script>

<?php
$content = ob_get_clean();

// Scripts para Tom Select
ob_start();
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializa Tom Select para todos os campos com classe tom-select-ajax
    document.querySelectorAll('.tom-select-ajax').forEach(function(selectElement) {
        const type = selectElement.dataset.type;
        const placeholder = selectElement.dataset.placeholder || 'Digite para buscar...';
        const selectedId = selectElement.dataset.selectedId;
        const selectedText = selectElement.dataset.selectedText;
        
        // Determina URL baseado no tipo
        const searchUrl = type === 'lead' 
            ? '<?php echo url('/drive/search/leads'); ?>' 
            : '<?php echo url('/drive/search/clients'); ?>';
        
        const config = {
            valueField: 'id',
            labelField: 'text',
            searchField: 'text',
            placeholder: placeholder,
            loadThrottle: 300,
            preload: false,
            load: function(query, callback) {
                if (query.length < 2) {
                    return callback();
                }
                fetch(`${searchUrl}?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(json => {
                        callback(json.results || []);
                    })
                    .catch(() => callback());
            },
            plugins: ['clear_button']
        };
        
        // Adiciona opção pré-selecionada se existir
        if (selectedId && selectedText) {
            config.options = [{
                id: selectedId,
                text: selectedText
            }];
            config.items = [selectedId];
        }
        
        new TomSelect(selectElement, config);
    });
    
    // Tom Select no campo de projeto
    new TomSelect('#project_id', {
        placeholder: 'Selecione um projeto...',
        allowEmptyOption: true,
        create: false,
        sortField: {
            field: 'text',
            direction: 'asc'
        }
    });
});
</script>
<?php
$scripts = ob_get_clean();

include base_path('views/layouts/app.php');
?>

