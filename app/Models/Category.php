<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Category extends Model
{
    protected string $table = 'categories';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'name', 'type', 'color', 'user_id'
    ];
    
    /**
     * Retorna subcategorias desta categoria
     */
    public function subcategories(): array
    {
        return Subcategory::where('category_id', $this->id)->get();
    }
}

