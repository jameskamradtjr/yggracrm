<aside class="left-sidebar with-vertical">
    <div>
        <!-- Logo -->
        <div class="brand-logo d-flex align-items-center justify-content-between">
            <a href="<?php echo url('/dashboard'); ?>" class="text-nowrap logo-img">
                <?php 
                // Obtém logo do sistema ou usa padrão
                $logoDark = \App\Models\SystemSetting::get('logo_dark');
                if (empty($logoDark)) {
                    $logoDark = asset('tema/assets/images/logos/dark-logo.svg');
                } elseif (strpos($logoDark, '/uploads/') === 0) {
                    $logoDark = asset($logoDark);
                }
                
                $logoLight = \App\Models\SystemSetting::get('logo_light');
                if (empty($logoLight)) {
                    $logoLight = asset('tema/assets/images/logos/light-logo.svg');
                } elseif (strpos($logoLight, '/uploads/') === 0) {
                    $logoLight = asset($logoLight);
                }
                ?>
                <img src="<?php echo $logoDark; ?>" class="dark-logo" alt="Logo" />
                <img src="<?php echo $logoLight; ?>" class="light-logo" alt="Logo" style="display: none;" />
            </a>
            <a href="javascript:void(0)" class="sidebartoggler ms-auto text-decoration-none fs-5 d-block d-xl-none">
                <i class="ti ti-x"></i>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav scroll-sidebar" data-simplebar>
            <ul id="sidebarnav">
                <!-- Dashboard -->
                <li class="nav-small-cap">
                    <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                    <span class="hide-menu">Dashboard</span>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/dashboard'); ?>">
                        <span><i class="ti ti-home"></i></span>
                        <span class="hide-menu">Dashboard</span>
                    </a>
                </li>

                <!-- Usuários -->
                <?php if (auth()->check()): ?>
                <li class="nav-small-cap">
                    <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                    <span class="hide-menu">Gerenciamento</span>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/users'); ?>">
                        <span><i class="ti ti-users"></i></span>
                        <span class="hide-menu">Usuários</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Roles e Permissões -->
                <?php if (auth()->check() && auth()->can('read', 'roles')): ?>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/roles'); ?>">
                        <span><i class="ti ti-shield"></i></span>
                        <span class="hide-menu">Roles</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Sistema -->
                <?php if (auth()->check()): ?>
                <li class="nav-small-cap">
                    <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                    <span class="hide-menu">Sistema</span>
                </li>
                <?php if (auth()->user()->canAccess('sistema', 'logs', 'view')): ?>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/sistema/logs'); ?>">
                        <span><i class="ti ti-file-text"></i></span>
                        <span class="hide-menu">Logs do Sistema</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('super-admin')): ?>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/settings'); ?>">
                        <span><i class="ti ti-settings"></i></span>
                        <span class="hide-menu">Configurações</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php endif; ?>

                <!-- Perfil -->
                <li class="nav-small-cap">
                    <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                    <span class="hide-menu">Conta</span>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/profile'); ?>">
                        <span><i class="ti ti-user-circle"></i></span>
                        <span class="hide-menu">Meu Perfil</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>

