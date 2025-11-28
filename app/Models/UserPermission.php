<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Model UserPermission
 * 
 * Representa permissões granulares de usuário por módulo/recurso/ação
 */
class UserPermission extends Model
{
    protected string $table = 'user_permissions';
    
    protected array $fillable = [
        'user_id',
        'module',
        'resource',
        'action',
        'granted',
        'owner_user_id'
    ];

    protected bool $timestamps = true;
    protected bool $multiTenant = true;
    
    /**
     * Verifica se o usuário tem permissão para uma ação específica
     */
    public static function hasPermission(int $userId, string $module, string $resource, string $action): bool
    {
        $db = \Core\Database::getInstance();
        
        // Busca o user_id do usuário para determinar o owner
        $userData = $db->queryOne("SELECT user_id FROM users WHERE id = ?", [$userId]);
        $ownerId = $userData['user_id'] ?? null;
        
        // Se o usuário não tem user_id (é principal), usa o próprio ID como owner
        if (empty($ownerId)) {
            $ownerId = $userId;
        }
        
        // Verifica permissão específica
        $permission = $db->queryOne(
            "SELECT granted FROM user_permissions 
             WHERE user_id = ? AND owner_user_id = ? AND module = ? AND resource = ? AND action = ?",
            [$userId, $ownerId, $module, $resource, $action]
        );
        
        if ($permission) {
            return (bool)$permission['granted'];
        }
        
        // Verifica se tem permissão "all" para o recurso
        $permissionAll = $db->queryOne(
            "SELECT granted FROM user_permissions 
             WHERE user_id = ? AND owner_user_id = ? AND module = ? AND resource = ? AND action = 'all'",
            [$userId, $ownerId, $module, $resource]
        );
        
        if ($permissionAll) {
            return (bool)$permissionAll['granted'];
        }
        
        // Por padrão, nega acesso se não houver permissão explícita
        return false;
    }
    
    /**
     * Sincroniza permissões de um usuário
     */
    public static function syncPermissions(int $userId, array $permissions): void
    {
        $db = \Core\Database::getInstance();
        
        // Busca o user_id do usuário para determinar o owner
        $userData = $db->queryOne("SELECT user_id FROM users WHERE id = ?", [$userId]);
        $ownerId = $userData['user_id'] ?? null;
        
        // Se o usuário não tem user_id (é principal), usa o próprio ID como owner
        if (empty($ownerId)) {
            $ownerId = $userId;
        }
        
        // Remove todas as permissões atuais do usuário
        $db->execute("DELETE FROM user_permissions WHERE user_id = ? AND owner_user_id = ?", [$userId, $ownerId]);
        
        // Insere novas permissões
        foreach ($permissions as $perm) {
            if (isset($perm['module']) && isset($perm['resource']) && isset($perm['action'])) {
                $db->execute(
                    "INSERT INTO user_permissions (user_id, module, resource, action, granted, owner_user_id, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())",
                    [
                        $userId,
                        $perm['module'],
                        $perm['resource'],
                        $perm['action'],
                        (int)($perm['granted'] ?? true),
                        $ownerId
                    ]
                );
            }
        }
    }
}


