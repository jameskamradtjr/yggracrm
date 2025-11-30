<!DOCTYPE html>
<html lang="pt-BR" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    
    <title><?php echo $title ?? config('app.name'); ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/png" href="<?php echo asset('tema/assets/images/logos/favicon.png'); ?>" />
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="<?php echo asset('tema/assets/css/styles.css'); ?>" />
    
    <!-- Estilos customizados para logo -->
    <style>
        .logo-img img.dark-logo,
        .logo-img img.light-logo {
            max-height: 50px;
            max-width: 200px;
            width: auto;
            height: auto;
            object-fit: contain;
        }
        
        /* Logo no header (mobile) - menor */
        .d-block.d-lg-none .logo-img img {
            max-height: 40px;
            max-width: 180px;
        }
    </style>
    
    <?php echo $styles ?? ''; ?>
</head>
<body>
    <!-- Preloader -->
    <div class="preloader">
        <img src="<?php echo asset('tema/assets/images/logos/favicon.png'); ?>" alt="loader" class="lds-ripple img-fluid" />
    </div>

    <div id="main-wrapper">
        <!-- Sidebar -->
        <?php include base_path('views/layouts/sidebar.php'); ?>

        <!-- Main Content -->
        <div class="page-wrapper">
            <!-- Header -->
            <?php include base_path('views/layouts/header.php'); ?>

            <!-- Content -->
            <div class="body-wrapper">
                <div class="container">
                    <!-- Mensagens Flash -->
                    <?php 
                    $successMessage = session()->has('_flash_success') ? session()->getFlash('success') : null;
                    $errorMessage = session()->has('_flash_error') ? session()->getFlash('error') : null;
                    $errors = session()->has('_flash_errors') ? session()->getFlash('errors') : null;
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

                    <?php if ($errors): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert" id="flash-errors">
                            <i class="ti ti-alert-circle me-2"></i>
                            <strong>Erros de validação:</strong>
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

                    <!-- Page Content -->
                    <?php echo $content ?? ''; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?php echo asset('tema/assets/js/vendor.min.js'); ?>"></script>
    <script src="<?php echo asset('tema/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js'); ?>"></script>
    <script src="<?php echo asset('tema/assets/libs/simplebar/dist/simplebar.min.js'); ?>"></script>
    <script src="<?php echo asset('tema/assets/js/theme/app.init.js'); ?>"></script>
    <script src="<?php echo asset('tema/assets/js/theme/theme.js'); ?>"></script>
    <script src="<?php echo asset('tema/assets/js/theme/app.min.js'); ?>"></script>
    <script src="<?php echo asset('tema/assets/js/theme/sidebarmenu.js'); ?>"></script>

    <?php echo $scripts ?? ''; ?>

    <script>
        // Remove preloader automaticamente
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
        
        // CSRF Token for AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Sistema de Notificações
        <?php if (auth()->check()): ?>
        function carregarNotificacoes() {
            $.ajax({
                url: '<?php echo url('/api/notificacoes'); ?>',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        atualizarBadge(response.total_nao_lidas);
                        renderizarNotificacoes(response.notificacoes);
                    }
                },
                error: function() {
                    console.error('Erro ao carregar notificações');
                }
            });
        }

        function atualizarBadge(total) {
            const badge = $('#badge-notificacoes');
            const contador = $('#contador-notificacoes');
            
            if (total > 0) {
                badge.show();
                contador.text(total);
            } else {
                badge.hide();
            }
        }

        function renderizarNotificacoes(notificacoes) {
            const lista = $('#lista-notificacoes');
            
            if (notificacoes.length === 0) {
                lista.html('<div class="text-center py-4 text-muted">Nenhuma notificação</div>');
                $('#btn-marcar-lidas').hide();
                return;
            }
            
            let html = '';
            notificacoes.forEach(function(notif) {
                const icon = notif.icon || 'ti-info-circle';
                const color = notif.color || 'primary';
                const url = notif.url || '#';
                const data = new Date(notif.data).toLocaleString('pt-BR');
                
                html += `
                    <a href="${url}" class="py-3 px-4 d-flex align-items-center border-bottom notificacao-item" data-id="${notif.id}" onclick="marcarComoLida(${notif.id}, event)">
                        <span class="d-flex align-items-center justify-content-center text-bg-${color} rounded-circle p-6 fs-6">
                            <i class="ti ${icon}"></i>
                        </span>
                        <div class="w-100 ps-3">
                            <h6 class="mb-0 fs-3 fw-semibold">${notif.titulo}</h6>
                            <span class="fs-2 d-block text-body-secondary">${notif.mensagem}</span>
                            <small class="text-muted">${data}</small>
                        </div>
                    </a>
                `;
            });
            
            lista.html(html);
            $('#btn-marcar-lidas').show();
        }

        function marcarComoLida(id, event) {
            if (event) {
                event.preventDefault();
            }
            
            $.ajax({
                url: `<?php echo url('/api/notificacoes'); ?>/${id}/mark-as-read`,
                method: 'POST',
                dataType: 'json',
                success: function() {
                    carregarNotificacoes();
                    if (event && event.target.closest('a').href !== '#') {
                        window.location.href = event.target.closest('a').href;
                    }
                }
            });
        }

        function marcarTodasComoLidas() {
            $.ajax({
                url: '<?php echo url('/api/notificacoes/mark-all-as-read'); ?>',
                method: 'POST',
                dataType: 'json',
                success: function() {
                    carregarNotificacoes();
                }
            });
        }

        // Carrega notificações ao carregar a página
        $(document).ready(function() {
            carregarNotificacoes();
            
            // Atualiza a cada 30 segundos
            setInterval(carregarNotificacoes, 30000);
        });
        <?php endif; ?>

        // Sistema de Notificações Flash
        (function() {
            // Função para mostrar toast notification
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
                
                // Remove o elemento após ser escondido
                toast.addEventListener('hidden.bs.toast', function() {
                    toast.remove();
                });
            }
            
            // Cria container de toasts se não existir
            function createToastContainer() {
                const container = document.createElement('div');
                container.id = 'toast-container';
                container.className = 'toast-container position-fixed top-0 end-0 p-3';
                container.style.zIndex = '9999';
                document.body.appendChild(container);
                return container;
            }
            
            // Verifica se há mensagens flash e exibe toast
            const successAlert = document.getElementById('flash-success');
            const errorAlert = document.getElementById('flash-error');
            const errorsAlert = document.getElementById('flash-errors');
            
            if (successAlert) {
                const message = successAlert.textContent.trim();
                showToast(message, 'success');
                // Mantém o alert também visível
                successAlert.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            
            if (errorAlert) {
                const message = errorAlert.textContent.trim();
                showToast(message, 'error');
                errorAlert.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            
            if (errorsAlert) {
                const message = 'Erros de validação encontrados. Verifique os campos.';
                showToast(message, 'error');
                errorsAlert.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        })();
        
        // Função para atualizar timer no navbar (disponível globalmente)
        window.atualizarNavbarTimer = function() {
            const container = document.getElementById('navbar-timer-container');
            const timeDisplay = document.getElementById('navbar-timer-time');
            const pauseBtn = document.getElementById('navbar-timer-pause-btn');
            
            if (!container || !timeDisplay) {
                console.log('Timer: Elementos não encontrados', { container: !!container, timeDisplay: !!timeDisplay });
                return;
            }
            
            fetch('<?php echo url('/projects/kanban/timer/active'); ?>', {
                method: 'GET',
                headers: {
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na resposta: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Timer: Dados recebidos', data);
                if (data.success && data.total_timers > 0) {
                    container.classList.remove('d-none');
                    container.style.display = '';
                    timeDisplay.textContent = data.tempo_total_formatado || '00:00';
                    
                    if (pauseBtn) {
                        pauseBtn.style.display = 'inline-block';
                        let tooltipText = 'Timers Ativos:\n';
                        data.timers.forEach(timer => {
                            tooltipText += `• ${timer.card_titulo}: ${timer.tempo_formatado}\n`;
                        });
                        pauseBtn.setAttribute('title', tooltipText.trim() + '\n\nClique para pausar todos os timers');
                    }
                    console.log('Timer: Exibido com sucesso');
                } else {
                    container.classList.add('d-none');
                    container.style.display = 'none';
                    if (pauseBtn) {
                        pauseBtn.style.display = 'none';
                    }
                    console.log('Timer: Ocultado - nenhum timer ativo');
                }
            })
            .catch(error => {
                console.error('Erro ao verificar timers ativos:', error);
            });
        };
        
        // Inicializa timer após definir a função
        (function() {
            // Verifica timers ativos e atualiza navbar imediatamente
            setTimeout(() => {
                if (typeof window.atualizarNavbarTimer === 'function') {
                    window.atualizarNavbarTimer();
                }
            }, 500);
            
            // Atualiza a cada 5 segundos
            setInterval(() => {
                if (typeof window.atualizarNavbarTimer === 'function') {
                    window.atualizarNavbarTimer();
                }
            }, 5000);
            
            // Se houver timers ativos, atualiza a cada segundo
            setInterval(() => {
                const container = document.getElementById('navbar-timer-container');
                if (container && !container.classList.contains('d-none')) {
                    if (typeof window.atualizarNavbarTimer === 'function') {
                        window.atualizarNavbarTimer();
                    }
                }
            }, 1000);
        })();
        
        // Função para pausar todos os timers
        window.pausarTodosTimers = function() {
            if (!confirm('Tem certeza que deseja pausar todos os timers ativos?')) {
                return;
            }
            
            fetch('<?php echo url('/projects/kanban/timer/pause-all'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    atualizarNavbarTimer();
                    
                    // Recarrega a página se estiver no Kanban para atualizar os cards
                    if (window.location.pathname.includes('/kanban')) {
                        location.reload();
                    }
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao pausar timers');
            });
        };
    </script>
</body>
</html>

