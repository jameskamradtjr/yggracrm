<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Model Role
 * 
 * Representa uma role (função/cargo) no sistema
 * Suporta multi-tenancy via user_id
 */
class Role extends Model
{
    protected string $table = 'roles';
    
    protected array $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'is_system'
    ];

    protected bool $multiTenant = true;
    protected string $tenantColumn = 'user_id';

    /**
     * Retorna as permissões da role
     */
    public function permissions(): array
    {
        if (!isset($this->id)) {
            return [];
        }

        $sql = "
            SELECT p.* 
            FROM permissions p
            INNER JOIN role_permission rp ON rp.permission_id = p.id
            WHERE rp.role_id = ?
        ";

        $results = $this->db->query($sql, [$this->id]);
        
        return array_map(function($row) {
            return (new Permission())->fill($row);
        }, $results);
    }

    /**
     * Retorna os usuários com esta role
     */
    public function users(): array
    {
        if (!isset($this->id)) {
            return [];
        }

        $sql = "
            SELECT u.* 
            FROM users u
            INNER JOIN user_role ur ON ur.user_id = u.id
            WHERE ur.role_id = ?
        ";

        $results = $this->db->query($sql, [$this->id]);
        
        return array_map(function($row) {
            return (new User())->fill($row);
        }, $results);
    }

    /**
     * Atribui uma permissão à role
     */
    public function givePermission(int $permissionId): bool
    {
        $sql = "INSERT INTO role_permission (role_id, permission_id, created_at) VALUES (?, ?, NOW())";
        
        try {
            $this->db->execute($sql, [$this->id, $permissionId]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove uma permissão da role
     */
    public function revokePermission(int $permissionId): bool
    {
        $sql = "DELETE FROM role_permission WHERE role_id = ? AND permission_id = ?";
        $stmt = $this->db->execute($sql, [$this->id, $permissionId]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Sincroniza permissões da role
     */
    public function syncPermissions(array $permissionIds): void
    {
        // Remove todas as permissões atuais
        $sql = "DELETE FROM role_permission WHERE role_id = ?";
        $this->db->execute($sql, [$this->id]);

        // Adiciona as novas permissões
        foreach ($permissionIds as $permissionId) {
            $this->givePermission($permissionId);
        }
    }

    /**
     * Verifica se a role tem uma permissão
     */
    public function hasPermission(string $permissionSlug): bool
    {
        $permissions = $this->permissions();
        
        foreach ($permissions as $permission) {
            if ($permission->slug === $permissionSlug) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se é uma role de sistema
     */
    public function isSystemRole(): bool
    {
        return (bool)$this->is_system;
    }
}

