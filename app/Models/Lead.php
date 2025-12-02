<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;
use App\Models\Client;
use App\Models\Proposal;
use App\Models\Contact;
use App\Models\User;

/**
 * Model Lead
 * 
 * Representa um lead capturado pelo quiz
 */
class Lead extends Model
{
    protected string $table = 'leads';
    
    // Habilita multi-tenancy para leads (filtra por user_id)
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'nome',
        'email',
        'telefone',
        'instagram',
        'ramo',
        'faturamento_raw',
        'faturamento_categoria',
        'invest_raw',
        'invest_categoria',
        'objetivo',
        'faz_trafego',
        'tem_software',
        'investimento_software',
        'tipo_sistema',
        'plataforma_app',
        'tags_ai',
        'score_potencial',
        'valor_oportunidade',
        'urgencia',
        'resumo',
        'status_kanban',
        'etapa_funil',
        'origem',
        'origem_conheceu',
        'responsible_user_id',
        'client_id',
        'user_id'
    ];

    protected array $casts = [
        'faz_trafego' => 'boolean',
        'tem_software' => 'boolean',
        'tags_ai' => 'json',
        'score_potencial' => 'integer',
        'valor_oportunidade' => 'float'
    ];

    /**
     * Retorna leads por etapa do funil
     */
    public static function byEtapaFunil(string $etapa): array
    {
        return static::where('etapa_funil', $etapa)->orderBy('score_potencial', 'DESC')->get();
    }

    /**
     * Retorna leads por status kanban (mantido para compatibilidade)
     */
    public static function byStatus(string $status): array
    {
        return static::where('status_kanban', $status)->orderBy('score_potencial', 'DESC')->get();
    }

    /**
     * Atualiza etapa do funil
     */
    public function updateEtapaFunil(string $etapa): bool
    {
        return $this->update(['etapa_funil' => $etapa]);
    }

    /**
     * Atualiza status kanban
     */
    public function updateStatus(string $status): bool
    {
        return $this->update(['status_kanban' => $status]);
    }
    
    /**
     * Relacionamento com cliente
     */
    public function client(): ?Client
    {
        if (!$this->client_id) {
            return null;
        }
        return Client::find($this->client_id);
    }
    
    /**
     * Relacionamento com propostas
     */
    public function proposals(): array
    {
        return Proposal::where('lead_id', $this->id)->get();
    }
    
    /**
     * Relacionamento com contatos
     */
    public function contacts(): array
    {
        return Contact::where('lead_id', $this->id)
            ->orderBy('data_contato', 'DESC')
            ->orderBy('hora_contato', 'DESC')
            ->get();
    }
    
    /**
     * Relacionamento com responsável
     */
    public function responsible(): ?User
    {
        if (!$this->responsible_user_id) {
            return null;
        }
        return User::find($this->responsible_user_id);
    }

    /**
     * Retorna tags da IA como array
     */
    public function getTagsAi(): array
    {
        $value = $this->tags_ai ?? null;
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($value) ? $value : [];
    }
    
    /**
     * Retorna tags do lead
     */
    public function getTags(): array
    {
        $db = \Core\Database::getInstance();
        $tags = $db->query(
            "SELECT t.* FROM tags t 
             INNER JOIN lead_tags lt ON t.id = lt.tag_id 
             WHERE lt.lead_id = ?",
            [$this->id]
        );
        
        return $tags ?: [];
    }
    
    /**
     * Adiciona uma tag ao lead
     */
    public function addTag(int $tagId): bool
    {
        $db = \Core\Database::getInstance();
        
        // Verifica se já existe
        $existing = $db->query(
            "SELECT id FROM lead_tags WHERE lead_id = ? AND tag_id = ?",
            [$this->id, $tagId]
        );
        
        if (!empty($existing)) {
            return true; // Já existe
        }
        
        try {
            $db->execute(
                "INSERT INTO lead_tags (lead_id, tag_id, created_at, updated_at) VALUES (?, ?, NOW(), NOW())",
                [$this->id, $tagId]
            );
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao adicionar tag ao lead: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove uma tag do lead
     */
    public function removeTag(int $tagId): bool
    {
        $db = \Core\Database::getInstance();
        try {
            $db->execute(
                "DELETE FROM lead_tags WHERE lead_id = ? AND tag_id = ?",
                [$this->id, $tagId]
            );
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao remover tag do lead: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove todas as tags do lead
     */
    public function removeAllTags(): bool
    {
        $db = \Core\Database::getInstance();
        try {
            $db->execute("DELETE FROM lead_tags WHERE lead_id = ?", [$this->id]);
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao remover todas as tags do lead: " . $e->getMessage());
            return false;
        }
    }
}

