<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title ?? 'Proposta Comercial'); ?></title>
    <link rel="stylesheet" href="<?php echo asset('tema/assets/css/styles.css'); ?>">
    <style>
        body {
            background: #f5f5f5;
            font-family: Arial, sans-serif;
        }
        .proposal-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
        }
        .proposal-header {
            margin-bottom: 30px;
        }
        .proposal-cover {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .proposal-title {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .proposal-info {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .service-item {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .condition-item {
            border-left: 4px solid #007bff;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        .d-flex {
            display: flex;
        }
        .justify-content-between {
            justify-content: space-between;
        }
        .mb-2 {
            margin-bottom: 0.5rem;
        }
        .mb-3 {
            margin-bottom: 1rem;
        }
        .mb-4 {
            margin-bottom: 1.5rem;
        }
        .mb-0 {
            margin-bottom: 0;
        }
        .text-muted {
            color: #6c757d;
        }
        .text-success {
            color: #28a745;
        }
        .fs-4 {
            font-size: 1.5rem;
        }
        hr {
            border: none;
            border-top: 1px solid #dee2e6;
            margin: 1rem 0;
        }
        @media print {
            body {
                background: white;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="proposal-container">
        <?php if (!empty($proposal->imagem_capa)): ?>
            <img src="<?php echo asset($proposal->imagem_capa); ?>" alt="Capa" class="proposal-cover" style="width: 100%; height: 300px; object-fit: cover; border-radius: 5px; margin-bottom: 30px;">
        <?php endif; ?>
        
        <div class="proposal-header">
            <h1 class="proposal-title"><?php echo e($proposal->titulo); ?></h1>
            <p class="text-muted">Proposta - <?php echo e($proposal->numero_proposta ?? 'N/A'); ?></p>
            <?php if ($proposal->data_validade): ?>
                <p class="text-muted">Data de Vencimento: <?php echo date('d/m/Y', strtotime($proposal->data_validade)); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="proposal-info">
            <?php if ($client): ?>
                <div class="mb-3">
                    <strong>Cliente:</strong><br>
                    <?php echo e($client->nome_razao_social); ?>
                    <?php if ($client->email): ?>
                        <br><small class="text-muted"><?php echo e($client->email); ?></small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($lead): ?>
                <div class="mb-3">
                    <strong>Contato:</strong><br>
                    <?php echo e($lead->nome); ?>
                    <?php if ($lead->email): ?>
                        <br><small class="text-muted"><?php echo e($lead->email); ?></small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div>
                <strong>Proposta elaborada por:</strong><br>
                <?php 
                // Busca o usuário que criou a proposta
                $user = \App\Models\User::find($proposal->user_id);
                echo e($user->name ?? 'Sistema');
                ?>
            </div>
        </div>
        
        <?php if (!empty($proposal->video_youtube)): ?>
            <div class="mb-4" style="margin-bottom: 30px;">
                <?php
                // Extrai o ID do vídeo do YouTube
                $videoId = null;
                if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $proposal->video_youtube, $matches)) {
                    $videoId = $matches[1];
                }
                ?>
                <?php if ($videoId): ?>
                    <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 5px;">
                        <iframe 
                            src="https://www.youtube.com/embed/<?php echo $videoId; ?>" 
                            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen>
                        </iframe>
                    </div>
                <?php else: ?>
                    <p class="text-muted">URL do vídeo inválida.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($proposal->apresentacao): ?>
            <div class="mb-4">
                <h3>Apresentação</h3>
                <p><?php echo nl2br(e($proposal->apresentacao)); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($proposal->objetivo): ?>
            <div class="mb-4">
                <h3>Objetivo</h3>
                <p><?php echo nl2br(e($proposal->objetivo)); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($services)): ?>
            <div class="mb-4">
                <h3>Serviços</h3>
                <?php foreach ($services as $service): ?>
                    <div class="service-item">
                        <h5><?php echo e($service->titulo); ?></h5>
                        <?php if ($service->descricao): ?>
                            <p><?php echo nl2br(e($service->descricao)); ?></p>
                        <?php endif; ?>
                        <p class="mb-0">
                            <strong>Quantidade:</strong> <?php echo $service->quantidade; ?> | 
                            <strong>Valor:</strong> R$ <?php echo number_format($service->valor_total, 2, ',', '.'); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($proposal->duracao_dias || $proposal->data_estimada_conclusao): ?>
            <div class="mb-4">
                <h3>Prazo</h3>
                <?php if ($proposal->duracao_dias): ?>
                    <p><strong>Duração:</strong> <?php echo $proposal->duracao_dias; ?> dias corridos</p>
                <?php endif; ?>
                <?php if ($proposal->data_estimada_conclusao): ?>
                    <p><strong>Data estimada para conclusão:</strong> <?php echo date('d/m/Y', strtotime($proposal->data_estimada_conclusao)); ?> iniciando hoje.</p>
                <?php endif; ?>
                <?php if ($proposal->disponibilidade_inicio_imediato): ?>
                    <p class="text-success"><i class="ti ti-check"></i> Disponibilidade para início imediato</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($proposal->forma_pagamento): ?>
            <div class="mb-4">
                <h3>Pagamento</h3>
                <p><strong>Forma de pagamento:</strong> 
                    <?php 
                    $formas = [
                        'a_vista' => 'À vista',
                        'parcelado' => 'Parcelado'
                    ];
                    echo $formas[$proposal->forma_pagamento] ?? $proposal->forma_pagamento;
                    ?>
                </p>
            </div>
        <?php endif; ?>
        
        <?php 
        // Separa condições normais de formas de pagamento
        $normalConditions = [];
        if (!empty($conditions)) {
            foreach ($conditions as $c) {
                if (!$c->isPaymentForm()) {
                    $normalConditions[] = $c;
                }
            }
        }
        $paymentForms = $paymentForms ?? [];
        ?>
        
        <?php if (!empty($paymentForms)): ?>
            <div class="mb-4">
                <h3>Formas de Pagamento</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 20px;">
                    <?php foreach ($paymentForms as $index => $form): ?>
                        <div class="payment-form-card" 
                             data-form-id="<?php echo $form->id; ?>"
                             style="background: white; border: 2px solid <?php echo $form->is_selected ? '#007bff' : '#e0e0e0'; ?>; border-radius: 8px; padding: 20px; cursor: pointer; transition: all 0.3s; position: relative;"
                             onclick="selectPaymentForm(<?php echo $form->id; ?>, this)"
                             onmouseover="if (!this.querySelector('span[style*=\"CONTRATO\"]')) { this.style.borderColor='#007bff'; this.style.boxShadow='0 4px 8px rgba(0,123,255,0.2)'; }"
                             onmouseout="if (!this.querySelector('span[style*=\"CONTRATO\"]')) { this.style.borderColor='<?php echo $form->is_selected ? '#007bff' : '#e0e0e0'; ?>'; this.style.boxShadow='none'; }">
                            <?php if ($form->is_selected): ?>
                                <span style="position: absolute; top: 10px; right: 10px; background: #007bff; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold;">CONTRATO</span>
                            <?php endif; ?>
                            <h4 style="margin-top: 0; margin-bottom: 10px; font-size: 18px; font-weight: 600;">
                                <?php echo ($index + 1) . '° ' . e($form->titulo); ?>
                            </h4>
                            <?php if ($form->descricao): ?>
                                <p style="color: #666; font-size: 14px; margin-bottom: 15px; line-height: 1.5;">
                                    <?php echo nl2br(e($form->descricao)); ?>
                                </p>
                            <?php endif; ?>
                            <div style="margin-top: 15px;">
                                <?php if ($form->valor_original && $form->valor_final): ?>
                                    <div style="margin-bottom: 5px;">
                                        <span style="text-decoration: line-through; color: #999; font-size: 14px;">
                                            R$ <?php echo number_format($form->valor_original, 2, ',', '.'); ?>
                                        </span>
                                    </div>
                                    <div style="font-size: 24px; font-weight: bold; color: #007bff;">
                                        R$ <?php echo number_format($form->valor_final, 2, ',', '.'); ?>
                                    </div>
                                <?php elseif ($form->parcelas && $form->valor_parcela): ?>
                                    <div style="font-size: 18px; font-weight: 600; color: #333;">
                                        <?php echo $form->parcelas; ?>x de R$ <?php echo number_format($form->valor_parcela, 2, ',', '.'); ?>
                                    </div>
                                    <?php if ($form->valor_final): ?>
                                        <div style="font-size: 14px; color: #666; margin-top: 5px;">
                                            Total: R$ <?php echo number_format($form->valor_final, 2, ',', '.'); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php elseif ($form->valor_final): ?>
                                    <div style="font-size: 24px; font-weight: bold; color: #007bff;">
                                        R$ <?php echo number_format($form->valor_final, 2, ',', '.'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($normalConditions)): ?>
            <div class="mb-4">
                <h3>Condições</h3>
                <?php foreach ($normalConditions as $condition): ?>
                    <div class="condition-item">
                        <h5><?php echo e($condition->titulo); ?></h5>
                        <?php if ($condition->descricao): ?>
                            <p class="mb-0"><?php echo nl2br(e($condition->descricao)); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($testimonials)): ?>
            <div class="mb-4">
                <h3>O que nossos clientes dizem</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
                    <?php foreach ($testimonials as $testimonial): ?>
                        <div style="background: #f9f9f9; border-radius: 8px; padding: 25px; border-left: 4px solid #007bff;">
                            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                                <?php if ($testimonial['photo_url']): ?>
                                    <img src="<?php echo e($testimonial['photo_url']); ?>" 
                                         alt="<?php echo e($testimonial['client_name']); ?>"
                                         style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-right: 15px;">
                                <?php else: ?>
                                    <div style="width: 60px; height: 60px; border-radius: 50%; background: #007bff; color: white; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; margin-right: 15px;">
                                        <?php echo strtoupper(substr($testimonial['client_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight: 600; font-size: 16px;"><?php echo e($testimonial['client_name']); ?></div>
                                    <?php if ($testimonial['company']): ?>
                                        <div style="color: #666; font-size: 14px;"><?php echo e($testimonial['company']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p style="color: #333; line-height: 1.6; font-style: italic; margin: 0;">
                                "<?php echo nl2br(e($testimonial['testimonial'])); ?>"
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php 
        // Logos das tecnologias
        $techLogos = [
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
        $technologies = $technologies ?? [];
        ?>
        
        <?php if (!empty($technologies)): ?>
            <div class="mb-4">
                <h3>Tecnologias Utilizadas</h3>
                <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 20px;">
                    <?php foreach ($technologies as $tech): ?>
                        <div style="display: flex; align-items: center; gap: 8px; padding: 10px 15px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e0e0e0;">
                            <?php if (isset($techLogos[$tech])): ?>
                                <img src="<?php echo $techLogos[$tech]; ?>" alt="<?php echo e($tech); ?>" style="width: 24px; height: 24px;">
                            <?php endif; ?>
                            <span style="font-weight: 500;"><?php echo e($tech); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php $roadmapSteps = $roadmapSteps ?? []; ?>
        <?php if (!empty($roadmapSteps)): ?>
            <div class="mb-4">
                <h3>Cronograma / Roadmap</h3>
                <div style="margin-top: 20px;">
                    <?php foreach ($roadmapSteps as $index => $step): ?>
                        <div style="display: flex; align-items: flex-start; margin-bottom: 20px;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: #007bff; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">
                                <?php echo $index + 1; ?>
                            </div>
                            <div style="flex: 1; margin-left: 15px; padding-bottom: 20px; <?php echo $index < count($roadmapSteps) - 1 ? 'border-left: 2px solid #e0e0e0; margin-left: 35px; padding-left: 35px;' : ''; ?>">
                                <div style="margin-top: -5px;">
                                    <h4 style="margin: 0 0 5px 0; font-size: 18px; font-weight: 600;"><?php echo e($step['title']); ?></h4>
                                    <?php if (!empty($step['estimated_date'])): ?>
                                        <span style="display: inline-block; background: #e8f4fd; color: #0066cc; padding: 3px 10px; border-radius: 12px; font-size: 13px; margin-bottom: 8px;">
                                            <i class="ti ti-calendar" style="margin-right: 4px;"></i>
                                            <?php echo date('d/m/Y', strtotime($step['estimated_date'])); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($step['description'])): ?>
                                        <p style="color: #666; margin: 10px 0 0 0; line-height: 1.6;"><?php echo nl2br(e($step['description'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($proposal->observacoes): ?>
            <div class="mb-4">
                <h3>Observações</h3>
                <p><?php echo nl2br(e($proposal->observacoes)); ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Footer com botões de ação -->
        <div class="proposal-footer" style="background: #1a1a1a; color: white; padding: 40px; margin-top: 50px; border-radius: 5px; text-align: center;">
            <div style="margin-bottom: 20px;">
                <?php 
                $user = \App\Models\User::find($proposal->user_id);
                if ($user):
                ?>
                    <div style="margin-bottom: 15px;">
                        <strong style="font-size: 18px;"><?php echo e($user->name ?? 'Sistema'); ?></strong>
                    </div>
                    <?php if ($user->email): ?>
                        <div style="color: #ccc; font-size: 14px;">
                            <?php echo e($user->email); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <div style="margin-top: 30px;">
                <?php if ($proposal->status === 'aprovada'): ?>
                    <div style="background: rgba(40, 167, 69, 0.2); padding: 30px; border-radius: 10px; margin-bottom: 20px;">
                        <h3 style="color: #28a745; margin-bottom: 10px;">✅ Proposta Aceita!</h3>
                        <p style="color: #ccc;">Você aceitou esta proposta. Em breve entraremos em contato!</p>
                    </div>
                    <button type="button" class="btn btn-secondary no-print" onclick="enviarMensagem()" style="padding: 12px 30px; font-size: 16px;">
                        <i class="ti ti-message"></i> Enviar mensagem
                    </button>
                <?php elseif ($proposal->status === 'rejeitada'): ?>
                    <div style="background: rgba(220, 53, 69, 0.2); padding: 30px; border-radius: 10px; margin-bottom: 20px;">
                        <h3 style="color: #dc3545; margin-bottom: 10px;">❌ Proposta Recusada</h3>
                        <p style="color: #ccc;">Você recusou esta proposta. Agradecemos o retorno!</p>
                    </div>
                    <button type="button" class="btn btn-secondary no-print" onclick="enviarMensagem()" style="padding: 12px 30px; font-size: 16px;">
                        <i class="ti ti-message"></i> Entrar em contato
                    </button>
                <?php else: ?>
                    <h3 style="color: white; margin-bottom: 15px;">Vamos trabalhar juntos?</h3>
                    <p style="color: #ccc; margin-bottom: 30px;">Será um prazer realizar o seu projeto!</p>
                    
                    <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                        <button type="button" class="btn btn-secondary no-print" onclick="enviarMensagem()" style="padding: 12px 30px; font-size: 16px;">
                            <i class="ti ti-message"></i> Enviar mensagem
                        </button>
                        <button type="button" class="btn btn-success no-print" onclick="aceitarProposta()" style="padding: 12px 30px; font-size: 16px; background: #28a745; border-color: #28a745;">
                            <i class="ti ti-check"></i> Aceitar proposta
                        </button>
                        <button type="button" class="btn btn-danger no-print" onclick="recusarProposta()" style="padding: 12px 30px; font-size: 16px; background: #dc3545; border-color: #dc3545;">
                            <i class="ti ti-x"></i> Recusar proposta
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    const proposalId = <?php echo $proposal->id; ?>;
    const proposalToken = '<?php echo $proposal->token_publico; ?>';
    const userWhatsapp = '<?php echo $user->telefone ?? $user->celular ?? ''; ?>';
    const userEmail = '<?php echo $user->email ?? ''; ?>';
    const proposalTitle = '<?php echo addslashes($proposal->titulo); ?>';
    
    // URLs dinâmicas - usa APP_URL do .env
    const urlAccept = `<?php echo url("/proposals"); ?>/${proposalId}/public/${proposalToken}/accept`;
    const urlReject = `<?php echo url("/proposals"); ?>/${proposalId}/public/${proposalToken}/reject`;
    const urlSelectPayment = `<?php echo url("/proposals"); ?>/${proposalId}/public/${proposalToken}/select-payment`;
    
    function enviarMensagem() {
        // Tenta abrir WhatsApp se houver número configurado
        if (userWhatsapp) {
            const numero = userWhatsapp.replace(/\D/g, ''); // Remove caracteres não numéricos
            const mensagem = encodeURIComponent(`Olá! Estou interessado na proposta "${proposalTitle}". Gostaria de esclarecer algumas dúvidas.`);
            const urlWhatsapp = `https://wa.me/55${numero}?text=${mensagem}`;
            window.open(urlWhatsapp, '_blank');
        } else if (userEmail) {
            // Fallback para email
            const assunto = encodeURIComponent(`Dúvida sobre proposta ${proposalTitle}`);
            const corpo = encodeURIComponent(`Olá! Visualizei a proposta "${proposalTitle}" e gostaria de esclarecer algumas dúvidas.\n\nAguardo retorno.`);
            window.location.href = `mailto:${userEmail}?subject=${assunto}&body=${corpo}`;
        } else {
            alert('Não há informações de contato disponíveis. Por favor, entre em contato pelos canais tradicionais.');
        }
    }
    
    async function aceitarProposta() {
        if (!confirm('🎉 Confirma que deseja ACEITAR esta proposta?\n\nAo confirmar, o responsável será notificado e entraremos em contato em breve!')) {
            return;
        }
        
        try {
            const response = await fetch(urlAccept, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('✅ ' + data.message);
                location.reload();
            } else {
                alert('❌ ' + (data.message || 'Erro ao aceitar proposta'));
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('❌ Erro ao processar sua resposta. Tente novamente.');
        }
    }
    
    async function recusarProposta() {
        const motivo = prompt('Poderia nos informar o motivo da recusa? (Opcional)\n\nIsso nos ajuda a melhorar nossas propostas futuras.');
        
        if (motivo === null) {
            return; // Usuário cancelou
        }
        
        if (!confirm('Confirma que deseja RECUSAR esta proposta?')) {
            return;
        }
        
        try {
            const response = await fetch(urlReject, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    motivo: motivo || null
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('✅ ' + data.message);
                location.reload();
            } else {
                alert('❌ ' + (data.message || 'Erro ao recusar proposta'));
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('❌ Erro ao processar sua resposta. Tente novamente.');
        }
    }
    
    function selectPaymentForm(conditionId, cardElement) {
        fetch(urlSelectPayment, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                condition_id: conditionId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Atualiza visualmente os cards
                document.querySelectorAll('.payment-form-card').forEach(card => {
                    card.style.borderColor = '#e0e0e0';
                    card.style.boxShadow = 'none';
                    const existingBadge = card.querySelector('span[style*="CONTRATO"]');
                    if (existingBadge) {
                        existingBadge.remove();
                    }
                });
                
                // Marca o card selecionado
                const selectedCard = cardElement || document.querySelector(`.payment-form-card[data-form-id="${conditionId}"]`);
                if (selectedCard) {
                    selectedCard.style.borderColor = '#007bff';
                    selectedCard.style.boxShadow = '0 4px 8px rgba(0,123,255,0.2)';
                    
                    // Adiciona badge CONTRATO
                    const badge = document.createElement('span');
                    badge.style.cssText = 'position: absolute; top: 10px; right: 10px; background: #007bff; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold;';
                    badge.textContent = 'CONTRATO';
                    selectedCard.appendChild(badge);
                }
                
                alert('✅ Forma de pagamento selecionada!');
            } else {
                alert('Erro: ' + (data.message || 'Erro ao selecionar forma de pagamento'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao selecionar forma de pagamento.');
        });
    }
    </script>
</body>
</html>