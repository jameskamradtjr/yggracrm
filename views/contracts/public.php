<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title ?? 'Contrato'); ?></title>
    <link rel="stylesheet" href="<?php echo asset('tema/assets/css/styles.css'); ?>">
    <style>
        body {
            background: #f5f5f5;
            font-family: Arial, sans-serif;
        }
        .contract-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
        }
        .contract-header {
            text-align: center;
            border-bottom: 3px solid #5d87ff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .contract-title {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .contract-number {
            color: #5d87ff;
            font-size: 18px;
            font-weight: 500;
        }
        .contract-info {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .contract-content {
            line-height: 1.8;
            text-align: justify;
            margin-bottom: 30px;
        }
        .contract-content p {
            margin-bottom: 15px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .status-assinado {
            background: #d4edda;
            color: #155724;
        }
        .status-aguardando {
            background: #fff3cd;
            color: #856404;
        }
        .status-rascunho {
            background: #e2e3e5;
            color: #383d41;
        }
        .services-section {
            margin: 30px 0;
        }
        .services-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
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
        .mb-3 {
            margin-bottom: 1rem;
        }
        .text-muted {
            color: #6c757d;
        }
        .fw-bold {
            font-weight: bold;
        }
        .contract-footer {
            background: #1a1a1a;
            color: white;
            padding: 40px;
            margin-top: 50px;
            border-radius: 5px;
            text-align: center;
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
    <div class="contract-container">
        <div class="contract-header">
            <h1 class="contract-title"><?php echo e($contract->titulo); ?></h1>
            <p class="contract-number">Contrato: <?php echo e($contract->numero_contrato); ?></p>
            
            <?php
            $statusClass = 'status-aguardando';
            $statusText = 'Aguardando Assinatura';
            if ($contract->status === 'assinado') {
                $statusClass = 'status-assinado';
                $statusText = 'Assinado';
            } elseif ($contract->status === 'rascunho') {
                $statusClass = 'status-rascunho';
                $statusText = 'Rascunho';
            }
            ?>
            <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
        </div>

        <div class="contract-info">
            <?php if ($client): ?>
                <div class="mb-3">
                    <strong>Contratante:</strong><br>
                    <?php echo e($client->nome_razao_social); ?>
                    <?php if ($client->cpf_cnpj): ?>
                        <br><small class="text-muted">CPF/CNPJ: <?php echo e($client->cpf_cnpj); ?></small>
                    <?php endif; ?>
                    <?php if ($client->email): ?>
                        <br><small class="text-muted"><?php echo e($client->email); ?></small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="mb-3">
                <strong>Período do Contrato:</strong><br>
                <?php echo date('d/m/Y', strtotime($contract->data_inicio)); ?> 
                até 
                <?php echo date('d/m/Y', strtotime($contract->data_termino)); ?>
            </div>
            
            <?php if ($contract->valor_total): ?>
                <div class="mb-3">
                    <strong>Valor Total:</strong><br>
                    <strong style="color: #5d87ff; font-size: 24px;">
                        R$ <?php echo number_format($contract->valor_total, 2, ',', '.'); ?>
                    </strong>
                </div>
            <?php endif; ?>
            
            <div>
                <strong>Contrato elaborado por:</strong><br>
                <?php 
                $user = \App\Models\User::find($contract->user_id);
                echo e($user->name ?? 'Sistema');
                ?>
            </div>
        </div>

        <div class="contract-content">
            <?php echo $contract->conteudo_gerado; ?>
        </div>

        <?php if (!empty($services)): ?>
            <div class="services-section">
                <h3>Serviços Contratados</h3>
                <table class="services-table">
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th style="text-align: center;">Quantidade</th>
                            <th style="text-align: right;">Valor Unitário</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($service->descricao); ?></strong>
                                    <?php if (!empty($service->observacoes)): ?>
                                        <br><small class="text-muted"><?php echo e($service->observacoes); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;"><?php echo $service->quantidade; ?></td>
                                <td style="text-align: right;">R$ <?php echo number_format($service->valor_unitario, 2, ',', '.'); ?></td>
                                <td style="text-align: right;"><strong>R$ <?php echo number_format($service->valor_total, 2, ',', '.'); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (!empty($conditions)): ?>
            <div class="services-section">
                <h3>Condições Contratuais</h3>
                <?php foreach ($conditions as $condition): ?>
                    <div style="border-left: 4px solid #5d87ff; padding-left: 15px; margin-bottom: 15px;">
                        <strong><?php echo e($condition->titulo); ?></strong>
                        <?php if ($condition->descricao): ?>
                            <p style="margin: 5px 0 0 0;"><?php echo nl2br(e($condition->descricao)); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($contract->observacoes)): ?>
            <div class="services-section">
                <h3>Observações</h3>
                <p><?php echo nl2br(e($contract->observacoes)); ?></p>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="contract-footer">
            <div style="margin-bottom: 20px;">
                <?php 
                $user = \App\Models\User::find($contract->user_id);
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
            
            <?php if ($contract->status === 'assinado' && $contract->data_assinatura_completa): ?>
                <div style="margin-top: 30px; padding: 20px; background: rgba(40, 167, 69, 0.2); border-radius: 5px;">
                    <h4 style="color: #28a745; margin-bottom: 10px;">✓ Contrato Assinado</h4>
                    <p style="color: #ccc;">
                        Assinado digitalmente em <?php echo date('d/m/Y às H:i', strtotime($contract->data_assinatura_completa)); ?>
                    </p>
                </div>
            <?php else: ?>
                <div style="margin-top: 30px;">
                    <h3 style="color: white; margin-bottom: 15px;">Dúvidas sobre o contrato?</h3>
                    <p style="color: #ccc; margin-bottom: 30px;">Entre em contato conosco!</p>
                    
                    <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                        <button type="button" class="btn btn-secondary no-print" onclick="enviarMensagem()" style="padding: 12px 30px; font-size: 16px;">
                            Enviar mensagem
                        </button>
                        <?php if ($contract->status !== 'assinado'): ?>
                            <button type="button" class="btn btn-success no-print" onclick="assinarContrato()" style="padding: 12px 30px; font-size: 16px; background: #28a745; border-color: #28a745;">
                                Assinar contrato
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    function enviarMensagem() {
        alert('Funcionalidade de enviar mensagem será implementada em breve.');
    }
    
    function assinarContrato() {
        if (confirm('Deseja prosseguir com a assinatura deste contrato?')) {
            // TODO: Implementar endpoint para assinar contrato
            alert('Funcionalidade de assinatura será implementada em breve.');
        }
    }
    </script>
</body>
</html>
