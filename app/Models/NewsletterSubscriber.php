<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;
use App\Models\UserSite;

class NewsletterSubscriber extends Model
{
    protected string $table = 'newsletter_subscribers';
    protected bool $multiTenant = false;
    
    protected array $fillable = [
        'user_site_id',
        'email',
        'name',
        'confirmed',
        'confirmed_at'
    ];
    
    protected array $casts = [
        'confirmed' => 'boolean'
    ];
    
    /**
     * Retorna o site relacionado
     */
    public function site(): ?UserSite
    {
        return UserSite::find($this->user_site_id);
    }
}

