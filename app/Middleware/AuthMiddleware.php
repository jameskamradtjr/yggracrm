<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * Middleware de Autenticação
 * 
 * Verifica se o usuário está autenticado
 */
class AuthMiddleware
{
    /**
     * Processa a requisição
     */
    public function handle(): bool
    {
        if (!auth()->check()) {
            // Verifica se é uma requisição AJAX/JSON
            $acceptsJson = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            $isJsonRequest = $acceptsJson || $isAjax;
            
            if ($isJsonRequest) {
                // Limpa qualquer output anterior
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Você precisa estar logado para acessar esta página.'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            // Caso contrário, redireciona normalmente
            session()->flash('error', 'Você precisa estar logado para acessar esta página.');
            redirect('/login');
            exit;
        }

        return true;
    }
}

