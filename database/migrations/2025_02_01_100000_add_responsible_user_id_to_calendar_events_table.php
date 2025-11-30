<?php

use Core\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $db = \Core\Database::getInstance();
        
        // Adiciona coluna responsible_user_id
        $db->execute("ALTER TABLE `calendar_events` ADD COLUMN `responsible_user_id` BIGINT UNSIGNED NULL AFTER `user_id`");
        
        // Adiciona índice
        $this->addIndex('calendar_events', 'responsible_user_id', 'calendar_events_responsible_user_id_index');
        
        // Adiciona foreign key
        $db->execute("ALTER TABLE `calendar_events` ADD CONSTRAINT `calendar_events_responsible_user_id_foreign` FOREIGN KEY (`responsible_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL");
    }

    public function down(): void
    {
        $db = \Core\Database::getInstance();
        
        // Remove foreign key
        $db->execute("ALTER TABLE `calendar_events` DROP FOREIGN KEY `calendar_events_responsible_user_id_foreign`");
        
        // Remove índice
        $this->dropIndex('calendar_events', 'calendar_events_responsible_user_id_index');
        
        // Remove coluna
        $this->dropColumn('calendar_events', 'responsible_user_id');
    }
};

