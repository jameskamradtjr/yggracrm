<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Model WhatsAppTemplate
 * 
 * Representa um template de mensagem WhatsApp
 */
class WhatsAppTemplate extends Model
{
    protected string $table = 'whatsapp_templates';
    
    protected array $fillable = [
        'name',
        'slug',
        'message',
        'variables',
        'is_active'
    ];

    // Templates são globais (não multi-tenant)
    protected bool $multiTenant = false;

    /**
     * Obtém um template por slug
     */
    public static function getBySlug(string $slug): ?self
    {
        return self::where('slug', $slug)->where('is_active', 1)->first();
    }

    /**
     * Processa o template substituindo variáveis
     */
    public function process(array $variables = []): string
    {
        $message = $this->message;

        foreach ($variables as $key => $value) {
            $message = str_replace('{{' . $key . '}}', $value, $message);
        }

        return $message;
    }

    /**
     * Obtém as variáveis disponíveis
     */
    public function getVariables(): array
    {
        if (empty($this->variables)) {
            return [];
        }

        return json_decode($this->variables, true) ?? [];
    }
}

