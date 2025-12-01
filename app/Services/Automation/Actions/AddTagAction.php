<?php

declare(strict_types=1);

namespace App\Services\Automation\Actions;

use App\Services\Automation\BaseAction;

class AddTagAction extends BaseAction
{
    public function __construct()
    {
        parent::__construct(
            'add_tag',
            'Adicionar Tag',
            'Adiciona uma tag a um item'
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
            return $this->addTagToEntity($entityType, $entityId, $tagId);
        } catch (\Exception $e) {
            error_log("Erro ao adicionar tag na automação: " . $e->getMessage());
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
    
    private function addTagToEntity(string $entityType, int $entityId, int $tagId): bool
    {
        $db = \Core\Database::getInstance();
        
        // Para project_card_tags, a estrutura é diferente (tem nome e cor, não tag_id)
        if ($entityType === 'project_card') {
            // Busca a tag para obter nome e cor
            $tag = \App\Models\Tag::find($tagId);
            if (!$tag) {
                return false;
            }
            
            // Verifica se já existe
            $existing = $db->query(
                "SELECT id FROM project_card_tags WHERE card_id = ? AND nome = ?",
                [$entityId, $tag->name]
            );
            
            if (!empty($existing)) {
                return true; // Tag já existe
            }
            
            // Adiciona a tag
            $db->execute(
                "INSERT INTO project_card_tags (card_id, nome, cor, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())",
                [$entityId, $tag->name, $tag->color ?? '#0dcaf0']
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
        
        // Verifica se a tabela existe, se não, cria
        $tableExists = $db->query("SHOW TABLES LIKE '{$table}'");
        if (empty($tableExists)) {
            // Cria a tabela se não existir
            $idField = $entityType . '_id';
            $db->execute("
                CREATE TABLE IF NOT EXISTS `{$table}` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `{$idField}` BIGINT UNSIGNED NOT NULL,
                    `tag_id` BIGINT UNSIGNED NOT NULL,
                    `created_at` TIMESTAMP NULL,
                    `updated_at` TIMESTAMP NULL,
                    UNIQUE KEY `unique_{$entityType}_tag` (`{$idField}`, `tag_id`),
                    INDEX `idx_{$idField}` (`{$idField}`),
                    INDEX `idx_tag_id` (`tag_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
        
        // Verifica se a tag já está associada
        $idField = $entityType . '_id';
        $existing = $db->query(
            "SELECT id FROM `{$table}` WHERE `{$idField}` = ? AND `tag_id` = ?",
            [$entityId, $tagId]
        );
        
        if (!empty($existing)) {
            return true; // Tag já existe, considera sucesso
        }
        
        // Adiciona a tag
        $db->execute(
            "INSERT INTO `{$table}` (`{$idField}`, `tag_id`, `created_at`, `updated_at`) VALUES (?, ?, NOW(), NOW())",
            [$entityId, $tagId]
        );
        
        return true;
    }
}

