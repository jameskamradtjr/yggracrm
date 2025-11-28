<?php

declare(strict_types=1);

namespace Core;

/**
 * Classe Session - Gerenciamento de sessões
 */
class Session
{
    private static ?Session $instance = null;

    private function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            $this->start();
        }
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
     * Inicia a sessão
     */
    public function start(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }

        $config = config('session');

        session_name($config['name'] ?? 'PHPSESSID');
        
        session_set_cookie_params([
            'lifetime' => $config['lifetime'] * 60,
            'path' => $config['path'] ?? '/',
            'domain' => $config['domain'] ?? '',
            'secure' => $config['secure'] ?? false,
            'httponly' => $config['http_only'] ?? true,
            'samesite' => $config['same_site'] ?? 'Lax'
        ]);

        return session_start();
    }

    /**
     * Define um valor na sessão
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Obtém um valor da sessão
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Verifica se existe uma chave
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove uma chave da sessão
     */
    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Define uma mensagem flash
     */
    public function flash(string $key, mixed $value): void
    {
        $this->set('_flash_' . $key, $value);
    }

    /**
     * Obtém e remove uma mensagem flash
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        $flashKey = '_flash_' . $key;
        $value = $this->get($flashKey, $default);
        
        if ($value !== null) {
            // Marca como "old" para ser removida na próxima requisição
            $oldKey = '_flash_old_' . $key;
            $this->set($oldKey, $value);
            $this->forget($flashKey);
        }
        
        return $value;
    }

    /**
     * Limpa todas as mensagens flash antigas
     * Remove apenas mensagens que foram marcadas como "old" na requisição anterior
     */
    public function ageFlashData(): void
    {
        // Remove apenas mensagens flash antigas (que foram lidas e marcadas como "old")
        foreach ($_SESSION as $key => $value) {
            if (str_starts_with($key, '_flash_old_')) {
                unset($_SESSION[$key]);
            }
        }
    }

    /**
     * Regenera o ID da sessão
     */
    public function regenerate(bool $deleteOld = true): bool
    {
        return session_regenerate_id($deleteOld);
    }

    /**
     * Destroi a sessão
     */
    public function destroy(): bool
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        return session_destroy();
    }

    /**
     * Obtém todos os dados da sessão
     */
    public function all(): array
    {
        return $_SESSION;
    }

    /**
     * Previne clonagem
     */
    private function __clone() {}
}

