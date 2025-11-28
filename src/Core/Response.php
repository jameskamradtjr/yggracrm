<?php

declare(strict_types=1);

namespace Core;

/**
 * Classe Response - Gerenciamento de respostas HTTP
 */
class Response
{
    /**
     * Retorna resposta JSON
     */
    public function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Redireciona para uma URL
     */
    public function redirect(string $url, int $status = 302): void
    {
        // Se não é uma URL completa, usa o helper url() para gerar corretamente
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = url($url);
        }
        
        header("Location: {$url}", true, $status);
        exit;
    }

    /**
     * Retorna para a página anterior
     */
    public function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        
        // Se o referer não tem o base path, adiciona
        if (!str_starts_with($referer, 'http://') && !str_starts_with($referer, 'https://')) {
            // Se já começa com /, mantém
            if (!str_starts_with($referer, '/')) {
                $referer = '/' . $referer;
            }
        }
        
        $this->redirect($referer);
    }

    /**
     * Define um header
     */
    public function header(string $name, string $value): self
    {
        header("{$name}: {$value}");
        return $this;
    }

    /**
     * Define o status HTTP
     */
    public function status(int $code): self
    {
        http_response_code($code);
        return $this;
    }

    /**
     * Força download de arquivo
     */
    public function download(string $path, ?string $name = null): void
    {
        if (!file_exists($path)) {
            http_response_code(404);
            die('File not found');
        }

        $name = $name ?? basename($path);

        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"{$name}\"");
        readfile($path);
        exit;
    }
}

