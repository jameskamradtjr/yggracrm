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
                'options' => [], // Será preenchido dinamicamente
                'loadOptions' => 'email-templates' // Indica que as opções devem ser carregadas da API
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
        // Obtém a execução atual para logs
        $execution = $triggerData['_execution'] ?? null;
        
        if ($execution) {
            $execution->addLog("Ação SendEmail: Iniciando envio de email", [
                'template_slug' => $config['template_slug'] ?? 'N/A',
                'to' => $config['to'] ?? 'N/A'
            ]);
        }
        
        if (!isset($config['template_slug']) || !isset($config['to'])) {
            if ($execution) {
                $execution->addLog("Ação SendEmail: Configuração incompleta", [
                    'config' => $config
                ]);
            }
            return false;
        }
        
        $template = EmailTemplate::getBySlug($config['template_slug']);
        if (!$template) {
            if ($execution) {
                $execution->addLog("Ação SendEmail: Template não encontrado", [
                    'template_slug' => $config['template_slug']
                ]);
            }
            return false;
        }
        
        if ($execution) {
            $execution->addLog("Ação SendEmail: Template encontrado", [
                'template_id' => $template->id ?? 'N/A',
                'template_name' => $template->name ?? 'N/A'
            ]);
        }
        
        // Processa variáveis
        $variables = $this->processVariables($config['variables'] ?? '{}', $triggerData);
        
        if ($execution) {
            $execution->addLog("Ação SendEmail: Variáveis processadas", [
                'variables' => $variables
            ]);
        }
        
        // Processa template
        $processed = $template->process($variables);
        
        if ($execution) {
            $execution->addLog("Ação SendEmail: Template processado", [
                'subject' => $processed['subject'] ?? 'N/A',
                'body_length' => strlen($processed['body'] ?? ''),
                'body_preview' => substr($processed['body'] ?? '', 0, 100) . (strlen($processed['body'] ?? '') > 100 ? '...' : '')
            ]);
        }
        
        // Processa email do destinatário (pode ter variáveis como {{email}})
        $to = $this->processEmail($config['to'], $triggerData);
        
        if ($execution) {
            $execution->addLog("Ação SendEmail: Email do destinatário processado", [
                'to' => $to
            ]);
        }
        
        // Envia email
        try {
            if ($execution) {
                $execution->addLog("Ação SendEmail: Tentando enviar email", [
                    'to' => $to,
                    'template_slug' => $config['template_slug']
                ]);
            }
            
            $smtp = new SmtpService();
            $result = $smtp->sendEmail(
                $to,
                $processed['subject'],
                $processed['body']
            );
            
            if ($result['success']) {
                if ($execution) {
                    $execution->addLog("Ação SendEmail: Email enviado com sucesso", [
                        'to' => $to,
                        'result' => $result
                    ]);
                }
                return true;
            } else {
                if ($execution) {
                    $execution->addLog("Ação SendEmail: Erro ao enviar email", [
                        'error' => $result['message'] ?? 'Erro desconhecido',
                        'to' => $to,
                        'template_slug' => $config['template_slug']
                    ]);
                }
                error_log("Erro ao enviar email na automação: " . ($result['message'] ?? 'Erro desconhecido'));
                return false;
            }
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            error_log("Erro ao enviar email na automação: " . $errorMsg);
            
            if ($execution) {
                $execution->addLog("Ação SendEmail: Exceção ao enviar email", [
                    'error' => $errorMsg,
                    'to' => $to,
                    'template_slug' => $config['template_slug'],
                    'trace' => $e->getTraceAsString()
                ]);
            }
            
            return false;
        }
    }
    
    private function processEmail(string $email, array $triggerData): string
    {
        // Se o email contém variáveis como {{email}}, substitui
        if (preg_match('/\{\{(\w+)\}\}/', $email, $matches)) {
            $varName = $matches[1];
            if (isset($triggerData[$varName])) {
                return $triggerData[$varName];
            }
        }
        
        return $email;
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

