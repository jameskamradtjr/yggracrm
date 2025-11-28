<?php

declare(strict_types=1);

namespace Core;

/**
 * Classe View - Sistema de templates
 * 
 * Renderiza views com suporte a layouts e componentes
 * Implementa cache de views compiladas
 */
class View
{
    private string $viewPath;
    private string $cachePath;
    private array $data = [];
    private ?string $layout = null;
    private array $sections = [];
    private array $sectionStack = [];

    public function __construct()
    {
        $this->viewPath = base_path('views');
        $this->cachePath = base_path('storage/cache');
    }

    /**
     * Renderiza uma view
     */
    public function render(string $view, array $data = []): string
    {
        $this->data = array_merge($this->data, $data);
        
        $viewFile = $this->viewPath . '/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View não encontrada: {$view}");
        }

        // Extrai variáveis para o escopo da view
        extract($this->data);
        extract($this->getSharedData());

        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        // Se tem layout, renderiza dentro dele
        if ($this->layout !== null) {
            return $this->renderLayout($content);
        }

        return $content;
    }

    /**
     * Renderiza o layout
     */
    private function renderLayout(string $content): string
    {
        $this->sections['content'] = $content;
        
        $layoutFile = $this->viewPath . '/layouts/' . $this->layout . '.php';

        if (!file_exists($layoutFile)) {
            throw new \RuntimeException("Layout não encontrado: {$this->layout}");
        }

        extract($this->data);
        extract($this->getSharedData());

        ob_start();
        include $layoutFile;
        return ob_get_clean();
    }

    /**
     * Define o layout
     */
    public function extend(string $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * Inicia uma section
     */
    public function section(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    /**
     * Finaliza uma section
     */
    public function endSection(): void
    {
        if (empty($this->sectionStack)) {
            throw new \RuntimeException("Nenhuma section foi iniciada");
        }

        $name = array_pop($this->sectionStack);
        $this->sections[$name] = ob_get_clean();
    }

    /**
     * Renderiza uma section
     */
    public function yield(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /**
     * Inclui uma view parcial
     */
    public function include(string $view, array $data = []): string
    {
        $tempView = new self();
        return $tempView->render($view, array_merge($this->data, $data));
    }

    /**
     * Compartilha dados com todas as views
     */
    private function getSharedData(): array
    {
        return [
            'auth' => auth(),
            'session' => session(),
            'errors' => session()->get('errors', []),
            'old' => session()->get('old', []),
            'success' => session()->get('success'),
            'error' => session()->get('error'),
        ];
    }

    /**
     * Escapa HTML
     */
    public function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

