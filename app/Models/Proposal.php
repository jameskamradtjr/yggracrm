<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;
use App\Models\Lead;
use App\Models\Client;
use App\Models\Project;

class Proposal extends Model
{
    protected string $table = 'proposals';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'user_id', 'lead_id', 'client_id', 'project_id', 'numero_proposta',
        'titulo', 'identificacao', 'imagem_capa', 'video_youtube', 'objetivo', 'apresentacao',
        'descricao', 'subtotal', 'desconto_valor', 'desconto_percentual', 'total',
        'duracao_dias', 'data_estimada_conclusao', 'disponibilidade_inicio_imediato',
        'forma_pagamento', 'formas_pagamento_aceitas', 'valor',
        'status', 'data_envio', 'data_validade', 'data_visualizacao_cliente',
        'token_publico', 'visualizacoes', 'observacoes', 'is_archived'
    ];
    
    protected array $casts = [
        'valor' => 'float',
        'subtotal' => 'float',
        'desconto_valor' => 'float',
        'desconto_percentual' => 'float',
        'total' => 'float',
        'duracao_dias' => 'integer',
        'disponibilidade_inicio_imediato' => 'boolean',
        'formas_pagamento_aceitas' => 'json',
        'is_archived' => 'boolean'
    ];
    
    /**
     * Retorna tecnologias da proposta
     */
    public function getTechnologies(): array
    {
        $db = \Core\Database::getInstance();
        $technologies = $db->query(
            "SELECT technology FROM proposal_technologies WHERE proposal_id = ? ORDER BY technology ASC",
            [$this->id]
        );
        
        return array_map(function($row) {
            return $row['technology'];
        }, $technologies ?: []);
    }
    
    /**
     * Adiciona tecnologia à proposta
     */
    public function addTechnology(string $technology): bool
    {
        $db = \Core\Database::getInstance();
        try {
            $db->execute(
                "INSERT INTO proposal_technologies (proposal_id, technology, created_at, updated_at) 
                 VALUES (?, ?, NOW(), NOW()) 
                 ON DUPLICATE KEY UPDATE updated_at = NOW()",
                [$this->id, $technology]
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Remove tecnologia da proposta
     */
    public function removeTechnology(string $technology): bool
    {
        $db = \Core\Database::getInstance();
        try {
            $db->execute(
                "DELETE FROM proposal_technologies WHERE proposal_id = ? AND technology = ?",
                [$this->id, $technology]
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Define tecnologias da proposta (substitui todas)
     */
    public function setTechnologies(array $technologies): bool
    {
        $db = \Core\Database::getInstance();
        try {
            // Remove todas as tecnologias existentes
            $db->execute("DELETE FROM proposal_technologies WHERE proposal_id = ?", [$this->id]);
            
            // Adiciona as novas tecnologias
            foreach ($technologies as $tech) {
                $db->execute(
                    "INSERT INTO proposal_technologies (proposal_id, technology, created_at, updated_at) 
                     VALUES (?, ?, NOW(), NOW())",
                    [$this->id, $tech]
                );
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Retorna etapas do roadmap
     */
    public function getRoadmapSteps(): array
    {
        $db = \Core\Database::getInstance();
        $steps = $db->query(
            "SELECT * FROM proposal_roadmap_steps WHERE proposal_id = ? ORDER BY `order` ASC, id ASC",
            [$this->id]
        );
        
        return $steps ?: [];
    }
    
    /**
     * Adiciona etapa ao roadmap
     */
    public function addRoadmapStep(string $title, ?string $description = null, ?string $estimatedDate = null, int $order = 0): ?int
    {
        $db = \Core\Database::getInstance();
        try {
            $db->execute(
                "INSERT INTO proposal_roadmap_steps (proposal_id, title, description, `order`, estimated_date, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
                [$this->id, $title, $description, $order, $estimatedDate]
            );
            return (int)$db->lastInsertId();
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Remove etapa do roadmap
     */
    public function removeRoadmapStep(int $stepId): bool
    {
        $db = \Core\Database::getInstance();
        try {
            $db->execute(
                "DELETE FROM proposal_roadmap_steps WHERE id = ? AND proposal_id = ?",
                [$stepId, $this->id]
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Atualiza etapa do roadmap
     */
    public function updateRoadmapStep(int $stepId, string $title, ?string $description = null, ?string $estimatedDate = null, int $order = 0): bool
    {
        $db = \Core\Database::getInstance();
        try {
            $db->execute(
                "UPDATE proposal_roadmap_steps 
                 SET title = ?, description = ?, `order` = ?, estimated_date = ?, updated_at = NOW()
                 WHERE id = ? AND proposal_id = ?",
                [$title, $description, $order, $estimatedDate, $stepId, $this->id]
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Gera número único da proposta (ex: P2025-02-1)
     */
    public function gerarNumeroProposta(): string
    {
        if ($this->numero_proposta) {
            return $this->numero_proposta;
        }
        
        $ano = date('Y');
        $mes = date('m');
        $userId = $this->user_id ?? auth()->getDataUserId();
        
        // Busca última proposta do mês para este usuário usando query direta
        $db = \Core\Database::getInstance();
        $pattern = "P{$ano}-{$mes}-%";
        $sql = "SELECT * FROM `proposals` WHERE `user_id` = ? AND `numero_proposta` LIKE ? ORDER BY `id` DESC LIMIT 1";
        $result = $db->queryOne($sql, [$userId, $pattern]);
        
        $sequencial = 1;
        if ($result && !empty($result['numero_proposta'])) {
            $parts = explode('-', $result['numero_proposta']);
            if (count($parts) === 3) {
                $sequencial = (int)$parts[2] + 1;
            }
        }
        
        $numero = "P{$ano}-{$mes}-{$sequencial}";
        $this->numero_proposta = $numero;
        return $numero;
    }
    
    /**
     * Gera token público para visualização
     */
    public function gerarTokenPublico(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->token_publico = $token;
        return $token;
    }
    
    /**
     * Relacionamento com lead
     */
    public function lead(): ?Lead
    {
        if (!$this->lead_id) {
            return null;
        }
        return Lead::find($this->lead_id);
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
     * Relacionamento com projeto
     */
    public function project(): ?Project
    {
        if (!$this->project_id) {
            return null;
        }
        return Project::find($this->project_id);
    }
    
    /**
     * Relacionamento com serviços
     */
    public function services(): array
    {
        return ProposalService::where('proposal_id', $this->id)
            ->orderBy('ordem', 'ASC')
            ->get();
    }
    
    /**
     * Relacionamento com condições
     */
    public function conditions(): array
    {
        return ProposalCondition::where('proposal_id', $this->id)
            ->orderBy('ordem', 'ASC')
            ->get();
    }
    
    /**
     * Retorna formas de pagamento (condições do tipo 'pagamento')
     */
    public function getPaymentForms(): array
    {
        $db = \Core\Database::getInstance();
        $forms = $db->query(
            "SELECT * FROM proposal_conditions 
             WHERE proposal_id = ? AND tipo = 'pagamento' 
             ORDER BY ordem ASC, id ASC",
            [$this->id]
        );
        
        return array_map(function($row) {
            return ProposalCondition::newInstance($row, true);
        }, $forms ?: []);
    }
    
    /**
     * Retorna provas sociais (testimonials)
     */
    public function getTestimonials(): array
    {
        $db = \Core\Database::getInstance();
        $testimonials = $db->query(
            "SELECT * FROM proposal_testimonials 
             WHERE proposal_id = ? 
             ORDER BY `order` ASC, id ASC 
             LIMIT 3",
            [$this->id]
        );
        
        if (!$testimonials) {
            return [];
        }
        
        // Retorna como arrays para facilitar uso nas views
        return array_map(function($row) {
            return [
                'id' => (int)$row['id'],
                'client_name' => $row['client_name'],
                'company' => $row['company'],
                'testimonial' => $row['testimonial'],
                'photo_url' => $row['photo_url'],
                'order' => (int)$row['order']
            ];
        }, $testimonials);
    }
    
    /**
     * Adiciona prova social
     */
    public function addTestimonial(string $clientName, string $testimonial, ?string $company = null, ?string $photoUrl = null, int $order = 0): ?int
    {
        $db = \Core\Database::getInstance();
        try {
            // Verifica se já tem 3 testimonials
            $count = $db->queryOne(
                "SELECT COUNT(*) as total FROM proposal_testimonials WHERE proposal_id = ?",
                [$this->id]
            );
            
            if ($count && (int)$count['total'] >= 3) {
                return null; // Limite de 3 atingido
            }
            
            $db->execute(
                "INSERT INTO proposal_testimonials (proposal_id, client_name, company, testimonial, photo_url, `order`, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [$this->id, $clientName, $company, $testimonial, $photoUrl, $order]
            );
            return (int)$db->lastInsertId();
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Remove prova social
     */
    public function removeTestimonial(int $testimonialId): bool
    {
        $db = \Core\Database::getInstance();
        try {
            $db->execute(
                "DELETE FROM proposal_testimonials WHERE id = ? AND proposal_id = ?",
                [$testimonialId, $this->id]
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Calcula totais baseado nos serviços
     */
    public function calcularTotais(): void
    {
        $services = $this->services();
        $subtotal = 0;
        
        foreach ($services as $service) {
            $subtotal += $service->valor_total;
        }
        
        $this->subtotal = $subtotal;
        
        // Aplica desconto
        if ($this->desconto_percentual > 0) {
            $this->desconto_valor = ($subtotal * $this->desconto_percentual) / 100;
        }
        
        $this->total = $subtotal - $this->desconto_valor;
        $this->valor = $this->total; // Mantém compatibilidade
    }
    
    /**
     * Retorna as tags da proposta
     */
    public function getTags(): array
    {
        $db = \Core\Database::getInstance();
        
        $tags = $db->query(
            "SELECT t.* FROM tags t
             INNER JOIN taggables tg ON t.id = tg.tag_id
             WHERE tg.taggable_type = 'Proposal'
             AND tg.taggable_id = ?
             ORDER BY t.name ASC",
            [$this->id]
        );
        
        return $tags ?? [];
    }
    
    /**
     * Adiciona uma tag à proposta
     */
    public function addTag(int $tagId): bool
    {
        $db = \Core\Database::getInstance();
        
        // Verifica se já existe
        $exists = $db->queryOne(
            "SELECT id FROM taggables 
             WHERE taggable_type = 'Proposal' 
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
                 VALUES ('Proposal', ?, ?, NOW(), NOW())",
                [$this->id, $tagId]
            );
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao adicionar tag à proposta: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove uma tag da proposta
     */
    public function removeTag(int $tagId): bool
    {
        $db = \Core\Database::getInstance();
        
        try {
            $db->execute(
                "DELETE FROM taggables 
                 WHERE taggable_type = 'Proposal' 
                 AND taggable_id = ? 
                 AND tag_id = ?",
                [$this->id, $tagId]
            );
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao remover tag da proposta: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove todas as tags da proposta
     */
    public function removeAllTags(): bool
    {
        $db = \Core\Database::getInstance();
        
        try {
            $db->execute(
                "DELETE FROM taggables 
                 WHERE taggable_type = 'Proposal' 
                 AND taggable_id = ?",
                [$this->id]
            );
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao remover todas as tags da proposta: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Define as tags da proposta (substitui todas)
     */
    public function setTags(array $tagIds): bool
    {
        $this->removeAllTags();
        
        foreach ($tagIds as $tagId) {
            if (is_numeric($tagId)) {
                $this->addTag((int)$tagId);
            }
        }
        
        return true;
    }
}
