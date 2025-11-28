<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\SystemSetting;
use App\Models\EmailTemplate;
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

        return $this->view('settings/index', [
            'tab' => $tab,
            'layoutSettings' => $layoutSettings,
            'emailSettings' => $emailSettings,
            'templates' => $templates
        ]);
    }

    /**
     * Salva configurações de layout
     */
    public function saveLayout(): void
    {
        $this->checkAdminMaster();

        // Valida CSRF
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/settings?tab=layout');
        }

        // Upload de logo
        if ($this->request->hasFile('logo')) {
            $file = $this->request->file('logo');
            
            if ($file && isset($file['error']) && $file['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml', 'image/gif'];
                
                if (!in_array($file['type'], $allowedTypes)) {
                    session()->flash('error', 'Tipo de arquivo não permitido. Use PNG, JPG, SVG ou GIF.');
                    $this->redirect('/settings?tab=layout');
                }

                // Cria diretório se não existir
                $uploadDir = base_path('public/uploads/logos');
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Gera nome único
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'logo_' . time() . '.' . $extension;
                $filepath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

                // Move arquivo
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    // Remove logo antiga se existir
                    $oldLogo = SystemSetting::get('logo_dark');
                    if ($oldLogo && strpos($oldLogo, '/uploads/') === 0) {
                        $oldPath = base_path('public' . $oldLogo);
                        if (file_exists($oldPath)) {
                            @unlink($oldPath);
                        }
                    }

                    // Salva caminho relativo
                    $logoPath = '/uploads/logos/' . $filename;
                    SystemSetting::set('logo_dark', $logoPath, 'image', 'layout', 'Logo escura do sistema');
                    SystemSetting::set('logo_light', $logoPath, 'image', 'layout', 'Logo clara do sistema');
                    
                    // Registra log
                    SistemaLog::registrar(
                        'system_settings',
                        'UPDATE',
                        null,
                        'Logo do sistema atualizada',
                        ['logo_antiga' => $oldLogo],
                        ['logo_nova' => $logoPath]
                    );
                    
                    session()->flash('success', 'Logo atualizada com sucesso!');
                } else {
                    session()->flash('error', 'Erro ao fazer upload da logo.');
                }
            }
        }

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

        // Obtém valor diretamente do request
        $geminiApiKey = trim($this->request->input('gemini_api_key', ''));

        // Obtém configuração anterior
        $oldApiKey = SystemSetting::get('gemini_api_key');
        
        // Se o campo está vazio mas já existe uma chave salva, mantém a chave existente
        // (isso acontece quando o campo é do tipo password e o usuário não preenche novamente)
        if (empty($geminiApiKey) && !empty($oldApiKey)) {
            // Mantém a chave existente, não atualiza
            $geminiApiKey = $oldApiKey;
        } else if (!empty($geminiApiKey)) {
            // Salva a nova chave apenas se não estiver vazia
            SystemSetting::set('gemini_api_key', $geminiApiKey, 'text', 'integrations', 'API Key do Google Gemini');
        }

        // Registra log (sem mostrar a chave completa)
        $oldApiKeyLog = $oldApiKey ? substr($oldApiKey, 0, 8) . '...' : 'não configurada';
        $newApiKeyLog = $geminiApiKey ? substr($geminiApiKey, 0, 8) . '...' : 'não configurada';
        
        SistemaLog::registrar(
            'system_settings',
            'UPDATE',
            null,
            'Configurações de integrações atualizadas',
            ['gemini_api_key' => $oldApiKeyLog],
            ['gemini_api_key' => $newApiKeyLog]
        );
        
        session()->flash('success', 'Configurações de integrações salvas com sucesso!');
        $this->redirect('/settings?tab=integrations');
    }
}

