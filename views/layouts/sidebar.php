<aside class="left-sidebar with-vertical">
    <div>
        <!-- Logo -->
        <div class="brand-logo d-flex align-items-center justify-content-between">
            <a href="<?php echo url('/dashboard'); ?>" class="text-nowrap logo-img">
                <?php 
                // Obtém logo do sistema ou usa padrão
                $logoDark = \App\Models\SystemSetting::get('logo_dark');
                // Garante que seja string
                if (!is_string($logoDark)) {
                    $logoDark = '';
                }
                // Valida se é uma URL válida (não pode ser hash/base64)
                if (empty($logoDark) || 
                    strlen($logoDark) > 100 || // Hash muito longo
                    (strpos($logoDark, '.svg') === false && strpos($logoDark, '.png') === false && strpos($logoDark, '.jpg') === false && strpos($logoDark, '.jpeg') === false && strpos($logoDark, '/uploads/') !== 0)) {
                    $logoDark = asset('tema/assets/images/logos/dark-logo.svg');
                } elseif (strpos($logoDark, '/uploads/') === 0) {
                    $logoDark = asset($logoDark);
                }
                
                $logoLight = \App\Models\SystemSetting::get('logo_light');
                // Garante que seja string
                if (!is_string($logoLight)) {
                    $logoLight = '';
                }
                // Valida se é uma URL válida (não pode ser hash/base64)
                if (empty($logoLight) || 
                    strlen($logoLight) > 100 || // Hash muito longo
                    (strpos($logoLight, '.svg') === false && strpos($logoLight, '.png') === false && strpos($logoLight, '.jpg') === false && strpos($logoLight, '.jpeg') === false && strpos($logoLight, '/uploads/') !== 0)) {
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

                <!-- Financeiro -->
                <?php if (auth()->check()): ?>
                <li class="nav-small-cap">
                    <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                    <span class="hide-menu">Financeiro</span>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/financial'); ?>">
                        <span><i class="ti ti-wallet"></i></span>
                        <span class="hide-menu">Lançamentos</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/financial/bank-accounts'); ?>">
                        <span><i class="ti ti-building-bank"></i></span>
                        <span class="hide-menu">Contas Bancárias</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/financial/credit-cards'); ?>">
                        <span><i class="ti ti-credit-card"></i></span>
                        <span class="hide-menu">Cartões de Crédito</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/financial/categories'); ?>">
                        <span><i class="ti ti-tags"></i></span>
                        <span class="hide-menu">Categorias</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/financial/cost-centers'); ?>">
                        <span><i class="ti ti-building"></i></span>
                        <span class="hide-menu">Centros de Custo</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/financial/suppliers'); ?>">
                        <span><i class="ti ti-truck"></i></span>
                        <span class="hide-menu">Fornecedores</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/financial/payment-methods'); ?>">
                        <span><i class="ti ti-cash"></i></span>
                        <span class="hide-menu">Formas de Pagamento</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/imports/financial'); ?>">
                        <span><i class="ti ti-file-import"></i></span>
                        <span class="hide-menu">Importar Lançamentos</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- CRM de Leads -->
                <?php if (auth()->check()): ?>
                <li class="nav-small-cap">
                    <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                    <span class="hide-menu">CRM</span>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/leads'); ?>">
                        <span><i class="ti ti-address-book"></i></span>
                        <span class="hide-menu">Leads</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/quizzes'); ?>">
                        <span><i class="ti ti-clipboard-list"></i></span>
                        <span class="hide-menu">Quizzes</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/clients'); ?>">
                        <span><i class="ti ti-user"></i></span>
                        <span class="hide-menu">Clientes</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/imports/clients'); ?>">
                        <span><i class="ti ti-file-import"></i></span>
                        <span class="hide-menu">Importar Clientes</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/projects'); ?>">
                        <span><i class="ti ti-briefcase"></i></span>
                        <span class="hide-menu">Projetos</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/calendar'); ?>">
                        <span><i class="ti ti-calendar"></i></span>
                        <span class="hide-menu">Agenda</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/drive'); ?>">
                        <span><i class="ti ti-cloud"></i></span>
                        <span class="hide-menu">Drive</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/proposals'); ?>">
                        <span><i class="ti ti-file-description"></i></span>
                        <span class="hide-menu">Propostas</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/contracts'); ?>">
                        <span><i class="ti ti-file-text"></i></span>
                        <span class="hide-menu">Contratos</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/contracts/templates'); ?>">
                        <span><i class="ti ti-notes"></i></span>
                        <span class="hide-menu">Templates de Contratos</span>
                    </a>
                </li>
                <?php endif; ?>

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
                
                <!-- Automações -->
                <?php if (auth()->check()): ?>
                <li class="nav-small-cap">
                    <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                    <span class="hide-menu">Automações</span>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="<?php echo url('/automations'); ?>">
                        <span><i class="ti ti-robot"></i></span>
                        <span class="hide-menu">Automações</span>
                    </a>
                </li>
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

