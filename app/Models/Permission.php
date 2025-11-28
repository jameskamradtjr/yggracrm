<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;
use Core\Database;

/**
 * Model Permission
 * 
 * Representa uma permissão no sistema
 * Permissões são globais (não multi-tenant)
 */
class Permission extends Model
{
    protected string $table = 'permissions';
    
    protected array $fillable = [
        'name',
        'slug',
        'resource',
        'action',
        'description'
    ];

    // Permissões são globais
    protected bool $multiTenant = false;

    /**
     * Retorna as roles que têm esta permissão
     */
    public function roles(): array
    {
        if (!isset($this->id)) {
            return [];
        }

        $sql = "
            SELECT r.* 
            FROM roles r
            INNER JOIN role_permission rp ON rp.role_id = r.id
            WHERE rp.permission_id = ?
        ";

        $results = $this->db->query($sql, [$this->id]);
        
        return array_map(function($row) {
            return (new Role())->fill($row);
        }, $results);
    }

    /**
     * Cria permissões CRUD para um recurso
     */
    public static function createCrudPermissions(string $resource, string $resourceName): array
    {
        $actions = [
            'create' => "Criar {$resourceName}",
            'read' => "Visualizar {$resourceName}",
            'update' => "Editar {$resourceName}",
            'delete' => "Deletar {$resourceName}",
        ];

        $permissions = [];
        $db = Database::getInstance();

        foreach ($actions as $action => $name) {
            $slug = "{$resource}.{$action}";
            
            // Verifica se já existe
            $existing = $db->queryOne(
                "SELECT id FROM permissions WHERE slug = ?",
                [$slug]
            );
            
            if ($existing) {
                // Se já existe, busca e adiciona ao array
                $permission = self::find($existing['id']);
                if ($permission) {
                    $permissions[] = $permission;
                }
            } else {
                // Cria nova permissão
                $permission = self::create([
                    'name' => $name,
                    'slug' => $slug,
                    'resource' => $resource,
                    'action' => $action,
                    'description' => "Permite {$name}"
                ]);

                $permissions[] = $permission;
            }
        }

        return $permissions;
    }
}

