<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Database;
use App\Models\Notificacao;

/**
 * Controller de Notificações
 */
class NotificacaoController extends Controller
{
    /**
     * API para buscar notificações
     */
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $db = Database::getInstance();
            $userId = auth()->id();
            
            if (!$userId) {
                echo json_encode([
                    'success' => false,
                    'notificacoes' => [],
                    'total_nao_lidas' => 0
                ]);
                exit;
            }
            
            // Busca notificações não lidas
            $notificacoes = $db->query(
                "SELECT * FROM notificacoes 
                 WHERE usuario_id = ? AND lida = 0
                 ORDER BY created_at DESC
                 LIMIT 10",
                [$userId]
            );
            
            // Formata notificações
            $notificacoesFormatadas = [];
            foreach ($notificacoes as $notif) {
                $notificacoesFormatadas[] = [
                    'id' => $notif['id'],
                    'tipo' => $notif['tipo'],
                    'titulo' => $notif['titulo'],
                    'mensagem' => $notif['mensagem'],
                    'url' => $notif['link'] ?? '#',
                    'data' => $notif['created_at'],
                    'icon' => match($notif['tipo']) {
                        'info' => 'ti-info-circle',
                        'success' => 'ti-check-circle',
                        'warning' => 'ti-alert-triangle',
                        'error' => 'ti-x-circle',
                        default => 'ti-bell'
                    },
                    'color' => match($notif['tipo']) {
                        'info' => 'primary',
                        'success' => 'success',
                        'warning' => 'warning',
                        'error' => 'danger',
                        default => 'secondary'
                    }
                ];
            }
            
            // Conta total de notificações não lidas
            $totalNaoLidas = $db->queryOne(
                "SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = ? AND lida = 0",
                [$userId]
            )['total'] ?? 0;
            
            echo json_encode([
                'success' => true,
                'notificacoes' => $notificacoesFormatadas,
                'total_nao_lidas' => (int)$totalNaoLidas
            ]);
            exit;
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'notificacoes' => [],
                'total_nao_lidas' => 0,
                'error' => 'Erro ao buscar notificações: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    /**
     * Marca notificação como lida
     */
    public function marcarLida(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $notificacao = Notificacao::find($params['id']);
            
            if (!$notificacao || $notificacao->usuario_id != auth()->id()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Notificação não encontrada']);
                exit;
            }
            
            $notificacao->marcarComoLida();
            
            echo json_encode(['success' => true]);
            exit;
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    /**
     * Marca todas as notificações como lidas
     */
    public function marcarTodasLidas(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            Notificacao::marcarTodasComoLidas(auth()->id());
            
            echo json_encode(['success' => true]);
            exit;
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
}


