<?php 
$clientLeads = $client->leads();
$hasLeads = !empty($clientLeads);
?>
<div class="chat-list chat active-chat">
    <div class="hstack align-items-start mb-7 pb-1 align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center position-relative" style="width: 72px; height: 72px;">
                <i class="ti ti-<?php echo $client->tipo === 'juridica' ? 'building' : 'user'; ?> text-primary fs-4"></i>
                <?php if ($hasLeads): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success" title="Convertido de lead">
                        <i class="ti ti-arrow-right"></i>
                    </span>
                <?php endif; ?>
            </div>
            <div>
                <h6 class="fs-4 mb-0">
                    <?php echo e($client->nome_razao_social); ?>
                    <?php if ($hasLeads): ?>
                        <span class="badge bg-success-subtle text-success ms-2" title="Este cliente foi criado a partir de um lead">
                            <i class="ti ti-arrow-right me-1"></i>De Lead
                        </span>
                    <?php endif; ?>
                </h6>
                <?php if ($client->nome_fantasia): ?>
                    <p class="mb-0"><?php echo e($client->nome_fantasia); ?></p>
                <?php endif; ?>
                <p class="mb-0">
                    <span class="badge bg-<?php echo $client->tipo === 'juridica' ? 'info' : 'primary'; ?>">
                        <?php echo $client->tipo === 'juridica' ? 'Pessoa Jurídica' : 'Pessoa Física'; ?>
                    </span>
                </p>
            </div>
        </div>
    </div>
    <div class="row">
        <?php if ($client->cpf_cnpj): ?>
        <div class="col-6 mb-7">
            <p class="mb-1 fs-2"><?php echo $client->tipo === 'juridica' ? 'CNPJ' : 'CPF'; ?></p>
            <h6 class="fw-semibold mb-0"><?php echo e($client->cpf_cnpj); ?></h6>
        </div>
        <?php endif; ?>
        <?php if ($client->email): ?>
        <div class="col-6 mb-7">
            <p class="mb-1 fs-2">Email</p>
            <h6 class="fw-semibold mb-0"><?php echo e($client->email); ?></h6>
        </div>
        <?php endif; ?>
        <?php if ($client->telefone): ?>
        <div class="col-6 mb-7">
            <p class="mb-1 fs-2">Telefone</p>
            <h6 class="fw-semibold mb-0"><?php echo e($client->telefone); ?></h6>
        </div>
        <?php endif; ?>
        <?php if ($client->celular): ?>
        <div class="col-6 mb-7">
            <p class="mb-1 fs-2">Celular</p>
            <h6 class="fw-semibold mb-0"><?php echo e($client->celular); ?></h6>
        </div>
        <?php endif; ?>
        <?php if ($client->instagram): ?>
        <div class="col-6 mb-7">
            <p class="mb-1 fs-2">Instagram</p>
            <h6 class="fw-semibold mb-0">
                <a href="https://instagram.com/<?php echo e(ltrim($client->instagram, '@')); ?>" target="_blank">
                    <?php echo e($client->instagram); ?>
                    <i class="ti ti-external-link ms-1"></i>
                </a>
            </h6>
        </div>
        <?php endif; ?>
        <?php if ($client->endereco || $client->numero || $client->bairro || $client->cidade || $client->estado || $client->cep): ?>
        <div class="col-12 mb-9">
            <p class="mb-1 fs-2">Endereço</p>
            <h6 class="fw-semibold mb-0">
                <?php
                $enderecoParts = array_filter([
                    $client->endereco,
                    $client->numero,
                    $client->complemento,
                    $client->bairro,
                    $client->cidade,
                    $client->estado,
                    $client->cep
                ]);
                echo e(implode(', ', $enderecoParts));
                ?>
            </h6>
        </div>
        <?php endif; ?>
        <div class="col-6 mb-7">
            <p class="mb-1 fs-2">Score</p>
            <h6 class="fw-semibold mb-0">
                <span class="badge bg-<?php echo $client->score >= 70 ? 'success' : ($client->score >= 40 ? 'warning' : 'danger'); ?>">
                    <?php echo $client->score; ?>
                </span>
            </h6>
        </div>
    </div>
    <?php if ($client->observacoes): ?>
    <div class="border-bottom pb-7 mb-4">
        <p class="mb-2 fs-2">Observações</p>
        <p class="mb-0 text-dark"><?php echo nl2br(e($client->observacoes)); ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Relacionamentos -->
    <?php if (!empty($leads)): ?>
    <div class="border-bottom pb-7 mb-4">
        <p class="mb-2 fs-2">
            <i class="ti ti-arrow-right me-2"></i>
            Leads que Originaram este Cliente
        </p>
        <div class="alert alert-info mb-3">
            <i class="ti ti-info-circle me-2"></i>
            Este cliente foi criado a partir de <strong><?php echo count($leads); ?> lead(s)</strong>.
        </div>
        <ul class="list-unstyled mb-0">
            <?php foreach ($leads as $lead): ?>
                <li class="mb-3 p-3 bg-light rounded">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <a href="<?php echo url('/leads/' . $lead->id); ?>" class="text-primary fw-semibold d-flex align-items-center mb-1">
                                <i class="ti ti-arrow-right me-2"></i>
                                <?php echo e($lead->nome); ?>
                            </a>
                            <div class="ms-4">
                                <?php if ($lead->email): ?>
                                    <small class="text-muted d-block">
                                        <i class="ti ti-mail me-1"></i><?php echo e($lead->email); ?>
                                    </small>
                                <?php endif; ?>
                                <?php if ($lead->origem): ?>
                                    <small class="text-muted d-block">
                                        <i class="ti ti-source-code me-1"></i>Origem: <?php echo e($lead->origem); ?>
                                    </small>
                                <?php endif; ?>
                                <small class="text-muted d-block">
                                    <i class="ti ti-calendar me-1"></i>
                                    Cadastrado em: <?php echo date('d/m/Y H:i', strtotime($lead->created_at)); ?>
                                </small>
                            </div>
                        </div>
                        <div>
                            <span class="badge bg-info">Lead #<?php echo $lead->id; ?></span>
                            <?php if ($lead->score_potencial): ?>
                                <span class="badge bg-warning ms-1">Score: <?php echo $lead->score_potencial; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php else: ?>
    <div class="border-bottom pb-7 mb-4">
        <p class="mb-2 fs-2">
            <i class="ti ti-arrow-right me-2"></i>
            Leads Associados
        </p>
        <p class="text-muted mb-0">
            <i class="ti ti-info-circle me-2"></i>
            Este cliente não foi criado a partir de um lead. Foi cadastrado manualmente.
        </p>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($proposals)): ?>
    <div class="border-bottom pb-7 mb-4">
        <p class="mb-2 fs-2">Propostas</p>
        <ul class="list-unstyled mb-0">
            <?php foreach ($proposals as $proposal): ?>
                <li class="mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><?php echo e($proposal->titulo); ?></span>
                        <span class="badge bg-<?php echo $proposal->status === 'aceita' ? 'success' : ($proposal->status === 'recusada' ? 'danger' : 'warning'); ?>">
                            <?php echo ucfirst($proposal->status); ?>
                        </span>
                    </div>
                    <?php if ($proposal->valor): ?>
                        <small class="text-muted">R$ <?php echo number_format((float)$proposal->valor, 2, ',', '.'); ?></small>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($contacts)): ?>
    <div class="border-bottom pb-7 mb-4">
        <p class="mb-2 fs-2">Últimos Contatos</p>
        <ul class="list-unstyled mb-0">
            <?php foreach (array_slice($contacts, 0, 5) as $contact): ?>
                <li class="mb-2">
                    <div>
                        <strong><?php echo e($contact->nome); ?></strong>
                        <?php if ($contact->cargo): ?>
                            <small class="text-muted"> - <?php echo e($contact->cargo); ?></small>
                        <?php endif; ?>
                    </div>
                    <?php if ($contact->data_contato): ?>
                        <small class="text-muted">
                            <?php echo date('d/m/Y', strtotime($contact->data_contato)); ?>
                            <?php if ($contact->hora_contato): ?>
                                às <?php echo e($contact->hora_contato); ?>
                            <?php endif; ?>
                        </small>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <div class="d-flex align-items-center gap-6">
        <a href="<?php echo url('/clients/' . $client->id . '/edit'); ?>" class="btn btn-primary">Editar</a>
        <button class="btn bg-danger-subtle text-danger" onclick="deleteClient(<?php echo $client->id; ?>)">Excluir</button>
    </div>
</div>

