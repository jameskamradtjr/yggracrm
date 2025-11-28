<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Model EmailTemplate
 * 
 * Representa um template de email
 */
class EmailTemplate extends Model
{
    protected string $table = 'email_templates';
    
    protected array $fillable = [
        'name',
        'slug',
        'subject',
        'body',
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
    public function process(array $variables = []): array
    {
        $subject = $this->subject;
        $body = $this->body;

        foreach ($variables as $key => $value) {
            $subject = str_replace('{{' . $key . '}}', $value, $subject);
            $body = str_replace('{{' . $key . '}}', $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body
        ];
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

