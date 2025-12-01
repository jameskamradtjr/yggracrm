<?php

/**
 * Script para processar delays agendados de automações
 * 
 * Este script deve ser executado periodicamente (ex: a cada minuto via cron)
 * 
 * Uso:
 *   php process_automation_delays.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Core\Application;
use App\Services\Automation\AutomationEngine;

// Inicializa aplicação
$app = Application::getInstance(__DIR__);

// Processa delays agendados
try {
    AutomationEngine::processScheduledDelays();
    echo "Delays processados com sucesso!\n";
} catch (\Exception $e) {
    echo "Erro ao processar delays: " . $e->getMessage() . "\n";
    exit(1);
}

