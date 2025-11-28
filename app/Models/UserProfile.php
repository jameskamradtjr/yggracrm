<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Model UserProfile
 * 
 * Perfil estendido do usuário
 */
class UserProfile extends Model
{
    protected string $table = 'user_profiles';
    
    protected array $fillable = [
        'user_id',
        'company_name',
        'cnpj',
        'cpf',
        'address',
        'city',
        'state',
        'zipcode',
        'country',
        'bio',
        'website',
        'social_links',
        'preferences'
    ];

    // Profile não usa multi-tenancy direto pois já tem user_id
    protected bool $multiTenant = false;

    /**
     * Retorna o usuário deste perfil
     */
    public function user(): ?User
    {
        return User::find($this->user_id);
    }

    /**
     * Retorna links sociais decodificados
     */
    public function getSocialLinks(): array
    {
        if (empty($this->social_links)) {
            return [];
        }

        return is_string($this->social_links) 
            ? json_decode($this->social_links, true) 
            : $this->social_links;
    }

    /**
     * Retorna preferências decodificadas
     */
    public function getPreferences(): array
    {
        if (empty($this->preferences)) {
            return [];
        }

        return is_string($this->preferences) 
            ? json_decode($this->preferences, true) 
            : $this->preferences;
    }

    /**
     * Define links sociais
     */
    public function setSocialLinks(array $links): void
    {
        $this->social_links = json_encode($links);
    }

    /**
     * Define preferências
     */
    public function setPreferences(array $preferences): void
    {
        $this->preferences = json_encode($preferences);
    }
}

