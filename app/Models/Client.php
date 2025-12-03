<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;
use App\Models\Lead;
use App\Models\Proposal;
use App\Models\Contact;

class Client extends Model
{
    protected string $table = 'clients';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'tipo', 'nome_razao_social', 'nome_fantasia', 'cpf_cnpj',
        'email', 'telefone', 'celular', 'instagram', 'endereco',
        'numero', 'complemento', 'bairro', 'cidade', 'estado', 'cep',
        'observacoes', 'score', 'user_id', 'foto'
    ];
    
    protected array $casts = [
        'score' => 'integer'
    ];
    
    /**
     * Relacionamento com leads
     */
    public function leads(): array
    {
        return Lead::where('client_id', $this->id)->get();
    }
    
    /**
     * Relacionamento com propostas
     */
    public function proposals(): array
    {
        return Proposal::where('client_id', $this->id)->get();
    }
    
    /**
     * Relacionamento com contatos
     */
    public function contacts(): array
    {
        return Contact::where('client_id', $this->id)
            ->orderBy('data_contato', 'DESC')
            ->orderBy('hora_contato', 'DESC')
            ->get();
    }
    
    /**
     * Retorna tags do cliente
     */
    public function getTags(): array
    {
        $db = \Core\Database::getInstance();
        $tags = $db->query(
            "SELECT t.* FROM tags t 
             INNER JOIN client_tags ct ON t.id = ct.tag_id 
             WHERE ct.client_id = ?",
            [$this->id]
        );
        
        return $tags ?: [];
    }
    
    /**
     * Adiciona uma tag ao cliente
     */
    public function addTag(int $tagId): bool
    {
        $db = \Core\Database::getInstance();
        
        // Verifica se já existe
        $existing = $db->query(
            "SELECT id FROM client_tags WHERE client_id = ? AND tag_id = ?",
            [$this->id, $tagId]
        );
        
        if (!empty($existing)) {
            return true; // Já existe
        }
        
        try {
            $db->execute(
                "INSERT INTO client_tags (client_id, tag_id, created_at, updated_at) VALUES (?, ?, NOW(), NOW())",
                [$this->id, $tagId]
            );
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao adicionar tag ao cliente: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove uma tag do cliente
     */
    public function removeTag(int $tagId): bool
    {
        $db = \Core\Database::getInstance();
        try {
            $db->execute(
                "DELETE FROM client_tags WHERE client_id = ? AND tag_id = ?",
                [$this->id, $tagId]
            );
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao remover tag do cliente: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove todas as tags do cliente
     */
    public function removeAllTags(): bool
    {
        $db = \Core\Database::getInstance();
        try {
            $db->execute("DELETE FROM client_tags WHERE client_id = ?", [$this->id]);
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao remover todas as tags do cliente: " . $e->getMessage());
            return false;
        }
    }
}

