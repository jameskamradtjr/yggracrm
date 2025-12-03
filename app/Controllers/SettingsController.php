<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\SystemSetting;
use App\Models\EmailTemplate;
use App\Models\WhatsAppTemplate;
use App\Models\SistemaLog;

/**
 * Controller de Configurações do Sistema
 * 
 * Apenas admin master pode acessar
 */
class SettingsController extends Controller
{
    /**
     * Verifica se o usuário é admin master
     */
    private function checkAdminMaster(): void
    {
        $user = auth()->user();
        
        if (!$user) {
            abort(403, 'Acesso negado.');
        }

        // Verifica se tem role admin ou super-admin
        if (!$user->hasRole('admin') && !$user->hasRole('super-admin')) {
            abort(403, 'Apenas administradores podem acessar esta página.');
        }
    }

    /**
     * Exibe página de configurações com abas
     */
    public function index(): string
    {
        $this->checkAdminMaster();

        $tab = $this->request->input('tab', 'layout');

        // Dados para cada aba
        $layoutSettings = SystemSetting::getByGroup('layout');
        $emailSettings = SystemSetting::getByGroup('email');
        $templates = EmailTemplate::all();
        $whatsappTemplates = WhatsAppTemplate::all();

        return $this->view('settings/index', [
            'tab' => $tab,
            'layoutSettings' => $layoutSettings,
            'emailSettings' => $emailSettings,
            'templates' => $templates,
            'whatsappTemplates' => $whatsappTemplates
        ]);
    }

    /**
     * Salva configurações de layout
     */
    public function saveLayout(): void
    {
        $this->checkAdminMaster();

        error_log("========== Settings saveLayout iniciado ==========");
        error_log("POST data: " . json_encode($_POST));
        error_log("REQUEST method: " . $_SERVER['REQUEST_METHOD']);

        // Valida CSRF
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            error_log("Settings: CSRF token inválido");
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/settings?tab=layout');
        }

        error_log("Settings: CSRF validado com sucesso");

        // Upload de logo via base64 para S3 (igual ao /profile)
        $logoBase64 = trim($this->request->input('logo_base64', ''));
        
        error_log("Settings: logo_base64 recebido? " . (!empty($logoBase64) ? 'SIM (tamanho: ' . strlen($logoBase64) . ')' : 'NÃO (vazio)'));
        
        if (!empty($logoBase64) && strlen($logoBase64) > 100) {
            try {
                error_log("Settings: Processando upload de logo para S3");
                
                // Remove prefixo data:image/...;base64,
                if (strpos($logoBase64, 'data:image') === 0) {
                    $logoBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $logoBase64);
                }
                
                // Decodifica base64
                $imageData = base64_decode($logoBase64);
                
                if ($imageData === false || empty($imageData)) {
                    error_log("Settings: Erro ao decodificar base64");
                    session()->flash('error', 'Erro ao processar imagem.');
                    $this->redirect('/settings?tab=layout');
                    return;
                }
                
                error_log("Settings: Imagem decodificada, tamanho: " . strlen($imageData) . " bytes");
                
                // Cria arquivo temporário SEM extensão primeiro
                $tempFile = tempnam(sys_get_temp_dir(), 'logo_');
                file_put_contents($tempFile, $imageData);
                
                error_log("Settings: Arquivo temporário criado: {$tempFile}");
                
                // Detecta tipo MIME
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $tempFile);
                finfo_close($finfo);
                
                error_log("Settings: Tipo MIME detectado: {$mimeType}");
                
                // Mapeia MIME para extensão
                $mimeToExtension = [
                    'image/png' => 'png',
                    'image/jpeg' => 'jpg',
                    'image/jpg' => 'jpg',
                    'image/gif' => 'gif',
                    'image/svg+xml' => 'svg'
                ];
                
                if (!isset($mimeToExtension[$mimeType])) {
                    @unlink($tempFile);
                    error_log("Settings: Tipo de arquivo não permitido: {$mimeType}");
                    session()->flash('error', 'Tipo de arquivo não permitido. Use PNG, JPG, SVG ou GIF.');
                    $this->redirect('/settings?tab=layout');
                    return;
                }
                
                $extension = $mimeToExtension[$mimeType];
                
                // Renomeia arquivo temporário COM extensão
                $tempFileWithExt = $tempFile . '.' . $extension;
                rename($tempFile, $tempFileWithExt);
                
                error_log("Settings: Arquivo renomeado com extensão: {$tempFileWithExt}");
                
                // Upload para S3 público
                $userId = auth()->getDataUserId();
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
                
                error_log("Settings: Iniciando upload para S3");
                error_log("Settings: - userId: {$userId}");
                error_log("Settings: - subfolder: logos");
                error_log("Settings: - arquivo: {$tempFileWithExt}");
                error_log("Settings: - tamanho: " . filesize($tempFileWithExt) . " bytes");
                error_log("Settings: - extensões permitidas: " . implode(', ', $allowedExtensions));
                
                // Verifica se S3 está configurado
                try {
                    $s3 = s3_public();
                    error_log("Settings: S3 público inicializado: " . get_class($s3));
                } catch (\Exception $e) {
                    error_log("Settings: ✗ ERRO ao inicializar S3: " . $e->getMessage());
                    @unlink($tempFileWithExt);
                    session()->flash('error', 'S3 não está configurado corretamente: ' . $e->getMessage());
                    $this->redirect('/settings?tab=layout');
                    return;
                }
                
                $logoUrl = s3_upload_public($tempFileWithExt, $userId, 'logos', $allowedExtensions);
                
                error_log("Settings: Resultado do s3_upload_public: " . ($logoUrl ? "URL: {$logoUrl}" : 'FALSE (falhou)'));
                
                // Remove arquivo temporário
                @unlink($tempFileWithExt);
                
                if ($logoUrl && $logoUrl !== false) {
                    error_log("Settings: ✓ Logo enviada para S3 com sucesso: {$logoUrl}");
                    
                    // Remove logo antiga do S3 se existir
                    $oldLogo = SystemSetting::get('logo_dark');
                    if ($oldLogo && (strpos($oldLogo, 's3.') !== false || strpos($oldLogo, 'amazonaws.com') !== false)) {
                        // Extrai a chave S3 da URL antiga
                        if (preg_match('/amazonaws\.com\/(.+)$/', $oldLogo, $matches)) {
                            $oldS3Key = urldecode($matches[1]);
                            $deleted = s3_delete_public($oldS3Key);
                            error_log("Settings: Logo antiga " . ($deleted ? '✓ removida' : '✗ não removida') . " do S3: {$oldS3Key}");
                        }
                    }
                    
                    // Salva URL do S3 no banco
                    SystemSetting::set('logo_dark', $logoUrl, 'image', 'layout', 'Logo escura do sistema');
                    SystemSetting::set('logo_light', $logoUrl, 'image', 'layout', 'Logo clara do sistema');
                    
                    error_log("Settings: ✓ URL da logo salva no banco de dados");
                    
                    // Registra log
                    SistemaLog::registrar(
                        'system_settings',
                        'UPDATE',
                        null,
                        'Logo do sistema atualizada (S3)',
                        ['logo_antiga' => $oldLogo],
                        ['logo_nova' => $logoUrl]
                    );
                    
                    session()->flash('success', 'Logo atualizada com sucesso!');
                } else {
                    // Tenta capturar erro mais detalhado
                    $s3 = s3_public();
                    $errorMsg = $s3->getLastError() ?: 'Erro desconhecido';
                    
                    error_log("Settings: ✗ Upload falhou. Resultado: " . var_export($logoUrl, true));
                    error_log("Settings: ✗ Erro S3: {$errorMsg}");
                    error_log("Settings: ✗ Verificar se bucket S3 público está configurado");
                    error_log("Settings: ✗ Verificar credenciais AWS no .env");
                    
                    session()->flash('error', 'Erro ao fazer upload da logo. Verifique a configuração do S3. Detalhes: ' . $errorMsg);
                }
            } catch (\Exception $e) {
                error_log("Settings: ✗ Exceção: " . $e->getMessage());
                error_log("Settings: Stack trace: " . $e->getTraceAsString());
                session()->flash('error', 'Erro ao processar logo: ' . $e->getMessage());
            }
        } else {
            error_log("Settings: Nenhuma logo enviada ou base64 muito pequeno");
            session()->flash('info', 'Nenhuma alteração foi feita. Selecione uma imagem.');
        }

        error_log("Settings: Redirecionando para /settings?tab=layout");
        $this->redirect('/settings?tab=layout');
    }

    /**
     * Salva configurações de email (SMTP)
     */
    public function saveEmail(): void
    {
        $this->checkAdminMaster();

        // Valida CSRF
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/settings?tab=email');
        }

        $data = $this->validate([
            'smtp_host' => 'required',
            'smtp_port' => 'required|integer',
            'smtp_username' => 'required',
            'smtp_password' => 'required',
            'smtp_encryption' => 'required|in:tls,ssl',
            'smtp_from_email' => 'required|email',
            'smtp_from_name' => 'required'
        ]);

        // Salva configurações SMTP
        $smtpConfig = [
            'host' => $data['smtp_host'],
            'port' => (int)$data['smtp_port'],
            'username' => $data['smtp_username'],
            'password' => $data['smtp_password'],
            'encryption' => $data['smtp_encryption'],
            'from_email' => $data['smtp_from_email'],
            'from_name' => $data['smtp_from_name']
        ];

        // Obtém configuração anterior para log
        $oldConfig = SystemSetting::get('smtp_config', []);
        
        SystemSetting::set('smtp_config', $smtpConfig, 'json', 'email', 'Configurações SMTP');
        
        // Registra log (sem mostrar senha)
        $smtpConfigLog = $smtpConfig;
        $smtpConfigLog['password'] = '***';
        $oldConfigLog = $oldConfig;
        if (isset($oldConfigLog['password'])) {
            $oldConfigLog['password'] = '***';
        }
        
        SistemaLog::registrar(
            'system_settings',
            'UPDATE',
            null,
            'Configurações SMTP atualizadas',
            ['config_anterior' => $oldConfigLog],
            ['config_nova' => $smtpConfigLog]
        );
        
        session()->flash('success', 'Configurações de email salvas com sucesso!');
        $this->redirect('/settings?tab=email');
    }

    /**
     * Exibe formulário de criação de template
     */
    public function createTemplate(): string
    {
        $this->checkAdminMaster();

        return $this->view('settings/templates/create');
    }

    /**
     * Salva novo template
     */
    public function storeTemplate(): void
    {
        $this->checkAdminMaster();

        // Valida CSRF
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/settings/templates/create');
        }

        $data = $this->validate([
            'name' => 'required|min:3|max:100',
            'slug' => 'required|unique:email_templates,slug',
            'subject' => 'required',
            'body' => 'required'
        ]);

        $variables = $this->request->input('variables', '');
        if (!empty($variables)) {
            $data['variables'] = json_encode(explode(',', $variables));
        }

        $template = EmailTemplate::create($data);

        // Registra log
        SistemaLog::registrar(
            'email_templates',
            'CREATE',
            $template->id,
            "Template de email '{$template->name}' criado",
            null,
            ['name' => $template->name, 'slug' => $template->slug, 'subject' => $template->subject]
        );

        session()->flash('success', 'Template criado com sucesso!');
        $this->redirect('/settings?tab=templates');
    }

    /**
     * Exibe formulário de edição de template
     */
    public function editTemplate(array $params): string
    {
        $this->checkAdminMaster();

        $template = EmailTemplate::find($params['id']);

        if (!$template) {
            abort(404, 'Template não encontrado.');
        }

        return $this->view('settings/templates/edit', ['template' => $template]);
    }

    /**
     * Atualiza template
     */
    public function updateTemplate(array $params): void
    {
        $this->checkAdminMaster();

        // Valida CSRF
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/settings/templates/' . $params['id'] . '/edit');
        }

        $template = EmailTemplate::find($params['id']);

        if (!$template) {
            abort(404, 'Template não encontrado.');
        }

        $data = $this->validate([
            'name' => 'required|min:3|max:100',
            'subject' => 'required',
            'body' => 'required'
        ]);

        // Verifica slug único se foi alterado
        if ($this->request->input('slug') !== $template->slug) {
            $data['slug'] = $this->request->input('slug');
            $existing = EmailTemplate::where('slug', $data['slug'])->first();
            if ($existing && $existing->id !== $template->id) {
                session()->flash('error', 'Este slug já está em uso.');
                $this->redirect('/settings/templates/' . $params['id'] . '/edit');
            }
        }

        $variables = $this->request->input('variables', '');
        if (!empty($variables)) {
            $data['variables'] = json_encode(explode(',', $variables));
        }

        $data['is_active'] = $this->request->has('is_active') ? 1 : 0;

        // Dados anteriores para log
        $dadosAnteriores = [
            'name' => $template->name,
            'slug' => $template->slug,
            'subject' => $template->subject,
            'is_active' => $template->is_active
        ];

        $template->update($data);

        // Registra log
        SistemaLog::registrar(
            'email_templates',
            'UPDATE',
            $template->id,
            "Template de email '{$template->name}' atualizado",
            $dadosAnteriores,
            ['name' => $data['name'], 'slug' => $data['slug'] ?? $template->slug, 'subject' => $data['subject'], 'is_active' => $data['is_active']]
        );

        session()->flash('success', 'Template atualizado com sucesso!');
        $this->redirect('/settings?tab=templates');
    }

    /**
     * Deleta template
     */
    public function deleteTemplate(array $params): void
    {
        $this->checkAdminMaster();

        $template = EmailTemplate::find($params['id']);

        if (!$template) {
            abort(404, 'Template não encontrado.');
        }

        // Dados para log antes de deletar
        $dadosTemplate = [
            'name' => $template->name,
            'slug' => $template->slug,
            'subject' => $template->subject
        ];

        $template->delete();

        // Registra log
        SistemaLog::registrar(
            'email_templates',
            'DELETE',
            $params['id'],
            "Template de email '{$dadosTemplate['name']}' deletado",
            $dadosTemplate,
            null
        );

        session()->flash('success', 'Template deletado com sucesso!');
        $this->redirect('/settings?tab=templates');
    }

    /**
     * Exibe formulário de criação de template WhatsApp
     */
    public function createWhatsAppTemplate(): string
    {
        $this->checkAdminMaster();

        return $this->view('settings/whatsapp-templates/create');
    }

    /**
     * Salva novo template WhatsApp
     */
    public function storeWhatsAppTemplate(): void
    {
        $this->checkAdminMaster();

        // Valida CSRF
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/settings/whatsapp-templates/create');
        }

        $data = $this->validate([
            'name' => 'required|max:255',
            'slug' => 'required|unique:whatsapp_templates,slug',
            'message' => 'required'
        ]);

        $data['is_active'] = $this->request->has('is_active') ? 1 : 0;

        $variables = $this->request->input('variables', '');
        if (!empty($variables)) {
            $data['variables'] = json_encode(explode(',', $variables));
        }

        $template = WhatsAppTemplate::create($data);

        // Registra log
        SistemaLog::registrar(
            'whatsapp_templates',
            'CREATE',
            $template->id,
            "Template de WhatsApp '{$template->name}' criado",
            null,
            ['name' => $template->name, 'slug' => $template->slug]
        );

        session()->flash('success', 'Template criado com sucesso!');
        $this->redirect('/settings?tab=whatsapp-templates');
    }

    /**
     * Exibe formulário de edição de template WhatsApp
     */
    public function editWhatsAppTemplate(array $params): string
    {
        $this->checkAdminMaster();

        $template = WhatsAppTemplate::find($params['id']);

        if (!$template) {
            abort(404, 'Template não encontrado.');
        }

        return $this->view('settings/whatsapp-templates/edit', ['template' => $template]);
    }

    /**
     * Atualiza template WhatsApp
     */
    public function updateWhatsAppTemplate(array $params): void
    {
        $this->checkAdminMaster();

        // Valida CSRF
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/settings/whatsapp-templates/' . $params['id'] . '/edit');
        }

        $template = WhatsAppTemplate::find($params['id']);

        if (!$template) {
            abort(404, 'Template não encontrado.');
        }

        $data = $this->validate([
            'name' => 'required|max:255',
            'slug' => 'required|unique:whatsapp_templates,slug,' . $template->id,
            'message' => 'required'
        ]);

        $data['is_active'] = $this->request->has('is_active') ? 1 : 0;

        $variables = $this->request->input('variables', '');
        if (!empty($variables)) {
            $data['variables'] = json_encode(explode(',', $variables));
        } else {
            $data['variables'] = null;
        }

        // Dados anteriores para log
        $dadosAnteriores = [
            'name' => $template->name,
            'slug' => $template->slug,
            'is_active' => $template->is_active
        ];

        $template->update($data);

        // Registra log
        SistemaLog::registrar(
            'whatsapp_templates',
            'UPDATE',
            $template->id,
            "Template de WhatsApp '{$template->name}' atualizado",
            $dadosAnteriores,
            ['name' => $data['name'], 'slug' => $data['slug'] ?? $template->slug, 'is_active' => $data['is_active']]
        );

        session()->flash('success', 'Template atualizado com sucesso!');
        $this->redirect('/settings?tab=whatsapp-templates');
    }

    /**
     * Deleta template WhatsApp
     */
    public function deleteWhatsAppTemplate(array $params): void
    {
        $this->checkAdminMaster();

        $template = WhatsAppTemplate::find($params['id']);

        if (!$template) {
            abort(404, 'Template não encontrado.');
        }

        // Dados para log antes de deletar
        $dadosTemplate = [
            'name' => $template->name,
            'slug' => $template->slug
        ];

        $template->delete();

        // Registra log
        SistemaLog::registrar(
            'whatsapp_templates',
            'DELETE',
            $params['id'],
            "Template de WhatsApp '{$dadosTemplate['name']}' deletado",
            $dadosTemplate,
            null
        );

        session()->flash('success', 'Template deletado com sucesso!');
        $this->redirect('/settings?tab=whatsapp-templates');
    }

    /**
     * Salva configurações de integrações
     */
    public function saveIntegrations(): void
    {
        $this->checkAdminMaster();

        // Valida CSRF
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/settings?tab=integrations');
        }

        // Google Gemini
        $geminiApiKey = trim($this->request->input('gemini_api_key', ''));
        $oldGeminiApiKey = SystemSetting::get('gemini_api_key');
        
        if (empty($geminiApiKey) && !empty($oldGeminiApiKey)) {
            $geminiApiKey = $oldGeminiApiKey;
        } else if (!empty($geminiApiKey)) {
            SystemSetting::set('gemini_api_key', $geminiApiKey, 'text', 'integrations', 'API Key do Google Gemini');
        }

        // APIzap
        $apizapInstanceKey = trim($this->request->input('apizap_instance_key', ''));
        $apizapToken = trim($this->request->input('apizap_token', ''));
        
        $oldApizapConfig = SystemSetting::get('apizap_config', []);
        $apizapConfig = [
            'instance_key' => $apizapInstanceKey,
            'token' => !empty($apizapToken) ? $apizapToken : ($oldApizapConfig['token'] ?? '')
        ];
        
        if (!empty($apizapInstanceKey)) {
            SystemSetting::set('apizap_config', $apizapConfig, 'json', 'integrations', 'Configurações da APIzap');
        }

        // Resend
        $resendApiKey = trim($this->request->input('resend_api_key', ''));
        $resendFromEmail = trim($this->request->input('resend_from_email', 'noreply@email.byte0.com.br'));
        
        $oldResendConfig = SystemSetting::get('resend_config', []);
        $resendConfig = [
            'api_key' => !empty($resendApiKey) ? $resendApiKey : ($oldResendConfig['api_key'] ?? ''),
            'from_email' => $resendFromEmail
        ];
        
        if (!empty($resendApiKey) || !empty($oldResendConfig['api_key'])) {
            SystemSetting::set('resend_config', $resendConfig, 'json', 'integrations', 'Configurações do Resend');
        }

        // Registra log (sem mostrar as chaves completas)
        $logData = [
            'gemini_api_key' => ($geminiApiKey && is_string($geminiApiKey)) ? substr($geminiApiKey, 0, 8) . '...' : 'não configurada',
            'apizap_instance_key' => $apizapInstanceKey ?: 'não configurada',
            'apizap_token' => (!empty($apizapConfig['token']) && is_string($apizapConfig['token'])) ? substr($apizapConfig['token'], 0, 8) . '...' : 'não configurada',
            'resend_api_key' => (!empty($resendConfig['api_key']) && is_string($resendConfig['api_key'])) ? substr($resendConfig['api_key'], 0, 8) . '...' : 'não configurada',
            'resend_from_email' => $resendFromEmail
        ];
        
        SistemaLog::registrar(
            'system_settings',
            'UPDATE',
            null,
            'Configurações de integrações atualizadas',
            [],
            $logData
        );
        
        session()->flash('success', 'Configurações de integrações salvas com sucesso!');
        $this->redirect('/settings?tab=integrations');
    }
}

