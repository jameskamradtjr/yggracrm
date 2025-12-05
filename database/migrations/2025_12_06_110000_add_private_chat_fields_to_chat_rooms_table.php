<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->execute("ALTER TABLE `chat_rooms` ADD COLUMN `is_private` TINYINT(1) DEFAULT 0 AFTER `type`");
        $this->execute("ALTER TABLE `chat_rooms` ADD COLUMN `participant1_id` BIGINT UNSIGNED NULL AFTER `is_private`");
        $this->execute("ALTER TABLE `chat_rooms` ADD COLUMN `participant2_id` BIGINT UNSIGNED NULL AFTER `participant1_id`");
        $this->execute("ALTER TABLE `chat_rooms` ADD INDEX `idx_private_participants` (`is_private`, `participant1_id`, `participant2_id`)");
    }

    public function down(): void
    {
        $this->dropIndex('chat_rooms', 'idx_private_participants');
        $this->dropColumn('chat_rooms', 'participant2_id');
        $this->dropColumn('chat_rooms', 'participant1_id');
        $this->dropColumn('chat_rooms', 'is_private');
    }
};

