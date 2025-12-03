<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title ?? 'Proposta Comercial'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo asset('tema/assets/css/styles.css'); ?>">
    
    <style>
        body {
            background: #f5f5f5;
            padding: 20px 0;
        }
        .proposal-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .company-header {
            text-align: center;
            border-bottom: 3px solid #5d87ff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-logo {
            max-width: 200px;
            max-height: 80px;
            margin-bottom: 15px;
        }
        .proposal-title {
            color: #2a3547;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .proposal-number {
            color: #5d87ff;
            font-size: 18px;
            font-weight: 500;
        }
        .info-section {
            margin: 30px 0;
        }
        .info-label {
            font-weight: 600;
            color: #2a3547;
            margin-bottom: 5px;
        }
        .info-value {
            color: #5a6a85;
            margin-bottom: 15px;
        }
        .services-table {
            width: 100%;
            margin: 30px 0;
        }
        .services-table th {
            background: #5d87ff;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .services-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        .total-section {
            text-align: right;
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .total-label {
            font-size: 18px;
            font-weight: 600;
            color: #2a3547;
        }
        .total-value {
            font-size: 24px;
            font-weight: 700;
            color: #5d87ff;
        }
        .conditions-section {
            margin: 30px 0;
            padding: 20px;
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            border-radius: 5px;
        }
        .footer-note {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #7c8fac;
            font-size: 14px;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .proposal-container {
                box-shadow: none;
                padding: 20px;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="proposal-container">
        <!-- Cabeçalho da Empresa -->
        <div class="company-header">
            <?php if (!empty($companyLogo)): ?>
                <img src="<?php echo asset($companyLogo); ?>" alt="<?php echo e($companyName); ?>" class="company-logo">
            <?php endif; ?>
            <h1 class="company-name"><?php echo e($companyName); ?></h1>
        </div>

        <!-- Título da Proposta -->
        <div class="text-center mb-4">
            <h2 class="proposal-title"><?php echo e($proposal->titulo); ?></h2>
            <p class="proposal-number">Proposta: <?php echo e($proposal->numero_proposta); ?></p>
            <?php if ($proposal->data_validade): ?>
                <p class="text-muted">Válida até: <?php echo date('d/m/Y', strtotime($proposal->data_validade)); ?></p>
            <?php endif; ?>
        </div>

        <!-- Informações do Cliente -->
        <div class="info-section">
            <h4 style="color: #2a3547; border-bottom: 2px solid #5d87ff; padding-bottom: 10px; margin-bottom: 15px;">
                Informações do Cliente
            </h4>
            <?php if ($client): ?>
                <div class="info-label">Cliente:</div>
                <div class="info-value"><?php echo e($client->nome_razao_social); ?></div>
                
                <?php if ($client->email): ?>
                    <div class="info-label">E-mail:</div>
                    <div class="info-value"><?php echo e($client->email); ?></div>
                <?php endif; ?>
                
                <?php if ($client->telefone || $client->celular): ?>
                    <div class="info-label">Telefone:</div>
                    <div class="info-value">
                        <?php echo e($client->telefone ?: $client->celular); ?>
                    </div>
                <?php endif; ?>
            <?php elseif ($lead): ?>
                <div class="info-label">Contato:</div>
                <div class="info-value"><?php echo e($lead->nome); ?></div>
                
                <?php if ($lead->email): ?>
                    <div class="info-label">E-mail:</div>
                    <div class="info-value"><?php echo e($lead->email); ?></div>
                <?php endif; ?>
                
                <?php if ($lead->telefone): ?>
                    <div class="info-label">Telefone:</div>
                    <div class="info-value"><?php echo e($lead->telefone); ?></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Descrição -->
        <?php if (!empty($proposal->descricao)): ?>
            <div class="info-section">
                <h4 style="color: #2a3547; border-bottom: 2px solid #5d87ff; padding-bottom: 10px; margin-bottom: 15px;">
                    Descrição
                </h4>
                <div><?php echo nl2br(e($proposal->descricao)); ?></div>
            </div>
        <?php endif; ?>

        <!-- Serviços/Produtos -->
        <?php if (!empty($services)): ?>
            <div class="info-section">
                <h4 style="color: #2a3547; border-bottom: 2px solid #5d87ff; padding-bottom: 10px; margin-bottom: 15px;">
                    Serviços/Produtos
                </h4>
                <table class="services-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Descrição</th>
                            <th style="text-align: center;">Qtd</th>
                            <th style="text-align: right;">Valor Unit.</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td><?php echo e($service->nome); ?></td>
                                <td><?php echo e($service->descricao ?? '-'); ?></td>
                                <td style="text-align: center;"><?php echo e($service->quantidade); ?></td>
                                <td style="text-align: right;">R$ <?php echo number_format($service->valor_unitario, 2, ',', '.'); ?></td>
                                <td style="text-align: right;">R$ <?php echo number_format($service->valor_total, 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Valores Totais -->
        <div class="total-section">
            <?php if ($proposal->subtotal > 0): ?>
                <div class="mb-2">
                    <span class="info-label">Subtotal:</span>
                    <span class="info-value">R$ <?php echo number_format($proposal->subtotal, 2, ',', '.'); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($proposal->desconto_valor > 0): ?>
                <div class="mb-2">
                    <span class="info-label">Desconto:</span>
                    <span class="info-value text-danger">- R$ <?php echo number_format($proposal->desconto_valor, 2, ',', '.'); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="mt-3 pt-3" style="border-top: 2px solid #5d87ff;">
                <span class="total-label">Valor Total:</span><br>
                <span class="total-value">R$ <?php echo number_format($proposal->total, 2, ',', '.'); ?></span>
            </div>
        </div>

        <!-- Forma de Pagamento -->
        <?php if (!empty($proposal->forma_pagamento)): ?>
            <div class="info-section">
                <h4 style="color: #2a3547; border-bottom: 2px solid #5d87ff; padding-bottom: 10px; margin-bottom: 15px;">
                    Forma de Pagamento
                </h4>
                <div><?php echo nl2br(e($proposal->forma_pagamento)); ?></div>
            </div>
        <?php endif; ?>

        <!-- Condições -->
        <?php if (!empty($conditions)): ?>
            <div class="conditions-section">
                <h5 style="color: #2a3547; margin-bottom: 15px;">Condições Gerais</h5>
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($conditions as $condition): ?>
                        <li style="margin-bottom: 8px;"><?php echo e($condition->descricao); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Observações -->
        <?php if (!empty($proposal->observacoes)): ?>
            <div class="info-section">
                <h4 style="color: #2a3547; border-bottom: 2px solid #5d87ff; padding-bottom: 10px; margin-bottom: 15px;">
                    Observações
                </h4>
                <div><?php echo nl2br(e($proposal->observacoes)); ?></div>
            </div>
        <?php endif; ?>

        <!-- Rodapé -->
        <div class="footer-note">
            <p>Esta proposta foi gerada eletronicamente e é válida sem assinatura.</p>
            <p>Em caso de dúvidas, entre em contato conosco.</p>
        </div>

        <!-- Botão de Impressão -->
        <div class="text-center mt-4 no-print">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="ti ti-printer"></i> Imprimir Proposta
            </button>
        </div>
    </div>
</body>
</html>

