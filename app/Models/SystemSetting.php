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
        
        if ($userId === null) {
            error_log("SystemSetting::get() - AVISO: Usuário não autenticado ao buscar '{$key}'");
            return $default;
        }
        
        // Query DIRETA via SQL para garantir filtro correto
        $db = \Core\Database::getInstance();
        $sql = "SELECT * FROM `system_settings` WHERE `key` = ? AND `user_id` = ? LIMIT 1";
        $result = $db->query($sql, [$key, $userId]);
        
        if (empty($result) || !isset($result[0])) {
            error_log("SystemSetting::get() - '{$key}' não encontrado para user_id={$userId}, retornando default");
            return $default;
        }
        
        $setting = (object)$result[0];
        
       // error_log("SystemSetting::get() - '{$key}' encontrado (id={$setting->id}, type={$setting->type}, key={$setting->key}) para user_id={$userId}");

        // Processa o valor baseado no tipo
        switch ($setting->type) {
            case 'json':
                $decoded = json_decode($setting->value, true);
                error_log("SystemSetting::get() - '{$key}' decodificado JSON: " . (is_array($decoded) ? "ARRAY com " . count($decoded) . " elementos" : "FALHOU"));
                return $decoded ?? $default;
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
        
        if ($userId === null) {
           // error_log("SystemSetting::set() - ERRO: Usuário não autenticado! Não é possível salvar configuração '{$key}'");
            return false;
        }
        
        // Processa o valor baseado no tipo
        if ($type === 'json' && is_array($value)) {
            $value = json_encode($value);
        } elseif ($type === 'boolean') {
            $value = $value ? '1' : '0';
        }

       // error_log("SystemSetting::set() - Salvando '{$key}' para user_id={$userId}, type={$type}, group={$group}");

        $db = \Core\Database::getInstance();
        
        // Busca configuração existente para este usuário (query DIRETA)
        $sqlCheck = "SELECT * FROM `system_settings` WHERE `key` = ? AND `user_id` = ? LIMIT 1";
        $resultCheck = $db->query($sqlCheck, [$key, $userId]);
        
        $setting = !empty($resultCheck) && isset($resultCheck[0]) ? (object)$resultCheck[0] : null;
        
        $now = date('Y-m-d H:i:s');

        if ($setting) {
            // UPDATE DIRETO com WHERE explícito para garantir que atualiza APENAS o registro correto
           // error_log("SystemSetting::set() - UPDATE: Registro encontrado (id={$setting->id}, key_atual={$setting->key}, key_nova={$key})");
            
            // Verifica se a key do registro encontrado corresponde à key que queremos salvar
            if ($setting->key !== $key) {
               // error_log("SystemSetting::set() - ERRO CRÍTICO: Key do registro encontrado ({$setting->key}) não corresponde à key solicitada ({$key})!");
                // Força INSERT ao invés de UPDATE
                $setting = null;
            } else {
                $sql = "UPDATE `system_settings` 
                        SET `value` = ?, `type` = ?, `group` = ?, `description` = ?, `updated_at` = ? 
                        WHERE `key` = ? AND `user_id` = ? AND `id` = ?";
                
                $result = $db->execute($sql, [
                    $value,
                    $type,
                    $group,
                    $description,
                    $now,
                    $key,           // WHERE key
                    $userId,        // WHERE user_id
                    $setting->id    // WHERE id (garantia extra)
                ]);
                
              //  error_log("SystemSetting::set() - UPDATE " . ($result !== false ? "✓ SUCESSO" : "✗ FALHOU"));
                return $result !== false;
            }
        }
        
        // INSERT DIRETO para garantir que user_id seja salvo corretamente
       // error_log("SystemSetting::set() - INSERT: Criando novo registro para key='{$key}'");
        
        $sql = "INSERT INTO `system_settings` (`key`, `value`, `type`, `group`, `description`, `user_id`, `created_at`, `updated_at`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $result = $db->execute($sql, [
            $key,
            $value,
            $type,
            $group,
            $description,
            $userId,
            $now,
            $now
        ]);
        
       // error_log("SystemSetting::set() - INSERT " . ($result !== false ? "✓ SUCESSO" : "✗ FALHOU"));
        return $result !== false;
    }

    /**
     * Obtém todas as configurações de um grupo (filtra por user_id do usuário logado)
     */
    public static function getByGroup(string $group): array
    {
        $userId = auth()->check() ? auth()->getDataUserId() : null;
        
        $query = self::where('group', $group);
        
        // Filtra por user_id do usuário logado
        if ($userId !== null) {
            $query = $query->where('user_id', $userId);
        }
        
        return $query->get();
    }
}

