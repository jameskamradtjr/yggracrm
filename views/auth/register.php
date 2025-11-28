<!DOCTYPE html>
<html lang="pt-BR" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    
    <title>Criar Conta - <?php echo config('app.name'); ?></title>
    
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
                            <img src="<?php echo asset('tema/assets/images/backgrounds/login-security.svg'); ?>" alt="Register" class="img-fluid" width="500">
                        </div>
                    </div>

                    <div class="col-xl-5 col-xxl-4">
                        <div class="authentication-login min-vh-100 bg-body row justify-content-center align-items-center p-4">
                            <div class="auth-max-width col-sm-8 col-md-6 col-xl-7 px-4">
                                <h2 class="mb-1 fs-7 fw-bolder">Criar Conta</h2>
                                <p class="mb-7">Comece a usar o sistema agora mesmo</p>

                                <?php 
                                $errors = session()->has('_flash_errors') ? session()->getFlash('errors') : null;
                                ?>
                                
                                <?php if ($errors): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert" id="flash-errors">
                                        <i class="ti ti-alert-circle me-2"></i>
                                        <strong>Erros:</strong>
                                        <ul class="mb-0 mt-2">
                                            <?php foreach ($errors as $field => $fieldErrors): ?>
                                                <?php foreach ($fieldErrors as $error): ?>
                                                    <li><?php echo e($error); ?></li>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <form action="<?php echo url('/register'); ?>" method="POST">
                                    <?php echo csrf_field(); ?>

                                    <div class="mb-3">
                                        <label for="name" class="form-label">Nome Completo</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo e(old('name')); ?>" required autofocus>
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo e(old('email')); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Telefone</label>
                                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo e(old('phone')); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="password" class="form-label">Senha</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>

                                    <div class="mb-4">
                                        <label for="password_confirmation" class="form-label">Confirmar Senha</label>
                                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 py-8 mb-4 rounded-2">Criar Conta</button>

                                    <div class="d-flex align-items-center justify-content-center">
                                        <p class="fs-4 mb-0 fw-medium">Já tem uma conta?</p>
                                        <a class="text-primary fw-medium ms-2" href="<?php echo url('/login'); ?>">Fazer login</a>
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
            const errorsAlert = document.getElementById('flash-errors');
            
            if (successAlert) {
                const message = successAlert.textContent.trim();
                showToast(message, 'success');
            }
            
            if (errorAlert) {
                const message = errorAlert.textContent.trim();
                showToast(message, 'error');
            }
            
            if (errorsAlert) {
                const message = 'Erros de validação encontrados. Verifique os campos.';
                showToast(message, 'error');
            }
        })();
    </script>
</body>
</html>

