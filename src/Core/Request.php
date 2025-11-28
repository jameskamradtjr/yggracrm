<?php

declare(strict_types=1);

namespace Core;

/**
 * Classe Request - Gerenciamento de requisições HTTP
 * 
 * Encapsula dados da requisição ($_GET, $_POST, $_SERVER, etc)
 * Fornece métodos para validação e sanitização
 */
class Request
{
    private array $query;
    private array $request;
    private array $server;
    private array $files;
    private array $cookies;
    private array $headers;

    public function __construct()
    {
        $this->query = $_GET;
        $this->request = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
        $this->headers = $this->getHeaders();

        // Parse JSON body se necessário
        if ($this->isJson()) {
            $json = json_decode(file_get_contents('php://input'), true);
            if (is_array($json)) {
                $this->request = array_merge($this->request, $json);
            }
        }
    }

    /**
     * Obtém todos os headers
     */
    private function getHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$header] = $value;
            }
        }

        return $headers;
    }

    /**
     * Obtém o método HTTP
     */
    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Obtém a URI
     */
    public function uri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    /**
     * Verifica se é requisição AJAX
     */
    public function isAjax(): bool
    {
        return isset($this->server['HTTP_X_REQUESTED_WITH']) 
            && strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Verifica se é requisição JSON
     */
    public function isJson(): bool
    {
        return isset($this->headers['Content-Type']) 
            && str_contains($this->headers['Content-Type'], 'application/json');
    }

    /**
     * Verifica se espera JSON como resposta
     */
    public function expectsJson(): bool
    {
        return $this->isJson() || $this->isAjax();
    }

    /**
     * Obtém um valor da query string
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Obtém um valor do POST
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->request[$key] ?? $default;
    }

    /**
     * Obtém um valor (POST ou GET)
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->input($key) ?? $this->query($key, $default);
    }

    /**
     * Obtém todos os dados (POST + GET)
     */
    public function all(): array
    {
        return array_merge($this->query, $this->request);
    }

    /**
     * Obtém apenas campos específicos
     */
    public function only(array $keys): array
    {
        $data = [];
        foreach ($keys as $key) {
            if ($this->has($key)) {
                $data[$key] = $this->get($key);
            }
        }
        
        return $data;
    }

    /**
     * Obtém todos exceto campos específicos
     */
    public function except(array $keys): array
    {
        $data = $this->all();
        
        foreach ($keys as $key) {
            unset($data[$key]);
        }
        
        return $data;
    }

    /**
     * Verifica se um campo existe
     */
    public function has(string $key): bool
    {
        return isset($this->request[$key]) || isset($this->query[$key]);
    }

    /**
     * Verifica se um campo existe e não está vazio
     */
    public function filled(string $key): bool
    {
        return $this->has($key) && !empty($this->get($key));
    }

    /**
     * Obtém um arquivo enviado
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Verifica se tem um arquivo
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Obtém um header
     */
    public function header(string $key, mixed $default = null): mixed
    {
        return $this->headers[$key] ?? $default;
    }

    /**
     * Obtém o IP do cliente
     */
    public function ip(): string
    {
        if (!empty($this->server['HTTP_CLIENT_IP'])) {
            return $this->server['HTTP_CLIENT_IP'];
        }
        
        if (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {
            return $this->server['HTTP_X_FORWARDED_FOR'];
        }
        
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Obtém o User Agent
     */
    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Obtém a URL anterior (referer)
     */
    public function referer(): ?string
    {
        return $this->server['HTTP_REFERER'] ?? null;
    }

    /**
     * Sanitiza uma string
     */
    public function sanitize(string $value): string
    {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }
}

