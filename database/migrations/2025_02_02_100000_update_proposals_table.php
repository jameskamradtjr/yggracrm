<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $db = \Core\Database::getInstance();
        
        // Verifica se user_id já existe e adiciona se não existir
        $columns = $db->query("SHOW COLUMNS FROM `proposals` LIKE 'user_id'");
        if (empty($columns)) {
            $db->execute("ALTER TABLE `proposals` ADD COLUMN `user_id` BIGINT UNSIGNED NOT NULL AFTER `id`");
        }
        
        // Adiciona novos campos (verifica se já existem)
        $this->addColumnIfNotExists('proposals', 'numero_proposta', "VARCHAR(50) NULL AFTER `user_id`");
        $this->addColumnIfNotExists('proposals', 'project_id', "BIGINT UNSIGNED NULL AFTER `client_id`");
        $this->addColumnIfNotExists('proposals', 'identificacao', "VARCHAR(255) NULL AFTER `titulo`");
        $this->addColumnIfNotExists('proposals', 'imagem_capa', "VARCHAR(500) NULL AFTER `identificacao`");
        $this->addColumnIfNotExists('proposals', 'objetivo', "TEXT NULL AFTER `imagem_capa`");
        $this->addColumnIfNotExists('proposals', 'apresentacao', "TEXT NULL AFTER `objetivo`");
        $this->addColumnIfNotExists('proposals', 'subtotal', "DECIMAL(15,2) DEFAULT 0 AFTER `apresentacao`");
        $this->addColumnIfNotExists('proposals', 'desconto_valor', "DECIMAL(15,2) DEFAULT 0 AFTER `subtotal`");
        $this->addColumnIfNotExists('proposals', 'desconto_percentual', "DECIMAL(5,2) DEFAULT 0 AFTER `desconto_valor`");
        $this->addColumnIfNotExists('proposals', 'total', "DECIMAL(15,2) DEFAULT 0 AFTER `desconto_percentual`");
        $this->addColumnIfNotExists('proposals', 'duracao_dias', "INT NULL AFTER `total`");
        $this->addColumnIfNotExists('proposals', 'data_estimada_conclusao', "DATE NULL AFTER `duracao_dias`");
        $this->addColumnIfNotExists('proposals', 'disponibilidade_inicio_imediato', "TINYINT(1) DEFAULT 0 AFTER `data_estimada_conclusao`");
        $this->addColumnIfNotExists('proposals', 'forma_pagamento', "VARCHAR(50) NULL AFTER `disponibilidade_inicio_imediato`");
        $this->addColumnIfNotExists('proposals', 'formas_pagamento_aceitas', "JSON NULL AFTER `forma_pagamento`");
        $this->addColumnIfNotExists('proposals', 'token_publico', "VARCHAR(100) NULL AFTER `formas_pagamento_aceitas`");
        $this->addColumnIfNotExists('proposals', 'data_visualizacao_cliente', "DATETIME NULL AFTER `token_publico`");
        
        // Adiciona índice único para token_publico se não existir
        try {
            $db->execute("ALTER TABLE `proposals` ADD UNIQUE INDEX `proposals_token_publico_unique` (`token_publico`)");
        } catch (\Exception $e) {
            // Ignora se já existir
        }
        
        // Índices
        $this->addIndex('proposals', 'user_id', 'proposals_user_id_index');
        $this->addIndex('proposals', 'numero_proposta', 'proposals_numero_proposta_index');
        $this->addIndex('proposals', 'project_id', 'proposals_project_id_index');
    }
    
    private function addColumnIfNotExists(string $table, string $column, string $definition): void
    {
        $db = \Core\Database::getInstance();
        $columns = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        if (empty($columns)) {
            $db->execute("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }

    public function down(): void
    {
        $db = \Core\Database::getInstance();
        
        $this->dropIndex('proposals', 'proposals_token_publico_index');
        $this->dropIndex('proposals', 'proposals_project_id_index');
        $this->dropIndex('proposals', 'proposals_numero_proposta_index');
        $this->dropIndex('proposals', 'proposals_user_id_index');
        
        $db->execute("ALTER TABLE `proposals` DROP COLUMN `data_visualizacao_cliente`");
        $db->execute("ALTER TABLE `proposals` DROP COLUMN `token_publico`");
        $db->execute("ALTER TABLE `proposals` DROP COLUMN `formas_pagamento_aceitas`");
        $db->execute("ALTER TABLE `proposals` DROP COLUMN `forma_pagamento`");
        $db->execute("ALTER TABLE `proposals` DROP COLUMN `disponibilidade_inicio_imediato`");
        $db->execute("ALTER TABLE `proposals` DROP COLUMN `data_estimada_conclusao`");
        $db->execute("ALTER TABLE `proposals` DROP COLUMN `duracao_dias`");
        $db->execute("ALTER TABLE `proposals` DROP COLUMN `total`");
        $db->execute("ALTER TABLE `proposals` DROP COLUMN `desconto_percentual`");
        $db->execute("ALTER TABLE `proposals` DROP COLUMN `desconto_valor`");
        $db->execute("ALTER TABLE `proposals` DROP COLUMN `subtotal`");
        $db->execute("ALTER TABLE `proposals` DROP COLUMN `apresentacao`");
        $db->execute("ALTER TABLE `proposals` DROP COLUMN `objetivo`");
        $db->execute("ALTER TABLE `proposals` DROP COLUMN `imagem_capa`");
        $db->execute("ALTER TABLE `proposals` DROP COLUMN `identificacao`");
        $db->execute("ALTER TABLE `proposals` DROP COLUMN `project_id`");
        $db->execute("ALTER TABLE `proposals` DROP COLUMN `numero_proposta`");
    }
};

