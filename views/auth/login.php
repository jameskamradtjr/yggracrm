<!DOCTYPE html>
<html lang="pt-BR" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    
    <title>Login - <?php echo config('app.name'); ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/png" href="<?php echo asset('tema/assets/images/logos/favicon.png'); ?>" />
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="<?php echo asset('tema/assets/css/styles.css'); ?>" />
</head>
<body>
    <!-- Preloader -->
    <div class="preloader">
        <img src="<?php echo asset('tema/assets/images/logos/favicon.png'); ?>" alt="loader" class="lds-ripple img-fluid" />
    </div>

    <div id="main-wrapper" class="auth-customizer-none">
        <div class="position-relative overflow-hidden radial-gradient min-vh-100 w-100">
            <div class="position-relative z-index-5">
                <div class="row">
                    <div class="col-xl-7 col-xxl-8">
                        <a href="<?php echo url('/'); ?>" class="text-nowrap logo-img d-block px-4 py-9 w-100">
                            <img src="<?php echo asset('tema/assets/images/logos/dark-logo.svg'); ?>" class="dark-logo" alt="Logo" />
                            <img src="<?php echo asset('tema/assets/images/logos/light-logo.svg'); ?>" class="light-logo" alt="Logo" />
                        </a>
                        <div class="d-none d-xl-flex align-items-center justify-content-center h-n80">
                            <img src="<?php echo asset('tema/assets/images/backgrounds/login-security.svg'); ?>" alt="Login" class="img-fluid" width="500">
                        </div>
                    </div>

                    <div class="col-xl-5 col-xxl-4">
                        <div class="authentication-login min-vh-100 bg-body row justify-content-center align-items-center p-4">
                            <div class="auth-max-width col-sm-8 col-md-6 col-xl-7 px-4">
                                <h2 class="mb-1 fs-7 fw-bolder">Bem-vindo ao <?php echo config('app.name'); ?></h2>
                                <p class="mb-7">Sistema de Gerenciamento</p>

                                <!-- Mensagens -->
                                <?php 
                                // Verifica mensagens flash de forma mais robusta
                                $successMessage = null;
                                $errorMessage = null;
                                
                                if (session()->has('_flash_success')) {
                                    $successMessage = session()->getFlash('success');
                                }
                                
                                if (session()->has('_flash_error')) {
                                    $errorMessage = session()->getFlash('error');
                                }
                                ?>
                                
                                <?php if ($successMessage): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert" id="flash-success">
                                        <i class="ti ti-check me-2"></i>
                                        <?php echo e($successMessage); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <?php if ($errorMessage): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert" id="flash-error" style="display: block !important;">
                                        <i class="ti ti-alert-circle me-2"></i>
                                        <?php echo e($errorMessage); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <!-- Formulário -->
                                <form action="<?php echo url('/login'); ?>" method="POST">
                                    <?php echo csrf_field(); ?>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo e(old('email')); ?>" autocomplete="email" required autofocus>
                                    </div>

                                    <div class="mb-4">
                                        <label for="password" class="form-label">Senha</label>
                                        <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-between mb-4">
                                        <div class="form-check">
                                            <input class="form-check-input primary" type="checkbox" value="1" id="remember" name="remember">
                                            <label class="form-check-label text-dark fs-3" for="remember">
                                                Lembrar-me
                                            </label>
                                        </div>
                                        <a class="text-primary fw-medium fs-3" href="<?php echo url('/forgot-password'); ?>">Esqueceu a senha?</a>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 py-8 mb-4 rounded-2">Entrar</button>

                                    <div class="d-flex align-items-center justify-content-center">
                                        <p class="fs-4 mb-0 fw-medium">Novo por aqui?</p>
                                        <a class="text-primary fw-medium ms-2" href="<?php echo url('/register'); ?>">Criar uma conta</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?php echo asset('tema/assets/js/vendor.min.js'); ?>"></script>
    <script src="<?php echo asset('tema/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js'); ?>"></script>
    
    <!-- Remove preloader automaticamente -->
    <script>
        window.addEventListener('load', function() {
            const preloader = document.querySelector('.preloader');
            if (preloader) {
                preloader.style.opacity = '0';
                setTimeout(() => preloader.style.display = 'none', 300);
            }
        });
        
        // Fallback: remove após 2 segundos de qualquer forma
        setTimeout(function() {
            const preloader = document.querySelector('.preloader');
            if (preloader) {
                preloader.style.display = 'none';
            }
        }, 2000);

        // Sistema de Notificações Flash
        (function() {
            function showToast(message, type = 'success') {
                // Limpa mensagem de ícones e caracteres especiais
                message = message.replace(/[✓✗×]/g, '').trim();
                
                // Verifica se Bootstrap está disponível
                if (typeof bootstrap === 'undefined') {
                    console.warn('Bootstrap não está disponível, usando alert nativo');
                    alert(message);
                    return;
                }
                
                const toastContainer = document.getElementById('toast-container') || createToastContainer();
                
                const toast = document.createElement('div');
                toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
                toast.setAttribute('role', 'alert');
                toast.setAttribute('aria-live', 'assertive');
                toast.setAttribute('aria-atomic', 'true');
                
                const icon = type === 'success' ? 'ti-check' : 'ti-alert-circle';
                
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="ti ${icon} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                `;
                
                toastContainer.appendChild(toast);
                
                try {
                    const bsToast = new bootstrap.Toast(toast, {
                        autohide: true,
                        delay: 5000
                    });
                    
                    bsToast.show();
                    
                    toast.addEventListener('hidden.bs.toast', function() {
                        toast.remove();
                    });
                } catch (e) {
                    console.error('Erro ao criar toast:', e);
                    // Fallback: mostra o alert na página
                    toast.style.display = 'block';
                    setTimeout(() => {
                        toast.style.opacity = '0';
                        setTimeout(() => toast.remove(), 300);
                    }, 5000);
                }
            }
            
            function createToastContainer() {
                const container = document.createElement('div');
                container.id = 'toast-container';
                container.className = 'toast-container position-fixed top-0 end-0 p-3';
                container.style.zIndex = '9999';
                document.body.appendChild(container);
                return container;
            }
            
            function checkAndShowAlerts() {
                const successAlert = document.getElementById('flash-success');
                const errorAlert = document.getElementById('flash-error');
                
                if (successAlert) {
                    let message = successAlert.textContent.trim();
                    // Garante que o alert está visível
                    successAlert.style.display = 'block';
                    successAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    showToast(message, 'success');
                }
                
                if (errorAlert) {
                    let message = errorAlert.textContent.trim();
                    // Garante que o alert está visível
                    errorAlert.style.display = 'block';
                    errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    showToast(message, 'error');
                }
            }
            
            // Executa quando o DOM estiver pronto
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', checkAndShowAlerts);
            } else {
                // DOM já está carregado
                setTimeout(checkAndShowAlerts, 100);
            }
        })();
    </script>
</body>
</html>

