<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\Role;
use App\Models\Permission;

/**
 * Controller de Roles (Funções/Cargos)
 * 
 * Gerencia CRUD de roles e suas permissões
 */
class RoleController extends Controller
{
    /**
     * Lista roles
     */
    public function index(): string
    {
        // Lista apenas roles da conta do owner
        $ownerId = auth()->id();
        $roles = Role::where('user_id', $ownerId)->get();

        return $this->view('roles/index', ['roles' => $roles]);
    }

    /**
     * Exibe formulário de criação
     */
    public function create(): string
    {
        $permissions = Permission::all();
        
        // Se não há permissões, cria as básicas
        if (empty($permissions)) {
            $this->createDefaultPermissions();
            $permissions = Permission::all();
        }
        
        // Agrupa permissões por resource (módulo)
        $permissionsByResource = [];
        foreach ($permissions as $permission) {
            $resource = $permission->resource ?? 'outros';
            if (!isset($permissionsByResource[$resource])) {
                $permissionsByResource[$resource] = [];
            }
            $permissionsByResource[$resource][] = $permission;
        }

        return $this->view('roles/create', [
            'permissionsByResource' => $permissionsByResource
        ]);
    }
    
    /**
     * Cria permissões padrão do sistema
     */
    private function createDefaultPermissions(): void
    {
        $resources = [
            'users' => 'Usuários',
            'roles' => 'Roles',
            'permissions' => 'Permissões',
            'dashboard' => 'Dashboard',
            'reports' => 'Relatórios',
            'settings' => 'Configurações',
        ];

        foreach ($resources as $resource => $name) {
            Permission::createCrudPermissions($resource, $name);
        }
    }

    /**
     * Salva nova role
     */
    public function store(): void
    {

        // Valida CSRF
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/roles/create');
        }

        $data = $this->validate([
            'name' => 'required|min:3|max:100',
            'slug' => 'required|unique:roles,slug',
            'description' => 'required'
        ]);

        // Adiciona user_id para multi-tenancy
        $data['user_id'] = auth()->id();
        $data['is_system'] = 0;

        // Cria role
        $role = Role::create($data);

        // Atribui permissões
        if ($this->request->has('permissions')) {
            $permissions = $this->request->input('permissions');
            if (is_array($permissions)) {
                $role->syncPermissions($permissions);
            }
        }

        session()->flash('success', 'Role criada com sucesso!');
        $this->redirect('/roles');
    }

    /**
     * Exibe detalhes da role
     */
    public function show(array $params): string
    {
        $ownerId = auth()->id();
        $role = Role::where('id', $params['id'])
                    ->where('user_id', $ownerId)
                    ->first();

        if (!$role) {
            abort(404, 'Role não encontrada.');
        }

        return $this->view('roles/show', ['role' => $role]);
    }

    /**
     * Exibe formulário de edição
     */
    public function edit(array $params): string
    {
        $ownerId = auth()->id();
        $role = Role::where('id', $params['id'])
                    ->where('user_id', $ownerId)
                    ->first();

        if (!$role) {
            abort(404, 'Role não encontrada.');
        }

        // Não permite editar roles de sistema
        if ($role->is_system) {
            session()->flash('error', 'Roles de sistema não podem ser editadas.');
            $this->redirect('/roles');
        }

        $permissions = Permission::all();
        
        // Se não há permissões, cria as básicas
        if (empty($permissions)) {
            $this->createDefaultPermissions();
            $permissions = Permission::all();
        }
        
        $rolePermissions = array_column($role->permissions(), 'id');
        
        // Agrupa permissões por resource (módulo)
        $permissionsByResource = [];
        foreach ($permissions as $permission) {
            $resource = $permission->resource ?? 'outros';
            if (!isset($permissionsByResource[$resource])) {
                $permissionsByResource[$resource] = [];
            }
            $permissionsByResource[$resource][] = $permission;
        }

        return $this->view('roles/edit', [
            'role' => $role,
            'permissionsByResource' => $permissionsByResource,
            'rolePermissions' => $rolePermissions
        ]);
    }

    /**
     * Atualiza role
     */
    public function update(array $params): void
    {
        // Valida CSRF
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/roles');
        }

        $ownerId = auth()->id();
        $role = Role::where('id', $params['id'])
                    ->where('user_id', $ownerId)
                    ->first();

        if (!$role) {
            abort(404, 'Role não encontrada.');
        }

        // Não permite editar roles de sistema
        if ($role->is_system) {
            session()->flash('error', 'Roles de sistema não podem ser editadas.');
            $this->redirect('/roles');
        }

        $data = $this->validate([
            'name' => 'required|min:3|max:100',
            'description' => 'required'
        ]);

        $role->update($data);

        // Atualiza permissões
        if ($this->request->has('permissions')) {
            $permissions = $this->request->input('permissions');
            if (is_array($permissions)) {
                $role->syncPermissions($permissions);
            }
        }

        session()->flash('success', 'Role atualizada com sucesso!');
        $this->redirect('/roles');
    }

    /**
     * Deleta role
     */
    public function destroy(array $params): void
    {
        $ownerId = auth()->id();
        $role = Role::where('id', $params['id'])
                    ->where('user_id', $ownerId)
                    ->first();

        if (!$role) {
            abort(404, 'Role não encontrada.');
        }

        // Não permite deletar roles de sistema
        if ($role->is_system) {
            session()->flash('error', 'Roles de sistema não podem ser deletadas.');
            $this->redirect('/roles');
        }

        $role->delete();

        session()->flash('success', 'Role deletada com sucesso!');
        $this->redirect('/roles');
    }
}

