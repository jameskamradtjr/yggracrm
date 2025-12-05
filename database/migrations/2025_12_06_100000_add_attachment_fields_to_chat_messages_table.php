<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->execute("ALTER TABLE `chat_messages` ADD COLUMN `attachment_s3_key` VARCHAR(500) NULL AFTER `attachment_url`");
        $this->execute("ALTER TABLE `chat_messages` ADD COLUMN `attachment_name` VARCHAR(255) NULL AFTER `attachment_s3_key`");
        $this->execute("ALTER TABLE `chat_messages` ADD COLUMN `attachment_size` BIGINT UNSIGNED NULL AFTER `attachment_name`");
        $this->execute("ALTER TABLE `chat_messages` ADD COLUMN `attachment_mime_type` VARCHAR(100) NULL AFTER `attachment_size`");
    }

    public function down(): void
    {
        $this->dropColumn('chat_messages', 'attachment_mime_type');
        $this->dropColumn('chat_messages', 'attachment_size');
        $this->dropColumn('chat_messages', 'attachment_name');
        $this->dropColumn('chat_messages', 'attachment_s3_key');
    }
};

