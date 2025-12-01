<?php

declare(strict_types=1);

namespace App\Services\Automation\Actions;

use App\Services\Automation\BaseAction;

class RemoveTagAction extends BaseAction
{
    public function __construct()
    {
        parent::__construct(
            'remove_tag',
            'Remover Tag',
            'Remove uma tag de um item'
        );
    }
    
    public function getConfigSchema(): array
    {
        return [
            [
                'name' => 'entity_type',
                'label' => 'Tipo de Entidade',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => 'lead', 'label' => 'Lead'],
                    ['value' => 'client', 'label' => 'Cliente'],
                    ['value' => 'project_card', 'label' => 'Card de Projeto']
                ]
            ],
            [
                'name' => 'tag_id',
                'label' => 'Tag',
                'type' => 'select',
                'required' => true,
                'options' => [], // Será preenchido dinamicamente
                'loadOptions' => 'tags' // Indica que as opções devem ser carregadas da API de tags
            ]
        ];
    }
    
    public function execute(array $triggerData, array $config): bool
    {
        if (!isset($config['entity_type']) || !isset($config['tag_id'])) {
            return false;
        }
        
        $entityType = $config['entity_type'];
        $tagId = (int)$config['tag_id'];
        
        // Obtém ID da entidade do trigger
        $entityId = $this->getEntityId($entityType, $triggerData);
        if (!$entityId) {
            return false;
        }
        
        try {
            return $this->removeTagFromEntity($entityType, $entityId, $tagId);
        } catch (\Exception $e) {
            error_log("Erro ao remover tag na automação: " . $e->getMessage());
            return false;
        }
    }
    
    private function getEntityId(string $entityType, array $triggerData): ?int
    {
        $fieldMap = [
            'lead' => 'lead_id',
            'client' => 'client_id',
            'project_card' => 'card_id'
        ];
        
        $field = $fieldMap[$entityType] ?? null;
        return $field ? ($triggerData[$field] ?? null) : null;
    }
    
    private function removeTagFromEntity(string $entityType, int $entityId, int $tagId): bool
    {
        $db = \Core\Database::getInstance();
        
        // Para project_card_tags, a estrutura é diferente (tem nome, não tag_id)
        if ($entityType === 'project_card') {
            // Busca a tag para obter nome
            $tag = \App\Models\Tag::find($tagId);
            if (!$tag) {
                return false;
            }
            
            // Remove a tag pelo nome
            $db->execute(
                "DELETE FROM project_card_tags WHERE card_id = ? AND nome = ?",
                [$entityId, $tag->name]
            );
            
            return true;
        }
        
        // Para leads e clients, usa tabelas de relacionamento padrão
        $tableMap = [
            'lead' => 'lead_tags',
            'client' => 'client_tags'
        ];
        
        $table = $tableMap[$entityType] ?? null;
        if (!$table) {
            return false;
        }
        
        // Verifica se a tabela existe
        $tableExists = $db->query("SHOW TABLES LIKE '{$table}'");
        if (empty($tableExists)) {
            return false; // Tabela não existe, não há nada para remover
        }
        
        // Remove a tag
        $idField = $entityType . '_id';
        $db->execute(
            "DELETE FROM `{$table}` WHERE `{$idField}` = ? AND `tag_id` = ?",
            [$entityId, $tagId]
        );
        
        return true;
    }
}

