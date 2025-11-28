<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class CostCenter extends Model
{
    protected string $table = 'cost_centers';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'name', 'user_id'
    ];
    
    /**
     * Retorna subcentros desta categoria
     */
    public function subCostCenters(): array
    {
        return SubCostCenter::where('cost_center_id', $this->id)->get();
    }
}

