<?php

declare(strict_types=1);

namespace App\Services\Automation\Triggers;

use App\Services\Automation\BaseTrigger;

class TagAddedTrigger extends BaseTrigger
{
    public function __construct()
    {
        parent::__construct(
            'tag_added',
            'Quando Adicionar uma Tag',
            'Dispara quando uma tag é adicionada a um item'
        );
    }
    
    public function getConfigSchema(): array
    {
        return [
            [
                'name' => 'tag_id',
                'label' => 'Tag Específica',
                'type' => 'select',
                'required' => false,
                'options' => [] // Será preenchido dinamicamente
            ],
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
            ]
        ];
    }
    
    public function check($data = null): ?array
    {
        if (!$data || !isset($data['tag_id']) || !isset($data['entity_type'])) {
            return null;
        }
        
        $config = $this->getConfig();
        
        // Verifica se é o tipo de entidade correto
        if (isset($config['entity_type']) && $config['entity_type'] !== $data['entity_type']) {
            return null;
        }
        
        // Verifica se é a tag específica (se configurado)
        if (isset($config['tag_id']) && !empty($config['tag_id']) && $config['tag_id'] != $data['tag_id']) {
            return null;
        }
        
        return $data;
    }
}

