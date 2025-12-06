<?php
ob_start();
$title = $title ?? 'Preview da Proposta';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?></title>
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
            
            <div>
                <strong>Proposta elaborada por:</strong><br>
                <?php 
                $user = auth()->user();
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
                             style="background: white; border: 2px solid <?php echo $form->is_selected ? '#007bff' : '#e0e0e0'; ?>; border-radius: 8px; padding: 20px; position: relative;">
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
                                <?php if (!empty($testimonial['photo_url'])): ?>
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
                                    <?php if (!empty($testimonial['company'])): ?>
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
                $user = auth()->user();
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
                <h3 style="color: white; margin-bottom: 15px;">Vamos trabalhar juntos?</h3>
                <p style="color: #ccc; margin-bottom: 30px;">Será um prazer realizar o seu projeto!</p>
                
                <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <button type="button" class="btn btn-secondary" onclick="enviarMensagem()" style="padding: 12px 30px; font-size: 16px;">
                        Enviar mensagem
                    </button>
                    <button type="button" class="btn btn-success" onclick="aceitarProposta()" style="padding: 12px 30px; font-size: 16px; background: #28a745; border-color: #28a745;">
                        Aceitar proposta
                    </button>
                    <button type="button" class="btn btn-danger" onclick="recusarProposta()" style="padding: 12px 30px; font-size: 16px; background: #dc3545; border-color: #dc3545;">
                        Recusar proposta
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function enviarMensagem() {
        alert('Funcionalidade de enviar mensagem será implementada em breve.');
    }
    
    function aceitarProposta() {
        if (confirm('Tem certeza que deseja aceitar esta proposta?')) {
            // TODO: Implementar endpoint para aceitar proposta
            alert('Funcionalidade de aceitar proposta será implementada em breve.');
        }
    }
    
    function recusarProposta() {
        if (confirm('Tem certeza que deseja recusar esta proposta?')) {
            // TODO: Implementar endpoint para recusar proposta
            alert('Funcionalidade de recusar proposta será implementada em breve.');
        }
    }
    </script>
</body>
</html>

<?php
$content = ob_get_clean();
// Para preview, não usa layout padrão
echo $content;
?>

