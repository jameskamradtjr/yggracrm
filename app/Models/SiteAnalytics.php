<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;
use App\Models\UserSite;
use App\Models\SitePost;

class SiteAnalytics extends Model
{
    protected string $table = 'site_analytics';
    protected bool $multiTenant = false;
    
    protected array $fillable = [
        'user_site_id',
        'site_post_id',
        'event_type',
        'page_path',
        'referrer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'country',
        'city'
    ];
    
    /**
     * Retorna o site relacionado
     */
    public function site(): ?UserSite
    {
        return UserSite::find($this->user_site_id);
    }
    
    /**
     * Retorna o post relacionado (se houver)
     */
    public function post(): ?SitePost
    {
        if (!$this->site_post_id) {
            return null;
        }
        return SitePost::find($this->site_post_id);
    }
    
    /**
     * Detecta tipo de dispositivo baseado no user agent
     */
    public static function detectDeviceType(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'unknown';
        }
        
        $userAgent = strtolower($userAgent);
        
        if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i', $userAgent)) {
            return 'mobile';
        }
        
        if (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
            return 'tablet';
        }
        
        return 'desktop';
    }
    
    /**
     * Detecta navegador baseado no user agent
     */
    public static function detectBrowser(?string $userAgent): ?string
    {
        if (!$userAgent) {
            return null;
        }
        
        $userAgent = strtolower($userAgent);
        
        if (strpos($userAgent, 'chrome') !== false && strpos($userAgent, 'edg') === false) {
            return 'Chrome';
        }
        if (strpos($userAgent, 'firefox') !== false) {
            return 'Firefox';
        }
        if (strpos($userAgent, 'safari') !== false && strpos($userAgent, 'chrome') === false) {
            return 'Safari';
        }
        if (strpos($userAgent, 'edg') !== false) {
            return 'Edge';
        }
        if (strpos($userAgent, 'opera') !== false || strpos($userAgent, 'opr') !== false) {
            return 'Opera';
        }
        
        return 'Other';
    }
    
    /**
     * Detecta sistema operacional baseado no user agent
     */
    public static function detectOS(?string $userAgent): ?string
    {
        if (!$userAgent) {
            return null;
        }
        
        $userAgent = strtolower($userAgent);
        
        if (strpos($userAgent, 'windows') !== false) {
            return 'Windows';
        }
        if (strpos($userAgent, 'mac') !== false) {
            return 'macOS';
        }
        if (strpos($userAgent, 'linux') !== false) {
            return 'Linux';
        }
        if (strpos($userAgent, 'android') !== false) {
            return 'Android';
        }
        if (strpos($userAgent, 'iphone') !== false || strpos($userAgent, 'ipad') !== false) {
            return 'iOS';
        }
        
        return 'Other';
    }
}

