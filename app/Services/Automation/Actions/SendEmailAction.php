<?php

declare(strict_types=1);

namespace App\Services\Automation\Actions;

use App\Services\Automation\BaseAction;
use App\Models\EmailTemplate;
use App\Services\SmtpService;

class SendEmailAction extends BaseAction
{
    public function __construct()
    {
        parent::__construct(
            'send_email',
            'Enviar Email',
            'Envia um email usando template'
        );
    }
    
    public function getConfigSchema(): array
    {
        return [
            [
                'name' => 'template_slug',
                'label' => 'Template de Email',
                'type' => 'select',
                'required' => true,
                'options' => [] // Será preenchido dinamicamente
            ],
            [
                'name' => 'to',
                'label' => 'Destinatário',
                'type' => 'text',
                'required' => true,
                'placeholder' => '{{email}} ou email@exemplo.com'
            ],
            [
                'name' => 'variables',
                'label' => 'Variáveis (JSON)',
                'type' => 'textarea',
                'required' => false,
                'placeholder' => '{"nome": "{{nome}}", "email": "{{email}}"}'
            ]
        ];
    }
    
    public function execute(array $triggerData, array $config): bool
    {
        if (!isset($config['template_slug']) || !isset($config['to'])) {
            return false;
        }
        
        $template = EmailTemplate::getBySlug($config['template_slug']);
        if (!$template) {
            return false;
        }
        
        // Processa variáveis
        $variables = $this->processVariables($config['variables'] ?? '{}', $triggerData);
        
        // Processa template
        $processed = $template->process($variables);
        
        // Envia email
        try {
            SmtpService::send(
                $config['to'],
                $processed['subject'],
                $processed['body']
            );
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao enviar email na automação: " . $e->getMessage());
            return false;
        }
    }
    
    private function processVariables(string $variablesJson, array $triggerData): array
    {
        $vars = json_decode($variablesJson, true) ?? [];
        
        // Substitui variáveis do trigger
        foreach ($vars as $key => $value) {
            if (is_string($value) && preg_match('/\{\{(\w+)\}\}/', $value, $matches)) {
                $varName = $matches[1];
                if (isset($triggerData[$varName])) {
                    $vars[$key] = $triggerData[$varName];
                }
            }
        }
        
        return $vars;
    }
}

