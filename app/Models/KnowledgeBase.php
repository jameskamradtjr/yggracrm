<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class KnowledgeBase extends Model
{
    protected string $table = 'knowledge_base';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'titulo',
        'conteudo',
        'resumo',
        'client_id',
        'categoria',
        'status',
        'visualizacoes',
        'user_id'
    ];
    
    protected array $casts = [
        'visualizacoes' => 'integer'
    ];
    
    /**
     * Retorna o cliente relacionado
     */
    public function client(): ?Client
    {
        if (!$this->client_id) {
            return null;
        }
        return Client::find($this->client_id);
    }
    
    /**
     * Retorna o usuário criador
     */
    public function author(): ?User
    {
        if (!$this->user_id) {
            return null;
        }
        return User::find($this->user_id);
    }
    
    /**
     * Retorna as tags do conhecimento
     */
    public function tags(): array
    {
        $db = \Core\Database::getInstance();
        
        $tags = $db->query(
            "SELECT t.* FROM tags t
             INNER JOIN taggables tg ON t.id = tg.tag_id
             WHERE tg.taggable_type = 'KnowledgeBase'
             AND tg.taggable_id = ?
             ORDER BY t.name ASC",
            [$this->id]
        );
        
        return $tags ?? [];
    }
    
    /**
     * Adiciona uma tag ao conhecimento
     */
    public function addTag(int $tagId): bool
    {
        $db = \Core\Database::getInstance();
        
        // Verifica se já existe
        $exists = $db->queryOne(
            "SELECT id FROM taggables 
             WHERE taggable_type = 'KnowledgeBase' 
             AND taggable_id = ? 
             AND tag_id = ?",
            [$this->id, $tagId]
        );
        
        if ($exists) {
            return true;
        }
        
        try {
            $db->execute(
                "INSERT INTO taggables (taggable_type, taggable_id, tag_id, created_at, updated_at) 
                 VALUES ('KnowledgeBase', ?, ?, NOW(), NOW())",
                [$this->id, $tagId]
            );
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao adicionar tag ao conhecimento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove uma tag do conhecimento
     */
    public function removeTag(int $tagId): bool
    {
        $db = \Core\Database::getInstance();
        
        try {
            $db->execute(
                "DELETE FROM taggables 
                 WHERE taggable_type = 'KnowledgeBase' 
                 AND taggable_id = ? 
                 AND tag_id = ?",
                [$this->id, $tagId]
            );
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao remover tag do conhecimento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove todas as tags do conhecimento
     */
    public function removeAllTags(): bool
    {
        $db = \Core\Database::getInstance();
        
        try {
            $db->execute(
                "DELETE FROM taggables 
                 WHERE taggable_type = 'KnowledgeBase' 
                 AND taggable_id = ?",
                [$this->id]
            );
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao remover todas as tags do conhecimento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Incrementa contador de visualizações
     */
    public function incrementViews(): void
    {
        $this->visualizacoes = ($this->visualizacoes ?? 0) + 1;
        $this->save();
    }
}

