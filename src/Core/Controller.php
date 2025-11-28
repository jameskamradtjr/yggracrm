<?php

declare(strict_types=1);

namespace Core;

/**
 * Classe Controller Base
 * 
 * Todos os controllers da aplicação devem estender esta classe
 * Fornece métodos auxiliares para views, validação, etc
 */
abstract class Controller
{
    protected Request $request;
    protected Response $response;

    public function __construct()
    {
        $this->request = new Request();
        $this->response = new Response();
    }

    /**
     * Renderiza uma view
     */
    protected function view(string $view, array $data = []): string
    {
        return view($view, $data);
    }

    /**
     * Retorna resposta JSON
     */
    protected function json(array $data, int $status = 200): void
    {
        $this->response->json($data, $status);
    }

    /**
     * Redireciona para uma URL
     */
    protected function redirect(string $url, int $status = 302): void
    {
        $this->response->redirect($url, $status);
    }

    /**
     * Retorna para a página anterior
     */
    protected function back(): void
    {
        $this->response->back();
    }

    /**
     * Valida dados da requisição
     */
    protected function validate(array $rules): array
    {
        $validator = new Validator($this->request->all(), $rules);
        
        if (!$validator->passes()) {
            session()->flash('errors', $validator->errors());
            session()->flash('old', $this->request->all());
            $this->back();
            exit;
        }

        return $validator->validated();
    }

    /**
     * Verifica se usuário está autenticado
     */
    protected function authenticated(): bool
    {
        return auth()->check();
    }

    /**
     * Obtém usuário autenticado
     */
    protected function user(): ?object
    {
        return auth()->user();
    }

    /**
     * Verifica se usuário tem permissão
     */
    protected function authorize(string $permission): bool
    {
        if (!$this->authenticated()) {
            return false;
        }

        return auth()->user()->hasPermission($permission);
    }

    /**
     * Lança erro 403 se não autorizado
     */
    protected function authorizeOrFail(string $permission): void
    {
        if (!$this->authorize($permission)) {
            http_response_code(403);
            die(view('errors/403'));
        }
    }

    /**
     * Verifica permissão granular (módulo/recurso/ação)
     */
    protected function authorizeGranular(string $module, string $resource, string $action): bool
    {
        if (!$this->authenticated()) {
            return false;
        }

        return auth()->user()->canAccess($module, $resource, $action);
    }

    /**
     * Lança erro 403 se não tiver permissão granular
     */
    protected function authorizeGranularOrFail(string $module, string $resource, string $action): void
    {
        if (!$this->authorizeGranular($module, $resource, $action)) {
            http_response_code(403);
            die(view('errors/403'));
        }
    }
}

