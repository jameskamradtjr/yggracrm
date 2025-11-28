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

    // Habilita multi-tenancy para configurações (cada usuário tem suas próprias)
    protected bool $multiTenant = true;

    /**
     * Obtém uma configuração por chave (filtra por user_id do usuário logado)
     */
    public static function get(string $key, $default = null)
    {
        $userId = auth()->check() ? auth()->getDataUserId() : null;
        
        $query = self::where('key', $key);
        
        // Filtra por user_id se houver usuário autenticado
        if ($userId !== null) {
            $query = $query->where('user_id', $userId);
        } else {
            // Se não há usuário autenticado, busca configurações globais (user_id NULL)
            $query = $query->whereNull('user_id');
        }
        
        $setting = $query->first();
        
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
     * Define uma configuração (associa ao user_id do usuário logado)
     */
    public static function set(string $key, $value, string $type = 'text', string $group = 'general', ?string $description = null): bool
    {
        $userId = auth()->check() ? auth()->getDataUserId() : null;
        
        // Processa o valor baseado no tipo
        if ($type === 'json' && is_array($value)) {
            $value = json_encode($value);
        } elseif ($type === 'boolean') {
            $value = $value ? '1' : '0';
        }

        // Busca configuração existente para este usuário
        $query = self::where('key', $key);
        if ($userId !== null) {
            $query = $query->where('user_id', $userId);
        } else {
            $query = $query->whereNull('user_id');
        }
        
        $setting = $query->first();

        if ($setting) {
            return $setting->update([
                'value' => $value,
                'type' => $type,
                'group' => $group,
                'description' => $description
            ]);
        } else {
            $data = [
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'group' => $group,
                'description' => $description
            ];
            
            // Adiciona user_id se houver usuário autenticado
            if ($userId !== null) {
                $data['user_id'] = $userId;
            }
            
            $setting = self::create($data);
            return $setting !== null;
        }
    }

    /**
     * Obtém todas as configurações de um grupo (filtra por user_id do usuário logado)
     */
    public static function getByGroup(string $group): array
    {
        $userId = auth()->check() ? auth()->getDataUserId() : null;
        
        $query = self::where('group', $group);
        
        // Filtra por user_id se houver usuário autenticado
        if ($userId !== null) {
            $query = $query->where('user_id', $userId);
        } else {
            // Se não há usuário autenticado, busca configurações globais (user_id NULL)
            $query = $query->whereNull('user_id');
        }
        
        return $query->get();
    }
}

