<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;

/**
 * Serviço para envio de mensagens WhatsApp via APIzap
 */
class ApiZapService
{
    private string $url = 'https://app.apizap.space/api/send-message-queue';
    private ?string $instanceKey = null;
    private ?string $token = null;

    public function __construct()
    {
        $config = SystemSetting::get('apizap_config', []);
        $this->instanceKey = $config['instance_key'] ?? null;
        $this->token = $config['token'] ?? null;
    }

    /**
     * Verifica se o serviço está configurado
     */
    public function isConfigured(): bool
    {
        return !empty($this->instanceKey) && !empty($this->token);
    }

    /**
     * Envia mensagem WhatsApp
     * 
     * @param string $to Número do destinatário (com DDD, sem caracteres especiais)
     * @param string $message Mensagem a ser enviada
     * @return array ['success' => bool, 'status' => string, 'response' => mixed, 'observacoes' => string|null]
     */
    public function sendMessage(string $to, string $message): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 'nao_enviado',
                'response' => null,
                'observacoes' => 'APIzap não configurada. Configure em Configurações > Integrações.'
            ];
        }

        // Remove caracteres especiais do telefone
        $phone = preg_replace('/[^0-9]/', '', $to);

        // Dados a serem enviados
        $data = [
            'instance_key' => $this->instanceKey,
            'to' => $phone,
            'message' => $message
        ];

        // Configuração da solicitação cURL
        $curl = curl_init($this->url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json'
        ]);

        // Executando a solicitação
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Verificando se houve algum erro
        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
            
            return [
                'success' => false,
                'status' => 'nao_enviado',
                'response' => null,
                'observacoes' => $error
            ];
        }

        // Fechando a conexão cURL
        curl_close($curl);

        // Verifica se foi enviado com sucesso (código 200 ou 201)
        $success = in_array($httpCode, [200, 201]);
        $responseData = json_decode($response, true);

        return [
            'success' => $success,
            'status' => $success ? 'enviado' : 'nao_enviado',
            'response' => $responseData,
            'observacoes' => $success ? null : ($responseData['message'] ?? $response)
        ];
    }

    /**
     * Envia mensagem com template (método auxiliar)
     * 
     * @param string $to Número do destinatário
     * @param string $template Template da mensagem (pode conter variáveis como {nome}, {email}, etc.)
     * @param array $variables Variáveis para substituir no template
     * @return array
     */
    public function sendTemplate(string $to, string $template, array $variables = []): array
    {
        $message = $template;
        
        // Substitui variáveis no template
        foreach ($variables as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        return $this->sendMessage($to, $message);
    }
}

