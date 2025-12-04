<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;
use App\Models\User;

class UserSite extends Model
{
    protected string $table = 'user_sites';
    protected bool $multiTenant = false; // Não usa user_id automaticamente, mas tem relacionamento
    
    protected array $fillable = [
        'user_id',
        'slug',
        'logo_url',
        'photo_url',
        'bio',
        'twitter_url',
        'youtube_url',
        'linkedin_url',
        'instagram_url',
        'meta_pixel_id',
        'google_analytics_id',
        'newsletter_title',
        'newsletter_description',
        'active'
    ];
    
    protected array $casts = [
        'active' => 'boolean'
    ];
    
    /**
     * Retorna o usuário dono do site
     */
    public function user(): ?User
    {
        return User::find($this->user_id);
    }
    
    /**
     * Retorna os posts do site
     */
    public function posts(): array
    {
        $db = \Core\Database::getInstance();
        $posts = $db->query(
            "SELECT * FROM site_posts WHERE user_site_id = ? AND published = 1 ORDER BY published_at DESC",
            [$this->id]
        );
        
        return array_map(function($row) {
            return SitePost::newInstance($row, true);
        }, $posts);
    }
    
    /**
     * Retorna todos os posts (incluindo não publicados)
     */
    public function allPosts(): array
    {
        $db = \Core\Database::getInstance();
        $posts = $db->query(
            "SELECT * FROM site_posts WHERE user_site_id = ? ORDER BY created_at DESC",
            [$this->id]
        );
        
        return array_map(function($row) {
            return SitePost::newInstance($row, true);
        }, $posts);
    }
    
    /**
     * Retorna assinantes da newsletter
     */
    public function subscribers(): array
    {
        $db = \Core\Database::getInstance();
        $subscribers = $db->query(
            "SELECT * FROM newsletter_subscribers WHERE user_site_id = ? ORDER BY created_at DESC",
            [$this->id]
        );
        
        return array_map(function($row) {
            return NewsletterSubscriber::newInstance($row, true);
        }, $subscribers);
    }
    
    /**
     * Gera slug único baseado no nome do usuário
     */
    public static function generateSlug(string $base): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $base)));
        $db = \Core\Database::getInstance();
        
        $counter = 1;
        $originalSlug = $slug;
        
        while (true) {
            $exists = $db->queryOne(
                "SELECT id FROM user_sites WHERE slug = ?",
                [$slug]
            );
            
            if (!$exists) {
                return $slug;
            }
            
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
    }
}

