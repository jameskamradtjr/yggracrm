<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\User;

/**
 * Controller do Dashboard
 */
class DashboardController extends Controller
{
    /**
     * Página principal do dashboard
     */
    public function index(): string
    {
        if (!auth()->check()) {
            $this->redirect('/login');
        }

        $user = auth()->user();

        // Estatísticas básicas
        $data = [
            'user' => $user,
            'total_users' => $this->getTotalUsers(),
            'user_roles' => $user->roles(),
            'recent_activity' => $this->getRecentActivity()
        ];

        return $this->view('dashboard/index', $data);
    }

    /**
     * Retorna total de usuários do sistema
     */
    private function getTotalUsers(): int
    {
        // Se for multi-tenant, conta apenas usuários do mesmo user_id
        if (config('app.multi_tenant_enabled')) {
            $users = User::where('id', auth()->id())->get();
            return count($users);
        }

        return count(User::all());
    }

    /**
     * Retorna atividades recentes
     */
    private function getRecentActivity(): array
    {
        // TODO: Implementar sistema de logs/atividades
        return [];
    }
}

