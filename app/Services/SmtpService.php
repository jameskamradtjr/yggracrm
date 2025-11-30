<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Serviço para envio de emails via SMTP
 */
class SmtpService
{
    private ?array $config = null;

    public function __construct()
    {
        $this->config = SystemSetting::get('smtp_config', []);
        
        // Log das configurações carregadas (para debug - com senha completa)
        if (!empty($this->config)) {
            error_log("SmtpService - Configurações carregadas da tabela system_settings (chave: smtp_config):");
            error_log("  Host: " . ($this->config['host'] ?? 'NÃO DEFINIDO'));
            error_log("  Port: " . ($this->config['port'] ?? 'NÃO DEFINIDO'));
            error_log("  Encryption: " . ($this->config['encryption'] ?? 'NÃO DEFINIDO'));
            error_log("  Username: " . ($this->config['username'] ?? 'NÃO DEFINIDO'));
            error_log("  Password: " . ($this->config['password'] ?? 'NÃO DEFINIDA'));
            error_log("  From Email: " . ($this->config['from_email'] ?? 'NÃO DEFINIDO'));
            error_log("  From Name: " . ($this->config['from_name'] ?? 'NÃO DEFINIDO'));
        } else {
            error_log("SmtpService - AVISO: Nenhuma configuração SMTP encontrada na tabela system_settings (chave: smtp_config)");
            error_log("SmtpService - Verifique se a configuração foi salva corretamente em Configurações > Integrações > Email");
        }
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
     * Testa a conexão SMTP sem enviar email
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'SMTP não configurado',
                'error' => 'Configurações incompletas'
            ];
        }
        
        $originalTimeLimit = ini_get('max_execution_time');
        
        try {
            set_time_limit(15); // 15 segundos para teste
            
            $mail = new PHPMailer(true);
            
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            
            // Ajusta encryption baseado na porta (465 = SSL, 587 = TLS)
            $port = (int)($this->config['port'] ?? 587);
            $encryption = $this->config['encryption'] ?? 'tls';
            
            // Se porta 465 e encryption não especificado ou TLS, força SSL
            if ($port === 465 && ($encryption === 'tls' || empty($encryption))) {
                $encryption = 'ssl';
            }
            
            $mail->SMTPSecure = $encryption;
            $mail->Port = $port;
            $mail->Timeout = 15; // 15 segundos para teste
            $mail->SMTPKeepAlive = false;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            // Tenta conectar sem enviar email
            $mail->smtpConnect();
            $mail->smtpClose();
            
            if ($originalTimeLimit !== false) {
                set_time_limit((int)$originalTimeLimit);
            }
            
            return [
                'success' => true,
                'message' => 'Conexão SMTP bem-sucedida'
            ];
        } catch (\Exception $e) {
            if (isset($originalTimeLimit) && $originalTimeLimit !== false) {
                set_time_limit((int)$originalTimeLimit);
            }
            
            $errorInfo = isset($mail) && !empty($mail->ErrorInfo) ? $mail->ErrorInfo : $e->getMessage();
            return [
                'success' => false,
                'message' => 'Erro ao conectar: ' . $errorInfo,
                'error' => $errorInfo
            ];
        }
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

        $originalTimeLimit = ini_get('max_execution_time');
        
        try {
            // Aumenta o tempo de execução apenas para este processo
            set_time_limit(60); // 60 segundos máximo para envio de email
            
            // Log detalhado das configurações que serão usadas (com senha completa para debug)
            error_log("SmtpService::sendEmail - Configurações SMTP sendo usadas:");
            error_log("  Host: " . ($this->config['host'] ?? 'NÃO DEFINIDO'));
            error_log("  Port: " . ($this->config['port'] ?? 587));
            error_log("  Encryption: " . ($this->config['encryption'] ?? 'tls'));
            error_log("  Username: " . ($this->config['username'] ?? 'NÃO DEFINIDO'));
            error_log("  Password: " . ($this->config['password'] ?? 'NÃO DEFINIDA'));
            error_log("  From Email: " . ($this->config['from_email'] ?? 'noreply@sistemabase.com'));
            error_log("  From Name: " . ($this->config['from_name'] ?? 'Sistema'));
            error_log("  Destinatário: " . $to);
            
            $mail = new PHPMailer(true);

            // Configurações do servidor
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            
            // Ajusta encryption baseado na porta (465 = SSL, 587 = TLS)
            $port = (int)($this->config['port'] ?? 587);
            $encryption = $this->config['encryption'] ?? 'tls';
            
            // Se porta 465 e encryption não especificado ou TLS, força SSL
            if ($port === 465 && ($encryption === 'tls' || empty($encryption))) {
                $encryption = 'ssl';
                error_log("SmtpService - Porta 465 detectada, alterando encryption para SSL");
            }
            
            $mail->SMTPSecure = $encryption;
            $mail->Port = $port;
            $mail->CharSet = 'UTF-8';
            
            error_log("SmtpService - Configuração final: Porta={$port}, Encryption={$encryption}");
            
            // Timeouts para evitar travamento - valores razoáveis
            $mail->Timeout = 30; // 30 segundos de timeout para conexão
            $mail->SMTPKeepAlive = false;
            
            // Configurações SSL/TLS mais permissivas para evitar problemas de certificado
            $cryptoMethod = ($encryption === 'ssl') ? STREAM_CRYPTO_METHOD_SSLv23_CLIENT : STREAM_CRYPTO_METHOD_TLS_CLIENT;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                    'crypto_method' => $cryptoMethod
                ]
            ];
            
            // Debug (habilitado temporariamente para diagnóstico)
            $mail->SMTPDebug = 2; // 2 = verbose (pode ser reduzido para 0 em produção)
            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer Debug: $str");
            };
            
            // Tenta conectar explicitamente antes de enviar (para detectar problemas mais cedo)
            error_log("Tentando conectar ao servidor SMTP...");
            $connectStart = time();
            try {
                $mail->smtpConnect();
                $connectTime = time() - $connectStart;
                error_log("Conexão SMTP estabelecida com sucesso em {$connectTime} segundos");
            } catch (\Exception $connectEx) {
                error_log("ERRO ao conectar ao SMTP: " . $connectEx->getMessage());
                throw $connectEx;
            }

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

            // Envia email
            error_log("Tentando enviar email para: {$to} via {$this->config['host']}:{$mail->Port}");
            $startTime = time();
            $mail->send();
            $elapsedTime = time() - $startTime;
            
            // Fecha conexão
            try {
                $mail->smtpClose();
            } catch (\Exception $e) {
                // Ignora erro ao fechar
            }
            
            // Restaura o tempo de execução original
            if ($originalTimeLimit !== false) {
                set_time_limit((int)$originalTimeLimit);
            }
            
            error_log("Email enviado com sucesso em {$elapsedTime} segundos para {$to}");

            return [
                'success' => true,
                'message' => 'Email enviado com sucesso',
                'error' => null
            ];
        } catch (PHPMailerException $e) {
            // Fecha conexão se estiver aberta
            if (isset($mail)) {
                try {
                    $mail->smtpClose();
                } catch (\Exception $closeEx) {
                    // Ignora erro ao fechar
                }
            }
            
            // Restaura o tempo de execução original
            if (isset($originalTimeLimit) && $originalTimeLimit !== false) {
                set_time_limit((int)$originalTimeLimit);
            }
            
            $errorInfo = isset($mail) && !empty($mail->ErrorInfo) ? $mail->ErrorInfo : $e->getMessage();
            error_log("SmtpService Error: " . $errorInfo);
            error_log("SmtpService Exception: " . $e->getTraceAsString());
            
            // Mensagem mais amigável para o usuário
            $userMessage = 'Erro ao enviar email. Verifique as configurações SMTP.';
            $errorLower = strtolower($errorInfo);
            if (strpos($errorLower, 'timeout') !== false || strpos($errorLower, 'connection timed out') !== false || strpos($errorLower, 'maximum execution time') !== false) {
                $userMessage = 'Timeout ao conectar ao servidor SMTP (' . $this->config['host'] . ':' . ($this->config['port'] ?? 587) . '). Verifique se o servidor está acessível e se as configurações estão corretas.';
            } elseif (strpos($errorLower, 'authentication failed') !== false || strpos($errorLower, 'invalid login') !== false) {
                $userMessage = 'Erro de autenticação SMTP. Verifique usuário e senha.';
            } elseif (strpos($errorLower, 'could not connect') !== false || strpos($errorLower, 'connection refused') !== false) {
                $userMessage = 'Não foi possível conectar ao servidor SMTP (' . $this->config['host'] . ':' . ($this->config['port'] ?? 587) . '). Verifique o host e porta.';
            }
            
            return [
                'success' => false,
                'message' => $userMessage,
                'error' => $errorInfo
            ];
        } catch (\Exception $e) {
            // Fecha conexão se estiver aberta
            if (isset($mail)) {
                try {
                    $mail->smtpClose();
                } catch (\Exception $closeEx) {
                    // Ignora erro ao fechar
                }
            }
            
            // Restaura o tempo de execução original
            if (isset($originalTimeLimit) && $originalTimeLimit !== false) {
                set_time_limit((int)$originalTimeLimit);
            }
            
            $errorInfo = $e->getMessage();
            error_log("SmtpService Generic Error: " . $errorInfo);
            error_log("SmtpService Exception: " . $e->getTraceAsString());
            
            return [
                'success' => false,
                'message' => 'Erro ao enviar email: ' . $errorInfo,
                'error' => $errorInfo
            ];
        }
    }
}
