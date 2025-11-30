<?php
$title = 'Configurações do Sistema';

// Inicia captura do conteúdo
ob_start();

// Obtém logo atual
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

// Obtém configurações SMTP
$smtpConfig = \App\Models\SystemSetting::get('smtp_config', []);
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title fw-semibold mb-4">Configurações do Sistema</h4>
                
                <!-- Abas -->
                <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $tab === 'layout' ? 'active' : ''; ?>" 
                                id="layout-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#layout" 
                                type="button" 
                                role="tab">
                            <i class="ti ti-palette me-2"></i>
                            Layout do Sistema
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $tab === 'email' ? 'active' : ''; ?>" 
                                id="email-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#email" 
                                type="button" 
                                role="tab">
                            <i class="ti ti-mail me-2"></i>
                            Configuração de Email
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $tab === 'templates' ? 'active' : ''; ?>" 
                                id="templates-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#templates" 
                                type="button" 
                                role="tab">
                            <i class="ti ti-file-text me-2"></i>
                            Templates de Email
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $tab === 'integrations' ? 'active' : ''; ?>" 
                                id="integrations-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#integrations" 
                                type="button" 
                                role="tab">
                            <i class="ti ti-plug me-2"></i>
                            Integrações
                        </button>
                    </li>
                </ul>

                <!-- Conteúdo das Abas -->
                <div class="tab-content" id="settingsTabsContent">
                    <!-- Aba Layout -->
                    <div class="tab-pane fade <?php echo $tab === 'layout' ? 'show active' : ''; ?>" 
                         id="layout" 
                         role="tabpanel">
                        <form action="<?php echo url('/settings/layout'); ?>" method="POST" enctype="multipart/form-data">
                            <?php echo csrf_field(); ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="mb-3">Logo do Sistema</h5>
                                    <p class="text-muted mb-4">
                                        Faça upload da logo que será exibida no sistema. 
                                        Formatos aceitos: PNG, JPG, SVG, GIF
                                    </p>
                                    
                                    <div class="mb-3">
                                        <label for="logo" class="form-label">Logo</label>
                                        <input type="file" 
                                               class="form-control" 
                                               id="logo" 
                                               name="logo" 
                                               accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/gif">
                                        <small class="text-muted">Tamanho recomendado: 200x50px</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Preview</label>
                                        <div class="border rounded p-3 bg-light">
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo $logoDark; ?>" 
                                                     id="logoPreview" 
                                                     alt="Logo Preview" 
                                                     style="max-height: 60px; max-width: 200px;">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-upload me-2"></i>
                                        Salvar Logo
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Aba Email -->
                    <div class="tab-pane fade <?php echo $tab === 'email' ? 'show active' : ''; ?>" 
                         id="email" 
                         role="tabpanel">
                        <form action="<?php echo url('/settings/email'); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            
                            <h5 class="mb-3">Configurações SMTP</h5>
                            <p class="text-muted mb-4">
                                Configure as credenciais SMTP para envio de emails pelo sistema.
                            </p>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="smtp_host" class="form-label">Servidor SMTP <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="smtp_host" 
                                           name="smtp_host" 
                                           value="<?php echo e($smtpConfig['host'] ?? ''); ?>" 
                                           required>
                                    <small class="text-muted">Ex: smtp.gmail.com</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="smtp_port" class="form-label">Porta <span class="text-danger">*</span></label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="smtp_port" 
                                           name="smtp_port" 
                                           value="<?php echo e($smtpConfig['port'] ?? '587'); ?>" 
                                           required>
                                    <small class="text-muted">Ex: 587 (TLS) ou 465 (SSL)</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="smtp_username" class="form-label">Usuário <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="smtp_username" 
                                           name="smtp_username" 
                                           value="<?php echo e($smtpConfig['username'] ?? ''); ?>" 
                                           required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="smtp_password" class="form-label">Senha <span class="text-danger">*</span></label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="smtp_password" 
                                           name="smtp_password" 
                                           value="<?php echo e($smtpConfig['password'] ?? ''); ?>" 
                                           required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="smtp_encryption" class="form-label">Criptografia <span class="text-danger">*</span></label>
                                    <select class="form-select" id="smtp_encryption" name="smtp_encryption" required>
                                        <option value="tls" <?php echo ($smtpConfig['encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                        <option value="ssl" <?php echo ($smtpConfig['encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="smtp_from_email" class="form-label">Email Remetente <span class="text-danger">*</span></label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="smtp_from_email" 
                                           name="smtp_from_email" 
                                           value="<?php echo e($smtpConfig['from_email'] ?? ''); ?>" 
                                           required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="smtp_from_name" class="form-label">Nome Remetente <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="smtp_from_name" 
                                           name="smtp_from_name" 
                                           value="<?php echo e($smtpConfig['from_name'] ?? ''); ?>" 
                                           required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-2"></i>
                                Salvar Configurações
                            </button>
                        </form>
                    </div>

                    <!-- Aba Templates -->
                    <div class="tab-pane fade <?php echo $tab === 'templates' ? 'show active' : ''; ?>" 
                         id="templates" 
                         role="tabpanel">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div>
                                <h5 class="mb-2">Templates de Email</h5>
                                <p class="text-muted mb-0">Gerencie os templates de email do sistema</p>
                            </div>
                            <a href="<?php echo url('/settings/templates/create'); ?>" class="btn btn-primary">
                                <i class="ti ti-plus me-2"></i>
                                Novo Template
                            </a>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Slug</th>
                                        <th>Assunto</th>
                                        <th>Status</th>
                                        <th class="text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($templates)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                Nenhum template encontrado.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($templates as $template): ?>
                                            <tr>
                                                <td><?php echo $template->id; ?></td>
                                                <td><strong><?php echo e($template->name); ?></strong></td>
                                                <td><code><?php echo e($template->slug); ?></code></td>
                                                <td><?php echo e($template->subject); ?></td>
                                                <td>
                                                    <?php if ($template->is_active): ?>
                                                        <span class="badge bg-success">Ativo</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inativo</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group">
                                                        <a href="<?php echo url('/settings/templates/' . $template->id . '/edit'); ?>" 
                                                           class="btn btn-sm btn-warning" 
                                                           title="Editar">
                                                            <i class="ti ti-edit"></i>
                                                        </a>
                                                        <form action="<?php echo url('/settings/templates/' . $template->id . '/delete'); ?>" 
                                                              method="POST" 
                                                              class="d-inline" 
                                                              onsubmit="return confirm('Tem certeza que deseja deletar este template?');">
                                                            <?php echo csrf_field(); ?>
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Deletar">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Aba Integrações -->
                    <div class="tab-pane fade <?php echo $tab === 'integrations' ? 'show active' : ''; ?>" 
                         id="integrations" 
                         role="tabpanel">
                        <form action="<?php echo url('/settings/integrations'); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            
                            <h5 class="mb-3">Integrações com APIs Externas</h5>
                            <p class="text-muted mb-4">
                                Configure as chaves de API para integrações com serviços externos.
                            </p>
                            
                            <?php
                            // Obtém configurações existentes
                            $geminiApiKey = \App\Models\SystemSetting::get('gemini_api_key', '');
                            $apizapConfig = \App\Models\SystemSetting::get('apizap_config', []);
                            $resendConfig = \App\Models\SystemSetting::get('resend_config', []);
                            ?>
                            
                            <!-- Google Gemini -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="ti ti-brand-google me-2"></i>Google Gemini</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="gemini_api_key" class="form-label">
                                            Google Gemini API Key
                                        </label>
                                        <div class="input-group">
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="gemini_api_key" 
                                                   name="gemini_api_key" 
                                                   value="" 
                                                   placeholder="<?php echo $geminiApiKey ? 'Digite nova chave para alterar' : 'Digite sua API Key do Google Gemini'; ?>"
                                                   autocomplete="new-password">
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('gemini_api_key', 'toggleGeminiIcon')">
                                                <i class="ti ti-eye" id="toggleGeminiIcon"></i>
                                            </button>
                                        </div>
                                        <?php if ($geminiApiKey): ?>
                                            <div class="alert alert-info mt-2 mb-0">
                                                <i class="ti ti-info-circle me-2"></i>
                                                <strong>Chave configurada:</strong> 
                                                <code><?php echo substr($geminiApiKey, 0, 8); ?>...</code>
                                                <br>
                                                <small>Deixe o campo acima vazio para manter a chave atual, ou digite uma nova para alterar.</small>
                                            </div>
                                        <?php endif; ?>
                                        <small class="text-muted d-block mt-2">
                                            <i class="ti ti-info-circle me-1"></i>
                                            Obtenha sua API Key em: 
                                            <a href="https://makersuite.google.com/app/apikey" target="_blank">
                                                Google AI Studio
                                                <i class="ti ti-external-link ms-1"></i>
                                            </a>
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- APIzap (WhatsApp) -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="ti ti-brand-whatsapp me-2"></i>APIzap - WhatsApp</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="apizap_instance_key" class="form-label">
                                                Instance Key <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="apizap_instance_key" 
                                                   name="apizap_instance_key" 
                                                   value="<?php echo e($apizapConfig['instance_key'] ?? ''); ?>" 
                                                   placeholder="Ex: instance_691dff417b1f1">
                                            <small class="text-muted">Chave da instância do WhatsApp</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="apizap_token" class="form-label">
                                                Token de Segurança <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <input type="password" 
                                                       class="form-control" 
                                                       id="apizap_token" 
                                                       name="apizap_token" 
                                                       value="" 
                                                       placeholder="<?php echo !empty($apizapConfig['token']) ? 'Digite novo token para alterar' : 'Digite o token de segurança'; ?>"
                                                       autocomplete="new-password">
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('apizap_token', 'toggleApizapIcon')">
                                                    <i class="ti ti-eye" id="toggleApizapIcon"></i>
                                                </button>
                                            </div>
                                            <?php if (!empty($apizapConfig['token'])): ?>
                                                <div class="alert alert-info mt-2 mb-0">
                                                    <i class="ti ti-info-circle me-2"></i>
                                                    <strong>Token configurado:</strong> 
                                                    <code><?php echo substr($apizapConfig['token'], 0, 8); ?>...</code>
                                                    <br>
                                                    <small>Deixe o campo acima vazio para manter o token atual.</small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <i class="ti ti-info-circle me-1"></i>
                                        Configure suas credenciais da APIzap para envio de mensagens WhatsApp.
                                    </small>
                                </div>
                            </div>

                            <!-- Resend (Email) -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="ti ti-mail me-2"></i>Resend - Email</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="resend_api_key" class="form-label">
                                                API Key <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <input type="password" 
                                                       class="form-control" 
                                                       id="resend_api_key" 
                                                       name="resend_api_key" 
                                                       value="" 
                                                       placeholder="<?php echo !empty($resendConfig['api_key']) ? 'Digite nova chave para alterar' : 'Digite sua API Key do Resend'; ?>"
                                                       autocomplete="new-password">
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('resend_api_key', 'toggleResendIcon')">
                                                    <i class="ti ti-eye" id="toggleResendIcon"></i>
                                                </button>
                                            </div>
                                            <?php if (!empty($resendConfig['api_key'])): ?>
                                                <div class="alert alert-info mt-2 mb-0">
                                                    <i class="ti ti-info-circle me-2"></i>
                                                    <strong>Chave configurada:</strong> 
                                                    <code><?php echo substr($resendConfig['api_key'], 0, 8); ?>...</code>
                                                    <br>
                                                    <small>Deixe o campo acima vazio para manter a chave atual.</small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="resend_from_email" class="form-label">
                                                Email Remetente
                                            </label>
                                            <input type="email" 
                                                   class="form-control" 
                                                   id="resend_from_email" 
                                                   name="resend_from_email" 
                                                   value="<?php echo e($resendConfig['from_email'] ?? 'noreply@email.byte0.com.br'); ?>" 
                                                   placeholder="noreply@email.byte0.com.br">
                                            <small class="text-muted">Email padrão para envio (opcional)</small>
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <i class="ti ti-info-circle me-1"></i>
                                        Configure suas credenciais do Resend para envio de emails. 
                                        <a href="https://resend.com/api-keys" target="_blank">
                                            Obtenha sua API Key
                                            <i class="ti ti-external-link ms-1"></i>
                                        </a>
                                    </small>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-2"></i>
                                Salvar Configurações
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preview da logo ao selecionar arquivo
    const logoInput = document.getElementById('logo');
    const logoPreview = document.getElementById('logoPreview');
    
    if (logoInput && logoPreview) {
        logoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    logoPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Ativa a aba correta baseada no parâmetro da URL
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab) {
        const tabButton = document.getElementById(tab + '-tab');
        if (tabButton) {
            const tabInstance = new bootstrap.Tab(tabButton);
            tabInstance.show();
        }
    }
});

// Função genérica para mostrar/ocultar senha
function togglePasswordVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
    if (input && icon) {
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('ti-eye');
            icon.classList.add('ti-eye-off');
        } else {
            input.type = 'password';
            icon.classList.remove('ti-eye-off');
            icon.classList.add('ti-eye');
        }
    }
}
</script>

<?php
// Captura o conteúdo
$content = ob_get_clean();

// Inclui o layout
include base_path('views/layouts/app.php');
?>

