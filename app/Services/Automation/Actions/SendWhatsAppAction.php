<?php

declare(strict_types=1);

namespace App\Services\Automation\Actions;

use App\Services\Automation\BaseAction;
use App\Models\WhatsAppTemplate;
use App\Services\ApiZapService;

class SendWhatsAppAction extends BaseAction
{
    public function __construct()
    {
        parent::__construct(
            'send_whatsapp',
            'Enviar WhatsApp',
            'Envia uma mensagem WhatsApp usando template'
        );
    }
    
    public function getConfigSchema(): array
    {
        return [
            [
                'name' => 'template_slug',
                'label' => 'Template de WhatsApp',
                'type' => 'select',
                'required' => true,
                'options' => [], // Será preenchido dinamicamente
                'loadOptions' => 'whatsapp-templates' // Carrega templates dinamicamente
            ],
            [
                'name' => 'to',
                'label' => 'Número do WhatsApp',
                'type' => 'text',
                'required' => true,
                'placeholder' => '{{telefone}} ou 5511999999999'
            ],
            [
                'name' => 'variables',
                'label' => 'Variáveis (JSON)',
                'type' => 'textarea',
                'required' => false,
                'placeholder' => '{"nome": "{{nome}}", "telefone": "{{telefone}}"}'
            ]
        ];
    }
    
    public function execute(array $triggerData, array $config): bool
    {
        $execution = $triggerData['_execution'] ?? null;
        
        if (!isset($config['template_slug']) || !isset($config['to'])) {
            if ($execution) {
                $execution->addLog("Ação SendWhatsApp: Configuração incompleta", [
                    'config' => $config,
                    'missing' => [
                        'template_slug' => !isset($config['template_slug']),
                        'to' => !isset($config['to'])
                    ]
                ]);
            }
            return false;
        }
        
        if ($execution) {
            $execution->addLog("Ação SendWhatsApp: Iniciando envio", [
                'template_slug' => $config['template_slug'],
                'to_config' => $config['to']
            ]);
        }
        
        $template = WhatsAppTemplate::getBySlug($config['template_slug']);
        if (!$template) {
            if ($execution) {
                $execution->addLog("Ação SendWhatsApp: Template não encontrado", [
                    'template_slug' => $config['template_slug']
                ]);
            }
            return false;
        }
        
        if ($execution) {
            $execution->addLog("Ação SendWhatsApp: Template encontrado", [
                'template_id' => $template->id,
                'template_name' => $template->name
            ]);
        }
        
        // Processa variáveis
        $variables = $this->processVariables($config['variables'] ?? '{}', $triggerData);
        
        if ($execution) {
            $execution->addLog("Ação SendWhatsApp: Variáveis processadas", [
                'variables' => $variables
            ]);
        }
        
        // Processa template
        $message = $template->process($variables);
        
        if ($execution) {
            $execution->addLog("Ação SendWhatsApp: Mensagem processada", [
                'message_length' => strlen($message),
                'message_preview' => substr($message, 0, 100) . (strlen($message) > 100 ? '...' : '')
            ]);
        }
        
        // Processa número de telefone
        $phone = $this->processPhone($config['to'], $triggerData);
        
        if ($execution) {
            $execution->addLog("Ação SendWhatsApp: Telefone processado", [
                'phone' => $phone
            ]);
        }
        
        // Envia WhatsApp
        try {
            if ($execution) {
                $execution->addLog("Ação SendWhatsApp: Tentando enviar mensagem", [
                    'phone' => $phone,
                    'template_slug' => $config['template_slug']
                ]);
            }
            
            $apizap = new ApiZapService();
            $result = $apizap->sendMessage($phone, $message);
            
            if ($execution) {
                $execution->addLog("Ação SendWhatsApp: Mensagem enviada com sucesso", [
                    'phone' => $phone,
                    'result' => $result
                ]);
            }
            
            return true;
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            error_log("Erro ao enviar WhatsApp na automação: " . $errorMsg);
            
            if ($execution) {
                $execution->addLog("Ação SendWhatsApp: Erro ao enviar mensagem", [
                    'error' => $errorMsg,
                    'phone' => $phone,
                    'template_slug' => $config['template_slug'],
                    'trace' => $e->getTraceAsString()
                ]);
            }
            
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
    
    private function processPhone(string $phone, array $triggerData): string
    {
        if (preg_match('/\{\{(\w+)\}\}/', $phone, $matches)) {
            $varName = $matches[1];
            if (isset($triggerData[$varName])) {
                return $triggerData[$varName];
            }
        }
        
        return $phone;
    }
}

