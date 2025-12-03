<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title ?? 'Contrato'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo asset('tema/assets/css/styles.css'); ?>">
    
    <style>
        body {
            background: #f5f5f5;
            padding: 20px 0;
        }
        .contract-container {
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
        .contract-title {
            color: #2a3547;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .contract-number {
            color: #5d87ff;
            font-size: 18px;
            font-weight: 500;
        }
        .contract-content {
            margin: 30px 0;
            line-height: 1.8;
            text-align: justify;
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
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        .status-assinado {
            background: #d4edda;
            color: #155724;
        }
        .status-aguardando {
            background: #fff3cd;
            color: #856404;
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
            .contract-container {
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
    <div class="contract-container">
        <!-- Cabeçalho da Empresa -->
        <div class="company-header">
            <?php if (!empty($companyLogo)): ?>
                <img src="<?php echo asset($companyLogo); ?>" alt="<?php echo e($companyName); ?>" class="company-logo">
            <?php endif; ?>
            <h1 class="company-name"><?php echo e($companyName); ?></h1>
        </div>

        <!-- Título do Contrato -->
        <div class="text-center mb-4">
            <h2 class="contract-title"><?php echo e($contract->titulo); ?></h2>
            <p class="contract-number">Contrato: <?php echo e($contract->numero_contrato); ?></p>
            
            <?php
            $statusClass = 'status-aguardando';
            $statusText = 'Aguardando Assinatura';
            if ($contract->status === 'assinado') {
                $statusClass = 'status-assinado';
                $statusText = 'Assinado';
            }
            ?>
            <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
        </div>

        <!-- Informações do Contrato -->
        <div class="info-section">
            <h4 style="color: #2a3547; border-bottom: 2px solid #5d87ff; padding-bottom: 10px; margin-bottom: 15px;">
                Informações do Contrato
            </h4>
            
            <?php if ($client): ?>
                <div class="info-label">Contratante:</div>
                <div class="info-value"><?php echo e($client->nome_razao_social); ?></div>
            <?php endif; ?>
            
            <div class="info-label">Período:</div>
            <div class="info-value">
                <?php echo date('d/m/Y', strtotime($contract->data_inicio)); ?> 
                até 
                <?php echo date('d/m/Y', strtotime($contract->data_termino)); ?>
            </div>
            
            <?php if ($contract->valor_total): ?>
                <div class="info-label">Valor Total:</div>
                <div class="info-value">
                    <strong style="color: #5d87ff; font-size: 20px;">
                        R$ <?php echo number_format($contract->valor_total, 2, ',', '.'); ?>
                    </strong>
                </div>
            <?php endif; ?>
        </div>

        <!-- Conteúdo do Contrato -->
        <div class="contract-content">
            <?php echo $contract->conteudo_gerado; ?>
        </div>

        <!-- Observações -->
        <?php if (!empty($contract->observacoes)): ?>
            <div class="info-section">
                <h4 style="color: #2a3547; border-bottom: 2px solid #5d87ff; padding-bottom: 10px; margin-bottom: 15px;">
                    Observações
                </h4>
                <div><?php echo nl2br(e($contract->observacoes)); ?></div>
            </div>
        <?php endif; ?>

        <!-- Rodapé -->
        <div class="footer-note">
            <p>Este contrato foi gerado eletronicamente.</p>
            <?php if ($contract->status === 'assinado' && $contract->data_assinatura_completa): ?>
                <p>Assinado digitalmente em <?php echo date('d/m/Y às H:i', strtotime($contract->data_assinatura_completa)); ?></p>
            <?php endif; ?>
        </div>

        <!-- Botão de Impressão -->
        <div class="text-center mt-4 no-print">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="ti ti-printer"></i> Imprimir Contrato
            </button>
        </div>
    </div>
</body>
</html>

