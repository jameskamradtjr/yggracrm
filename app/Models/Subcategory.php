<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Subcategory extends Model
{
    protected string $table = 'subcategories';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'category_id', 'name', 'user_id'
    ];
}

