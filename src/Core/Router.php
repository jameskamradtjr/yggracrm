<?php

declare(strict_types=1);

namespace Core;

/**
 * Classe Router - Gerenciamento de rotas da aplicação
 * 
 * Suporta todos os métodos HTTP (GET, POST, PUT, DELETE, PATCH)
 * Suporta parâmetros dinâmicos em rotas
 * Suporta middlewares por rota
 */
class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private array $groupMiddlewares = [];
    private string $groupPrefix = '';

    /**
     * Adiciona uma rota GET
     */
    public function get(string $path, array|callable $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * Adiciona uma rota POST
     */
    public function post(string $path, array|callable $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * Adiciona uma rota PUT
     */
    public function put(string $path, array|callable $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Adiciona uma rota DELETE
     */
    public function delete(string $path, array|callable $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Adiciona uma rota PATCH
     */
    public function patch(string $path, array|callable $handler): self
    {
        return $this->addRoute('PATCH', $path, $handler);
    }

    /**
     * Adiciona uma rota para qualquer método
     */
    public function any(string $path, array|callable $handler): self
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE', 'PATCH'] as $method) {
            $this->addRoute($method, $path, $handler);
        }
        
        return $this;
    }

    /**
     * Adiciona middleware a última rota adicionada
     */
    public function middleware(string|array $middleware): self
    {
        if (!empty($this->routes)) {
            $lastKey = array_key_last($this->routes);
            $middlewares = is_array($middleware) ? $middleware : [$middleware];
            $this->routes[$lastKey]['middlewares'] = array_merge(
                $this->routes[$lastKey]['middlewares'] ?? [],
                $middlewares
            );
        }
        
        return $this;
    }

    /**
     * Cria um grupo de rotas com prefixo e middlewares
     */
    public function group(array $attributes, callable $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddlewares = $this->groupMiddlewares;

        $this->groupPrefix = $previousPrefix . ($attributes['prefix'] ?? '');
        $this->groupMiddlewares = array_merge(
            $previousMiddlewares,
            $attributes['middleware'] ?? []
        );

        call_user_func($callback, $this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddlewares = $previousMiddlewares;
    }

    /**
     * Adiciona uma rota ao array de rotas
     */
    private function addRoute(string $method, string $path, array|callable $handler): self
    {
        $path = $this->groupPrefix . $path;
        $path = '/' . trim($path, '/');
        
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $this->groupMiddlewares,
            'regex' => $this->convertToRegex($path)
        ];

        return $this;
    }

    /**
     * Converte uma rota em regex para matching
     */
    private function convertToRegex(string $path): string
    {
        // Substitui {param} por regex capturando
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        // Escapa apenas a barra para regex
        $pattern = str_replace('/', '\/', $pattern);
        
        return '#^' . $pattern . '$#';
    }

    /**
     * Despacha a requisição para o handler apropriado
     */
    public function dispatch(string $method, string $uri): mixed
    {
        // Remove query string
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = '/' . trim($uri, '/');
        
        // Log de debug
        error_log("Router dispatch - Method: {$method}, URI: {$uri}");
        error_log("Total de rotas registradas: " . count($this->routes));

        // Separa rotas em específicas (sem parâmetros) e genéricas (com parâmetros)
        $specificRoutes = [];
        $genericRoutes = [];
        
        foreach ($this->routes as $index => $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            // Verifica se a rota tem parâmetros (contém {})
            if (strpos($route['path'], '{') !== false) {
                $genericRoutes[] = $route;
            } else {
                $specificRoutes[] = $route;
            }
        }
        
        // Verifica rotas específicas primeiro, depois genéricas
        $routesToCheck = array_merge($specificRoutes, $genericRoutes);
        
        foreach ($routesToCheck as $index => $route) {
            if (preg_match($route['regex'], $uri, $matches)) {
                error_log("Rota encontrada! Index: {$index}, Path: {$route['path']}, Regex: {$route['regex']}");
                // Extrai parâmetros da URL
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Executa middlewares
                foreach ($route['middlewares'] ?? [] as $middleware) {
                    $middlewareInstance = new $middleware();
                    $result = $middlewareInstance->handle();
                    
                    if ($result !== true) {
                        return $result;
                    }
                }

                // Executa o handler
                if (is_callable($route['handler'])) {
                    return call_user_func_array($route['handler'], [$params]);
                }

                if (is_array($route['handler'])) {
                    [$controller, $action] = $route['handler'];
                    
                    $controllerInstance = new $controller();
                    
                    if (!method_exists($controllerInstance, $action)) {
                        throw new \RuntimeException(
                            "Método {$action} não existe no controller " . get_class($controllerInstance)
                        );
                    }

                    return call_user_func_array(
                        [$controllerInstance, $action],
                        [$params]
                    );
                }
            }
        }

        // Rota não encontrada - Log para debug
        error_log("Rota não encontrada: {$method} {$uri}");
        error_log("Rotas registradas: " . count($this->routes));
        
        // Verifica se é uma requisição AJAX/JSON
        $acceptsJson = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $isJsonRequest = $acceptsJson || $isAjax;
        
        if ($isJsonRequest) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Rota não encontrada: ' . $uri
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        
        http_response_code(404);
        
        // Verifica se a view existe
        $errorView = base_path('views/errors/404.php');
        if (file_exists($errorView)) {
            return view('errors/404');
        }
        
        // Fallback simples
        return '<h1>404 - Página não encontrada</h1><p>URI: ' . htmlspecialchars($uri) . '</p>';
    }

    /**
     * Retorna todas as rotas registradas
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}

