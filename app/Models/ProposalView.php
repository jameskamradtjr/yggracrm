<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class ProposalView extends Model
{
    protected string $table = 'proposal_views';
    protected bool $multiTenant = false; // Não precisa de multi-tenancy (é log público)
    
    protected array $fillable = [
        'proposal_id',
        'ip_address',
        'user_agent',
        'referer',
        'country',
        'city',
        'viewed_at'
    ];
    
    /**
     * Registra uma visualização da proposta
     */
    public static function registrarVisualizacao(int $proposalId): void
    {
        $ipAddress = self::getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        
        // Cria registro de visualização
        self::create([
            'proposal_id' => $proposalId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'referer' => $referer,
            'viewed_at' => date('Y-m-d H:i:s')
        ]);
        
        // Incrementa contador na proposta
        $proposal = Proposal::find($proposalId);
        if ($proposal) {
            $visualizacoes = ($proposal->visualizacoes ?? 0) + 1;
            $proposal->update(['visualizacoes' => $visualizacoes]);
        }
    }
    
    /**
     * Obtém o IP real do cliente (considerando proxies)
     */
    private static function getClientIp(): ?string
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }
    
    /**
     * Relacionamento com Proposal
     */
    public function proposal()
    {
        return Proposal::find($this->proposal_id);
    }
    
    /**
     * Obtém todas as visualizações de uma proposta
     */
    public static function getByProposal(int $proposalId): array
    {
        return self::where('proposal_id', $proposalId)
            ->orderBy('viewed_at', 'DESC')
            ->get();
    }
    
    /**
     * Obtém estatísticas de visualizações
     */
    public static function getStats(int $proposalId): array
    {
        $db = \Core\Database::getInstance();
        
        // Total de visualizações
        $total = $db->queryOne(
            "SELECT COUNT(*) as total FROM proposal_views WHERE proposal_id = ?",
            [$proposalId]
        );
        
        // Visualizações únicas (IPs únicos)
        $unique = $db->queryOne(
            "SELECT COUNT(DISTINCT ip_address) as total FROM proposal_views WHERE proposal_id = ?",
            [$proposalId]
        );
        
        // Visualizações hoje
        $today = $db->queryOne(
            "SELECT COUNT(*) as total FROM proposal_views 
             WHERE proposal_id = ? AND DATE(viewed_at) = CURDATE()",
            [$proposalId]
        );
        
        // Última visualização
        $last = $db->queryOne(
            "SELECT viewed_at FROM proposal_views 
             WHERE proposal_id = ? 
             ORDER BY viewed_at DESC LIMIT 1",
            [$proposalId]
        );
        
        return [
            'total' => $total['total'] ?? 0,
            'unique' => $unique['total'] ?? 0,
            'today' => $today['total'] ?? 0,
            'last_view' => $last['viewed_at'] ?? null
        ];
    }
}

