<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Tag extends Model
{
    protected string $table = 'tags';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'name', 'color', 'user_id'
    ];
}

