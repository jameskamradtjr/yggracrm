<?php

declare(strict_types=1);

namespace App\Services\Automation;

/**
 * Classe base para condições de automação
 */
abstract class BaseCondition
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
     * Retorna informações da condição para o frontend
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
     * Retorna schema de configuração da condição
     */
    abstract public function getConfigSchema(): array;
    
    /**
     * Avalia a condição
     * Retorna true se a condição for satisfeita
     */
    abstract public function evaluate(array $triggerData, array $config): bool;
    
    /**
     * Configura a condição
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

