<?php

declare(strict_types=1);

use Core\Application;
use Core\Auth;
use Core\Session;
use Core\View;

if (!function_exists('app')) {
    /**
     * Obtém a instância da aplicação
     */
    function app(): Application
    {
        return Application::getInstance();
    }
}

if (!function_exists('base_path')) {
    /**
     * Obtém o caminho base da aplicação
     */
    function base_path(string $path = ''): string
    {
        return app()->basePath($path);
    }
}

if (!function_exists('config')) {
    /**
     * Obtém um valor de configuração
     */
    function config(string $key, mixed $default = null): mixed
    {
        static $config = [];
        static $basePath = null;

        // Define basePath na primeira chamada
        if ($basePath === null) {
            $basePath = dirname(__DIR__, 2);
        }

        $keys = explode('.', $key);
        $file = array_shift($keys);

        if (!isset($config[$file])) {
            $configFile = $basePath . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "{$file}.php";
            if (file_exists($configFile)) {
                $config[$file] = require $configFile;
            } else {
                return $default;
            }
        }

        $value = $config[$file];

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }
}

if (!function_exists('env')) {
    /**
     * Obtém uma variável de ambiente
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        // Converte strings especiais
        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $value,
        };
    }
}

if (!function_exists('view')) {
    /**
     * Renderiza uma view
     */
    function view(string $view, array $data = []): string
    {
        $viewInstance = new View();
        return $viewInstance->render($view, $data);
    }
}

if (!function_exists('redirect')) {
    /**
     * Redireciona para uma URL
     */
    function redirect(string $url): void
    {
        // Se não é uma URL completa, usa o helper url() para gerar corretamente
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = url($url);
        }
        
        header("Location: {$url}");
        exit;
    }
}

if (!function_exists('url')) {
    /**
     * Gera uma URL completa para rotas da aplicação
     */
    function url(string $path = ''): string
    {
        // Detecta o base path do projeto (sem /public)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        
        // Se SCRIPT_NAME é /sistemabase26/public/index.php
        // Remove /index.php E /public para gerar URLs limpas
        $basePath = str_replace('/index.php', '', $scriptName);
        $basePath = str_replace('/public', '', $basePath);
        
        // Remove barra dupla se houver
        $path = '/' . ltrim($path, '/');
        
        return $basePath . $path;
    }
}

if (!function_exists('asset')) {
    /**
     * Gera URL para assets (fora de public)
     */
    function asset(string $path): string
    {
        // Detecta o base path do projeto (sem /public)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        
        // Se SCRIPT_NAME é /sistemabase26/public/index.php
        // Remove /public/index.php para obter /sistemabase26
        $basePath = preg_replace('#/public/index\.php$#', '', $scriptName);
        
        // Assets (tema) estão na raiz do projeto
        $path = '/' . ltrim($path, '/');
        
        return $basePath . $path;
    }
}

if (!function_exists('session')) {
    /**
     * Obtém a instância da sessão
     */
    function session(): Session
    {
        return Session::getInstance();
    }
}

if (!function_exists('auth')) {
    /**
     * Obtém a instância do Auth
     */
    function auth(): Auth
    {
        return Auth::getInstance();
    }
}

if (!function_exists('old')) {
    /**
     * Obtém valor antigo de input
     */
    function old(string $key, mixed $default = ''): mixed
    {
        $old = session()->get('old', []);
        return $old[$key] ?? $default;
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Gera um token CSRF
     */
    function csrf_token(): string
    {
        if (!session()->has('_csrf_token')) {
            session()->set('_csrf_token', bin2hex(random_bytes(32)));
        }

        return session()->get('_csrf_token');
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Gera campo hidden com token CSRF
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('verify_csrf')) {
    /**
     * Verifica token CSRF
     */
    function verify_csrf(string $token): bool
    {
        return hash_equals(session()->get('_csrf_token', ''), $token);
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and die
     */
    function dd(mixed ...$vars): void
    {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
        die();
    }
}

if (!function_exists('dump')) {
    /**
     * Dump sem parar execução
     */
    function dump(mixed ...$vars): void
    {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
    }
}

if (!function_exists('e')) {
    /**
     * Escapa HTML
     */
    function e(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('now')) {
    /**
     * Retorna a data/hora atual
     */
    function now(string $format = 'Y-m-d H:i:s'): string
    {
        return date($format);
    }
}

if (!function_exists('bcrypt')) {
    /**
     * Hash de senha com bcrypt
     */
    function bcrypt(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, [
            'cost' => config('app.bcrypt_rounds', 12)
        ]);
    }
}

if (!function_exists('str_slug')) {
    /**
     * Converte string em slug
     */
    function str_slug(string $string): string
    {
        $string = preg_replace('/[^\p{L}\p{N}\s-]/u', '', mb_strtolower($string));
        $string = preg_replace('/[\s-]+/', '-', $string);
        return trim($string, '-');
    }
}

if (!function_exists('abort')) {
    /**
     * Aborta com código HTTP
     */
    function abort(int $code = 404, string $message = ''): void
    {
        http_response_code($code);
        
        if (file_exists(base_path("views/errors/{$code}.php"))) {
            echo view("errors/{$code}", ['message' => $message]);
        } else {
            echo $message ?: "Erro {$code}";
        }
        
        exit;
    }
}

if (!function_exists('logger')) {
    /**
     * Log de mensagens
     */
    function logger(string $message, string $level = 'info'): void
    {
        $logFile = base_path('storage/logs/' . date('Y-m-d') . '.log');
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

if (!function_exists('json_response')) {
    /**
     * Retorna resposta JSON
     */
    function json_response(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

// ============================================================================
// AWS S3 Helpers
// ============================================================================

if (!function_exists('s3_public')) {
    /**
     * Obtém uma instância do serviço S3 público
     */
    function s3_public(): \App\Services\AWS\S3PublicService
    {
        return new \App\Services\AWS\S3PublicService();
    }
}

if (!function_exists('s3_private')) {
    /**
     * Obtém uma instância do serviço S3 privado
     */
    function s3_private(): \App\Services\AWS\S3PrivateService
    {
        return new \App\Services\AWS\S3PrivateService();
    }
}

if (!function_exists('s3_upload_public')) {
    /**
     * Faz upload de arquivo público e retorna a URL
     * 
     * @param string $localFilePath Caminho local do arquivo
     * @param int $userId ID do usuário
     * @param string $subfolder Subpasta opcional (ex: 'avatars', 'logos')
     * @param array $allowedExtensions Extensões permitidas
     * @return string|false URL pública ou false em caso de erro
     */
    function s3_upload_public(
        string $localFilePath,
        int $userId,
        string $subfolder = '',
        array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf']
    ): string|false {
        $s3 = s3_public();
        
        if (!$s3->validateFile($localFilePath, $allowedExtensions)) {
            error_log('S3 Public Upload Validation Error: ' . $s3->getLastError());
            return false;
        }
        
        $originalFileName = basename($localFilePath);
        $s3Key = $s3->generateUniqueKey($userId, $originalFileName, $subfolder);
        
        return $s3->uploadAndGetUrl($localFilePath, $s3Key);
    }
}

if (!function_exists('s3_upload_private')) {
    /**
     * Faz upload de arquivo privado e retorna o caminho S3
     * 
     * @param string $localFilePath Caminho local do arquivo
     * @param int $userId ID do usuário
     * @param string $subfolder Subpasta opcional (ex: 'documents', 'contracts')
     * @param int $maxSizeMB Tamanho máximo em MB
     * @return string|false Caminho S3 ou false em caso de erro
     */
    function s3_upload_private(
        string $localFilePath,
        int $userId,
        string $subfolder = '',
        int $maxSizeMB = 50
    ): string|false {
        $s3 = s3_private();
        
        if (!$s3->validateFile($localFilePath, $maxSizeMB)) {
            error_log('S3 Private Upload Validation Error: ' . $s3->getLastError());
            return false;
        }
        
        $originalFileName = basename($localFilePath);
        $s3Key = $s3->generateUniqueKey($userId, $originalFileName, $subfolder);
        
        if ($s3->upload($localFilePath, $s3Key)) {
            return $s3Key;
        }
        
        error_log('S3 Private Upload Error: ' . $s3->getLastError());
        return false;
    }
}

if (!function_exists('s3_get_signed_url')) {
    /**
     * Gera URL assinada para download de arquivo privado
     * 
     * @param string $s3Key Caminho do arquivo no S3
     * @param int $expirationMinutes Tempo de validade em minutos
     * @return string|false URL assinada ou false em caso de erro
     */
    function s3_get_signed_url(string $s3Key, int $expirationMinutes = 15): string|false
    {
        return s3_private()->getSignedDownloadUrl($s3Key, $expirationMinutes);
    }
}

if (!function_exists('s3_delete_public')) {
    /**
     * Deleta arquivo público do S3
     * 
     * @param string $s3Key Caminho do arquivo no S3
     * @return bool
     */
    function s3_delete_public(string $s3Key): bool
    {
        return s3_public()->delete($s3Key);
    }
}

if (!function_exists('s3_delete_private')) {
    /**
     * Deleta arquivo privado do S3
     * 
     * @param string $s3Key Caminho do arquivo no S3
     * @return bool
     */
    function s3_delete_private(string $s3Key): bool
    {
        return s3_private()->delete($s3Key);
    }
}

if (!function_exists('s3_public_url')) {
    /**
     * Retorna a URL pública de um arquivo no bucket público
     * 
     * @param string $s3Key Caminho do arquivo no S3
     * @return string URL pública
     */
    function s3_public_url(string $s3Key): string
    {
        return s3_public()->getPublicUrl($s3Key);
    }
}

