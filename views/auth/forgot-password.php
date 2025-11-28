<!DOCTYPE html>
<html lang="pt-BR" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    
    <title>Esqueci a Senha - <?php echo config('app.name'); ?></title>
    
    <link rel="shortcut icon" type="image/png" href="<?php echo asset('tema/assets/images/logos/favicon.png'); ?>" />
    <link rel="stylesheet" href="<?php echo asset('tema/assets/css/styles.css'); ?>" />
</head>
<body>
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
                            <img src="<?php echo asset('tema/assets/images/backgrounds/login-security.svg'); ?>" alt="Forgot Password" class="img-fluid" width="500">
                        </div>
                    </div>

                    <div class="col-xl-5 col-xxl-4">
                        <div class="authentication-login min-vh-100 bg-body row justify-content-center align-items-center p-4">
                            <div class="auth-max-width col-sm-8 col-md-6 col-xl-7 px-4">
                                <h2 class="mb-1 fs-7 fw-bolder">Esqueceu a Senha?</h2>
                                <p class="mb-7">Digite seu email para recuperar o acesso</p>

                                <?php 
                                $successMessage = session()->has('_flash_success') ? session()->getFlash('success') : null;
                                $errorMessage = session()->has('_flash_error') ? session()->getFlash('error') : null;
                                ?>
                                
                                <?php if ($successMessage): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert" id="flash-success">
                                        <i class="ti ti-check me-2"></i>
                                        <?php echo e($successMessage); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <?php if ($errorMessage): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert" id="flash-error">
                                        <i class="ti ti-alert-circle me-2"></i>
                                        <?php echo e($errorMessage); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <form action="<?php echo url('/forgot-password'); ?>" method="POST">
                                    <?php echo csrf_field(); ?>

                                    <div class="mb-4">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 py-8 mb-4 rounded-2">Enviar Instruções</button>

                                    <div class="d-flex align-items-center justify-content-center">
                                        <a class="text-primary fw-medium" href="<?php echo url('/login'); ?>">Voltar para o login</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
        setTimeout(function() {
            const preloader = document.querySelector('.preloader');
            if (preloader) preloader.style.display = 'none';
        }, 2000);

        // Sistema de Notificações Flash
        (function() {
            function showToast(message, type = 'success') {
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
                
                const bsToast = new bootstrap.Toast(toast, {
                    autohide: true,
                    delay: 5000
                });
                
                bsToast.show();
                
                toast.addEventListener('hidden.bs.toast', function() {
                    toast.remove();
                });
            }
            
            function createToastContainer() {
                const container = document.createElement('div');
                container.id = 'toast-container';
                container.className = 'toast-container position-fixed top-0 end-0 p-3';
                container.style.zIndex = '9999';
                document.body.appendChild(container);
                return container;
            }
            
            const successAlert = document.getElementById('flash-success');
            const errorAlert = document.getElementById('flash-error');
            
            if (successAlert) {
                const message = successAlert.textContent.trim();
                showToast(message, 'success');
            }
            
            if (errorAlert) {
                const message = errorAlert.textContent.trim();
                showToast(message, 'error');
            }
        })();
    </script>
</body>
</html>

