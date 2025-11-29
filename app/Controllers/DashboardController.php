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
        $userId = auth()->getDataUserId();

        // Busca estatísticas de leads
        $leadStats = $this->getLeadStats($userId);

        // Estatísticas básicas
        $data = [
            'user' => $user,
            'total_users' => $this->getTotalUsers(),
            'user_roles' => $user->roles(),
            'recent_activity' => $this->getRecentActivity(),
            'lead_stats' => $leadStats
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
        if (!$userId) {
            return [
                'total_leads' => 0,
                'total_oportunidades' => 0,
                'valor_total' => 0.0,
                'valor_medio' => 0.0,
                'por_etapa' => [
                    'interessados' => ['total' => 0, 'valor' => 0],
                    'negociacao_proposta' => ['total' => 0, 'valor' => 0],
                    'fechamento' => ['total' => 0, 'valor' => 0],
                    'perdidos' => ['total' => 0, 'valor' => 0]
                ]
            ];
        }

        $db = \Core\Database::getInstance();
        
        try {
            // Total de leads
            $totalLeadsResult = $db->query(
                "SELECT COUNT(*) as total FROM leads WHERE user_id = ?",
                [$userId]
            );
            $totalLeads = !empty($totalLeadsResult) && isset($totalLeadsResult[0]['total']) 
                ? (int)$totalLeadsResult[0]['total'] 
                : 0;
            
            // Total de oportunidades (leads com valor)
            $totalOportunidadesResult = $db->query(
                "SELECT COUNT(*) as total FROM leads WHERE user_id = ? AND valor_oportunidade IS NOT NULL AND valor_oportunidade > 0",
                [$userId]
            );
            $totalOportunidades = !empty($totalOportunidadesResult) && isset($totalOportunidadesResult[0]['total'])
                ? (int)$totalOportunidadesResult[0]['total'] 
                : 0;
            
            // Valor total das oportunidades
            $valorTotalResult = $db->query(
                "SELECT COALESCE(SUM(valor_oportunidade), 0) as total FROM leads WHERE user_id = ? AND valor_oportunidade IS NOT NULL AND valor_oportunidade > 0",
                [$userId]
            );
            $valorTotal = !empty($valorTotalResult) && isset($valorTotalResult[0]['total'])
                ? (float)$valorTotalResult[0]['total'] 
                : 0.0;
            
            // Valor médio das oportunidades
            $valorMedio = $totalOportunidades > 0 ? ($valorTotal / $totalOportunidades) : 0.0;
            
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
            
            if (!empty($leadsPorEtapa)) {
                foreach ($leadsPorEtapa as $etapa) {
                    $etapaNome = $etapa['etapa_funil'] ?? 'interessados';
                    if (isset($etapas[$etapaNome])) {
                        $etapas[$etapaNome]['total'] = (int)($etapa['total'] ?? 0);
                        $etapas[$etapaNome]['valor'] = (float)($etapa['valor_total'] ?? 0);
                    }
                }
            }
            
            return [
                'total_leads' => $totalLeads,
                'total_oportunidades' => $totalOportunidades,
                'valor_total' => $valorTotal,
                'valor_medio' => $valorMedio,
                'por_etapa' => $etapas
            ];
        } catch (\Exception $e) {
            // Em caso de erro, retorna valores zerados
            error_log("Erro ao buscar estatísticas de leads: " . $e->getMessage());
            return [
                'total_leads' => 0,
                'total_oportunidades' => 0,
                'valor_total' => 0.0,
                'valor_medio' => 0.0,
                'por_etapa' => [
                    'interessados' => ['total' => 0, 'valor' => 0],
                    'negociacao_proposta' => ['total' => 0, 'valor' => 0],
                    'fechamento' => ['total' => 0, 'valor' => 0],
                    'perdidos' => ['total' => 0, 'valor' => 0]
                ]
            ];
        }
    }
}

