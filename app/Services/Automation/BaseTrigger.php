<?php

declare(strict_types=1);

namespace App\Services\Automation;

/**
 * Classe base para triggers de automação
 */
abstract class BaseTrigger
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
     * Retorna o ID do trigger
     */
    public function getId(): string
    {
        return $this->id;
    }
    
    /**
     * Retorna o nome do trigger
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Retorna a descrição do trigger
     */
    public function getDescription(): string
    {
        return $this->description;
    }
    
    /**
     * Retorna informações do trigger para o frontend
     */
    public function getInfo(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'schema' => $this->getConfigSchema()
        ];
    }
    
    /**
     * Retorna schema de configuração do trigger
     */
    abstract public function getConfigSchema(): array;
    
    /**
     * Verifica se o trigger foi acionado
     * Retorna dados do evento ou null se não foi acionado
     */
    abstract public function check($data = null): ?array;
    
    /**
     * Configura o trigger
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

