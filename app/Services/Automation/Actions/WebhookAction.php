<?php

declare(strict_types=1);

namespace App\Services\Automation\Actions;

use App\Services\Automation\BaseAction;

class WebhookAction extends BaseAction
{
    public function __construct()
    {
        parent::__construct(
            'webhook',
            'Webhook',
            'Envia dados para uma URL via webhook'
        );
    }
    
    public function getConfigSchema(): array
    {
        return [
            [
                'name' => 'url',
                'label' => 'URL do Webhook',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'https://exemplo.com/webhook'
            ],
            [
                'name' => 'method',
                'label' => 'Método HTTP',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => 'POST', 'label' => 'POST'],
                    ['value' => 'GET', 'label' => 'GET'],
                    ['value' => 'PUT', 'label' => 'PUT']
                ]
            ],
            [
                'name' => 'headers',
                'label' => 'Headers (JSON)',
                'type' => 'textarea',
                'required' => false,
                'placeholder' => '{"Authorization": "Bearer token"}'
            ],
            [
                'name' => 'body',
                'label' => 'Body (JSON)',
                'type' => 'textarea',
                'required' => false,
                'placeholder' => '{"data": "{{trigger_data}}"}'
            ]
        ];
    }
    
    public function execute(array $triggerData, array $config): bool
    {
        if (!isset($config['url'])) {
            return false;
        }
        
        $url = $config['url'];
        $method = $config['method'] ?? 'POST';
        $headers = json_decode($config['headers'] ?? '{}', true) ?? [];
        $body = $this->processBody($config['body'] ?? '{}', $triggerData);
        
        try {
            $ch = curl_init($url);
            
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->formatHeaders($headers));
            
            if (in_array($method, ['POST', 'PUT']) && !empty($body)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $httpCode >= 200 && $httpCode < 300;
        } catch (\Exception $e) {
            error_log("Erro ao executar webhook na automação: " . $e->getMessage());
            return false;
        }
    }
    
    private function processBody(string $bodyJson, array $triggerData): array
    {
        $body = json_decode($bodyJson, true) ?? [];
        
        // Substitui variáveis do trigger
        array_walk_recursive($body, function(&$value) use ($triggerData) {
            if (is_string($value) && preg_match('/\{\{(\w+)\}\}/', $value, $matches)) {
                $varName = $matches[1];
                if (isset($triggerData[$varName])) {
                    $value = $triggerData[$varName];
                }
            }
        });
        
        return $body;
    }
    
    private function formatHeaders(array $headers): array
    {
        $formatted = ['Content-Type: application/json'];
        
        foreach ($headers as $key => $value) {
            $formatted[] = "$key: $value";
        }
        
        return $formatted;
    }
}

