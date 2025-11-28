<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página Não Encontrada</title>
    <link rel="stylesheet" href="<?php echo asset('tema/assets/css/styles.css'); ?>">
</head>
<body>
    <div class="container-fluid">
        <div class="row vh-100 align-items-center justify-content-center">
            <div class="col-lg-6 text-center">
                <img src="<?php echo asset('tema/assets/images/backgrounds/errorimg.svg'); ?>" alt="404" class="img-fluid mb-4" style="max-width: 400px;">
                <h1 class="fw-bold mb-3">Página Não Encontrada</h1>
                <p class="text-muted mb-4">A página que você está procurando não existe ou foi removida.</p>
                <a href="<?php echo url('/dashboard'); ?>" class="btn btn-primary">Voltar ao Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>

