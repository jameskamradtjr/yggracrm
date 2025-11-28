<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;
use Core\Database;

/**
 * Model SistemaLog
 * 
 * Sistema de logs completo do sistema
 */
class SistemaLog extends Model
{
    protected string $table = 'sistema_logs';
    
    protected array $fillable = [
        'tabela',
        'registro_id',
        'acao',
        'descricao',
        'dados_anteriores',
        'dados_novos',
        'usuario_id',
        'ip_address',
        'user_agent'
    ];

    protected bool $timestamps = false;
    protected bool $multiTenant = false;

    /**
     * Registra um log
     */
    public static function registrar(
        string $tabela,
        string $acao,
        ?int $registro_id = null,
        ?string $descricao = null,
        ?array $dados_anteriores = null,
        ?array $dados_novos = null
    ): bool {
        try {
            $db = Database::getInstance();
            
            $usuario_id = auth()->check() ? auth()->id() : null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $dados_anteriores_json = $dados_anteriores ? json_encode($dados_anteriores) : null;
            $dados_novos_json = $dados_novos ? json_encode($dados_novos) : null;
            
            $db->execute(
                "INSERT INTO sistema_logs (tabela, registro_id, acao, descricao, dados_anteriores, dados_novos, usuario_id, ip_address, user_agent, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $tabela,
                    $registro_id,
                    $acao,
                    $descricao,
                    $dados_anteriores_json,
                    $dados_novos_json,
                    $usuario_id,
                    $ip,
                    $user_agent
                ]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao registrar log: " . $e->getMessage());
            return false;
        }
    }
}


