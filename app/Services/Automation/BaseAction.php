<?php

declare(strict_types=1);

namespace App\Services\Automation;

/**
 * Classe base para ações de automação
 */
abstract class BaseAction
{
    protected string $id;
    protected string $name;
    protected string $description;
    protected array $config = [];
    
    public function __construct(string $id, string $name, string $description = '')
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
    }
    
    /**
     * Retorna informações da ação para o frontend
     */
    public function getInfo(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'config' => $this->getConfigSchema()
        ];
    }
    
    /**
     * Retorna schema de configuração da ação
     */
    abstract public function getConfigSchema(): array;
    
    /**
     * Executa a ação
     * Retorna true se executada com sucesso
     */
    abstract public function execute(array $triggerData, array $config): bool;
    
    /**
     * Configura a ação
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }
    
    /**
     * Obtém configuração
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}

