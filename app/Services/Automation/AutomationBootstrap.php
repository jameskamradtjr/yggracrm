<?php

declare(strict_types=1);

namespace App\Services\Automation;

/**
 * Bootstrap para registrar todos os componentes de automação
 */
class AutomationBootstrap
{
    /**
     * Registra todos os componentes disponíveis
     */
    public static function registerAll(): void
    {
        // Registra Triggers
        AutomationRegistry::registerTrigger(new Triggers\TagAddedTrigger());
        AutomationRegistry::registerTrigger(new Triggers\NewLeadTrigger());
        AutomationRegistry::registerTrigger(new Triggers\CalendarEventTrigger());
        AutomationRegistry::registerTrigger(new Triggers\FinancialEntryTrigger());
        AutomationRegistry::registerTrigger(new Triggers\KanbanCardTrigger());
        AutomationRegistry::registerTrigger(new Triggers\ProposalTrigger());
        AutomationRegistry::registerTrigger(new Triggers\ContractTrigger());
        
        // Registra Conditions
        AutomationRegistry::registerCondition(new Conditions\HasTagCondition());
        
        // Registra Actions
        AutomationRegistry::registerAction(new Actions\SendEmailAction());
        AutomationRegistry::registerAction(new Actions\SendWhatsAppAction());
        AutomationRegistry::registerAction(new Actions\WebhookAction());
        AutomationRegistry::registerAction(new Actions\AssignResponsibleAction());
        AutomationRegistry::registerAction(new Actions\MoveCardAction());
        AutomationRegistry::registerAction(new Actions\DelayAction());
        AutomationRegistry::registerAction(new Actions\AddTagAction());
        AutomationRegistry::registerAction(new Actions\RemoveTagAction());
        AutomationRegistry::registerAction(new Actions\TriggerAutomationAction());
    }
}

