<?php

declare(strict_types=1);

namespace Core;

use Dotenv\Dotenv;

/**
 * Classe Application - Core da aplicação
 * 
 * Gerencia o ciclo de vida da aplicação
 * Inicializa componentes e processa requisições
 */
class Application
{
    private static ?Application $instance = null;
    private Router $router;
    private Request $request;
    private string $basePath;

    private function __construct(string $basePath)
    {
        $this->basePath = $basePath;
        $this->bootstrap();
    }

    /**
     * Obtém instância da aplicação (Singleton)
     */
    public static function getInstance(?string $basePath = null): self
    {
        if (self::$instance === null) {
            if ($basePath === null) {
                // Tenta detectar o basePath automaticamente
                $basePath = dirname(__DIR__, 2);
            }
            self::$instance = new self($basePath);
        }

        return self::$instance;
    }

    /**
     * Inicializa a aplicação
     */
    private function bootstrap(): void
    {
        // Carrega variáveis de ambiente
        $this->loadEnvironment();

        // Configura timezone
        date_default_timezone_set(config('app.timezone', 'America/Sao_Paulo'));

        // Configura error reporting
        if (config('app.debug')) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }

        // Inicializa router e request
        $this->router = new Router();
        $this->request = new Request();

        // Apenas inicia sessão e carrega rotas se não for CLI
        if (php_sapi_name() !== 'cli') {
            // Inicia sessão
            Session::getInstance()->start();

            // Limpa flash data antiga
            Session::getInstance()->ageFlashData();

            // Carrega rotas
            $this->loadRoutes();
        }
    }

    /**
     * Carrega variáveis de ambiente
     */
    private function loadEnvironment(): void
    {
        if (class_exists(Dotenv::class)) {
            $dotenv = Dotenv::createImmutable($this->basePath);
            $dotenv->safeLoad();
        }
    }

    /**
     * Carrega arquivos de rotas
     */
    private function loadRoutes(): void
    {
        $router = $this->router;
        
        $webRoutes = $this->basePath . '/routes/web.php';
        if (file_exists($webRoutes)) {
            require $webRoutes;
        }

        $apiRoutes = $this->basePath . '/routes/api.php';
        if (file_exists($apiRoutes)) {
            require $apiRoutes;
        }
    }

    /**
     * Retorna o router
     */
    public function router(): Router
    {
        return $this->router;
    }

    /**
     * Retorna o request
     */
    public function request(): Request
    {
        return $this->request;
    }

    /**
     * Retorna o base path
     */
    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Executa a aplicação
     */
    public function run(): void
    {
        try {
            $method = $this->request->method();
            $uri = $this->request->uri();
            
            // Remove o base path da URI se existir
            $uri = $this->removeBasePath($uri);

            $response = $this->router->dispatch($method, $uri);

            if (is_string($response)) {
                echo $response;
            }

        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Remove o base path da URI
     */
    private function removeBasePath(string $uri): string
    {
        // Detecta o script name (ex: /sistemabase26/public/index.php)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        
        // Remove o index.php e /public do final para pegar o base path do projeto
        // Ex: /sistemabase26/public/index.php -> /sistemabase26
        $basePath = str_replace('/index.php', '', $scriptName);
        $basePath = str_replace('/public', '', $basePath);
        
        // Se a URI começa com o base path, remove
        if ($basePath && $basePath !== '/' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }
        
        // Garante que começa com /
        if (empty($uri) || $uri[0] !== '/') {
            $uri = '/' . $uri;
        }
        
        return $uri;
    }

    /**
     * Trata exceções
     */
    private function handleException(\Throwable $e): void
    {
        error_log($e->getMessage() . "\n" . $e->getTraceAsString());

        // Verifica se é uma requisição que espera JSON
        $acceptsJson = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isJsonRequest = strpos($contentType, 'application/json') !== false || $acceptsJson || $isAjax;

        // Se for uma requisição JSON/AJAX, retorna JSON
        if ($isJsonRequest) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Erro na aplicação: ' . $e->getMessage(),
                'file' => config('app.debug', false) ? $e->getFile() : null,
                'line' => config('app.debug', false) ? $e->getLine() : null,
                'trace' => config('app.debug', false) ? $e->getTraceAsString() : null
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        // Caso contrário, renderiza HTML
        if (config('app.debug')) {
            echo "<h1>Erro na aplicação</h1>";
            echo "<p><strong>Mensagem:</strong> {$e->getMessage()}</p>";
            echo "<p><strong>Arquivo:</strong> {$e->getFile()}:{$e->getLine()}</p>";
            echo "<pre>{$e->getTraceAsString()}</pre>";
        } else {
            http_response_code(500);
            if (file_exists($this->basePath('views/errors/500.php'))) {
                include $this->basePath('views/errors/500.php');
            } else {
                echo "Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.";
            }
        }
    }

    /**
     * Previne clonagem
     */
    private function __clone() {}
}

