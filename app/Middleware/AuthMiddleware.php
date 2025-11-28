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
            session()->flash('error', 'Você precisa estar logado para acessar esta página.');
            redirect('/login');
            exit;
        }

        return true;
    }
}

