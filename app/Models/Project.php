<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Project extends Model
{
    protected string $table = 'projects';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'titulo', 'descricao', 'status', 'prioridade',
        'data_inicio', 'data_termino_prevista', 'data_termino_real',
        'client_id', 'lead_id', 'responsible_user_id',
        'orcamento', 'custo_real', 'progresso', 'observacoes', 'user_id'
    ];
    
    protected array $casts = [
        'orcamento' => 'float',
        'custo_real' => 'float',
        'progresso' => 'integer'
    ];
    
    /**
     * Busca cliente associado
     */
    public function client()
    {
        if (!$this->client_id) {
            return null;
        }
        return \App\Models\Client::find($this->client_id);
    }
    
    /**
     * Busca lead associado
     */
    public function lead()
    {
        if (!$this->lead_id) {
            return null;
        }
        return \App\Models\Lead::find($this->lead_id);
    }
    
    /**
     * Busca usuário responsável
     */
    public function responsible()
    {
        if (!$this->responsible_user_id) {
            return null;
        }
        return \App\Models\User::find($this->responsible_user_id);
    }
    
    /**
     * Retorna tags do projeto
     */
    public function getTags(): array
    {
        $db = \Core\Database::getInstance();
        $tags = $db->query(
            "SELECT t.* FROM tags t 
             INNER JOIN project_tags pt ON t.id = pt.tag_id 
             WHERE pt.project_id = ?",
            [$this->id]
        );
        
        return $tags ?: [];
    }
    
    /**
     * Adiciona uma tag ao projeto
     */
    public function addTag(int $tagId): bool
    {
        $db = \Core\Database::getInstance();
        
        // Verifica se já existe
        $existing = $db->query(
            "SELECT id FROM project_tags WHERE project_id = ? AND tag_id = ?",
            [$this->id, $tagId]
        );
        
        if (!empty($existing)) {
            return true; // Já existe
        }
        
        try {
            $db->execute(
                "INSERT INTO project_tags (project_id, tag_id, created_at, updated_at) VALUES (?, ?, NOW(), NOW())",
                [$this->id, $tagId]
            );
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao adicionar tag ao projeto: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove uma tag do projeto
     */
    public function removeTag(int $tagId): bool
    {
        $db = \Core\Database::getInstance();
        try {
            $db->execute(
                "DELETE FROM project_tags WHERE project_id = ? AND tag_id = ?",
                [$this->id, $tagId]
            );
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao remover tag do projeto: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove todas as tags do projeto
     */
    public function removeAllTags(): bool
    {
        $db = \Core\Database::getInstance();
        try {
            $db->execute("DELETE FROM project_tags WHERE project_id = ?", [$this->id]);
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao remover todas as tags do projeto: " . $e->getMessage());
            return false;
        }
    }
}

