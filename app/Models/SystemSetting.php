<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Model SystemSetting
 * 
 * Representa uma configuração do sistema
 */
class SystemSetting extends Model
{
    protected string $table = 'system_settings';
    
    protected array $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description'
    ];

    // Configurações são globais (não multi-tenant)
    protected bool $multiTenant = false;

    /**
     * Obtém uma configuração por chave
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        // Processa o valor baseado no tipo
        switch ($setting->type) {
            case 'json':
                return json_decode($setting->value, true) ?? $default;
            case 'boolean':
                return (bool)$setting->value;
            case 'integer':
                return (int)$setting->value;
            default:
                return $setting->value ?? $default;
        }
    }

    /**
     * Define uma configuração
     */
    public static function set(string $key, $value, string $type = 'text', string $group = 'general', ?string $description = null): bool
    {
        // Processa o valor baseado no tipo
        if ($type === 'json' && is_array($value)) {
            $value = json_encode($value);
        } elseif ($type === 'boolean') {
            $value = $value ? '1' : '0';
        }

        $setting = self::where('key', $key)->first();

        if ($setting) {
            return $setting->update([
                'value' => $value,
                'type' => $type,
                'group' => $group,
                'description' => $description
            ]);
        } else {
            $setting = self::create([
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'group' => $group,
                'description' => $description
            ]);
            return $setting !== null;
        }
    }

    /**
     * Obtém todas as configurações de um grupo
     */
    public static function getByGroup(string $group): array
    {
        return self::where('group', $group)->get();
    }
}

