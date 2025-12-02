<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Quiz extends Model
{
    protected string $table = 'quizzes';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'name',
        'description',
        'slug',
        'primary_color',
        'secondary_color',
        'text_color',
        'background_color',
        'button_color',
        'button_text_color',
        'logo_url',
        'welcome_message',
        'completion_message',
        'default_tag_id',
        'active',
        'user_id'
    ];
    
    protected array $casts = [
        'active' => 'boolean'
    ];
    
    /**
     * Retorna os steps do quiz ordenados
     */
    public function steps(): array
    {
        try {
            $steps = QuizStep::where('quiz_id', $this->id)
                ->orderBy('order', 'ASC')
                ->get();
            return is_array($steps) ? $steps : [];
        } catch (\Exception $e) {
            error_log("Erro ao buscar steps do quiz {$this->id}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Retorna a tag padrão
     */
    public function defaultTag(): ?Tag
    {
        if (!$this->default_tag_id) {
            return null;
        }
        return Tag::find($this->default_tag_id);
    }
    
    /**
     * Gera um slug único baseado no nome
     */
    public static function generateSlug(string $name, ?int $userId = null): string
    {
        $baseSlug = strtolower(trim($name));
        $baseSlug = preg_replace('/[^a-z0-9-]/', '-', $baseSlug);
        $baseSlug = preg_replace('/-+/', '-', $baseSlug);
        $baseSlug = trim($baseSlug, '-');
        
        $slug = $baseSlug;
        $counter = 1;
        
        while (true) {
            $existing = static::where('slug', $slug)->first();
            if (!$existing || ($userId && $existing->user_id == $userId)) {
                break;
            }
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Retorna a URL pública do quiz
     */
    public function getPublicUrl(): string
    {
        return url('/quiz/' . $this->slug);
    }
}

