<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposta - <?php echo e($proposal->numero_proposta ?? 'N/A'); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #007bff;
        }
        .info-box {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section h3 {
            color: #007bff;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .service-item {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 3px;
        }
        .pricing-summary {
            background: #f0f0ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .pricing-summary table {
            width: 100%;
        }
        .pricing-summary td {
            padding: 5px;
        }
        .pricing-summary .total {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
        }
        .condition-item {
            border-left: 4px solid #007bff;
            padding-left: 10px;
            margin-bottom: 10px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo e($proposal->titulo); ?></h1>
        <p>Proposta - <?php echo e($proposal->numero_proposta ?? 'N/A'); ?></p>
        <?php if ($proposal->data_validade): ?>
            <p>Data de Vencimento: <?php echo date('d/m/Y', strtotime($proposal->data_validade)); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="info-box">
        <?php if ($client): ?>
            <div style="margin-bottom: 10px;">
                <strong>Cliente:</strong> <?php echo e($client->nome_razao_social); ?>
                <?php if ($client->email): ?>
                    <br><strong>Email:</strong> <?php echo e($client->email); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div>
            <strong>Proposta elaborada por:</strong> <?php echo e(auth()->user()->name ?? 'Sistema'); ?>
        </div>
    </div>
    
    <?php if ($proposal->apresentacao): ?>
        <div class="section">
            <h3>Apresentação</h3>
            <p><?php echo nl2br(e($proposal->apresentacao)); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($proposal->objetivo): ?>
        <div class="section">
            <h3>Objetivo</h3>
            <p><?php echo nl2br(e($proposal->objetivo)); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($services)): ?>
        <div class="section">
            <h3>Serviços</h3>
            <?php foreach ($services as $service): ?>
                <div class="service-item">
                    <strong><?php echo e($service->titulo); ?></strong>
                    <?php if ($service->descricao): ?>
                        <p><?php echo nl2br(e($service->descricao)); ?></p>
                    <?php endif; ?>
                    <p style="margin: 0;">
                        <strong>Quantidade:</strong> <?php echo $service->quantidade; ?> | 
                        <strong>Valor:</strong> R$ <?php echo number_format($service->valor_total, 2, ',', '.'); ?>
                    </p>
                </div>
            <?php endforeach; ?>
            
            <div class="pricing-summary">
                <table>
                    <tr>
                        <td>Subtotal:</td>
                        <td style="text-align: right;"><strong>R$ <?php echo number_format($proposal->subtotal ?? 0, 2, ',', '.'); ?></strong></td>
                    </tr>
                    <?php if ($proposal->desconto_valor > 0): ?>
                    <tr>
                        <td>Desconto (<?php echo number_format($proposal->desconto_percentual ?? 0, 2, ',', '.'); ?>%):</td>
                        <td style="text-align: right; color: #28a745;"><strong>- R$ <?php echo number_format($proposal->desconto_valor, 2, ',', '.'); ?></strong></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="total">Total:</td>
                        <td class="total" style="text-align: right;">R$ <?php echo number_format($proposal->total ?? 0, 2, ',', '.'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($proposal->duracao_dias || $proposal->data_estimada_conclusao): ?>
        <div class="section">
            <h3>Prazo</h3>
            <?php if ($proposal->duracao_dias): ?>
                <p><strong>Duração:</strong> <?php echo $proposal->duracao_dias; ?> dias corridos</p>
            <?php endif; ?>
            <?php if ($proposal->data_estimada_conclusao): ?>
                <p><strong>Data estimada para conclusão:</strong> <?php echo date('d/m/Y', strtotime($proposal->data_estimada_conclusao)); ?> iniciando hoje.</p>
            <?php endif; ?>
            <?php if ($proposal->disponibilidade_inicio_imediato): ?>
                <p>✓ Disponibilidade para início imediato</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($proposal->forma_pagamento): ?>
        <div class="section">
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
        <div class="section">
            <h3>Condições</h3>
            <?php foreach ($conditions as $condition): ?>
                <div class="condition-item">
                    <strong><?php echo e($condition->titulo); ?></strong>
                    <?php if ($condition->descricao): ?>
                        <p style="margin: 5px 0 0 0;"><?php echo nl2br(e($condition->descricao)); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($proposal->observacoes): ?>
        <div class="section">
            <h3>Observações</h3>
            <p><?php echo nl2br(e($proposal->observacoes)); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="footer">
        <p>Documento gerado em <?php echo date('d/m/Y H:i:s'); ?></p>
        <p>&copy; <?php echo date('Y'); ?> - Todos os direitos reservados</p>
    </div>
</body>
</html>

