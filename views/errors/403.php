<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Acesso Negado</title>
    <link rel="stylesheet" href="<?php echo asset('tema/assets/css/styles.css'); ?>">
</head>
<body>
    <div class="container-fluid">
        <div class="row vh-100 align-items-center justify-content-center">
            <div class="col-lg-6 text-center">
                <h1 class="fw-bold mb-3 display-1 text-danger">403</h1>
                <h2 class="fw-bold mb-3">Acesso Negado</h2>
                <p class="text-muted mb-4">Você não tem permissão para acessar esta página.</p>
                <a href="<?php echo url('/dashboard'); ?>" class="btn btn-primary">Voltar ao Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>

