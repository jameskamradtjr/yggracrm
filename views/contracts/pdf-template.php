<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Contrato <?php echo e($contract->numero_contrato); ?></title>
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
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .contract-info {
            margin-bottom: 30px;
        }
        .contract-info p {
            margin: 5px 0;
        }
        .content {
            margin-bottom: 30px;
            text-align: justify;
        }
        .signatures {
            margin-top: 50px;
            display: table;
            width: 100%;
        }
        .signature-block {
            display: table-cell;
            width: 50%;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #333;
        }
        .signature-info {
            margin-top: 10px;
            font-size: 10px;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CONTRATO <?php echo e($contract->numero_contrato); ?></h1>
        <p><?php echo e($contract->titulo); ?></p>
    </div>
    
    <div class="contract-info">
        <?php if ($client): ?>
            <p><strong>Contratante:</strong> <?php echo e($client->nome_razao_social); ?></p>
            <?php if ($client->cpf_cnpj): ?>
                <p><strong>CPF/CNPJ:</strong> <?php echo e($client->cpf_cnpj); ?></p>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($contract->data_inicio): ?>
            <p><strong>Data de Início:</strong> <?php echo date('d/m/Y', strtotime($contract->data_inicio)); ?></p>
        <?php endif; ?>
        
        <?php if ($contract->data_termino): ?>
            <p><strong>Data de Término:</strong> <?php echo date('d/m/Y', strtotime($contract->data_termino)); ?></p>
        <?php endif; ?>
        
        <?php if ($contract->valor_total): ?>
            <p><strong>Valor Total:</strong> R$ <?php echo number_format($contract->valor_total, 2, ',', '.'); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="content">
        <?php echo $contract->conteudo_gerado; ?>
    </div>
    
    <?php if (!empty($services)): ?>
        <div class="services">
            <h3>Serviços</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f5f5f5;">
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Descrição</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: right;">Valor</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: center;">Qtd</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 8px;"><?php echo e($service->descricao); ?></td>
                            <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">R$ <?php echo number_format($service->valor ?? 0, 2, ',', '.'); ?></td>
                            <td style="border: 1px solid #ddd; padding: 8px; text-align: center;"><?php echo $service->quantidade; ?></td>
                            <td style="border: 1px solid #ddd; padding: 8px; text-align: right;"><strong>R$ <?php echo number_format($service->getValorTotal(), 2, ',', '.'); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($conditions)): ?>
        <div class="conditions" style="margin-top: 30px;">
            <h3>Condições</h3>
            <?php foreach ($conditions as $condition): ?>
                <div style="margin-bottom: 15px;">
                    <strong><?php echo e($condition->titulo); ?></strong>
                    <p><?php echo nl2br(e($condition->descricao)); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="signatures">
        <?php foreach ($signatures as $signature): ?>
            <div class="signature-block">
                <p><strong><?php echo $signature->tipo_assinante === 'contratante' ? 'Contratante' : 'Contratado'; ?></strong></p>
                <p><?php echo e($signature->nome_assinante); ?></p>
                <?php if ($signature->cpf_cnpj): ?>
                    <p>CPF/CNPJ: <?php echo e($signature->cpf_cnpj); ?></p>
                <?php endif; ?>
                <?php if ($signature->assinado && $signature->assinado_em): ?>
                    <div class="signature-info">
                        <p>Assinado em: <?php echo date('d/m/Y H:i', strtotime($signature->assinado_em)); ?></p>
                        <?php if ($signature->hash_assinatura): ?>
                            <p style="font-size: 8px; word-break: break-all;">Hash: <?php echo substr($signature->hash_assinatura, 0, 32); ?>...</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #999;">Aguardando assinatura</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="footer">
        <p>Documento gerado em <?php echo date('d/m/Y H:i:s'); ?></p>
        <p>Este documento possui validade jurídica através de assinatura eletrônica.</p>
    </div>
</body>
</html>

