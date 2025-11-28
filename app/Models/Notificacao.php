<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Model Notificacao
 * 
 * Representa notificações do sistema
 */
class Notificacao extends Model
{
    protected string $table = 'notificacoes';
    
    protected array $fillable = [
        'usuario_id',
        'tipo',
        'titulo',
        'mensagem',
        'link',
        'lida'
    ];

    protected bool $timestamps = false;
    protected bool $multiTenant = false;
    
    /**
     * Cria uma notificação
     */
    public static function criar(int $usuarioId, string $tipo, string $titulo, string $mensagem, ?string $link = null): static
    {
        return static::create([
            'usuario_id' => $usuarioId,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'link' => $link,
            'lida' => false
        ]);
    }
    
    /**
     * Marca notificação como lida
     */
    public function marcarComoLida(): bool
    {
        return $this->update(['lida' => true]);
    }
    
    /**
     * Marca todas as notificações do usuário como lidas
     */
    public static function marcarTodasComoLidas(int $usuarioId): void
    {
        $db = \Core\Database::getInstance();
        $db->execute(
            "UPDATE notificacoes SET lida = 1 WHERE usuario_id = ?",
            [$usuarioId]
        );
    }
}


