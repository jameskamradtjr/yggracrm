<header class="topbar">
    <div class="with-vertical">
        <nav class="navbar navbar-expand-lg p-0">
            <ul class="navbar-nav">
                <li class="nav-item nav-icon-hover-bg rounded-circle ms-n2">
                    <a class="nav-link sidebartoggler" id="headerCollapse" href="javascript:void(0)">
                        <i class="ti ti-menu-2"></i>
                    </a>
                </li>
            </ul>

            <div class="d-block d-lg-none py-4">
                <a href="<?php echo url('/dashboard'); ?>" class="text-nowrap logo-img">
                    <?php 
                    // Obtém logo do sistema ou usa padrão
                    $logoDark = \App\Models\SystemSetting::get('logo_dark');
                    // Valida se é uma URL válida (não pode ser hash/base64)
                    if (empty($logoDark) || 
                        (strlen($logoDark) > 100) || // Hash muito longo
                        (strpos($logoDark, '.svg') === false && strpos($logoDark, '.png') === false && strpos($logoDark, '.jpg') === false && strpos($logoDark, '.jpeg') === false && strpos($logoDark, '/uploads/') !== 0)) {
                        $logoDark = asset('tema/assets/images/logos/dark-logo.svg');
                    } elseif (strpos($logoDark, '/uploads/') === 0) {
                        $logoDark = asset($logoDark);
                    }
                    
                    $logoLight = \App\Models\SystemSetting::get('logo_light');
                    // Valida se é uma URL válida (não pode ser hash/base64)
                    if (empty($logoLight) || 
                        (strlen($logoLight) > 100) || // Hash muito longo
                        (strpos($logoLight, '.svg') === false && strpos($logoLight, '.png') === false && strpos($logoLight, '.jpg') === false && strpos($logoLight, '.jpeg') === false && strpos($logoLight, '/uploads/') !== 0)) {
                        $logoLight = asset('tema/assets/images/logos/light-logo.svg');
                    } elseif (strpos($logoLight, '/uploads/') === 0) {
                        $logoLight = asset($logoLight);
                    }
                    ?>
                    <img src="<?php echo $logoDark; ?>" class="dark-logo" alt="Logo" />
                    <img src="<?php echo $logoLight; ?>" class="light-logo" alt="Logo" />
                </a>
            </div>

            <a class="navbar-toggler nav-icon-hover-bg rounded-circle p-0 mx-0 border-0" href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="ti ti-dots fs-7"></i>
            </a>

            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <div class="d-flex align-items-center justify-content-between">
                    <ul class="navbar-nav flex-row ms-auto align-items-center justify-content-center">
                        <!-- Timer Ativo -->
                        <?php if (auth()->check()): ?>
                        <li class="nav-item d-none" id="navbar-timer-container">
                            <div class="d-flex align-items-center gap-2 px-2">
                                <i class="ti ti-clock-play text-success fs-5"></i>
                                <span class="text-success fw-semibold" id="navbar-timer-time" style="font-size: 0.9rem;">00:00</span>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="navbar-timer-pause-btn" onclick="pausarTodosTimers()" title="Pausar Timer">
                                    <i class="ti ti-player-pause"></i>
                                </button>
                            </div>
                        </li>
                        <?php endif; ?>
                        
                        <!-- Notificações -->
                        <?php if (auth()->check()): ?>
                        <li class="nav-item dropdown nav-icon-hover-bg rounded-circle">
                            <a class="nav-link position-relative" href="javascript:void(0)" id="drop2" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ti ti-bell"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="badge-notificacoes" style="display: none; font-size: 0.7rem; padding: 2px 6px;">
                                    <span id="contador-notificacoes">0</span>
                                </span>
                            </a>
                            <div class="dropdown-menu content-dd dropdown-menu-end dropdown-menu-animate-up" aria-labelledby="drop2" style="width: 350px; max-width: 90vw;" id="dropdown-notificacoes">
                                <div class="d-flex align-items-center justify-content-between pt-3 pb-3 px-4 border-bottom">
                                    <h5 class="mb-0 fs-5 fw-semibold">Notificações</h5>
                                    <a href="javascript:void(0)" class="text-muted" onclick="marcarTodasComoLidas()" id="btn-marcar-lidas" style="display: none;">
                                        <small>Marcar todas como lidas</small>
                                    </a>
                                </div>
                                <div class="message-body" data-simplebar style="max-height: 400px;" id="lista-notificacoes">
                                    <div class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Carregando...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <?php endif; ?>

                        <!-- Perfil -->
                        <li class="nav-item dropdown">
                            <a class="nav-link" href="javascript:void(0)" id="drop1" aria-expanded="false">
                                <div class="d-flex align-items-center gap-2 lh-base">
                                    <?php 
                                    $user = auth()->user();
                                    $avatar = $user->avatar ?? null;
                                    ?>
                                    <?php if (!empty($avatar)): ?>
                                        <img src="<?php echo asset($avatar); ?>" class="rounded-circle" width="35" height="35" alt="Avatar" style="object-fit: cover;" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <?php else: ?>
                                        <div class="rounded-circle bg-primary-subtle d-inline-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            <span class="fs-7 fw-bold text-primary"><?php echo strtoupper(substr($user->name ?? 'U', 0, 1)); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <span class="d-none d-md-block fw-semibold">
                                        <?php echo e($user->name ?? 'Usuário'); ?>
                                    </span>
                                    <i class="ti ti-chevron-down d-none d-md-block"></i>
                                </div>
                            </a>
                            <div class="dropdown-menu content-dd dropdown-menu-end dropdown-menu-animate-up" aria-labelledby="drop1">
                                <div class="profile-dropdown position-relative" data-simplebar>
                                    <div class="d-flex align-items-center justify-content-between pt-3 pb-3 px-7">
                                        <h5 class="mb-0 fs-5 fw-semibold">Perfil</h5>
                                    </div>

                                    <div class="d-flex align-items-center mx-7 py-3 border-bottom">
                                        <?php 
                                        $user = auth()->user();
                                        $avatar = $user->avatar ?? null;
                                        ?>
                                        <?php if (!empty($avatar)): ?>
                                            <img src="<?php echo asset($avatar); ?>" class="rounded-circle" width="60" height="60" alt="Avatar" style="object-fit: cover;" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-primary-subtle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                <span class="fs-5 fw-bold text-primary"><?php echo strtoupper(substr($user->name ?? 'U', 0, 2)); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="ms-3">
                                            <h5 class="mb-0 fs-4 fw-semibold"><?php echo e($user->name ?? 'Usuário'); ?></h5>
                                            <span class="mb-0 d-block text-dark"><?php echo e($user->email ?? ''); ?></span>
                                        </div>
                                    </div>

                                    <div class="message-body">
                                        <a href="<?php echo url('/profile'); ?>" class="py-8 px-7 d-flex align-items-center">
                                            <span class="d-flex align-items-center justify-content-center text-bg-light rounded-circle p-6 fs-6">
                                                <i class="ti ti-user"></i>
                                            </span>
                                            <div class="w-100 ps-3">
                                                <h5 class="mb-0 fs-3 fw-normal">Meu Perfil</h5>
                                                <span class="fs-2 d-block text-body-secondary">Configurações da conta</span>
                                            </div>
                                        </a>
                                    </div>

                                    <div class="py-4 px-7 pt-2">
                                        <form action="<?php echo url('/logout'); ?>" method="POST">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="btn btn-outline-primary w-100">Sair</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
</header>

