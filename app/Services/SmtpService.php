<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Serviço para envio de emails via SMTP
 */
class SmtpService
{
    private ?array $config = null;

    public function __construct()
    {
        $this->config = SystemSetting::get('smtp_config', []);
    }

    /**
     * Verifica se o serviço está configurado
     */
    public function isConfigured(): bool
    {
        return !empty($this->config['host']) && 
               !empty($this->config['username']) && 
               !empty($this->config['password']);
    }

    /**
     * Envia email
     * 
     * @param string $to Email do destinatário
     * @param string $subject Assunto do email
     * @param string $html Conteúdo HTML do email
     * @param string|null $from Email remetente (opcional)
     * @param string|null $fromName Nome do remetente (opcional)
     * @return array ['success' => bool, 'message' => string, 'error' => string|null]
     */
    public function sendEmail(string $to, string $subject, string $html, ?string $from = null, ?string $fromName = null): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'SMTP não configurado. Configure em Configurações > Integrações > Email.',
                'error' => 'SMTP não configurado'
            ];
        }

        try {
            $mail = new PHPMailer(true);

            // Configurações do servidor
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            $mail->SMTPSecure = $this->config['encryption'] ?? 'tls';
            $mail->Port = (int)($this->config['port'] ?? 587);
            $mail->CharSet = 'UTF-8';

            // Remetente
            $fromEmail = $from ?? ($this->config['from_email'] ?? 'noreply@sistemabase.com');
            $fromName = $fromName ?? ($this->config['from_name'] ?? 'Sistema');
            $mail->setFrom($fromEmail, $fromName);

            // Destinatário
            $mail->addAddress($to);

            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = strip_tags($html);

            // Envia
            $mail->send();

            return [
                'success' => true,
                'message' => 'Email enviado com sucesso',
                'error' => null
            ];
        } catch (Exception $e) {
            $errorInfo = isset($mail) ? $mail->ErrorInfo : $e->getMessage();
            return [
                'success' => false,
                'message' => 'Erro ao enviar email: ' . $errorInfo,
                'error' => $errorInfo
            ];
        }
    }
}

