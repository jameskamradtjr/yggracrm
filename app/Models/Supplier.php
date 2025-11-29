<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Supplier extends Model
{
    protected string $table = 'suppliers';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'name', 'fantasy_name', 'cnpj', 'email', 'phone', 'address',
        'additional_info', 'is_client', 'receives_invoice', 'issues_invoice', 'user_id'
    ];
    
    protected array $casts = [
        'is_client' => 'boolean',
        'receives_invoice' => 'boolean',
        'issues_invoice' => 'boolean'
    ];
}

