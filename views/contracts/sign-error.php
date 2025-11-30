<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro - Assinatura de Contrato</title>
    <link rel="stylesheet" href="<?php echo asset('tema/assets/css/styles.css'); ?>">
    <style>
        body {
            background: #f5f5f5;
            font-family: Arial, sans-serif;
        }
        .error-container {
            max-width: 600px;
            margin: 100px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <i class="ti ti-alert-circle text-danger" style="font-size: 64px;"></i>
        <h2 class="mt-3">Erro</h2>
        <p class="text-muted"><?php echo e($message ?? 'Link de assinatura inválido ou expirado.'); ?></p>
        <a href="/" class="btn btn-primary mt-3">Voltar ao Início</a>
    </div>
</body>
</html>

