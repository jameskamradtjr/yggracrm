<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;

/**
 * Serviço para envio de emails via Resend
 */
class ResendService
{
    private string $url = 'https://api.resend.com/emails';
    private ?string $apiKey = null;
    private string $fromEmail = 'noreply@email.byte0.com.br';

    public function __construct()
    {
        $config = SystemSetting::get('resend_config', []);
        $this->apiKey = $config['api_key'] ?? null;
        $this->fromEmail = $config['from_email'] ?? 'noreply@email.byte0.com.br';
    }

    /**
     * Verifica se o serviço está configurado
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Envia email
     * 
     * @param string $to Email do destinatário
     * @param string $subject Assunto do email
     * @param string $html Conteúdo HTML do email
     * @param string|null $from Email remetente (opcional, usa o padrão se não informado)
     * @return array ['success' => bool, 'response' => mixed, 'httpCode' => int]
     */
    public function sendEmail(string $to, string $subject, string $html, ?string $from = null): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'response' => ['error' => 'Resend não configurado. Configure em Configurações > Integrações.'],
                'httpCode' => 0
            ];
        }

        $fromEmail = $from ?? $this->fromEmail;

        $data = [
            'from' => $fromEmail,
            'to' => [$to],
            'subject' => $subject,
            'html' => $html
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return [
                'success' => false,
                'response' => ['error' => $curlError],
                'httpCode' => 0
            ];
        }

        return [
            'success' => $httpCode === 200,
            'response' => json_decode($response, true),
            'httpCode' => $httpCode
        ];
    }

    /**
     * Envia email com template (método auxiliar)
     * 
     * @param string $to Email do destinatário
     * @param string $subject Assunto do email
     * @param string $template Template HTML (pode conter variáveis como {nome}, {email}, etc.)
     * @param array $variables Variáveis para substituir no template
     * @param string|null $from Email remetente (opcional)
     * @return array
     */
    public function sendTemplate(string $to, string $subject, string $template, array $variables = [], ?string $from = null): array
    {
        $html = $template;
        
        // Substitui variáveis no template
        foreach ($variables as $key => $value) {
            $html = str_replace('{' . $key . '}', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $html);
        }

        return $this->sendEmail($to, $subject, $html, $from);
    }
}

