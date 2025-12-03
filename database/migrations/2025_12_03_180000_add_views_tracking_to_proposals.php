<?php

use Core\Migration;
use Core\Database;

return new class extends Migration
{
    public function up(): void
    {
        $db = Database::getInstance();
        
        // Adiciona campo de contagem de visualizações na tabela proposals
        $this->addColumnIfNotExists('proposals', 'visualizacoes', 'INT DEFAULT 0 AFTER data_visualizacao_cliente');
        
        // Cria tabela para log detalhado de visualizações
        $db->execute("
            CREATE TABLE IF NOT EXISTS `proposal_views` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `proposal_id` BIGINT UNSIGNED NOT NULL,
                `ip_address` VARCHAR(45) NULL COMMENT 'IP do visitante',
                `user_agent` TEXT NULL COMMENT 'Navegador/dispositivo',
                `referer` VARCHAR(500) NULL COMMENT 'De onde veio',
                `country` VARCHAR(100) NULL COMMENT 'País (opcional)',
                `city` VARCHAR(100) NULL COMMENT 'Cidade (opcional)',
                `viewed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_proposal_id` (`proposal_id`),
                INDEX `idx_viewed_at` (`viewed_at`),
                INDEX `idx_ip_address` (`ip_address`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        $db = Database::getInstance();
        
        // Remove campo de visualizações
        $this->dropColumnIfExists('proposals', 'visualizacoes');
        
        // Remove tabela de logs
        $db->execute("DROP TABLE IF EXISTS `proposal_views`");
    }
    
    // Helper methods
    private function addColumnIfNotExists(string $table, string $column, string $definition): void
    {
        $db = Database::getInstance();
        $columns = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        if (empty($columns)) {
            $db->execute("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }
    
    private function dropColumnIfExists(string $table, string $column): void
    {
        $db = Database::getInstance();
        $columns = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        if (!empty($columns)) {
            $db->execute("ALTER TABLE `{$table}` DROP COLUMN `{$column}`");
        }
    }
};

