<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class BankAccount extends Model
{
    protected string $table = 'bank_accounts';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'name', 'type', 'bank_name', 'account_number', 'agency', 'digit',
        'initial_balance', 'current_balance', 'hide_balance', 'alert_email',
        'alert_when_zero', 'user_id'
    ];
    
    protected array $casts = [
        'initial_balance' => 'float',
        'current_balance' => 'float',
        'hide_balance' => 'boolean',
        'alert_when_zero' => 'boolean'
    ];
}

