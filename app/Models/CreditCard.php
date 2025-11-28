<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class CreditCard extends Model
{
    protected string $table = 'credit_cards';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'name', 'brand', 'bank_account_id', 'closing_day', 'due_day',
        'limit', 'alert_limit', 'alert_percentage', 'user_id'
    ];
    
    protected array $casts = [
        'limit' => 'float',
        'alert_limit' => 'boolean',
        'closing_day' => 'integer',
        'due_day' => 'integer',
        'alert_percentage' => 'integer'
    ];
    
    /**
     * Calcula o total gasto no cartão no período atual
     */
    public function getCurrentSpent(): float
    {
        $today = date('Y-m-d');
        $closingDay = $this->closing_day;
        $currentMonth = date('m');
        $currentYear = date('Y');
        
        // Calcula a data de fechamento do período atual
        if (date('d') >= $closingDay) {
            $startDate = date("{$currentYear}-{$currentMonth}-{$closingDay}");
            $endDate = date("{$currentYear}-{$currentMonth}-{$closingDay}", strtotime('+1 month'));
        } else {
            $startDate = date("{$currentYear}-{$currentMonth}-{$closingDay}", strtotime('-1 month'));
            $endDate = date("{$currentYear}-{$currentMonth}-{$closingDay}");
        }
        
        $entries = \App\Models\FinancialEntry::where('credit_card_id', $this->id)
            ->where('competence_date', '>=', $startDate)
            ->where('competence_date', '<', $endDate)
            ->where('type', 'saida')
            ->get();
        
        $total = 0;
        foreach ($entries as $entry) {
            $total += (float) $entry->value;
        }
        
        return $total;
    }
    
    /**
     * Retorna o limite disponível
     */
    public function getAvailableLimit(): float
    {
        return max(0, (float) $this->limit - $this->getCurrentSpent());
    }
}

