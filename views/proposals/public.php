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
        .pricing-summary {
            background: #f0f0ff;
            padding: 20px;
            border-radius: 5px;
            margin: 30px 0;
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
                
                <div class="pricing-summary">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <strong>R$ <?php echo number_format($proposal->subtotal ?? 0, 2, ',', '.'); ?></strong>
                    </div>
                    <?php if ($proposal->desconto_valor > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Desconto (<?php echo number_format($proposal->desconto_percentual ?? 0, 2, ',', '.'); ?>%):</span>
                            <strong>- R$ <?php echo number_format($proposal->desconto_valor, 2, ',', '.'); ?></strong>
                        </div>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span><strong>Total:</strong></span>
                        <strong class="fs-4">R$ <?php echo number_format($proposal->total ?? 0, 2, ',', '.'); ?></strong>
                    </div>
                </div>
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
        
        <?php if (!empty($conditions)): ?>
            <div class="mb-4">
                <h3>Condições</h3>
                <?php foreach ($conditions as $condition): ?>
                    <div class="condition-item">
                        <h5><?php echo e($condition->titulo); ?></h5>
                        <?php if ($condition->descricao): ?>
                            <p class="mb-0"><?php echo nl2br(e($condition->descricao)); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
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
            const response = await fetch(`/yggracrm/proposals/${proposalId}/public/${proposalToken}/accept`, {
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
            const response = await fetch(`/yggracrm/proposals/${proposalId}/public/${proposalToken}/reject`, {
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
    </script>
</body>
</html>
