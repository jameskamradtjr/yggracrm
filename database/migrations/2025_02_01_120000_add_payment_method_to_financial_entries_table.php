<?php

use Core\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $db = \Core\Database::getInstance();
        
        // Verifica se a coluna payment_method_id já existe
        $columns = $db->query("SHOW COLUMNS FROM `financial_entries` LIKE 'payment_method_id'");
        if (empty($columns)) {
            // Adiciona coluna payment_method_id
            $db->execute("ALTER TABLE `financial_entries` ADD COLUMN `payment_method_id` BIGINT UNSIGNED NULL AFTER `bank_account_id`");
        }
        
        // Verifica se a coluna data_liberacao já existe
        $columns = $db->query("SHOW COLUMNS FROM `financial_entries` LIKE 'data_liberacao'");
        if (empty($columns)) {
            // Adiciona coluna data_liberacao (data quando o dinheiro será liberado)
            $db->execute("ALTER TABLE `financial_entries` ADD COLUMN `data_liberacao` DATE NULL AFTER `due_date`");
        }
        
        // Verifica se o índice já existe antes de adicionar
        $indexes = $db->query("SHOW INDEX FROM `financial_entries` WHERE Key_name = 'financial_entries_payment_method_id_index'");
        if (empty($indexes)) {
            $this->addIndex('financial_entries', 'payment_method_id', 'financial_entries_payment_method_id_index');
        }
        
        $indexes = $db->query("SHOW INDEX FROM `financial_entries` WHERE Key_name = 'financial_entries_data_liberacao_index'");
        if (empty($indexes)) {
            $this->addIndex('financial_entries', 'data_liberacao', 'financial_entries_data_liberacao_index');
        }
        
        // Verifica se a foreign key já existe
        $fks = $db->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'financial_entries' AND CONSTRAINT_NAME = 'financial_entries_payment_method_id_foreign'");
        if (empty($fks)) {
            // Adiciona foreign key
            $db->execute("ALTER TABLE `financial_entries` ADD CONSTRAINT `financial_entries_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL");
        }
    }

    public function down(): void
    {
        $db = \Core\Database::getInstance();
        
        // Remove foreign key
        $db->execute("ALTER TABLE `financial_entries` DROP FOREIGN KEY `financial_entries_payment_method_id_foreign`");
        
        // Remove índices
        $this->dropIndex('financial_entries', 'financial_entries_data_liberacao_index');
        $this->dropIndex('financial_entries', 'financial_entries_payment_method_id_index');
        
        // Remove colunas
        $this->dropColumn('financial_entries', 'data_liberacao');
        $this->dropColumn('financial_entries', 'payment_method_id');
    }
};

