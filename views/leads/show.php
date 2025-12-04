<?php
$title = 'Detalhes do Lead';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title fw-semibold mb-0">Detalhes do Lead</h4>
                    <div class="d-flex gap-2">
                        <a href="<?php echo url('/leads/' . $lead->id . '/edit'); ?>" class="btn btn-primary">
                            <i class="ti ti-edit me-2"></i>
                            Editar
                        </a>
                        <a href="<?php echo url('/leads'); ?>" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-left me-2"></i>
                            Voltar
                        </a>
                    </div>
                </div>

                <div class="row">
                    <!-- Informações Básicas -->
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="ti ti-user me-2"></i>Informações Básicas</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-semibold" style="width: 40%;">Nome:</td>
                                        <td><?php echo e($lead->nome); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Email:</td>
                                        <td><?php echo e($lead->email); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Telefone:</td>
                                        <td><?php echo e($lead->telefone); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Instagram:</td>
                                        <td>
                                            <?php if ($lead->instagram): ?>
                                                <a href="https://instagram.com/<?php echo e(ltrim($lead->instagram, '@')); ?>" target="_blank">
                                                    <?php echo e($lead->instagram); ?>
                                                    <i class="ti ti-external-link ms-1"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Não informado</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php if ($lead->origem_conheceu): ?>
                                    <tr>
                                        <td class="fw-semibold">De onde nos conheceu:</td>
                                        <td>
                                            <span class="badge bg-info"><?php echo e($lead->origem_conheceu); ?></span>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if ($lead->tem_software !== null): ?>
                                    <tr>
                                        <td class="fw-semibold">Já possui software:</td>
                                        <td>
                                            <?php if ($lead->tem_software): ?>
                                                <span class="badge bg-success">Sim</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Não</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if ($lead->investimento_software): ?>
                                    <tr>
                                        <td class="fw-semibold">Investimento:</td>
                                        <td><?php echo e($lead->investimento_software); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if ($lead->tipo_sistema): ?>
                                    <tr>
                                        <td class="fw-semibold">Tipo de Sistema:</td>
                                        <td>
                                            <?php
                                            $tipos = [
                                                'interno' => 'Uso Interno',
                                                'cliente' => 'Para Cliente',
                                                'saas' => 'SaaS'
                                            ];
                                            echo $tipos[$lead->tipo_sistema] ?? $lead->tipo_sistema;
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if ($lead->plataforma_app): ?>
                                    <tr>
                                        <td class="fw-semibold">Plataforma App:</td>
                                        <td>
                                            <?php
                                            $plataformas = [
                                                'ios_android' => 'iOS e Android',
                                                'ios' => 'Apenas iOS',
                                                'android' => 'Apenas Android',
                                                'nenhum' => 'Não precisa'
                                            ];
                                            echo $plataformas[$lead->plataforma_app] ?? $lead->plataforma_app;
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td class="fw-semibold">Ramo:</td>
                                        <td><?php echo e($lead->ramo ?? 'Não informado'); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Respostas do Quiz -->
                    <?php 
                    // Busca respostas do quiz
                    $hasQuizResponses = !empty($quizResponses) && is_array($quizResponses) && count($quizResponses) > 0;
                    if ($hasQuizResponses || ($lead->origem && str_starts_with($lead->origem, 'quiz_'))): 
                    ?>
                    <div class="col-md-6">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="ti ti-file-question me-2"></i>
                                    Respostas do Quiz
                                    <?php if ($quiz): ?>
                                        : <?php echo e($quiz->name); ?>
                                    <?php elseif ($lead->origem && str_starts_with($lead->origem, 'quiz_')): ?>
                                        (<?php echo e(str_replace('quiz_', '', $lead->origem)); ?>)
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($hasQuizResponses): ?>
                                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th style="width: 40%;">Pergunta</th>
                                                    <th>Resposta</th>
                                                    <th style="width: 10%;" class="text-center">Pontos</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($quizResponses as $response): ?>
                                                    <?php 
                                                    $step = $response->step();
                                                    $pergunta = $step ? $step->title : ($response->field_name ?? 'Pergunta');
                                                    ?>
                                                    <tr>
                                                        <td class="fw-semibold small"><?php echo e($pergunta); ?></td>
                                                        <td class="small"><?php echo e($response->response); ?></td>
                                                        <td class="text-center">
                                                            <span class="badge bg-info"><?php echo $response->points; ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info mb-0">
                                        <i class="ti ti-info-circle me-2"></i>
                                        Este lead foi criado a partir de um quiz, mas as respostas não foram encontradas.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Análise da IA -->
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="ti ti-brain me-2"></i>Análise da IA</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-semibold" style="width: 40%;">Score Potencial:</td>
                                        <td>
                                            <div class="progress" style="height: 25px;">
                                                <div class="progress-bar <?php 
                                                    echo $lead->score_potencial >= 70 ? 'bg-success' : 
                                                        ($lead->score_potencial >= 40 ? 'bg-warning' : 'bg-danger'); 
                                                ?>" 
                                                role="progressbar" 
                                                style="width: <?php echo $lead->score_potencial; ?>%">
                                                    <?php echo $lead->score_potencial; ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php if ($lead->valor_oportunidade && $lead->valor_oportunidade > 0): ?>
                                    <tr>
                                        <td class="fw-semibold">Valor da Oportunidade:</td>
                                        <td>
                                            <h5 class="mb-0 text-success">
                                                <i class="ti ti-currency-dollar me-2"></i>
                                                R$ <?php echo number_format((float)$lead->valor_oportunidade, 2, ',', '.'); ?>
                                            </h5>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td class="fw-semibold">Urgência:</td>
                                        <td>
                                            <?php
                                            $urgenciaClass = match($lead->urgencia) {
                                                'alta' => 'danger',
                                                'media' => 'warning',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?php echo $urgenciaClass; ?>">
                                                <?php echo ucfirst($lead->urgencia); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Etapa do Funil:</td>
                                        <td>
                                            <?php
                                            $etapaLabels = [
                                                'interessados' => 'Interessados',
                                                'negociacao_proposta' => 'Negociação e Proposta',
                                                'fechamento' => 'Fechamento'
                                            ];
                                            $etapaClass = match($lead->etapa_funil ?? 'interessados') {
                                                'fechamento' => 'success',
                                                'negociacao_proposta' => 'warning',
                                                default => 'info'
                                            };
                                            ?>
                                            <span class="badge bg-<?php echo $etapaClass; ?>">
                                                <?php echo $etapaLabels[$lead->etapa_funil ?? 'interessados'] ?? ($lead->etapa_funil ?? 'interessados'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Responsável:</td>
                                        <td>
                                            <?php 
                                            $responsible = $lead->responsible();
                                            if ($responsible):
                                            ?>
                                                <span class="badge bg-primary"><?php echo e($responsible->name); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">Sem responsável</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Origem:</td>
                                        <td><?php echo e($lead->origem ?? 'Não informado'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Faturamento:</td>
                                        <td>
                                            <strong><?php echo e($lead->faturamento_raw); ?></strong>
                                            <small class="text-muted">(<?php echo e($lead->faturamento_categoria); ?>)</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Investimento:</td>
                                        <td>
                                            <strong><?php echo e($lead->invest_raw); ?></strong>
                                            <small class="text-muted">(<?php echo e($lead->invest_categoria); ?>)</small>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resumo da IA -->
                <?php if ($lead->resumo): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card border">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="ti ti-file-text me-2"></i>Resumo da Análise</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-0"><?php echo nl2br(e($lead->resumo)); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tags da IA -->
                <?php 
                $tags = $lead->getTagsAi();
                if (!empty($tags)):
                ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card border">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0"><i class="ti ti-tags me-2"></i>Tags da IA</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($tags as $tag): ?>
                                    <span class="badge bg-primary-subtle text-primary me-2 mb-2" style="font-size: 0.9rem;">
                                        <?php echo e($tag); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>


                <!-- Informações Adicionais -->
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0"><i class="ti ti-info-circle me-2"></i>Informações Adicionais</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-semibold" style="width: 40%;">Objetivo:</td>
                                        <td><?php echo e($lead->objetivo ?? 'Não informado'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Data de Cadastro:</td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($lead->created_at)); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cliente Associado -->
                <?php if ($client): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="ti ti-user-check me-2"></i>Cliente Associado</h5>
                                <a href="<?php echo url('/clients/' . $client->id); ?>" class="btn btn-sm btn-light">
                                    <i class="ti ti-external-link me-1"></i>
                                    Ver Cliente
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info mb-3">
                                    <i class="ti ti-info-circle me-2"></i>
                                    <strong>Este lead foi convertido em cliente.</strong> 
                                    O cliente está disponível em <a href="<?php echo url('/clients'); ?>" class="alert-link">/clients</a>.
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Nome/Razão Social:</strong> <?php echo e($client->nome_razao_social); ?></p>
                                        <?php if ($client->nome_fantasia): ?>
                                            <p><strong>Nome Fantasia:</strong> <?php echo e($client->nome_fantasia); ?></p>
                                        <?php endif; ?>
                                        <p><strong>Tipo:</strong> 
                                            <span class="badge bg-<?php echo $client->tipo === 'juridica' ? 'info' : 'primary'; ?>">
                                                <?php echo $client->tipo === 'juridica' ? 'Pessoa Jurídica' : 'Pessoa Física'; ?>
                                            </span>
                                        </p>
                                        <?php if ($client->cpf_cnpj): ?>
                                            <p><strong>CPF/CNPJ:</strong> <?php echo e($client->cpf_cnpj); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?php if ($client->email): ?>
                                            <p><strong>Email:</strong> <?php echo e($client->email); ?></p>
                                        <?php endif; ?>
                                        <?php if ($client->telefone): ?>
                                            <p><strong>Telefone:</strong> <?php echo e($client->telefone); ?></p>
                                        <?php endif; ?>
                                        <?php if ($client->celular): ?>
                                            <p><strong>Celular:</strong> <?php echo e($client->celular); ?></p>
                                        <?php endif; ?>
                                        <p><strong>Score:</strong> 
                                            <span class="badge bg-<?php echo $client->score >= 70 ? 'success' : ($client->score >= 40 ? 'warning' : 'danger'); ?>">
                                                <?php echo $client->score; ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card border-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Converter Lead em Cliente</h6>
                                        <p class="text-muted mb-0">Este lead ainda não foi convertido em cliente.</p>
                                    </div>
                                    <button type="button" class="btn btn-primary" onclick="converterEmCliente(<?php echo $lead->id; ?>)">
                                        <i class="ti ti-user-plus me-2"></i>
                                        Converter em Cliente
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Propostas -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="ti ti-file-text me-2"></i>Propostas</h5>
                                <button type="button" class="btn btn-sm btn-primary" onclick="novaProposta(<?php echo $lead->id; ?>)">
                                    <i class="ti ti-plus me-2"></i>
                                    Nova Proposta
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (empty($proposals)): ?>
                                    <p class="text-muted text-center py-4">Nenhuma proposta cadastrada</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Título</th>
                                                    <th>Valor</th>
                                                    <th>Status</th>
                                                    <th>Data Envio</th>
                                                    <th>Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($proposals as $proposal): ?>
                                                    <tr>
                                                        <td><?php echo e($proposal->titulo); ?></td>
                                                        <td>R$ <?php echo number_format((float)$proposal->valor, 2, ',', '.'); ?></td>
                                                        <td>
                                                            <?php
                                                            $statusClass = match($proposal->status) {
                                                                'aprovada' => 'success',
                                                                'rejeitada' => 'danger',
                                                                'enviada' => 'info',
                                                                'cancelada' => 'secondary',
                                                                default => 'warning'
                                                            };
                                                            ?>
                                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                                <?php echo ucfirst($proposal->status); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo $proposal->data_envio ? date('d/m/Y', strtotime($proposal->data_envio)) : '-'; ?></td>
                                                        <td>
                                                            <a href="javascript:void(0);" class="btn btn-sm btn-info" onclick="editarProposta(<?php echo $proposal->id; ?>)">
                                                                <i class="ti ti-edit"></i>
                                                            </a>
                                                        </td>
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

                <!-- Contatos -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="ti ti-phone me-2"></i>Histórico de Contatos</h5>
                                <button type="button" class="btn btn-sm btn-primary" onclick="novoContato(<?php echo $lead->id; ?>)">
                                    <i class="ti ti-plus me-2"></i>
                                    Novo Contato
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (empty($contacts)): ?>
                                    <p class="text-muted text-center py-4">Nenhum contato registrado</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Data/Hora</th>
                                                    <th>Tipo</th>
                                                    <th>Assunto</th>
                                                    <th>Resultado</th>
                                                    <th>Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($contacts as $contact): ?>
                                                    <tr>
                                                        <td>
                                                            <?php echo date('d/m/Y', strtotime($contact->data_contato)); ?>
                                                            <?php if ($contact->hora_contato): ?>
                                                                <br><small class="text-muted"><?php echo date('H:i', strtotime($contact->hora_contato)); ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-info"><?php echo ucfirst($contact->tipo); ?></span>
                                                        </td>
                                                        <td><?php echo e($contact->assunto ?? '-'); ?></td>
                                                        <td>
                                                            <?php
                                                            $resultadoClass = match($contact->resultado) {
                                                                'agendado' => 'success',
                                                                'interessado' => 'primary',
                                                                'nao_interessado' => 'danger',
                                                                'retornar' => 'warning',
                                                                default => 'secondary'
                                                            };
                                                            ?>
                                                            <span class="badge bg-<?php echo $resultadoClass; ?>">
                                                                <?php echo ucfirst(str_replace('_', ' ', $contact->resultado)); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="javascript:void(0);" class="btn btn-sm btn-info" onclick="editarContato(<?php echo $contact->id; ?>)">
                                                                <i class="ti ti-edit"></i>
                                                            </a>
                                                        </td>
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

                <!-- Botões de Ação -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <button type="button" id="btn-reanalyze" class="btn btn-primary">
                                <i class="ti ti-refresh me-2"></i>
                                Reanalisar com IA
                            </button>
                            <a href="<?php echo url('/leads/' . $lead->id . '/edit'); ?>" class="btn btn-info">
                                <i class="ti ti-edit me-2"></i>
                                Editar Lead
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
if (document.getElementById('btn-reanalyze')) {
    document.getElementById('btn-reanalyze').addEventListener('click', function() {
        const btn = this;
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="ti ti-loader me-2"></i>Reanalisando...';
        
        fetch('<?php echo url('/leads/' . $lead->id . '/reanalyze'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Lead reanalisado com sucesso!');
                location.reload();
            } else {
                alert('Erro: ' + data.message);
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao reanalisar lead.');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });
}

function converterEmCliente(leadId) {
    // Abre modal de cadastro de cliente
    const modal = new bootstrap.Modal(document.getElementById('convertLeadModal'));
    modal.show();
    
    // Preenche dados do lead no formulário
    document.getElementById('convert_lead_id').value = leadId;
}

function novaProposta(leadId) {
    // TODO: Implementar modal de nova proposta
    alert('Funcionalidade em desenvolvimento');
}

function editarProposta(proposalId) {
    // TODO: Implementar edição de proposta
    alert('Funcionalidade em desenvolvimento');
}

function novoContato(leadId) {
    // TODO: Implementar modal de novo contato
    alert('Funcionalidade em desenvolvimento');
}

function editarContato(contactId) {
    // TODO: Implementar edição de contato
    alert('Funcionalidade em desenvolvimento');
}

// Submete formulário de conversão
document.getElementById('convertLeadForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const leadId = formData.get('lead_id');
    
    fetch(`<?php echo url('/leads/'); ?>${leadId}/convert-to-client`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(Object.fromEntries(formData))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Cliente cadastrado com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao cadastrar cliente');
    });
});
</script>

<!-- Modal para Converter Lead em Cliente -->
<div class="modal fade" id="convertLeadModal" tabindex="-1" aria-labelledby="convertLeadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="convertLeadForm">
                <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="lead_id" id="convert_lead_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="convertLeadModalLabel">Cadastrar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo *</label>
                            <select name="tipo" class="form-select" required>
                                <option value="fisica">Pessoa Física</option>
                                <option value="juridica">Pessoa Jurídica</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome/Razão Social *</label>
                            <input type="text" name="nome_razao_social" class="form-control" value="<?php echo e($lead->nome); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome Fantasia</label>
                            <input type="text" name="nome_fantasia" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">CPF/CNPJ</label>
                            <input type="text" name="cpf_cnpj" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo e($lead->email); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefone</label>
                            <input type="text" name="telefone" class="form-control" value="<?php echo e($lead->telefone); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Celular</label>
                            <input type="text" name="celular" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Score (0-100)</label>
                            <input type="number" name="score" class="form-control" min="0" max="100" value="<?php echo $lead->score_potencial ?? 50; ?>">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Observações</label>
                            <textarea name="observacoes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-2"></i>
                        Cadastrar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include base_path('views/layouts/app.php');
?>

