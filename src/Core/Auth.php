<?php

declare(strict_types=1);

namespace Core;

use App\Models\User;

/**
 * Classe Auth - Gerenciamento de autenticação
 * 
 * Implementa autenticação de usuários com sessão
 * Suporta "remember me" e multi-tenancy
 */
class Auth
{
    private static ?Auth $instance = null;
    private ?object $user = null;
    private bool $loaded = false;

    private function __construct()
    {
        $this->loadUser();
    }

    /**
     * Obtém instância única (Singleton)
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Carrega o usuário da sessão
     */
    private function loadUser(): void
    {
        if ($this->loaded) {
            return;
        }

        $userId = session()->get('user_id');

        if ($userId) {
            $this->user = User::find($userId);
        }

        $this->loaded = true;
    }

    /**
     * Autentica um usuário
     */
    public function attempt(array $credentials): bool
    {
        $email = $credentials['email'] ?? null;
        $password = $credentials['password'] ?? null;

        if (!$email || !$password) {
            return false;
        }

        $user = User::where('email', $email)->first();

        if (!$user || !password_verify($password, $user->password)) {
            return false;
        }

        // Verifica se a conta está ativa
        if (isset($user->status) && $user->status !== 'active') {
            return false;
        }

        $this->login($user);

        return true;
    }

    /**
     * Faz login de um usuário
     */
    public function login(object $user): void
    {
        session()->regenerate();
        session()->set('user_id', $user->id);
        session()->set('user_email', $user->email);
        
        // Define o usuário e marca como carregado
        $this->user = $user;
        $this->loaded = true;
        
        // Atualiza último login
        if (method_exists($user, 'updateLastLogin')) {
            $user->updateLastLogin();
        }
    }

    /**
     * Faz logout do usuário
     */
    public function logout(): void
    {
        session()->forget('user_id');
        session()->forget('user_email');
        session()->destroy();
        
        $this->user = null;
    }

    /**
     * Verifica se usuário está autenticado
     */
    public function check(): bool
    {
        // Garante que o usuário foi carregado
        if (!$this->loaded) {
            $this->loadUser();
        }
        
        return $this->user !== null && session()->has('user_id');
    }

    /**
     * Verifica se usuário é convidado (não autenticado)
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Retorna o usuário autenticado
     */
    public function user(): ?object
    {
        // Garante que o usuário foi carregado
        if (!$this->loaded) {
            $this->loadUser();
        }
        
        return $this->user;
    }

    /**
     * Retorna o ID do usuário autenticado
     */
    public function id(): ?int
    {
        return $this->user?->id;
    }

    /**
     * Verifica se o usuário tem uma permissão
     */
    public function can(string $permission): bool
    {
        if (!$this->check()) {
            return false;
        }

        return $this->user->hasPermission($permission);
    }

    /**
     * Verifica se o usuário tem uma role
     */
    public function hasRole(string $role): bool
    {
        if (!$this->check()) {
            return false;
        }

        return $this->user->hasRole($role);
    }

    /**
     * Verifica se o usuário tem qualquer uma das roles
     */
    public function hasAnyRole(array $roles): bool
    {
        if (!$this->check()) {
            return false;
        }

        foreach ($roles as $role) {
            if ($this->user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retorna o ID do owner para multi-tenancy
     * Se o usuário é sub-usuário (tem user_id), retorna o user_id (owner)
     * Se o usuário é master (user_id é NULL), retorna o próprio ID
     */
    public function getDataUserId(): ?int
    {
        if (!$this->check()) {
            return null;
        }

        $user = $this->user();
        
        // Acessa user_id do array de atributos do model
        $userId = $user->attributes['user_id'] ?? null;
        
        // Se o usuário tem user_id (é secundário), usa o user_id como owner
        if (!empty($userId)) {
            return (int)$userId;
        }
        
        // Se o usuário não tem user_id (é principal), usa o próprio ID
        return $this->id();
    }

    /**
     * Previne clonagem
     */
    private function __clone() {}
}

