<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;
use App\Models\UserSite;

class SitePost extends Model
{
    protected string $table = 'site_posts';
    protected bool $multiTenant = false;
    
    protected array $fillable = [
        'user_site_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'type',
        'external_url',
        'featured_image',
        'likes_count',
        'views_count',
        'published',
        'published_at'
    ];
    
    protected array $casts = [
        'likes_count' => 'integer',
        'views_count' => 'integer',
        'published' => 'boolean'
    ];
    
    /**
     * Retorna o site relacionado
     */
    public function site(): ?UserSite
    {
        return UserSite::find($this->user_site_id);
    }
    
    /**
     * Incrementa visualizações
     */
    public function incrementViews(): void
    {
        $this->views_count = ($this->views_count ?? 0) + 1;
        $this->save();
    }
    
    /**
     * Verifica se um IP já curtiu este post
     */
    public function hasLiked(string $ipAddress): bool
    {
        $db = \Core\Database::getInstance();
        $like = $db->queryOne(
            "SELECT id FROM post_likes WHERE site_post_id = ? AND ip_address = ?",
            [$this->id, $ipAddress]
        );
        
        return !empty($like);
    }
    
    /**
     * Adiciona like
     */
    public function addLike(string $ipAddress, ?string $userAgent = null): bool
    {
        if ($this->hasLiked($ipAddress)) {
            return false; // Já curtiu
        }
        
        $db = \Core\Database::getInstance();
        try {
            $db->execute(
                "INSERT INTO post_likes (site_post_id, ip_address, user_agent, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())",
                [$this->id, $ipAddress, $userAgent]
            );
            
            // Atualiza contador
            $this->likes_count = ($this->likes_count ?? 0) + 1;
            $this->save();
            
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao adicionar like: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove like
     */
    public function removeLike(string $ipAddress): bool
    {
        $db = \Core\Database::getInstance();
        try {
            $db->execute(
                "DELETE FROM post_likes WHERE site_post_id = ? AND ip_address = ?",
                [$this->id, $ipAddress]
            );
            
            // Atualiza contador
            $this->likes_count = max(0, ($this->likes_count ?? 0) - 1);
            $this->save();
            
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao remover like: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gera slug único baseado no título
     */
    public static function generateSlug(string $title, int $userSiteId): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $db = \Core\Database::getInstance();
        
        $counter = 1;
        $originalSlug = $slug;
        
        while (true) {
            $exists = $db->queryOne(
                "SELECT id FROM site_posts WHERE slug = ? AND user_site_id = ?",
                [$slug, $userSiteId]
            );
            
            if (!$exists) {
                return $slug;
            }
            
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
    }
    
    /**
     * Extrai ID do vídeo do YouTube da URL
     */
    public function getYoutubeVideoId(): ?string
    {
        if ($this->type !== 'youtube' || !$this->external_url) {
            return null;
        }
        
        // Suporta vários formatos de URL do YouTube
        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $this->external_url, $matches);
        
        return $matches[1] ?? null;
    }
    
    /**
     * Extrai ID do tweet do Twitter/X da URL
     */
    public function getTwitterTweetId(): ?string
    {
        if ($this->type !== 'twitter' || !$this->external_url) {
            return null;
        }
        
        // Suporta twitter.com e x.com
        preg_match('/(?:twitter\.com|x\.com)\/\w+\/status\/(\d+)/', $this->external_url, $matches);
        
        return $matches[1] ?? null;
    }
}

