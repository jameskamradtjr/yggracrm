<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\UserPermission;
use App\Models\SistemaLog;
use Core\FileHelper;

/**
 * Controller de Usuários
 * 
 * Gerencia CRUD de usuários e sub-usuários
 */
class UserController extends Controller
{
    /**
     * Lista usuários
     */
    public function index(): string
    {
        // Verifica permissão
        $this->authorizeGranularOrFail('gerenciamento', 'usuarios', 'view');
        
        // Lista apenas sub-usuários da conta (user_id do owner) e o próprio owner
        $ownerId = auth()->getDataUserId();
        $db = \Core\Database::getInstance();
        
        // Query direta porque User não tem multiTenant habilitado
        $users = $db->query(
            "SELECT * FROM users WHERE user_id = ? OR id = ? ORDER BY name ASC",
            [$ownerId, $ownerId]
        );
        
        // Converte para objetos User
        $userModels = array_map(function($row) {
            return User::newInstance($row, true);
        }, $users);

        return $this->view('users/index', ['users' => $userModels]);
    }

    /**
     * Exibe formulário de criação
     */
    public function create(): string
    {
        // Verifica permissão
        $this->authorizeGranularOrFail('gerenciamento', 'usuarios', 'create');
        
        // Lista apenas roles da conta do owner
        $ownerId = auth()->getDataUserId();
        $roles = Role::where('user_id', $ownerId)->get();

        return $this->view('users/create', ['roles' => $roles]);
    }

    /**
     * Salva novo usuário
     */
    public function store(): void
    {

        // Valida CSRF
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/users/create');
        }

        $data = $this->validate([
            'name' => 'required|min:3|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'phone' => 'required',
            'status' => 'required'
        ]);

        $data['password'] = bcrypt($data['password']);
        $data['status'] = $data['status'] ?? 'active';
        
        // Associa ao user_id do owner (conta principal)
        $data['user_id'] = auth()->getDataUserId();

        // Cria usuário
        $user = User::create($data);
        
        // Processa foto se fornecida (após criar o usuário para ter o ID)
        if ($this->request->has('avatar_base64') && !empty($this->request->input('avatar_base64'))) {
            $avatarBase64 = trim($this->request->input('avatar_base64', ''));
            if (!empty($avatarBase64) && strlen($avatarBase64) > 100) {
                $filename = 'user_' . $user->id . '_' . time();
                $avatarPath = FileHelper::saveBase64Image($avatarBase64, 'storage/avatars', $filename);
                
                if ($avatarPath) {
                    // Atualiza o usuário com o caminho do avatar
                    $user->update(['avatar' => $avatarPath]);
                }
            }
        }

        // Cria perfil do usuário
        \App\Models\UserProfile::create([
            'user_id' => $user->id
        ]);

        // Atribui roles
        if ($this->request->has('roles')) {
            $roles = $this->request->input('roles');
            if (is_array($roles) && !empty($roles)) {
                $user->syncRoles($roles);
            }
        }
        
        // Sincroniza permissões granulares
        $permissions = [];
        
        // Tenta pegar do formato JSON primeiro
        if ($this->request->has('permissions_json') && !empty($this->request->input('permissions_json'))) {
            $permissionsJson = json_decode($this->request->input('permissions_json'), true);
            if (is_array($permissionsJson)) {
                $permissions = $permissionsJson;
            }
        }
        
        // Se não tiver JSON, tenta o formato array direto
        if (empty($permissions) && $this->request->has('permissions') && is_array($this->request->input('permissions'))) {
            foreach ($this->request->input('permissions') as $modulo => $recursos) {
                foreach ($recursos as $recurso => $acoes) {
                    foreach ($acoes as $acao => $granted) {
                        if ($granted) {
                            $permissions[] = [
                                'module' => $modulo,
                                'resource' => $recurso,
                                'action' => $acao,
                                'granted' => true
                            ];
                        }
                    }
                }
            }
        }
        
        if (!empty($permissions)) {
            UserPermission::syncPermissions($user->id, $permissions);
        }
        
        SistemaLog::registrar('users', 'CREATE', $user->id, "Usuário criado: {$user->name}");

        session()->flash('success', 'Usuário criado com sucesso!');
        $this->redirect('/users');
    }

    /**
     * Exibe detalhes do usuário
     */
    public function show(array $params): string
    {

        $ownerId = auth()->getDataUserId();
        $db = \Core\Database::getInstance();
        $userData = $db->queryOne(
            "SELECT * FROM users WHERE id = ? AND (user_id = ? OR id = ?)",
            [$params['id'], $ownerId, $ownerId]
        );
        
        if (!$userData) {
            abort(404, 'Usuário não encontrado.');
        }
        
        $user = User::newInstance($userData, true);

        if (!$user) {
            abort(404, 'Usuário não encontrado.');
        }

        return $this->view('users/show', ['user' => $user]);
    }

    /**
     * Exibe formulário de edição
     */
    public function edit(array $params): string
    {
        // Verifica permissão
        $this->authorizeGranularOrFail('gerenciamento', 'usuarios', 'edit');
        
        $ownerId = auth()->getDataUserId();
        $db = \Core\Database::getInstance();
        $userData = $db->queryOne(
            "SELECT * FROM users WHERE id = ? AND (user_id = ? OR id = ?)",
            [$params['id'], $ownerId, $ownerId]
        );
        
        if (!$userData) {
            abort(404, 'Usuário não encontrado.');
        }
        
        $user = User::newInstance($userData, true);

        // Lista apenas roles da conta do owner
        $roles = Role::where('user_id', $ownerId)->get();
        $userRoles = array_column($user->roles(), 'id');
        
        // Busca permissões granulares do usuário
        $permissions = $db->query(
            "SELECT module, resource, action, granted FROM user_permissions WHERE user_id = ? AND owner_user_id = ?",
            [$user->id, $ownerId]
        );
        
        $userPermissions = [];
        foreach ($permissions as $perm) {
            $key = "{$perm['module']}.{$perm['resource']}.{$perm['action']}";
            $userPermissions[$key] = (bool)$perm['granted'];
        }

        return $this->view('users/edit', [
            'user' => $user,
            'roles' => $roles,
            'userRoles' => $userRoles,
            'userPermissions' => $userPermissions
        ]);
    }

    /**
     * Atualiza usuário
     */
    public function update(array $params): void
    {
        // Verifica permissão
        $this->authorizeGranularOrFail('gerenciamento', 'usuarios', 'edit');
        
        // Valida CSRF
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/users');
        }

        $ownerId = auth()->getDataUserId();
        $db = \Core\Database::getInstance();
        $userData = $db->queryOne(
            "SELECT * FROM users WHERE id = ? AND (user_id = ? OR id = ?)",
            [$params['id'], $ownerId, $ownerId]
        );
        
        if (!$userData) {
            abort(404, 'Usuário não encontrado.');
        }
        
        $user = User::newInstance($userData, true);
        
        $dadosAnteriores = $user->toArray();

        $data = $this->validate([
            'name' => 'required|min:3|max:100',
            'email' => 'required|email',
            'phone' => 'required',
            'status' => 'required'
        ]);

        // Atualiza senha se fornecida
        if ($this->request->filled('password')) {
            $this->validate(['password' => 'required|min:6|confirmed']);
            $data['password'] = bcrypt($this->request->input('password'));
        }
        
        // Processa foto se fornecida
        if ($this->request->has('avatar_base64') && !empty($this->request->input('avatar_base64'))) {
            $avatarBase64 = trim($this->request->input('avatar_base64', ''));
            if (!empty($avatarBase64) && strlen($avatarBase64) > 100) {
                // Remove avatar antigo se existir
                if (!empty($user->avatar)) {
                    FileHelper::deleteFile($user->avatar);
                }
                
                $filename = 'user_' . $user->id . '_' . time();
                $avatarPath = FileHelper::saveBase64Image($avatarBase64, 'storage/avatars', $filename);
                
                if ($avatarPath) {
                    $data['avatar'] = $avatarPath;
                }
            }
        }

        $user->update($data);

        // Atualiza roles
        if ($this->request->has('roles')) {
            $roles = $this->request->input('roles');
            if (is_array($roles)) {
                $user->syncRoles($roles);
            }
        }
        
        // Sincroniza permissões granulares
        $permissions = [];
        
        // Tenta pegar do formato JSON primeiro
        if ($this->request->has('permissions_json') && !empty($this->request->input('permissions_json'))) {
            $permissionsJson = json_decode($this->request->input('permissions_json'), true);
            if (is_array($permissionsJson)) {
                $permissions = $permissionsJson;
            }
        }
        
        // Se não tiver JSON, tenta o formato array direto
        if (empty($permissions) && $this->request->has('permissions') && is_array($this->request->input('permissions'))) {
            foreach ($this->request->input('permissions') as $modulo => $recursos) {
                foreach ($recursos as $recurso => $acoes) {
                    foreach ($acoes as $acao => $granted) {
                        if ($granted) {
                            $permissions[] = [
                                'module' => $modulo,
                                'resource' => $recurso,
                                'action' => $acao,
                                'granted' => true
                            ];
                        }
                    }
                }
            }
        }
        
        UserPermission::syncPermissions($user->id, $permissions);
        
        SistemaLog::registrar('users', 'UPDATE', $user->id, "Usuário atualizado: {$user->name}", $dadosAnteriores, $user->toArray());

        session()->flash('success', 'Usuário atualizado com sucesso!');
        $this->redirect('/users');
    }

    /**
     * Deleta usuário
     */
    public function destroy(array $params): void
    {
        // Verifica permissão
        $this->authorizeGranularOrFail('gerenciamento', 'usuarios', 'delete');
        
        $ownerId = auth()->getDataUserId();
        $db = \Core\Database::getInstance();
        $userData = $db->queryOne(
            "SELECT * FROM users WHERE id = ? AND (user_id = ? OR id = ?)",
            [$params['id'], $ownerId, $ownerId]
        );
        
        if (!$userData) {
            abort(404, 'Usuário não encontrado.');
        }
        
        $user = User::newInstance($userData, true);

        // Não permite deletar a si mesmo
        if ($user->id === auth()->id()) {
            session()->flash('error', 'Você não pode deletar seu próprio usuário.');
            $this->back();
            return;
        }
        
        $nome = $user->name;
        $id = $user->id;
        
        // Remove avatar se existir
        if (!empty($user->avatar)) {
            FileHelper::deleteFile($user->avatar);
        }

        $user->delete();
        
        SistemaLog::registrar('users', 'DELETE', $id, "Usuário deletado: {$nome}");

        session()->flash('success', 'Usuário deletado com sucesso!');
        $this->redirect('/users');
    }

    /**
     * Perfil do usuário logado
     */
    public function profile(): string
    {
        $user = auth()->user();
        $profile = $user->profile();

        return $this->view('users/profile', [
            'user' => $user,
            'profile' => $profile
        ]);
    }

    /**
     * Atualiza perfil do usuário logado
     */
    public function updateProfile(): void
    {
        // Valida CSRF
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/users');
        }

        $user = auth()->user();

        $data = $this->validate([
            'name' => 'required|min:3|max:100',
            'email' => 'required|email',
            'phone' => 'required'
        ]);

        // Atualiza senha se fornecida
        if ($this->request->filled('password')) {
            $currentPassword = $this->request->input('current_password');
            
            // Verifica senha atual
            if (!password_verify($currentPassword, $user->password)) {
                session()->flash('error', 'Senha atual incorreta.');
                $this->back();
                return;
            }
            
            $this->validate([
                'password' => 'required|min:6|confirmed',
                'current_password' => 'required'
            ]);
            
            $data['password'] = bcrypt($this->request->input('password'));
        }
        
        // Processa foto se fornecida
        if ($this->request->has('avatar_base64') && !empty($this->request->input('avatar_base64'))) {
            $avatarBase64 = trim($this->request->input('avatar_base64', ''));
            if (!empty($avatarBase64) && strlen($avatarBase64) > 100) {
                // Remove avatar antigo se existir
                if (!empty($user->avatar)) {
                    FileHelper::deleteFile($user->avatar);
                }
                
                $filename = 'user_' . $user->id . '_' . time();
                $avatarPath = FileHelper::saveBase64Image($avatarBase64, 'storage/avatars', $filename);
                
                if ($avatarPath) {
                    $data['avatar'] = $avatarPath;
                }
            }
        }

        $user->update($data);

        // Atualiza profile
        $profile = $user->profile();
        if ($profile) {
            $profileData = $this->request->only([
                'company_name', 'cnpj', 'cpf', 'address', 
                'city', 'state', 'zipcode', 'bio', 'website'
            ]);
            $profile->update($profileData);
        }
        
        SistemaLog::registrar('users', 'UPDATE', $user->id, "Perfil atualizado: {$user->name}");

        session()->flash('success', 'Perfil atualizado com sucesso!');
        $this->back();
    }
}

