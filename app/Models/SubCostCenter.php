<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class SubCostCenter extends Model
{
    protected string $table = 'sub_cost_centers';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'cost_center_id', 'name', 'user_id'
    ];
}

