<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;
use App\Models\SitePost;

class PostLike extends Model
{
    protected string $table = 'post_likes';
    protected bool $multiTenant = false;
    
    protected array $fillable = [
        'site_post_id',
        'ip_address',
        'user_agent'
    ];
    
    /**
     * Retorna o post relacionado
     */
    public function post(): ?SitePost
    {
        return SitePost::find($this->site_post_id);
    }
}

