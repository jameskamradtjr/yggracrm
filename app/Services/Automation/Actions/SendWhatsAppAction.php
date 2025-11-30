<?php

declare(strict_types=1);

namespace App\Services\Automation\Actions;

use App\Services\Automation\BaseAction;
use App\Models\WhatsAppTemplate;
use App\Services\APIzapService;

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
                'options' => [] // Será preenchido dinamicamente
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
        if (!isset($config['template_slug']) || !isset($config['to'])) {
            return false;
        }
        
        $template = WhatsAppTemplate::getBySlug($config['template_slug']);
        if (!$template) {
            return false;
        }
        
        // Processa variáveis
        $variables = $this->processVariables($config['variables'] ?? '{}', $triggerData);
        
        // Processa template
        $message = $template->process($variables);
        
        // Processa número de telefone
        $phone = $this->processPhone($config['to'], $triggerData);
        
        // Envia WhatsApp
        try {
            APIzapService::sendMessage($phone, $message);
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao enviar WhatsApp na automação: " . $e->getMessage());
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

