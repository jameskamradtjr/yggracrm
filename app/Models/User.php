<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;
use Core\Database;

/**
 * Model User
 * 
 * Representa um usuário do sistema
 * Implementa RBAC e multi-tenancy
 */
class User extends Model
{
    protected string $table = 'users';
    
    protected array $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'status',
        'email_verified_at',
        'remember_token',
        'last_login_at',
        'last_login_ip',
        'user_id'
    ];

    protected array $hidden = [
        'password',
        'remember_token'
    ];

    // Desabilita multi-tenant para User (é a tabela base)
    protected bool $multiTenant = false;

    /**
     * Retorna as roles do usuário
     */
    public function roles(): array
    {
        if (!isset($this->id)) {
            return [];
        }

        $sql = "
            SELECT r.* 
            FROM roles r
            INNER JOIN user_role ur ON ur.role_id = r.id
            WHERE ur.user_id = ?
        ";

        $results = $this->db->query($sql, [$this->id]);
        
        return array_map(function($row) {
            return (new Role())->fill($row);
        }, $results);
    }

    /**
     * Retorna as permissões do usuário
     */
    public function permissions(): array
    {
        if (!isset($this->id)) {
            return [];
        }

        $sql = "
            SELECT DISTINCT p.* 
            FROM permissions p
            INNER JOIN role_permission rp ON rp.permission_id = p.id
            INNER JOIN user_role ur ON ur.role_id = rp.role_id
            WHERE ur.user_id = ?
        ";

        $results = $this->db->query($sql, [$this->id]);
        
        return array_map(function($row) {
            return (new Permission())->fill($row);
        }, $results);
    }

    /**
     * Verifica se o usuário tem uma role
     */
    public function hasRole(string $roleSlug): bool
    {
        $roles = $this->roles();
        
        foreach ($roles as $role) {
            if ($role->slug === $roleSlug) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se o usuário tem qualquer uma das roles
     */
    public function hasAnyRole(array $roleSlugs): bool
    {
        foreach ($roleSlugs as $slug) {
            if ($this->hasRole($slug)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se o usuário tem todas as roles
     */
    public function hasAllRoles(array $roleSlugs): bool
    {
        foreach ($roleSlugs as $slug) {
            if (!$this->hasRole($slug)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifica se o usuário tem uma permissão
     */
    public function hasPermission(string $permissionSlug): bool
    {
        // Super admin tem todas as permissões
        if ($this->hasRole('super-admin')) {
            return true;
        }

        $permissions = $this->permissions();
        
        foreach ($permissions as $permission) {
            if ($permission->slug === $permissionSlug) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se o usuário pode realizar uma ação em um recurso
     */
    public function can(string $action, string $resource): bool
    {
        return $this->hasPermission("{$resource}.{$action}");
    }

    /**
     * Atribui uma role ao usuário
     */
    public function assignRole(int $roleId): bool
    {
        $sql = "INSERT INTO user_role (user_id, role_id, created_at) VALUES (?, ?, NOW())";
        
        try {
            $this->db->execute($sql, [$this->id, $roleId]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove uma role do usuário
     */
    public function removeRole(int $roleId): bool
    {
        $sql = "DELETE FROM user_role WHERE user_id = ? AND role_id = ?";
        $stmt = $this->db->execute($sql, [$this->id, $roleId]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Sincroniza roles do usuário
     */
    public function syncRoles(array $roleIds): void
    {
        // Remove todas as roles atuais
        $sql = "DELETE FROM user_role WHERE user_id = ?";
        $this->db->execute($sql, [$this->id]);

        // Adiciona as novas roles
        foreach ($roleIds as $roleId) {
            $this->assignRole($roleId);
        }
    }

    /**
     * Retorna o perfil do usuário
     */
    public function profile(): ?UserProfile
    {
        return UserProfile::where('user_id', $this->id)->first();
    }

    /**
     * Atualiza último login
     */
    public function updateLastLogin(): void
    {
        $this->update([
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }

    /**
     * Verifica se o email está verificado
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Marca email como verificado
     */
    public function markEmailAsVerified(): bool
    {
        return $this->update([
            'email_verified_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Verifica se a conta está ativa
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Verifica se a conta está suspensa
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Verifica se o usuário pode acessar um módulo/recurso/ação (permissões granulares)
     */
    public function canAccess(string $module, string $resource, string $action): bool
    {
        // Usuário principal (user_id = NULL) tem todas as permissões (admin master)
        $userId = $this->attributes['user_id'] ?? null;
        if (empty($userId)) {
            return true;
        }
        
        // Super admin tem todas as permissões
        if ($this->hasRole('super-admin')) {
            return true;
        }

        return \App\Models\UserPermission::hasPermission($this->id, $module, $resource, $action);
    }
}

