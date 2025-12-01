<?php

declare(strict_types=1);

namespace App\Services\Automation\Conditions;

use App\Services\Automation\BaseCondition;

class HasTagCondition extends BaseCondition
{
    public function __construct()
    {
        parent::__construct(
            'has_tag',
            'Tem Tag',
            'Verifica se o item tem uma tag específica'
        );
    }
    
    public function getConfigSchema(): array
    {
        return [
            [
                'name' => 'tag_id',
                'label' => 'Tag',
                'type' => 'select',
                'required' => true,
                'loadOptions' => 'tags' // Carrega tags dinamicamente
            ],
            [
                'name' => 'operator',
                'label' => 'Operador',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => 'has', 'label' => 'Tem'],
                    ['value' => 'not_has', 'label' => 'Não tem']
                ]
            ]
        ];
    }
    
    public function evaluate(array $triggerData, array $config): bool
    {
        if (!isset($config['tag_id']) || !isset($config['operator'])) {
            return false;
        }
        
        $tagId = (int)$config['tag_id'];
        $operator = $config['operator'];
        
        // Busca tags do item
        $tags = $this->getItemTags($triggerData);
        
        $hasTag = in_array($tagId, $tags);
        
        return $operator === 'has' ? $hasTag : !$hasTag;
    }
    
    private function getItemTags(array $triggerData): array
    {
        // Implementação depende do tipo de entidade
        // Por enquanto, retorna array vazio
        // Será implementado quando integrarmos com os models
        return [];
    }
}

