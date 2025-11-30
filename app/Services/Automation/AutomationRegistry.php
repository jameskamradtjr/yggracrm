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
        $info = $trigger->getInfo();
        self::$triggers[$info['id']] = $trigger;
    }
    
    /**
     * Registra uma condição
     */
    public static function registerCondition(BaseCondition $condition): void
    {
        $info = $condition->getInfo();
        self::$conditions[$info['id']] = $condition;
    }
    
    /**
     * Registra uma ação
     */
    public static function registerAction(BaseAction $action): void
    {
        $info = $action->getInfo();
        self::$actions[$info['id']] = $action;
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
        return array_map(fn($trigger) => $trigger->getInfo(), self::$triggers);
    }
    
    /**
     * Retorna todas as condições disponíveis
     */
    public static function getAllConditions(): array
    {
        return array_map(fn($condition) => $condition->getInfo(), self::$conditions);
    }
    
    /**
     * Retorna todas as ações disponíveis
     */
    public static function getAllActions(): array
    {
        return array_map(fn($action) => $action->getInfo(), self::$actions);
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

