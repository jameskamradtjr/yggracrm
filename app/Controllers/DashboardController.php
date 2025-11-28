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

    /**
     * Retorna estatísticas de leads e oportunidades
     */
    private function getLeadStats(int $userId): array
    {
        $db = \Core\Database::getInstance();
        
        // Total de leads
        $totalLeads = $db->query(
            "SELECT COUNT(*) as total FROM leads WHERE user_id = ?",
            [$userId]
        )[0]['total'] ?? 0;
        
        // Total de oportunidades (leads com valor)
        $totalOportunidades = $db->query(
            "SELECT COUNT(*) as total FROM leads WHERE user_id = ? AND valor_oportunidade IS NOT NULL AND valor_oportunidade > 0",
            [$userId]
        )[0]['total'] ?? 0;
        
        // Valor total das oportunidades
        $valorTotal = $db->query(
            "SELECT COALESCE(SUM(valor_oportunidade), 0) as total FROM leads WHERE user_id = ? AND valor_oportunidade IS NOT NULL AND valor_oportunidade > 0",
            [$userId]
        )[0]['total'] ?? 0;
        
        // Valor médio das oportunidades
        $valorMedio = $totalOportunidades > 0 ? ($valorTotal / $totalOportunidades) : 0;
        
        // Leads por etapa do funil
        $leadsPorEtapa = $db->query(
            "SELECT etapa_funil, COUNT(*) as total, COALESCE(SUM(valor_oportunidade), 0) as valor_total 
             FROM leads 
             WHERE user_id = ? 
             GROUP BY etapa_funil",
            [$userId]
        );
        
        $etapas = [
            'interessados' => ['total' => 0, 'valor' => 0],
            'negociacao_proposta' => ['total' => 0, 'valor' => 0],
            'fechamento' => ['total' => 0, 'valor' => 0],
            'perdidos' => ['total' => 0, 'valor' => 0]
        ];
        
        foreach ($leadsPorEtapa as $etapa) {
            $etapaNome = $etapa['etapa_funil'] ?? 'interessados';
            if (isset($etapas[$etapaNome])) {
                $etapas[$etapaNome]['total'] = (int)$etapa['total'];
                $etapas[$etapaNome]['valor'] = (float)$etapa['valor_total'];
            }
        }
        
        return [
            'total_leads' => (int)$totalLeads,
            'total_oportunidades' => (int)$totalOportunidades,
            'valor_total' => (float)$valorTotal,
            'valor_medio' => (float)$valorMedio,
            'por_etapa' => $etapas
        ];
    }
}

