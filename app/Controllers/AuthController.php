<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\User;
use App\Models\Role;

/**
 * Controller de Autenticação
 * 
 * Gerencia login, registro, logout e recuperação de senha
 */
class AuthController extends Controller
{
    /**
     * Exibe formulário de login
     */
    public function showLogin(): string
    {
        if (auth()->check()) {
            $this->redirect('/dashboard');
        }

        return $this->view('auth/login');
    }

    /**
     * Processa login
     */
    public function login(): void
    {
        // Valida CSRF
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/login');
        }

        $data = $this->validate([
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        if (auth()->attempt($data)) {
            session()->flash('success', 'Login realizado com sucesso!');
            $this->redirect('/dashboard');
        }

        session()->flash('error', 'Credenciais inválidas.');
        $this->redirect('/login');
    }

    /**
     * Exibe formulário de registro
     */
    public function showRegister(): string
    {
        if (auth()->check()) {
            $this->redirect('/dashboard');
        }

        return $this->view('auth/register');
    }

    /**
     * Processa registro
     */
    public function register(): void
    {
        // Valida CSRF
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/register');
        }

        $data = $this->validate([
            'name' => 'required|min:3|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'phone' => 'required'
        ]);

        // Hash da senha
        $data['password'] = bcrypt($data['password']);
        $data['status'] = 'active';

        // Cria usuário
        $user = User::create($data);

        // Cria role padrão para o usuário principal (owner)
        $role = Role::create([
            'user_id' => $user->id,
            'name' => 'Administrador',
            'slug' => 'admin',
            'description' => 'Administrador da conta',
            'is_system' => 1
        ]);

        // Atribui a role ao usuário
        $user->assignRole($role->id);

        // Cria perfil
        \App\Models\UserProfile::create([
            'user_id' => $user->id
        ]);

        // Faz login automático
        auth()->login($user);

        session()->flash('success', 'Conta criada com sucesso! Bem-vindo ao sistema.');
        $this->redirect('/dashboard');
    }

    /**
     * Exibe formulário de esqueci senha
     */
    public function showForgotPassword(): string
    {
        return $this->view('auth/forgot-password');
    }

    /**
     * Envia email de recuperação
     */
    public function forgotPassword(): void
    {
        // Valida CSRF
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/forgot-password');
        }

        $data = $this->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            session()->flash('error', 'Email não encontrado.');
            $this->redirect('/forgot-password');
        }

        // Gera token
        $token = bin2hex(random_bytes(32));

        // Salva token no banco
        $this->request->app->db->execute(
            "INSERT INTO password_resets (email, token, created_at) VALUES (?, ?, NOW())",
            [$data['email'], hash('sha256', $token)]
        );

        // TODO: Enviar email com link de reset
        // Por enquanto, apenas mostra mensagem de sucesso
        
        session()->flash('success', 'Instruções de recuperação enviadas para seu email.');
        $this->redirect('/login');
    }

    /**
     * Exibe formulário de reset de senha
     */
    public function showResetPassword(): string
    {
        $token = $this->request->query('token');

        if (!$token) {
            session()->flash('error', 'Token inválido.');
            $this->redirect('/login');
        }

        return $this->view('auth/reset-password', ['token' => $token]);
    }

    /**
     * Processa reset de senha
     */
    public function resetPassword(): void
    {
        // Valida CSRF
        if (!verify_csrf($this->request->input('_csrf_token'))) {
            session()->flash('error', 'Token de segurança inválido.');
            $this->redirect('/reset-password');
        }

        $data = $this->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed'
        ]);

        // Verifica token
        $reset = $this->request->app->db->queryOne(
            "SELECT * FROM password_resets WHERE email = ? AND token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$data['email'], hash('sha256', $data['token'])]
        );

        if (!$reset) {
            session()->flash('error', 'Token inválido ou expirado.');
            $this->redirect('/forgot-password');
        }

        // Atualiza senha
        $user = User::where('email', $data['email'])->first();
        $user->update(['password' => bcrypt($data['password'])]);

        // Remove token usado
        $this->request->app->db->execute(
            "DELETE FROM password_resets WHERE email = ?",
            [$data['email']]
        );

        session()->flash('success', 'Senha alterada com sucesso!');
        $this->redirect('/login');
    }

    /**
     * Faz logout
     */
    public function logout(): void
    {
        auth()->logout();
        session()->flash('success', 'Você saiu do sistema.');
        $this->redirect('/login');
    }
}

