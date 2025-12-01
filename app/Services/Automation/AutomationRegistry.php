<?php

declare(strict_types=1);

namespace App\Services\Automation;

/**
 * Registro central de componentes de automação
 */
class AutomationRegistry
{
    private static array $triggers = [];
    private static array $conditions = [];
    private static array $actions = [];
    
    /**
     * Registra um trigger
     */
    public static function registerTrigger(BaseTrigger $trigger): void
    {
        self::$triggers[$trigger->getId()] = $trigger;
    }
    
    /**
     * Registra uma condição
     */
    public static function registerCondition(BaseCondition $condition): void
    {
        self::$conditions[$condition->getId()] = $condition;
    }
    
    /**
     * Registra uma ação
     */
    public static function registerAction(BaseAction $action): void
    {
        self::$actions[$action->getId()] = $action;
    }
    
    /**
     * Obtém um trigger
     */
    public static function getTrigger(string $id): ?BaseTrigger
    {
        return self::$triggers[$id] ?? null;
    }
    
    /**
     * Obtém uma condição
     */
    public static function getCondition(string $id): ?BaseCondition
    {
        return self::$conditions[$id] ?? null;
    }
    
    /**
     * Obtém uma ação
     */
    public static function getAction(string $id): ?BaseAction
    {
        return self::$actions[$id] ?? null;
    }
    
    /**
     * Retorna todos os triggers disponíveis
     */
    public static function getAllTriggers(): array
    {
        $triggers = [];
        foreach (self::$triggers as $trigger) {
            $triggers[] = [
                'id' => $trigger->getId(),
                'name' => $trigger->getName(),
                'description' => $trigger->getDescription(),
                'schema' => $trigger->getConfigSchema()
            ];
        }
        return $triggers;
    }
    
    /**
     * Retorna todas as condições disponíveis
     */
    public static function getAllConditions(): array
    {
        $conditions = [];
        foreach (self::$conditions as $condition) {
            $conditions[] = [
                'id' => $condition->getId(),
                'name' => $condition->getName(),
                'description' => $condition->getDescription(),
                'schema' => $condition->getConfigSchema()
            ];
        }
        return $conditions;
    }
    
    /**
     * Retorna todas as ações disponíveis
     */
    public static function getAllActions(): array
    {
        $actions = [];
        foreach (self::$actions as $action) {
            $actions[] = [
                'id' => $action->getId(),
                'name' => $action->getName(),
                'description' => $action->getDescription(),
                'schema' => $action->getConfigSchema()
            ];
        }
        return $actions;
    }
    
    /**
     * Retorna todos os componentes disponíveis
     */
    public static function getAllComponents(): array
    {
        return [
            'triggers' => self::getAllTriggers(),
            'conditions' => self::getAllConditions(),
            'actions' => self::getAllActions()
        ];
    }
}

